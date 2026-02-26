<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Question;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;

/**
 * Builds JSON API payloads for attempt view, submit and review endpoints.
 *
 * Extracts the entity-to-DTO mapping and language-filtered test query
 * previously inlined in Api\AttemptsController.
 */
class AttemptViewPayloadService
{
    use LocatorAwareTrait;

    /**
     * Load a test with language-filtered translations for the attempt view.
     *
     * @param int $testId Test id.
     * @param int|null $langId Resolved language id (null = no filter).
     * @return object|null Test entity or null.
     */
    public function loadTestWithTranslations(int $testId, ?int $langId): ?object
    {
        $test = $this->fetchTable('Tests')->find()
            ->where(['Tests.id' => $testId])
            ->contain([
                'TestTranslations' => fn(SelectQuery $q) => $langId
                    ? $q->where(['TestTranslations.language_id' => $langId])
                    : $q,
                'Categories.CategoryTranslations' => fn(SelectQuery $q) => $langId
                    ? $q->where(['CategoryTranslations.language_id' => $langId])
                    : $q,
                'Difficulties.DifficultyTranslations' => fn(SelectQuery $q) => $langId
                    ? $q->where(['DifficultyTranslations.language_id' => $langId])
                    : $q,
            ])
            ->first();

        return $test;
    }

    /**
     * Convert a question entity to response payload.
     *
     * @param object $question Question entity.
     * @param bool $includeCorrect Include correctness metadata.
     * @param int|null $attemptId Attempt id for deterministic ordering.
     * @return array<string, mixed>
     */
    public function questionPayload(object $question, bool $includeCorrect, ?int $attemptId = null): array
    {
        $content = '';
        $explanation = null;
        if (!empty($question->question_translations)) {
            $qt = $question->question_translations[0];
            $content = (string)($qt->content ?? '');
            $explanation = isset($qt->explanation) && $qt->explanation !== ''
                ? (string)$qt->explanation
                : null;
        }

        $payload = [
            'id' => (int)$question->id,
            'content' => $content,
            'explanation' => $explanation,
            'type' => (string)$question->question_type,
            'position' => $question->position,
            'answers' => [],
        ];

        if ((string)$question->question_type === Question::TYPE_TEXT) {
            return $payload;
        }

        $answers = array_values((array)($question->answers ?? []));
        if ($attemptId !== null && $attemptId > 0) {
            $orderingService = new AttemptOrderingService();
            $answers = $orderingService->orderAnswers($answers, $attemptId, (int)($question->id ?? 0));
        }

        foreach ($answers as $ans) {
            $aContent = '';
            if (!empty($ans->answer_translations)) {
                $aContent = (string)($ans->answer_translations[0]->content ?? '');
            }
            if ($aContent === '' && isset($ans->source_text)) {
                $aContent = (string)$ans->source_text;
            }

            $row = [
                'id' => (int)$ans->id,
                'content' => $aContent,
                'position' => $ans->position,
            ];
            $side = trim((string)($ans->match_side ?? ''));
            if ($side !== '') {
                $row['match_side'] = $side;
            }
            if ($includeCorrect) {
                $row['is_correct'] = (bool)$ans->is_correct;
                $group = $ans->match_group !== null ? (int)$ans->match_group : null;
                if ($group !== null && $group > 0) {
                    $row['match_group'] = $group;
                }
            }
            $payload['answers'][] = $row;
        }

        return $payload;
    }

    /**
     * Convert attempt entity to summary payload.
     *
     * @param object $attempt Attempt entity.
     * @return array<string, mixed>
     */
    public function attemptSummary(object $attempt): array
    {
        return [
            'id' => (int)$attempt->id,
            'test_id' => $attempt->test_id !== null ? (int)$attempt->test_id : null,
            'started_at' => $attempt->started_at?->format('c'),
            'finished_at' => $attempt->finished_at?->format('c'),
            'score' => $attempt->score !== null ? (float)$attempt->score : null,
            'total_questions' => $attempt->total_questions !== null ? (int)$attempt->total_questions : null,
            'correct_answers' => $attempt->correct_answers !== null ? (int)$attempt->correct_answers : null,
            'language_id' => $attempt->language_id !== null ? (int)$attempt->language_id : null,
        ];
    }

    /**
     * Convert test entity to summary payload.
     *
     * @param object $test Test entity.
     * @return array<string, mixed>
     */
    public function testSummary(object $test): array
    {
        $translation = $test->test_translations[0] ?? null;
        $diffTrans = $test->difficulty?->difficulty_translations[0] ?? null;
        $catTrans = $test->category?->category_translations[0] ?? null;

        return [
            'id' => (int)$test->id,
            'title' => $translation?->title ?? 'Untitled Test',
            'description' => $translation?->description ?? '',
            'difficulty' => $diffTrans?->name ?? null,
            'difficulty_id' => $test->difficulty_id !== null ? (int)$test->difficulty_id : null,
            'category' => $catTrans?->name ?? null,
            'category_id' => $test->category_id !== null ? (int)$test->category_id : null,
        ];
    }
}
