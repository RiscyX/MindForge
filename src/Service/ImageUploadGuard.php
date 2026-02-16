<?php
declare(strict_types=1);

namespace App\Service;

use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;

class ImageUploadGuard
{
    /**
     * Validate an uploaded file as a real image and return detected MIME type.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded file.
     * @param list<string> $allowedMimeTypes Allowed image MIME types.
     * @param int $maxBytes Maximum allowed file size in bytes.
     * @return string
     */
    public function assertImageUpload(UploadedFileInterface $file, array $allowedMimeTypes, int $maxBytes): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Upload failed.');
        }

        $size = (int)$file->getSize();
        if ($size <= 0) {
            throw new RuntimeException('Uploaded image is empty.');
        }
        if ($maxBytes > 0 && $size > $maxBytes) {
            throw new RuntimeException('Image is too large.');
        }

        $content = $this->readContents($file);
        if ($content === '') {
            throw new RuntimeException('Uploaded image is empty.');
        }

        set_error_handler(static function (): bool {
            return true;
        });
        $imageInfo = getimagesizefromstring($content);
        restore_error_handler();
        if (!is_array($imageInfo)) {
            throw new RuntimeException('Uploaded file is not a valid image.');
        }

        $detectedMime = strtolower((string)($imageInfo['mime'] ?? ''));
        if ($detectedMime === '') {
            throw new RuntimeException('Could not detect image type.');
        }

        $allowed = array_values(array_unique(array_map(
            static fn(string $mime): string => strtolower(trim($mime)),
            $allowedMimeTypes,
        )));
        if (!in_array($detectedMime, $allowed, true)) {
            throw new RuntimeException('Unsupported image type: ' . $detectedMime);
        }

        return $detectedMime;
    }

    /**
     * @param string $mime Image MIME type.
     * @return string
     */
    public static function extensionForMime(string $mime): string
    {
        return match (strtolower($mime)) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw new RuntimeException('Unsupported image type: ' . $mime),
        };
    }

    /**
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded file.
     * @return string
     */
    private function readContents(UploadedFileInterface $file): string
    {
        try {
            $stream = $file->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            $contents = $stream->getContents();

            if ($stream->isSeekable()) {
                $stream->rewind();
            }

            return $contents;
        } catch (Throwable) {
            throw new RuntimeException('Could not read uploaded image.');
        }
    }
}
