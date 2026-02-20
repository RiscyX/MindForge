<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Client\Response;
use Cake\Log\Log;
use Throwable;

/**
 * AI provider gateway with built-in retry / exponential back-off.
 *
 * Retryable conditions (up to MAX_RETRIES attempts):
 *  - HTTP 429 (rate-limited)
 *  - HTTP 5xx (server error)
 *  - Network / timeout exceptions
 *
 * Non-retryable conditions (fail immediately):
 *  - HTTP 401/403 (auth)
 *  - HTTP 4xx other than 429 (client error)
 *  - Unexpected response format
 */
class AiService
{
    /**
     * Maximum number of retry attempts after the initial request.
     */
    private const MAX_RETRIES = 3;

    /**
     * Base delay in seconds for exponential back-off (doubles each retry).
     */
    private const BASE_DELAY_SECONDS = 1.0;

    /**
     * Absolute cap for any single delay (seconds).
     */
    private const MAX_DELAY_SECONDS = 15.0;

    /**
     * HTTP request timeout in seconds (connect + read).
     */
    private const REQUEST_TIMEOUT = 60;

    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected ?string $visionModel;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->client = new Client([
            'timeout' => self::REQUEST_TIMEOUT,
        ]);
        $this->apiKey = Configure::read('AI.apiKey');
        $this->baseUrl = Configure::read('AI.baseUrl');
        $this->model = Configure::read('AI.defaultModel');
        $this->visionModel = Configure::read('AI.visionModel');

        if (empty($this->apiKey)) {
            Log::warning('AI API Key is missing in configuration.');
        }
    }

    /**
     * Low-level chat completion helper with automatic retry / back-off.
     *
     * Returns assistant message content on success.
     * Throws AiServiceException with rich metadata on failure.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param string|null $model
     * @param float $temperature
     * @param array<string, mixed> $additionalParams
     * @return string
     * @throws \App\Service\AiServiceException
     */
    public function generateChatContent(
        array $messages,
        ?string $model = null,
        float $temperature = 0.7,
        array $additionalParams = [],
    ): string {
        $payload = array_merge([
            'model' => $model ?: $this->model,
            'messages' => $messages,
            'temperature' => $temperature,
        ], $additionalParams);

        $lastException = null;
        $attempt = 0;

        while ($attempt <= self::MAX_RETRIES) {
            try {
                $response = $this->client->post(
                    "{$this->baseUrl}/chat/completions",
                    $payload,
                    [
                        'type' => 'json',
                        'headers' => [
                            'Authorization' => 'Bearer ' . $this->apiKey,
                            'Content-Type' => 'application/json',
                        ],
                    ],
                );

                if ($response->isOk()) {
                    return $this->extractContent($response);
                }

                $statusCode = $response->getStatusCode();
                $body = $response->getStringBody();
                $category = $this->categorizeHttpError($statusCode);

                Log::error(sprintf(
                    'AI API Error [attempt %d/%d] HTTP %d: %s',
                    $attempt + 1,
                    self::MAX_RETRIES + 1,
                    $statusCode,
                    mb_substr($body, 0, 500),
                ));

                // Non-retryable errors → fail immediately
                if (!$this->isRetryable($statusCode)) {
                    throw new AiServiceException(
                        "AI request failed with HTTP {$statusCode}.",
                        $statusCode,
                        $category,
                        $attempt,
                    );
                }

                // For 429, respect Retry-After header if present
                $retryAfter = $this->parseRetryAfter($response, $attempt);
                $lastException = new AiServiceException(
                    "AI request failed with HTTP {$statusCode} (attempt " . ($attempt + 1) . ').',
                    $statusCode,
                    $category,
                    $attempt,
                );

                if ($attempt < self::MAX_RETRIES) {
                    $this->backoff($retryAfter);
                }
            } catch (AiServiceException $e) {
                // Re-throw our own exceptions (non-retryable ones exit above)
                if (!$this->isRetryable($e->getHttpStatus())) {
                    throw $e;
                }
                $lastException = $e;

                if ($attempt < self::MAX_RETRIES) {
                    $this->backoff($this->calculateDelay($attempt));
                }
            } catch (Throwable $e) {
                // Network / timeout / unexpected errors
                $isTimeout = $this->isTimeoutException($e);
                $category = $isTimeout
                    ? AiServiceException::CATEGORY_TIMEOUT
                    : AiServiceException::CATEGORY_NETWORK;

                Log::error(sprintf(
                    'AI network error [attempt %d/%d]: %s',
                    $attempt + 1,
                    self::MAX_RETRIES + 1,
                    $e->getMessage(),
                ));

                $lastException = new AiServiceException(
                    $isTimeout
                        ? 'AI request timed out (attempt ' . ($attempt + 1) . ').'
                        : 'AI request network error (attempt ' . ($attempt + 1) . ').',
                    0,
                    $category,
                    $attempt,
                    $e,
                );

                if ($attempt < self::MAX_RETRIES) {
                    $this->backoff($this->calculateDelay($attempt));
                }
            }

            $attempt++;
        }

        // All retries exhausted
        Log::error('AI request failed after ' . (self::MAX_RETRIES + 1) . ' attempts.');

        throw $lastException ?? new AiServiceException(
            'AI request failed after all retry attempts.',
            0,
            AiServiceException::CATEGORY_SERVER_ERROR,
            self::MAX_RETRIES,
        );
    }

    /**
     * Sends a prompt to the AI provider and returns the response content.
     *
     * @param string $prompt The user prompt.
     * @param string|null $systemMessage Optional system message to set context.
     * @param float $temperature The temperature for response randomness (default 0.7).
     * @param array $additionalParams Any other parameters to pass to the API.
     * @return string The content of the AI's response.
     * @throws \App\Service\AiServiceException
     */
    public function generateContent(
        string $prompt,
        ?string $systemMessage = null,
        float $temperature = 0.7,
        array $additionalParams = [],
    ): string {
        $messages = [];
        if ($systemMessage) {
            $messages[] = ['role' => 'system', 'content' => $systemMessage];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $this->generateChatContent($messages, null, $temperature, $additionalParams);
    }

    /**
     * Generate content from prompt + optional images using a vision-capable model.
     *
     * @param string $prompt
     * @param list<string> $imageDataUrls data: URLs (base64)
     * @param string|null $systemMessage
     * @param float $temperature
     * @param array<string, mixed> $additionalParams
     * @return string
     * @throws \App\Service\AiServiceException
     */
    public function generateVisionContent(
        string $prompt,
        array $imageDataUrls = [],
        ?string $systemMessage = null,
        float $temperature = 0.7,
        array $additionalParams = [],
    ): string {
        $messages = [];
        if ($systemMessage) {
            $messages[] = ['role' => 'system', 'content' => $systemMessage];
        }

        $content = [];
        $content[] = ['type' => 'text', 'text' => $prompt];
        foreach ($imageDataUrls as $url) {
            $content[] = ['type' => 'image_url', 'image_url' => ['url' => $url]];
        }
        $messages[] = ['role' => 'user', 'content' => $content];

        $model = $this->visionModel ?: $this->model;

        return $this->generateChatContent($messages, $model, $temperature, $additionalParams);
    }

    // ------------------------------------------------------------------
    //  Private helpers
    // ------------------------------------------------------------------

    /**
     * Extract assistant content from a successful response, or throw.
     */
    private function extractContent(Response $response): string
    {
        $json = $response->getJson();
        /** @var array $json */
        if (isset($json['choices'][0]['message']['content'])) {
            return (string)$json['choices'][0]['message']['content'];
        }

        Log::error('AI Unexpected Response Format: ' . mb_substr($response->getStringBody(), 0, 500));

        throw new AiServiceException(
            'Invalid response format from AI provider.',
            $response->getStatusCode(),
            AiServiceException::CATEGORY_INVALID_RESPONSE,
        );
    }

    /**
     * Determine whether an HTTP status code warrants a retry.
     */
    private function isRetryable(int $statusCode): bool
    {
        // 0 = network error (always retry), 429 = rate-limited, 5xx = server error
        if ($statusCode === 0 || $statusCode === 429) {
            return true;
        }

        return $statusCode >= 500 && $statusCode < 600;
    }

    /**
     * Categorize an HTTP error into a broad bucket.
     */
    private function categorizeHttpError(int $statusCode): string
    {
        if ($statusCode === 429) {
            return AiServiceException::CATEGORY_RATE_LIMITED;
        }
        if ($statusCode === 401 || $statusCode === 403) {
            return AiServiceException::CATEGORY_AUTH;
        }
        if ($statusCode >= 500) {
            return AiServiceException::CATEGORY_SERVER_ERROR;
        }

        return AiServiceException::CATEGORY_CLIENT_ERROR;
    }

    /**
     * Parse Retry-After header. Falls back to exponential delay.
     */
    private function parseRetryAfter(Response $response, int $attempt): float
    {
        $header = $response->getHeaderLine('Retry-After');
        if ($header !== '') {
            // Could be seconds or an HTTP-date – we only handle seconds.
            if (is_numeric($header)) {
                $parsed = (float)$header;
                // Clamp to a reasonable range
                return min(max($parsed, 0.5), self::MAX_DELAY_SECONDS);
            }
        }

        return $this->calculateDelay($attempt);
    }

    /**
     * Exponential back-off with jitter:  base * 2^attempt + random(0..0.5s)
     */
    private function calculateDelay(int $attempt): float
    {
        $delay = self::BASE_DELAY_SECONDS * (2 ** $attempt);
        $jitter = mt_rand(0, 500) / 1000.0;
        $delay += $jitter;

        return min($delay, self::MAX_DELAY_SECONDS);
    }

    /**
     * Sleep for the given number of seconds (allows fractional).
     */
    private function backoff(float $seconds): void
    {
        $microseconds = (int)($seconds * 1_000_000);
        if ($microseconds > 0) {
            usleep($microseconds);
        }
    }

    /**
     * Best-effort detection of timeout-related exceptions.
     */
    private function isTimeoutException(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        $patterns = ['timed out', 'timeout', 'connection reset', 'operation timed out'];
        foreach ($patterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
