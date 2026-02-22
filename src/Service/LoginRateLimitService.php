<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;

/**
 * Simple IP-based login rate limiting using CakePHP Cache.
 *
 * Extracted from UsersController::rateLimitKey/Allow/Hit/Clear().
 */
class LoginRateLimitService
{
    /**
     * @var int Maximum login attempts per window.
     */
    private int $maxAttempts;

    /**
     * @var int Window duration in seconds.
     */
    private int $windowSeconds;

    /**
     * @param int $maxAttempts Maximum attempts before blocking (default 5).
     * @param int $windowSeconds Window duration in seconds (default 60).
     */
    public function __construct(int $maxAttempts = 5, int $windowSeconds = 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->windowSeconds = $windowSeconds;
    }

    /**
     * Check whether the given IP is allowed to attempt login.
     *
     * @param string $ip Client IP address.
     * @return bool
     */
    public function isAllowed(string $ip): bool
    {
        $key = $this->cacheKey($ip);
        $data = Cache::read($key, 'default');

        if (!is_array($data)) {
            return true;
        }

        $count = (int)($data['count'] ?? 0);
        $start = (int)($data['start'] ?? 0);

        if ($start <= 0 || (time() - $start) > $this->windowSeconds) {
            return true; // window expired
        }

        return $count < $this->maxAttempts;
    }

    /**
     * Record a failed login attempt for the given IP.
     *
     * @param string $ip Client IP address.
     * @return void
     */
    public function recordAttempt(string $ip): void
    {
        $key = $this->cacheKey($ip);
        $data = Cache::read($key, 'default');

        if (!is_array($data)) {
            $data = ['count' => 0, 'start' => time()];
        }

        $start = (int)($data['start'] ?? 0);
        if ($start <= 0 || (time() - $start) > $this->windowSeconds) {
            $data = ['count' => 0, 'start' => time()];
        }

        $data['count'] = ((int)$data['count']) + 1;

        // Keep it around slightly longer than the window
        Cache::write($key, $data, 'default');
    }

    /**
     * Clear rate limit counter for the given IP (e.g. after successful login).
     *
     * @param string $ip Client IP address.
     * @return void
     */
    public function clear(string $ip): void
    {
        Cache::delete($this->cacheKey($ip), 'default');
    }

    /**
     * Build a cache key from IP address.
     *
     * @param string $ip Client IP address.
     * @return string
     */
    private function cacheKey(string $ip): string
    {
        return 'login_rl_' . preg_replace('/[^0-9a-fA-F\:\.]/', '_', $ip);
    }
}
