<?php
declare(strict_types=1);

namespace App\Service\Providers;

use App\Service\Contracts\AiServiceInterface;
use App\Service\Dto\AiResponseDto;
use Cake\Core\Configure;
use Cake\Http\Client;
use Throwable;

class OpenAIService implements AiServiceInterface
{
    protected Client $client;
    protected string $apiKey;
    protected string $baseUrl;
    protected string $defaultModel;
    protected ?string $visionModel;
    protected int $timeoutSeconds;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = (string)Configure::read('AI.apiKey', '');
        $this->baseUrl = rtrim((string)Configure::read('AI.baseUrl', 'https://api.openai.com/v1'), '/');
        $this->defaultModel = (string)Configure::read('AI.defaultModel', 'gpt-4');
        $visionModel = (string)Configure::read('AI.visionModel', '');
        $this->visionModel = $visionModel !== '' ? $visionModel : null;
        $this->timeoutSeconds = max(1, (int)Configure::read('AI.timeoutSeconds', 30));
    }

    /**
     * @param string $prompt
     * @param string|null $systemMessage
     * @param float $temperature
     * @param array<string, mixed> $additionalParams
     * @return \App\Service\Dto\AiResponseDto
     */
    public function generateQuizFromText(
        string $prompt,
        ?string $systemMessage = null,
        float $temperature = 0.7,
        array $additionalParams = [],
    ): AiResponseDto {
        $messages = [];
        if ($systemMessage) {
            $messages[] = ['role' => 'system', 'content' => $systemMessage];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $this->requestChat($messages, $this->defaultModel, $temperature, $additionalParams);
    }

    /**
     * @param string $prompt
     * @param list<string> $imageDataUrls
     * @param string|null $systemMessage
     * @param float $temperature
     * @param array<string, mixed> $additionalParams
     * @return \App\Service\Dto\AiResponseDto
     */
    public function generateFromOCR(
        string $prompt,
        array $imageDataUrls = [],
        ?string $systemMessage = null,
        float $temperature = 0.7,
        array $additionalParams = [],
    ): AiResponseDto {
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

        $model = $this->visionModel ?: $this->defaultModel;

        return $this->requestChat($messages, $model, $temperature, $additionalParams);
    }

    /**
     * @param string $prompt
     * @param string|null $systemMessage
     * @param float $temperature
     * @param array<string, mixed> $additionalParams
     * @return \App\Service\Dto\AiResponseDto
     */
    public function validateOutput(
        string $prompt,
        ?string $systemMessage = null,
        float $temperature = 0.0,
        array $additionalParams = [],
    ): AiResponseDto {
        return $this->generateQuizFromText($prompt, $systemMessage, $temperature, $additionalParams);
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @param string $model
     * @param float $temperature
     * @param array<string, mixed> $additionalParams
     * @return \App\Service\Dto\AiResponseDto
     */
    private function requestChat(
        array $messages,
        string $model,
        float $temperature,
        array $additionalParams,
    ): AiResponseDto {
        if ($this->apiKey === '') {
            return AiResponseDto::fail('AI API key is missing.');
        }

        $startedAt = microtime(true);
        $payload = array_merge([
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
        ], $additionalParams);

        try {
            $response = $this->client->post(
                $this->baseUrl . '/chat/completions',
                $payload,
                [
                    'type' => 'json',
                    'timeout' => $this->timeoutSeconds,
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                    ],
                ],
            );
        } catch (Throwable $e) {
            return AiResponseDto::fail($e->getMessage(), [
                'provider' => 'openai',
                'duration_ms' => (int)max(0, round((microtime(true) - $startedAt) * 1000)),
            ]);
        }

        $durationMs = (int)max(0, round((microtime(true) - $startedAt) * 1000));
        if (!$response->isOk()) {
            return AiResponseDto::fail('AI request failed: ' . $response->getStatusCode(), [
                'provider' => 'openai',
                'duration_ms' => $durationMs,
                'status_code' => $response->getStatusCode(),
                'raw' => $response->getStringBody(),
            ]);
        }

        $json = $response->getJson();
        if (!is_array($json) || !isset($json['choices'][0]['message']['content'])) {
            return AiResponseDto::fail('Invalid response format from AI provider.', [
                'provider' => 'openai',
                'duration_ms' => $durationMs,
                'raw' => $response->getStringBody(),
            ]);
        }

        $usage = isset($json['usage']) && is_array($json['usage']) ? $json['usage'] : [];
        $promptTokens = isset($usage['prompt_tokens']) && is_numeric($usage['prompt_tokens'])
            ? (int)$usage['prompt_tokens']
            : null;
        $completionTokens = isset($usage['completion_tokens']) && is_numeric($usage['completion_tokens'])
            ? (int)$usage['completion_tokens']
            : null;
        $totalTokens = isset($usage['total_tokens']) && is_numeric($usage['total_tokens'])
            ? (int)$usage['total_tokens']
            : null;

        $costUsd = null;
        if (isset($usage['total_cost']) && is_numeric($usage['total_cost'])) {
            $costUsd = (float)$usage['total_cost'];
        } elseif (isset($usage['cost']) && is_numeric($usage['cost'])) {
            $costUsd = (float)$usage['cost'];
        } elseif (isset($json['cost']) && is_numeric($json['cost'])) {
            $costUsd = (float)$json['cost'];
        }

        return AiResponseDto::ok([
            'content' => (string)$json['choices'][0]['message']['content'],
            'model' => isset($json['model']) ? (string)$json['model'] : $model,
            'provider' => 'openai',
            'usage' => $usage,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'cost_usd' => $costUsd,
            'response' => $json,
        ], [
            'provider' => 'openai',
            'duration_ms' => $durationMs,
            'status_code' => $response->getStatusCode(),
        ]);
    }
}
