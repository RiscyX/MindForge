<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AvatarUploadService;
use App\Service\LanguageResolverService;
use App\Service\PasswordResetService;
use App\Service\UserStatsService;
use App\Service\UserTokensService;
use App\Service\WebLoginService;
use App\Service\WebUserRegistrationService;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Routing\Router;
use Psr\Http\Message\UploadedFileInterface;
use function Cake\Core\env;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Users->find()
            ->contain(['Roles']);
        $users = $this->paginate($query);

        $this->set(compact('users'));
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $user = $this->Users->get($id, contain: ['Roles', 'ActivityLogs',
            'AiRequests', 'DeviceLogs', 'TestAttempts', 'UserTokens']);
        $this->set(compact('user'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $user = $this->Users->newEmptyEntity();
        if ($this->request->is('post')) {
            $user = $this->Users->patchEntity($user, $this->request->getData(), [
                'fields' => ['email', 'username', 'password', 'avatar_url'],
            ]);
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $roles = $this->Users->Roles->find('list', limit: 200)->all();
        $this->set(compact('user', 'roles'));
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $user = $this->Users->get($id, contain: []);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->request->getData(), [
                'fields' => ['email', 'username', 'password', 'avatar_url'],
            ]);
            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }
        $roles = $this->Users->Roles->find('list', limit: 200)->all();
        $this->set(compact('user', 'roles'));
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $user = $this->Users->get($id);
        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * @param \Cake\Event\EventInterface $event
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Authentication->addUnauthenticatedActions([
            'login',
            'logout',
            'register',
            'confirm',
            'resendActivation',
            'forgotPassword',
            'resetPassword',
        ]);
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function register(): ?Response
    {
        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->newEmptyEntity();

        if (!$this->request->is('post')) {
            $this->set(compact('user'));

            return null;
        }

        $lang = (string)$this->request->getParam('lang', 'en');
        $baseUrl = rtrim((string)env('BASE_URL', Router::url('/', true)), '/');

        $registrationService = new WebUserRegistrationService();
        $result = $registrationService->register(
            requestData: $this->request->getData(),
            lang: $lang,
            baseUrl: $baseUrl,
            ipAddress: (string)$this->request->clientIp(),
            userAgent: (string)$this->request->getHeaderLine('User-Agent'),
        );

        if (!$result['ok']) {
            $user = $result['user'];

            if ($result['code'] === 'RATE_LIMITED') {
                $this->Flash->error(__('Too many registration attempts. Please try again later.'));
            } elseif ($result['code'] === 'PASSWORD_MISMATCH') {
                $this->Flash->error(__('Passwords do not match.'));
            } else {
                $this->Flash->error(__('Registration failed.'));
            }

            $this->set(compact('user'));

            return null;
        }

        if ($result['email_sent'] ?? false) {
            $this->Flash->success(__('Check your email to activate your account.'));
        } else {
            $this->Flash->warning(__(
                'Registration successful but activation email could not be sent. Please contact support.',
            ));
        }

        return $this->redirect(['action' => 'login', 'lang' => $lang]);
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
            $resendUrl = $this->Url->build(['action' => 'resendActivation', 'lang' => $lang]);
            $this->Flash->error(
                __('Invalid or expired activation token.') .
                ' <a href="' . h($resendUrl) . '">' . __('Request a new activation email.') . '</a>',
                ['escape' => false],
            );

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

    /**
     * Resend an activation email to an unactivated account.
     *
     * Accepts GET (show form) and POST (process request).
     * Always returns a generic success response to prevent email enumeration.
     *
     * @return \Cake\Http\Response|null
     */
    public function resendActivation(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $this->set(compact('lang'));

        if (!$this->request->is('post')) {
            return null;
        }

        $email = trim((string)$this->request->getData('email'));
        $baseUrl = rtrim((string)env('BASE_URL', Router::url('/', true)), '/');

        $registrationService = new WebUserRegistrationService();
        $registrationService->resendActivation(
            email: $email,
            lang: $lang,
            baseUrl: $baseUrl,
            ipAddress: (string)$this->request->clientIp(),
        );

        // Always show a generic success message (no enumeration)
        $this->Flash->success(__('If the email exists and is awaiting activation, we sent a new activation link.'));

        return $this->redirect(['action' => 'login', 'lang' => $lang]);
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function login(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);

        $authResult = $this->Authentication->getResult();
        $identity = $this->Authentication->getIdentity();

        $identityData = null;
        if ($authResult && $authResult->isValid() && $identity) {
            $identityData = [
                'id' => $identity->getIdentifier(),
                'email' => $identity->get('email'),
                'is_active' => $identity->get('is_active'),
                'is_blocked' => $identity->get('is_blocked'),
                'role_id' => $identity->get('role_id'),
            ];
        }

        $loginService = new WebLoginService();
        $result = $loginService->handleLogin(
            isPost: $this->request->is('post'),
            isAuthenticated: $authResult && $authResult->isValid(),
            identityData: $identityData,
            email: (string)$this->request->getData('email'),
            ip: (string)$this->request->clientIp(),
            userAgent: (string)$this->request->getHeaderLine('User-Agent'),
            lang: (string)$this->request->getParam('lang', 'en'),
            queryRedirect: $this->request->getQuery('redirect'),
        );

        // Service may instruct us to log out (inactive/blocked gate)
        if (!empty($result['should_logout'])) {
            $this->Authentication->logout();
        }

        // Flash message if present
        if (!empty($result['flash_message']) && !empty($result['flash_type'])) {
            $this->Flash->{$result['flash_type']}($result['flash_message']);
        }

        // Rate-limited redirect goes back to the same URL
        if ($result['code'] === WebLoginService::CODE_RATE_LIMITED) {
            return $this->redirect($this->request->getRequestTarget());
        }

        // Redirect if the service resolved a target
        if ($result['action'] === WebLoginService::RESULT_REDIRECT && isset($result['redirect_url'])) {
            return $this->redirect($result['redirect_url']);
        }

        return null; // render login template
    }

    /**
     * @return \Cake\Http\Response
     */
    public function logout(): Response
    {
        $this->request->allowMethod(['post']);

        $user = $this->Authentication->getIdentity();
        $ip = (string)$this->request->clientIp();
        $userAgent = (string)$this->request->getHeaderLine('User-Agent');

        $loginService = new WebLoginService();
        $loginService->handleLogout(
            $user ? (int)$user->getIdentifier() : null,
            $ip,
            $userAgent,
        );

        $this->Authentication->logout();

        $this->Flash->success(__('You are now logged out.'));

        $lang = $this->request->getParam('lang', 'en');

        return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
    }

    /**
     * Forgot password: request a password reset email.
     *
     * @return \Cake\Http\Response|null
     */
    public function forgotPassword(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $this->set(compact('lang'));

        if (!$this->request->is('post')) {
            return null;
        }

        $email = trim((string)$this->request->getData('email'));

        // Always show the same response to avoid account enumeration.
        $genericSuccess = __('If the email exists in our system, we sent a password reset link.');

        $passwordResetService = new PasswordResetService();
        $passwordResetService->requestReset($email, $lang);

        $this->Flash->success($genericSuccess);

        return $this->redirect(['action' => 'login', 'lang' => $lang]);
    }

    /**
     * Reset password: validate token and set a new password.
     *
     * @return \Cake\Http\Response|null
     */
    public function resetPassword(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $token = (string)$this->request->getQuery('token');

        $passwordResetService = new PasswordResetService();
        if (!$passwordResetService->isResetTokenValid($token)) {
            $this->Flash->error(__('Invalid or expired reset token.'));

            return $this->redirect(['action' => 'forgotPassword', 'lang' => $lang]);
        }

        $this->set(compact('lang', 'token'));

        if (!$this->request->is('post')) {
            return null;
        }

        $password = (string)$this->request->getData('password');
        $passwordConfirm = (string)$this->request->getData('password_confirm');

        if ($password === '' || $passwordConfirm === '' || $password !== $passwordConfirm) {
            $this->Flash->error(__('Passwords do not match.'));

            return null;
        }

        $result = $passwordResetService->resetPasswordWithToken($token, $password, $passwordConfirm);
        if ($result['ok']) {
            $this->Flash->success(__('Your password has been reset. You can now log in.'));

            return $this->redirect(['action' => 'login', 'lang' => $lang]);
        }

        if ($result['code'] === PasswordResetService::RESET_PASSWORD_MISMATCH) {
            $this->Flash->error(__('Passwords do not match.'));

            return null;
        }

        if ($result['code'] === PasswordResetService::RESET_TOKEN_INVALID) {
            $this->Flash->error(__('Invalid or expired reset token.'));

            return $this->redirect(['action' => 'forgotPassword', 'lang' => $lang]);
        }

        $this->Flash->error(__('Failed to reset password. Please try again.'));

        return null;
    }

    /**
     * Profile method for the logged-in user.
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function profile()
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['action' => 'login']);
        }

        $userId = (int)$identity->getIdentifier();
        $user = $this->Users->get($userId);

        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);
        $statsService = new UserStatsService();
        $this->set(['user' => $user] + $statsService->buildUserStatsData($userId, $languageId));
    }

    /**
     * Profile edit method for the logged-in user.
     *
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     */
    public function profileEdit()
    {
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            return $this->redirect(['action' => 'login']);
        }

        $user = $this->Users->get($identity->getIdentifier());

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Handle Avatar Upload
            $avatarFile = $data['avatar_file'] ?? null;
            if ($avatarFile instanceof UploadedFileInterface) {
                if ($avatarFile->getError() === UPLOAD_ERR_NO_FILE) {
                    $avatarFile = null;
                }
            }

            if ($avatarFile instanceof UploadedFileInterface) {
                $avatarService = new AvatarUploadService();
                $result = $avatarService->upload($avatarFile, $user->id, (string)($user->avatar_url ?? ''));

                if ($result['ok']) {
                    $data['avatar_url'] = $result['avatar_url'];
                } else {
                    $this->Flash->error(__($result['error'] ?? 'Failed to upload avatar.'));
                }
            }
            unset($data['avatar_file']); // Remove file object from data to avoid patching issues if not handled by table

            $user = $this->Users->patchEntity($user, $data, [
                'fields' => ['username', 'avatar_url'],
            ]);
            if ($this->Users->save($user)) {
                $this->Flash->success(__('Your profile has been updated.'));
                $lang = $this->request->getParam('lang', 'en');

                return $this->redirect(['action' => 'profile', 'lang' => $lang]);
            }
            $this->Flash->error(__('Unable to update your profile. Please try again.'));
        }

        $this->set(compact('user'));
    }

    /**
     * Statistics for the logged-in user.
     *
     * @return \Cake\Http\Response|null|void
     */
    public function stats()
    {
        $this->request->allowMethod(['get']);

        $lang = (string)$this->request->getParam('lang', 'en');
        $identity = $this->Authentication->getIdentity();
        if (!$identity) {
            $this->Flash->error(__('Please log in to view your stats.'));

            return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $userId = (int)$identity->getIdentifier();
        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));
        $languageId = (new LanguageResolverService())->resolveId($langCode);
        $statsService = new UserStatsService();
        $this->set($statsService->buildUserStatsData($userId, $languageId));
    }
}
