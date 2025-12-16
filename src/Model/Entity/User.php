<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string $email
 * @property string|null $avatar_url
 * @property string $password_hash
 * @property int $role_id
 * @property bool $is_active
 * @property bool $is_blocked
 * @property \Cake\I18n\DateTime|null $last_login_at
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 *
 * @property \App\Model\Entity\Role $role
 * @property \App\Model\Entity\ActivityLog[] $activity_logs
 * @property \App\Model\Entity\AiRequest[] $ai_requests
 * @property \App\Model\Entity\DeviceLog[] $device_logs
 * @property \App\Model\Entity\TestAttempt[] $test_attempts
 * @property \App\Model\Entity\UserToken[] $user_tokens
 */
class User extends Entity
{
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
        'email' => true,
        'avatar_url' => true,
        'password' => true,
        'password_hash' => true,
        'role_id' => true,
        'is_active' => true,
        'is_blocked' => true,
        'last_login_at' => true,
        'created_at' => true,
        'updated_at' => true,
        'role' => true,
        'activity_logs' => true,
        'ai_requests' => true,
        'device_logs' => true,
        'test_attempts' => true,
        'user_tokens' => true,
    ];

    /**
     * Hidden fields from serialization (JSON, array export).
     *
     * @var list<string>
     */
    protected array $_hidden = [
        'password_hash',
    ];

    /**
     * Hashes the password and stores it in password_hash.
     *
     * Uses PHP's native password_hash() with PASSWORD_DEFAULT (bcrypt).
     * This is compatible with CakePHP's Authentication plugin DefaultPasswordHasher.
     *
     * @param string|null $password The plain text password.
     * @return string|null Always returns null to avoid storing plain password.
     */
    protected function _setPassword(?string $password): ?string
    {
        if ($password !== null && strlen($password) > 0) {
            $this->password_hash = password_hash($password, PASSWORD_DEFAULT);
        }

        return null; // Don't store the plain password
    }
}
