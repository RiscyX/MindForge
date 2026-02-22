<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Question;

class AttemptExplanationService
{
    /**
     * @param object $question
     * @param object $attemptAnswer
     * @param string $lang
     * @return array{question_type: string, question_text: string, user_info: array<int,string>, correct_info: array<int,string>, prompt_json: string, system_message: string}
     */
    public function buildPromptContext(object $question, object $attemptAnswer, string $lang): array
    {
        $questionText = '';
        $baseExplanation = '';
        if (!empty($question->question_translations)) {
            $questionText = trim((string)($question->question_translations[0]->content ?? ''));
            $baseExplanation = trim((string)($question->question_translations[0]->explanation ?? ''));
        }
        if ($questionText === '') {
            $questionText = 'Question #' . (int)$question->id;
        }

        $questionType = (string)$question->question_type;
        [$userInfo, $correctInfo, $matchingPairDetails] = $this->buildAnswerComparisonData(
            $question,
            $attemptAnswer,
            $questionType,
        );

        $langCode = strtolower(trim($lang));
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
            . 'Keep the explanation educational, respectful, and actionable. Return ONLY valid JSON in this format: '
            . '{"explanation":"..."}';

        return [
            'question_type' => $questionType,
            'question_text' => $questionText,
            'user_info' => $userInfo,
            'correct_info' => $correctInfo,
            'prompt_json' => $promptJson,
            'system_message' => $systemMessage,
        ];
    }

    /**
     * @param string $questionType
     * @param array<int, string> $userInfo
     * @param array<int, string> $correctInfo
     * @param bool $isCorrect
     * @param string $lang
     * @return string
     */
    public function buildFallbackExplanation(
        string $questionType,
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
     * @param object $question
     * @param object $attemptAnswer
     * @param string $questionType
     * @return array{0: array<int,string>, 1: array<int,string>, 2: array<int,array<string,mixed>>}
     */
    private function buildAnswerComparisonData(object $question, object $attemptAnswer, string $questionType): array
    {
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
            foreach (($question->answers ?? []) as $ans) {
                $aid = (int)$ans->id;
                $side = (string)($ans->match_side ?? '');
                if ($side === '') {
                    continue;
                }
                $entry = [
                    'id' => $aid,
                    'text' => $answerText($ans),
                    'group' => (int)($ans->match_group ?? 0),
                ];
                if ($side === 'left') {
                    $leftMap[$aid] = $entry;
                } elseif ($side === 'right') {
                    $rightMap[$aid] = $entry;
                }
            }

            $expectedRightByLeft = [];
            foreach ($leftMap as $left) {
                foreach ($rightMap as $right) {
                    if ($left['group'] > 0 && $left['group'] === $right['group']) {
                        $expectedRightByLeft[$left['id']] = $right;
                        $correctInfo[] = $left['text'] . ' -> ' . $right['text'];
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

        return [$userInfo, $correctInfo, $matchingPairDetails];
    }
}
