<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * API-specific test catalog and detail operations.
 *
 * Handles listing public tests, viewing test details with questions,
 * and serializing them for JSON API responses.
 *
 * Extracted from Api\TestsController::index() and view().
 */
class ApiTestService
{
    /**
     * List public tests for the API catalog.
     *
     * @param int|null $langId Language ID for translations.
     * @param int|null $categoryId Optional category filter.
     * @param int|null $difficultyId Optional difficulty filter.
     * @return list<array<string, mixed>>
     */
    public function listPublicTests(?int $langId, ?int $categoryId = null, ?int $difficultyId = null): array
    {
        $testsTable = TableRegistry::getTableLocator()->get('Tests');

        $conditions = ['Tests.is_public' => true];
        if ($categoryId !== null) {
            $conditions['Tests.category_id'] = $categoryId;
        }
        if ($difficultyId !== null) {
            $conditions['Tests.difficulty_id'] = $difficultyId;
        }

        $query = $testsTable
            ->find()
            ->where($conditions)
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($langId) {
                    return $q->where(['CategoryTranslations.language_id' => $langId]);
                },
                'Difficulties.DifficultyTranslations' => function ($q) use ($langId) {
                    return $q->where(['DifficultyTranslations.language_id' => $langId]);
                },
                'TestTranslations' => function ($q) use ($langId) {
                    return $q->where(['TestTranslations.language_id' => $langId]);
                },
            ]);

        $results = $query->all();

        $tests = [];
        foreach ($results as $test) {
            $translation = $test->test_translations[0] ?? null;
            $diffTrans = $test->difficulty?->difficulty_translations[0] ?? null;
            $catTrans = $test->category?->category_translations[0] ?? null;

            $tests[] = [
                'id' => $test->id,
                'title' => $translation?->title ?? 'Untitled Test (ID: ' . $test->id . ')',
                'description' => $translation?->description ?? '',
                'category_id' => $test->category_id !== null ? (int)$test->category_id : null,
                'difficulty' => $diffTrans?->name ?? 'Unknown',
                'difficulty_id' => $test->difficulty_id !== null ? (int)$test->difficulty_id : null,
                'category' => $catTrans?->name ?? 'Uncategorized',
                'number_of_questions' => $test->number_of_questions !== null ? (int)$test->number_of_questions : null,
                'created' => $test->created_at?->format('c'),
                'modified' => $test->updated_at?->format('c'),
            ];
        }

        return $tests;
    }

    /**
     * View a single public test with questions and answers, serialized for the API.
     *
     * @param int $testId
     * @param int|null $langId
     * @return array<string, mixed>|null Null if test not found.
     */
    public function getPublicTestDetail(int $testId, ?int $langId): ?array
    {
        $testsTable = TableRegistry::getTableLocator()->get('Tests');

        $test = $testsTable->find()
            ->where(['Tests.id' => $testId, 'Tests.is_public' => true])
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($langId) {
                    return $q->where(['CategoryTranslations.language_id' => $langId]);
                },
                'Difficulties.DifficultyTranslations' => function ($q) use ($langId) {
                    return $q->where(['DifficultyTranslations.language_id' => $langId]);
                },
                'TestTranslations' => function ($q) use ($langId) {
                    return $q->where(['TestTranslations.language_id' => $langId]);
                },
                'Questions' => function ($q) {
                    return $q->orderBy(['Questions.position' => 'ASC', 'Questions.id' => 'ASC']);
                },
                'Questions.QuestionTranslations' => function ($q) use ($langId) {
                    return $q->where(['QuestionTranslations.language_id' => $langId]);
                },
                'Questions.Answers',
                'Questions.Answers.AnswerTranslations' => function ($q) use ($langId) {
                    return $q->where(['AnswerTranslations.language_id' => $langId]);
                },
            ])
            ->first();

        if (!$test) {
            return null;
        }

        return $this->serializeTestDetail($test);
    }

    /**
     * Serialize a test entity with questions and answers into an API-friendly array.
     *
     * @param \Cake\Datasource\EntityInterface $test
     * @return array<string, mixed>
     */
    private function serializeTestDetail(object $test): array
    {
        $translation = $test->test_translations[0] ?? null;
        $diffTrans = $test->difficulty?->difficulty_translations[0] ?? null;
        $catTrans = $test->category?->category_translations[0] ?? null;

        $result = [
            'id' => $test->id,
            'title' => $translation?->title ?? 'Untitled Test',
            'description' => $translation?->description ?? '',
            'difficulty' => $diffTrans?->name ?? 'Unknown',
            'category' => $catTrans?->name ?? 'Uncategorized',
            'category_id' => $test->category_id,
            'difficulty_id' => $test->difficulty_id,
            'number_of_questions' => $test->number_of_questions,
            'questions' => [],
            'created' => $test->created_at?->format('c'),
            'modified' => $test->updated_at?->format('c'),
        ];

        foreach ($test->questions as $question) {
            $qTrans = $question->question_translations[0] ?? null;

            $answers = [];
            foreach ($question->answers as $answer) {
                $aTrans = $answer->answer_translations[0] ?? null;
                $answerRow = [
                    'id' => $answer->id,
                    'content' => $aTrans?->content ?? 'No content',
                ];
                $matchSide = trim((string)($answer->match_side ?? ''));
                if ($matchSide !== '') {
                    $answerRow['match_side'] = $matchSide;
                }
                $answers[] = $answerRow;
            }

            $result['questions'][] = [
                'id' => $question->id,
                'content' => $qTrans?->content ?? 'Untitled Question',
                'explanation' => $qTrans?->explanation,
                'type' => $question->question_type,
                'position' => $question->position,
                'answers' => $answers,
            ];
        }

        return $result;
    }
}
