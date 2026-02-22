<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Provides creator-facing metadata (active categories, difficulties) for API endpoints.
 */
class CreatorMetadataService
{
    use LocatorAwareTrait;

    /**
     * Resolve the language ID from a language code.
     *
     * Falls back to the first language in the table if no match is found.
     *
     * @param string $langCode A language code like 'en' or 'hu'.
     * @return int|null
     */
    public function resolveLanguageId(string $langCode): ?int
    {
        $languages = $this->fetchTable('Languages');
        $language = $languages->find()
            ->where(['Languages.code LIKE' => $langCode . '%'])
            ->first();

        if (!$language) {
            $language = $languages->find()->first();
        }

        return $language?->id;
    }

    /**
     * Get active categories with translated names for the given language.
     *
     * @param int|null $langId Resolved language ID.
     * @return list<array{id: int, name: string}>
     */
    public function getActiveCategories(?int $langId): array
    {
        $categoriesTable = $this->fetchTable('Categories');
        $query = $categoriesTable->find()
            ->select(['Categories.id'])
            ->contain([
                'CategoryTranslations' => function ($q) use ($langId) {
                    return $langId ? $q->where(['CategoryTranslations.language_id' => $langId]) : $q;
                },
            ])
            ->orderByAsc('Categories.id');

        if ($categoriesTable->getSchema()->hasColumn('is_active')) {
            $query->where(['Categories.is_active' => true]);
        }

        $categories = [];
        foreach ($query->all() as $category) {
            $name = (string)($category->category_translations[0]->name ?? '#' . (int)$category->id);
            $categories[] = [
                'id' => (int)$category->id,
                'name' => $name,
            ];
        }

        return $categories;
    }

    /**
     * Get active difficulties with translated names for the given language.
     *
     * @param int|null $langId Resolved language ID.
     * @return list<array{id: int, name: string}>
     */
    public function getActiveDifficulties(?int $langId): array
    {
        $difficultiesTable = $this->fetchTable('Difficulties');
        $query = $difficultiesTable->find()
            ->select(['Difficulties.id', 'Difficulties.level'])
            ->contain([
                'DifficultyTranslations' => function ($q) use ($langId) {
                    return $langId ? $q->where(['DifficultyTranslations.language_id' => $langId]) : $q;
                },
            ])
            ->orderByAsc('Difficulties.level')
            ->orderByAsc('Difficulties.id');

        if ($difficultiesTable->getSchema()->hasColumn('is_active')) {
            $query->where(['Difficulties.is_active' => true]);
        }

        $difficulties = [];
        foreach ($query->all() as $difficulty) {
            $name = (string)($difficulty->difficulty_translations[0]->name ?? 'Level ' . (int)$difficulty->level);
            $difficulties[] = [
                'id' => (int)$difficulty->id,
                'name' => $name,
            ];
        }

        return $difficulties;
    }
}
