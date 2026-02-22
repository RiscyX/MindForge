<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\AdminUserDetailService;
use App\Service\AdminUserManagementService;
use App\Service\AvatarUploadService;
use Cake\Core\Configure;
use Cake\Http\Response;
use Psr\Http\Message\UploadedFileInterface;

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
        $mgmtService = new AdminUserManagementService();
        $ids = $mgmtService->sanitizeIds($this->request->getData('ids'));
        $selfId = trim((string)$identity->get('id'));

        $result = $mgmtService->executeBulk($action, $ids, $selfId);

        if ($result['code'] === 'success') {
            if ($action === 'delete' && !empty($result['deleted_ids'])) {
                $this->logAdminAction('admin_bulk_delete_users', [
                    'count' => $result['deleted'],
                    'ids' => implode(',', $result['deleted_ids']),
                ]);
            }
            $this->Flash->success($result['message']);
        } else {
            $this->Flash->error($result['message']);
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

            // Extract cropped image bytes without writing to disk yet
            [$data, $pendingBytes, $pendingFilename] = $this->extractCroppedImageData($data);

            // For Path B (legacy file upload): write happens inside applyAvatarUpload
            if ($pendingBytes === null) {
                $data = $this->applyAvatarUpload($data);
            } else {
                // Path A: set avatar_url tentatively; file written only after save succeeds
                $data['avatar_url'] = '/img/avatars/' . $pendingFilename;
            }

            $user = $usersTable->patchEntity(
                $user,
                $data,
                ['fields' => ['username', 'avatar_url']],
            );

            if ($usersTable->save($user)) {
                // Write the cropped image file only after the DB update was successful
                if ($pendingBytes !== null && $pendingFilename !== null) {
                    $dir = WWW_ROOT . 'img' . DS . 'avatars' . DS;
                    if (!is_dir($dir)) {
                        mkdir($dir, 0775, true);
                    }
                    file_put_contents($dir . $pendingFilename, $pendingBytes);
                }

                $this->Flash->success(__('Your profile has been updated.'));

                return $this->redirect([
                    'action' => 'myProfile',
                    'lang' => $lang,
                ]);
            }

            // Show the first validation error to help the user
            $errors = $user->getErrors();
            if ($errors) {
                $firstField = (string)array_key_first($errors);
                $firstMsg = (string)reset($errors[$firstField]);
                $this->Flash->error($firstField . ': ' . $firstMsg);
            } else {
                $this->Flash->error(__('Unable to update your profile. Please try again.'));
            }
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

        $userId = (string)$identity->get('id');
        $mgmtService = new AdminUserManagementService();
        $result = $mgmtService->sendPasswordResetEmail($userId, $lang);

        if ($result['code'] === 'success') {
            $this->Flash->success($result['message']);
        } else {
            $this->Flash->error($result['message']);
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

        $detailService = new AdminUserDetailService();
        $testAttempts = $detailService->recentTestAttempts($user->id);
        $activityLogs = $detailService->recentActivityLogs($user->id);
        $deviceLogs = $detailService->recentDeviceLogs($user->id);

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
     * Extracts and validates a cropped base64 image from POST data WITHOUT writing to disk.
     *
     * Returns [modifiedData, imageBytes|null, proposedFilename|null].
     * The caller must write the file only after a successful DB save.
     *
     * @param array<string, mixed> $data
     * @return array{0: array<string, mixed>, 1: string|null, 2: string|null}
     */
    private function extractCroppedImageData(array $data): array
    {
        $croppedData = isset($data['avatar_cropped_data']) ? (string)$data['avatar_cropped_data'] : '';
        unset($data['avatar_cropped_data']);
        unset($data['avatar_file_raw']);

        if ($croppedData === '' || !str_starts_with($croppedData, 'data:image/')) {
            return [$data, null, null];
        }

        $commaPos = strpos($croppedData, ',');
        if ($commaPos === false) {
            return [$data, null, null];
        }

        $imageBytes = base64_decode(substr($croppedData, $commaPos + 1), true);
        if ($imageBytes === false || strlen($imageBytes) < 100) {
            return [$data, null, null];
        }

        $maxBytes = (int)Configure::read('Uploads.avatarMaxBytes', 3 * 1024 * 1024);
        if (strlen($imageBytes) > $maxBytes) {
            $this->Flash->error(__('Avatar image is too large.'));

            return [$data, null, null];
        }

        $filename = bin2hex(random_bytes(16)) . '.jpg';

        return [$data, $imageBytes, $filename];
    }

    /**
     * Apply a legacy plain file avatar upload to the user data.
     *
     * @param array $data User data array.
     * @return array
     */
    private function applyAvatarUpload(array $data): array
    {
        // Path A (cropped) is handled separately via extractCroppedImageData().
        // This method handles Path B only: legacy plain file upload.
        unset($data['avatar_cropped_data'], $data['avatar_file_raw']);

        // --- Path B: legacy plain file upload (fallback) ---
        if (!isset($data['avatar_file'])) {
            return $data;
        }

        $file = $data['avatar_file'];
        unset($data['avatar_file']);

        if (!$file instanceof UploadedFileInterface) {
            return $data;
        }

        if ($file->getError() === UPLOAD_ERR_NO_FILE) {
            return $data;
        }

        $avatarService = new AvatarUploadService();
        $result = $avatarService->upload($file);

        if (!$result['ok']) {
            $this->Flash->error(__($result['error'] ?? 'Failed to upload avatar.'));

            return $data;
        }

        $data['avatar_url'] = '/img/' . $result['avatar_url'];

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
        $selfId = $identity !== null ? (string)$identity->get('id') : '';

        $mgmtService = new AdminUserManagementService();
        $result = $mgmtService->deleteUser((string)$id, $selfId);

        if ($result['code'] === 'success') {
            $this->logAdminAction('admin_delete_user', ['id' => $result['user_id']]);
            $this->Flash->success($result['message']);

            return $this->redirect(['action' => 'index', 'lang' => $lang]);
        }

        $this->Flash->error($result['message']);

        $redirectAction = $result['redirect_action'] ?? 'index';
        if ($redirectAction === 'edit') {
            return $this->redirect(['action' => 'edit', $id, 'lang' => $lang]);
        }

        return $this->redirect(['action' => 'index', 'lang' => $lang]);
    }
}
