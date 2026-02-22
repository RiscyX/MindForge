<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Extracts text content from uploaded documents (DOCX, ODT, PDF, plain text)
 * and builds a context string suitable for AI prompts.
 *
 * Extracted from TestsController::buildUploadedDocumentContextForAi() and
 * related private helpers.
 */
class DocumentExtractorService
{
    /**
     * Build a combined context string from multiple uploaded document files.
     *
     * @param array<mixed> $uploadedFiles The uploaded files array (from request).
     * @return string Combined text context, empty string if no documents.
     * @throws \RuntimeException On validation failures (too many docs, bad type, etc.).
     */
    public function buildContextForAi(array $uploadedFiles): string
    {
        $documents = $uploadedFiles['documents'] ?? null;

        $documentFiles = [];
        if (is_array($documents)) {
            $documentFiles = $documents;
        } elseif ($documents instanceof UploadedFileInterface) {
            $documentFiles = [$documents];
        }

        if (!$documentFiles) {
            return '';
        }

        $maxCount = (int)Configure::read('AI.maxDocuments', 4);
        if (count($documentFiles) > $maxCount) {
            throw new RuntimeException('Too many documents. Max: ' . $maxCount);
        }

        $allowedMimes = (array)Configure::read('AI.allowedDocumentMimeTypes', []);
        $maxBytes = (int)Configure::read('AI.maxDocumentBytes', 8 * 1024 * 1024);
        $maxExtractChars = max(2000, (int)Configure::read('AI.maxDocumentExtractChars', 20000));

        $blocks = [];
        $remainingChars = $maxExtractChars;
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
            if ($size > $maxBytes) {
                throw new RuntimeException('Uploaded document is too large.');
            }

            $mime = $this->detectMime($file);
            if ($mime === '' || !in_array($mime, $allowedMimes, true)) {
                throw new RuntimeException('Unsupported document type: ' . ($mime !== '' ? $mime : 'unknown'));
            }

            $text = $this->extractText($file, $mime);
            if ($text === '' || $remainingChars <= 0) {
                continue;
            }

            if (mb_strlen($text) > $remainingChars) {
                $text = mb_substr($text, 0, $remainingChars);
            }
            $remainingChars -= mb_strlen($text);

            $clientName = trim((string)$file->getClientFilename());
            $sourceName = $clientName !== '' ? $clientName : 'uploaded-document';
            $blocks[] = 'Source: ' . $sourceName . ' (' . $mime . ")\n" . $text;
        }

        return implode("\n\n---\n\n", $blocks);
    }

    /**
     * Detect uploaded file MIME type from bytes.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file
     * @return string
     */
    public function detectMime(UploadedFileInterface $file): string
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

    /**
     * Extract text content from a supported uploaded document.
     *
     * @param \Psr\Http\Message\UploadedFileInterface $file
     * @param string $mime
     * @return string
     */
    public function extractText(UploadedFileInterface $file, string $mime): string
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

        if (!is_string($content) || $content === '') {
            return '';
        }

        $text = '';
        $mime = strtolower(trim($mime));
        if (
            in_array(
                $mime,
                ['text/plain', 'text/markdown', 'text/csv', 'application/json', 'application/xml', 'text/xml'],
                true,
            )
        ) {
            $text = $content;
        } elseif ($mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            $text = $this->extractDocxTextFromBytes($content);
        } elseif ($mime === 'application/vnd.oasis.opendocument.text') {
            $text = $this->extractOdtTextFromBytes($content);
        } elseif ($mime === 'application/pdf') {
            $text = $this->extractPdfTextFromBytes($content);
        }

        return $this->normalizeExtractedText($text);
    }

    /**
     * Extract text from DOCX bytes.
     *
     * @param string $bytes
     * @return string
     */
    private function extractDocxTextFromBytes(string $bytes): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'mf_docx_');
        if ($tmpPath === false) {
            return '';
        }
        file_put_contents($tmpPath, $bytes);

        $zip = new ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            if (is_file($tmpPath)) {
                unlink($tmpPath);
            }

            return '';
        }

        $chunks = [];
        foreach (
            [
                'word/document.xml',
                'word/header1.xml',
                'word/header2.xml',
                'word/footer1.xml',
                'word/footer2.xml',
            ] as $entry
        ) {
            $xml = $zip->getFromName($entry);
            if (!is_string($xml) || $xml === '') {
                continue;
            }
            $chunks[] = trim(html_entity_decode(strip_tags($xml)));
        }
        $zip->close();
        if (is_file($tmpPath)) {
            unlink($tmpPath);
        }

        return implode("\n", array_filter($chunks, static fn($v) => $v !== ''));
    }

    /**
     * Extract text from ODT bytes.
     *
     * @param string $bytes
     * @return string
     */
    private function extractOdtTextFromBytes(string $bytes): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'mf_odt_');
        if ($tmpPath === false) {
            return '';
        }
        file_put_contents($tmpPath, $bytes);

        $zip = new ZipArchive();
        if ($zip->open($tmpPath) !== true) {
            if (is_file($tmpPath)) {
                unlink($tmpPath);
            }

            return '';
        }

        $xml = $zip->getFromName('content.xml');
        $zip->close();
        if (is_file($tmpPath)) {
            unlink($tmpPath);
        }

        if (!is_string($xml) || $xml === '') {
            return '';
        }

        return trim(html_entity_decode(strip_tags($xml)));
    }

    /**
     * Extract text from PDF bytes.
     *
     * @param string $bytes
     * @return string
     */
    private function extractPdfTextFromBytes(string $bytes): string
    {
        if (!function_exists('shell_exec')) {
            return '';
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'mf_pdf_');
        if ($tmpPath === false) {
            return '';
        }
        file_put_contents($tmpPath, $bytes);

        $cmd = 'pdftotext -layout -nopgbrk ' . escapeshellarg($tmpPath) . ' - 2>/dev/null';
        $output = shell_exec($cmd);
        if (is_file($tmpPath)) {
            unlink($tmpPath);
        }

        return is_string($output) ? $output : '';
    }

    /**
     * Normalize extracted text for prompt usage.
     *
     * @param string $text
     * @return string
     */
    public function normalizeExtractedText(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = preg_replace('/\r\n?/', "\n", $text) ?? $text;
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?? $text;
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}
