<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Handles test entity persistence (create/update) with nested associations.
 *
 * Extracted from TestsController::add() and TestsController::edit().
 */
class TestPersistenceService
{
    use LocatorAwareTrait;

    /**
     * Create a new test entity from submitted data.
     *
     * @param array<string, mixed> $data Request data.
     * @param int|null $userId Creator user id.
     * @param int|null $languageId Resolved language id.
     * @return array{ok: bool, test: object, errors?: array<string, mixed>}
     */
    public function create(array $data, ?int $userId, ?int $languageId): array
    {
        $testsTable = $this->fetchTable('Tests');
        $test = $testsTable->newEmptyEntity();
        $data['created_by'] = $userId;

        if (!empty($data['questions']) && is_array($data['questions'])) {
            $data['number_of_questions'] = count($data['questions']);
            (new TestQuestionPayloadEnricherService())->enrichForSave($data, $userId, $languageId);
        }

        $test = $testsTable->patchEntity($test, $data, [
            'associated' => $this->nestedAssociations(),
        ]);

        if ($testsTable->save($test)) {
            return ['ok' => true, 'test' => $test];
        }

        return ['ok' => false, 'test' => $test, 'errors' => $test->getErrors()];
    }

    /**
     * Update an existing test entity from submitted data.
     *
     * @param string|int $testId Test id.
     * @param array<string, mixed> $data Request data.
     * @param int|null $userId Current user id.
     * @param int|null $languageId Resolved language id.
     * @return array{ok: bool, test: object, errors?: array<string, mixed>}
     */
    public function update(string|int $testId, array $data, ?int $userId, ?int $languageId): array
    {
        $testsTable = $this->fetchTable('Tests');

        $test = $testsTable->get($testId, contain: [
            'TestTranslations',
            'Questions' => function ($q) {
                return $q->orderByAsc('position')
                    ->contain([
                        'QuestionTranslations',
                        'Answers' => function ($q) {
                            return $q->orderByAsc('id')
                                ->contain(['AnswerTranslations']);
                        },
                    ]);
            },
        ]);

        $this->backfillNestedTranslationIds($data, $test);

        if (!empty($data['questions']) && is_array($data['questions'])) {
            $data['number_of_questions'] = count($data['questions']);
            (new TestQuestionPayloadEnricherService())->enrichForSave($data, $userId, $languageId);
        }

        // Set save strategy to replace to handle deletions
        $testsTable->Questions->setSaveStrategy('replace');
        $testsTable->Questions->getTarget()->Answers->setSaveStrategy('replace');

        $test = $testsTable->patchEntity($test, $data, [
            'associated' => $this->nestedAssociations(),
        ]);

        if ($testsTable->save($test)) {
            return ['ok' => true, 'test' => $test];
        }

        return ['ok' => false, 'test' => $test, 'errors' => $test->getErrors()];
    }

    /**
     * Load a test with its full nested associations for the edit form.
     *
     * @param string|int $testId Test id.
     * @return object
     */
    public function loadForEdit(string|int $testId): object
    {
        $testsTable = $this->fetchTable('Tests');

        return $testsTable->get($testId, contain: [
            'TestTranslations',
            'Questions' => function ($q) {
                return $q->orderByAsc('position')
                    ->contain([
                        'QuestionTranslations',
                        'Answers' => function ($q) {
                            return $q->orderByAsc('id')
                                ->contain(['AnswerTranslations']);
                        },
                    ]);
            },
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function nestedAssociations(): array
    {
        return [
            'Questions' => ['associated' => [
                'Answers' => ['associated' => ['AnswerTranslations']],
                'QuestionTranslations',
            ]],
            'TestTranslations',
        ];
    }

    /**
     * Fill missing nested translation IDs by matching existing rows on language_id.
     *
     * @param array<string, mixed> $data Incoming update payload.
     * @param object $test Loaded test with nested associations.
     * @return void
     */
    private function backfillNestedTranslationIds(array &$data, object $test): void
    {
        if (empty($data['questions']) || !is_array($data['questions'])) {
            return;
        }

        $existingQuestions = [];
        if (isset($test->questions) && is_iterable($test->questions)) {
            foreach ($test->questions as $existingQuestion) {
                $existingQuestions[(int)$existingQuestion->id] = $existingQuestion;
            }
        }

        foreach ($data['questions'] as &$question) {
            if (!is_array($question)) {
                continue;
            }

            $questionId = isset($question['id']) && is_numeric($question['id'])
                ? (int)$question['id']
                : 0;
            if ($questionId <= 0 || !isset($existingQuestions[$questionId])) {
                continue;
            }

            $existingQuestion = $existingQuestions[$questionId];
            $this->backfillQuestionTranslationIds($question, $existingQuestion);
            $this->backfillAnswerTranslationIds($question, $existingQuestion);
        }
        unset($question);
    }

    /**
     * @param array<string, mixed> $question Incoming question payload.
     * @param object $existingQuestion Persisted question entity.
     * @return void
     */
    private function backfillQuestionTranslationIds(array &$question, object $existingQuestion): void
    {
        if (empty($question['question_translations']) || !is_array($question['question_translations'])) {
            return;
        }

        $translationsByLanguage = [];
        if (isset($existingQuestion->question_translations) && is_iterable($existingQuestion->question_translations)) {
            foreach ($existingQuestion->question_translations as $translation) {
                $languageId = isset($translation->language_id) ? (int)$translation->language_id : 0;
                if ($languageId > 0) {
                    $translationsByLanguage[$languageId] = (int)$translation->id;
                }
            }
        }

        foreach ($question['question_translations'] as &$translation) {
            if (!is_array($translation)) {
                continue;
            }
            if (!empty($translation['id'])) {
                continue;
            }
            $languageId = isset($translation['language_id']) && is_numeric($translation['language_id'])
                ? (int)$translation['language_id']
                : 0;
            if ($languageId > 0 && isset($translationsByLanguage[$languageId])) {
                $translation['id'] = $translationsByLanguage[$languageId];
            }
        }
        unset($translation);
    }

    /**
     * @param array<string, mixed> $question Incoming question payload.
     * @param object $existingQuestion Persisted question entity.
     * @return void
     */
    private function backfillAnswerTranslationIds(array &$question, object $existingQuestion): void
    {
        if (empty($question['answers']) || !is_array($question['answers'])) {
            return;
        }

        $existingAnswers = [];
        if (isset($existingQuestion->answers) && is_iterable($existingQuestion->answers)) {
            foreach ($existingQuestion->answers as $answer) {
                $existingAnswers[(int)$answer->id] = $answer;
            }
        }

        foreach ($question['answers'] as &$answer) {
            if (!is_array($answer)) {
                continue;
            }

            $answerId = isset($answer['id']) && is_numeric($answer['id'])
                ? (int)$answer['id']
                : 0;
            if ($answerId <= 0 || !isset($existingAnswers[$answerId])) {
                continue;
            }

            if (empty($answer['answer_translations']) || !is_array($answer['answer_translations'])) {
                continue;
            }

            $translationsByLanguage = [];
            $existingAnswer = $existingAnswers[$answerId];
            if (isset($existingAnswer->answer_translations) && is_iterable($existingAnswer->answer_translations)) {
                foreach ($existingAnswer->answer_translations as $translation) {
                    $languageId = isset($translation->language_id) ? (int)$translation->language_id : 0;
                    if ($languageId > 0) {
                        $translationsByLanguage[$languageId] = (int)$translation->id;
                    }
                }
            }

            foreach ($answer['answer_translations'] as &$translation) {
                if (!is_array($translation)) {
                    continue;
                }
                if (!empty($translation['id'])) {
                    continue;
                }
                $languageId = isset($translation['language_id']) && is_numeric($translation['language_id'])
                    ? (int)$translation['language_id']
                    : 0;
                if ($languageId > 0 && isset($translationsByLanguage[$languageId])) {
                    $translation['id'] = $translationsByLanguage[$languageId];
                }
            }
            unset($translation);
        }
        unset($answer);
    }
}
