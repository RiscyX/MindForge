<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use Exception;
use function Cake\Core\env;

class PasswordResetService
{
    use LocatorAwareTrait;

    public const RESET_OK = 'ok';
    public const RESET_PASSWORD_MISMATCH = 'password_mismatch';
    public const RESET_TOKEN_INVALID = 'token_invalid';
    public const RESET_SAVE_FAILED = 'save_failed';

    /**
     * Request a password reset by sending a reset email to the user.
     *
     * @param string $email User email address.
     * @param string $lang Language code for the email.
     * @return void
     */
    public function requestReset(string $email, string $lang = 'en'): void
    {
        $email = trim($email);
        $lang = in_array($lang, ['en', 'hu'], true) ? $lang : 'en';
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        /** @var \App\Model\Entity\User|null $user */
        $user = $users->find()->where(['email' => $email])->first();
        if ($user === null) {
            return;
        }

        /** @var \App\Model\Table\UserTokensTable $userTokens */
        $userTokens = $this->fetchTable('UserTokens');
        $tokenService = new UserTokensService($userTokens);
        $token = $tokenService->createPasswordResetToken($user);

        $baseUrl = rtrim((string)env('BASE_URL', Router::url('/', true)), '/');
        $resetUrl = $baseUrl . '/' . $lang . '/reset-password?token=' . urlencode($token);

        $locale = $lang === 'hu' ? 'hu_HU' : 'en_US';
        $previousLocale = I18n::getLocale();
        try {
            I18n::setLocale($locale);
            $mailer = new Mailer('default');
            $mailer
                ->setFrom([env('EMAIL_FROM', 'no-reply@mindforge.local') => 'MindForge'])
                ->setTo($user->email)
                ->setEmailFormat('both')
                ->setSubject(__('Reset your MindForge password'))
                ->setViewVars(['resetUrl' => $resetUrl]);
            $mailer->viewBuilder()->setTemplate('password_reset');
            $mailer->deliver();
            Log::info('Password reset email sent to: ' . $user->email);
        } catch (Exception $e) {
            Log::error('Password reset email send failed for ' . $email . ': ' . $e->getMessage());
        } finally {
            I18n::setLocale($previousLocale);
        }
    }

    /**
     * @return array{ok: bool, code: string}
     */
    public function resetPasswordWithToken(string $token, string $password, string $passwordConfirm): array
    {
        $token = trim($token);
        if ($password === '' || $passwordConfirm === '' || $password !== $passwordConfirm) {
            return ['ok' => false, 'code' => self::RESET_PASSWORD_MISMATCH];
        }

        /** @var \App\Model\Table\UserTokensTable $userTokens */
        $userTokens = $this->fetchTable('UserTokens');
        $tokenService = new UserTokensService($userTokens);
        $userToken = $tokenService->validatePasswordResetToken($token);
        if ($userToken === null) {
            return ['ok' => false, 'code' => self::RESET_TOKEN_INVALID];
        }

        $user = $userToken->user;
        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->fetchTable('Users');
        $users->patchEntity($user, ['password' => $password], ['fields' => ['password']]);
        if (!$users->save($user) || !$tokenService->markTokenAsUsed($userToken)) {
            return ['ok' => false, 'code' => self::RESET_SAVE_FAILED];
        }

        return ['ok' => true, 'code' => self::RESET_OK];
    }

    /**
     * Check if a password reset token is still valid.
     *
     * @param string $token Reset token.
     * @return bool
     */
    public function isResetTokenValid(string $token): bool
    {
        /** @var \App\Model\Table\UserTokensTable $userTokens */
        $userTokens = $this->fetchTable('UserTokens');
        $tokenService = new UserTokensService($userTokens);

        return $tokenService->validatePasswordResetToken(trim($token)) !== null;
    }
}
