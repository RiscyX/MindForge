<?php
declare(strict_types=1);

namespace App\Service;

use RuntimeException;

/**
 * Rich exception thrown by AiService when the AI provider returns an error
 * or the request fails after all retry attempts are exhausted.
 *
 * Callers can inspect HTTP status, error category, and whether the failure
 * was transient (i.e. retries were attempted).
 */
class AiServiceException extends RuntimeException
{
    /**
     * Broad error categories for callers to switch on.
     */
    public const CATEGORY_RATE_LIMITED = 'rate_limited';
    public const CATEGORY_SERVER_ERROR = 'server_error';
    public const CATEGORY_TIMEOUT = 'timeout';
    public const CATEGORY_AUTH = 'auth_error';
    public const CATEGORY_CLIENT_ERROR = 'client_error';
    public const CATEGORY_INVALID_RESPONSE = 'invalid_response';
    public const CATEGORY_NETWORK = 'network_error';

    protected int $httpStatus;
    protected string $category;
    protected int $retryAttempts;

    /**
     * @param string $message Human-readable description.
     * @param int $httpStatus The HTTP status code returned by the provider (0 for network errors).
     * @param string $category One of the CATEGORY_* constants.
     * @param int $retryAttempts How many retry attempts were made before giving up.
     * @param \Throwable|null $previous Original exception chain, if any.
     */
    public function __construct(
        string $message,
        int $httpStatus = 0,
        string $category = self::CATEGORY_SERVER_ERROR,
        int $retryAttempts = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);
        $this->httpStatus = $httpStatus;
        $this->category = $category;
        $this->retryAttempts = $retryAttempts;
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    /**
     * Whether this was a transient failure (retries were attempted).
     */
    public function wasRetried(): bool
    {
        return $this->retryAttempts > 0;
    }

    /**
     * A short, UI-safe error code derived from the category.
     */
    public function getErrorCode(): string
    {
        return match ($this->category) {
            self::CATEGORY_RATE_LIMITED => 'AI_RATE_LIMITED',
            self::CATEGORY_SERVER_ERROR => 'AI_SERVER_ERROR',
            self::CATEGORY_TIMEOUT => 'AI_TIMEOUT',
            self::CATEGORY_AUTH => 'AI_AUTH_ERROR',
            self::CATEGORY_CLIENT_ERROR => 'AI_CLIENT_ERROR',
            self::CATEGORY_INVALID_RESPONSE => 'AI_INVALID_RESPONSE',
            self::CATEGORY_NETWORK => 'AI_NETWORK_ERROR',
            default => 'AI_FAILED',
        };
    }

    /**
     * A user-friendly message suitable for frontend display (no internal details).
     */
    public function getUserMessage(): string
    {
        return match ($this->category) {
            self::CATEGORY_RATE_LIMITED => 'The AI service is temporarily overloaded. Please try again in a few minutes.',
            self::CATEGORY_SERVER_ERROR => 'The AI service encountered an internal error. Please try again later.',
            self::CATEGORY_TIMEOUT => 'The AI service took too long to respond. Please try again.',
            self::CATEGORY_AUTH => 'AI service authentication failed. Please contact an administrator.',
            self::CATEGORY_NETWORK => 'Could not reach the AI service. Please check your connection and try again.',
            self::CATEGORY_INVALID_RESPONSE => 'The AI service returned an unexpected response. Please try again.',
            default => 'An error occurred while communicating with the AI service.',
        };
    }
}
