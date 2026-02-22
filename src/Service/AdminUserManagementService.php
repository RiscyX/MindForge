<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Role;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use Exception;
use Throwable;
use function Cake\Core\env;

/**
 * Encapsulates business logic for admin user management operations:
 * bulk actions, password reset emails, and safe deletion with guard rails.
 */
class AdminUserManagementService
{
    use LocatorAwareTrait;

    /**
     * Sanitize and deduplicate bulk user IDs (string-based, matching admin convention).
     *
     * @param mixed $rawIds Raw ids from request data.
     * @return array<string>
     */
    public function sanitizeIds(mixed $rawIds): array
    {
        $ids = [];
        if (is_array($rawIds)) {
            foreach ($rawIds as $id) {
                $idStr = trim((string)$id);
                if ($idStr !== '') {
                    $ids[] = $idStr;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Execute a bulk action on the given user IDs.
     *
     * Self-protection: the acting admin is excluded from ban/unban and cannot
     * delete themselves.  Last-admin protection prevents deleting all admins.
     *
     * @param string $action One of 'ban', 'unban', 'delete'.
     * @param array<string> $ids User IDs to act on.
     * @param string $selfId The acting admin's user ID.
     * @return array{action: string, code: string, deleted?: int, affected?: int, message: string}
     */
    public function executeBulk(string $action, array $ids, string $selfId): array
    {
        if (!in_array($action, ['ban', 'unban', 'delete'], true)) {
            return ['action' => $action, 'code' => 'invalid_action', 'message' => __('Invalid bulk action.')];
        }

        if (count($ids) === 0) {
            return ['action' => $action, 'code' => 'no_selection', 'message' => __('Select at least one user.')];
        }

        // Self-protection
        if (in_array($selfId, $ids, true)) {
            if ($action === 'delete') {
                return [
                    'action' => $action,
                    'code' => 'self_delete',
                    'message' => __('You cannot delete your own account.'),
                ];
            }
            $ids = array_values(array_filter($ids, fn($id) => $id !== $selfId));
        }

        if (count($ids) === 0) {
            return ['action' => $action, 'code' => 'no_valid', 'message' => __('No valid users selected.')];
        }

        $usersTable = $this->fetchTable('Users');

        if ($action === 'ban' || $action === 'unban') {
            $blocked = $action === 'ban';
            $affected = $usersTable->updateAll(
                ['is_blocked' => $blocked],
                ['id IN' => $ids],
            );

            if ($affected > 0) {
                return [
                    'action' => $action,
                    'code' => 'success',
                    'affected' => $affected,
                    'message' => __('{0} users updated.', $affected),
                ];
            }

            return ['action' => $action, 'code' => 'none_updated', 'message' => __('No users were updated.')];
        }

        // Delete action — last-admin protection
        $adminCount = $usersTable->find()
            ->where(['Users.role_id' => Role::ADMIN])
            ->count();

        $adminsSelected = $usersTable->find()
            ->where(['Users.id IN' => $ids, 'Users.role_id' => Role::ADMIN])
            ->count();

        if ($adminCount - $adminsSelected < 1) {
            return [
                'action' => $action,
                'code' => 'last_admin',
                'message' => __('You cannot delete the last admin account.'),
            ];
        }

        $deleted = 0;
        $deletedIds = [];
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
                    $deleted++;
                    $deletedIds[] = $user->id;
                }
            } catch (Throwable) {
                continue;
            }
        }

        if ($deleted > 0) {
            return [
                'action' => $action,
                'code' => 'success',
                'deleted' => $deleted,
                'deleted_ids' => $deletedIds,
                'message' => __('{0} users deleted.', $deleted),
            ];
        }

        return ['action' => $action, 'code' => 'none_deleted', 'message' => __('No users were deleted.')];
    }

    /**
     * Safely delete a single user with self-deletion and last-admin guards.
     *
     * @param string $userId The user ID to delete.
     * @param string $selfId The acting admin's user ID.
     * @return array{code: string, message: string, redirect_action?: string}
     */
    public function deleteUser(string $userId, string $selfId): array
    {
        if ($selfId === $userId) {
            return [
                'code' => 'self_delete',
                'message' => __('You cannot delete your own account.'),
                'redirect_action' => 'edit',
            ];
        }

        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->get($userId);

        if ((int)$user->role_id === Role::ADMIN) {
            $adminCount = $usersTable->find()
                ->where(['Users.role_id' => Role::ADMIN])
                ->count();

            if ($adminCount <= 1) {
                return [
                    'code' => 'last_admin',
                    'message' => __('You cannot delete the last admin account.'),
                    'redirect_action' => 'edit',
                ];
            }
        }

        if ($usersTable->delete($user)) {
            return [
                'code' => 'success',
                'message' => __('The user has been deleted.'),
                'user_id' => (string)$user->id,
            ];
        }

        return [
            'code' => 'failed',
            'message' => __('The user could not be deleted. Please, try again.'),
        ];
    }

    /**
     * Send a password reset email to the given user.
     *
     * Creates a reset token and sends a locale-aware email.
     *
     * @param string $userId The user ID.
     * @param string $langCode The current language code (e.g. 'en', 'hu').
     * @return array{code: string, message: string}
     */
    public function sendPasswordResetEmail(string $userId, string $langCode): array
    {
        $usersTable = $this->fetchTable('Users');
        $user = $usersTable->get($userId);

        /** @var \App\Model\Table\UserTokensTable $userTokensTable */
        $userTokensTable = $this->fetchTable('UserTokens');
        $tokenService = new UserTokensService($userTokensTable);
        $token = $tokenService->createPasswordResetToken($user);

        $baseUrl = rtrim((string)env('BASE_URL', Router::url('/', true)), '/');
        $resetUrl = $baseUrl . '/' . $langCode . '/reset-password?token=' . urlencode($token);

        $locale = $langCode === 'hu' ? 'hu_HU' : 'en_US';
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

            return ['code' => 'success', 'message' => __('We sent a password reset link to your email.')];
        } catch (Exception $e) {
            Log::error(
                'Admin password reset email failed for user ' . $userId . ': ' . $e->getMessage(),
            );

            return [
                'code' => 'failed',
                'message' => __('Could not send the password reset email. Please try again later.'),
            ];
        } finally {
            I18n::setLocale($previousLocale);
        }
    }
}
