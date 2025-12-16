<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Role;
use App\Service\UserTokensService;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
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

                $baseUrl = rtrim((string)env('BASE_URL', Router::url('/', true)), '/');
                $activationUrl = $baseUrl . '/activation?token=' . urlencode($token);

                try {
                    $mailer = new Mailer('default');
                    $mailer
                        ->setFrom([env('EMAIL_FROM', 'no-reply@mindforge.local') => 'MindForge'])
                        ->setTo($user->email)
                        ->setEmailFormat('both')
                        ->setSubject(__('Activate your MindForge account'))
                        ->deliver(
                            sprintf(
                                "%s\n\n%s",
                                __('Please activate your account using the link below:'),
                                $activationUrl,
                            ),
                        );

                    Log::info('Activation email sent to: ' . $user->email);
                    $this->Flash->success(__('Check your email to activate your account.'));
                } catch (Exception $e) {
                    // Email failed but registration succeeded - log for debugging
                    Log::error('Failed to send activation email to ' . $user->email . ': ' . $e->getMessage());

                    // Still show success, user can request resend later
                    $this->Flash->warning(__('Registration successful
                     but email failed. Activation link: {0}', $activationUrl));
                }

                return $this->redirect('/');
            }

            $this->Flash->error(__('Registration failed.'));
        }

        $this->set(compact('user'));

        return null;
    }
}
