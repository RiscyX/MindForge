<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\ActivityLog;
use Cake\Http\Client;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Detection\MobileDetect;
use Exception;
use function Cake\Core\env;

/**
 * Handles login/logout activity logging and device detection.
 *
 * Extracted from UsersController::logLogin(), logLogout(), logLoginFailure(), detectDeviceType().
 */
class LoginActivityService
{
    /**
     * Log a successful login: update last_login_at, create activity log, device log with IP lookup.
     *
     * @param int $userId User id.
     * @param string $ip Client IP address.
     * @param string $userAgent Raw User-Agent header.
     * @return void
     */
    public function logLogin(int $userId, string $ip, string $userAgent): void
    {
        // Update last_login_at
        $usersTable = TableRegistry::getTableLocator()->get('Users');
        $userEntity = $usersTable->get($userId);
        $userEntity->last_login_at = FrozenTime::now();
        $usersTable->save($userEntity);

        // Activity Log
        $activityLogsTable = TableRegistry::getTableLocator()->get('ActivityLogs');
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

        $deviceLogsTable = TableRegistry::getTableLocator()->get('DeviceLogs');
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
     * Log a logout action.
     *
     * @param int $userId User id.
     * @param string $ip Client IP address.
     * @param string $userAgent Raw User-Agent header.
     * @return void
     */
    public function logLogout(int $userId, string $ip, string $userAgent): void
    {
        $activityLogsTable = TableRegistry::getTableLocator()->get('ActivityLogs');
        $log = $activityLogsTable->newEmptyEntity();
        $log->user_id = $userId;
        $log->action = ActivityLog::TYPE_LOGOUT;
        $log->ip_address = $ip;
        $log->user_agent = $userAgent;
        $activityLogsTable->save($log);
    }

    /**
     * Log a failed login attempt.
     *
     * @param string|null $email Email used for login attempt.
     * @param string $reason Failure reason.
     * @param string $ip Client IP address.
     * @param string $userAgent Raw User-Agent header.
     * @return void
     */
    public function logLoginFailure(?string $email, string $reason, string $ip, string $userAgent): void
    {
        $userId = null;
        if ($email) {
            $user = TableRegistry::getTableLocator()->get('Users')
                ->find()
                ->where(['email' => $email])
                ->first();
            if ($user) {
                $userId = $user->id;
            }
        }

        $activityLogsTable = TableRegistry::getTableLocator()->get('ActivityLogs');
        $log = $activityLogsTable->newEntity([
            'user_id' => $userId,
            'action' => substr(ActivityLog::TYPE_LOGIN_FAILED . ': ' . $reason, 0, 100),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $activityLogsTable->save($log);
    }

    /**
     * Detect device type from user agent.
     *
     * @param string $userAgent Raw user agent string.
     * @return int 0 = Mobile, 1 = Tablet, 2 = Desktop
     */
    public function detectDeviceType(string $userAgent): int
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
}
