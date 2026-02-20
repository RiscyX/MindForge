<?php
declare(strict_types=1);

namespace App\Service\Dto;

class AiResponseDto
{
    /**
     * @param bool $success
     * @param array<string, mixed> $data
     * @param string|null $error
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public bool $success,
        public array $data = [],
        public ?string $error = null,
        public array $meta = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     * @return static
     */
    public static function ok(array $data = [], array $meta = []): self
    {
        return new self(true, $data, null, $meta);
    }

    /**
     * @param string $error
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fail(string $error, array $meta = [], array $data = []): self
    {
        return new self(false, $data, $error, $meta);
    }

    /**
     * @return string
     */
    public function content(): string
    {
        return isset($this->data['content']) ? (string)$this->data['content'] : '';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'error' => $this->error,
            'meta' => $this->meta,
        ];
    }
}
