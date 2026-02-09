<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Model\Entity\User;
use App\Service\ApiAuthErrorCodes;
use App\Service\ApiAuthService;
use App\Service\ApiRateLimiterService;
use App\Service\ApiTokenService;
use Cake\Routing\Router;
use Laminas\Diactoros\UploadedFile;
use OpenApi\Attributes as OA;
use Throwable;
use function Cake\Core\env;

#[OA\Tag(name: 'Auth', description: 'Mobile authentication endpoints')]
class AuthController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->Authentication->allowUnauthenticated(['login', 'register', 'refresh', 'logout', 'me', 'updateMe']);
    }

    #[OA\Post(
        path: '/api/v1/auth/register',
        summary: 'Register a mobile account',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Registered (activation required)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'user_id', type: 'integer', nullable: true),
                        new OA\Property(property: 'requires_activation', type: 'boolean', example: true),
                        new OA\Property(property: 'email_sent', type: 'boolean'),
                    ],
                ),
            ),
            new OA\Response(response: 409, description: 'Email already used'),
            new OA\Response(response: 422, description: 'Invalid input'),
            new OA\Response(response: 429, description: 'Rate limited'),
        ],
    )]
    public function register(): void
    {
        $this->request->allowMethod(['post']);

        $ip = (string)$this->request->clientIp();
        $email = trim((string)$this->request->getData('email'));
        $password = (string)$this->request->getData('password');
        $passwordConfirm = (string)$this->request->getData('password_confirm');
        $lang = strtolower(trim((string)$this->request->getData('lang', 'en')));
        $lang = in_array($lang, ['en', 'hu'], true) ? $lang : 'en';

        $rateLimiter = new ApiRateLimiterService();
        $bucketKey = strtolower($email) . '|' . $ip;
        $maxAttempts = 5;
        $windowSeconds = 60;
        if (!$rateLimiter->isAllowed('auth_register', $bucketKey, $maxAttempts, $windowSeconds)) {
            $this->jsonError(429, ApiAuthErrorCodes::RATE_LIMITED, 'Too many registration attempts.');

            return;
        }

        $tableLocator = $this->getTableLocator();
        /** @var \App\Model\Table\UsersTable $users */
        $users = $tableLocator->get('Users');
        /** @var \App\Model\Table\ApiTokensTable $apiTokens */
        $apiTokens = $tableLocator->get('ApiTokens');
        /** @var \Cake\ORM\Table $activityLogs */
        $activityLogs = $tableLocator->get('ActivityLogs');
        /** @var \Cake\ORM\Table $deviceLogs */
        $deviceLogs = $tableLocator->get('DeviceLogs');
        /** @var \Cake\ORM\Table $userTokens */
        $userTokens = $tableLocator->get('UserTokens');

        $tokenService = new ApiTokenService($apiTokens);
        $authService = new ApiAuthService($users, $tokenService, $activityLogs, $deviceLogs);

        $result = $authService->register(
            email: $email,
            password: $password,
            passwordConfirm: $passwordConfirm,
            lang: $lang,
            baseUrl: rtrim((string)env('BASE_URL', Router::url('/', true)), '/'),
            ip: $ip,
            userAgent: (string)$this->request->getHeaderLine('User-Agent'),
            userTokensTable: $userTokens,
        );

        if (!$result['ok']) {
            $rateLimiter->hit('auth_register', $bucketKey, $windowSeconds);
            $status = $this->statusForErrorCode((string)$result['code']);
            $message = $this->messageForErrorCode((string)$result['code']);
            $this->jsonError($status, (string)$result['code'], $message);

            return;
        }

        $rateLimiter->clear('auth_register', $bucketKey);

        $this->response = $this->response->withStatus(201);
        $this->jsonSuccess([
            'message' => 'Registration successful. Please activate your account from email.',
            'user_id' => $result['user_id'] ?? null,
            'requires_activation' => true,
            'email_sent' => (bool)($result['email_sent'] ?? false),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Login with email and password',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token pair',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(property: 'refresh_token', type: 'string'),
                        new OA\Property(property: 'expires_in', type: 'integer', nullable: true),
                        new OA\Property(property: 'refresh_expires_in', type: 'integer', nullable: true),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'user', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Invalid credentials / token invalid'),
            new OA\Response(response: 403, description: 'User inactive/blocked'),
            new OA\Response(response: 429, description: 'Rate limited'),
        ],
    )]
    public function login(): void
    {
        $this->request->allowMethod(['post']);

        $ip = (string)$this->request->clientIp();
        $email = trim((string)$this->request->getData('email'));
        $password = (string)$this->request->getData('password');
        $lang = strtolower(trim((string)$this->request->getData('lang', 'en')));
        $lang = in_array($lang, ['en', 'hu'], true) ? $lang : 'en';

        $rateLimiter = new ApiRateLimiterService();
        $bucketKey = strtolower($email) . '|' . $ip;
        $maxAttempts = 6;
        $windowSeconds = 60;
        if (!$rateLimiter->isAllowed('auth_login', $bucketKey, $maxAttempts, $windowSeconds)) {
            $this->jsonError(429, ApiAuthErrorCodes::RATE_LIMITED, 'Too many login attempts.');

            return;
        }

        $tableLocator = $this->getTableLocator();
        /** @var \App\Model\Table\UsersTable $users */
        $users = $tableLocator->get('Users');
        /** @var \App\Model\Table\ApiTokensTable $apiTokens */
        $apiTokens = $tableLocator->get('ApiTokens');
        /** @var \Cake\ORM\Table $activityLogs */
        $activityLogs = $tableLocator->get('ActivityLogs');
        /** @var \Cake\ORM\Table $deviceLogs */
        $deviceLogs = $tableLocator->get('DeviceLogs');

        $tokenService = new ApiTokenService($apiTokens);
        $authService = new ApiAuthService($users, $tokenService, $activityLogs, $deviceLogs);

        $result = $authService->login(
            email: $email,
            password: $password,
            ip: $ip,
            userAgent: (string)$this->request->getHeaderLine('User-Agent'),
        );

        if (!$result['ok']) {
            $rateLimiter->hit('auth_login', $bucketKey, $windowSeconds);
            $status = $this->statusForErrorCode((string)$result['code']);
            $message = $this->messageForErrorCode((string)$result['code']);
            $this->jsonError($status, (string)$result['code'], $message);

            return;
        }

        $rateLimiter->clear('auth_login', $bucketKey);

        /** @var \App\Model\Entity\User $user */
        $user = $result['user'];
        $tokens = $result['tokens'] ?? [];

        $payload = [
            'message' => $lang === 'hu' ? 'Sikeres bejelentkezés.' : 'Login successful.',
            'access_token' => $tokens['access_token'] ?? null,
            'access_token_id' => $tokens['access_token_id'] ?? null,
            'expires_in' => $tokens['access_expires_in'] ?? null,
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'refresh_token_id' => $tokens['refresh_token_id'] ?? null,
            'refresh_expires_in' => $tokens['refresh_expires_in'] ?? null,
            'token_type' => $tokens['token_type'] ?? 'Bearer',
            // Compatibility: some clients expect a nested tokens object.
            'tokens' => [
                'access_token' => $tokens['access_token'] ?? null,
                'access_token_id' => $tokens['access_token_id'] ?? null,
                'expires_in' => $tokens['access_expires_in'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'refresh_token_id' => $tokens['refresh_token_id'] ?? null,
                'refresh_expires_in' => $tokens['refresh_expires_in'] ?? null,
                'token_type' => $tokens['token_type'] ?? 'Bearer',
            ],
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'is_active' => (bool)$user->is_active,
                'is_blocked' => (bool)$user->is_blocked,
            ],
        ];

        $this->jsonSuccess($payload);
    }

    #[OA\Post(
        path: '/api/v1/auth/refresh',
        summary: 'Rotate refresh token and issue new pair',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'New token pair',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(property: 'refresh_token', type: 'string'),
                        new OA\Property(property: 'expires_in', type: 'integer', nullable: true),
                        new OA\Property(property: 'refresh_expires_in', type: 'integer', nullable: true),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Invalid/expired refresh token'),
            new OA\Response(response: 403, description: 'User inactive/blocked'),
            new OA\Response(response: 429, description: 'Rate limited'),
        ],
    )]
    public function refresh(): void
    {
        $this->request->allowMethod(['post']);

        $ip = (string)$this->request->clientIp();
        $refreshToken = trim((string)$this->request->getData('refresh_token'));

        $rateLimiter = new ApiRateLimiterService();
        $bucketKey = hash('sha256', $refreshToken . '|' . $ip);
        $maxAttempts = 10;
        $windowSeconds = 60;
        if (!$rateLimiter->isAllowed('auth_refresh', $bucketKey, $maxAttempts, $windowSeconds)) {
            $this->jsonError(429, ApiAuthErrorCodes::RATE_LIMITED, 'Too many refresh attempts.');

            return;
        }

        $tableLocator = $this->getTableLocator();
        /** @var \App\Model\Table\UsersTable $users */
        $users = $tableLocator->get('Users');
        /** @var \App\Model\Table\ApiTokensTable $apiTokens */
        $apiTokens = $tableLocator->get('ApiTokens');
        /** @var \Cake\ORM\Table $activityLogs */
        $activityLogs = $tableLocator->get('ActivityLogs');
        /** @var \Cake\ORM\Table $deviceLogs */
        $deviceLogs = $tableLocator->get('DeviceLogs');

        $tokenService = new ApiTokenService($apiTokens);
        $authService = new ApiAuthService($users, $tokenService, $activityLogs, $deviceLogs);

        $result = $authService->refresh(
            refreshToken: $refreshToken,
            ip: $ip,
            userAgent: (string)$this->request->getHeaderLine('User-Agent'),
        );

        if (!$result['ok']) {
            $rateLimiter->hit('auth_refresh', $bucketKey, $windowSeconds);
            $status = $this->statusForErrorCode((string)$result['code']);
            $message = $this->messageForErrorCode((string)$result['code']);
            $this->jsonError($status, (string)$result['code'], $message);

            return;
        }

        $rateLimiter->clear('auth_refresh', $bucketKey);

        $tokens = $result['tokens'] ?? [];
        $payload = [
            'message' => 'Token refreshed.',
            'access_token' => $tokens['access_token'] ?? null,
            'access_token_id' => $tokens['access_token_id'] ?? null,
            'expires_in' => $tokens['access_expires_in'] ?? null,
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'refresh_token_id' => $tokens['refresh_token_id'] ?? null,
            'refresh_expires_in' => $tokens['refresh_expires_in'] ?? null,
            'token_type' => $tokens['token_type'] ?? 'Bearer',
            'tokens' => [
                'access_token' => $tokens['access_token'] ?? null,
                'access_token_id' => $tokens['access_token_id'] ?? null,
                'expires_in' => $tokens['access_expires_in'] ?? null,
                'refresh_token' => $tokens['refresh_token'] ?? null,
                'refresh_token_id' => $tokens['refresh_token_id'] ?? null,
                'refresh_expires_in' => $tokens['refresh_expires_in'] ?? null,
                'token_type' => $tokens['token_type'] ?? 'Bearer',
            ],
        ];

        $this->jsonSuccess($payload);
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Logout current session or all devices',
        tags: ['Auth'],
        responses: [
            new OA\Response(response: 204, description: 'Logged out'),
        ],
    )]
    public function logout(): void
    {
        $this->request->allowMethod(['post']);
        $this->disableAutoRender();

        $allDevices = (bool)$this->request->getData('all_devices', false);
        $refreshToken = trim((string)$this->request->getData('refresh_token', ''));
        $accessToken = $this->extractBearerToken();

        $tableLocator = $this->getTableLocator();
        /** @var \App\Model\Table\UsersTable $users */
        $users = $tableLocator->get('Users');
        /** @var \App\Model\Table\ApiTokensTable $apiTokens */
        $apiTokens = $tableLocator->get('ApiTokens');
        /** @var \Cake\ORM\Table $activityLogs */
        $activityLogs = $tableLocator->get('ActivityLogs');
        /** @var \Cake\ORM\Table $deviceLogs */
        $deviceLogs = $tableLocator->get('DeviceLogs');

        $tokenService = new ApiTokenService($apiTokens);
        $authService = new ApiAuthService($users, $tokenService, $activityLogs, $deviceLogs);

        $user = null;
        if ($accessToken !== null) {
            $validation = $tokenService->validateAccessToken($accessToken);
            if ($validation['ok']) {
                /** @var \App\Model\Entity\ApiToken $validAccess */
                $validAccess = $validation['token'];
                $validAccess = $apiTokens->get($validAccess->id, contain: ['Users']);
                $user = $validAccess->user;
            }
        }

        $authService->logout($accessToken, $refreshToken, $allDevices, $user);

        $this->response = $this->response->withStatus(204);
    }

    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Current authenticated user profile',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User payload',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'user', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
        ],
    )]
    public function me(): void
    {
        /** @var \App\Model\Entity\User|null $apiUser */
        $apiUser = $this->request->getAttribute('apiUser');
        if ($apiUser === null) {
            $this->jsonError(401, ApiAuthErrorCodes::TOKEN_INVALID, 'Access token is required.');

            return;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get((int)$apiUser->id, contain: ['Roles']);

        $this->jsonSuccess(['user' => $this->buildUserPayload($user)]);
    }

    #[OA\Patch(
        path: '/api/v1/auth/me',
        summary: 'Update current authenticated user profile',
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Updated user payload',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'ok', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'user', type: 'object'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Missing/invalid token'),
            new OA\Response(response: 422, description: 'Validation failed'),
            new OA\Response(response: 500, description: 'Server error'),
        ],
    )]
    public function updateMe(): void
    {
        $this->request->allowMethod(['patch', 'put', 'post']);

        /** @var \App\Model\Entity\User|null $apiUser */
        $apiUser = $this->request->getAttribute('apiUser');
        if ($apiUser === null) {
            $this->jsonError(401, ApiAuthErrorCodes::TOKEN_INVALID, 'Access token is required.');

            return;
        }

        /** @var \App\Model\Table\UsersTable $users */
        $users = $this->getTableLocator()->get('Users');
        $user = $users->get((int)$apiUser->id, contain: ['Roles']);

        $data = [];
        if ($this->request->getData('username') !== null) {
            $data['username'] = trim((string)$this->request->getData('username'));
        }

        $avatarFile = $this->request->getData('avatar_file');
        if ($avatarFile instanceof UploadedFile && $avatarFile->getError() === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $fileType = (string)$avatarFile->getClientMediaType();
            if (!in_array($fileType, $allowedTypes, true)) {
                $this->jsonError(422, ApiAuthErrorCodes::INVALID_AVATAR_FORMAT, 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP.');

                return;
            }

            $ext = pathinfo((string)$avatarFile->getClientFilename(), PATHINFO_EXTENSION);
            $filename = 'avatar_' . $user->id . '_' . time() . ($ext !== '' ? '.' . $ext : '');
            $avatarsDir = WWW_ROOT . 'img' . DS . 'avatars';
            if (!is_dir($avatarsDir)) {
                mkdir($avatarsDir, 0775, true);
            }

            if (!is_dir($avatarsDir) || !is_writable($avatarsDir)) {
                $this->jsonError(500, ApiAuthErrorCodes::PROFILE_UPDATE_FAILED, 'Avatar upload directory is not writable.');

                return;
            }

            $targetPath = $avatarsDir . DS . $filename;
            try {
                $avatarFile->moveTo($targetPath);
            } catch (Throwable) {
                $this->jsonError(500, ApiAuthErrorCodes::PROFILE_UPDATE_FAILED, 'Failed to store avatar image.');

                return;
            }

            if ($user->avatar_url && str_contains((string)$user->avatar_url, 'avatars/')) {
                $oldFile = WWW_ROOT . 'img' . DS . (string)$user->avatar_url;
                if (file_exists($oldFile) && is_file($oldFile)) {
                    unlink($oldFile);
                }
            }

            $data['avatar_url'] = 'avatars/' . $filename;
        }

        $user = $users->patchEntity($user, $data, ['fields' => ['username', 'avatar_url']]);
        if (!$users->save($user)) {
            $this->response = $this->response->withStatus(422);
            $this->set('error', [
                'code' => ApiAuthErrorCodes::PROFILE_UPDATE_FAILED,
                'message' => 'Unable to update profile.',
                'details' => $user->getErrors(),
            ]);
            $this->viewBuilder()->setOption('serialize', ['error']);

            return;
        }

        $user = $users->get((int)$user->id, contain: ['Roles']);
        $this->jsonSuccess([
            'message' => 'Profile updated successfully.',
            'user' => $this->buildUserPayload($user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUserPayload(User $user): array
    {
        $roleName = null;
        if ($user->hasValue('role') && $user->role !== null) {
            $roleName = $user->role->name;
        }

        return [
            'id' => $user->id,
            'email' => $user->email,
            'username' => $user->username,
            'avatar_url' => $user->avatar_url,
            'role_id' => $user->role_id,
            'role_name' => $roleName,
            'is_active' => (bool)$user->is_active,
            'is_blocked' => (bool)$user->is_blocked,
            'created_at' => $user->created_at?->format('c'),
            'last_login_at' => $user->last_login_at?->format('c'),
        ];
    }

    private function statusForErrorCode(string $code): int
    {
        return match ($code) {
            ApiAuthErrorCodes::USER_INACTIVE,
            ApiAuthErrorCodes::USER_BLOCKED,
            ApiAuthErrorCodes::FORBIDDEN_ROLE => 403,
            ApiAuthErrorCodes::EMAIL_ALREADY_USED => 409,
            ApiAuthErrorCodes::PASSWORD_MISMATCH,
            ApiAuthErrorCodes::REGISTRATION_FAILED,
            ApiAuthErrorCodes::PROFILE_UPDATE_FAILED,
            ApiAuthErrorCodes::INVALID_AVATAR_FORMAT => 422,
            ApiAuthErrorCodes::RATE_LIMITED => 429,
            default => 401,
        };
    }

    private function messageForErrorCode(string $code): string
    {
        return match ($code) {
            ApiAuthErrorCodes::INVALID_CREDENTIALS => 'Invalid email or password.',
            ApiAuthErrorCodes::USER_INACTIVE => 'User account is inactive.',
            ApiAuthErrorCodes::USER_BLOCKED => 'User account is blocked.',
            ApiAuthErrorCodes::TOKEN_EXPIRED => 'Token has expired.',
            ApiAuthErrorCodes::TOKEN_REVOKED => 'Token is revoked.',
            ApiAuthErrorCodes::TOKEN_REUSED => 'Refresh token reuse detected.',
            ApiAuthErrorCodes::RATE_LIMITED => 'Too many attempts.',
            ApiAuthErrorCodes::FORBIDDEN_ROLE => 'User role is not allowed.',
            ApiAuthErrorCodes::PASSWORD_MISMATCH => 'Passwords do not match.',
            ApiAuthErrorCodes::EMAIL_ALREADY_USED => 'Email is already registered.',
            ApiAuthErrorCodes::REGISTRATION_FAILED => 'Registration failed due to invalid data.',
            ApiAuthErrorCodes::PROFILE_UPDATE_FAILED => 'Unable to update profile.',
            ApiAuthErrorCodes::INVALID_AVATAR_FORMAT => 'Invalid image format. Allowed: JPG, PNG, GIF, WEBP.',
            default => 'Authentication failed.',
        };
    }

    private function extractBearerToken(): ?string
    {
        $header = trim((string)$this->request->getHeaderLine('Authorization'));
        if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return trim((string)$matches[1]);
    }
}
