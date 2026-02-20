<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\Dto\AiResponseDto;
use Cake\Http\Exception\HttpException;

/**
 * Backward-compatible AI service wrapper.
 *
 * New code should depend on AiGatewayService + AiServiceInterface.
 */
class AiService
{
    protected AiGatewayService $gateway;

    /**
     * @param \App\Service\AiGatewayService|null $gateway
     */
    public function __construct(?AiGatewayService $gateway = null)
    {
        $this->gateway = $gateway ?? new AiGatewayService();
    }

    /**
     * @param string $prompt
     * @param string|null $systemMessage
     * @param float $temperature
     * @param array<string, mixed> $additionalParams
     * @return string
     */
    public function generateContent(
        string $prompt,
        ?string $systemMessage = null,
        float $temperature = 0.7,
        array $additionalParams = [],
    ): string {
        $dto = $this->gateway->generateQuizFromText($prompt, $systemMessage, $temperature, $additionalParams);

        return $this->contentOrThrow($dto);
    }

    /**
     * @param string $prompt
     * @param list<string> $imageDataUrls
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
        $dto = $this->gateway->generateFromOCR(
            $prompt,
            $imageDataUrls,
            $systemMessage,
            $temperature,
            $additionalParams,
        );

        return $this->contentOrThrow($dto);
    }

    /**
     * @param string $prompt
     * @param list<string> $imageDataUrls
     * @param string|null $systemMessage
     * @param float $temperature
     * @param array<string, mixed> $additionalParams
     * @return array<string, mixed>
     */
    public function generateVisionResponse(
        string $prompt,
        array $imageDataUrls = [],
        ?string $systemMessage = null,
        float $temperature = 0.7,
        array $additionalParams = [],
    ): array {
        $dto = $this->gateway->generateFromOCR(
            $prompt,
            $imageDataUrls,
            $systemMessage,
            $temperature,
            $additionalParams,
        );
        if (!$dto->success) {
            throw new HttpException($dto->error ?? 'AI request failed.');
        }

        return $dto->data;
    }

    /**
     * @param \App\Service\Dto\AiResponseDto $dto
     * @return string
     */
    private function contentOrThrow(AiResponseDto $dto): string
    {
        if (!$dto->success) {
            throw new HttpException($dto->error ?? 'AI request failed.');
        }

        return $dto->content();
    }
}
