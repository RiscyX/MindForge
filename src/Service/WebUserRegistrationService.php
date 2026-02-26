<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Role;
use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\TableRegistry;
use Throwable;
use function Cake\Core\env;

/**
 * Handles web registration workflow (user save, token creation, activation email).
 */
class WebUserRegistrationService
{
    /**
     * @param array<string, mixed> $requestData
     * @param string $lang
     * @param string $baseUrl
     * @param string $ipAddress
     * @param string $userAgent
     * @return array{ok: bool, code: string, user: object, email_sent?: bool}
     */
    public function register(
        array $requestData,
        string $lang,
        string $baseUrl,
        string $ipAddress,
        string $userAgent,
    ): array {
        // Rate limit: 5 attempts per 60 seconds per IP (prevents email bombing)
        $rateLimiter = new LoginRateLimitService();
        if (!$rateLimiter->isAllowedRegistration($ipAddress)) {
            return [
                'ok' => false,
                'code' => 'RATE_LIMITED',
                'user' => TableRegistry::getTableLocator()->get('Users')->newEmptyEntity(),
            ];
        }
        $rateLimiter->recordRegistrationAttempt($ipAddress);

        $usersTable = TableRegistry::getTableLocator()->get('Users');

        // Silent resend: if email already exists but account is not activated,
        // invalidate the old token, issue a new one and send it again.
        // This prevents "zombie account" lock-out without leaking enumeration info.
        $email = strtolower(trim((string)($requestData['email'] ?? '')));
        if ($email !== '') {
            /** @var \App\Model\Entity\User|null $existingUser */
            $existingUser = $usersTable->find()
                ->where(['email' => $email])
                ->first();

            if ($existingUser !== null) {
                if (!(bool)$existingUser->is_active) {
                    // Re-issue activation token silently
                    $userTokensTable = TableRegistry::getTableLocator()->get('UserTokens');

                    // Invalidate any previous unused activation tokens for this user
                    $userTokensTable->updateAll(
                        ['used_at' => FrozenTime::now()],
                        [
                            'user_id' => $existingUser->id,
                            'type' => 'activate',
                            'used_at IS' => null,
                        ],
                    );

                    $tokenService = new UserTokensService($userTokensTable);
                    $token = $tokenService->createActivationToken($existingUser);

                    $activationUrl = rtrim($baseUrl, '/') . '/' . $lang . '/confirm?token=' . urlencode($token);
                    $emailSent = $this->sendActivationEmail((string)$existingUser->email, $lang, $activationUrl);

                    return [
                        'ok' => true,
                        'code' => 'RESENT',
                        'user' => $existingUser,
                        'email_sent' => $emailSent,
                    ];
                }

                // Email exists and account IS active — fall through to normal save
                // so the duplicate-email validation error is raised naturally.
                // We intentionally return a generic error to avoid enumeration.
                $user = $usersTable->newEmptyEntity();
                $user = $usersTable->patchEntity(
                    $user,
                    $requestData,
                    ['fields' => ['email', 'password', 'password_confirm']],
                );

                return [
                    'ok' => false,
                    'code' => 'SAVE_FAILED',
                    'user' => $user,
                ];
            }
        }

        $user = $usersTable->newEmptyEntity();
        $user = $usersTable->patchEntity(
            $user,
            $requestData,
            ['fields' => ['email', 'password', 'password_confirm']],
        );

        $password = (string)($requestData['password'] ?? '');
        $passwordConfirm = (string)($requestData['password_confirm'] ?? '');
        if ($password === '' || $passwordConfirm === '' || $password !== $passwordConfirm) {
            return [
                'ok' => false,
                'code' => 'PASSWORD_MISMATCH',
                'user' => $user,
            ];
        }

        $user->is_active = false;
        $user->is_blocked = false;
        $user->role_id = Role::USER;
        $user->username = $this->buildRegistrationUsername((string)$user->email);

        $now = FrozenTime::now();
        $user->created_at = $now;
        $user->updated_at = $now;

        try {
            $usersTable->saveOrFail($user);

            $activityLogsTable = TableRegistry::getTableLocator()->get('ActivityLogs');
            $log = $activityLogsTable->newEntity([
                'user_id' => $user->id,
                'action' => 'registration',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
            ]);
            $activityLogsTable->save($log);
        } catch (PersistenceFailedException $e) {
            $errors = $e->getEntity()->getErrors();
            Log::error('Registration failed (validation/rules): ' . json_encode($errors));

            return [
                'ok' => false,
                'code' => 'SAVE_FAILED',
                'user' => $e->getEntity(),
            ];
        } catch (Throwable $e) {
            Log::error('Registration failed (exception): ' . $e->getMessage());

            return [
                'ok' => false,
                'code' => 'SAVE_FAILED',
                'user' => $user,
            ];
        }

        $userTokensTable = TableRegistry::getTableLocator()->get('UserTokens');
        $tokenService = new UserTokensService($userTokensTable);
        $token = $tokenService->createActivationToken($user);

        $activationUrl = rtrim($baseUrl, '/') . '/' . $lang . '/confirm?token=' . urlencode($token);
        $emailSent = $this->sendActivationEmail((string)$user->email, $lang, $activationUrl);

        return [
            'ok' => true,
            'code' => 'REGISTERED',
            'user' => $user,
            'email_sent' => $emailSent,
        ];
    }

    /**
     * Re-issue an activation token for an unactivated account.
     *
     * Always returns a generic ok=true response so callers cannot enumerate
     * which email addresses exist in the system.
     *
     * @param string $email
     * @param string $lang
     * @param string $baseUrl
     * @param string $ipAddress
     * @return array{ok: bool, email_sent?: bool}
     */
    public function resendActivation(
        string $email,
        string $lang,
        string $baseUrl,
        string $ipAddress,
    ): array {
        // Rate limit shared with registration attempts
        $rateLimiter = new LoginRateLimitService();
        if (!$rateLimiter->isAllowedRegistration($ipAddress)) {
            // Return generic ok so the rate limit itself is not leaked to the caller
            return ['ok' => true];
        }
        $rateLimiter->recordRegistrationAttempt($ipAddress);

        $email = strtolower(trim($email));
        if ($email === '') {
            return ['ok' => true];
        }

        $usersTable = TableRegistry::getTableLocator()->get('Users');

        /** @var \App\Model\Entity\User|null $user */
        $user = $usersTable->find()
            ->where(['email' => $email, 'is_active' => false])
            ->first();

        if ($user === null) {
            // Email doesn't exist or account is already active — silent no-op
            return ['ok' => true];
        }

        $userTokensTable = TableRegistry::getTableLocator()->get('UserTokens');

        // Invalidate previous unused activation tokens
        $userTokensTable->updateAll(
            ['used_at' => FrozenTime::now()],
            [
                'user_id' => $user->id,
                'type' => 'activate',
                'used_at IS' => null,
            ],
        );

        $tokenService = new UserTokensService($userTokensTable);
        $token = $tokenService->createActivationToken($user);

        $activationUrl = rtrim($baseUrl, '/') . '/' . $lang . '/confirm?token=' . urlencode($token);
        $emailSent = $this->sendActivationEmail((string)$user->email, $lang, $activationUrl);

        return ['ok' => true, 'email_sent' => $emailSent];
    }

    /**
     * @param string $email
     * @return string
     */
    private function buildRegistrationUsername(string $email): string
    {
        $usersTable = TableRegistry::getTableLocator()->get('Users');

        $localPart = strtolower(trim(strtok($email, '@') ?: ''));
        $base = preg_replace('/[^a-z0-9._-]/', '', $localPart) ?? '';
        if ($base === '') {
            $base = 'user';
        }

        $base = substr($base, 0, 50);
        $username = $base;
        $counter = 2;

        while ($usersTable->exists(['username' => $username])) {
            $suffix = '_' . $counter;
            $username = substr($base, 0, max(1, 50 - strlen($suffix))) . $suffix;
            $counter++;
        }

        return $username;
    }

    /**
     * @param string $email
     * @param string $lang
     * @param string $activationUrl
     * @return bool
     */
    private function sendActivationEmail(string $email, string $lang, string $activationUrl): bool
    {
        $locale = $lang === 'hu' ? 'hu_HU' : 'en_US';
        $previousLocale = I18n::getLocale();

        try {
            I18n::setLocale($locale);

            $mailer = new Mailer('default');
            $mailer
                ->setFrom([env('EMAIL_FROM', 'no-reply@mindforge.local') => 'MindForge'])
                ->setTo($email)
                ->setEmailFormat('both')
                ->setSubject(__('Activate your MindForge account'))
                ->setViewVars(['activationUrl' => $activationUrl]);

            $mailer->viewBuilder()->setTemplate('activation');
            $mailer->deliver();

            Log::info('Activation email sent to: ' . $email);

            return true;
        } catch (Throwable $e) {
            Log::error('Failed to send activation email to ' . $email . ': ' . $e->getMessage());

            return false;
        } finally {
            I18n::setLocale($previousLocale);
        }
    }
}
