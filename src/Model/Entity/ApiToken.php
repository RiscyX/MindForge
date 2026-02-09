<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ApiToken Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $token_id
 * @property string $token_hash
 * @property string $token_type
 * @property string $family_id
 * @property int|null $parent_token_id
 * @property int|null $replaced_by_token_id
 * @property \Cake\I18n\DateTime $expires_at
 * @property \Cake\I18n\DateTime|null $used_at
 * @property \Cake\I18n\DateTime|null $revoked_at
 * @property string|null $revoked_reason
 * @property string|null $issued_ip
 * @property string|null $issued_user_agent
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 *
 * @property \App\Model\Entity\User $user
 */
class ApiToken extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'token_id' => true,
        'token_hash' => true,
        'token_type' => true,
        'family_id' => true,
        'parent_token_id' => true,
        'replaced_by_token_id' => true,
        'expires_at' => true,
        'used_at' => true,
        'revoked_at' => true,
        'revoked_reason' => true,
        'issued_ip' => true,
        'issued_user_agent' => true,
        'created_at' => true,
        'updated_at' => true,
        'user' => true,
    ];

    protected array $_hidden = [
        'token_hash',
    ];
}
