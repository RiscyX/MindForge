<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Role;
use App\Service\UserTokensService;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;
use Exception;
use function Cake\Core\env;

/**
 * Auth Controller
 *
 * Handles user authentication including login and registration.
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class AuthController extends AppController
{
    /**
     * Display login page.
     *
     * @return void
     */
    public function login(): void
    {
    }

    /**
     * Handle user registration.
     *
     * @return \Cake\Http\Response|null
     */
    public function register(): ?Response
    {
        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $user = $usersTable->patchEntity(
                $user,
                $this->request->getData(),
                ['fields' => ['email', 'password', 'password_confirm']],
            );

            // Basic password confirmation check (front-end also validates)
            $password = (string)$this->request->getData('password');
            $passwordConfirm = (string)$this->request->getData('password_confirm');

            if ($password === '' || $passwordConfirm === '' || $password !== $passwordConfirm) {
                $this->Flash->error(__('Passwords do not match.'));
                $this->set(compact('user'));

                return null;
            }

            $user->is_active = false;
            $user->is_blocked = false;
            $user->role_id = Role::USER;
            $user->created_at = FrozenTime::now();
            $user->updated_at = FrozenTime::now();

            if ($usersTable->save($user)) {
                /** @var \App\Model\Table\UserTokensTable $userTokensTable */
                $userTokensTable = $this->fetchTable('UserTokens');

                $tokenService = new UserTokensService($userTokensTable);
                $token = $tokenService->createActivationToken($user);

                $lang = $this->request->getParam('lang', 'en');
                $baseUrl = rtrim((string)env('BASE_URL', Router::url('/', true)), '/');
                $activationUrl = $baseUrl . '/' . $lang . '/confirm?token=' . urlencode($token);

                // Set locale for email
                $locale = $lang === 'hu' ? 'hu_HU' : 'en_US';

                try {
                    $mailer = new Mailer('default');
                    $mailer
                        ->setFrom([env('EMAIL_FROM', 'no-reply@mindforge.local') => 'MindForge'])
                        ->setTo($user->email)
                        ->setEmailFormat('both')
                        ->setSubject(__('Activate your MindForge account'))
                        ->setViewVars(['activationUrl' => $activationUrl])
                        ->viewBuilder()
                            ->setTemplate('activation');

                    // Set locale in the mailer's renderer
                    I18n::setLocale($locale);

                    $mailer->deliver();

                    Log::info('Activation email sent to: ' . $user->email);
                    $this->Flash->success(__('Check your email to activate your account.'));
                } catch (Exception $e) {
                    // Email failed but registration succeeded - log for debugging
                    Log::error('Failed to send activation email to ' . $user->email . ': ' . $e->getMessage());

                    // Still show success, user can request resend later
                    $this->Flash->warning(__('Registration successful
                     but email failed. Activation link: {0}', $activationUrl));
                }

                return $this->redirect(['action' => 'login', 'lang' => $lang]);
            }

            $this->Flash->error(__('Registration failed.'));
        }

        $this->set(compact('user'));

        return null;
    }

    /**
     * Confirm user account activation via token.
     *
     * @return \Cake\Http\Response|null
     */
    public function confirm(): ?Response
    {
        $token = (string)$this->request->getQuery('token');
        $lang = $this->request->getParam('lang', 'en');

        /** @var \App\Model\Table\UserTokensTable $userTokensTable */
        $userTokensTable = $this->fetchTable('UserTokens');

        $tokenService = new UserTokensService($userTokensTable);
        $userToken = $tokenService->validateActivationToken($token);

        if ($userToken === null) {
            $this->Flash->error(__('Invalid or expired activation token.'));

            return $this->redirect(['action' => 'login', 'lang' => $lang]);
        }

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $user = $userToken->user;
        $user->is_active = true;

        if ($usersTable->save($user) && $tokenService->markTokenAsUsed($userToken)) {
            $this->Flash->success(__('Your account has been activated. You can now log in.'));

            return $this->redirect(['action' => 'login', 'lang' => $lang]);
        }

        $this->Flash->error(__('Failed to activate your account. Please try again.'));

        return $this->redirect(['action' => 'login', 'lang' => $lang]);
    }
}
