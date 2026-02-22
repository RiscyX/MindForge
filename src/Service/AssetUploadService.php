<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;

/**
 * Handles persisting uploaded images and documents for AI generation requests.
 *
 * Extracted from Api\CreatorAiController to keep the controller thin and
 * allow reuse from other entry points (CLI workers, other API controllers).
 */
class AssetUploadService
{
    /**
     * Persist uploaded images and documents for an AI request.
     *
     * @param int $aiRequestId AI request identifier.
     * @param array<string, mixed> $uploadedFiles Result of `$request->getUploadedFiles()`.
     * @return void
     * @throws \RuntimeException On validation or storage failure.
     */
    public function saveUploadedAssets(int $aiRequestId, array $uploadedFiles): void
    {
        $images = $uploadedFiles['images'] ?? null;
        $documents = $uploadedFiles['documents'] ?? null;

        $imageFiles = [];
        if (is_array($images)) {
            $imageFiles = $images;
        } elseif ($images instanceof UploadedFileInterface) {
            $imageFiles = [$images];
        }

        $documentFiles = [];
        if (is_array($documents)) {
            $documentFiles = $documents;
        } elseif ($documents instanceof UploadedFileInterface) {
            $documentFiles = [$documents];
        }

        if (!$imageFiles && !$documentFiles) {
            return;
        }

        $maxCount = (int)Configure::read('AI.maxImages', 6);
        if (count($imageFiles) > $maxCount) {
            throw new RuntimeException('Too many images. Max: ' . $maxCount);
        }

        $allowed = (array)Configure::read('AI.allowedImageMimeTypes', [
            'image/jpeg',
            'image/png',
            'image/webp',
        ]);
        $maxBytes = (int)Configure::read('AI.maxImageBytes', 6 * 1024 * 1024);
        $imageUploadGuard = new ImageUploadGuard();

        $maxDocumentCount = (int)Configure::read('AI.maxDocuments', 4);
        if (count($documentFiles) > $maxDocumentCount) {
            throw new RuntimeException('Too many documents. Max: ' . $maxDocumentCount);
        }
        $allowedDocumentMimes = (array)Configure::read('AI.allowedDocumentMimeTypes', []);
        $maxDocumentBytes = (int)Configure::read('AI.maxDocumentBytes', 8 * 1024 * 1024);

        $baseDir = rtrim(TMP, DS) . DS . 'ai_assets' . DS . (string)$aiRequestId;
        if (!is_dir($baseDir) && !mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new RuntimeException('Upload directory is not writable.');
        }

        $assetsTable = TableRegistry::getTableLocator()->get('AiRequestAssets');
        $now = FrozenTime::now();

        foreach ($imageFiles as $file) {
            if (!$file instanceof UploadedFileInterface) {
                continue;
            }
            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $mime = $imageUploadGuard->assertImageUpload($file, $allowed, $maxBytes);

            $ext = ImageUploadGuard::extensionForMime($mime);
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            $absPath = $baseDir . DS . $name;
            $file->moveTo($absPath);

            $hash = hash_file('sha256', $absPath);
            $sha256 = $hash !== false ? $hash : null;
            // Store as a workspace-relative path for the worker.
            $relPath = 'tmp/ai_assets/' . $aiRequestId . '/' . $name;
            $size = (int)$file->getSize();

            $asset = $assetsTable->newEntity([
                'ai_request_id' => $aiRequestId,
                'storage_path' => $relPath,
                'mime_type' => $mime,
                'size_bytes' => $size,
                'sha256' => $sha256,
                'created_at' => $now,
            ]);
            if (!$assetsTable->save($asset)) {
                throw new RuntimeException('Failed to store image metadata.');
            }
        }

        foreach ($documentFiles as $file) {
            if (!$file instanceof UploadedFileInterface) {
                continue;
            }
            if ($file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($file->getError() !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Document upload failed.');
            }

            $size = (int)$file->getSize();
            if ($size <= 0) {
                throw new RuntimeException('Uploaded document is empty.');
            }
            if ($size > $maxDocumentBytes) {
                throw new RuntimeException('Uploaded document is too large.');
            }

            $mime = $this->detectUploadedMime($file);
            if ($mime === '' || !in_array($mime, $allowedDocumentMimes, true)) {
                throw new RuntimeException('Unsupported document type: ' . ($mime !== '' ? $mime : 'unknown'));
            }

            $ext = $this->extensionForDocumentMime($mime);
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            $absPath = $baseDir . DS . $name;
            $file->moveTo($absPath);

            $hash = hash_file('sha256', $absPath);
            $sha256 = $hash !== false ? $hash : null;
            $relPath = 'tmp/ai_assets/' . $aiRequestId . '/' . $name;

            $asset = $assetsTable->newEntity([
                'ai_request_id' => $aiRequestId,
                'storage_path' => $relPath,
                'mime_type' => $mime,
                'size_bytes' => $size,
                'sha256' => $sha256,
                'created_at' => $now,
            ]);
            if (!$assetsTable->save($asset)) {
                throw new RuntimeException('Failed to store document metadata.');
            }
        }
    }

    /**
     * Map allowed document MIME types to file extension.
     *
     * @param string $mime MIME type.
     * @return string
     * @throws \RuntimeException If the MIME type is not supported.
     */
    public function extensionForDocumentMime(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.oasis.opendocument.text' => 'odt',
            'text/plain' => 'txt',
            'text/markdown' => 'md',
            'text/csv' => 'csv',
            'application/json' => 'json',
            'application/xml', 'text/xml' => 'xml',
            default => throw new RuntimeException('Unsupported document type: ' . $mime),
        };
    }

    /**
     * Detect uploaded file MIME type from content (falls back to client-reported type).
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file Uploaded file.
     * @return string
     */
    public function detectUploadedMime(UploadedFileInterface $file): string
    {
        try {
            $stream = $file->getStream();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
            $content = $stream->getContents();
            if ($stream->isSeekable()) {
                $stream->rewind();
            }
        } catch (Throwable) {
            $content = '';
        }

        $detected = '';
        if (is_string($content) && $content !== '') {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $tmp = finfo_buffer($finfo, $content);
                finfo_close($finfo);
                if (is_string($tmp)) {
                    $detected = strtolower(trim($tmp));
                }
            }
        }

        if ($detected === '') {
            $detected = strtolower(trim((string)$file->getClientMediaType()));
        }

        return $detected;
    }
}
