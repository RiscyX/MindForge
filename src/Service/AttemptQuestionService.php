<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query\SelectQuery;

class AttemptQuestionService
{
    use LocatorAwareTrait;

    /**
     * @param int $testId
     * @param int|null $langId
     * @param bool $includeCorrect
     * @return array<int, object>
     */
    public function listForTest(int $testId, ?int $langId, bool $includeCorrect): array
    {
        $contain = [
            'QuestionTranslations' => function (SelectQuery $q) use ($langId) {
                return $langId ? $q->where(['QuestionTranslations.language_id' => $langId]) : $q;
            },
        ];

        if ($includeCorrect) {
            $contain['Answers'] = function (SelectQuery $q) {
                return $q->orderByAsc('Answers.position')->orderByAsc('Answers.id');
            };
            $contain['Answers.AnswerTranslations'] = function (SelectQuery $q) use ($langId) {
                return $langId ? $q->where(['AnswerTranslations.language_id' => $langId]) : $q;
            };
        } else {
            $contain['Answers'] = function (SelectQuery $q) {
                return $q
                    ->select(['id', 'question_id', 'position', 'source_text', 'match_side'])
                    ->orderByAsc('Answers.position')
                    ->orderByAsc('Answers.id');
            };
            $contain['Answers.AnswerTranslations'] = function (SelectQuery $q) use ($langId) {
                return $langId ? $q->where(['AnswerTranslations.language_id' => $langId]) : $q;
            };
        }

        return $this->fetchTable('Questions')->find()
            ->where([
                'Questions.test_id' => $testId,
                'Questions.is_active' => true,
            ])
            ->orderByAsc('Questions.position')
            ->orderByAsc('Questions.id')
            ->contain($contain)
            ->all()
            ->toList();
    }

    /**
     * Get a single question for a test with translations and answers.
     *
     * @param int $questionId Question ID.
     * @param int $testId Test ID.
     * @param int|null $langId Language ID for translations.
     * @param bool $includeCorrect Whether to include correct answer data.
     * @return object|null
     */
    public function getForTest(int $questionId, int $testId, ?int $langId, bool $includeCorrect): ?object
    {
        $contain = [
            'QuestionTranslations' => function (SelectQuery $q) use ($langId) {
                return $langId ? $q->where(['QuestionTranslations.language_id' => $langId]) : $q;
            },
            'Answers' => function (SelectQuery $q) {
                return $q->orderByAsc('Answers.position')->orderByAsc('Answers.id');
            },
            'Answers.AnswerTranslations' => function (SelectQuery $q) use ($langId) {
                return $langId ? $q->where(['AnswerTranslations.language_id' => $langId]) : $q;
            },
        ];

        if (!$includeCorrect) {
            $contain['Answers'] = function (SelectQuery $q) {
                return $q
                    ->select(['id', 'question_id', 'position', 'source_text', 'match_side'])
                    ->orderByAsc('Answers.position')
                    ->orderByAsc('Answers.id');
            };
        }

        return $this->fetchTable('Questions')->find()
            ->where([
                'Questions.id' => $questionId,
                'Questions.test_id' => $testId,
            ])
            ->contain($contain)
            ->first();
    }

    /**
     * @param int $attemptId
     * @return array<int, object>
     */
    public function answersByQuestionId(int $attemptId): array
    {
        return $this->fetchTable('TestAttemptAnswers')->find()
            ->where(['test_attempt_id' => $attemptId])
            ->all()
            ->indexBy('question_id')
            ->toArray();
    }
}
