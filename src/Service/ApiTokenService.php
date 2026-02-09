<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\ApiToken;
use App\Model\Entity\User;
use App\Model\Table\ApiTokensTable;
use Cake\I18n\FrozenTime;
use Cake\ORM\Exception\PersistenceFailedException;
use function Cake\Core\env;

class ApiTokenService
{
    public function __construct(private ApiTokensTable $apiTokens)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function issueTokenPair(
        User $user,
        string $ip,
        string $userAgent,
        ?string $familyId = null,
        ?int $parentRefreshTokenId = null,
    ): array {
        $familyId = $familyId ?: $this->randomId(32);

        [$accessTokenPlain, $accessTokenEntity] = $this->createToken(
            user: $user,
            tokenType: 'access',
            familyId: $familyId,
            ip: $ip,
            userAgent: $userAgent,
            ttlSeconds: $this->accessTtlMinutes() * 60,
            parentTokenId: null,
        );

        [$refreshTokenPlain, $refreshTokenEntity] = $this->createToken(
            user: $user,
            tokenType: 'refresh',
            familyId: $familyId,
            ip: $ip,
            userAgent: $userAgent,
            ttlSeconds: $this->refreshTtlDays() * 86400,
            parentTokenId: $parentRefreshTokenId,
        );

        if ($parentRefreshTokenId !== null) {
            $parent = $this->apiTokens->get($parentRefreshTokenId);
            $parent->replaced_by_token_id = $refreshTokenEntity->id;
            $this->apiTokens->saveOrFail($parent);
        }

        return [
            'access_token' => $accessTokenPlain,
            'access_token_id' => $accessTokenEntity->token_id,
            'access_expires_in' => $this->accessTtlMinutes() * 60,
            'refresh_token' => $refreshTokenPlain,
            'refresh_token_id' => $refreshTokenEntity->token_id,
            'refresh_expires_in' => $this->refreshTtlDays() * 86400,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * @return array{ok: bool, code?: string, token?: \App\Model\Entity\ApiToken}
     */
    public function validateAccessToken(string $rawToken): array
    {
        return $this->validateToken($rawToken, 'access');
    }

    /**
     * @return array{ok: bool, code?: string, token?: \App\Model\Entity\ApiToken, reused?: bool}
     */
    public function validateRefreshToken(string $rawToken): array
    {
        $result = $this->validateToken($rawToken, 'refresh', true);
        if (!$result['ok']) {
            return $result;
        }

        /** @var \App\Model\Entity\ApiToken $token */
        $token = $result['token'];

        if ($token->used_at !== null) {
            return [
                'ok' => false,
                'code' => ApiAuthErrorCodes::TOKEN_REUSED,
                'token' => $token,
                'reused' => true,
            ];
        }

        return $result;
    }

    /**
     * @return array{ok: bool, code?: string, tokens?: array<string, mixed>}
     */
    public function rotateRefreshToken(string $rawRefreshToken, string $ip, string $userAgent): array
    {
        $validation = $this->validateRefreshToken($rawRefreshToken);
        if (!$validation['ok']) {
            if (($validation['code'] ?? null) === ApiAuthErrorCodes::TOKEN_REUSED && isset($validation['token'])) {
                /** @var \App\Model\Entity\ApiToken $reusedToken */
                $reusedToken = $validation['token'];
                $this->revokeFamily($reusedToken->family_id, 'reuse_detected');
            }

            return [
                'ok' => false,
                'code' => $validation['code'] ?? ApiAuthErrorCodes::TOKEN_INVALID,
            ];
        }

        /** @var \App\Model\Entity\ApiToken $refreshToken */
        $refreshToken = $validation['token'];
        /** @var \App\Model\Entity\User $user */
        $user = $refreshToken->user;

        $connection = $this->apiTokens->getConnection();
        $tokens = $connection->transactional(function () use ($refreshToken, $user, $ip, $userAgent): array {
            $refreshToken->used_at = FrozenTime::now();
            $this->apiTokens->saveOrFail($refreshToken);

            return $this->issueTokenPair(
                user: $user,
                ip: $ip,
                userAgent: $userAgent,
                familyId: $refreshToken->family_id,
                parentRefreshTokenId: $refreshToken->id,
            );
        });

        return [
            'ok' => true,
            'tokens' => $tokens,
        ];
    }

    public function revokeByRawToken(string $rawToken, string $reason = 'logout'): void
    {
        $parsed = $this->parseRawToken($rawToken);
        if ($parsed === null) {
            return;
        }

        $token = $this->apiTokens->find()
            ->where(['token_id' => $parsed['token_id']])
            ->first();

        if (!$token instanceof ApiToken) {
            return;
        }

        if (!$this->hashMatches($token->token_hash, $parsed['secret'])) {
            return;
        }

        if ($token->revoked_at !== null) {
            return;
        }

        $token->revoked_at = FrozenTime::now();
        $token->revoked_reason = $reason;
        $this->apiTokens->save($token);
    }

    public function revokeFamily(string $familyId, string $reason = 'logout_all'): int
    {
        return $this->apiTokens->updateAll(
            [
                'revoked_at' => FrozenTime::now(),
                'revoked_reason' => $reason,
            ],
            [
                'family_id' => $familyId,
                'revoked_at IS' => null,
            ],
        );
    }

    public function revokeAllForUser(int $userId, string $reason = 'logout_all'): int
    {
        return $this->apiTokens->updateAll(
            [
                'revoked_at' => FrozenTime::now(),
                'revoked_reason' => $reason,
            ],
            [
                'user_id' => $userId,
                'revoked_at IS' => null,
            ],
        );
    }

    public function cleanup(int $retentionDays = 30): int
    {
        $threshold = FrozenTime::now()->subDays($retentionDays);

        return $this->apiTokens->deleteAll([
            'OR' => [
                ['expires_at <' => FrozenTime::now()],
                [
                    'revoked_at IS NOT' => null,
                    'updated_at <' => $threshold,
                ],
            ],
        ]);
    }

    /**
     * @return array{ok: bool, code?: string, token?: \App\Model\Entity\ApiToken}
     */
    private function validateToken(string $rawToken, string $expectedType, bool $containUser = false): array
    {
        $parsed = $this->parseRawToken($rawToken);
        if ($parsed === null) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::TOKEN_INVALID];
        }

        // Load by token_id first so we can provide better diagnostics
        // (e.g. refresh token used where access token is required).
        $query = $this->apiTokens->find()->where([
            'token_id' => $parsed['token_id'],
        ]);

        if ($containUser) {
            $query->contain(['Users']);
        }

        $token = $query->first();
        if (!$token instanceof ApiToken) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::TOKEN_INVALID];
        }

        if ((string)$token->token_type !== $expectedType) {
            return [
                'ok' => false,
                'code' => ApiAuthErrorCodes::TOKEN_INVALID,
                'reason' => 'wrong_token_type',
            ];
        }

        if (!$this->hashMatches($token->token_hash, $parsed['secret'])) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::TOKEN_INVALID];
        }

        if ($token->expires_at <= FrozenTime::now()) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::TOKEN_EXPIRED];
        }

        if ($token->revoked_at !== null) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::TOKEN_REVOKED, 'token' => $token];
        }

        return ['ok' => true, 'token' => $token];
    }

    /**
     * @return array{0: string, 1: \App\Model\Entity\ApiToken}
     */
    private function createToken(
        User $user,
        string $tokenType,
        string $familyId,
        string $ip,
        string $userAgent,
        int $ttlSeconds,
        ?int $parentTokenId,
    ): array {
        $tokenId = $this->randomId(16);
        $secret = $this->randomId(32);
        $plainToken = $tokenId . '.' . $secret;

        $entity = $this->apiTokens->newEntity([
            'user_id' => $user->id,
            'token_id' => $tokenId,
            'token_hash' => hash('sha256', $secret),
            'token_type' => $tokenType,
            'family_id' => $familyId,
            'parent_token_id' => $parentTokenId,
            'expires_at' => FrozenTime::now()->addSeconds($ttlSeconds),
            'issued_ip' => $ip,
            'issued_user_agent' => substr($userAgent, 0, 255),
        ]);

        if (!$this->apiTokens->save($entity)) {
            throw new PersistenceFailedException($entity, 'Failed to persist API token');
        }

        return [$plainToken, $entity];
    }

    /**
     * @return array{token_id: string, secret: string}|null
     */
    private function parseRawToken(string $rawToken): ?array
    {
        $parts = explode('.', trim($rawToken), 2);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return null;
        }

        return [
            'token_id' => $parts[0],
            'secret' => $parts[1],
        ];
    }

    private function hashMatches(string $storedHash, string $secret): bool
    {
        return hash_equals($storedHash, hash('sha256', $secret));
    }

    private function randomId(int $bytes): string
    {
        return bin2hex(random_bytes($bytes));
    }

    private function accessTtlMinutes(): int
    {
        return max(1, (int)env('API_ACCESS_TTL_MINUTES', '15'));
    }

    private function refreshTtlDays(): int
    {
        return max(1, (int)env('API_REFRESH_TTL_DAYS', '30'));
    }
}
