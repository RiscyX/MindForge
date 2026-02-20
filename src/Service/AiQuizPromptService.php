<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Throwable;

class AiQuizPromptService
{
    public const GENERATION_PROMPT_VERSION = 'ai_quiz_generation_v2';
    public const TRANSLATION_PROMPT_VERSION = 'ai_quiz_translation_v1';

    /**
     * Build and cache the base system prompt for quiz generation.
     *
     * @param array<int, string> $languages language_id => language name/code.
     * @return string
     */
    public function getGenerationSystemPrompt(array $languages): string
    {
        $normalized = $this->normalizeLanguages($languages);
        $cacheKey = 'ai_quiz_prompt_generation_' . md5(json_encode($normalized) ?: 'default');

        try {
            return (string)Cache::remember($cacheKey, function () use ($normalized): string {
                return $this->buildGenerationPrompt($normalized);
            }, $this->getCacheConfig());
        } catch (Throwable) {
            return $this->buildGenerationPrompt($normalized);
        }
    }

    /**
     * @param array<int, string> $normalized
     * @return string
     */
    private function buildGenerationPrompt(array $normalized): string
    {
        $langIds = array_keys($normalized);
        $languagesList = implode(', ', array_map(
            static fn(int $id, string $name): string => $id . ':' . $name,
            $langIds,
            array_values($normalized),
        ));

        return "You are a senior quiz author for MindForge.
Return ONLY a strict JSON object (no markdown, no prose).

Goal:
- Produce high-quality, pedagogically sound quiz drafts.
- Keep questions clear, concise, and unambiguous.
- Avoid trivia fluff and avoid repeated question stems.

Supported question types:
- multiple_choice
- true_false
- text
- matching

Language requirements:
- Available languages: {$languagesList}
- For EVERY textual field, provide translations for ALL language IDs above.
- Use ONLY integer language IDs as object keys: " . implode(', ', $langIds) . ".

Expected JSON schema:
{
  \"translations\": {
    \"[language_id]\": { \"title\": \"...\", \"description\": \"...\" }
  },
  \"questions\": [
    {
      \"type\": \"multiple_choice\"|\"true_false\"|\"text\"|\"matching\",
      \"translations\": {
        \"[language_id]\": {
          \"content\": \"question text\",
          \"explanation\": \"short educational explanation\"
        }
      },
      \"answers\": [
        {
          \"is_correct\": true|false,
          \"match_side\": \"left\"|\"right\"|null,
          \"match_group\": integer|null,
          \"translations\": { \"[language_id]\": \"answer text\" }
        }
      ]
    }
  ]
}

Quality rules:
1) multiple_choice: provide exactly 4 answers with at least one correct answer.
2) true_false: provide exactly 2 answers (True / False equivalents), with exactly one correct answer.
3) text: provide 1-3 accepted answers; each accepted answer must have is_correct=true.
4) matching: create 3-5 pairs using answers with left/right sides and shared match_group values.
5) Keep distractors plausible and non-overlapping.
6) Ensure translation meaning is equivalent across languages.
7) For each question translation, include a short explanation (1-2 sentences, max ~320 chars) in the same language.
   - The explanation must clarify why the correct answer(s) are correct.
   - For matching questions, explicitly mention at least one correct left->right pair rationale.
8) Use mixed templates across questions (definition, scenario, comparison, application, cause/effect).
9) Avoid duplicate or near-duplicate questions.
10) Output must be valid JSON object only.
";
    }

    /**
     * Build a generation user prompt with strict constraints.
     *
     * @param string $userPrompt
     * @param int|null $questionCount
     * @return string
     */
    public function buildGenerationUserPrompt(string $userPrompt, ?int $questionCount = null): string
    {
        $cleanPrompt = trim($userPrompt);
        $countLine = '';
        if ($questionCount !== null && $questionCount > 0) {
            $countLine = "- Generate exactly {$questionCount} questions.\n";
        }

        return "User request:\n{$cleanPrompt}\n\nGeneration constraints:\n"
            . $countLine
            . "- Use all supported question types where possible (multiple_choice, true_false, text, matching).\n"
            . "- If question count is 4 or more, include at least one question from each type.\n"
            . "- Ensure at least one moderate-difficulty scenario/application question.\n"
            . "- Keep answer keys internally consistent and educationally meaningful.\n"
            . "- For text questions, always include at least one accepted answer (is_correct=true).\n"
            . "- Return strict JSON object only.\n";
    }

    /**
     * @return string
     */
    public function getGenerationPromptVersion(): string
    {
        return self::GENERATION_PROMPT_VERSION;
    }

    /**
     * Build and cache the base system prompt for translation.
     *
     * @param array<int, string> $languages
     * @param int $sourceLanguageId
     * @return string
     */
    public function getTranslationSystemPrompt(array $languages, int $sourceLanguageId): string
    {
        $normalized = $this->normalizeLanguages($languages);
        $cacheKey = 'ai_quiz_prompt_translation_' . md5(
            (string)$sourceLanguageId . '|' . (json_encode($normalized) ?: 'default'),
        );

        try {
            return (string)Cache::remember($cacheKey, function () use ($normalized, $sourceLanguageId): string {
                return $this->buildTranslationPrompt($normalized, $sourceLanguageId);
            }, $this->getCacheConfig());
        } catch (Throwable) {
            return $this->buildTranslationPrompt($normalized, $sourceLanguageId);
        }
    }

    /**
     * @return string
     */
    public function getTranslationPromptVersion(): string
    {
        return self::TRANSLATION_PROMPT_VERSION;
    }

    /**
     * @param array<int, string> $normalized
     * @param int $sourceLanguageId
     * @return string
     */
    private function buildTranslationPrompt(array $normalized, int $sourceLanguageId): string
    {
        $langIds = array_keys($normalized);
        $languagesList = implode(', ', array_map(
            static fn(int $id, string $name): string => $id . ':' . $name,
            $langIds,
            array_values($normalized),
        ));
        $sourceLanguageName = $normalized[$sourceLanguageId] ?? 'Language ' . $sourceLanguageId;

        return "You are a professional quiz translator.
Return ONLY valid JSON object, no markdown.

Configured languages: {$languagesList}
Source language: {$sourceLanguageId}:{$sourceLanguageName}

Rules:
1) Include ALL configured language_ids in each translations object.
2) Keep source language text unchanged.
3) Preserve ids and is_correct flags exactly.
4) Preserve match_side and match_group exactly for matching answers.
5) Preserve question types exactly.
6) Maintain semantic equivalence and quiz tone across languages.
";
    }

    /**
     * @param array<int, string> $languages
     * @return array<int, string>
     */
    private function normalizeLanguages(array $languages): array
    {
        $normalized = [];
        foreach ($languages as $id => $name) {
            $lid = (int)$id;
            $lname = trim((string)$name);
            if ($lid <= 0 || $lname === '') {
                continue;
            }
            $normalized[$lid] = $lname;
        }
        ksort($normalized);

        return $normalized;
    }

    /**
     * @return string
     */
    private function getCacheConfig(): string
    {
        $configured = (string)Configure::read('AI.promptCacheConfig', 'default');

        return $configured !== '' ? $configured : 'default';
    }
}
