<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Model\Entity\Role;
use App\Service\ImageUploadGuard;
use App\Service\UserTokensService;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;
use Exception;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;
use function Cake\Core\env;

/**
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    /**
     * Bulk actions for selected users.
     *
     * Expects POST with:
     * - bulk_action: ban|unban|delete
     * - ids: array of user ids
     *
     * @return \Cake\Http\Response
     */
    public function bulk(): Response
    {
        $this->request->allowMethod(['post']);

        $lang = (string)$this->request->getParam('lang', 'en');

        $identity = $this->request->getAttribute('identity');
        if ($identity === null) {
            return $this->redirect(['prefix' => false, 'controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        $action = (string)$this->request->getData('bulk_action');
        $idsRaw = $this->request->getData('ids');

        $ids = [];
        if (is_array($idsRaw)) {
            foreach ($idsRaw as $id) {
                $idStr = trim((string)$id);
                if ($idStr !== '') {
                    $ids[] = $idStr;
                }
            }
        }
        $ids = array_values(array_unique($ids));

        if ($action === '' || !in_array($action, ['ban', 'unban', 'delete'], true)) {
            $this->Flash->error(__('Invalid bulk action.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        if (count($ids) === 0) {
            $this->Flash->error(__('Select at least one user.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $selfId = trim((string)$identity->get('id'));

        if (in_array($selfId, $ids, true)) {
            // Never allow actions that could lock the admin out.
            if ($action === 'delete') {
                $this->Flash->error(__('You cannot delete your own account.'));

                return $this->redirect(['action' => 'index', 'lang' => $lang]);
            }

            // For ban/unban, just exclude self.
            $ids = array_values(array_filter($ids, fn($id) => $id !== $selfId));
        }

        if (count($ids) === 0) {
            $this->Flash->error(__('No valid users selected.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        if ($action === 'ban' || $action === 'unban') {
            $blocked = $action === 'ban';
            $affected = $usersTable->updateAll(
                ['is_blocked' => $blocked],
                ['id IN' => $ids],
            );

            if ($affected > 0) {
                $this->Flash->success(__('{0} users updated.', $affected));
            } else {
                $this->Flash->error(__('No users were updated.'));
            }

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        // delete
        $adminCount = $usersTable->find()
            ->where(['Users.role_id' => Role::ADMIN])
            ->count();

        $adminsSelected = $usersTable->find()
            ->where(['Users.id IN' => $ids, 'Users.role_id' => Role::ADMIN])
            ->count();

        if ($adminCount - $adminsSelected < 1) {
            $this->Flash->error(__('You cannot delete the last admin account.'));

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $user = $usersTable->get($id);

                if ((int)$user->role_id === Role::ADMIN) {
                    $remainingAdmins = $usersTable->find()
                        ->where(['Users.role_id' => Role::ADMIN, 'Users.id !=' => $user->id])
                        ->count();

                    if ($remainingAdmins < 1) {
                        continue;
                    }
                }

                if ($usersTable->delete($user)) {
                    $deleted += 1;
                    $this->logAdminAction('admin_delete_user', ['id' => $user->id]);
                }
            } catch (Throwable) {
                // Ignore individual failures, continue.
                continue;
            }
        }

        if ($deleted > 0) {
            $this->Flash->success(__('{0} users deleted.', $deleted));
        } else {
            $this->Flash->error(__('No users were deleted.'));
        }

        return $this->redirect(['action' => 'index', 'lang' => $lang]);
    }

    /**
     * Admin My Profile page.
     *
     * Allows the currently logged-in admin to edit their username and avatar.
     *
     * @return \Cake\Http\Response|null
     */
    public function myProfile(): ?Response
    {
        $lang = (string)$this->request->getParam('lang', 'en');

        $this->viewBuilder()->setTemplatePath('Admin');
        $this->viewBuilder()->setTemplate('my_profile');

        $identity = $this->request->getAttribute('identity');
        if ($identity === null) {
            return $this->redirect(['prefix' => false, 'controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $userId = (string)$identity->get('id');
        $user = $usersTable->get($userId, contain: ['Roles']);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $data = $this->applyAvatarUpload($data);

            $user = $usersTable->patchEntity(
                $user,
                $data,
                ['fields' => ['username', 'avatar_url']],
            );

            if ($usersTable->save($user)) {
                $this->Flash->success(__('Your profile has been updated.'));

                return $this->redirect([
                    'action' => 'myProfile',
                    'lang' => $lang,
                ]);
            }

            $this->Flash->error(__('Unable to update your profile. Please try again.'));
        }

        $this->set(compact('user', 'lang'));

        // AI usage stats for the profile page (admin / quiz_creator roles only)
        $roleName = strtolower((string)($user->role->name ?? ''));
        $aiStats = null;
        if (in_array($roleName, ['admin', 'quiz_creator', 'quiz-creator', 'creator'], true)) {
            $aiRequestsTable = $this->fetchTable('AiRequests');
            $total = (int)$aiRequestsTable->find()->where(['user_id' => $user->id])->count();
            $success = (int)$aiRequestsTable->find()->where(['user_id' => $user->id, 'status' => 'success'])->count();
            $tokensRow = $aiRequestsTable->find()
                ->select(['s' => $aiRequestsTable->find()->func()->sum('total_tokens')])
                ->where(['user_id' => $user->id])
                ->enableHydration(false)
                ->first();
            $costRow = $aiRequestsTable->find()
                ->select(['s' => $aiRequestsTable->find()->func()->sum('cost_usd')])
                ->where(['user_id' => $user->id])
                ->enableHydration(false)
                ->first();

            $aiStats = [
                'total' => $total,
                'success' => $success,
                'failed' => $total - $success,
                'totalTokens' => (int)($tokensRow['s'] ?? 0),
                'totalCostUsd' => round((float)($costRow['s'] ?? 0), 6),
            ];
        }

        $this->set(compact('aiStats'));

        return null;
    }

    /**
     * Sends a password reset email to the currently logged-in admin.
     *
     * This reuses the public reset-password flow, but the request is initiated from the admin area.
     *
     * @return \Cake\Http\Response
     */
    public function requestPasswordReset(): Response
    {
        $this->request->allowMethod(['post']);

        $lang = (string)$this->request->getParam('lang', 'en');

        $identity = $this->request->getAttribute('identity');
        if ($identity === null) {
            return $this->redirect(['prefix' => false, 'controller' => 'Users', 'action' => 'login', 'lang' => $lang]);
        }

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $userId = (string)$identity->get('id');
        $user = $usersTable->get($userId);

        /** @var \App\Model\Table\UserTokensTable $userTokensTable */
        $userTokensTable = $this->fetchTable('UserTokens');
        $tokenService = new UserTokensService($userTokensTable);
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
                ->setTo((string)$user->email)
                ->setEmailFormat('both')
                ->setSubject(__('Reset your MindForge password'))
                ->setViewVars(['resetUrl' => $resetUrl])
                ->viewBuilder()
                ->setTemplate('password_reset');

            $mailer->deliver();

            $this->Flash->success(__('We sent a password reset link to your email.'));
        } catch (Exception $e) {
            Log::error('Admin password reset email failed for user ' . $userId . ': ' . $e->getMessage());
            $this->Flash->error(__('Could not send the password reset email. Please try again later.'));
        } finally {
            I18n::setLocale($previousLocale);
        }

        return $this->redirect([
            'action' => 'myProfile',
            'lang' => $lang,
        ]);
    }

    /**
     * @return void
     */
    public function index(): void
    {
        $query = $this->fetchTable('Users')
            ->find()
            ->contain(['Roles'])
            ->orderDesc('Users.created_at');

        $users = $query->all();

        $this->set(compact('users'));
    }

    /**
     * @return \Cake\Http\Response|null
     */
    public function add(): ?Response
    {
        $lang = (string)$this->request->getParam('lang', 'en');

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();

            $data = $this->applyAvatarUpload($data);

            if (!isset($data['is_active'])) {
                $data['is_active'] = true;
            }
            if (!isset($data['is_blocked'])) {
                $data['is_blocked'] = false;
            }

            $user = $usersTable->patchEntity($user, $data);

            if ($usersTable->save($user)) {
                $this->Flash->success(__('The user has been created.'));

                return $this->redirect([
                    'action' => 'index',
                    'lang' => $lang,
                ]);
            }

            $this->Flash->error(__('The user could not be created. Please, try again.'));
        }

        $roles = $usersTable->Roles->find('list')->all();
        $this->set(compact('user', 'roles'));

        return null;
    }

    /**
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null
     */
    public function edit(?string $id = null): ?Response
    {
        $lang = (string)$this->request->getParam('lang', 'en');

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            if (isset($data['password']) && (string)$data['password'] === '') {
                unset($data['password']);
            }

            $data = $this->applyAvatarUpload($data);

            $user = $usersTable->patchEntity($user, $data);

            if ($usersTable->save($user)) {
                $this->Flash->success(__('The user has been updated.'));

                return $this->redirect([
                    'action' => 'index',
                    'lang' => $lang,
                ]);
            }

            $this->Flash->error(__('The user could not be updated. Please, try again.'));
        }

        $roles = $usersTable->Roles->find('list')->all();

        $testAttempts = $this->fetchTable('TestAttempts')
            ->find()
            ->contain([
                'Tests' => static fn ($q) => $q->contain([
                    'TestTranslations' => static fn ($tq) => $tq->select(['TestTranslations.test_id', 'TestTranslations.title'])->limit(1),
                ]),
            ])
            ->where(['TestAttempts.user_id' => $user->id])
            ->orderByDesc('TestAttempts.created_at')
            ->limit(5)
            ->all();

        $activityLogs = $this->fetchTable('ActivityLogs')
            ->find()
            ->where(['ActivityLogs.user_id' => $user->id])
            ->orderByDesc('ActivityLogs.created_at')
            ->limit(5)
            ->all();

        $deviceLogs = $this->fetchTable('DeviceLogs')
            ->find()
            ->where(['DeviceLogs.user_id' => $user->id])
            ->orderByDesc('DeviceLogs.created_at')
            ->limit(5)
            ->all();

        $this->set(compact('user', 'roles', 'testAttempts', 'activityLogs', 'deviceLogs'));

        return null;
    }

    /**
     * @param string|null $id User id.
     * @return \Cake\Http\Response
     */
    public function ban(?string $id = null): Response
    {
        $this->request->allowMethod(['post']);

        $lang = (string)$this->request->getParam('lang', 'en');

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->get($id);
        $user->is_blocked = true;

        if ($usersTable->save($user)) {
            $this->Flash->success(__('User has been banned.'));
        } else {
            $this->Flash->error(__('Could not ban user. Please, try again.'));
        }

        return $this->redirect([
            'action' => 'index',
            'lang' => $lang,
        ]);
    }

    /**
     * @param string|null $id User id.
     * @return \Cake\Http\Response
     */
    public function unban(?string $id = null): Response
    {
        $this->request->allowMethod(['post']);

        $lang = (string)$this->request->getParam('lang', 'en');

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->get($id);
        $user->is_blocked = false;

        if ($usersTable->save($user)) {
            $this->Flash->success(__('User has been unbanned.'));
        } else {
            $this->Flash->error(__('Could not unban user. Please, try again.'));
        }

        return $this->redirect([
            'action' => 'index',
            'lang' => $lang,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function applyAvatarUpload(array $data): array
    {
        if (!isset($data['avatar_file'])) {
            return $data;
        }

        $file = $data['avatar_file'];
        unset($data['avatar_file']);

        if (!$file instanceof UploadedFileInterface) {
            return $data;
        }

        if ($file->getError() !== UPLOAD_ERR_OK && $file->getError() !== UPLOAD_ERR_NO_FILE) {
            return $data;
        }
        if ($file->getError() === UPLOAD_ERR_NO_FILE) {
            return $data;
        }

        $allowedTypes = (array)Configure::read(
            'Uploads.avatarAllowedMimeTypes',
            ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        );
        $maxBytes = (int)Configure::read('Uploads.avatarMaxBytes', 3 * 1024 * 1024);
        $imageUploadGuard = new ImageUploadGuard();

        try {
            $mime = $imageUploadGuard->assertImageUpload($file, $allowedTypes, $maxBytes);
            $ext = ImageUploadGuard::extensionForMime($mime);
        } catch (RuntimeException) {
            $this->Flash->error(__('Unsupported avatar image type.'));

            return $data;
        }

        $dir = WWW_ROOT . 'img' . DS . 'avatars' . DS;
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            $this->Flash->error(__('Failed to upload avatar.'));

            return $data;
        }
        if (!is_writable($dir)) {
            $this->Flash->error(__('Failed to upload avatar.'));

            return $data;
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $dir . $filename;

        try {
            $file->moveTo($targetPath);
        } catch (Throwable) {
            $this->Flash->error(__('Failed to upload avatar.'));

            return $data;
        }

        $data['avatar_url'] = '/img/avatars/' . $filename;

        return $data;
    }

    /**
     * @param string|null $id User id.
     * @return \Cake\Http\Response
     */
    public function delete(?string $id = null): Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $lang = (string)$this->request->getParam('lang', 'en');

        $identity = $this->request->getAttribute('identity');
        if ($identity !== null && (string)$identity->get('id') === (string)$id) {
            $this->Flash->error(__('You cannot delete your own account.'));

            return $this->redirect([
                'action' => 'edit',
                $id,
                'lang' => $lang,
            ]);
        }

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->get($id);

        if ((int)$user->role_id === Role::ADMIN) {
            $adminCount = $usersTable->find()
                ->where(['Users.role_id' => Role::ADMIN])
                ->count();

            if ($adminCount <= 1) {
                $this->Flash->error(__('You cannot delete the last admin account.'));

                return $this->redirect([
                    'action' => 'edit',
                    $user->id,
                    'lang' => $lang,
                ]);
            }
        }

        if ($usersTable->delete($user)) {
            $this->logAdminAction('admin_delete_user', ['id' => $user->id]);
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect([
            'action' => 'index',
            'lang' => $lang,
        ]);
    }
}
