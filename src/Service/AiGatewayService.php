<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Contracts\AiServiceInterface;
use App\Service\Dto\AiResponseDto;
use App\Service\Providers\OpenAIService;
use Cake\Core\Configure;
use RuntimeException;

class AiGatewayService implements AiServiceInterface
{
    protected AiServiceInterface $provider;

    /**
     * @param \App\Service\Contracts\AiServiceInterface|null $provider
     */
    public function __construct(?AiServiceInterface $provider = null)
    {
        $this->provider = $provider ?? $this->resolveProvider();
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
        return $this->provider->generateQuizFromText($prompt, $systemMessage, $temperature, $additionalParams);
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
        return $this->provider->generateFromOCR(
            $prompt,
            $imageDataUrls,
            $systemMessage,
            $temperature,
            $additionalParams,
        );
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
        return $this->provider->validateOutput($prompt, $systemMessage, $temperature, $additionalParams);
    }

    /**
     * @return \App\Service\Contracts\AiServiceInterface
     */
    protected function resolveProvider(): AiServiceInterface
    {
        $provider = strtolower(trim((string)Configure::read('AI.provider', 'openai')));

        return match ($provider) {
            'openai', 'openai_compatible' => new OpenAIService(),
            default => throw new RuntimeException('Unsupported AI provider: ' . $provider),
        };
    }
}
