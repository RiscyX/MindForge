<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Enriches nested questions/answers payload before test save.
 */
class TestQuestionPayloadEnricherService
{
    /**
     * @param array<string, mixed> $data Form data.
     * @param int|null $userId Authenticated user id.
     * @param int|null $languageId Resolved language id.
     * @return void
     */
    public function enrichForSave(array &$data, ?int $userId, ?int $languageId): void
    {
        if (empty($data['questions']) || !is_array($data['questions'])) {
            return;
        }

        $categoryId = !empty($data['category_id']) && is_numeric($data['category_id'])
            ? (int)$data['category_id']
            : null;
        $resolvedLanguageId = (int)($languageId ?? 0);

        foreach ($data['questions'] as &$question) {
            if (!is_array($question)) {
                continue;
            }

            if ($categoryId !== null) {
                $question['category_id'] = $categoryId;
            }
            if (empty($question['id']) && $userId !== null) {
                $question['created_by'] = $userId;
            }
            // Ensure new questions always have is_active and source_type defaults
            if (empty($question['id'])) {
                if (!isset($question['is_active'])) {
                    $question['is_active'] = true;
                }
                if (empty($question['source_type'])) {
                    $question['source_type'] = 'human';
                }
            }

            if (empty($question['answers']) || !is_array($question['answers'])) {
                continue;
            }

            $questionSourceType = (string)($question['source_type'] ?? 'human');
            $position = 1;

            foreach ($question['answers'] as &$answer) {
                if (!is_array($answer)) {
                    continue;
                }

                $answer['position'] = $position;
                $position += 1;

                if (empty($answer['source_type'])) {
                    $answer['source_type'] = $questionSourceType;
                }

                $sourceText = '';
                $translations = $answer['answer_translations'] ?? null;
                if (is_array($translations) && $translations) {
                    if (
                        $resolvedLanguageId > 0
                        && isset($translations[$resolvedLanguageId])
                        && is_array($translations[$resolvedLanguageId])
                    ) {
                        $sourceText = trim((string)($translations[$resolvedLanguageId]['content'] ?? ''));
                    }
                    if ($sourceText === '') {
                        foreach ($translations as $translation) {
                            if (!is_array($translation)) {
                                continue;
                            }
                            $candidate = trim((string)($translation['content'] ?? ''));
                            if ($candidate !== '') {
                                $sourceText = $candidate;

                                break;
                            }
                        }
                    }

                    foreach ($translations as &$translation) {
                        if (!is_array($translation)) {
                            continue;
                        }
                        if (empty($translation['source_type'])) {
                            $translation['source_type'] = (string)$answer['source_type'];
                        }
                        if ($userId !== null && empty($translation['created_by'])) {
                            $translation['created_by'] = $userId;
                        }
                    }
                    unset($translation);
                }

                if ($sourceText !== '') {
                    $answer['source_text'] = $sourceText;
                }
            }
            unset($answer);
        }
        unset($question);
    }
}
