<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Log\Log;
use RuntimeException;
use Throwable;

class IpEnrichmentService
{
    private const CACHE_KEY_PREFIX = 'ip_enrich_';
    private const DEFAULT_PROVIDER = 'iplocate';
    private const DEFAULT_TTL_DAYS = 7;

    private Client $http;

    /**
     * @param \Cake\Http\Client|null $http Optional HTTP client.
     */
    public function __construct(?Client $http = null)
    {
        $this->http = $http ?? new Client();
    }

    /**
     * Enrich IP address with location/network metadata.
     *
     * @param string $ip IP address.
     * @return array{country:?string,city:?string,isp:?string,provider:string,cached:bool}
     */
    public function enrich(string $ip): array
    {
        $ip = trim($ip);
        $provider = strtolower((string)Configure::read('IpEnrichment.provider', self::DEFAULT_PROVIDER));

        $empty = [
            'country' => null,
            'city' => null,
            'isp' => null,
            'provider' => $provider,
            'cached' => false,
        ];

        if ($ip === '' || !$this->isPublicIp($ip)) {
            return $empty;
        }

        $cacheKey = $this->cacheKey($provider, $ip);
        $cached = Cache::read($cacheKey, 'default');
        if (is_array($cached) && $this->isCachePayloadValid($cached)) {
            return [
                'country' => $this->toNullableString($cached['country'] ?? null),
                'city' => $this->toNullableString($cached['city'] ?? null),
                'isp' => $this->toNullableString($cached['isp'] ?? null),
                'provider' => $provider,
                'cached' => true,
            ];
        }

        try {
            $fresh = match ($provider) {
                'iplocate' => $this->lookupWithIplocate($ip),
                default => $empty,
            };
        } catch (Throwable $e) {
            Log::warning('IP enrichment failed: ' . $e->getMessage());

            return $empty;
        }

        $ttlSeconds = $this->ttlSeconds();
        Cache::write($cacheKey, [
            'country' => $fresh['country'],
            'city' => $fresh['city'],
            'isp' => $fresh['isp'],
            'expires_at' => time() + $ttlSeconds,
        ], 'default');

        return [
            'country' => $fresh['country'],
            'city' => $fresh['city'],
            'isp' => $fresh['isp'],
            'provider' => $provider,
            'cached' => false,
        ];
    }

    /**
     * @param string $ip IP address.
     * @return array{country:?string,city:?string,isp:?string}
     */
    private function lookupWithIplocate(string $ip): array
    {
        $apiKey = trim((string)Configure::read('IpEnrichment.iplocateApiKey', ''));
        $url = 'https://www.iplocate.io/api/lookup/' . rawurlencode($ip);
        if ($apiKey !== '') {
            $url .= '?apikey=' . rawurlencode($apiKey);
        }

        $response = $this->http->get($url, [], ['timeout' => 5]);
        if (!$response->isOk()) {
            throw new RuntimeException('IP provider responded with HTTP ' . $response->getStatusCode());
        }

        $json = $response->getJson();
        if (!is_array($json)) {
            throw new RuntimeException('IP provider returned invalid payload.');
        }

        $isp = $json['isp']
            ?? $json['org']
            ?? $json['organization']
            ?? $json['organisation']
            ?? null;

        return [
            'country' => $this->toNullableString($json['country'] ?? $json['country_name'] ?? null),
            'city' => $this->toNullableString($json['city'] ?? null),
            'isp' => $this->toNullableString($isp),
        ];
    }

    /**
     * @param mixed $value Value to normalize.
     * @return string|null
     */
    private function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string)$value);

        return $normalized !== '' ? $normalized : null;
    }

    /**
     * @param string $provider Provider name.
     * @param string $ip IP address.
     * @return string
     */
    private function cacheKey(string $provider, string $ip): string
    {
        return self::CACHE_KEY_PREFIX . sha1($provider . '|' . $ip);
    }

    /**
     * @param array<mixed> $cached Raw cache value.
     * @return bool
     */
    private function isCachePayloadValid(array $cached): bool
    {
        if (!isset($cached['expires_at']) || !is_numeric($cached['expires_at'])) {
            return false;
        }

        return (int)$cached['expires_at'] > time();
    }

    /**
     * @return int
     */
    private function ttlSeconds(): int
    {
        $days = (int)Configure::read('IpEnrichment.cacheTtlDays', self::DEFAULT_TTL_DAYS);
        $days = max(1, $days);

        return $days * 24 * 60 * 60;
    }

    /**
     * @param string $ip IP address.
     * @return bool
     */
    private function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }
}
