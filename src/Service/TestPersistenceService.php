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
}
