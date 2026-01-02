<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use App\Model\Entity\UserToken;
use App\Model\Table\UserTokensTable;
use Cake\I18n\FrozenTime;

class UserTokensService
{
    /**
     * @param \App\Model\Table\UserTokensTable $userToken
     */
    public function __construct(private UserTokensTable $userTokens)
    {
    }

    /**
     * @param \App\Model\Entity\User $user
     * @return string
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     */
    public function createActivationToken(User $user): string
    {
        $tokens = $this->userTokens;

        $tokenValue = bin2hex(random_bytes(32));

        $entity = $tokens->newEntity([
            'user_id' => $user->id,
            'type' => 'activate',
            'token' => $tokenValue,
            'expires_at' => FrozenTime::now()->addHours(4),
            'used_at' => null,
        ]);

        $tokens->saveOrFail($entity);

        return $tokenValue;
    }

    /**
     * Create a password reset token for a user.
     *
     * Also invalidates any previously issued (unused) password reset tokens for the same user.
     *
     * @param \App\Model\Entity\User $user
     * @return string
     */
    public function createPasswordResetToken(User $user): string
    {
        $tokens = $this->userTokens;

        // Invalidate previous unused reset tokens for this user
        $tokens->updateAll(
            ['used_at' => FrozenTime::now()],
            [
                'user_id' => $user->id,
                'type' => 'password_reset',
                'used_at IS' => null,
            ],
        );

        $tokenValue = bin2hex(random_bytes(32));

        $entity = $tokens->newEntity([
            'user_id' => $user->id,
            'type' => 'password_reset',
            'token' => $tokenValue,
            'expires_at' => FrozenTime::now()->addHours(1),
            'used_at' => null,
        ]);

        $tokens->saveOrFail($entity);

        return $tokenValue;
    }

    /**
     * Validate an activation token and return the associated user token entity.
     *
     * @param string $token The token value to validate.
     * @return \App\Model\Entity\UserToken|null The user token entity if valid, null otherwise.
     */
    public function validateActivationToken(string $token): ?UserToken
    {
        if ($token === '') {
            return null;
        }

        /** @var \App\Model\Entity\UserToken|null $userToken */
        $userToken = $this->userTokens->find()
            ->where([
                'token' => $token,
                'type' => 'activate',
                'used_at IS' => null,
                'expires_at >' => FrozenTime::now(),
            ])
            ->contain(['Users'])
            ->first();

        return $userToken;
    }

    /**
     * Validate a password reset token and return the associated user token entity.
     *
     * @param string $token
     * @return \App\Model\Entity\UserToken|null
     */
    public function validatePasswordResetToken(string $token): ?UserToken
    {
        if ($token === '') {
            return null;
        }

        /** @var \App\Model\Entity\UserToken|null $userToken */
        $userToken = $this->userTokens->find()
            ->where([
                'token' => $token,
                'type' => 'password_reset',
                'used_at IS' => null,
                'expires_at >' => FrozenTime::now(),
            ])
            ->contain(['Users'])
            ->first();

        return $userToken;
    }

    /**
     * Mark a token as used.
     *
     * @param \App\Model\Entity\UserToken $userToken The token entity to mark as used.
     * @return bool True if successful, false otherwise.
     */
    public function markTokenAsUsed(UserToken $userToken): bool
    {
        $userToken->used_at = FrozenTime::now();

        return (bool)$this->userTokens->save($userToken);
    }
}
