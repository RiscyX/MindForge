<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ActivityLog Entity
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Cake\I18n\DateTime $created_at
 *
 * @property \App\Model\Entity\User $user
 */
class ActivityLog extends Entity
{
    public const TYPE_LOGIN = 'login';
    public const TYPE_LOGOUT = 'logout';
    public const TYPE_LOGIN_FAILED = 'login_failed';
    public const TYPE_AI_GENERATED_TEST = 'ai_generated_test';
    public const TYPE_API_TOKEN_REUSE_DETECTED = 'api_token_reuse_detected';

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'action' => true,
        'ip_address' => true,
        'user_agent' => true,
        'created_at' => true,
        'user' => true,
    ];
}
