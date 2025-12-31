<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\Role;
use App\Service\UserTokensService;
use Cake\Cache\Cache;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;
use Exception;

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
            $user = $this->Users->patchEntity($user, $this->request->getData());
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
            $user = $this->Users->patchEntity($user, $this->request->getData());
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
            'activation',
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

    /**
     * @return \Cake\Http\Response|null
     */
    public function login(): ?Response
    {
        $this->request->allowMethod(['get', 'post']);

        // Rate limit only matters on POST attempts
        if ($this->request->is('post')) {
            $ip = (string)$this->request->clientIp();
            if (!$this->rateLimitAllow($ip)) {
                $this->Flash->error(__('Too many login attempts. Please try again in a minute.'));

                return $this->redirect($this->request->getRequestTarget());
            }
        }

        $result = $this->Authentication->getResult();
        $email = (string)$this->request->getData('email');
        $u = $this->fetchTable('Users')->find()->where(['email' => $email])->first();
        Log::debug('User lookup: ' . ($u ? 'FOUND id=' . $u->id : 'NOT FOUND'));

        // If already logged in, redirect away from login page
        if ($result && $result->isValid()) {
            $user = $this->Authentication->getIdentity();
            if ($user) {
                // Gate: inactive / blocked users must not stay logged in
                $isActive = (bool)$user->get('is_active');
                $isBlocked = (bool)$user->get('is_blocked');

                if (!$isActive || $isBlocked) {
                    $this->Authentication->logout();

                    // Count this as a "failed" attempt too
                    $ip = (string)$this->request->clientIp();
                    $this->rateLimitHit($ip);

                    $this->Flash->error(__('Invalid email or password.'));

                    return null; // render login
                }
            }

            // Success: clear rate limit counter for this IP
            $ip = (string)$this->request->clientIp();
            $this->rateLimitClear($ip);

            $lang = $this->request->getParam('lang', 'en');

            $redirect = $this->request->getQuery('redirect', [
                'controller' => 'Pages',
                'action' => 'display',
                'home',
                'lang' => $lang,
            ]);

            return $this->redirect($redirect);
        }

        // POST + invalid credentials
        if ($this->request->is('post') && (!$result || !$result->isValid())) {
            $ip = (string)$this->request->clientIp();
            $this->rateLimitHit($ip);

            $this->Flash->error(__('Invalid email or password.'));
        }

        return null; // render login template
    }

    /**
     * @return \Cake\Http\Response
     */
    public function logout(): Response
    {
        $this->request->allowMethod(['post', 'get']); // if you want GET logout; POST-only is stricter

        $this->Authentication->logout();

        // Optional: also clear limiter for current IP
        $ip = (string)$this->request->clientIp();
        $this->rateLimitClear($ip);

        $this->Flash->success(__('You are now logged out.'));

        $lang = $this->request->getParam('lang', 'en');

        return $this->redirect(['controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
    }

    /**
     * --- Simple rate limiting (N attempts / IP / minute) using Cache ---
     * Adjust limits here.
     */
    private function rateLimitKey(string $ip): string
    {
        return 'login_rl_' . preg_replace('/[^0-9a-fA-F\:\.]/', '_', $ip);
    }

    /**
     * @param string $ip
     * @return bool
     */
    private function rateLimitAllow(string $ip): bool
    {
        $key = $this->rateLimitKey($ip);
        $data = Cache::read($key, 'default');

        $maxAttempts = 5;
        $windowSeconds = 60;

        if (!is_array($data)) {
            return true;
        }

        $count = (int)($data['count'] ?? 0);
        $start = (int)($data['start'] ?? 0);

        if ($start <= 0 || (time() - $start) > $windowSeconds) {
            return true; // window expired
        }

        return $count < $maxAttempts;
    }

    /**
     * @param string $ip
     * @return void
     */
    private function rateLimitHit(string $ip): void
    {
        $key = $this->rateLimitKey($ip);
        $data = Cache::read($key, 'default');

        $windowSeconds = 60;

        if (!is_array($data)) {
            $data = ['count' => 0, 'start' => time()];
        }

        $start = (int)($data['start'] ?? 0);
        if ($start <= 0 || (time() - $start) > $windowSeconds) {
            $data = ['count' => 0, 'start' => time()];
        }

        $data['count'] = ((int)$data['count']) + 1;

        // Keep it around slightly longer than the window
        Cache::write($key, $data, 'default');
    }

    /**
     * @param string $ip
     * @return void
     */
    private function rateLimitClear(string $ip): void
    {
        Cache::delete($this->rateLimitKey($ip), 'default');
    }
}
