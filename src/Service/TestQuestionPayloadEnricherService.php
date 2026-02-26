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

            $question['source_type'] = $this->normalizeSourceType($question['source_type'] ?? null);

            if ($categoryId !== null) {
                $question['category_id'] = $categoryId;
            }
            if (empty($question['id']) && $userId !== null) {
                $question['created_by'] = $userId;
            }
            // Ensure new questions always have is_active defaults
            if (empty($question['id'])) {
                if (!isset($question['is_active'])) {
                    $question['is_active'] = true;
                }
            }

            $questionTranslations = $question['question_translations'] ?? null;
            if (is_array($questionTranslations) && $questionTranslations) {
                foreach ($questionTranslations as &$translation) {
                    if (!is_array($translation)) {
                        continue;
                    }
                    $translation['source_type'] = $this->normalizeSourceType(
                        $translation['source_type'] ?? null,
                        (string)$question['source_type'],
                    );
                    if ($userId !== null && empty($translation['created_by'])) {
                        $translation['created_by'] = $userId;
                    }
                }
                unset($translation);

                $question['question_translations'] = $questionTranslations;
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

                $answer['source_type'] = $this->normalizeSourceType(
                    $answer['source_type'] ?? null,
                    $questionSourceType,
                );

                $answer['position'] = $position;
                $position += 1;

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
                        $translation['source_type'] = $this->normalizeSourceType(
                            $translation['source_type'] ?? null,
                            (string)$answer['source_type'],
                        );
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

    /**
     * @param mixed $value Source type candidate value.
     * @param string $fallback Fallback source type.
     * @return string
     */
    private function normalizeSourceType(mixed $value, string $fallback = 'human'): string
    {
        $normalizedFallback = in_array($fallback, ['human', 'ai'], true) ? $fallback : 'human';
        $candidate = strtolower(trim((string)$value));

        return in_array($candidate, ['human', 'ai'], true) ? $candidate : $normalizedFallback;
    }
}
