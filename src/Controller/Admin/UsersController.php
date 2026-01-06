<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;

/**
 * @property \App\Model\Table\UsersTable $Users
 */
class UsersController extends AppController
{
    /**
     * @return void
     */
    public function index(): void
    {
        $users = $this->fetchTable('Users')
            ->find()
            ->contain(['Roles'])
            ->orderDesc('Users.created_at')
            ->all();

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
        $this->set(compact('user', 'roles'));

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
            'action' => 'edit',
            $user->id,
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
            'action' => 'edit',
            $user->id,
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

        if ($file->getError() !== UPLOAD_ERR_OK || $file->getSize() === 0) {
            return $data;
        }

        $mime = (string)$file->getClientMediaType();
        if ($mime === '' || !str_starts_with($mime, 'image/')) {
            $this->Flash->error(__('Avatar must be an image.'));

            return $data;
        }

        $originalName = (string)$file->getClientFilename();
        $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if ($ext === '' || !in_array($ext, $allowedExts, true)) {
            $this->Flash->error(__('Unsupported avatar image type.'));

            return $data;
        }

        $dir = WWW_ROOT . 'img' . DS . 'avatars' . DS;
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
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

        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');

        $user = $usersTable->get($id);

        if ($usersTable->delete($user)) {
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
