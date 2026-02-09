<?php
declare(strict_types=1);

namespace App\Controller\Api;

use Cake\ORM\Query\SelectQuery;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Stats', description: 'Statistics for the authenticated user')]
class StatsController extends AppController
{
    #[OA\Get(
        path: '/api/v1/me/stats/quizzes',
        summary: 'Get quiz stats for the current user (best attempt per quiz)',
        tags: ['Stats'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'lang',
                in: 'query',
                required: false,
                description: 'Language code (en, hu)',
                schema: new OA\Schema(type: 'string', default: 'en'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Quiz stats',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'quizzes', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
        ],
    )]
    public function quizzes(): void
    {
        $this->request->allowMethod(['get']);

        $user = $this->request->getAttribute('apiUser');
        $userId = $user ? (int)$user->id : null;
        if ($userId === null) {
            $this->jsonError(401, 'TOKEN_INVALID', 'Access token is required.');

            return;
        }

        $langId = $this->resolveLanguageIdFromQuery();

        $attemptsTable = $this->fetchTable('TestAttempts');

        // All attempts count (including unfinished) per test.
        $countRows = $attemptsTable->find()
            ->select([
                'test_id' => 'TestAttempts.test_id',
                'attempts_count' => $attemptsTable->find()->func()->count('TestAttempts.id'),
            ])
            ->where([
                'TestAttempts.user_id' => $userId,
                'TestAttempts.test_id IS NOT' => null,
            ])
            ->groupBy(['TestAttempts.test_id'])
            ->enableHydration(false)
            ->all()
            ->toList();

        $attemptsCountByTest = [];
        $testIds = [];
        foreach ($countRows as $row) {
            $tid = (int)($row['test_id'] ?? 0);
            $cnt = (int)($row['attempts_count'] ?? 0);
            if ($tid > 0 && $cnt > 0) {
                $attemptsCountByTest[$tid] = $cnt;
                $testIds[] = $tid;
            }
        }

        if (!$testIds) {
            $this->jsonSuccess(['quizzes' => []]);

            return;
        }

        // Best finished attempt per test (by score, then correct_answers, then finished_at).
        $bestAttempts = $attemptsTable->find()
            ->where([
                'TestAttempts.user_id' => $userId,
                'TestAttempts.test_id IN' => $testIds,
                'TestAttempts.finished_at IS NOT' => null,
            ])
            ->orderByAsc('TestAttempts.test_id')
            ->orderByDesc('TestAttempts.score')
            ->orderByDesc('TestAttempts.correct_answers')
            ->orderByDesc('TestAttempts.finished_at')
            ->orderByDesc('TestAttempts.id')
            ->all()
            ->toArray();

        $bestByTest = [];
        foreach ($bestAttempts as $attempt) {
            $tid = (int)($attempt->test_id ?? 0);
            if ($tid <= 0) {
                continue;
            }
            if (!isset($bestByTest[$tid])) {
                $bestByTest[$tid] = $attempt;
            }
        }

        // Fetch test meta (only public tests should be visible in the mobile catalog).
        $tests = $this->fetchTable('Tests')->find()
            ->where([
                'Tests.id IN' => array_keys($attemptsCountByTest),
                'Tests.is_public' => true,
            ])
            ->contain([
                'Categories.CategoryTranslations' => fn(SelectQuery $q) => $langId ? $q->where(['CategoryTranslations.language_id' => $langId]) : $q,
                'Difficulties.DifficultyTranslations' => fn(SelectQuery $q) => $langId ? $q->where(['DifficultyTranslations.language_id' => $langId]) : $q,
                'TestTranslations' => fn(SelectQuery $q) => $langId ? $q->where(['TestTranslations.language_id' => $langId]) : $q,
            ])
            ->all()
            ->indexBy('id')
            ->toArray();

        $items = [];
        foreach ($tests as $testId => $test) {
            $translation = $test->test_translations[0] ?? null;
            $diffTrans = $test->difficulty?->difficulty_translations[0] ?? null;
            $catTrans = $test->category?->category_translations[0] ?? null;

            $best = $bestByTest[(int)$testId] ?? null;

            $items[] = [
                'test' => [
                    'id' => (int)$test->id,
                    'title' => $translation?->title ?? 'Untitled Test',
                    'description' => $translation?->description ?? '',
                    'category' => $catTrans?->name ?? null,
                    'category_id' => $test->category_id !== null ? (int)$test->category_id : null,
                    'difficulty' => $diffTrans?->name ?? null,
                    'difficulty_id' => $test->difficulty_id !== null ? (int)$test->difficulty_id : null,
                ],
                'attempts_count' => (int)($attemptsCountByTest[(int)$testId] ?? 0),
                'best_attempt' => $best ? [
                    'id' => (int)$best->id,
                    'finished_at' => $best->finished_at?->format('c'),
                    'score' => $best->score !== null ? (float)$best->score : null,
                    'total_questions' => $best->total_questions !== null ? (int)$best->total_questions : null,
                    'correct_answers' => $best->correct_answers !== null ? (int)$best->correct_answers : null,
                ] : null,
            ];
        }

        // Most relevant first.
        usort($items, static function (array $a, array $b): int {
            $as = (float)($a['best_attempt']['score'] ?? -1);
            $bs = (float)($b['best_attempt']['score'] ?? -1);
            if ($as !== $bs) {
                return $bs <=> $as;
            }

            $ac = (int)($a['attempts_count'] ?? 0);
            $bc = (int)($b['attempts_count'] ?? 0);

            return $bc <=> $ac;
        });

        $this->jsonSuccess(['quizzes' => $items]);
    }

    private function resolveLanguageIdFromQuery(): ?int
    {
        $langCode = strtolower(trim((string)$this->request->getQuery('lang', 'en')));
        $languages = $this->fetchTable('Languages');
        $lang = $languages->find()->where(['code LIKE' => $langCode . '%'])->first();
        if (!$lang) {
            $lang = $languages->find()->first();
        }

        return $lang?->id;
    }
}
