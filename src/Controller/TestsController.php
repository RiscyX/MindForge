<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\ActivityLog;
use App\Model\Entity\Language;
use App\Model\Entity\Question;
use App\Model\Entity\Role;
use App\Service\AiGatewayService;
use App\Service\AiQuizPromptService;
use App\Service\AiServiceException;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\View\JsonView;
use Exception;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;
use ZipArchive;
use function Cake\Core\env;

/**
 * Tests Controller
 *
 * @property \App\Model\Table\TestsTable $Tests
 */
class TestsController extends AppController
{
    /**
     * @param \Cake\Event\EventInterface $event
     * @return \Cake\Http\Response|null|void
     */
    public function beforeRender(EventInterface $event)
    {
        parent::beforeRender($event);

        if (!$this->request->getParam('prefix')) {
            $this->viewBuilder()->setLayout('default');

            return;
        }

        $this->viewBuilder()->setLayout('admin');
    }

    /**
     * @return array<\class-string>
     */
    public function viewClasses(): array
    {
        return [JsonView::class];
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $identity = $this->Authentication->getIdentity();
        $roleId = $identity ? (int)$identity->get('role_id') : null;
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        $prefix = (string)$this->request->getParam('prefix', '');
        $isCreatorCatalog = $roleId === Role::CREATOR && $prefix === 'QuizCreator';
        $isCatalog = $prefix === '' || $prefix === 'QuizCreator';

        $langCode = $this->request->getParam('lang');
        $language = $this->fetchTable('Languages')->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        // Fallback
        if (!$language) {
             $language = $this->fetchTable('Languages')->find()->first();
        }

        $query = $this->Tests
            ->find()
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($language) {
                    return $q->where(['CategoryTranslations.language_id' => $language->id ?? null]);
                },
                'Difficulties.DifficultyTranslations' => function ($q) use ($language) {
                    return $q->where(['DifficultyTranslations.language_id' => $language->id ?? null]);
                },
                'TestTranslations' => function ($q) use ($language) {
                    return $q->where(['TestTranslations.language_id' => $language->id ?? null]);
                },
            ])
            ->orderByAsc('Tests.id');

        // Public catalog for regular users only.
        $canSeeAllCatalogTests = in_array($roleId, [Role::ADMIN, Role::CREATOR], true);
        if ($isCatalog && !$isCreatorCatalog && !$canSeeAllCatalogTests) {
            $query->where(['Tests.is_public' => true]);
        }

        $filters = [
            'q' => trim((string)$this->request->getQuery('q', '')),
            'category' => (string)$this->request->getQuery('category', ''),
            'difficulty' => (string)$this->request->getQuery('difficulty', ''),
            'visibility' => (string)$this->request->getQuery('visibility', ''),
            'sort' => (string)$this->request->getQuery('sort', 'latest'),
        ];

        $categoryOptions = [];
        $difficultyOptions = [];
        $catalogPagination = null;
        $recentAttempts = [];
        $topQuizzes = [];
        $topCategories = [];

        if ($isCatalog && !$isCreatorCatalog && $userId !== null) {
            $recentAttempts = $this->fetchTable('TestAttempts')->find()
                ->where([
                    'TestAttempts.user_id' => $userId,
                    'TestAttempts.finished_at IS NOT' => null,
                ])
                ->contain([
                    'Categories.CategoryTranslations' => function ($q) use ($language) {
                        return $q->where(['CategoryTranslations.language_id' => $language->id ?? null]);
                    },
                    'Tests.TestTranslations' => function ($q) use ($language) {
                        return $q->where(['TestTranslations.language_id' => $language->id ?? null]);
                    },
                ])
                ->orderByDesc('TestAttempts.finished_at')
                ->limit(3)
                ->all()
                ->toList();
        }

        if ($isCatalog && !$isCreatorCatalog) {
            $languageId = $language && $language->id !== null ? (int)$language->id : null;
            $publicOnly = !$canSeeAllCatalogTests;
            $topQuizzes = $this->getTopQuizzesForCatalog($languageId, $publicOnly, 3);
            $topCategories = $this->getTopCategoriesForCatalog($languageId, $publicOnly, 5);
        }

        if ($isCreatorCatalog && $userId !== null) {
            $query->where(['Tests.created_by' => $userId]);

            $creatorBase = $this->Tests->find()
                ->where(['Tests.created_by' => $userId]);

            $categoryRows = (clone $creatorBase)
                ->select(['category_id'])
                ->where(['Tests.category_id IS NOT' => null])
                ->distinct(['Tests.category_id'])
                ->enableHydration(false)
                ->all();
            $categoryIds = [];
            foreach ($categoryRows as $row) {
                $cid = (int)($row['category_id'] ?? 0);
                if ($cid > 0) {
                    $categoryIds[] = $cid;
                }
            }

            $difficultyRows = (clone $creatorBase)
                ->select(['difficulty_id'])
                ->where(['Tests.difficulty_id IS NOT' => null])
                ->distinct(['Tests.difficulty_id'])
                ->enableHydration(false)
                ->all();
            $difficultyIds = [];
            foreach ($difficultyRows as $row) {
                $did = (int)($row['difficulty_id'] ?? 0);
                if ($did > 0) {
                    $difficultyIds[] = $did;
                }
            }

            if ($categoryIds) {
                $categoryTranslations = $this->fetchTable('CategoryTranslations')->find()
                    ->select(['category_id', 'name'])
                    ->where([
                        'CategoryTranslations.category_id IN' => $categoryIds,
                        'CategoryTranslations.language_id' => $language->id ?? null,
                    ])
                    ->enableHydration(false)
                    ->all();

                foreach ($categoryTranslations as $row) {
                    $cid = (int)($row['category_id'] ?? 0);
                    $name = trim((string)($row['name'] ?? ''));
                    if ($cid > 0 && $name !== '') {
                        $categoryOptions[$cid] = $name;
                    }
                }
                asort($categoryOptions);
            }

            if ($difficultyIds) {
                $difficultyTranslations = $this->fetchTable('DifficultyTranslations')->find()
                    ->select(['difficulty_id', 'name'])
                    ->where([
                        'DifficultyTranslations.difficulty_id IN' => $difficultyIds,
                        'DifficultyTranslations.language_id' => $language->id ?? null,
                    ])
                    ->enableHydration(false)
                    ->all();

                foreach ($difficultyTranslations as $row) {
                    $did = (int)($row['difficulty_id'] ?? 0);
                    $name = trim((string)($row['name'] ?? ''));
                    if ($did > 0 && $name !== '') {
                        $difficultyOptions[$did] = $name;
                    }
                }
                asort($difficultyOptions);
            }

            if ($filters['category'] !== '' && ctype_digit($filters['category'])) {
                $query->where(['Tests.category_id' => (int)$filters['category']]);
            }

            if ($filters['difficulty'] !== '' && ctype_digit($filters['difficulty'])) {
                $query->where(['Tests.difficulty_id' => (int)$filters['difficulty']]);
            }

            if ($filters['visibility'] === 'public') {
                $query->where(['Tests.is_public' => true]);
            } elseif ($filters['visibility'] === 'private') {
                $query->where(['Tests.is_public' => false]);
            }

            if ($filters['q'] !== '') {
                $qLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
                $query
                    ->matching('TestTranslations', function ($q) use ($language, $qLike) {
                        $query = $q->where([
                            'OR' => [
                                'TestTranslations.title LIKE' => $qLike,
                                'TestTranslations.description LIKE' => $qLike,
                            ],
                        ]);
                        if ($language && $language->id !== null) {
                            $query = $query->where(['TestTranslations.language_id' => (int)$language->id]);
                        }

                        return $query;
                    })
                    ->distinct(['Tests.id']);
            }

            $sort = $filters['sort'];
            if ($sort === 'oldest') {
                $query->orderByAsc('Tests.id');
            } elseif ($sort === 'updated') {
                $query->orderByDesc('Tests.updated_at')->orderByDesc('Tests.id');
            } else {
                $query->orderByDesc('Tests.id');
                $filters['sort'] = 'latest';
            }
        } elseif ($isCatalog) {
            $catalogBase = $this->Tests->find();
            if (!$canSeeAllCatalogTests) {
                $catalogBase->where(['Tests.is_public' => true]);
            }

            $categoryRows = (clone $catalogBase)
                ->select(['category_id'])
                ->where(['Tests.category_id IS NOT' => null])
                ->distinct(['Tests.category_id'])
                ->enableHydration(false)
                ->all();
            $categoryIds = [];
            foreach ($categoryRows as $row) {
                $cid = (int)($row['category_id'] ?? 0);
                if ($cid > 0) {
                    $categoryIds[] = $cid;
                }
            }

            $difficultyRows = (clone $catalogBase)
                ->select(['difficulty_id'])
                ->where(['Tests.difficulty_id IS NOT' => null])
                ->distinct(['Tests.difficulty_id'])
                ->enableHydration(false)
                ->all();
            $difficultyIds = [];
            foreach ($difficultyRows as $row) {
                $did = (int)($row['difficulty_id'] ?? 0);
                if ($did > 0) {
                    $difficultyIds[] = $did;
                }
            }

            if ($categoryIds) {
                $categoryTranslations = $this->fetchTable('CategoryTranslations')->find()
                    ->select(['category_id', 'name'])
                    ->where([
                        'CategoryTranslations.category_id IN' => $categoryIds,
                        'CategoryTranslations.language_id' => $language->id ?? null,
                    ])
                    ->enableHydration(false)
                    ->all();

                foreach ($categoryTranslations as $row) {
                    $cid = (int)($row['category_id'] ?? 0);
                    $name = trim((string)($row['name'] ?? ''));
                    if ($cid > 0 && $name !== '') {
                        $categoryOptions[$cid] = $name;
                    }
                }
                asort($categoryOptions);
            }

            if ($difficultyIds) {
                $difficultyTranslations = $this->fetchTable('DifficultyTranslations')->find()
                    ->select(['difficulty_id', 'name'])
                    ->where([
                        'DifficultyTranslations.difficulty_id IN' => $difficultyIds,
                        'DifficultyTranslations.language_id' => $language->id ?? null,
                    ])
                    ->enableHydration(false)
                    ->all();

                foreach ($difficultyTranslations as $row) {
                    $did = (int)($row['difficulty_id'] ?? 0);
                    $name = trim((string)($row['name'] ?? ''));
                    if ($did > 0 && $name !== '') {
                        $difficultyOptions[$did] = $name;
                    }
                }
                asort($difficultyOptions);
            }

            if ($filters['category'] !== '' && ctype_digit($filters['category'])) {
                $query->where(['Tests.category_id' => (int)$filters['category']]);
            }

            if ($filters['difficulty'] !== '' && ctype_digit($filters['difficulty'])) {
                $query->where(['Tests.difficulty_id' => (int)$filters['difficulty']]);
            }

            if ($filters['q'] !== '') {
                $qLike = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
                $query
                    ->matching('TestTranslations', function ($q) use ($language, $qLike) {
                        $query = $q->where([
                            'OR' => [
                                'TestTranslations.title LIKE' => $qLike,
                                'TestTranslations.description LIKE' => $qLike,
                            ],
                        ]);
                        if ($language && $language->id !== null) {
                            $query = $query->where(['TestTranslations.language_id' => (int)$language->id]);
                        }

                        return $query;
                    })
                    ->distinct(['Tests.id']);
            }

            $sort = $filters['sort'];
            if ($sort === 'oldest') {
                $query->orderByAsc('Tests.id');
            } elseif ($sort === 'updated') {
                $query->orderByDesc('Tests.updated_at')->orderByDesc('Tests.id');
            } else {
                $query->orderByDesc('Tests.id');
                $filters['sort'] = 'latest';
            }
        }

        if ($isCatalog) {
            $perPageOptions = [12, 24, 48];
            $defaultPerPage = 12;
            $perPage = (int)$this->request->getQuery('per_page', $defaultPerPage);
            if (!in_array($perPage, $perPageOptions, true)) {
                $perPage = $defaultPerPage;
            }

            $page = max(1, (int)$this->request->getQuery('page', 1));
            $totalItems = (int)(clone $query)->count();
            $totalPages = max(1, (int)ceil($totalItems / $perPage));
            if ($page > $totalPages) {
                $page = $totalPages;
            }

            $query
                ->limit($perPage)
                ->offset(($page - 1) * $perPage);

            $catalogPagination = [
                'page' => $page,
                'perPage' => $perPage,
                'perPageOptions' => $perPageOptions,
                'totalItems' => $totalItems,
                'totalPages' => $totalPages,
            ];
        }

        $tests = $query->all();

        // Difficulty ordering is admin-configurable, but ids are increasing.
        // Expose a stable difficulty_id -> rank mapping for UI color coding.
        $difficultyRanks = [];
        $difficultyCount = 0;
        try {
            $difficultyIds = $this->fetchTable('Difficulties')->find()
                ->select(['id'])
                ->orderByAsc('id')
                ->enableHydration(false)
                ->all()
                ->toList();

            $difficultyCount = count($difficultyIds);
            $rank = 0;
            foreach ($difficultyIds as $row) {
                $did = (int)($row['id'] ?? 0);
                if ($did > 0) {
                    $difficultyRanks[$did] = $rank;
                    $rank += 1;
                }
            }
        } catch (Throwable) {
            // Keep UI working if difficulties table is unavailable.
        }

        $this->set(compact(
            'tests',
            'roleId',
            'difficultyRanks',
            'difficultyCount',
            'isCreatorCatalog',
            'filters',
            'categoryOptions',
            'difficultyOptions',
            'catalogPagination',
            'recentAttempts',
            'topQuizzes',
            'topCategories',
        ));
    }

    /**
     * @param int|null $languageId
     * @param bool $publicOnly
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    private function getTopQuizzesForCatalog(?int $languageId, bool $publicOnly, int $limit): array
    {
        $limit = max(1, $limit);
        $cacheKey = sprintf('catalog_top_quizzes_l%d_p%d_n%d', (int)($languageId ?? 0), $publicOnly ? 1 : 0, $limit);
        $cached = $this->readTimedCatalogCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $attemptsTable = $this->fetchTable('TestAttempts');
        $attemptCountSubquery = $attemptsTable->find()
            ->select([
                'cnt' => $attemptsTable->find()->func()->count('*'),
            ])
            ->where(function ($exp) {
                return $exp->equalFields('TestAttempts.test_id', 'Tests.id');
            });

        $query = $this->Tests
            ->find()
            ->select([
                'id' => 'Tests.id',
                'attempt_count' => $attemptCountSubquery,
                'title' => 'TestTranslations.title',
                'category_name' => 'CategoryTranslations.name',
                'difficulty_name' => 'DifficultyTranslations.name',
            ])
            ->leftJoinWith('TestTranslations', function ($q) use ($languageId) {
                if ($languageId === null || $languageId <= 0) {
                    return $q;
                }

                return $q->where(['TestTranslations.language_id' => $languageId]);
            })
            ->leftJoinWith('Categories.CategoryTranslations', function ($q) use ($languageId) {
                if ($languageId === null || $languageId <= 0) {
                    return $q;
                }

                return $q->where(['CategoryTranslations.language_id' => $languageId]);
            })
            ->leftJoinWith('Difficulties.DifficultyTranslations', function ($q) use ($languageId) {
                if ($languageId === null || $languageId <= 0) {
                    return $q;
                }

                return $q->where(['DifficultyTranslations.language_id' => $languageId]);
            })
            ->orderByDesc('attempt_count')
            ->orderByDesc('Tests.id')
            ->limit($limit)
            ->enableHydration(false);

        if ($publicOnly) {
            $query->where(['Tests.is_public' => true]);
        }

        $rows = [];
        foreach ($query->all() as $row) {
            $rows[] = [
                'id' => (int)($row['id'] ?? 0),
                'title' => trim((string)($row['title'] ?? '')) ?: (string)__('Untitled quiz'),
                'category_name' => trim((string)($row['category_name'] ?? '')) ?: (string)__('Uncategorized'),
                'difficulty_name' => trim((string)($row['difficulty_name'] ?? '')),
                'attempt_count' => (int)($row['attempt_count'] ?? 0),
            ];
        }

        $this->writeTimedCatalogCache($cacheKey, $rows);

        return $rows;
    }

    /**
     * @param int|null $languageId
     * @param bool $publicOnly
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    private function getTopCategoriesForCatalog(?int $languageId, bool $publicOnly, int $limit): array
    {
        $limit = max(1, $limit);
        $cacheKey = sprintf('catalog_top_categories_l%d_p%d_n%d', (int)($languageId ?? 0), $publicOnly ? 1 : 0, $limit);
        $cached = $this->readTimedCatalogCache($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $query = $this->Tests->find()
            ->select([
                'category_id' => 'Tests.category_id',
                'attempt_count' => $this->Tests->find()->func()->count('TestAttempts.id'),
            ])
            ->innerJoinWith('TestAttempts')
            ->where(['Tests.category_id IS NOT' => null])
            ->groupBy(['Tests.category_id'])
            ->orderByDesc('attempt_count')
            ->orderByDesc('Tests.category_id')
            ->limit($limit)
            ->enableHydration(false);

        if ($publicOnly) {
            $query->where(['Tests.is_public' => true]);
        }

        $rows = $query->all()->toList();
        $categoryIds = [];
        foreach ($rows as $row) {
            $cid = (int)($row['category_id'] ?? 0);
            if ($cid > 0) {
                $categoryIds[] = $cid;
            }
        }

        $names = [];
        if ($categoryIds && $languageId !== null && $languageId > 0) {
            $translations = $this->fetchTable('CategoryTranslations')->find()
                ->select(['category_id', 'name'])
                ->where([
                    'CategoryTranslations.category_id IN' => $categoryIds,
                    'CategoryTranslations.language_id' => $languageId,
                ])
                ->enableHydration(false)
                ->all();

            foreach ($translations as $row) {
                $cid = (int)($row['category_id'] ?? 0);
                $name = trim((string)($row['name'] ?? ''));
                if ($cid > 0 && $name !== '') {
                    $names[$cid] = $name;
                }
            }
        }

        if ($categoryIds) {
            $missing = array_values(array_filter($categoryIds, static fn($id) => !isset($names[$id])));
            if ($missing) {
                $fallbackTranslations = $this->fetchTable('CategoryTranslations')->find()
                    ->select(['category_id', 'name'])
                    ->where(['CategoryTranslations.category_id IN' => $missing])
                    ->orderByAsc('CategoryTranslations.language_id')
                    ->enableHydration(false)
                    ->all();

                foreach ($fallbackTranslations as $row) {
                    $cid = (int)($row['category_id'] ?? 0);
                    if ($cid <= 0 || isset($names[$cid])) {
                        continue;
                    }
                    $name = trim((string)($row['name'] ?? ''));
                    if ($name !== '') {
                        $names[$cid] = $name;
                    }
                }
            }
        }

        $result = [];
        foreach ($rows as $row) {
            $cid = (int)($row['category_id'] ?? 0);
            if ($cid <= 0) {
                continue;
            }
            $result[] = [
                'category_id' => $cid,
                'name' => $names[$cid] ?? (string)__('Uncategorized'),
                'attempt_count' => (int)($row['attempt_count'] ?? 0),
            ];
        }

        $this->writeTimedCatalogCache($cacheKey, $result);

        return $result;
    }

    /**
     * @param string $cacheKey
     * @return array<int, array<string, mixed>>|null
     */
    private function readTimedCatalogCache(string $cacheKey): ?array
    {
        $cached = Cache::read($cacheKey, 'default');
        if (!is_array($cached)) {
            return null;
        }

        $generatedAt = (int)($cached['generated_at'] ?? 0);
        if ($generatedAt <= 0 || (time() - $generatedAt) > 600) {
            return null;
        }

        $data = $cached['data'] ?? null;

        return is_array($data) ? $data : null;
    }

    /**
     * @param string $cacheKey
     * @param array<int, array<string, mixed>> $data
     * @return void
     */
    private function writeTimedCatalogCache(string $cacheKey, array $data): void
    {
        Cache::write($cacheKey, [
            'generated_at' => time(),
            'data' => $data,
        ], 'default');
    }

    /**
     * Start a test as the currently logged-in user.
     *
     * Creates a TestAttempt row and redirects to the take flow.
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null
     */
    public function start(?string $id = null): ?Response
    {
        $this->request->allowMethod(['get', 'post']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;

        if ($userId === null) {
            $redirectUrl = Router::url(['controller' => 'Tests', 'action' => 'start', $id, 'lang' => $lang], true);

            return $this->redirect([
                'controller' => 'Users',
                'action' => 'login',
                'lang' => $lang,
                '?' => ['redirect' => $redirectUrl],
            ]);
        }

        $roleId = (int)($identity->get('role_id') ?? 0);
        if ($roleId === Role::USER) {
            $test = $this->Tests->find()
                ->where(['Tests.id' => $id, 'Tests.is_public' => true])
                ->contain(['Categories', 'Difficulties'])
                ->first();
        } else {
            $test = $this->Tests->find()
                ->where(['Tests.id' => $id])
                ->contain(['Categories', 'Difficulties'])
                ->first();
        }

        if (!$test) {
            $this->Flash->error(__('Quiz not found.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $language = $this->resolveLanguage();
        $languageId = $language ? (int)$language->id : null;

        $questionsCount = (int)$this->fetchTable('Questions')->find()
            ->where([
                'Questions.test_id' => (int)$test->id,
                'Questions.is_active' => true,
            ])
            ->count();

        if ($questionsCount <= 0) {
            $this->Flash->error(__('This quiz has no active questions yet.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $now = FrozenTime::now();
        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->newEmptyEntity();
        $attempt = $attempts->patchEntity($attempt, [
            'user_id' => $userId,
            'test_id' => (int)$test->id,
            'category_id' => $test->category_id,
            'difficulty_id' => $test->difficulty_id,
            'language_id' => $languageId,
            'started_at' => $now,
            'created_at' => $now,
            'total_questions' => $questionsCount,
            'correct_answers' => 0,
        ]);

        if (!$attempts->save($attempt)) {
            $this->Flash->error(__('Could not start the quiz. Please try again.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        return $this->redirect(['action' => 'take', $attempt->id, 'lang' => $lang]);
    }

    /**
     * Render the quiz attempt form.
     *
     * @param string|null $id TestAttempt id.
     * @return \Cake\Http\Response|void
     */
    public function take(?string $id = null)
    {
        $this->request->allowMethod(['get']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to start a quiz.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get($id, contain: ['Tests']);
        if ((int)$attempt->user_id !== $userId) {
            return $this->response->withStatus(403);
        }
        if ($attempt->finished_at !== null) {
            return $this->redirect(['action' => 'result', $attempt->id, 'lang' => $lang]);
        }

        // Use the currently selected UI language for translations.
        // If the attempt is still in progress, persist the user's language switch.
        $language = $this->resolveLanguage();
        $languageId = $language ? (int)$language->id : null;
        if ($languageId !== null && (int)($attempt->language_id ?? 0) !== $languageId) {
            try {
                $attempt->language_id = $languageId;
                $attempts->save($attempt, ['validate' => false]);
            } catch (Throwable) {
                // Non-blocking: keep rendering with the selected language.
            }
        }

        $test = $this->Tests->find()
            ->where(['Tests.id' => (int)$attempt->test_id])
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['CategoryTranslations.language_id' => $languageId]);
                },
                'Difficulties.DifficultyTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['DifficultyTranslations.language_id' => $languageId]);
                },
                'TestTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['TestTranslations.language_id' => $languageId]);
                },
            ])
            ->first();

        $questions = $this->fetchTable('Questions')->find()
            ->where([
                'Questions.test_id' => (int)$attempt->test_id,
                'Questions.is_active' => true,
            ])
            ->orderByAsc('Questions.position')
            ->orderByAsc('Questions.id')
            ->contain([
                'QuestionTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['QuestionTranslations.language_id' => $languageId]);
                },
                'Answers' => function ($q) {
                    return $q->orderByAsc('Answers.position')->orderByAsc('Answers.id');
                },
                'Answers.AnswerTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['AnswerTranslations.language_id' => $languageId]);
                },
            ])
            ->all()
            ->toList();

        $questions = $this->orderQuestionsForAttempt($questions, (int)$attempt->id);

        $this->set(compact('attempt', 'test', 'questions'));
    }

    /**
     * Abort an in-progress quiz attempt.
     *
     * @param string|null $id TestAttempt id.
     * @return \Cake\Http\Response|null
     */
    public function abort(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to manage attempts.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get($id);
        if ((int)$attempt->user_id !== $userId) {
            return $this->response->withStatus(403);
        }

        if ($attempt->finished_at !== null) {
            $this->Flash->error(__('This attempt is already finished and cannot be aborted.'));

            return $this->redirect(['action' => 'result', $attempt->id, 'lang' => $lang]);
        }

        $testId = (int)($attempt->test_id ?? 0);
        if ($attempts->delete($attempt)) {
            $this->Flash->success(__('Attempt aborted.'));
        } else {
            $this->Flash->error(__('Could not abort this attempt. Please try again.'));
        }

        $roleId = (int)($identity->get('role_id') ?? 0);
        if ($testId > 0 && $roleId !== Role::CREATOR) {
            return $this->redirect(['action' => 'details', $testId, 'lang' => $lang]);
        }

        return $this->redirect(['action' => 'index', 'lang' => $lang]);
    }

    /**
     * Submit a quiz attempt.
     *
     * @param string|null $id TestAttempt id.
     * @return \Cake\Http\Response|null
     */
    public function submit(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to submit a quiz.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get($id);
        if ((int)$attempt->user_id !== $userId) {
            return $this->response->withStatus(403);
        }
        if ($attempt->finished_at !== null) {
            return $this->redirect(['action' => 'result', $attempt->id, 'lang' => $lang]);
        }

        $existingCount = (int)$this->fetchTable('TestAttemptAnswers')->find()
            ->where(['test_attempt_id' => (int)$attempt->id])
            ->count();
        if ($existingCount > 0) {
            $this->Flash->error(__('This attempt has already been submitted.'));

            return $this->redirect(['action' => 'result', $attempt->id, 'lang' => $lang]);
        }

        // Submit should follow the currently selected UI language.
        $language = $this->resolveLanguage();
        $languageId = $language ? (int)$language->id : null;
        if ($languageId !== null && (int)($attempt->language_id ?? 0) !== $languageId) {
            try {
                $attempt->language_id = $languageId;
                $attempts->save($attempt, ['validate' => false]);
            } catch (Throwable) {
                // Non-blocking.
            }
        }
        $questions = $this->fetchTable('Questions')->find()
            ->where([
                'Questions.test_id' => (int)$attempt->test_id,
                'Questions.is_active' => true,
            ])
            ->orderByAsc('Questions.position')
            ->orderByAsc('Questions.id')
            ->contain([
                'Answers' => function ($q) {
                    return $q->orderByAsc('Answers.position')->orderByAsc('Answers.id');
                },
                'Answers.AnswerTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['AnswerTranslations.language_id' => $languageId]);
                },
            ])
            ->all()
            ->toList();

        if (!$questions) {
            $this->Flash->error(__('This quiz has no active questions.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $answersInput = $this->request->getData('answers');
        $answersInput = is_array($answersInput) ? $answersInput : [];

        $now = FrozenTime::now();
        $attemptAnswersTable = $this->fetchTable('TestAttemptAnswers');
        $attemptAnswerEntities = [];
        $correct = 0;

        foreach ($questions as $question) {
            $qid = (int)$question->id;
            $questionType = (string)$question->question_type;

            $chosen = $answersInput[$qid] ?? null;
            $chosenAnswerId = null;
            $userAnswerText = null;
            $userAnswerPayload = null;
            $isCorrect = false;

            if ($questionType === Question::TYPE_MATCHING) {
                $leftById = [];
                $rightById = [];
                foreach (array_values((array)($question->answers ?? [])) as $index => $answer) {
                    $aid = (int)$answer->id;
                    $side = trim((string)($answer->match_side ?? ''));
                    if ($side === '') {
                        $side = $index % 2 === 0 ? 'left' : 'right';
                    }
                    $group = (int)($answer->match_group ?? 0);
                    if ($group <= 0) {
                        $group = (int)floor($index / 2) + 1;
                        $answer->match_group = $group;
                    }
                    if ($aid <= 0 || !in_array($side, ['left', 'right'], true)) {
                        continue;
                    }
                    if ($side === 'left') {
                        $leftById[$aid] = $answer;
                    } else {
                        $rightById[$aid] = $answer;
                    }
                }

                $pairsInput = [];
                if (is_array($chosen) && isset($chosen['pairs']) && is_array($chosen['pairs'])) {
                    $pairsInput = $chosen['pairs'];
                }

                $normalizedPairs = [];
                $seenRights = [];
                $allValid = !empty($leftById) && !empty($rightById) && count($leftById) === count($rightById);

                if ($allValid) {
                    foreach ($leftById as $leftId => $leftAnswer) {
                        $rawRight = $pairsInput[(string)$leftId] ?? ($pairsInput[$leftId] ?? null);
                        $rightId = is_numeric($rawRight) ? (int)$rawRight : null;
                        if (
                            $rightId === null
                            || $rightId <= 0
                            || !isset($rightById[$rightId])
                            || isset($seenRights[$rightId])
                        ) {
                            $allValid = false;
                            break;
                        }

                        $seenRights[$rightId] = true;
                        $normalizedPairs[(string)$leftId] = $rightId;

                        $leftGroup = (int)($leftAnswer->match_group ?? 0);
                        $rightGroup = (int)($rightById[$rightId]->match_group ?? 0);
                        if ($leftGroup <= 0 || $rightGroup <= 0 || $leftGroup !== $rightGroup) {
                            $allValid = false;
                            break;
                        }
                    }
                }

                if ($allValid && count($normalizedPairs) === count($leftById)) {
                    $isCorrect = true;
                }

                $encoded = json_encode(['pairs' => $normalizedPairs], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $userAnswerPayload = is_string($encoded) ? $encoded : null;
            } elseif ($questionType === Question::TYPE_TEXT) {
                $userAnswerText = trim((string)$chosen);

                $correctTexts = [];
                $correctTextsRaw = [];
                foreach ($question->answers ?? [] as $answer) {
                    if (!$answer->is_correct) {
                        continue;
                    }
                    $t = '';
                    if (!empty($answer->answer_translations)) {
                        $t = (string)($answer->answer_translations[0]->content ?? '');
                    }
                    if ($t === '' && isset($answer->source_text)) {
                        $t = (string)$answer->source_text;
                    }
                    $t = trim($t);
                    if ($t !== '') {
                        $correctTextsRaw[] = $t;
                        $correctTexts[] = $this->normalizeTextAnswerForCompare($t);
                    }
                }
                if ($userAnswerText !== '' && $correctTexts) {
                    $normalizedUser = $this->normalizeTextAnswerForCompare($userAnswerText);
                    $isCorrect = in_array($normalizedUser, $correctTexts, true);
                    if (!$isCorrect && $normalizedUser !== '' && $userId !== null) {
                        $isCorrect = $this->evaluateTextAnswerWithAi(
                            $userId,
                            $question,
                            $userAnswerText,
                            $correctTextsRaw,
                            $lang,
                        );
                    }
                }
            } else {
                $chosenAnswerId = is_numeric($chosen) ? (int)$chosen : null;
                $answerCorrectMap = [];
                foreach ($question->answers ?? [] as $answer) {
                    $answerCorrectMap[(int)$answer->id] = (bool)$answer->is_correct;
                }
                if ($chosenAnswerId !== null && $chosenAnswerId > 0) {
                    if (!array_key_exists($chosenAnswerId, $answerCorrectMap)) {
                        $chosenAnswerId = null;
                        $isCorrect = false;
                    } else {
                        $isCorrect = (bool)$answerCorrectMap[$chosenAnswerId];
                    }
                }
            }

            if ($isCorrect) {
                $correct += 1;
            }

            $attemptAnswerEntities[] = $attemptAnswersTable->newEntity([
                'test_attempt_id' => (int)$attempt->id,
                'question_id' => $qid,
                'answer_id' => $chosenAnswerId,
                'user_answer_text' => $userAnswerText,
                'user_answer_payload' => $userAnswerPayload,
                'is_correct' => $isCorrect,
                'answered_at' => $now,
            ]);
        }

        $total = count($questions);
        $pct = $total > 0 ? $correct / $total * 100.0 : 0.0;
        $score = number_format($pct, 2, '.', '');

        $conn = $attempts->getConnection();
        $conn->begin();
        try {
            if (!$attemptAnswersTable->saveMany($attemptAnswerEntities)) {
                throw new Exception('Failed to save answers.');
            }

            $attempt->finished_at = $now;
            $attempt->total_questions = $total;
            $attempt->correct_answers = $correct;
            $attempt->score = $score;

            if (!$attempts->save($attempt)) {
                throw new Exception('Failed to finalize attempt.');
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $this->Flash->error(__('Could not submit your answers. Please try again.'));

            return $this->redirect(['action' => 'take', $attempt->id, 'lang' => $lang]);
        }

        return $this->redirect(['action' => 'review', $attempt->id, 'lang' => $lang]);
    }

    /**
     * Show quiz attempt results.
     *
     * @param string|null $id TestAttempt id.
     * @return \Cake\Http\Response|void
     */
    public function result(?string $id = null)
    {
        $this->request->allowMethod(['get']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to view results.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get($id, contain: ['Tests']);
        if ((int)$attempt->user_id !== $userId) {
            return $this->response->withStatus(403);
        }

        $this->set(compact('attempt'));
    }

    /**
     * Review a finished quiz attempt, one question at a time.
     *
     * @param string|null $id TestAttempt id.
     * @return \Cake\Http\Response|void
     */
    public function review(?string $id = null)
    {
        $this->request->allowMethod(['get']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            $this->Flash->error(__('Please log in to view results.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get($id);
        if ((int)$attempt->user_id !== $userId) {
            return $this->response->withStatus(403);
        }

        if ($attempt->finished_at === null) {
            return $this->redirect(['action' => 'take', $attempt->id, 'lang' => $lang]);
        }

        // Review uses the currently selected UI language for translations.
        $language = $this->resolveLanguage();
        $languageId = $language ? (int)$language->id : null;

        $test = $this->Tests->find()
            ->where(['Tests.id' => (int)$attempt->test_id])
            ->contain([
                'TestTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['TestTranslations.language_id' => $languageId]);
                },
            ])
            ->first();

        $questions = $this->fetchTable('Questions')->find()
            ->where([
                'Questions.test_id' => (int)$attempt->test_id,
                'Questions.is_active' => true,
            ])
            ->orderByAsc('Questions.position')
            ->orderByAsc('Questions.id')
            ->contain([
                'QuestionTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['QuestionTranslations.language_id' => $languageId]);
                },
                'Answers' => function ($q) {
                    return $q->orderByAsc('Answers.position')->orderByAsc('Answers.id');
                },
                'Answers.AnswerTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['AnswerTranslations.language_id' => $languageId]);
                },
            ])
            ->all()
            ->toList();

        $questions = $this->orderQuestionsForAttempt($questions, (int)$attempt->id);

        $attemptAnswers = $this->fetchTable('TestAttemptAnswers')->find()
            ->where(['test_attempt_id' => (int)$attempt->id])
            ->all()
            ->indexBy('question_id')
            ->toArray();

        $attemptAnswerIds = [];
        foreach ($attemptAnswers as $attemptAnswer) {
            $idValue = (int)($attemptAnswer->id ?? 0);
            if ($idValue > 0) {
                $attemptAnswerIds[] = $idValue;
            }
        }

        $explanationsByAttemptAnswer = [];
        if ($attemptAnswerIds) {
            $explanations = $this->fetchTable('AttemptAnswerExplanations')->find()
                ->where(['test_attempt_answer_id IN' => $attemptAnswerIds])
                ->all();
            foreach ($explanations as $explanation) {
                $taaId = (int)($explanation->test_attempt_answer_id ?? 0);
                $langOfExplanation = $explanation->language_id !== null ? (int)$explanation->language_id : null;

                if (!isset($explanationsByAttemptAnswer[$taaId])) {
                    $explanationsByAttemptAnswer[$taaId] = $explanation;
                    continue;
                }

                $existingLang = $explanationsByAttemptAnswer[$taaId]->language_id !== null
                    ? (int)$explanationsByAttemptAnswer[$taaId]->language_id
                    : null;
                if ($languageId !== null && $langOfExplanation === $languageId && $existingLang !== $languageId) {
                    $explanationsByAttemptAnswer[$taaId] = $explanation;
                }
            }
        }

        $csrfToken = (string)($this->request->getAttribute('csrfToken') ?? '');
        $this->set(compact(
            'attempt',
            'test',
            'questions',
            'attemptAnswers',
            'explanationsByAttemptAnswer',
            'csrfToken',
        ));
    }

    /**
     * Generate or retrieve AI explanation for a reviewed answer.
     *
     * @param string|null $attemptId Attempt id.
     * @param string|null $questionId Question id.
     * @return \Cake\Http\Response
     */
    public function explainAnswer(?string $attemptId = null, ?string $questionId = null): Response
    {
        $this->request->allowMethod(['post']);
        $this->viewBuilder()->disableAutoLayout();

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        if ($userId === null) {
            return $this->response
                ->withStatus(401)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('Please log in.'),
                ]));
        }

        $attempts = $this->fetchTable('TestAttempts');
        $attempt = $attempts->get((int)$attemptId);
        if ((int)$attempt->user_id !== $userId) {
            return $this->response->withStatus(403);
        }
        if ($attempt->finished_at === null) {
            return $this->response
                ->withStatus(409)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('Explanation is available after submission.'),
                ]));
        }

        $language = $this->resolveLanguage();
        $languageId = $language ? (int)$language->id : null;
        $attemptAnswersTable = $this->fetchTable('TestAttemptAnswers');
        $attemptAnswer = $attemptAnswersTable->find()
            ->where([
                'test_attempt_id' => (int)$attempt->id,
                'question_id' => (int)$questionId,
            ])
            ->first();
        if (!$attemptAnswer) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('Answer record not found.'),
                ]));
        }

        $force = (bool)$this->request->getData('force', false);
        $explanationsTable = $this->fetchTable('AttemptAnswerExplanations');
        $existingExplanation = null;
        $cacheScope = null;
        if ($languageId !== null) {
            $existingExplanation = $explanationsTable->find()
                ->where([
                    'test_attempt_answer_id' => (int)$attemptAnswer->id,
                    'language_id' => $languageId,
                ])
                ->first();
        }
        if ($existingExplanation === null) {
            $existingExplanation = $explanationsTable->find()
                ->where(['test_attempt_answer_id' => (int)$attemptAnswer->id])
                ->orderByDesc('id')
                ->first();
        }

        if ($existingExplanation) {
            $cacheScope = 'attempt';
        }

        if (!$force && $existingExplanation === null) {
            $crossUserQuery = $explanationsTable->find()
                ->innerJoinWith('TestAttemptAnswers', function ($q) use ($questionId, $attemptAnswer) {
                    return $q->where([
                        'TestAttemptAnswers.question_id' => (int)$questionId,
                        'TestAttemptAnswers.is_correct' => (bool)$attemptAnswer->is_correct,
                    ]);
                })
                ->where([
                    'AttemptAnswerExplanations.test_attempt_answer_id !=' => (int)$attemptAnswer->id,
                ])
                ->orderByDesc('AttemptAnswerExplanations.id');

            if ($languageId !== null) {
                $crossUserQuery->where(['AttemptAnswerExplanations.language_id' => $languageId]);
            }

            $crossUserExplanation = $crossUserQuery->first();
            if ($crossUserExplanation !== null) {
                $cacheScope = 'question';

                $now = FrozenTime::now();
                $reuseEntity = $explanationsTable->newEmptyEntity();
                $reuseEntity = $explanationsTable->patchEntity($reuseEntity, [
                    'test_attempt_answer_id' => (int)$attemptAnswer->id,
                    'language_id' => $languageId,
                    'ai_request_id' => $crossUserExplanation->ai_request_id,
                    'source' => 'ai_cache',
                    'explanation_text' => (string)$crossUserExplanation->explanation_text,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $explanationsTable->save($reuseEntity);

                $existingExplanation = $reuseEntity;
            }
        }

        if ($existingExplanation && !$force) {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'cached' => true,
                    'cache_scope' => $cacheScope,
                    'explanation' => (string)$existingExplanation->explanation_text,
                ]));
        }

        $aiExplanationLimit = $this->getAiExplanationLimitInfo($userId);
        if (!$aiExplanationLimit['allowed']) {
            return $this->response
                ->withStatus(429)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'limit_reached' => true,
                    'message' => __('AI explanation limit reached for today. Please try again tomorrow.'),
                    'resets_at' => $aiExplanationLimit['resets_at_iso'],
                    'used' => $aiExplanationLimit['used'],
                    'limit' => $aiExplanationLimit['limit'],
                    'remaining' => $aiExplanationLimit['remaining'],
                ]));
        }

        $question = $this->fetchTable('Questions')->find()
            ->where([
                'Questions.id' => (int)$questionId,
                'Questions.test_id' => (int)$attempt->test_id,
            ])
            ->contain([
                'QuestionTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['QuestionTranslations.language_id' => $languageId]);
                },
                'Answers' => function ($q) {
                    return $q->orderByAsc('Answers.position')->orderByAsc('Answers.id');
                },
                'Answers.AnswerTranslations' => function ($q) use ($languageId) {
                    if ($languageId === null) {
                        return $q;
                    }

                    return $q->where(['AnswerTranslations.language_id' => $languageId]);
                },
            ])
            ->first();
        if (!$question) {
            return $this->response
                ->withStatus(404)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => __('Question not found.'),
                ]));
        }

        $questionText = '';
        $baseExplanation = '';
        if (!empty($question->question_translations)) {
            $questionText = trim((string)($question->question_translations[0]->content ?? ''));
            $baseExplanation = trim((string)($question->question_translations[0]->explanation ?? ''));
        }
        if ($questionText === '') {
            $questionText = __('Question #{0}', (int)$question->id);
        }

        $answerText = static function ($answer): string {
            $txt = '';
            if (!empty($answer->answer_translations)) {
                $txt = (string)($answer->answer_translations[0]->content ?? '');
            }
            if ($txt === '' && isset($answer->source_text)) {
                $txt = (string)$answer->source_text;
            }

            return trim($txt);
        };

        $questionType = (string)$question->question_type;
        $correctInfo = [];
        $userInfo = [];
        $matchingPairDetails = [];
        if ($questionType === Question::TYPE_TEXT) {
            foreach (($question->answers ?? []) as $ans) {
                if (!(bool)$ans->is_correct) {
                    continue;
                }
                $txt = $answerText($ans);
                if ($txt !== '') {
                    $correctInfo[] = $txt;
                }
            }
            $userInfo[] = trim((string)($attemptAnswer->user_answer_text ?? ''));
        } elseif ($questionType === Question::TYPE_MATCHING) {
            $leftMap = [];
            $rightMap = [];
            $expectedRightByLeft = [];
            foreach (($question->answers ?? []) as $ans) {
                $aid = (int)$ans->id;
                $side = trim((string)($ans->match_side ?? ''));
                if ($side === 'left') {
                    $leftMap[$aid] = ['text' => $answerText($ans), 'group' => (int)($ans->match_group ?? 0)];
                } elseif ($side === 'right') {
                    $rightMap[$aid] = ['text' => $answerText($ans), 'group' => (int)($ans->match_group ?? 0)];
                }
            }
            foreach ($leftMap as $leftId => $left) {
                foreach ($rightMap as $rightId => $right) {
                    if ($left['group'] > 0 && $left['group'] === $right['group']) {
                        $correctInfo[] = $left['text'] . ' -> ' . $right['text'];
                        $expectedRightByLeft[(int)$leftId] = [
                            'id' => (int)$rightId,
                            'text' => $right['text'],
                        ];
                        break;
                    }
                }
            }

            $payload = (string)($attemptAnswer->user_answer_payload ?? '');
            $pairs = [];
            if ($payload !== '') {
                $decoded = json_decode($payload, true);
                if (is_array($decoded) && isset($decoded['pairs']) && is_array($decoded['pairs'])) {
                    $pairs = $decoded['pairs'];
                }
            }
            foreach ($pairs as $leftId => $rightId) {
                $leftIdInt = is_numeric($leftId) ? (int)$leftId : 0;
                $rightIdInt = is_numeric($rightId) ? (int)$rightId : 0;
                $leftText = $leftMap[$leftIdInt]['text'] ?? '#' . $leftIdInt;
                $rightText = $rightMap[$rightIdInt]['text'] ?? '#' . $rightIdInt;
                $userInfo[] = $leftText . ' -> ' . $rightText;

                $expected = $expectedRightByLeft[$leftIdInt]['text'] ?? '';
                $matchingPairDetails[] = [
                    'left' => $leftText,
                    'selected' => $rightText,
                    'expected' => $expected,
                    'is_pair_correct' => $expected !== '' && $expected === $rightText,
                ];
            }

            foreach ($leftMap as $leftId => $left) {
                $leftIdInt = (int)$leftId;
                if (array_key_exists((string)$leftIdInt, $pairs) || array_key_exists($leftIdInt, $pairs)) {
                    continue;
                }

                $expectedText = isset($expectedRightByLeft[$leftIdInt])
                    ? (string)$expectedRightByLeft[$leftIdInt]['text']
                    : '';
                $line = $left['text'] . ' -> (no match selected)';
                if ($expectedText !== '') {
                    $line .= ' | expected: ' . $expectedText;
                }
                $userInfo[] = $line;
                $matchingPairDetails[] = [
                    'left' => $left['text'],
                    'selected' => '(no match selected)',
                    'expected' => $expectedText,
                    'is_pair_correct' => false,
                ];
            }
        } else {
            foreach (($question->answers ?? []) as $ans) {
                if ((bool)$ans->is_correct) {
                    $correctInfo[] = $answerText($ans);
                }
                if ($attemptAnswer->answer_id !== null && (int)$attemptAnswer->answer_id === (int)$ans->id) {
                    $userInfo[] = $answerText($ans);
                }
            }
        }

        $langCode = strtolower(trim((string)$lang));
        $outputLanguage = $langCode === 'hu' ? 'Hungarian' : 'English';
        $promptPayload = [
            'question_type' => $questionType,
            'question' => $questionText,
            'base_explanation' => $baseExplanation,
            'user_answer' => $userInfo,
            'correct_answer' => $correctInfo,
            'matching_pair_details' => $matchingPairDetails,
            'is_correct' => (bool)$attemptAnswer->is_correct,
            'language' => $outputLanguage,
        ];
        $promptJson = (string)json_encode($promptPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $systemMessage =
            'You are a quiz tutor assistant. Explain clearly and briefly why '
            . 'the submitted answer is correct or incorrect. '
            . 'Use the provided base_explanation as reference context when available, '
            . 'but tailor the response to the user_answer and correct_answer comparison. '
            . 'If question_type is matching, explain pair-by-pair and explicitly point out '
            . 'missing or incorrect matches. '
            . 'Be concrete, avoid generic wording, and give one actionable tip to improve. '
            .
            'Keep the explanation educational, respectful, and actionable. Return ONLY valid JSON in this format: ' .
            '{"explanation":"..."}';

        try {
            $aiService = new AiGatewayService();
            $aiResponse = $aiService->validateOutput(
                $promptJson,
                $systemMessage,
                0.2,
                ['response_format' => ['type' => 'json_object']],
            );
            if (!$aiResponse->success) {
                throw new Exception((string)($aiResponse->error ?? 'AI request failed.'));
            }
            $responseContent = $aiResponse->content();

            $decoded = json_decode((string)$responseContent, true);
            $explanationText = '';
            if (is_array($decoded)) {
                $explanationText = trim((string)($decoded['explanation'] ?? ''));
            }
            if ($explanationText === '') {
                $explanationText = trim((string)$responseContent);
            }

            if ($explanationText === '') {
                throw new Exception('Empty explanation from AI.');
            }

            $aiRequestsTable = $this->fetchTable('AiRequests');
            $aiRequest = $aiRequestsTable->newEmptyEntity();
            $aiRequestOutputPayload = json_encode(
                ['raw' => (string)$responseContent],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            );
            $aiRequest = $aiRequestsTable->patchEntity($aiRequest, [
                'user_id' => $userId,
                'language_id' => $languageId,
                'source_medium' => 'attempt_review',
                'source_reference' => 'attempt:' . (int)$attempt->id . ':question:' . (int)$question->id,
                'type' => 'attempt_answer_explanation',
                'input_payload' => $promptJson,
                'output_payload' => is_string($aiRequestOutputPayload) ? $aiRequestOutputPayload : '{}',
                'status' => 'success',
            ]);
            $aiRequestsTable->save($aiRequest);

            $now = FrozenTime::now();
            if ($existingExplanation) {
                $explanationEntity = $existingExplanation;
            } else {
                $explanationEntity = $explanationsTable->newEmptyEntity();
            }

            $patchData = [
                'test_attempt_answer_id' => (int)$attemptAnswer->id,
                'language_id' => $languageId,
                'ai_request_id' => $aiRequest?->id,
                'source' => 'ai',
                'explanation_text' => $explanationText,
                'updated_at' => $now,
            ];
            if (!$existingExplanation) {
                $patchData['created_at'] = $now;
            }

            $explanationEntity = $explanationsTable->patchEntity($explanationEntity, $patchData);
            $explanationsTable->save($explanationEntity);

            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'cached' => false,
                    'explanation' => $explanationText,
                ]));
        } catch (Throwable $e) {
            $fallbackExplanation = $this->buildFallbackExplanation(
                $questionType,
                $questionText,
                $userInfo,
                $correctInfo,
                (bool)$attemptAnswer->is_correct,
                $lang,
            );

            $aiRequestsTable = $this->fetchTable('AiRequests');
            $aiRequest = $aiRequestsTable->newEmptyEntity();
            $errorPayload = json_encode([
                'error' => $e->getMessage(),
                'trace_hint' => 'attempt_answer_explanation',
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $aiErrorCode = 'AI_FAILED';
            if ($e instanceof AiServiceException) {
                $aiErrorCode = $e->getErrorCode();
            }
            $aiRequest = $aiRequestsTable->patchEntity($aiRequest, [
                'user_id' => $userId,
                'language_id' => $languageId,
                'source_medium' => 'attempt_review',
                'source_reference' => 'attempt:' . (int)$attempt->id . ':question:' . (int)$question->id,
                'type' => 'attempt_answer_explanation',
                'input_payload' => $promptJson,
                'output_payload' => is_string($errorPayload) ? $errorPayload : '{}',
                'status' => 'failed',
                'error_code' => $aiErrorCode,
                'error_message' => $e->getMessage(),
            ]);
            $aiRequestsTable->save($aiRequest);

            $now = FrozenTime::now();
            $explanationEntity = $existingExplanation ?: $explanationsTable->newEmptyEntity();
            $patchData = [
                'test_attempt_answer_id' => (int)$attemptAnswer->id,
                'language_id' => $languageId,
                'ai_request_id' => $aiRequest?->id,
                'source' => 'fallback',
                'explanation_text' => $fallbackExplanation,
                'updated_at' => $now,
            ];
            if (!$existingExplanation) {
                $patchData['created_at'] = $now;
            }
            $explanationEntity = $explanationsTable->patchEntity($explanationEntity, $patchData);
            $explanationsTable->save($explanationEntity);

            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'cached' => false,
                    'fallback' => true,
                    'explanation' => $fallbackExplanation,
                ]));
        }
    }

    /**
     * @param string $questionType
     * @param string $questionText
     * @param array<int, string> $userInfo
     * @param array<int, string> $correctInfo
     * @param bool $isCorrect
     * @param string $lang
     * @return string
     */
    private function buildFallbackExplanation(
        string $questionType,
        string $questionText,
        array $userInfo,
        array $correctInfo,
        bool $isCorrect,
        string $lang,
    ): string {
        $isHu = strtolower(trim($lang)) === 'hu';
        $userJoined = $userInfo
            ? implode('; ', array_filter(array_map('trim', $userInfo), static fn($v) => $v !== ''))
            : '';
        $correctJoined = $correctInfo
            ? implode('; ', array_filter(array_map('trim', $correctInfo), static fn($v) => $v !== ''))
            : '';

        if ($isHu) {
            $parts = [];
            $parts[] = $isCorrect
                ? 'A valaszod helyes, mert megfelel a kerdes elvart felteteleinek.'
                : 'A valaszod most nem egyezik a vart megoldassal.';
            if ($questionType === Question::TYPE_TEXT) {
                $parts[] = 'Text kerdesnel az elfogadott valaszokkal hasonlitjuk ossze a beirt szoveget '
                    . '(kis-nagybetu fuggetlenul).';
            } elseif ($questionType === Question::TYPE_MATCHING) {
                $parts[] = 'Matchingnel minden parnak helyesen kell osszeallnia, kulonben a kerdes hibas.';
            } else {
                $parts[] = 'Valasztasos kerdesnel a helyesnek jelolt opciok szamitanak jo megoldasnak.';
            }
            if ($userJoined !== '') {
                $parts[] = 'A te valaszod: ' . $userJoined . '.';
            }
            if ($correctJoined !== '') {
                $parts[] = 'A vart megoldas: ' . $correctJoined . '.';
            }

            return implode(' ', $parts);
        }

        $parts = [];
        $parts[] = $isCorrect
            ? 'Your answer is correct because it matches the expected solution criteria.'
            : 'Your answer does not match the expected solution in this case.';
        if ($questionType === Question::TYPE_TEXT) {
            $parts[] = 'For text questions, we compare your input against accepted answers (case-insensitive).';
        } elseif ($questionType === Question::TYPE_MATCHING) {
            $parts[] = 'For matching questions, all pairs must be matched correctly '
                . 'for the question to be marked correct.';
        } else {
            $parts[] = 'For choice-based questions, correctness is determined by the answer options marked as correct.';
        }
        if ($userJoined !== '') {
            $parts[] = 'Your answer: ' . $userJoined . '.';
        }
        if ($correctJoined !== '') {
            $parts[] = 'Expected answer: ' . $correctJoined . '.';
        }

        return implode(' ', $parts);
    }

    /**
     * @param array<int, object> $questions
     * @param int $attemptId
     * @return array<int, object>
     */
    private function orderQuestionsForAttempt(array $questions, int $attemptId): array
    {
        usort($questions, static function ($a, $b) use ($attemptId): int {
            $aId = (int)($a->id ?? 0);
            $bId = (int)($b->id ?? 0);
            $aKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $aId);
            $bKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $bId);

            if ($aKey === $bKey) {
                return $aId <=> $bId;
            }

            return $aKey <=> $bKey;
        });

        return $questions;
    }

    /**
     * Bulk actions for the index table.
     *
     * @return \Cake\Http\Response|null
     */
    public function bulk(): ?Response
    {
        $this->request->allowMethod(['post']);

        $action = (string)$this->request->getData('bulk_action');
        $rawIds = $this->request->getData('ids');
        $ids = is_array($rawIds) ? $rawIds : [];
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn($v) => $v > 0)));

        if (!$ids) {
            $this->Flash->error(__('Select at least one item.'));

            return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
        }

        if ($action !== 'delete') {
            $this->Flash->error(__('Invalid bulk action.'));

            return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
        }

        $deleted = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $entity = $this->Tests->get((string)$id);
                if ($this->Tests->delete($entity)) {
                    $deleted += 1;
                } else {
                    $failed += 1;
                }
            } catch (Throwable) {
                $failed += 1;
            }
        }

        if ($deleted > 0) {
            $this->Flash->success(__('Deleted {0} item(s).', $deleted));
        }
        if ($failed > 0) {
            $this->Flash->error(__('Could not delete {0} item(s).', $failed));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }

    /**
     * Resolve current route language with fallback.
     *
     * @return \App\Model\Entity\Language|null
     */
    private function resolveLanguage(): ?Language
    {
        $langCode = (string)$this->request->getParam('lang', 'en');
        $language = $this->fetchTable('Languages')->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        if ($language) {
            return $language;
        }

        return $this->fetchTable('Languages')->find()->first();
    }

    /**
     * Enrich incoming question payload before patching entities.
     *
     * @param array<string, mixed> $data Form data.
     * @param int|null $userId Authenticated user id.
     * @return void
     */
    private function enrichQuestionsForSave(array &$data, ?int $userId): void
    {
        if (empty($data['questions']) || !is_array($data['questions'])) {
            return;
        }

        $categoryId = !empty($data['category_id']) && is_numeric($data['category_id'])
            ? (int)$data['category_id']
            : null;
        $languageId = (int)($this->resolveLanguage()?->id ?? 0);

        foreach ($data['questions'] as &$question) {
            if (!is_array($question)) {
                continue;
            }

            if ($categoryId !== null) {
                $question['category_id'] = $categoryId;
            }
            if (empty($question['id']) && $userId !== null) {
                $question['created_by'] = $userId;
            }

            if (empty($question['answers']) || !is_array($question['answers'])) {
                continue;
            }

            $questionSourceType = (string)($question['source_type'] ?? 'human');
            $position = 1;

            foreach ($question['answers'] as &$answer) {
                if (!is_array($answer)) {
                    continue;
                }

                $answer['position'] = $position;
                $position += 1;

                if (empty($answer['source_type'])) {
                    $answer['source_type'] = $questionSourceType;
                }

                $sourceText = '';
                $translations = $answer['answer_translations'] ?? null;
                if (is_array($translations) && $translations) {
                    if ($languageId > 0 && isset($translations[$languageId]) && is_array($translations[$languageId])) {
                        $sourceText = trim((string)($translations[$languageId]['content'] ?? ''));
                    }
                    if ($sourceText === '') {
                        foreach ($translations as $translation) {
                            if (!is_array($translation)) {
                                continue;
                            }
                            $candidate = trim((string)($translation['content'] ?? ''));
                            if ($candidate !== '') {
                                $sourceText = $candidate;
                                break;
                            }
                        }
                    }

                    foreach ($translations as &$translation) {
                        if (!is_array($translation)) {
                            continue;
                        }
                        if (empty($translation['source_type'])) {
                            $translation['source_type'] = (string)$answer['source_type'];
                        }
                        if ($userId !== null && empty($translation['created_by'])) {
                            $translation['created_by'] = $userId;
                        }
                    }
                    unset($translation);
                }

                if ($sourceText !== '') {
                    $answer['source_text'] = $sourceText;
                }
            }
            unset($answer);
        }
        unset($question);
    }

    /**
     * Build aggregate quiz statistics from attempts.
     *
     * @param int $testId Test id.
     * @return array<string, float|int>
     */
    private function buildQuizStats(int $testId): array
    {
        $attemptsTable = $this->fetchTable('TestAttempts');
        $quizAttempts = $attemptsTable->find()->where(['TestAttempts.test_id' => $testId]);

        $attemptsCount = (int)(clone $quizAttempts)->count();
        $finishedCount = (int)(clone $quizAttempts)
            ->where(['TestAttempts.finished_at IS NOT' => null])
            ->count();

        $finishedWithScore = (clone $quizAttempts)
            ->where([
                'TestAttempts.finished_at IS NOT' => null,
                'TestAttempts.score IS NOT' => null,
            ]);

        $avgScoreRow = (clone $finishedWithScore)
            ->select(['avg_score' => $attemptsTable->find()->func()->avg('TestAttempts.score')])
            ->enableHydration(false)
            ->first();

        $bestScoreRow = (clone $finishedWithScore)
            ->select(['best_score' => $attemptsTable->find()->func()->max('TestAttempts.score')])
            ->enableHydration(false)
            ->first();

        $correctnessRow = (clone $quizAttempts)
            ->where([
                'TestAttempts.finished_at IS NOT' => null,
                'TestAttempts.correct_answers IS NOT' => null,
                'TestAttempts.total_questions IS NOT' => null,
                'TestAttempts.total_questions >' => 0,
            ])
            ->select([
                'sum_correct' => $attemptsTable->find()->func()->sum('TestAttempts.correct_answers'),
                'sum_total' => $attemptsTable->find()->func()->sum('TestAttempts.total_questions'),
            ])
            ->enableHydration(false)
            ->first();

        $uniqueUsers = (int)(clone $quizAttempts)
            ->select(['user_id'])
            ->distinct(['user_id'])
            ->count();

        $sumCorrect = (float)($correctnessRow['sum_correct'] ?? 0);
        $sumTotal = (float)($correctnessRow['sum_total'] ?? 0);

        return [
            'attempts' => $attemptsCount,
            'finished' => $finishedCount,
            'completionRate' => $attemptsCount > 0 ? $finishedCount / $attemptsCount * 100.0 : 0.0,
            'avgScore' => (float)($avgScoreRow['avg_score'] ?? 0.0),
            'bestScore' => (float)($bestScoreRow['best_score'] ?? 0.0),
            'avgCorrectRate' => $sumTotal > 0 ? $sumCorrect / $sumTotal * 100.0 : 0.0,
            'uniqueUsers' => $uniqueUsers,
        ];
    }

    /**
     * View method
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $identity = $this->Authentication->getIdentity();
        $roleId = $identity ? (int)$identity->get('role_id') : null;

        // Public/user-facing details page for non-creator users on non-prefixed routes.
        if (!$this->request->getParam('prefix') && $roleId !== Role::CREATOR) {
            $lang = (string)$this->request->getParam('lang', 'en');
            $language = $this->resolveLanguage();
            $languageId = $language ? (int)$language->id : null;

            $test = $this->Tests->find()
                ->where([
                    'Tests.id' => $id,
                    'Tests.is_public' => true,
                ])
                ->contain([
                    'Categories.CategoryTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['CategoryTranslations.language_id' => $languageId]) : $q;
                    },
                    'Difficulties.DifficultyTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['DifficultyTranslations.language_id' => $languageId]) : $q;
                    },
                    'TestTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['TestTranslations.language_id' => $languageId]) : $q;
                    },
                ])
                ->first();

            if (!$test) {
                $this->Flash->error(__('Quiz not found.'));

                return $this->redirect(['action' => 'index', 'lang' => $lang]);
            }

            $userId = $identity ? (int)$identity->getIdentifier() : null;
            $attemptsCount = 0;
            $finishedCount = 0;
            $bestAttempt = null;
            $lastAttempt = null;
            $attemptHistory = [];

            if ($userId !== null) {
                $attemptsTable = $this->fetchTable('TestAttempts');

                $attemptsCount = (int)$attemptsTable->find()
                    ->where([
                        'TestAttempts.user_id' => $userId,
                        'TestAttempts.test_id' => (int)$test->id,
                    ])
                    ->count();

                $finishedCount = (int)$attemptsTable->find()
                    ->where([
                        'TestAttempts.user_id' => $userId,
                        'TestAttempts.test_id' => (int)$test->id,
                        'TestAttempts.finished_at IS NOT' => null,
                    ])
                    ->count();

                $bestAttempt = $attemptsTable->find()
                    ->where([
                        'TestAttempts.user_id' => $userId,
                        'TestAttempts.test_id' => (int)$test->id,
                        'TestAttempts.finished_at IS NOT' => null,
                    ])
                    ->orderByDesc('TestAttempts.score')
                    ->orderByDesc('TestAttempts.correct_answers')
                    ->orderByDesc('TestAttempts.finished_at')
                    ->orderByDesc('TestAttempts.id')
                    ->first();

                $lastAttempt = $attemptsTable->find()
                    ->where([
                        'TestAttempts.user_id' => $userId,
                        'TestAttempts.test_id' => (int)$test->id,
                        'TestAttempts.finished_at IS NOT' => null,
                    ])
                    ->orderByDesc('TestAttempts.finished_at')
                    ->orderByDesc('TestAttempts.id')
                    ->first();

                $attemptHistory = $attemptsTable->find()
                    ->where([
                        'TestAttempts.user_id' => $userId,
                        'TestAttempts.test_id' => (int)$test->id,
                    ])
                    ->orderByDesc('TestAttempts.started_at')
                    ->orderByDesc('TestAttempts.id')
                    ->limit(20)
                    ->all();
            }

            $this->set(compact(
                'test',
                'attemptsCount',
                'finishedCount',
                'bestAttempt',
                'lastAttempt',
                'attemptHistory',
            ));
            $this->viewBuilder()->setTemplate('catalog_view');

            return;
        }

        if (!$this->request->getParam('prefix') && $roleId === Role::CREATOR) {
            $lang = (string)$this->request->getParam('lang', 'en');
            $language = $this->resolveLanguage();
            $languageId = $language ? (int)$language->id : null;
            $userId = $identity ? (int)$identity->getIdentifier() : null;

            $test = $this->Tests->find()
                ->where([
                    'Tests.id' => $id,
                    'Tests.created_by' => $userId,
                ])
                ->contain([
                    'Categories.CategoryTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['CategoryTranslations.language_id' => $languageId]) : $q;
                    },
                    'Difficulties.DifficultyTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['DifficultyTranslations.language_id' => $languageId]) : $q;
                    },
                    'TestTranslations' => function ($q) use ($languageId) {
                        return $languageId ? $q->where(['TestTranslations.language_id' => $languageId]) : $q;
                    },
                ])
                ->first();

            if (!$test) {
                $this->Flash->error(__('Quiz not found.'));

                return $this->redirect(['action' => 'index', 'lang' => $lang]);
            }

            $stats = $this->buildQuizStats((int)$test->id);

            $this->set(compact('test', 'stats'));
            $this->viewBuilder()->setTemplate('creator_view');

            return;
        }

        $test = $this->Tests->get($id, contain: [
            'Categories',
            'Difficulties',
            'AiRequests',
            'Questions',
            'TestAttempts',
            'TestTranslations',
            'UserFavoriteTests',
        ]);
        $this->set(compact('test'));
    }

    /**
     * Public/user-facing details page.
     *
     * Friendly URL: /{lang}/tests/{id}/details
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null|void
     */
    public function details(?string $id = null)
    {
        return $this->view($id);
    }

    /**
     * Quiz statistics page for admins/creators.
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null|void
     */
    public function stats(?string $id = null)
    {
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        $roleId = $identity ? (int)$identity->get('role_id') : null;
        $lang = (string)$this->request->getParam('lang', 'en');

        if ($userId === null || !in_array($roleId, [Role::ADMIN, Role::CREATOR], true)) {
            $this->Flash->error(__('You do not have access to quiz statistics.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $language = $this->resolveLanguage();
        $languageId = $language ? (int)$language->id : null;

        $conditions = ['Tests.id' => $id];
        if ($roleId === Role::CREATOR) {
            $conditions['Tests.created_by'] = $userId;
        }

        $test = $this->Tests->find()
            ->where($conditions)
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($languageId) {
                    return $languageId ? $q->where(['CategoryTranslations.language_id' => $languageId]) : $q;
                },
                'Difficulties.DifficultyTranslations' => function ($q) use ($languageId) {
                    return $languageId ? $q->where(['DifficultyTranslations.language_id' => $languageId]) : $q;
                },
                'TestTranslations' => function ($q) use ($languageId) {
                    return $languageId ? $q->where(['TestTranslations.language_id' => $languageId]) : $q;
                },
            ])
            ->first();

        if (!$test) {
            $this->Flash->error(__('Quiz not found.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $stats = $this->buildQuizStats((int)$test->id);
        $this->set(compact('test', 'stats'));
        $this->viewBuilder()->setTemplate('creator_view');
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $test = $this->Tests->newEmptyEntity();
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $identityUserId = $this->Authentication->getIdentity()?->getIdentifier();
            $userId = is_numeric($identityUserId) ? (int)$identityUserId : null;
            $data['created_by'] = $userId;

            if (!empty($data['questions']) && is_array($data['questions'])) {
                $data['number_of_questions'] = count($data['questions']);
                $this->enrichQuestionsForSave($data, $userId);
            }

            $test = $this->Tests->patchEntity($test, $data, [
                'associated' => [
                    'Questions' => ['associated' => [
                        'Answers' => ['associated' => ['AnswerTranslations']],
                        'QuestionTranslations',
                    ]],
                    'TestTranslations',
                ],
            ]);

            if ($this->Tests->save($test)) {
                $this->Flash->success(__('The test has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            // Log validation errors
            // \Cake\Log\Log::error('Test save failed: ' . json_encode($test->getErrors()));
            $this->Flash->error(__('The test could not be saved. Please, try again.'));
            if (Configure::read('debug')) {
                 $this->Flash->error(json_encode($test->getErrors()));
            }
        }

        $langCode = $this->request->getParam('lang');
        $language = $this->fetchTable('Languages')->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        // Fallback if exact/fuzzy match fails (prevention for null crash)
        if (!$language) {
             $language = $this->fetchTable('Languages')->find()->first();
        }

        $languageId = $language ? (int)$language->id : null;

        $categories = [];
        $difficulties = [];

        if ($languageId) {
            $categories = $this->Tests->Categories->CategoryTranslations->find('list', [
                'keyField' => 'category_id',
                'valueField' => 'name',
            ])
            ->where(['language_id' => $languageId])
            ->all();

            $difficulties = $this->Tests->Difficulties->DifficultyTranslations->find('list', [
                'keyField' => 'difficulty_id',
                'valueField' => 'name',
            ])
            ->where(['language_id' => $languageId])
            ->all();
        }

        $languages = $this->fetchTable('Languages')->find('list')->all();
        $languagesMeta = $this->fetchTable('Languages')->find()
            ->select(['id', 'code', 'name'])
            ->orderByAsc('id')
            ->enableHydration(false)
            ->toArray();
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        $aiGenerationLimit = $this->getAiGenerationLimitInfo($userId);
        $currentLanguageId = $languageId;
        $this->set(compact(
            'test',
            'categories',
            'difficulties',
            'languages',
            'languagesMeta',
            'aiGenerationLimit',
            'currentLanguageId',
        ));
    }

    /**
     * Edit method
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $test = $this->Tests->get($id, contain: [
            'TestTranslations',
            'Questions' => function ($q) {
                return $q->orderByAsc('position')
                    ->contain([
                        'QuestionTranslations',
                        'Answers' => function ($q) {
                            return $q->orderByAsc('id')
                                ->contain(['AnswerTranslations']);
                        },
                    ]);
            },
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            $identityUserId = $this->Authentication->getIdentity()?->getIdentifier();
            $userId = is_numeric($identityUserId) ? (int)$identityUserId : null;

            if (!empty($data['questions']) && is_array($data['questions'])) {
                $data['number_of_questions'] = count($data['questions']);
                $this->enrichQuestionsForSave($data, $userId);
            }

            // Set save strategy to replace to handle deletions
            $this->Tests->Questions->setSaveStrategy('replace');
            $this->Tests->Questions->getTarget()->Answers->setSaveStrategy('replace');

            $test = $this->Tests->patchEntity($test, $data, [
                'associated' => [
                    'Questions' => ['associated' => [
                        'Answers' => ['associated' => ['AnswerTranslations']],
                        'QuestionTranslations',
                    ]],
                    'TestTranslations',
                ],
            ]);

            if ($this->Tests->save($test)) {
                $this->Flash->success(__('The test has been saved.'));

                return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
            }
            $this->Flash->error(__('The test could not be saved. Please, try again.'));
            if (Configure::read('debug')) {
                 $this->Flash->error(json_encode($test->getErrors()));
            }
        }

        $langCode = $this->request->getParam('lang');
        $language = $this->fetchTable('Languages')->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        // Fallback if exact/fuzzy match fails
        if (!$language) {
             $language = $this->fetchTable('Languages')->find()->first();
        }

        $languageId = $language ? (int)$language->id : null;

        $categories = [];
        $difficulties = [];

        if ($languageId) {
            $categories = $this->Tests->Categories->CategoryTranslations->find('list', [
                'keyField' => 'category_id',
                'valueField' => 'name',
            ])
            ->where(['language_id' => $languageId])
            ->all();

            $difficulties = $this->Tests->Difficulties->DifficultyTranslations->find('list', [
                'keyField' => 'difficulty_id',
                'valueField' => 'name',
            ])
            ->where(['language_id' => $languageId])
            ->all();
        }

        $languages = $this->fetchTable('Languages')->find('list')->all();
        $languagesMeta = $this->fetchTable('Languages')->find()
            ->select(['id', 'code', 'name'])
            ->orderByAsc('id')
            ->enableHydration(false)
            ->toArray();

        // Re-index translations by language_id for the form helper
        $indexedTranslations = [];
        foreach ($test->test_translations as $translation) {
            $indexedTranslations[$translation->language_id] = $translation;
        }
        $test->test_translations = $indexedTranslations;

        // Prepare Questions Data for JS
        $questionsData = [];
        foreach ($test->questions as $question) {
            $qData = [
                'id' => $question->id, // keep ID to update existing
                'type' => $question->question_type,
                'source_type' => (string)$question->source_type,
                'translations' => [],
                'answers' => [],
            ];

            foreach ($question->question_translations as $qt) {
                $qData['translations'][$qt->language_id] = [
                    'id' => $qt->id,
                    'content' => $qt->content,
                    'explanation' => $qt->explanation,
                ];
            }

            foreach ($question->answers as $answer) {
                $aData = [
                    'id' => $answer->id,
                    'source_type' => (string)$answer->source_type,
                    'is_correct' => $answer->is_correct,
                    'match_side' => $answer->match_side,
                    'match_group' => $answer->match_group,
                    'translations' => [],
                ];
                foreach ($answer->answer_translations as $at) {
                    $aData['translations'][$at->language_id] = [
                        'id' => $at->id,
                        'content' => $at->content,
                    ];
                }
                $qData['answers'][] = $aData;
            }
            $questionsData[] = $qData;
        }

        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;
        $aiGenerationLimit = $this->getAiGenerationLimitInfo($userId);
        $currentLanguageId = $languageId;
        $this->set(compact(
            'test',
            'categories',
            'difficulties',
            'languages',
            'languagesMeta',
            'questionsData',
            'aiGenerationLimit',
            'currentLanguageId',
        ));
    }

    /**
     * Delete method
     *
     * @param string|null $id Test id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $test = $this->Tests->get($id);
        if ($this->Tests->delete($test)) {
            $this->Flash->success(__('The test has been deleted.'));
        } else {
            $this->Flash->error(__('The test could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index', 'lang' => $this->request->getParam('lang')]);
    }

    /**
     * Generate test content using AI
     *
     * @return \Cake\Http\Response|null|void
     */
    public function generateWithAi()
    {
        $this->request->allowMethod(['post']);
        $prompt = (string)$this->request->getData('prompt');
        $requestedCount = $this->request->getData('question_count');
        $questionCount = is_numeric($requestedCount) ? (int)$requestedCount : null;
        $identity = $this->Authentication->getIdentity();
        $userId = $identity ? (int)$identity->getIdentifier() : null;

        $aiGenerationLimit = $this->getAiGenerationLimitInfo($userId);
        if (!$aiGenerationLimit['allowed']) {
            return $this->response->withStatus(429)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'limit_reached' => true,
                    'message' => __('AI generation limit reached. Limit resets tomorrow.'),
                    'resets_at' => $aiGenerationLimit['resets_at_iso'],
                ]));
        }

        if (empty($prompt)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => false, 'message' => 'Empty prompt']));
        }

        try {
            // Resolve language context
            $langCode = $this->request->getParam('lang');
            $currentLanguage = $this->fetchTable('Languages')->find()
                ->where(['code LIKE' => $langCode . '%'])
                ->first();

            // Fallback to first language if not found, or leave null?
            if (!$currentLanguage) {
                 $currentLanguage = $this->fetchTable('Languages')->find()->first();
            }
            $currentLanguageId = $currentLanguage ? $currentLanguage->id : null;

            // Get languages to inform AI about required translations
            $languages = $this->fetchTable('Languages')->find('list')->toArray();
            $promptService = new AiQuizPromptService();
            $systemMessage = $promptService->getGenerationSystemPrompt($languages);
            $finalPrompt = $promptService->buildGenerationUserPrompt($prompt, $questionCount);
            $documentContext = $this->buildUploadedDocumentContextForAi();
            if ($documentContext !== '') {
                $finalPrompt .= "\n\nUse these uploaded source documents as additional context. "
                    . "Prioritize factual consistency with them:\n\n"
                    . $documentContext;
            }

            $aiService = new AiGatewayService();
            $aiResponse = $aiService->generateQuizFromText(
                $finalPrompt,
                $systemMessage,
                0.45,
                ['response_format' => ['type' => 'json_object']],
            );
            if (!$aiResponse->success) {
                throw new Exception((string)($aiResponse->error ?? 'AI request failed.'));
            }
            $responseContent = $aiResponse->content();

            $json = json_decode($responseContent, true);
            // Save AI Request Log
            $aiRequestsTable = $this->fetchTable('AiRequests');
            $aiRequest = $aiRequestsTable->newEmptyEntity();
            $aiRequest = $aiRequestsTable->patchEntity($aiRequest, [
                'user_id' => $userId,
                'language_id' => $currentLanguageId,
                'source_medium' => 'user_prompt',
                'source_reference' => 'test_generator',
                'type' => 'test_generation',
                'input_payload' => json_encode([
                    'prompt' => $prompt,
                    'question_count' => $questionCount,
                    'final_prompt' => $finalPrompt,
                ]),
                'output_payload' => $responseContent,
                'status' => 'success',
            ]);
            $aiRequestsTable->save($aiRequest);

            if (json_last_error() !== JSON_ERROR_NONE) {
                 return $this->response->withType('application/json')
                    ->withStringBody(json_encode([
                        'success' => false,
                        'message' => 'Invalid JSON from AI',
                        'debug' => $responseContent,
                    ]));
            }

            if (isset($json['questions']) && is_array($json['questions'])) {
                foreach ($json['questions'] as &$question) {
                    if (!is_array($question)) {
                        continue;
                    }
                    $question['source_type'] = 'ai';
                    $qType = (string)($question['type'] ?? '');

                    if (
                        $qType === Question::TYPE_TEXT
                        && (!isset($question['answers']) || !is_array($question['answers']) || !$question['answers'])
                    ) {
                        $fallbackAnswers = $question['accepted_answers'] ?? ($question['text_answers'] ?? null);
                        if (is_array($fallbackAnswers)) {
                            $normalizedAnswers = [];
                            foreach ($fallbackAnswers as $candidate) {
                                if (is_string($candidate)) {
                                    $text = trim($candidate);
                                    if ($text === '') {
                                        continue;
                                    }
                                    $translations = [];
                                    foreach ($languages as $langId => $_langName) {
                                        $translations[(string)$langId] = $text;
                                    }
                                    $normalizedAnswers[] = [
                                        'is_correct' => true,
                                        'source_type' => 'ai',
                                        'translations' => $translations,
                                    ];
                                    continue;
                                }

                                if (is_array($candidate)) {
                                    $translations = $candidate['translations'] ?? [];
                                    if (!is_array($translations)) {
                                        $translations = [];
                                    }
                                    $normalizedAnswers[] = [
                                        'is_correct' => true,
                                        'source_type' => 'ai',
                                        'translations' => $translations,
                                    ];
                                }
                            }
                            if ($normalizedAnswers) {
                                $question['answers'] = $normalizedAnswers;
                            }
                        }
                    }

                    if (
                        (string)($question['type'] ?? '') === Question::TYPE_MATCHING
                        && isset($question['pairs'])
                        && is_array($question['pairs'])
                        && !isset($question['answers'])
                    ) {
                        $answers = [];
                        $group = 1;
                        foreach ($question['pairs'] as $pair) {
                            if (!is_array($pair)) {
                                continue;
                            }
                            $leftTranslations = $pair['left_translations'] ?? ($pair['left'] ?? []);
                            $rightTranslations = $pair['right_translations'] ?? ($pair['right'] ?? []);
                            if (!is_array($leftTranslations) || !is_array($rightTranslations)) {
                                continue;
                            }

                            $answers[] = [
                                'source_type' => 'ai',
                                'is_correct' => false,
                                'match_side' => 'left',
                                'match_group' => $group,
                                'translations' => $leftTranslations,
                            ];
                            $answers[] = [
                                'source_type' => 'ai',
                                'is_correct' => false,
                                'match_side' => 'right',
                                'match_group' => $group,
                                'translations' => $rightTranslations,
                            ];
                            $group += 1;
                        }
                        $question['answers'] = $answers;
                    }

                    if (isset($question['answers']) && is_array($question['answers'])) {
                        foreach ($question['answers'] as &$answer) {
                            if (!is_array($answer)) {
                                continue;
                            }
                            $answer['source_type'] = 'ai';
                            if ($qType === Question::TYPE_MATCHING) {
                                $answer['is_correct'] = false;
                            } elseif ($qType === Question::TYPE_TEXT) {
                                $answer['is_correct'] = true;
                            }
                        }
                        unset($answer);
                    }
                }
                unset($question);
            }

            // Save Activity Log
            $activityLogsTable = $this->fetchTable('ActivityLogs');
            $activityLog = $activityLogsTable->newEmptyEntity();
            $activityLog = $activityLogsTable->patchEntity($activityLog, [
                'user_id' => $userId,
                'action' => ActivityLog::TYPE_AI_GENERATED_TEST,
                'ip_address' => $this->request->clientIp(),
                'user_agent' => $this->request->getHeaderLine('User-Agent'),
            ]);
            $activityLogsTable->save($activityLog);

            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => true, 'data' => $json]));
        } catch (\Throwable $e) {
            // Persist a failed AiRequest record for audit trail.
            $aiRequestsTable = $this->fetchTable('AiRequests');
            $failedReq = $aiRequestsTable->newEmptyEntity();
            $errorCode = 'AI_FAILED';
            $userMessage = $e->getMessage();
            if ($e instanceof AiServiceException) {
                $errorCode = $e->getErrorCode();
                $userMessage = $e->getUserMessage();
            }
            $failedReq = $aiRequestsTable->patchEntity($failedReq, [
                'user_id' => $userId,
                'source_medium' => 'user_prompt',
                'source_reference' => 'test_generator',
                'type' => 'test_generation',
                'input_payload' => json_encode([
                    'prompt' => $prompt,
                    'question_count' => $questionCount ?? null,
                ]),
                'output_payload' => $e->getMessage(),
                'status' => 'failed',
                'error_code' => $errorCode,
                'error_message' => $e->getMessage(),
            ]);
            $aiRequestsTable->save($failedReq);

            Log::error('AI generateWithAi failed: ' . $e->getMessage());

            $httpStatus = ($e instanceof AiServiceException && $e->getHttpStatus() === 429) ? 429 : 500;

            return $this->response
                ->withStatus($httpStatus)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'error_code' => $errorCode,
                    'message' => $userMessage,
                    'retried' => $e instanceof AiServiceException ? $e->wasRetried() : false,
                ]));
        }
    }

    /**
     * Translate the current test content into all configured languages using AI.
     *
     * Accepts the source-language content from the form to avoid losing unsaved edits.
     *
     * @param string|null $id Test id (optional, used for logging only).
     * @return \Cake\Http\Response
     */
    public function translateWithAi(?string $id = null): Response
    {
        $this->request->allowMethod(['post']);

        $sourceLanguageId = (int)($this->request->getData('source_language_id') ?? 0);
        $testSource = (array)($this->request->getData('test') ?? []);
        $questionsSource = $this->request->getData('questions');
        $questionsSource = is_array($questionsSource) ? $questionsSource : [];

        $title = trim((string)($testSource['title'] ?? ''));
        $description = trim((string)($testSource['description'] ?? ''));
        if ($sourceLanguageId <= 0 || $title === '') {
            return $this->response
                ->withStatus(422)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => 'Missing source language or title.',
                ]));
        }

        try {
            $languagesQuery = $this->fetchTable('Languages')->find()->orderByAsc('Languages.id');
            $languages = [];
            foreach ($languagesQuery->all() as $lang) {
                $languages[(int)$lang->id] = (string)($lang->name ?? $lang->code ?? 'Lang ' . $lang->id);
            }

            if (!$languages) {
                throw new Exception('No languages configured.');
            }

            $sourcePayload = [
                'source_language_id' => $sourceLanguageId,
                'test' => [
                    'title' => $title,
                    'description' => $description,
                ],
                'questions' => [],
            ];

            foreach ($questionsSource as $q) {
                if (!is_array($q)) {
                    continue;
                }

                $qId = isset($q['id']) ? (int)$q['id'] : null;
                $qType = (string)($q['type'] ?? $q['question_type'] ?? '');
                $qContent = trim((string)($q['content'] ?? ''));
                if ($qContent === '') {
                    continue;
                }

                $answersOut = [];
                $answers = $q['answers'] ?? [];
                if (is_array($answers)) {
                    foreach ($answers as $a) {
                        if (!is_array($a)) {
                            continue;
                        }
                        $aId = isset($a['id']) ? (int)$a['id'] : null;
                        $aContent = trim((string)($a['content'] ?? ''));
                        $isCorrect = (bool)($a['is_correct'] ?? false);
                        $matchSide = trim((string)($a['match_side'] ?? ''));
                        $matchGroup = isset($a['match_group']) && is_numeric($a['match_group'])
                            ? (int)$a['match_group']
                            : null;
                        if ($aContent === '') {
                            continue;
                        }
                        $answersOut[] = [
                            'id' => $aId,
                            'is_correct' => $isCorrect,
                            'content' => $aContent,
                            'match_side' => $matchSide !== '' ? $matchSide : null,
                            'match_group' => $matchGroup,
                        ];
                    }
                }

                $sourcePayload['questions'][] = [
                    'id' => $qId,
                    'type' => $qType,
                    'content' => $qContent,
                    'answers' => $answersOut,
                ];
            }

            $promptService = new AiQuizPromptService();
            $systemMessage = $promptService->getTranslationSystemPrompt($languages, $sourceLanguageId);

            $prompt = json_encode($sourcePayload);
            if ($prompt === false) {
                throw new Exception('Failed to encode translation payload.');
            }

            $aiService = new AiGatewayService();
            $aiResponse = $aiService->generateQuizFromText(
                $prompt,
                $systemMessage,
                0.2,
                ['response_format' => ['type' => 'json_object']],
            );
            if (!$aiResponse->success) {
                throw new Exception((string)($aiResponse->error ?? 'AI request failed.'));
            }
            $responseContent = $aiResponse->content();

            $json = json_decode($responseContent, true);

            // Save AI Request Log
            $identity = $this->Authentication->getIdentity();
            $userId = $identity ? (int)$identity->getIdentifier() : null;
            $aiRequestsTable = $this->fetchTable('AiRequests');
            $aiRequest = $aiRequestsTable->newEmptyEntity();
            $aiRequest = $aiRequestsTable->patchEntity($aiRequest, [
                'user_id' => $userId,
                'language_id' => $sourceLanguageId,
                'source_medium' => 'test_payload',
                'source_reference' => $id ? 'test:' . $id : 'test:unsaved',
                'type' => 'test_translation',
                'input_payload' => $prompt,
                'output_payload' => $responseContent,
                'status' => 'success',
            ]);
            $aiRequestsTable->save($aiRequest);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
                return $this->response
                    ->withStatus(500)
                    ->withType('application/json')
                    ->withStringBody((string)json_encode([
                        'success' => false,
                        'message' => 'Invalid JSON from AI',
                        'debug' => $responseContent,
                    ]));
            }

            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'data' => $json,
                ]));
        } catch (\Throwable $e) {
            // Persist a failed AiRequest record for audit trail.
            $identity = $this->Authentication->getIdentity();
            $failUserId = $identity ? (int)$identity->getIdentifier() : null;
            $aiRequestsTable = $this->fetchTable('AiRequests');
            $failedReq = $aiRequestsTable->newEmptyEntity();
            $errorCode = 'AI_FAILED';
            $userMessage = $e->getMessage();
            if ($e instanceof AiServiceException) {
                $errorCode = $e->getErrorCode();
                $userMessage = $e->getUserMessage();
            }
            $failedReq = $aiRequestsTable->patchEntity($failedReq, [
                'user_id' => $failUserId,
                'language_id' => $sourceLanguageId,
                'source_medium' => 'test_payload',
                'source_reference' => $id ? 'test:' . $id : 'test:unsaved',
                'type' => 'test_translation',
                'input_payload' => json_encode(['source_language_id' => $sourceLanguageId, 'title' => $title]),
                'output_payload' => $e->getMessage(),
                'status' => 'failed',
                'error_code' => $errorCode,
                'error_message' => $e->getMessage(),
            ]);
            $aiRequestsTable->save($failedReq);

            Log::error('AI translateWithAi failed: ' . $e->getMessage());

            $httpStatus = ($e instanceof AiServiceException && $e->getHttpStatus() === 429) ? 429 : 500;

            return $this->response
                ->withStatus($httpStatus)
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'error_code' => $errorCode,
                    'message' => $userMessage,
                    'retried' => $e instanceof AiServiceException ? $e->wasRetried() : false,
                ]));
        }
    }

    /**
     * @return array{allowed: bool, used: int, limit: int, remaining: int, resets_at_iso: string}
     */
    private function getAiGenerationLimitInfo(?int $userId): array
    {
        $dailyLimit = max(1, (int)env('AI_TEST_GENERATION_DAILY_LIMIT', '20'));
        if ($userId === null) {
            return [
                'allowed' => false,
                'used' => $dailyLimit,
                'limit' => $dailyLimit,
                'remaining' => 0,
                'resets_at_iso' => FrozenTime::tomorrow()->format('c'),
            ];
        }

        $todayStart = FrozenTime::today();
        $tomorrowStart = FrozenTime::tomorrow();

        $aiRequestsTable = $this->fetchTable('AiRequests');
        $used = (int)$aiRequestsTable->find()
            ->where([
                'user_id' => $userId,
                'type' => 'test_generation',
                'created_at >=' => $todayStart,
                'created_at <' => $tomorrowStart,
            ])
            ->count();

        $remaining = max(0, $dailyLimit - $used);

        return [
            'allowed' => $remaining > 0,
            'used' => $used,
            'limit' => $dailyLimit,
            'remaining' => $remaining,
            'resets_at_iso' => $tomorrowStart->format('c'),
        ];
    }

    /**
     * Build uploaded document context snippet for AI generation.
     *
     * @return string
     */
    private function buildUploadedDocumentContextForAi(): string
    {
        $files = $this->request->getUploadedFiles();
        $documents = $files['documents'] ?? null;

        $documentFiles = [];
        if (is_array($documents)) {
            $documentFiles = $documents;
        } elseif ($documents instanceof UploadedFileInterface) {
            $documentFiles = [$documents];
        }

        if (!$documentFiles) {
            return '';
        }

        $maxCount = (int)Configure::read('AI.maxDocuments', 4);
        if (count($documentFiles) > $maxCount) {
            throw new RuntimeException('Too many documents. Max: ' . $maxCount);
        }

        $allowedMimes = (array)Configure::read('AI.allowedDocumentMimeTypes', []);
        $maxBytes = (int)Configure::read('AI.maxDocumentBytes', 8 * 1024 * 1024);
        $maxExtractChars = max(2000, (int)Configure::read('AI.maxDocumentExtractChars', 20000));

        $blocks = [];
        $remainingChars = $maxExtractChars;
        foreach ($documentFiles as $file) {
            if (!$file instanceof UploadedFileInterface) {
                continue;
            }
            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($file->getError() !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Document upload failed.');
            }

            $size = (int)$file->getSize();
            if ($size <= 0) {
                throw new RuntimeException('Uploaded document is empty.');
            }
            if ($size > $maxBytes) {
                throw new RuntimeException('Uploaded document is too large.');
            }

            $mime = $this->detectUploadedMime($file);
            if ($mime === '' || !in_array($mime, $allowedMimes, true)) {
                throw new RuntimeException('Unsupported document type: ' . ($mime !== '' ? $mime : 'unknown'));
            }

            $text = $this->extractTextFromUploadedDocument($file, $mime);
            if ($text === '' || $remainingChars <= 0) {
                continue;
            }

            $text = $this->chunkExtractedText($text, $remainingChars);
            $remainingChars -= mb_strlen($text);

            $clientName = trim((string)$file->getClientFilename());
            $sourceName = $clientName !== '' ? $clientName : 'uploaded-document';
            $blocks[] = 'Source: ' . $sourceName . ' (' . $mime . ")\n" . $text;
        }

        return implode("\n\n---\n\n", $blocks);
    }

    /**
     * Detect uploaded file MIME type from bytes.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file
     * @return string
     */
    private function detectUploadedMime(UploadedFileInterface $file): string
    {
        try {
            $stream = $file->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            $content = $stream->getContents();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
        } catch (Throwable) {
            $content = '';
        }

        $detected = '';
        if (is_string($content) && $content !== '') {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $tmp = finfo_buffer($finfo, $content);
                finfo_close($finfo);
                if (is_string($tmp)) {
                    $detected = strtolower(trim($tmp));
                }
            }
        }

        if ($detected === '') {
            $detected = strtolower(trim((string)$file->getClientMediaType()));
        }

        return $detected;
    }

    /**
     * Extract text content from supported uploaded document.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file
     * @param string $mime
     * @return string
     */
    private function extractTextFromUploadedDocument(UploadedFileInterface $file, string $mime): string
    {
        try {
            $stream = $file->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            $content = $stream->getContents();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
        } catch (Throwable) {
            $content = '';
        }

        if (!is_string($content) || $content === '') {
            return '';
        }

        $text = '';
        $mime = strtolower(trim($mime));
        if (
            in_array(
                $mime,
                ['text/plain', 'text/markdown', 'text/csv', 'application/json', 'application/xml', 'text/xml'],
                true,
            )
        ) {
            $text = $content;
        } elseif ($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            $text = $this->extractDocxTextFromBytes($content);
        } elseif ($mime === 'application/vnd.oasis.opendocument.text') {
            $text = $this->extractOdtTextFromBytes($content);
        } elseif ($mime === 'application/pdf') {
            $text = $this->extractPdfTextFromBytes($content);
        }

        return $this->normalizeExtractedText($text);
    }

    /**
     * Extract text from DOCX bytes.
     *
     * @param string $bytes
     * @return string
     */
    private function extractDocxTextFromBytes(string $bytes): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'mf_docx_');
        if ($tmpPath === false) {
            return '';
        }
        file_put_contents($tmpPath, $bytes);

        $zip = new ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            if (is_file($tmpPath)) {
                unlink($tmpPath);
            }

            return '';
        }

        // Only extract body content — skip headers/footers to avoid noise.
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if (is_file($tmpPath)) {
            unlink($tmpPath);
        }

        if (!is_string($xml) || $xml === '') {
            return '';
        }

        return trim(html_entity_decode(strip_tags($xml)));
    }

    /**
     * Extract text from ODT bytes.
     *
     * @param string $bytes
     * @return string
     */
    private function extractOdtTextFromBytes(string $bytes): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'mf_odt_');
        if ($tmpPath === false) {
            return '';
        }
        file_put_contents($tmpPath, $bytes);

        $zip = new ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            if (is_file($tmpPath)) {
                unlink($tmpPath);
            }

            return '';
        }

        $xml = $zip->getFromName('content.xml');
        $zip->close();
        if (is_file($tmpPath)) {
            unlink($tmpPath);
        }

        if (!is_string($xml) || $xml === '') {
            return '';
        }

        return trim(html_entity_decode(strip_tags($xml)));
    }

    /**
     * Extract text from PDF bytes.
     *
     * @param string $bytes
     * @return string
     */
    private function extractPdfTextFromBytes(string $bytes): string
    {
        if (!function_exists('shell_exec')) {
            return '';
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'mf_pdf_');
        if ($tmpPath === false) {
            return '';
        }
        file_put_contents($tmpPath, $bytes);

        $cmd = 'pdftotext -layout -nopgbrk ' . escapeshellarg($tmpPath) . ' - 2>/dev/null';
        $output = shell_exec($cmd);
        if (is_file($tmpPath)) {
            unlink($tmpPath);
        }

        return is_string($output) ? $output : '';
    }

    /**
     * Split extracted text into paragraph chunks and return as much as fits within $maxChars.
     * Uses paragraph boundaries to avoid cutting mid-sentence.
     *
     * @param string $text
     * @param int $maxChars
     * @return string
     */
    private function chunkExtractedText(string $text, int $maxChars): string
    {
        if ($maxChars <= 0) {
            return '';
        }
        if (mb_strlen($text) <= $maxChars) {
            return $text;
        }

        $paragraphs = preg_split('/\n{2,}/', $text);
        if (!is_array($paragraphs)) {
            return mb_substr($text, 0, $maxChars);
        }

        $result = '';
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }
            $separator = $result !== '' ? "\n\n" : '';
            if (mb_strlen($result) + mb_strlen($separator) + mb_strlen($paragraph) > $maxChars) {
                break;
            }
            $result .= $separator . $paragraph;
        }

        // Fallback: if even the first paragraph exceeds limit, hard-cut it.
        if ($result === '') {
            $result = mb_substr($text, 0, $maxChars);
        }

        return $result;
    }

    /**
     * Normalize extracted text for prompt usage.
     *
     * @param string $text
     * @return string
     */
    private function normalizeExtractedText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\r\n?/', "\n", $text) ?? $text;
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Normalize text answers for compare fallback.
     *
     * @param string $value
     * @return string
     */
    private function normalizeTextAnswerForCompare(string $value): string
    {
        $value = mb_strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = preg_replace('/[\p{P}\p{S}]+/u', '', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param int $userId
     * @param object $question
     * @param string $userAnswer
     * @param array<int, string> $acceptedAnswers
     * @param string $lang
     * @return bool
     */
    private function evaluateTextAnswerWithAi(
        int $userId,
        object $question,
        string $userAnswer,
        array $acceptedAnswers,
        string $lang,
    ): bool {
        $limit = $this->getAiTextEvaluationLimitInfo($userId);
        if (!$limit['allowed']) {
            return false;
        }

        $questionText = '';
        if (!empty($question->question_translations)) {
            $questionText = trim((string)($question->question_translations[0]->content ?? ''));
        }

        $langCode = strtolower(trim($lang));
        $outputLanguage = $langCode === 'hu' ? 'Hungarian' : 'English';
        $payload = [
            'question_type' => 'text',
            'question' => $questionText,
            'accepted_answers' => array_values($acceptedAnswers),
            'user_answer' => $userAnswer,
            'instruction' => 'Decide if user answer should be accepted as semantically equivalent.',
            'output_language' => $outputLanguage,
        ];

        $prompt = (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $systemMessage = 'You validate short text quiz answers. Return ONLY strict JSON: '
            . '{"is_correct":true|false,"confidence":0..1,"reason":"short"}. '
            . 'Accept minor phrasing, synonyms, and grammar differences, but reject different meaning.';

        $aiRequests = $this->fetchTable('AiRequests');
        try {
            $ai = new AiGatewayService();
            $aiResponse = $ai->validateOutput(
                $prompt,
                $systemMessage,
                0.0,
                ['response_format' => ['type' => 'json_object']],
            );
            if (!$aiResponse->success) {
                throw new Exception((string)($aiResponse->error ?? 'AI request failed.'));
            }
            $content = $aiResponse->content();

            $decoded = json_decode((string)$content, true);
            $isCorrect = is_array($decoded) && isset($decoded['is_correct'])
                ? (bool)$decoded['is_correct']
                : false;

            $outputPayload = json_encode(['raw' => (string)$content], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $req = $aiRequests->newEntity([
                'user_id' => $userId,
                'language_id' => null,
                'source_medium' => 'quiz_submit',
                'source_reference' => 'question:' . (int)($question->id ?? 0),
                'type' => 'text_answer_evaluation',
                'input_payload' => $prompt,
                'output_payload' => is_string($outputPayload) ? $outputPayload : '{}',
                'status' => 'success',
            ]);
            $aiRequests->save($req);

            return $isCorrect;
        } catch (Throwable $e) {
            $errorPayload = json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $aiErrCode = 'AI_FAILED';
            if ($e instanceof AiServiceException) {
                $aiErrCode = $e->getErrorCode();
            }
            $req = $aiRequests->newEntity([
                'user_id' => $userId,
                'language_id' => null,
                'source_medium' => 'quiz_submit',
                'source_reference' => 'question:' . (int)($question->id ?? 0),
                'type' => 'text_answer_evaluation',
                'input_payload' => $prompt,
                'output_payload' => is_string($errorPayload) ? $errorPayload : '{}',
                'status' => 'failed',
                'error_code' => $aiErrCode,
                'error_message' => $e->getMessage(),
            ]);
            $aiRequests->save($req);

            return false;
        }
    }

    /**
     * @return array{allowed: bool, used: int, limit: int, remaining: int, resets_at_iso: string}
     */
    private function getAiExplanationLimitInfo(?int $userId): array
    {
        $dailyLimit = max(1, (int)env('AI_EXPLANATION_DAILY_LIMIT', '60'));
        if ($userId === null) {
            return [
                'allowed' => false,
                'used' => $dailyLimit,
                'limit' => $dailyLimit,
                'remaining' => 0,
                'resets_at_iso' => FrozenTime::tomorrow()->format('c'),
            ];
        }

        $todayStart = FrozenTime::today();
        $tomorrowStart = FrozenTime::tomorrow();

        $aiRequestsTable = $this->fetchTable('AiRequests');
        $used = (int)$aiRequestsTable->find()
            ->where([
                'user_id' => $userId,
                'type' => 'attempt_answer_explanation',
                'created_at >=' => $todayStart,
                'created_at <' => $tomorrowStart,
            ])
            ->count();

        $remaining = max(0, $dailyLimit - $used);

        return [
            'allowed' => $remaining > 0,
            'used' => $used,
            'limit' => $dailyLimit,
            'remaining' => $remaining,
            'resets_at_iso' => $tomorrowStart->format('c'),
        ];
    }

    /**
     * @return array{allowed: bool, used: int, limit: int, remaining: int, resets_at_iso: string}
     */
    private function getAiTextEvaluationLimitInfo(?int $userId): array
    {
        $dailyLimit = max(1, (int)env('AI_TEXT_EVALUATION_DAILY_LIMIT', '80'));
        if ($userId === null) {
            return [
                'allowed' => false,
                'used' => $dailyLimit,
                'limit' => $dailyLimit,
                'remaining' => 0,
                'resets_at_iso' => FrozenTime::tomorrow()->format('c'),
            ];
        }

        $todayStart = FrozenTime::today();
        $tomorrowStart = FrozenTime::tomorrow();
        $aiRequestsTable = $this->fetchTable('AiRequests');
        $used = (int)$aiRequestsTable->find()
            ->where([
                'user_id' => $userId,
                'type' => 'text_answer_evaluation',
                'created_at >=' => $todayStart,
                'created_at <' => $tomorrowStart,
            ])
            ->count();

        $remaining = max(0, $dailyLimit - $used);

        return [
            'allowed' => $remaining > 0,
            'used' => $used,
            'limit' => $dailyLimit,
            'remaining' => $remaining,
            'resets_at_iso' => $tomorrowStart->format('c'),
        ];
    }
}
