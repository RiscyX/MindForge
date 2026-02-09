<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\ActivityLog;
use App\Model\Entity\Role;
use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Table;
use Detection\MobileDetect;
use Throwable;
use function Cake\Core\env;

class ApiAuthService
{
    public function __construct(
        private UsersTable $users,
        private ApiTokenService $apiTokenService,
        private Table $activityLogs,
        private Table $deviceLogs,
    ) {
    }

    /**
     * @return array{ok: bool, code?: string, user?: \App\Model\Entity\User, tokens?: array<string, mixed>}
     */
    public function login(string $email, string $password, string $ip, string $userAgent): array
    {
        /** @var \App\Model\Entity\User|null $user */
        $user = $this->users->find()->where(['email' => trim($email)])->first();
        if ($user === null) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::INVALID_CREDENTIALS];
        }

        $hasher = new DefaultPasswordHasher();
        if (!$hasher->check($password, (string)$user->password_hash)) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::INVALID_CREDENTIALS];
        }

        $stateError = $this->validateUserState($user);
        if ($stateError !== null) {
            return ['ok' => false, 'code' => $stateError];
        }

        $tokens = $this->apiTokenService->issueTokenPair($user, $ip, $userAgent);

        $this->updateLastLoginAt((int)$user->id);
        $this->logActivity((int)$user->id, ActivityLog::TYPE_LOGIN, $ip, $userAgent);
        $this->logDevice((int)$user->id, $ip, $userAgent);

        return [
            'ok' => true,
            'user' => $user,
            'tokens' => $tokens,
        ];
    }

    /**
     * @return array{ok: bool, code?: string, user?: \App\Model\Entity\User, tokens?: array<string, mixed>}
     */
    public function refresh(string $refreshToken, string $ip, string $userAgent): array
    {
        $preValidation = $this->apiTokenService->validateRefreshToken($refreshToken);
        if (!$preValidation['ok']) {
            if (($preValidation['code'] ?? null) === ApiAuthErrorCodes::TOKEN_REUSED && isset($preValidation['token'])) {
                /** @var \App\Model\Entity\ApiToken $reusedToken */
                $reusedToken = $preValidation['token'];
                $this->apiTokenService->revokeFamily($reusedToken->family_id, 'reuse_detected');
                if ($reusedToken->user !== null) {
                    $this->logActivity((int)$reusedToken->user_id, ActivityLog::TYPE_API_TOKEN_REUSE_DETECTED, $ip, $userAgent);
                }
            }

            return [
                'ok' => false,
                'code' => $preValidation['code'] ?? ApiAuthErrorCodes::TOKEN_INVALID,
            ];
        }

        /** @var \App\Model\Entity\ApiToken $preValidatedToken */
        $preValidatedToken = $preValidation['token'];
        /** @var \App\Model\Entity\User|null $preValidatedUser */
        $preValidatedUser = $preValidatedToken->user;
        if ($preValidatedUser === null) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::TOKEN_INVALID];
        }

        $stateError = $this->validateUserState($preValidatedUser);
        if ($stateError !== null) {
            $this->apiTokenService->revokeAllForUser((int)$preValidatedUser->id, 'state_invalid_before_refresh');

            return ['ok' => false, 'code' => $stateError];
        }

        $result = $this->apiTokenService->rotateRefreshToken($refreshToken, $ip, $userAgent);
        if (!$result['ok']) {
            if (($result['code'] ?? null) === ApiAuthErrorCodes::TOKEN_REUSED) {
                $user = $this->findUserByRefreshToken($refreshToken);
                if ($user !== null) {
                    $this->logActivity((int)$user->id, ActivityLog::TYPE_API_TOKEN_REUSE_DETECTED, $ip, $userAgent);
                }
            }

            return [
                'ok' => false,
                'code' => $result['code'] ?? ApiAuthErrorCodes::TOKEN_INVALID,
            ];
        }

        $user = $this->findUserByRefreshToken($refreshToken);
        if ($user === null) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::TOKEN_INVALID];
        }

        $stateError = $this->validateUserState($user);
        if ($stateError !== null) {
            $this->apiTokenService->revokeAllForUser((int)$user->id, 'state_invalid_after_refresh');

            return ['ok' => false, 'code' => $stateError];
        }

        return [
            'ok' => true,
            'user' => $user,
            'tokens' => $result['tokens'] ?? [],
        ];
    }

    public function logout(?string $accessToken, ?string $refreshToken, bool $allDevices, ?User $user = null): void
    {
        if ($allDevices && $user !== null) {
            $this->apiTokenService->revokeAllForUser((int)$user->id, 'logout_all_devices');

            return;
        }

        if (is_string($accessToken) && $accessToken !== '') {
            $this->apiTokenService->revokeByRawToken($accessToken, 'logout');
        }

        if (is_string($refreshToken) && $refreshToken !== '') {
            $this->apiTokenService->revokeByRawToken($refreshToken, 'logout');
        }
    }

    public function validateUserState(User $user): ?string
    {
        if (!(bool)$user->is_active) {
            return ApiAuthErrorCodes::USER_INACTIVE;
        }

        if ((bool)$user->is_blocked) {
            return ApiAuthErrorCodes::USER_BLOCKED;
        }

        $allowedRoles = [Role::ADMIN, Role::CREATOR, Role::USER];
        if (!in_array((int)$user->role_id, $allowedRoles, true)) {
            return ApiAuthErrorCodes::FORBIDDEN_ROLE;
        }

        return null;
    }

    /**
     * @param \Cake\ORM\Table $userTokensTable
     * @return array{ok: bool, code?: string, errors?: array<string, mixed>, email_sent?: bool, user_id?: int}
     */
    public function register(
        string $email,
        string $password,
        string $passwordConfirm,
        string $lang,
        string $baseUrl,
        string $ip,
        string $userAgent,
        Table $userTokensTable,
    ): array {
        $email = trim($email);
        if ($password === '' || $passwordConfirm === '' || $password !== $passwordConfirm) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::PASSWORD_MISMATCH];
        }

        if ($this->users->exists(['email' => $email])) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::EMAIL_ALREADY_USED];
        }

        $user = $this->users->newEmptyEntity();
        $user = $this->users->patchEntity($user, [
            'email' => $email,
            'password' => $password,
        ], [
            'fields' => ['email', 'password'],
        ]);

        $user->is_active = false;
        $user->is_blocked = false;
        $user->role_id = Role::USER;
        $user->username = $this->buildRegistrationUsername($email);
        $now = FrozenTime::now();
        $user->created_at = $now;
        $user->updated_at = $now;

        try {
            $this->users->saveOrFail($user);
        } catch (PersistenceFailedException $e) {
            return [
                'ok' => false,
                'code' => ApiAuthErrorCodes::REGISTRATION_FAILED,
                'errors' => $e->getEntity()->getErrors(),
            ];
        } catch (Throwable) {
            return ['ok' => false, 'code' => ApiAuthErrorCodes::REGISTRATION_FAILED];
        }

        $this->logActivity((int)$user->id, 'registration', $ip, $userAgent);

        $tokenService = new UserTokensService($userTokensTable);
        $token = $tokenService->createActivationToken($user);
        $activationUrl = rtrim($baseUrl, '/') . '/' . $lang . '/confirm?token=' . urlencode($token);

        $emailSent = true;
        $locale = $lang === 'hu' ? 'hu_HU' : 'en_US';
        $previousLocale = I18n::getLocale();
        try {
            I18n::setLocale($locale);
            $mailer = new Mailer('default');
            $mailer
                ->setFrom([env('EMAIL_FROM', 'no-reply@mindforge.local') => 'MindForge'])
                ->setTo((string)$user->email)
                ->setEmailFormat('both')
                ->setSubject(__('Activate your MindForge account'))
                ->setViewVars(['activationUrl' => $activationUrl]);
            $mailer->viewBuilder()->setTemplate('activation');
            $mailer->deliver();
        } catch (Throwable $e) {
            $emailSent = false;
            Log::error('Failed to send activation email to ' . $user->email . ': ' . $e->getMessage());
        } finally {
            I18n::setLocale($previousLocale);
        }

        return [
            'ok' => true,
            'user_id' => (int)$user->id,
            'email_sent' => $emailSent,
        ];
    }

    private function findUserByRefreshToken(string $refreshToken): ?User
    {
        $parts = explode('.', trim($refreshToken), 2);
        if (count($parts) !== 2 || $parts[0] === '') {
            return null;
        }

        $token = $this->users->ApiTokens->find()
            ->where([
                'token_id' => $parts[0],
                'token_type' => 'refresh',
            ])
            ->contain(['Users'])
            ->first();

        if ($token === null || $token->user === null) {
            return null;
        }

        return $token->user;
    }

    private function logActivity(int $userId, string $action, string $ip, string $userAgent): void
    {
        $entity = $this->activityLogs->newEntity([
            'user_id' => $userId,
            'action' => $action,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
        $this->activityLogs->save($entity);
    }

    private function updateLastLoginAt(int $userId): void
    {
        /** @var \App\Model\Entity\User $user */
        $user = $this->users->get($userId);
        $user->last_login_at = FrozenTime::now();
        $this->users->save($user);
    }

    private function logDevice(int $userId, string $ip, string $userAgent): void
    {
        $deviceType = $this->detectDeviceType($userAgent);

        $entity = $this->deviceLogs->newEntity([
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
        ]);

        $this->deviceLogs->save($entity);
    }

    private function detectDeviceType(string $userAgent): int
    {
        $detect = new MobileDetect();
        $detect->setUserAgent($userAgent);

        if ($detect->isTablet()) {
            return 1;
        }

        if ($detect->isMobile()) {
            return 0;
        }

        $normalizedUa = strtolower($userAgent);

        $tabletHints = [
            'ipad',
            'tablet',
            'sm-t',
            'kindle',
            'silk/',
            'playbook',
        ];

        foreach ($tabletHints as $hint) {
            if (str_contains($normalizedUa, $hint)) {
                return 1;
            }
        }

        $mobileHints = [
            'okhttp/',
            'dalvik/',
            'android',
            'iphone',
            'ipod',
            'cfnetwork/',
            'mobile',
            'reactnative',
            'expo',
            'flutter',
        ];

        foreach ($mobileHints as $hint) {
            if (str_contains($normalizedUa, $hint)) {
                return 0;
            }
        }

        return 2;
    }

    private function buildRegistrationUsername(string $email): string
    {
        $localPart = strtolower(trim(strtok($email, '@') ?: ''));
        $base = preg_replace('/[^a-z0-9._-]/', '', $localPart) ?? '';
        if ($base === '') {
            $base = 'user';
        }

        $base = substr($base, 0, 50);
        $username = $base;
        $counter = 2;

        while ($this->users->exists(['username' => $username])) {
            $suffix = '_' . $counter;
            $username = substr($base, 0, max(1, 50 - strlen($suffix))) . $suffix;
            $counter++;
        }

        return $username;
    }
}
