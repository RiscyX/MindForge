<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Api;

use Cake\I18n\FrozenTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class AttemptsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.UserTokens',
        'app.ApiTokens',
        'app.ActivityLogs',
        'app.DeviceLogs',
        'app.Tests',
        'app.Categories',
        'app.Difficulties',
        'app.Questions',
        'app.Answers',
        'app.QuestionTranslations',
        'app.AnswerTranslations',
        'app.Languages',
        'app.TestAttempts',
        'app.TestAttemptAnswers',
        'app.OfflineSyncAttempts',
    ];

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

    public function testOfflineSyncIsIdempotentByClientAttemptId(): void
    {
        $loginPayload = $this->postJson('/api/v1/auth/login', [
            'email' => 'api-user@example.com',
            'password' => 'Passw0rd!23',
        ]);
        $this->assertResponseOk();

        $attemptsTable = $this->getTableLocator()->get('TestAttempts');
        $beforeCount = (int)$attemptsTable->find()->count();

        $headers = [
            'Authorization' => 'Bearer ' . $loginPayload['access_token'],
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        $payload = [
            'items' => [
                [
                    'client_attempt_id' => 'mobile-offline-1',
                    'test_id' => 1,
                    'language_id' => 1,
                    'answers' => [
                        1 => 1,
                    ],
                ],
            ],
        ];

        $first = $this->postJsonWithHeaders('/api/v1/me/attempts/offline-sync', $payload, $headers);
        $this->assertSame(200, $this->_response->getStatusCode(), (string)$this->_response->getBody());
        $this->assertSame('synced', $first['results'][0]['status'] ?? null);
        $this->assertArrayHasKey('attempt_id', $first['results'][0]);

        $afterFirstCount = (int)$attemptsTable->find()->count();
        $this->assertSame($beforeCount + 1, $afterFirstCount);

        $second = $this->postJsonWithHeaders('/api/v1/me/attempts/offline-sync', $payload, $headers);
        $this->assertSame(200, $this->_response->getStatusCode(), (string)$this->_response->getBody());
        $this->assertSame('duplicate', $second['results'][0]['status'] ?? null);
        $this->assertSame($first['results'][0]['attempt_id'] ?? null, $second['results'][0]['attempt_id'] ?? null);

        $afterSecondCount = (int)$attemptsTable->find()->count();
        $this->assertSame($afterFirstCount, $afterSecondCount);
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
