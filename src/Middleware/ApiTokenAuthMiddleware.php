<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\ApiAuthErrorCodes;
use App\Service\ApiAuthService;
use App\Service\ApiRateLimiterService;
use App\Service\ApiTokenService;
use Cake\Datasource\FactoryLocator;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiTokenAuthMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request through the API token authentication middleware.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Server\RequestHandlerInterface $handler The request handler.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = rtrim($request->getUri()->getPath(), '/');
        $method = strtoupper($request->getMethod());

        // App can be hosted in a subdirectory (e.g. /MindForge).
        // Normalize to the API sub-path so auth works in both cases.
        $apiPath = $this->apiSubPath($path);

        if ($apiPath === null || $method === 'OPTIONS' || $this->isPublicEndpoint($method, $apiPath)) {
            return $handler->handle($request);
        }

        $bearer = $this->extractBearerToken($request);
        if ($bearer === null) {
            return $this->jsonError(
                401,
                ApiAuthErrorCodes::TOKEN_INVALID,
                'Missing Authorization header. Use: Authorization: Bearer <access_token>',
            );
        }

        $tableLocator = FactoryLocator::get('Table');
        /** @var \App\Model\Table\ApiTokensTable $apiTokens */
        $apiTokens = $tableLocator->get('ApiTokens');
        /** @var \App\Model\Table\UsersTable $users */
        $users = $tableLocator->get('Users');
        /** @var \Cake\ORM\Table $activityLogs */
        $activityLogs = $tableLocator->get('ActivityLogs');
        /** @var \Cake\ORM\Table $deviceLogs */
        $deviceLogs = $tableLocator->get('DeviceLogs');

        $tokenService = new ApiTokenService($apiTokens);
        $authService = new ApiAuthService($users, $tokenService, $activityLogs, $deviceLogs);

        $validation = $tokenService->validateAccessToken($bearer);
        if (!$validation['ok']) {
            $reason = (string)($validation['reason'] ?? '');
            if ($reason === 'wrong_token_type') {
                return $this->jsonError(
                    401,
                    (string)$validation['code'],
                    'Invalid token type. Use an access token (not a refresh token).',
                );
            }

            return $this->jsonError(401, (string)$validation['code'], 'Access token is invalid or expired.');
        }

        /** @var \App\Model\Entity\ApiToken $token */
        $token = $validation['token'];
        $token = $apiTokens->get($token->id, contain: ['Users']);

        $rateLimiter = new ApiRateLimiterService();
        $serverParams = $request->getServerParams();
        $remoteIp = (string)($serverParams['REMOTE_ADDR'] ?? 'unknown');
        $rateKey = $token->token_id . '|' . $remoteIp;
        if (!$rateLimiter->isAllowed('api_request', $rateKey, 120, 60)) {
            return $this->jsonError(429, ApiAuthErrorCodes::RATE_LIMITED, 'Too many API requests.');
        }
        $rateLimiter->hit('api_request', $rateKey, 60);

        $user = $token->user;

        if ($user === null) {
            return $this->jsonError(401, ApiAuthErrorCodes::TOKEN_INVALID, 'User not found for token.');
        }

        $stateError = $authService->validateUserState($user);
        if ($stateError !== null) {
            return $this->jsonError(403, $stateError, 'User is not allowed to access API.');
        }

        $request = $request
            ->withAttribute('apiUser', $user)
            ->withAttribute('apiAccessToken', $token)
            ->withAttribute('identity', $user);

        return $handler->handle($request);
    }

    /**
     * Check if the given path is an API path.
     *
     * @param string $path Request path.
     * @return bool
     */
    private function isApiPath(string $path): bool
    {
        return $path === '/api/v1' || str_starts_with($path, '/api/v1/');
    }

    /**
     * Extract the API sub-path from the full request path.
     *
     * @param string $path Full request path.
     * @return string|null The API sub-path, or null if not an API path.
     */
    private function apiSubPath(string $path): ?string
    {
        $pos = strpos($path, '/api/v1');
        if ($pos === false) {
            return null;
        }

        $sub = substr($path, $pos);
        if ($sub === '') {
            return null;
        }

        return rtrim($sub, '/');
    }

    /**
     * Check if the given endpoint is publicly accessible without authentication.
     *
     * @param string $method HTTP method.
     * @param string $path API sub-path.
     * @return bool
     */
    private function isPublicEndpoint(string $method, string $path): bool
    {
        $publicEndpoints = [
            'POST /api/v1/auth/register',
            'POST /api/v1/auth/login',
            'POST /api/v1/auth/forgot-password',
            'POST /api/v1/auth/reset-password',
            'POST /api/v1/auth/refresh',
            'POST /api/v1/auth/logout',
            'GET /api/v1/tests',
        ];

        if (in_array($method . ' ' . $path, $publicEndpoints, true)) {
            return true;
        }

        if ($method === 'GET' && preg_match('#^/api/v1/tests/[^/]+$#', $path) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Extract the bearer token from the Authorization header.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @return string|null The bearer token, or null if not present.
     */
    private function extractBearerToken(ServerRequestInterface $request): ?string
    {
        $header = trim((string)$request->getHeaderLine('Authorization'));
        if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return trim((string)$matches[1]);
    }

    /**
     * Create a JSON error response.
     *
     * @param int $status HTTP status code.
     * @param string $code Application error code.
     * @param string $message Error message.
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function jsonError(int $status, string $code, string $message): ResponseInterface
    {
        $response = new Response();
        $payload = [
            'ok' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        return $response
            ->withType('application/json')
            ->withStatus($status)
            ->withStringBody((string)json_encode($payload));
    }
}
