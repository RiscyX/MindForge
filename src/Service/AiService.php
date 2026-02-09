<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Http\Client;
use Cake\Http\Exception\HttpException;
use Cake\Log\Log;
use Exception;

class AiService
{
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
        $this->client = new Client();
        $this->apiKey = Configure::read('AI.apiKey');
        $this->baseUrl = Configure::read('AI.baseUrl');
        $this->model = Configure::read('AI.defaultModel');
        $this->visionModel = Configure::read('AI.visionModel');

        if (empty($this->apiKey)) {
            Log::warning('AI API Key is missing in configuration.');
        }
    }

    /**
     * Low-level chat completion helper. Returns assistant message content.
     *
     * @param array<int, array<string, mixed>> $messages
     * @param string|null $model
     * @param float $temperature
     * @param array<string, mixed> $additionalParams
     * @return string
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

        if (!$response->isOk()) {
            Log::error('AI API Error: ' . $response->getStringBody());
            throw new HttpException('AI Request Failed: ' . $response->getStatusCode());
        }

        $json = $response->getJson();
        /** @var array $json */
        if (isset($json['choices'][0]['message']['content'])) {
            return (string)$json['choices'][0]['message']['content'];
        }

        Log::error('AI Unexpected Response Format: ' . $response->getStringBody());
        throw new HttpException('Invalid response format from AI provider.');
    }

    /**
     * Sends a prompt to the AI provider and returns the response content.
     *
     * @param string $prompt The user prompt.
     * @param string|null $systemMessage Optional system message to set context.
     * @param float $temperature The temperature for response randomness (default 0.7).
     * @param array $additionalParams Any other parameters to pass to the API.
     * @return string The content of the AI's response.
     * @throws \Cake\Http\Exception\HttpException
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

        try {
            return $this->generateChatContent($messages, null, $temperature, $additionalParams);
        } catch (Exception $e) {
            Log::error('AI Service Exception: ' . $e->getMessage());
            throw $e;
        }
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
}
