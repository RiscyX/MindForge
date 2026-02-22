<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;

/**
 * Handles multi-language translation scaffolding for entities.
 *
 * Creates empty translation entities for languages that are missing, so that
 * forms always display a row for every language.
 */
class TranslationScaffoldService
{
    use LocatorAwareTrait;

    /**
     * Build a complete set of empty translation entities for a new entity.
     *
     * @param \Cake\ORM\Table $translationsTable The translation table (e.g. CategoryTranslations).
     * @return list<\Cake\Datasource\EntityInterface>
     */
    public function buildNewTranslations(Table $translationsTable): array
    {
        $languages = $this->fetchTable('Languages')->find('all');
        $translations = [];

        foreach ($languages as $language) {
            $t = $translationsTable->newEmptyEntity();
            $t->language_id = $language->id;
            $translations[] = $t;
        }

        return $translations;
    }

    /**
     * Merge existing translations with empty stubs for missing languages.
     *
     * Ensures that every language has a translation entity, either existing
     * or a newly created empty one.
     *
     * @param \Cake\ORM\Table $translationsTable The translation table.
     * @param iterable<\Cake\Datasource\EntityInterface> $existingTranslations Current translations.
     * @return list<\Cake\Datasource\EntityInterface>
     */
    public function mergeTranslations(Table $translationsTable, iterable $existingTranslations): array
    {
        $existing = [];
        foreach ($existingTranslations as $t) {
            $existing[$t->language_id] = $t;
        }

        $languages = $this->fetchTable('Languages')->find('all');
        $complete = [];

        foreach ($languages as $language) {
            if (isset($existing[$language->id])) {
                $complete[] = $existing[$language->id];
            } else {
                $t = $translationsTable->newEmptyEntity();
                $t->language_id = $language->id;
                $complete[] = $t;
            }
        }

        return $complete;
    }
}
