<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\ActivityLog;
use App\Model\Entity\Role;
use App\Model\Table\UsersTable;
use App\Service\ImageUploadGuard;
use App\Service\UserTokensService;
use Authentication\IdentityInterface;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Client;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Query\SelectQuery;
use Cake\Routing\Router;
use Detection\MobileDetect;
use Exception;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;
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

        if (!$this->request->is('post')) {
            $this->set(compact('user'));

            return null;
        }

        $user = $usersTable->patchEntity(
            $user,
            $this->request->getData(),
            [
                'fields' => ['email', 'password', 'password_confirm'],
            ],
        );

        // Basic password confirmation check (front-end also validates)
        $password = (string)$this->request->getData('password');
        $passwordConfirm = (string)$this->request->getData('password_confirm');

        if ($password === '' || $passwordConfirm === '' || $password !== $passwordConfirm) {
            $this->Flash->error(__('Passwords do not match.'));
            $this->set(compact('user'));

            return null;
        }

        // Set server-controlled fields (never trust client)
        $user->is_active = false;
        $user->is_blocked = false;
        $user->role_id = Role::USER;
        $user->username = $this->buildRegistrationUsername($usersTable, (string)$user->email);

        // Only set timestamps if you actually use these columns and don't have Timestamp behavior
        $now = FrozenTime::now();
        $user->created_at = $now;
        $user->updated_at = $now;

        try {
            $usersTable->saveOrFail($user);

            // Log registration activity
            $activityLogsTable = $this->fetchTable('ActivityLogs');
            $log = $activityLogsTable->newEntity([
                'user_id' => $user->id,
                'action' => 'registration',
                'ip_address' => (string)$this->request->clientIp(),
                'user_agent' => (string)$this->request->getHeaderLine('User-Agent'),
            ]);
            $activityLogsTable->save($log);
        } catch (PersistenceFailedException $e) {
            $errors = $e->getEntity()->getErrors();
            Log::error('Registration failed (validation/rules): ' . json_encode($errors));
            $this->Flash->error(__('Registration failed.'));
            $this->set(compact('user'));

            return null;
        } catch (Throwable $e) {
            Log::error('Registration failed (exception): ' . $e->getMessage());
            $this->Flash->error(__('Registration failed.'));
            $this->set(compact('user'));

            return null;
        }

        /** @var \App\Model\Table\UserTokensTable $userTokensTable */
        $userTokensTable = $this->fetchTable('UserTokens');

        $tokenService = new UserTokensService($userTokensTable);
        $token = $tokenService->createActivationToken($user);

        $lang = (string)$this->request->getParam('lang', 'en');
        $baseUrl = rtrim((string)env('BASE_URL', Router::url('/', true)), '/');
        $activationUrl = $baseUrl . '/' . $lang . '/confirm?token=' . urlencode($token);

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

            Log::info('Activation email sent to: ' . $user->email);
            $this->Flash->success(__('Check your email to activate your account.'));
        } catch (Throwable $e) {
            Log::error('Failed to send activation email to ' . $user->email . ': ' . $e->getMessage());

            // Registration succeeded, only email failed
            $this->Flash->warning(
                __('Registration successful but email failed. Activation link: {0}', $activationUrl),
            );
        } finally {
            I18n::setLocale($previousLocale);
        }

        return $this->redirect(['action' => 'login', 'lang' => $lang]);
    }

    /**
     * Build a unique username from email local-part.
     *
     * @param \App\Model\Table\UsersTable $usersTable Users table instance.
     * @param string $email User email.
     * @return string
     */
    private function buildRegistrationUsername(UsersTable $usersTable, string $email): string
    {
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

                $email = (string)$this->request->getData('email');
                $this->logLoginFailure($email, 'rate_limit');

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

                    $this->logLoginFailure($user->email, 'inactive_or_blocked');

                    $this->Flash->error(__('Invalid email or password.'));

                    return null; // render login
                }

                // Successful login logic
                $this->logLogin($user);
            }

            // Success: clear rate limit counter for this IP
            $ip = (string)$this->request->clientIp();
            $this->rateLimitClear($ip);

            $lang = $this->request->getParam('lang', 'en');

            if ($user && (int)$user->get('role_id') === Role::ADMIN) {
                return $this->redirect([
                    'prefix' => 'Admin',
                    'controller' => 'Dashboard',
                    'action' => 'index',
                    'lang' => $lang,
                ]);
            }

            if ($user && (int)$user->get('role_id') === Role::CREATOR) {
                return $this->redirect([
                    'prefix' => 'QuizCreator',
                    'controller' => 'Dashboard',
                    'action' => 'index',
                    'lang' => $lang,
                ]);
            }

            $redirect = $this->request->getQuery('redirect');
            if ($redirect) {
                return $this->redirect($redirect);
            }

            return $this->redirect([
                'prefix' => false,
                'controller' => 'Tests',
                'action' => 'index',
                'lang' => $lang,
            ]);
        }

        // POST + invalid credentials
        if ($this->request->is('post') && (!$result || !$result->isValid())) {
            $ip = (string)$this->request->clientIp();
            $this->rateLimitHit($ip);

            $email = (string)$this->request->getData('email');
            $this->logLoginFailure($email, 'invalid_credentials');

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

        $user = $this->Authentication->getIdentity();
        if ($user) {
            $this->logLogout($user);
        }

        $this->Authentication->logout();

        // Optional: also clear limiter for current IP
        $ip = (string)$this->request->clientIp();
        $this->rateLimitClear($ip);

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

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->Flash->success($genericSuccess);

            return $this->redirect(['action' => 'login', 'lang' => $lang]);
        }

        /** @var \App\Model\Entity\User|null $user */
        $user = $this->fetchTable('Users')->find()
            ->where(['email' => $email])
            ->first();

        if (!$user) {
            $this->Flash->success($genericSuccess);

            return $this->redirect(['action' => 'login', 'lang' => $lang]);
        }

        /** @var \App\Model\Table\UserTokensTable $userTokensTable */
        $userTokensTable = $this->fetchTable('UserTokens');
        $tokenService = new UserTokensService($userTokensTable);
        $token = $tokenService->createPasswordResetToken($user);

        $baseUrl = rtrim((string)env('BASE_URL', Router::url('/', true)), '/');
        $resetUrl = $baseUrl . '/' . $lang . '/reset-password?token=' . urlencode($token);

        $locale = $lang === 'hu' ? 'hu_HU' : 'en_US';

        try {
            $mailer = new Mailer('default');
            $mailer
                ->setFrom([env('EMAIL_FROM', 'no-reply@mindforge.local') => 'MindForge'])
                ->setTo($user->email)
                ->setEmailFormat('both')
                ->setSubject(__('Reset your MindForge password'))
                ->setViewVars(['resetUrl' => $resetUrl])
                ->viewBuilder()
                ->setTemplate('password_reset');

            I18n::setLocale($locale);

            $mailer->deliver();
            Log::info('Password reset email sent to: ' . $user->email);
        } catch (Exception $e) {
            Log::error('Failed to send password reset email to ' . $email . ': ' . $e->getMessage());
            // Intentionally do not reveal details to user.
        }

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

        /** @var \App\Model\Table\UserTokensTable $userTokensTable */
        $userTokensTable = $this->fetchTable('UserTokens');
        $tokenService = new UserTokensService($userTokensTable);
        $userToken = $tokenService->validatePasswordResetToken($token);

        if ($userToken === null) {
            $this->Flash->error(__('Invalid or expired reset token.'));

            return $this->redirect(['action' => 'forgotPassword', 'lang' => $lang]);
        }

        $user = $userToken->user;
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

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $usersTable->patchEntity($user, ['password' => $password], ['fields' => ['password']]);

        if ($usersTable->save($user) && $tokenService->markTokenAsUsed($userToken)) {
            $this->Flash->success(__('Your password has been reset. You can now log in.'));

            return $this->redirect(['action' => 'login', 'lang' => $lang]);
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

        $this->set(['user' => $user] + $this->buildUserStatsData($userId));
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
                $allowedTypes = (array)Configure::read(
                    'Uploads.avatarAllowedMimeTypes',
                    ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                );
                $maxBytes = (int)Configure::read('Uploads.avatarMaxBytes', 3 * 1024 * 1024);
                $imageUploadGuard = new ImageUploadGuard();

                $mime = null;
                try {
                    $mime = $imageUploadGuard->assertImageUpload($avatarFile, $allowedTypes, $maxBytes);
                } catch (RuntimeException) {
                    $this->Flash->error(__('Invalid image format. Allowed: JPG, PNG, GIF, WEBP'));
                }

                if ($mime !== null) {
                    try {
                        $ext = ImageUploadGuard::extensionForMime($mime);
                        $filename = 'avatar_' . $user->id . '_' . time() . '.' . $ext;

                        $avatarsDir = WWW_ROOT . 'img' . DS . 'avatars';
                        if (!is_dir($avatarsDir) && !mkdir($avatarsDir, 0775, true) && !is_dir($avatarsDir)) {
                            throw new RuntimeException('Avatar upload directory is not writable.');
                        }
                        if (!is_writable($avatarsDir)) {
                            throw new RuntimeException('Avatar upload directory is not writable.');
                        }

                        $targetPath = $avatarsDir . DS . $filename;
                        $avatarFile->moveTo($targetPath);

                        // Delete old avatar if exists and is local
                        if ($user->avatar_url && str_contains((string)$user->avatar_url, 'avatars/')) {
                            $oldRelative = ltrim((string)$user->avatar_url, '/');
                            $oldRelative = preg_replace('#^img/#', '', $oldRelative) ?? $oldRelative;
                            $oldFile = WWW_ROOT . 'img' . DS . str_replace('/', DS, $oldRelative);
                            if (file_exists($oldFile) && is_file($oldFile)) {
                                unlink($oldFile);
                            }
                        }

                        $data['avatar_url'] = 'avatars/' . $filename;
                    } catch (Throwable) {
                        $this->Flash->error(__('Failed to upload avatar.'));
                    }
                }
            }
            unset($data['avatar_file']); // Remove file object from data to avoid patching issues if not handled by table

            $user = $this->Users->patchEntity($user, $data);
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
        $this->set($this->buildUserStatsData($userId));
    }

    /**
     * @param int $userId
     * @return array<string, mixed>
     */
    private function buildUserStatsData(int $userId): array
    {
        $languageId = $this->resolveLanguageIdFromRoute();

        $attemptsTable = $this->fetchTable('TestAttempts');

        $base = $attemptsTable->find()
            ->where([
                'TestAttempts.user_id' => $userId,
                'TestAttempts.test_id IS NOT' => null,
            ])
            ->innerJoinWith('Tests', function (SelectQuery $q) use ($userId): SelectQuery {
                return $q->where([
                    'OR' => [
                        ['Tests.is_public' => true],
                        ['Tests.created_by' => $userId],
                    ],
                ]);
            });

        $totalAttempts = (int)(clone $base)->count();

        $finishedBase = (clone $base)->where(['TestAttempts.finished_at IS NOT' => null]);
        $finishedAttempts = (int)(clone $finishedBase)->count();

        $uniqueQuizzes = (int)(clone $finishedBase)
            ->select(['test_id' => 'TestAttempts.test_id'])
            ->distinct(['TestAttempts.test_id'])
            ->count();

        $avgScoreRow = (clone $finishedBase)
            ->select(['avg_score' => $attemptsTable->find()->func()->avg('TestAttempts.score')])
            ->where(['TestAttempts.score IS NOT' => null])
            ->enableHydration(false)
            ->first();
        $avgScore = $avgScoreRow ? (float)($avgScoreRow['avg_score'] ?? 0) : 0.0;

        $bestScoreRow = (clone $finishedBase)
            ->select(['best_score' => $attemptsTable->find()->func()->max('TestAttempts.score')])
            ->where(['TestAttempts.score IS NOT' => null])
            ->enableHydration(false)
            ->first();
        $bestScore = $bestScoreRow ? (float)($bestScoreRow['best_score'] ?? 0) : 0.0;

        // Last 7 days finished attempts
        $sevenDaysAgo = FrozenTime::now()->subDays(7);
        $last7DaysCount = (int)(clone $finishedBase)
            ->where(['TestAttempts.finished_at >=' => $sevenDaysAgo])
            ->count();

        // Category breakdown: attempts count + avg score + best score per category
        $breakdownRaw = (clone $finishedBase)
            ->select([
                'category_id' => 'TestAttempts.category_id',
                'attempts' => $attemptsTable->find()->func()->count('TestAttempts.id'),
                'avg_score' => $attemptsTable->find()->func()->avg('TestAttempts.score'),
                'best_score' => $attemptsTable->find()->func()->max('TestAttempts.score'),
            ])
            ->groupBy(['TestAttempts.category_id'])
            ->enableHydration(false)
            ->all()
            ->toList();

        // Sort by attempts desc in PHP to avoid aggregate-alias ORDER BY dialect issues
        usort($breakdownRaw, static function (array $a, array $b): int {
            return (int)($b['attempts'] ?? 0) <=> (int)($a['attempts'] ?? 0);
        });

        // Load category names for the breakdown
        $categoryIds = array_filter(array_unique(array_column($breakdownRaw, 'category_id')));
        $categoryNames = [];
        if ($categoryIds) {
            $catTranslations = $this->fetchTable('CategoryTranslations')
                ->find()
                ->where([
                    'CategoryTranslations.category_id IN' => array_values($categoryIds),
                    'CategoryTranslations.language_id' => $languageId ?? 0,
                ])
                ->enableHydration(false)
                ->all()
                ->toList();

            foreach ($catTranslations as $ct) {
                $cid = (int)($ct['category_id'] ?? 0);
                if ($cid > 0 && !isset($categoryNames[$cid])) {
                    $categoryNames[$cid] = (string)($ct['name'] ?? '');
                }
            }

            // Fallback: load any translation if the language-specific one is missing
            $missingIds = array_values(array_filter($categoryIds, fn($id) => !isset($categoryNames[(int)$id])));
            if ($missingIds) {
                $fallbackTranslations = $this->fetchTable('CategoryTranslations')
                    ->find()
                    ->where(['CategoryTranslations.category_id IN' => $missingIds])
                    ->enableHydration(false)
                    ->all()
                    ->toList();
                foreach ($fallbackTranslations as $ct) {
                    $cid = (int)($ct['category_id'] ?? 0);
                    if ($cid > 0 && !isset($categoryNames[$cid]) && ($ct['name'] ?? '') !== '') {
                        $categoryNames[$cid] = (string)$ct['name'];
                    }
                }
            }
        }

        $categoryBreakdown = [];
        foreach ($breakdownRaw as $row) {
            $cid = (int)($row['category_id'] ?? 0);
            $avgRaw = $row['avg_score'] !== null ? (float)$row['avg_score'] : null;
            $bestRaw = $row['best_score'] !== null ? (float)$row['best_score'] : null;
            $categoryBreakdown[] = [
                'category_id' => $cid,
                'name' => $cid > 0 ? ($categoryNames[$cid] ?? __('Category #{0}', $cid)) : __('Uncategorized'),
                'attempts' => (int)($row['attempts'] ?? 0),
                'avg_score' => $avgRaw,
                'best_score' => $bestRaw,
            ];
        }

        $recentAttempts = (clone $finishedBase)
            ->contain([
                'Tests' => function (SelectQuery $q) use ($languageId): SelectQuery {
                    return $q->contain([
                        'Categories.CategoryTranslations' => function (SelectQuery $q) use ($languageId) {
                            if ($languageId === null) {
                                return $q;
                            }

                            return $q->where(['CategoryTranslations.language_id' => $languageId]);
                        },
                        'Difficulties.DifficultyTranslations' => function (SelectQuery $q) use ($languageId) {
                            if ($languageId === null) {
                                return $q;
                            }

                            return $q->where(['DifficultyTranslations.language_id' => $languageId]);
                        },
                        'TestTranslations' => function (SelectQuery $q) use ($languageId) {
                            if ($languageId === null) {
                                return $q;
                            }

                            return $q->where(['TestTranslations.language_id' => $languageId]);
                        },
                    ]);
                },
            ])
            ->orderByDesc('TestAttempts.finished_at')
            ->orderByDesc('TestAttempts.id')
            ->limit(20)
            ->all();

        return compact(
            'totalAttempts',
            'finishedAttempts',
            'uniqueQuizzes',
            'avgScore',
            'bestScore',
            'last7DaysCount',
            'categoryBreakdown',
            'recentAttempts',
        );
    }

    /**
     * Resolve language id from route language code.
     *
     * @return int|null
     */
    private function resolveLanguageIdFromRoute(): ?int
    {
        $langCode = strtolower(trim((string)$this->request->getParam('lang', 'en')));

        $languages = $this->fetchTable('Languages');
        $lang = $languages->find()->where(['code LIKE' => $langCode . '%'])->first();
        if (!$lang) {
            $lang = $languages->find()->first();
        }

        return $lang?->id;
    }

    /**
     * Log successful login and device info.
     *
     * @param \Authentication\IdentityInterface $user
     * @return void
     */
    private function logLogin(IdentityInterface $user): void
    {
        $ip = (string)$this->request->clientIp();
        $userAgent = (string)$this->request->getHeaderLine('User-Agent');
        $userId = $user->getIdentifier();

        // Update last_login_at
        $usersTable = $this->fetchTable('Users');
        $userEntity = $usersTable->get($userId);
        $userEntity->last_login_at = FrozenTime::now();
        $usersTable->save($userEntity);

        // Activity Log
        $activityLogsTable = $this->fetchTable('ActivityLogs');
        $log = $activityLogsTable->newEntity([
            'user_id' => $userId,
            'action' => ActivityLog::TYPE_LOGIN,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
        $activityLogsTable->save($log);

        // Device Log
        $deviceType = $this->detectDeviceType($userAgent);

        // IP Lookup via iplocate.io
        $country = null;
        $city = null;

        try {
            $http = new Client();

            $apiKey = env('IPLOCATE_API_KEY', null);
            $url = 'https://www.iplocate.io/api/lookup/' . $ip;
            if ($apiKey) {
                $url .= '?apikey=' . $apiKey;
            }

            $response = $http->get($url);
            if ($response->isOk()) {
                $json = $response->getJson();
                $country = $json['country'] ?? null;
                $city = $json['city'] ?? null;
            }
        } catch (Exception $e) {
            Log::error('IP lookup failed: ' . $e->getMessage());
        }

        $deviceLogsTable = $this->fetchTable('DeviceLogs');
        $deviceLog = $deviceLogsTable->newEntity([
            'user_id' => $userId,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'country' => $country,
            'city' => $city,
        ]);

        $deviceLogsTable->save($deviceLog);
    }

    /**
     * Detect device type from user agent.
     *
     * @param string $userAgent Raw user agent string.
     * @return int
     */
    private function detectDeviceType(string $userAgent): int
    {
        $detect = new MobileDetect();
        $detect->setUserAgent($userAgent);

        if ($detect->isTablet()) {
            return 1; // Tablet
        }

        if ($detect->isMobile()) {
            return 0; // Mobile
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

        return 2; // Desktop
    }

    /**
     * Log logout action.
     *
     * @param \Authentication\IdentityInterface $user
     * @return void
     */
    private function logLogout(IdentityInterface $user): void
    {
        $ip = (string)$this->request->clientIp();
        $userAgent = (string)$this->request->getHeaderLine('User-Agent');
        $userId = $user->getIdentifier();

        $activityLogsTable = $this->fetchTable('ActivityLogs');
        $log = $activityLogsTable->newEmptyEntity();
        $log->user_id = $userId;
        $log->action = ActivityLog::TYPE_LOGOUT;
        $log->ip_address = $ip;
        $log->user_agent = $userAgent;
        $activityLogsTable->save($log);
    }

    /**
     * Log failed login attempt.
     *
     * @param string|null $email
     * @param string $reason
     * @return void
     */
    private function logLoginFailure(?string $email, string $reason): void
    {
        $ip = (string)$this->request->clientIp();
        $userAgent = (string)$this->request->getHeaderLine('User-Agent');

        $userId = null;
        if ($email) {
            // We might already have the user in login(), but to keep this method self-contained:
            $user = $this->fetchTable('Users')->find()->where(['email' => $email])->first();
            if ($user) {
                $userId = $user->id;
            }
        }

        $activityLogsTable = $this->fetchTable('ActivityLogs');
        $log = $activityLogsTable->newEntity([
            'user_id' => $userId,
            'action' => substr(ActivityLog::TYPE_LOGIN_FAILED . ': ' . $reason, 0, 100),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $activityLogsTable->save($log);
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
