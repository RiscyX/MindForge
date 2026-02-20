<?php
declare(strict_types=1);

namespace App\Service\Contracts;

use App\Service\Dto\AiResponseDto;

interface AiServiceInterface
{
    /**
     * Generate quiz content from text input.
     *
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
    ): AiResponseDto;

    /**
     * Generate output from OCR/vision context.
     *
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
    ): AiResponseDto;

    /**
     * Validate structured output.
     *
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
    ): AiResponseDto;
}
