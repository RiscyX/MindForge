<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Question;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use RuntimeException;
use Throwable;

class AttemptSubmissionService
{
    use LocatorAwareTrait;

    /**
     * @param object $attempt
     * @param array<int, object> $questions
     * @param array<mixed> $answersInput
     * @param callable(object, string, array<int, string>): bool|null $textAiEvaluator
     * @return array{ok: bool, error?: string}
     */
    public function submit(
        object $attempt,
        array $questions,
        array $answersInput,
        ?callable $textAiEvaluator = null,
    ): array {
        $attemptAnswersTable = $this->fetchTable('TestAttemptAnswers');
        $attemptsTable = $this->fetchTable('TestAttempts');

        $now = DateTime::now();
        $entities = [];
        $correct = 0;

        foreach ($questions as $question) {
            $qid = (int)$question->id;
            $questionType = (string)$question->question_type;

            $raw = $answersInput[$qid] ?? null;
            if (is_array($raw)) {
                $rawAnswerId = $raw['answer_id'] ?? null;
                $rawText = $raw['text'] ?? ($raw['user_answer_text'] ?? null);
            } else {
                $rawAnswerId = $raw;
                $rawText = $raw;
            }

            $chosenAnswerId = null;
            $userAnswerText = null;
            $userAnswerPayload = null;
            $isCorrect = false;

            if ($questionType === Question::TYPE_MATCHING) {
                $pairsInput = is_array($raw) && isset($raw['pairs']) && is_array($raw['pairs']) ? $raw['pairs'] : [];
                [$isCorrect, $normalizedPairs] = $this->evaluateMatchingAnswer($question, $pairsInput);
                $encoded = json_encode(['pairs' => $normalizedPairs], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $userAnswerPayload = is_string($encoded) ? $encoded : null;
            } elseif ($questionType === Question::TYPE_TEXT) {
                $userAnswerText = trim((string)$rawText);
                $correctTexts = $this->correctTextsForQuestion($question);
                $correctTextsRaw = $this->correctTextsForQuestion($question, true);
                if ($userAnswerText !== '' && $correctTexts) {
                    $normalizedUser = $this->normalizeTextAnswerForCompare($userAnswerText);
                    $isCorrect = in_array($normalizedUser, $correctTexts, true);
                    if (!$isCorrect && $textAiEvaluator !== null) {
                        $isCorrect = (bool)$textAiEvaluator($question, $userAnswerText, $correctTextsRaw);
                    }
                }
            } else {
                $chosenAnswerId = is_numeric($rawAnswerId) ? (int)$rawAnswerId : null;
                $answerCorrectMap = [];
                foreach (($question->answers ?? []) as $ans) {
                    $answerCorrectMap[(int)$ans->id] = (bool)$ans->is_correct;
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

            $entities[] = $attemptAnswersTable->newEntity([
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

        $conn = $attemptsTable->getConnection();
        $conn->begin();
        try {
            if (!$attemptAnswersTable->saveMany($entities)) {
                throw new RuntimeException('Failed to save attempt answers.');
            }

            $attempt->finished_at = $now;
            $attempt->total_questions = $total;
            $attempt->correct_answers = $correct;
            $attempt->score = $score;
            if (!$attemptsTable->save($attempt)) {
                throw new RuntimeException('Failed to finalize attempt.');
            }

            $conn->commit();

            return ['ok' => true];
        } catch (Throwable) {
            $conn->rollback();

            return ['ok' => false, 'error' => 'SUBMIT_FAILED'];
        }
    }

    /**
     * @param object $question
     * @param array<mixed> $pairsInput
     * @return array{0: bool, 1: array<string, int>}
     */
    private function evaluateMatchingAnswer(object $question, array $pairsInput): array
    {
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

        $normalizedPairs = [];
        $allValid = !empty($leftById) && !empty($rightById) && count($leftById) === count($rightById);
        $seenRights = [];

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

        return [$allValid && count($normalizedPairs) === count($leftById), $normalizedPairs];
    }

    /**
     * @param object $question
     * @param bool $raw
     * @return array<int, string>
     */
    private function correctTextsForQuestion(object $question, bool $raw = false): array
    {
        $texts = [];
        foreach (($question->answers ?? []) as $ans) {
            if (!(bool)$ans->is_correct) {
                continue;
            }

            $t = '';
            if (!empty($ans->answer_translations)) {
                $t = (string)($ans->answer_translations[0]->content ?? '');
            }
            if ($t === '' && isset($ans->source_text)) {
                $t = (string)$ans->source_text;
            }
            $t = trim($t);
            if ($t === '') {
                continue;
            }
            $texts[] = $raw ? $t : $this->normalizeTextAnswerForCompare($t);
        }

        return array_values(array_unique($texts));
    }

    /**
     * Normalize a text answer for case-insensitive comparison.
     *
     * @param string $value Raw answer text.
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
}
