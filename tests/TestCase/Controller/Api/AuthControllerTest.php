<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AuthControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.UserTokens',
        'app.ApiTokens',
        'app.ActivityLogs',
        'app.DeviceLogs',
    ];

    public function testRegisterCreatesInactiveUserForMobile(): void
    {
        $email = 'mobile.signup@example.com';
        $payload = $this->postJson('/api/v1/auth/register', [
            'email' => $email,
            'password' => 'Passw0rd!23',
            'password_confirm' => 'Passw0rd!23',
            'lang' => 'en',
        ]);

        $this->assertSame(201, $this->_response->getStatusCode(), (string)$this->_response->getBody());
        $this->assertTrue((bool)($payload['requires_activation'] ?? false));
        $this->assertArrayHasKey('email_sent', $payload);

        $users = $this->getTableLocator()->get('Users');
        $user = $users->find()->where(['email' => $email])->firstOrFail();
        $this->assertSame('mobile.signup', $user->username);
        $this->assertFalse((bool)$user->is_active);
        $this->assertSame(3, (int)$user->role_id);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $users = $this->getTableLocator()->get('Users');
        $existing = $users->find()->where(['email' => 'api-user@example.com'])->first();
        if ($existing === null) {
            $user = $users->newEntity([
                'email' => 'api-user@example.com',
                'password' => 'Passw0rd!23',
                'role_id' => 1,
                'is_active' => true,
                'is_blocked' => false,
                'created_at' => FrozenTime::now(),
                'updated_at' => FrozenTime::now(),
            ]);
            $users->saveOrFail($user);
        }
    }

    public function testLoginAndMeLifecycle(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $deviceLogs = $this->getTableLocator()->get('DeviceLogs');
        $userBefore = $users->find()->where(['email' => 'api-user@example.com'])->firstOrFail();
        $deviceLogCountBefore = (int)$deviceLogs->find()->where(['user_id' => $userBefore->id])->count();

        $loginPayload = $this->postJson('/api/v1/auth/login', [
            'email' => 'api-user@example.com',
            'password' => 'Passw0rd!23',
        ]);

        $this->assertSame(200, $this->_response->getStatusCode(), (string)$this->_response->getBody());
        $this->assertArrayHasKey('access_token', $loginPayload);
        $this->assertArrayHasKey('refresh_token', $loginPayload);

        $userAfter = $users->find()->where(['email' => 'api-user@example.com'])->firstOrFail();
        $this->assertNotNull($userAfter->last_login_at);
        $this->assertSame(
            $deviceLogCountBefore + 1,
            (int)$deviceLogs->find()->where(['user_id' => $userAfter->id])->count(),
            'Mobile login should create a device log row.',
        );

        $this->configRequest([
            'environment' => [],
            'headers' => [
                'Authorization' => 'Bearer ' . $loginPayload['access_token'],
                'Accept' => 'application/json',
            ],
        ]);
        $this->get('/api/v1/auth/me');

        $this->assertSame(200, $this->_response->getStatusCode(), (string)$this->_response->getBody());
        $payload = $this->readJsonResponse();
        $this->assertSame('api-user@example.com', $payload['user']['email']);
    }

    public function testRefreshRotationAndReuseDetection(): void
    {
        $loginPayload = $this->postJson('/api/v1/auth/login', [
            'email' => 'api-user@example.com',
            'password' => 'Passw0rd!23',
        ]);

        $this->assertSame(200, $this->_response->getStatusCode(), (string)$this->_response->getBody());

        $refreshOne = (string)$loginPayload['refresh_token'];
        $rotatedPayload = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshOne,
        ]);

        $this->assertSame(200, $this->_response->getStatusCode(), (string)$this->_response->getBody());
        $refreshTwo = (string)$rotatedPayload['refresh_token'];
        $this->assertNotSame($refreshOne, $refreshTwo);

        $reusePayload = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshOne,
        ]);

        $this->assertResponseCode(401);
        $this->assertSame('AUTH_TOKEN_REUSED', $reusePayload['error']['code']);

        $revokedPayload = $this->postJson('/api/v1/auth/refresh', [
            'refresh_token' => $refreshTwo,
        ]);

        $this->assertResponseCode(401);
        $this->assertSame('AUTH_TOKEN_REVOKED', $revokedPayload['error']['code']);
    }

    public function testLogoutIsIdempotent(): void
    {
        $loginPayload = $this->postJson('/api/v1/auth/login', [
            'email' => 'api-user@example.com',
            'password' => 'Passw0rd!23',
        ]);
        $this->assertResponseOk();

        $headers = [
            'Authorization' => 'Bearer ' . $loginPayload['access_token'],
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $this->postJsonWithHeaders('/api/v1/auth/logout', [
            'refresh_token' => $loginPayload['refresh_token'],
            'all_devices' => false,
        ], $headers);
        $this->assertSame(204, $this->_response->getStatusCode(), (string)$this->_response->getBody());

        $this->postJsonWithHeaders('/api/v1/auth/logout', [
            'refresh_token' => $loginPayload['refresh_token'],
            'all_devices' => false,
        ], $headers);
        $this->assertSame(204, $this->_response->getStatusCode(), (string)$this->_response->getBody());
    }

    public function testBlockedUserCannotLogin(): void
    {
        $users = $this->getTableLocator()->get('Users');
        $user = $users->find()->where(['email' => 'api-user@example.com'])->firstOrFail();
        $user->is_blocked = true;
        $users->saveOrFail($user);

        $payload = $this->postJson('/api/v1/auth/login', [
            'email' => 'api-user@example.com',
            'password' => 'Passw0rd!23',
        ]);

        $this->assertResponseCode(403);
        $this->assertSame('AUTH_USER_BLOCKED', $payload['error']['code']);
    }

    public function testAuthenticatedUserCanUpdateOwnProfile(): void
    {
        $loginPayload = $this->postJson('/api/v1/auth/login', [
            'email' => 'api-user@example.com',
            'password' => 'Passw0rd!23',
        ]);
        $this->assertSame(200, $this->_response->getStatusCode(), (string)$this->_response->getBody());

        $this->_request = [];
        $this->configRequest([
            'headers' => [
                'Authorization' => 'Bearer ' . $loginPayload['access_token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'input' => json_encode([
                'username' => 'mobile_profile_name',
            ]),
        ]);
        $this->patch('/api/v1/auth/me');

        $this->assertSame(200, $this->_response->getStatusCode(), (string)$this->_response->getBody());
        $payload = $this->readJsonResponse();
        $this->assertSame('mobile_profile_name', $payload['user']['username']);
        $this->assertSame('api-user@example.com', $payload['user']['email']);

        $users = $this->getTableLocator()->get('Users');
        $user = $users->find()->where(['email' => 'api-user@example.com'])->firstOrFail();
        $this->assertSame('mobile_profile_name', $user->username);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function postJson(string $url, array $payload): array
    {
        $this->_request = [];
        $this->configRequest([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'input' => json_encode($payload),
        ]);
        $this->post($url);

        return $this->readJsonResponse();
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     * @return array<string, mixed>
     */
    private function postJsonWithHeaders(string $url, array $payload, array $headers): array
    {
        $this->_request = [];
        $this->configRequest([
            'headers' => $headers,
            'input' => json_encode($payload),
        ]);
        $this->post($url);

        return $this->readJsonResponse();
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonResponse(): array
    {
        $decoded = json_decode((string)$this->_response->getBody(), true);

        return is_array($decoded) ? $decoded : [];
    }
}
