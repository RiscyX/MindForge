<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\Role;

/**
 * Handles web login/logout workflow: rate limiting, account gate checks,
 * activity logging, and role-based redirect resolution.
 *
 * Extracted from UsersController::login() and logout().
 */
class WebLoginService
{
    public const RESULT_RENDER_LOGIN = 'render_login';
    public const RESULT_REDIRECT = 'redirect';

    public const CODE_RATE_LIMITED = 'rate_limited';
    public const CODE_INACTIVE_OR_BLOCKED = 'inactive_or_blocked';
    public const CODE_SUCCESS = 'success';
    public const CODE_INVALID_CREDENTIALS = 'invalid_credentials';
    public const CODE_NOT_POST = 'not_post';

    private LoginRateLimitService $rateLimiter;
    private LoginActivityService $activityService;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->rateLimiter = new LoginRateLimitService();
        $this->activityService = new LoginActivityService();
    }

    /**
     * Process a login attempt.
     *
     * @param bool $isPost Whether the request is a POST.
     * @param bool $isAuthenticated Whether CakePHP's Authentication result is valid.
     * @param array<string, mixed>|null $identityData Identity data (id, email, is_active, is_blocked, role_id) if authenticated.
     * @param string $email The email submitted in the form.
     * @param string $ip Client IP address.
     * @param string $userAgent Raw User-Agent header.
     * @param string $lang Current language code.
     * @param string|null $queryRedirect The ?redirect= query param, if any.
     * @return array{action: string, code: string, flash_type?: string, flash_message?: string, redirect_url?: array|string, should_logout?: bool}
     */
    public function handleLogin(
        bool $isPost,
        bool $isAuthenticated,
        ?array $identityData,
        string $email,
        string $ip,
        string $userAgent,
        string $lang,
        ?string $queryRedirect = null,
    ): array {
        // Rate limit check on POST
        if ($isPost && !$this->rateLimiter->isAllowed($ip)) {
            $this->activityService->logLoginFailure($email, 'rate_limit', $ip, $userAgent);

            return [
                'action' => self::RESULT_REDIRECT,
                'code' => self::CODE_RATE_LIMITED,
                'flash_type' => 'error',
                'flash_message' => __('Too many login attempts. Please try again in a minute.'),
            ];
        }

        // Successfully authenticated
        if ($isAuthenticated && $identityData !== null) {
            return $this->handleAuthenticatedUser($identityData, $ip, $userAgent, $lang, $queryRedirect);
        }

        // POST with invalid credentials
        if ($isPost) {
            $this->rateLimiter->recordAttempt($ip);
            $this->activityService->logLoginFailure($email, 'invalid_credentials', $ip, $userAgent);

            return [
                'action' => self::RESULT_RENDER_LOGIN,
                'code' => self::CODE_INVALID_CREDENTIALS,
                'flash_type' => 'error',
                'flash_message' => __('Invalid email or password.'),
            ];
        }

        // GET request — just render the login form
        return [
            'action' => self::RESULT_RENDER_LOGIN,
            'code' => self::CODE_NOT_POST,
        ];
    }

    /**
     * Process logout: log activity and clear rate limit.
     *
     * @param int|null $userId The authenticated user ID (null if no identity).
     * @param string $ip Client IP address.
     * @param string $userAgent Raw User-Agent header.
     * @return void
     */
    public function handleLogout(?int $userId, string $ip, string $userAgent): void
    {
        if ($userId !== null) {
            $this->activityService->logLogout($userId, $ip, $userAgent);
        }

        $this->rateLimiter->clear($ip);
    }

    /**
     * Handle an authenticated user: gate check, activity log, redirect resolution.
     *
     * @param array<string, mixed> $identityData
     * @param string $ip
     * @param string $userAgent
     * @param string $lang
     * @param string|null $queryRedirect
     * @return array<string, mixed>
     */
    private function handleAuthenticatedUser(
        array $identityData,
        string $ip,
        string $userAgent,
        string $lang,
        ?string $queryRedirect,
    ): array {
        $isActive = (bool)($identityData['is_active'] ?? false);
        $isBlocked = (bool)($identityData['is_blocked'] ?? false);

        // Gate: inactive or blocked users must not stay logged in
        if (!$isActive || $isBlocked) {
            $this->rateLimiter->recordAttempt($ip);
            $this->activityService->logLoginFailure(
                $identityData['email'] ?? null,
                'inactive_or_blocked',
                $ip,
                $userAgent,
            );

            return [
                'action' => self::RESULT_RENDER_LOGIN,
                'code' => self::CODE_INACTIVE_OR_BLOCKED,
                'flash_type' => 'error',
                'flash_message' => __('Invalid email or password.'),
                'should_logout' => true,
            ];
        }

        // Successful login
        $userId = (int)$identityData['id'];
        $this->activityService->logLogin($userId, $ip, $userAgent);
        $this->rateLimiter->clear($ip);

        $redirectUrl = $this->resolvePostLoginRedirect(
            (int)($identityData['role_id'] ?? Role::USER),
            $lang,
            $queryRedirect,
        );

        return [
            'action' => self::RESULT_REDIRECT,
            'code' => self::CODE_SUCCESS,
            'redirect_url' => $redirectUrl,
        ];
    }

    /**
     * Resolve the post-login redirect URL based on user role.
     *
     * @param int $roleId
     * @param string $lang
     * @param string|null $queryRedirect
     * @return array<string, mixed>|string
     */
    private function resolvePostLoginRedirect(int $roleId, string $lang, ?string $queryRedirect): array|string
    {
        if ($roleId === Role::ADMIN) {
            return [
                'prefix' => 'Admin',
                'controller' => 'Dashboard',
                'action' => 'index',
                'lang' => $lang,
            ];
        }

        if ($roleId === Role::CREATOR) {
            return [
                'prefix' => 'QuizCreator',
                'controller' => 'Dashboard',
                'action' => 'index',
                'lang' => $lang,
            ];
        }

        if ($this->isSafeRelativeRedirect($queryRedirect)) {
            return $queryRedirect;
        }

        return [
            'prefix' => false,
            'controller' => 'Tests',
            'action' => 'index',
            'lang' => $lang,
        ];
    }

    /**
     * Accept only local, absolute-path redirects (e.g. /en/tests).
     *
     * @param string|null $redirect
     * @return bool
     */
    private function isSafeRelativeRedirect(?string $redirect): bool
    {
        if ($redirect === null) {
            return false;
        }

        $redirect = trim($redirect);
        if ($redirect === '' || !str_starts_with($redirect, '/')) {
            return false;
        }

        if (str_starts_with($redirect, '//')) {
            return false;
        }

        $parts = parse_url($redirect);
        if ($parts === false) {
            return false;
        }

        if (isset($parts['scheme']) || isset($parts['host'])) {
            return false;
        }

        return true;
    }
}
