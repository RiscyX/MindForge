<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Question;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Encapsulates answer normalization and validation logic for question editing.
 */
class QuestionEditorService
{
    use LocatorAwareTrait;

    /**
     * Normalize raw answer rows posted from the inline edit form.
     *
     * Filters out empty/meaningless rows and casts each field to its
     * expected type so the ORM receives clean data.
     *
     * @param array<int|string, mixed> $answers Raw answer rows.
     * @return array<int, array<string, mixed>>
     */
    public function normalizeAnswersPayload(array $answers): array
    {
        $normalized = [];
        foreach ($answers as $answer) {
            if (!is_array($answer)) {
                continue;
            }

            $id = isset($answer['id']) && is_numeric($answer['id']) ? (int)$answer['id'] : null;
            $sourceText = trim((string)($answer['source_text'] ?? ''));
            $sourceType = (string)($answer['source_type'] ?? 'human');
            if (!in_array($sourceType, ['human', 'ai'], true)) {
                $sourceType = 'human';
            }
            $position = isset($answer['position']) && $answer['position'] !== '' && is_numeric($answer['position'])
                ? (int)$answer['position']
                : null;
            $isCorrect = (string)($answer['is_correct'] ?? '0') === '1';

            $isMeaningful = $id !== null || $sourceText !== '' || $isCorrect || $position !== null;
            if (!$isMeaningful) {
                continue;
            }

            $row = [
                'source_type' => $sourceType,
                'source_text' => $sourceText,
                'position' => $position,
                'is_correct' => $isCorrect,
            ];
            if ($id !== null) {
                $row['id'] = $id;
            }
            $normalized[] = $row;
        }

        return $normalized;
    }

    /**
     * Validate that a question entity has at least one correct answer.
     *
     * If no correct answer is found, sets a validation error on the entity.
     *
     * @param \App\Model\Entity\Question $question The question entity with loaded answers.
     * @return bool True if at least one correct answer exists, false otherwise.
     */
    public function validateCorrectAnswer(Question $question): bool
    {
        foreach ($question->answers as $answer) {
            if ((bool)$answer->is_correct) {
                return true;
            }
        }

        $question->setError('answers', [
            'correct' => __('At least one correct answer is required.'),
        ]);

        return false;
    }
}
