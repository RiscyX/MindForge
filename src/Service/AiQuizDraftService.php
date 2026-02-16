<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Question;
use RuntimeException;

class AiQuizDraftService
{
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
            $description = (string)($t['description'] ?? '');
            if ($lid <= 0 || $title === '') {
                continue;
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
            foreach ($qTranslations as $langId => $content) {
                if (!is_numeric($langId)) {
                    continue;
                }
                $c = trim((string)$content);
                if ($c === '') {
                    continue;
                }
                $questionTranslations[] = [
                    'language_id' => (int)$langId,
                    'source_type' => 'ai',
                    'content' => $c,
                ];
            }
            if (!$questionTranslations) {
                throw new RuntimeException('Question has no usable translations.');
            }

            $answersData = [];
            $correctCount = 0;
            if ($type !== Question::TYPE_TEXT) {
                $answers = $q['answers'] ?? null;
                if (!is_array($answers) || !$answers) {
                    throw new RuntimeException('Non-text question missing answers.');
                }

                $aPos = 1;
                foreach ($answers as $a) {
                    if (!is_array($a)) {
                        continue;
                    }
                    $isCorrect = (bool)($a['is_correct'] ?? false);
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
                if ($correctCount < 1) {
                    throw new RuntimeException('Question must have at least one correct answer.');
                }
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
