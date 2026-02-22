<?php
declare(strict_types=1);

namespace App\Service;

class AttemptOrderingService
{
    /**
     * @param array<int, object> $questions
     * @param int $attemptId
     * @return array<int, object>
     */
    public function orderQuestions(array $questions, int $attemptId): array
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
     * @param array<int, object> $answers
     * @param int $attemptId
     * @param int $questionId
     * @return array<int, object>
     */
    public function orderAnswers(array $answers, int $attemptId, int $questionId): array
    {
        usort($answers, static function ($a, $b) use ($attemptId, $questionId): int {
            $aId = (int)($a->id ?? 0);
            $bId = (int)($b->id ?? 0);
            $aKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $questionId . ':option:' . $aId);
            $bKey = hash('sha256', 'attempt:' . $attemptId . ':question:' . $questionId . ':option:' . $bId);

            if ($aKey === $bKey) {
                return $aId <=> $bId;
            }

            return $aKey <=> $bKey;
        });

        return $answers;
    }
}
