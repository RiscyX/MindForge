<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\TableRegistry;

/**
 * Unified language resolution for all controllers.
 *
 * Replaces duplicated `resolveLanguageId()`, `resolveLanguageIdFromQuery()`,
 * `resolveLanguageIdFromRoute()`, `resolveLanguage()` and `resolveLanguageCode()`
 * methods across 8+ controllers.
 */
class LanguageResolverService
{
    /**
     * Resolve a language id from a language code string.
     *
     * Uses prefix matching (`code LIKE '{code}%'`) with automatic fallback
     * to the first available language when no match is found.
     *
     * @param string $langCode Language code (e.g. 'en', 'hu').
     * @return int|null Language id or null if no languages exist at all.
     */
    public function resolveId(string $langCode): ?int
    {
        $langCode = strtolower(trim($langCode));
        if ($langCode === '') {
            $langCode = 'en';
        }

        $languages = TableRegistry::getTableLocator()->get('Languages');
        $lang = $languages->find()->where(['code LIKE' => $langCode . '%'])->first();
        if (!$lang) {
            $lang = $languages->find()->first();
        }

        return $lang?->id;
    }

    /**
     * Resolve a language id, preferring an explicit numeric id over a code.
     *
     * Useful for API endpoints that accept both `language_id` (int) and `lang` (string).
     *
     * @param string|int|null $languageId Explicit language id (takes priority when > 0).
     * @param string $langCode Fallback language code.
     * @return int|null
     */
    public function resolveIdWithFallback(int|string|null $languageId, string $langCode): ?int
    {
        if (is_numeric($languageId) && (int)$languageId > 0) {
            return (int)$languageId;
        }

        return $this->resolveId($langCode);
    }

    /**
     * Resolve language code string from a language id.
     *
     * @param int $langId Language id.
     * @param string $default Default code when id is invalid or not found.
     * @return string
     */
    public function resolveCode(int $langId, string $default = 'en'): string
    {
        if ($langId <= 0) {
            return $default;
        }

        $lang = TableRegistry::getTableLocator()->get('Languages')
            ->find()
            ->select(['code'])
            ->where(['id' => $langId])
            ->first();

        return (string)($lang->code ?? $default);
    }
}
