<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
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
}
