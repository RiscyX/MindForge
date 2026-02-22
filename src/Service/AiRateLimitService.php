<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use function Cake\Core\env;

/**
 * Unified AI rate-limiting service.
 *
 * Replaces the nearly identical getAiGenerationLimitInfo(),
 * getAiExplanationLimitInfo(), and getAiTextEvaluationLimitInfo()
 * methods that were duplicated across controllers.
 */
class AiRateLimitService
{
    /**
     * Well-known rate limit types with their env-key and default limit.
     */
    public const TYPE_GENERATION = 'test_generation';
    public const TYPE_EXPLANATION = 'attempt_answer_explanation';
    public const TYPE_TEXT_EVALUATION = 'text_answer_evaluation';

    /**
     * Mapping of type to [env key, default limit].
     *
     * @var array<string, array{string, int}>
     */
    private const TYPE_DEFAULTS = [
        self::TYPE_GENERATION => ['AI_TEST_GENERATION_DAILY_LIMIT', 20],
        self::TYPE_EXPLANATION => ['AI_EXPLANATION_DAILY_LIMIT', 60],
        self::TYPE_TEXT_EVALUATION => ['AI_TEXT_EVALUATION_DAILY_LIMIT', 80],
    ];

    /**
     * Get rate limit information for a given AI request type.
     *
     * @param int|null $userId User id (null = not authenticated, always denied).
     * @param string $type AiRequest type column value (e.g. 'test_generation').
     * @param string|null $envKey Override env variable name for the daily limit.
     * @param int|null $defaultLimit Override default limit when env var is not set.
     * @return array{allowed: bool, used: int, limit: int, remaining: int, resets_at_iso: string}
     */
    public function getLimitInfo(
        ?int $userId,
        string $type,
        ?string $envKey = null,
        ?int $defaultLimit = null,
    ): array {
        // Resolve env key and default from the well-known map if not overridden.
        if ($envKey === null || $defaultLimit === null) {
            $mapped = self::TYPE_DEFAULTS[$type] ?? null;
            $envKey = $envKey ?? ($mapped[0] ?? 'AI_DAILY_LIMIT');
            $defaultLimit = $defaultLimit ?? ($mapped[1] ?? 50);
        }

        $dailyLimit = max(1, (int)env($envKey, (string)$defaultLimit));

        if ($userId === null) {
            return [
                'allowed' => false,
                'used' => $dailyLimit,
                'limit' => $dailyLimit,
                'remaining' => 0,
                'resets_at_iso' => FrozenTime::tomorrow()->format('c'),
            ];
        }

        $todayStart = FrozenTime::today();
        $tomorrowStart = FrozenTime::tomorrow();

        $aiRequestsTable = TableRegistry::getTableLocator()->get('AiRequests');
        $used = (int)$aiRequestsTable->find()
            ->where([
                'user_id' => $userId,
                'type' => $type,
                'created_at >=' => $todayStart,
                'created_at <' => $tomorrowStart,
            ])
            ->count();

        $remaining = max(0, $dailyLimit - $used);

        return [
            'allowed' => $remaining > 0,
            'used' => $used,
            'limit' => $dailyLimit,
            'remaining' => $remaining,
            'resets_at_iso' => $tomorrowStart->format('c'),
        ];
    }

    /**
     * Convenience: get generation limit info.
     *
     * @param int|null $userId
     * @return array{allowed: bool, used: int, limit: int, remaining: int, resets_at_iso: string}
     */
    public function getGenerationLimitInfo(?int $userId): array
    {
        return $this->getLimitInfo($userId, self::TYPE_GENERATION);
    }

    /**
     * Convenience: get explanation limit info.
     *
     * @param int|null $userId
     * @return array{allowed: bool, used: int, limit: int, remaining: int, resets_at_iso: string}
     */
    public function getExplanationLimitInfo(?int $userId): array
    {
        return $this->getLimitInfo($userId, self::TYPE_EXPLANATION);
    }

    /**
     * Convenience: get text evaluation limit info.
     *
     * @param int|null $userId
     * @return array{allowed: bool, used: int, limit: int, remaining: int, resets_at_iso: string}
     */
    public function getTextEvaluationLimitInfo(?int $userId): array
    {
        return $this->getLimitInfo($userId, self::TYPE_TEXT_EVALUATION);
    }
}
