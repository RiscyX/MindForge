<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Question;

class AttemptReviewService
{
    /**
     * @param array<int, object> $questions
     * @param array<int, object> $attemptAnswers
     * @param callable(object): array<string, mixed> $questionPayloadBuilder
     * @return array<int, array<string, mixed>>
     */
    public function buildReviewItems(array $questions, array $attemptAnswers, callable $questionPayloadBuilder): array
    {
        $reviewItems = [];

        foreach ($questions as $question) {
            $qid = (int)$question->id;
            $attemptAnswer = $attemptAnswers[$qid] ?? null;
            $chosenId = $attemptAnswer?->answer_id !== null ? (int)$attemptAnswer->answer_id : null;
            $userText = $attemptAnswer?->user_answer_text !== null ? (string)$attemptAnswer->user_answer_text : null;
            $userPayload = $attemptAnswer?->user_answer_payload !== null
                ? (string)$attemptAnswer->user_answer_payload
                : null;

            $answersPayload = [];
            $correctTexts = [];
            $matching = null;

            if ((string)$question->question_type === Question::TYPE_TEXT) {
                $correctTexts = $this->correctTextsForQuestion($question);
            } elseif ((string)$question->question_type === Question::TYPE_MATCHING) {
                $matching = $this->buildMatchingPayload($question, $userPayload);
            } else {
                foreach (($question->answers ?? []) as $ans) {
                    $answersPayload[] = [
                        'id' => (int)$ans->id,
                        'content' => $this->answerContent($ans),
                        'is_correct' => (bool)$ans->is_correct,
                        'is_chosen' => ($chosenId !== null && (int)$ans->id === $chosenId),
                    ];
                }
            }

            $reviewItems[] = [
                'question' => $questionPayloadBuilder($question),
                'answer' => [
                    'answer_id' => $chosenId,
                    'text' => $userText,
                    'payload' => $userPayload,
                    'is_correct' => $attemptAnswer ? (bool)$attemptAnswer->is_correct : false,
                ],
                'answers' => $answersPayload,
                'correct_texts' => $correctTexts,
                'matching' => $matching,
            ];
        }

        return $reviewItems;
    }

    /**
     * @param object $question
     * @return array<int, string>
     */
    private function correctTextsForQuestion(object $question): array
    {
        $texts = [];
        foreach (($question->answers ?? []) as $ans) {
            if (!(bool)$ans->is_correct) {
                continue;
            }

            $t = $this->answerContent($ans);
            if ($t === '') {
                continue;
            }

            $texts[] = $t;
        }

        return array_values(array_unique($texts));
    }

    /**
     * @param object $question
     * @param string|null $userPayload
     * @return array<string, mixed>
     */
    private function buildMatchingPayload(object $question, ?string $userPayload): array
    {
        $allAnswers = [];
        foreach (($question->answers ?? []) as $ans) {
            $allAnswers[(int)$ans->id] = [
                'id' => (int)$ans->id,
                'content' => $this->answerContent($ans),
                'match_side' => (string)($ans->match_side ?? ''),
                'match_group' => (int)($ans->match_group ?? 0),
            ];
        }

        $leftItems = [];
        $rightItems = [];
        $correctPairs = [];
        foreach ($allAnswers as $aid => $row) {
            if ($row['match_side'] === 'left') {
                $leftItems[] = ['id' => $aid, 'content' => $row['content']];
                foreach ($allAnswers as $rid => $candidate) {
                    if ($candidate['match_side'] !== 'right') {
                        continue;
                    }
                    if ($candidate['match_group'] > 0 && $candidate['match_group'] === $row['match_group']) {
                        $correctPairs[(string)$aid] = $rid;
                        break;
                    }
                }
            } elseif ($row['match_side'] === 'right') {
                $rightItems[] = ['id' => $aid, 'content' => $row['content']];
            }
        }

        $userPairs = [];
        if (is_string($userPayload) && $userPayload !== '') {
            $decoded = json_decode($userPayload, true);
            if (is_array($decoded) && isset($decoded['pairs']) && is_array($decoded['pairs'])) {
                foreach ($decoded['pairs'] as $leftId => $rightId) {
                    if (is_numeric($leftId) && is_numeric($rightId)) {
                        $userPairs[(string)(int)$leftId] = (int)$rightId;
                    }
                }
            }
        }

        return [
            'left' => $leftItems,
            'right' => $rightItems,
            'user_pairs' => $userPairs,
            'correct_pairs' => $correctPairs,
        ];
    }

    /**
     * Extract the display content from an answer entity.
     *
     * @param object $ans Answer entity.
     * @return string
     */
    private function answerContent(object $ans): string
    {
        $content = '';
        if (!empty($ans->answer_translations)) {
            $content = (string)($ans->answer_translations[0]->content ?? '');
        }
        if ($content === '' && isset($ans->source_text)) {
            $content = (string)$ans->source_text;
        }

        return $content;
    }
}
