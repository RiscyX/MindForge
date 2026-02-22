<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Provides dashboard metrics for the public/user-facing dashboard.
 */
class DashboardMetricsService
{
    use LocatorAwareTrait;

    /**
     * Get the most recent finished test attempts for a user.
     *
     * @param int $userId The user ID.
     * @param string $langCode The language code for category translations.
     * @param int $limit Maximum number of attempts to return.
     * @return list<mixed>
     */
    public function getRecentAttempts(int $userId, string $langCode, int $limit = 5): array
    {
        if ($userId <= 0) {
            return [];
        }

        $language = $this->fetchTable('Languages')->find()
            ->where(['code LIKE' => $langCode . '%'])
            ->first();

        if ($language === null) {
            $language = $this->fetchTable('Languages')->find()->first();
        }

        $languageId = $language->id ?? null;

        return $this->fetchTable('TestAttempts')->find()
            ->where([
                'TestAttempts.user_id' => $userId,
                'TestAttempts.finished_at IS NOT' => null,
            ])
            ->contain([
                'Categories.CategoryTranslations' => function ($q) use ($languageId) {
                    return $q->where(['CategoryTranslations.language_id' => $languageId]);
                },
            ])
            ->orderByDesc('TestAttempts.finished_at')
            ->limit($limit)
            ->all()
            ->toList();
    }
}
