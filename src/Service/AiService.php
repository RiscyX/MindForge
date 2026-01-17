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

    /**
     * Construct
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = Configure::read('AI.apiKey');
        $this->baseUrl = Configure::read('AI.baseUrl');
        $this->model = Configure::read('AI.defaultModel');

        if (empty($this->apiKey)) {
            Log::warning('AI API Key is missing in configuration.');
        }
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

        $payload = array_merge([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $temperature,
        ], $additionalParams);

        try {
            $response = $this->client->post("{$this->baseUrl}/chat/completions", json_encode($payload), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'type' => 'json', // This ensures the request body is treated as JSON if passed as array, but we encoded it manually above.
                                 // Actually for Cake HTTP Client, if we pass array as 2nd arg and type=>json, it encodes it.
                                 // Let's refine the call below.
            ]);

            // Re-doing the call cleanly with CakePHP Http Client features
            $response = $this->client->post(
                "{$this->baseUrl}/chat/completions",
                json_encode($payload),
                [
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
                return $json['choices'][0]['message']['content'];
            }

            Log::error('AI Unexpected Response Format: ' . $response->getStringBody());
            throw new HttpException('Invalid response format from AI provider.');
        } catch (Exception $e) {
            Log::error('AI Service Exception: ' . $e->getMessage());
            throw $e;
        }
    }
}
