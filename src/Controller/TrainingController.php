<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\LanguageResolverService;
use Cake\Http\Response;
use Cake\ORM\Query\SelectQuery;

/**
 * Infinity Training – endless practice mode.
 *
 * The user picks a category, then receives a random stream of questions
 * drawn from every active, public test in that category. There is no
 * fixed question count – the counter simply increments (1, 2, 3 …).
 * When the user exits, a summary screen shows how many answers were correct.
 *
 * All question navigation and scoring happen client-side (JS).  The server
 * provides two endpoints:
 *   - index()      → renders the full training page (category picker + runner)
 *   - questions()   → JSON endpoint that returns shuffled questions for a category
 */
class TrainingController extends AppController
{
    /**
     * Render the training page (category picker + SPA runner).
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $lang = (string)$this->request->getParam('lang', 'en');

        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            $this->Flash->error(__('Please log in to use training mode.'));

            return $this->redirect([
                'controller' => 'Users',
                'action' => 'login',
                'lang' => $lang,
            ]);
        }

        $langCode = strtolower(trim($lang));
        $languageId = (new LanguageResolverService())->resolveId($langCode);

        // Pagination params
        $perPage = 12;
        $page = max(1, (int)($this->request->getQuery('page') ?? 1));

        // Fetch active categories that have at least one active, public test
        // with at least one active question.
        $categoriesTable = $this->fetchTable('Categories');
        $allCategories = $categoriesTable->find()
            ->where(['Categories.is_active' => true])
            ->contain([
                'CategoryTranslations' => function (SelectQuery $q) use ($languageId) {
                    return $languageId
                        ? $q->where(['CategoryTranslations.language_id' => $languageId])
                        : $q;
                },
            ])
            ->matching('Tests', function (SelectQuery $q) {
                return $q
                    ->where([
                        'Tests.is_public' => true,
                    ])
                    ->matching('Questions', function (SelectQuery $q2) {
                        return $q2->where(['Questions.is_active' => true]);
                    });
            })
            ->select($categoriesTable) // avoid extra columns from matching
            ->distinct()
            ->orderByAsc('Categories.id')
            ->all()
            ->toList();

        $totalCategories = count($allCategories);
        $totalPages = max(1, (int)ceil($totalCategories / $perPage));
        $page = min($page, $totalPages);
        $categories = array_slice($allCategories, ($page - 1) * $perPage, $perPage);

        $this->set(compact('categories', 'lang', 'languageId', 'page', 'totalPages', 'totalCategories'));
    }

    /**
     * JSON endpoint – return shuffled questions for a category.
     *
     * GET /{lang}/training/questions?category_id=X
     *
     * Returns all active questions (with answers + translations) from every
     * public test in the given category, in random order.
     *
     * @return \Cake\Http\Response
     */
    public function questions(): Response
    {
        $this->request->allowMethod(['get']);
        $this->autoRender = false;

        $identity = $this->Authentication->getIdentity();
        if ($identity === null) {
            return $this->response
                ->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['error' => 'Unauthenticated']));
        }

        $categoryId = (int)$this->request->getQuery('category_id', '0');
        if ($categoryId <= 0) {
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['error' => 'Missing category_id']));
        }

        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);

        // All active questions from public tests in this category
        $questionsTable = $this->fetchTable('Questions');
        $questions = $questionsTable->find()
            ->where([
                'Questions.is_active' => true,
            ])
            ->matching('Tests', function (SelectQuery $q) use ($categoryId) {
                return $q->where([
                    'Tests.category_id' => $categoryId,
                    'Tests.is_public' => true,
                ]);
            })
            ->contain([
                'QuestionTranslations' => function (SelectQuery $q) use ($languageId) {
                    return $languageId
                        ? $q->where(['QuestionTranslations.language_id' => $languageId])
                        : $q;
                },
                'Answers' => function (SelectQuery $q) {
                    return $q
                        ->select([
                            'Answers.id',
                            'Answers.question_id',
                            'Answers.is_correct',
                            'Answers.position',
                            'Answers.source_text',
                            'Answers.match_side',
                            'Answers.match_group',
                        ])
                        ->orderByAsc('Answers.position')
                        ->orderByAsc('Answers.id');
                },
                'Answers.AnswerTranslations' => function (SelectQuery $q) use ($languageId) {
                    return $languageId
                        ? $q->where(['AnswerTranslations.language_id' => $languageId])
                        : $q;
                },
            ])
            ->select($questionsTable)
            ->distinct()
            ->all()
            ->toList();

        // Shuffle randomly
        shuffle($questions);

        // Serialize into a clean JSON payload
        $payload = [];
        foreach ($questions as $question) {
            $qText = '';
            if (!empty($question->question_translations)) {
                $qText = (string)($question->question_translations[0]->content ?? '');
            }

            $answers = [];
            foreach (($question->answers ?? []) as $answer) {
                $aText = '';
                if (!empty($answer->answer_translations)) {
                    $aText = (string)($answer->answer_translations[0]->content ?? '');
                }
                if ($aText === '' && isset($answer->source_text)) {
                    $aText = (string)$answer->source_text;
                }

                $answers[] = [
                    'id' => (int)$answer->id,
                    'text' => $aText,
                    'is_correct' => (bool)$answer->is_correct,
                    'match_side' => $answer->match_side ?? null,
                    'match_group' => $answer->match_group ?? null,
                ];
            }

            // Shuffle answer order (except for matching)
            if ((string)$question->question_type !== 'matching') {
                shuffle($answers);
            }

            $payload[] = [
                'id' => (int)$question->id,
                'text' => $qText,
                'type' => (string)$question->question_type,
                'answers' => $answers,
            ];
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode(['questions' => $payload]));
    }
}
