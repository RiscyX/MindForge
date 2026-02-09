<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;
use Throwable;

class ApiRateLimiterService
{
    public function isAllowed(string $bucket, string $key, int $maxAttempts, int $windowSeconds): bool
    {
        try {
            $payload = Cache::read($this->cacheKey($bucket, $key), 'default');
        } catch (Throwable) {
            return true;
        }

        if (!is_array($payload)) {
            return true;
        }

        $count = (int)($payload['count'] ?? 0);
        $start = (int)($payload['start'] ?? 0);

        if ($start <= 0 || (time() - $start) > $windowSeconds) {
            return true;
        }

        return $count < $maxAttempts;
    }

    public function hit(string $bucket, string $key, int $windowSeconds): void
    {
        $cacheKey = $this->cacheKey($bucket, $key);
        try {
            $payload = Cache::read($cacheKey, 'default');
        } catch (Throwable) {
            return;
        }

        if (!is_array($payload)) {
            $payload = ['count' => 0, 'start' => time()];
        }

        $start = (int)($payload['start'] ?? 0);
        if ($start <= 0 || (time() - $start) > $windowSeconds) {
            $payload = ['count' => 0, 'start' => time()];
        }

        $payload['count'] = ((int)$payload['count']) + 1;
        try {
            Cache::write($cacheKey, $payload, 'default');
        } catch (Throwable) {
            return;
        }
    }

    public function clear(string $bucket, string $key): void
    {
        try {
            Cache::delete($this->cacheKey($bucket, $key), 'default');
        } catch (Throwable) {
            return;
        }
    }

    private function cacheKey(string $bucket, string $key): string
    {
        $normalizedBucket = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $bucket) ?? 'bucket';
        $normalizedKey = preg_replace('/[^a-zA-Z0-9_\-:\.]/', '_', $key) ?? 'key';

        return sprintf('api_rl_%s_%s', $normalizedBucket, $normalizedKey);
    }
}
