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
     * Serialize a test entity with ALL translations and full question/answer data
     * for the creator/admin edit screen.
     *
     * Unlike serializeTestDetail, this:
     * - Returns is_public and created_by
     * - Returns all language translations (not just one)
     * - Returns is_correct, match_side, match_group on answers
     *
     * @param \Cake\Datasource\EntityInterface $test
     * @return array<string, mixed>
     */
    public function serializeTestForEdit(object $test): array
    {
        // Build test_translations keyed by language_id
        $testTranslations = [];
        if (!empty($test->test_translations)) {
            foreach ($test->test_translations as $tt) {
                $testTranslations[] = [
                    'id' => $tt->id ?? null,
                    'language_id' => $tt->language_id,
                    'title' => $tt->title ?? '',
                    'description' => $tt->description ?? '',
                ];
            }
        }

        $questions = [];
        if (!empty($test->questions)) {
            foreach ($test->questions as $question) {
                // All question translations
                $questionTranslations = [];
                if (!empty($question->question_translations)) {
                    foreach ($question->question_translations as $qt) {
                        $questionTranslations[] = [
                            'id' => $qt->id ?? null,
                            'language_id' => $qt->language_id,
                            'content' => $qt->content ?? '',
                            'explanation' => $qt->explanation ?? null,
                        ];
                    }
                }

                // All answers with full data
                $answers = [];
                if (!empty($question->answers)) {
                    foreach ($question->answers as $answer) {
                        $answerTranslations = [];
                        if (!empty($answer->answer_translations)) {
                            foreach ($answer->answer_translations as $at) {
                                $answerTranslations[] = [
                                    'id' => $at->id ?? null,
                                    'language_id' => $at->language_id,
                                    'content' => $at->content ?? '',
                                ];
                            }
                        }

                        $answerRow = [
                            'id' => $answer->id,
                            'is_correct' => (bool)$answer->is_correct,
                            'position' => $answer->position,
                            'answer_translations' => $answerTranslations,
                        ];

                        $matchSide = trim((string)($answer->match_side ?? ''));
                        if ($matchSide !== '') {
                            $answerRow['match_side'] = $matchSide;
                            $answerRow['match_group'] = $answer->match_group;
                        }

                        $answers[] = $answerRow;
                    }
                }

                $questions[] = [
                    'id' => $question->id,
                    'question_type' => $question->question_type,
                    'position' => $question->position,
                    'is_active' => (bool)$question->is_active,
                    'source_type' => $question->source_type,
                    'question_translations' => $questionTranslations,
                    'answers' => $answers,
                ];
            }
        }

        return [
            'id' => $test->id,
            'category_id' => $test->category_id,
            'difficulty_id' => $test->difficulty_id,
            'is_public' => (bool)$test->is_public,
            'created_by' => $test->created_by,
            'number_of_questions' => $test->number_of_questions,
            'test_translations' => $testTranslations,
            'questions' => $questions,
        ];
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
                    'is_correct' => (bool)$answer->is_correct,
                ];
                $matchSide = trim((string)($answer->match_side ?? ''));
                if ($matchSide !== '') {
                    $answerRow['match_side'] = $matchSide;
                    $answerRow['match_group'] = $answer->match_group;
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
