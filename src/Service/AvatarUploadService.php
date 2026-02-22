<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;

/**
 * Handles avatar image upload, validation, storage, and old-avatar cleanup.
 *
 * Replaces duplicated avatar upload logic from:
 * - Admin\UsersController::applyAvatarUpload()
 * - UsersController::profileEdit() (inline)
 * - Api\AuthController::updateMe() (inline)
 */
class AvatarUploadService
{
    /**
     * Process an avatar upload: validate, store, and return the new relative URL.
     *
     * On success returns `['ok' => true, 'avatar_url' => 'avatars/filename.ext']`.
     * On validation/storage failure returns `['ok' => false, 'error' => 'message']`.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded avatar file.
     * @param string|int|null $userId User id (used for deterministic filenames; pass null for random name).
     * @param string|null $oldAvatarUrl Current avatar_url value (for cleanup).
     * @return array{ok: bool, avatar_url?: string, error?: string}
     */
    public function upload(
        UploadedFileInterface $file,
        int|string|null $userId = null,
        ?string $oldAvatarUrl = null,
    ): array {
        // Skip if no file was actually uploaded
        if ($file->getError() === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'No file uploaded.'];
        }
        if ($file->getError() !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload error code: ' . $file->getError()];
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
        } catch (RuntimeException $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }

        // Build filename
        if ($userId !== null) {
            $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        } else {
            $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        }

        // Ensure target directory exists
        $avatarsDir = WWW_ROOT . 'img' . DS . 'avatars';
        if (!is_dir($avatarsDir) && !mkdir($avatarsDir, 0775, true) && !is_dir($avatarsDir)) {
            return ['ok' => false, 'error' => 'Avatar upload directory could not be created.'];
        }
        if (!is_writable($avatarsDir)) {
            return ['ok' => false, 'error' => 'Avatar upload directory is not writable.'];
        }

        $targetPath = $avatarsDir . DS . $filename;

        try {
            $file->moveTo($targetPath);
        } catch (Throwable) {
            return ['ok' => false, 'error' => 'Failed to store avatar image.'];
        }

        // Clean up old avatar if it is a local file
        if ($oldAvatarUrl !== null && $oldAvatarUrl !== '') {
            $this->deleteOldAvatar($oldAvatarUrl);
        }

        return ['ok' => true, 'avatar_url' => 'avatars/' . $filename];
    }

    /**
     * Delete old avatar file from disk if it is a local avatar.
     *
     * @param string $avatarUrl Relative avatar URL (e.g. 'avatars/old.jpg' or '/img/avatars/old.jpg').
     * @return void
     */
    private function deleteOldAvatar(string $avatarUrl): void
    {
        if (!str_contains($avatarUrl, 'avatars/')) {
            return;
        }

        // Normalize: strip leading /img/ or img/ prefix
        $relative = ltrim($avatarUrl, '/');
        $relative = preg_replace('#^img/#', '', $relative) ?? $relative;

        $oldFile = WWW_ROOT . 'img' . DS . str_replace('/', DS, $relative);
        if (file_exists($oldFile) && is_file($oldFile)) {
            unlink($oldFile);
        }
    }
}
