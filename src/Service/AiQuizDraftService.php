<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Question;
use RuntimeException;

class AiQuizDraftService
{
    // Maximum character lengths for AI-generated content (enforce before DB write)
    public const MAX_TEST_TITLE_LENGTH = 255;
    public const MAX_TEST_DESCRIPTION_LENGTH = 2000;
    public const MAX_QUESTION_CONTENT_LENGTH = 1000;
    public const MAX_ANSWER_CONTENT_LENGTH = 500;
    public const MAX_EXPLANATION_LENGTH = 500;

    /**
     * @param mixed $draft
     * @return array{draft: array, testData: array}
     */
    public function validateAndBuildTestData(mixed $draft): array
    {
        if (!is_array($draft)) {
            throw new RuntimeException('AI draft is not an object.');
        }

        $translations = $draft['translations'] ?? null;
        $questions = $draft['questions'] ?? null;
        if (!is_array($translations) || !$translations) {
            throw new RuntimeException('AI draft missing translations.');
        }
        if (!is_array($questions) || !$questions) {
            throw new RuntimeException('AI draft missing questions.');
        }

        $testTranslations = [];
        foreach ($translations as $langId => $t) {
            if (!is_numeric($langId) || !is_array($t)) {
                continue;
            }
            $lid = (int)$langId;
            $title = trim((string)($t['title'] ?? ''));
            $description = trim((string)($t['description'] ?? ''));
            if ($lid <= 0 || $title === '') {
                continue;
            }
            if (mb_strlen($title) > self::MAX_TEST_TITLE_LENGTH) {
                throw new RuntimeException(sprintf(
                    'Test title exceeds maximum length of %d characters (lang %d).',
                    self::MAX_TEST_TITLE_LENGTH,
                    $lid,
                ));
            }
            if (mb_strlen($description) > self::MAX_TEST_DESCRIPTION_LENGTH) {
                $description = mb_substr($description, 0, self::MAX_TEST_DESCRIPTION_LENGTH);
            }
            $testTranslations[] = [
                'language_id' => $lid,
                'source_type' => 'ai',
                'title' => $title,
                'description' => $description,
            ];
        }
        if (!$testTranslations) {
            throw new RuntimeException('AI draft has no usable test translations.');
        }

        $questionsData = [];
        $pos = 1;
        foreach ($questions as $q) {
            if (!is_array($q)) {
                continue;
            }
            $type = (string)($q['type'] ?? '');
            if (
                !in_array(
                    $type,
                    [
                        Question::TYPE_MULTIPLE_CHOICE,
                        Question::TYPE_TRUE_FALSE,
                        Question::TYPE_TEXT,
                        Question::TYPE_MATCHING,
                    ],
                    true,
                )
            ) {
                throw new RuntimeException('Invalid question type: ' . $type);
            }

            $qTranslations = $q['translations'] ?? null;
            if (!is_array($qTranslations) || !$qTranslations) {
                throw new RuntimeException('Question missing translations.');
            }
            $questionTranslations = [];
            foreach ($qTranslations as $langId => $translationPayload) {
                if (!is_numeric($langId)) {
                    continue;
                }

                $content = '';
                $explanation = null;
                if (is_array($translationPayload)) {
                    $content = trim((string)($translationPayload['content'] ?? ''));
                    $rawExplanation = trim((string)($translationPayload['explanation'] ?? ''));
                    if ($rawExplanation !== '') {
                        $explanation = mb_substr($rawExplanation, 0, self::MAX_EXPLANATION_LENGTH);
                    }
                } else {
                    $content = trim((string)$translationPayload);
                }

                $c = $content;
                if ($c === '') {
                    continue;
                }
                if (mb_strlen($c) > self::MAX_QUESTION_CONTENT_LENGTH) {
                    throw new RuntimeException(sprintf(
                        'Question content exceeds maximum length of %d characters (lang %d).',
                        self::MAX_QUESTION_CONTENT_LENGTH,
                        (int)$langId,
                    ));
                }
                $entry = [
                    'language_id' => (int)$langId,
                    'source_type' => 'ai',
                    'content' => $c,
                ];
                if ($explanation !== null) {
                    $entry['explanation'] = $explanation;
                }
                $questionTranslations[] = $entry;
            }
            if (!$questionTranslations) {
                throw new RuntimeException('Question has no usable translations.');
            }

            $answersData = [];
            $correctCount = 0;
            $answers = $q['answers'] ?? null;
            if (!is_array($answers) || !$answers) {
                throw new RuntimeException('Question missing answers.');
            }

            $aPos = 1;
            foreach ($answers as $a) {
                if (!is_array($a)) {
                    continue;
                }

                // For text questions all answers are accepted answers — force is_correct=true
                // regardless of what the AI returned, since the model occasionally omits the flag.
                $isCorrect = $type === Question::TYPE_TEXT ? true : (bool)($a['is_correct'] ?? false);
                $matchSide = $a['match_side'] ?? null;
                $matchGroup = $a['match_group'] ?? null;

                if ($type !== Question::TYPE_MATCHING) {
                    $matchSide = null;
                    $matchGroup = null;
                } else {
                    if (!is_string($matchSide) || !in_array($matchSide, ['left', 'right'], true)) {
                        throw new RuntimeException('Matching answer must have match_side left/right.');
                    }
                    if (!is_numeric($matchGroup) || (int)$matchGroup <= 0) {
                        throw new RuntimeException('Matching answer must have positive match_group.');
                    }
                }

                $aTranslations = $a['translations'] ?? null;
                if (!is_array($aTranslations) || !$aTranslations) {
                    continue;
                }

                $answerTranslations = [];
                foreach ($aTranslations as $langId => $content) {
                    if (!is_numeric($langId)) {
                        continue;
                    }
                    $c = trim((string)$content);
                    if ($c === '') {
                        continue;
                    }
                    if (mb_strlen($c) > self::MAX_ANSWER_CONTENT_LENGTH) {
                        throw new RuntimeException(sprintf(
                            'Answer content exceeds maximum length of %d characters (lang %d).',
                            self::MAX_ANSWER_CONTENT_LENGTH,
                            (int)$langId,
                        ));
                    }
                    $answerTranslations[] = [
                        'language_id' => (int)$langId,
                        'source_type' => 'ai',
                        'content' => $c,
                    ];
                }
                if (!$answerTranslations) {
                    continue;
                }

                if ($isCorrect) {
                    $correctCount += 1;
                }

                $answersData[] = [
                    'source_type' => 'ai',
                    'position' => $aPos,
                    'is_correct' => $isCorrect,
                    'match_side' => $matchSide,
                    'match_group' => is_numeric($matchGroup) ? (int)$matchGroup : null,
                    'answer_translations' => $answerTranslations,
                ];
                $aPos += 1;
            }

            if ($type === Question::TYPE_TRUE_FALSE && count($answersData) !== 2) {
                throw new RuntimeException('True/False question must have exactly 2 answers.');
            }
            if ($type === Question::TYPE_MULTIPLE_CHOICE && count($answersData) < 2) {
                throw new RuntimeException('Multiple choice question must have at least 2 answers.');
            }
            if ($type === Question::TYPE_TEXT) {
                if (count($answersData) < 1 || count($answersData) > 3) {
                    throw new RuntimeException('Text question must have 1-3 accepted answers.');
                }
                if ($correctCount !== count($answersData)) {
                    throw new RuntimeException('Text question answers must all be correct.');
                }
            } elseif ($type === Question::TYPE_MATCHING) {
                if (count($answersData) < 6) {
                    throw new RuntimeException('Matching question must have at least 3 pairs.');
                }

                $leftByGroup = [];
                $rightByGroup = [];
                foreach ($answersData as $answerData) {
                    $group = (int)($answerData['match_group'] ?? 0);
                    $side = (string)($answerData['match_side'] ?? '');
                    if ($group <= 0) {
                        continue;
                    }
                    if ($side === 'left') {
                        $leftByGroup[$group] = ($leftByGroup[$group] ?? 0) + 1;
                    }
                    if ($side === 'right') {
                        $rightByGroup[$group] = ($rightByGroup[$group] ?? 0) + 1;
                    }
                }

                $groups = array_unique(array_merge(array_keys($leftByGroup), array_keys($rightByGroup)));
                if (count($groups) < 3) {
                    throw new RuntimeException('Matching question must contain at least 3 groups.');
                }
                foreach ($groups as $group) {
                    if (($leftByGroup[$group] ?? 0) < 1 || ($rightByGroup[$group] ?? 0) < 1) {
                        throw new RuntimeException('Each matching group must contain both left and right answers.');
                    }
                }
            } elseif ($correctCount < 1) {
                throw new RuntimeException('Question must have at least one correct answer.');
            }

            $questionsData[] = [
                'source_type' => 'ai',
                'position' => $pos,
                'question_type' => $type,
                'is_active' => true,
                'question_translations' => $questionTranslations,
                'answers' => $answersData,
            ];
            $pos += 1;
        }
        if (!$questionsData) {
            throw new RuntimeException('AI draft contained no usable questions.');
        }

        $testData = [
            'test_translations' => $testTranslations,
            'questions' => $questionsData,
            'number_of_questions' => count($questionsData),
        ];

        return [
            'draft' => $draft,
            'testData' => $testData,
        ];
    }
}
