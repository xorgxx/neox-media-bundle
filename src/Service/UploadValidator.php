<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Service;

use Neox\MediaBundle\Enum\MediaRiskLevel;
use Neox\MediaBundle\Enum\MediaType;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadValidator
{
    private const DANGEROUS_EXTENSIONS = [
        'php', 'phtml', 'php3', 'php4', 'php5', 'php7', 'phps',
        'phar', 'exe', 'bat', 'cmd', 'sh', 'cgi', 'pl', 'py',
        'rb', 'jsp', 'asp', 'aspx', 'htaccess', 'htpasswd',
    ];

    /**
     * @param array<string, int> $maxSizes          e.g. ['image' => 4194304, 'video' => 209715200, ...]
     * @param array<int, string> $allowedMime
     * @param array<int, string> $allowedExtensions
     * @param array<int, string> $sandboxMime
     * @param array<int, string> $sandboxExtensions
     */
    public function __construct(
        private readonly array $maxSizes,
        private readonly array $allowedMime,
        private readonly array $allowedExtensions,
        private readonly bool $sandboxEnabled = true,
        private readonly array $sandboxMime = [],
        private readonly array $sandboxExtensions = [],
    ) {
    }

    public function validate(UploadedFile $file): ?string
    {
        $result = $this->validateWithRisk($file);

        return $result->isBlocked() ? $result->message : null;
    }

    public function validateWithRisk(UploadedFile $file): UploadValidationResult
    {
        $error = $this->validateSize($file);
        if ($error !== null) {
            return new UploadValidationResult(MediaRiskLevel::BLOCKED, $error);
        }

        $error = $this->validateExtension($file);
        if ($error !== null) {
            return new UploadValidationResult(MediaRiskLevel::BLOCKED, $error);
        }

        $error = $this->validateMime($file);
        if ($error !== null) {
            return new UploadValidationResult(MediaRiskLevel::BLOCKED, $error);
        }

        $sandboxReason = $this->detectSandboxReason($file);
        if ($sandboxReason !== null) {
            return new UploadValidationResult(MediaRiskLevel::SUSPICIOUS, $sandboxReason);
        }

        return new UploadValidationResult(MediaRiskLevel::SAFE);
    }

    public function detectRealMime(UploadedFile $file): string|false
    {
        $path = $file->getRealPath();
        if ($path === false) {
            return false;
        }

        return mime_content_type($path);
    }

    public function detectMediaType(UploadedFile $file): MediaType
    {
        $realMime = $this->detectRealMime($file) ?: (string) $file->getMimeType();

        return match (true) {
            str_starts_with($realMime, 'image/') => MediaType::IMAGE,
            str_starts_with($realMime, 'video/') => MediaType::VIDEO,
            str_starts_with($realMime, 'audio/') => MediaType::AUDIO,
            str_starts_with($realMime, 'application/zip') => MediaType::ARCHIVE,
            str_starts_with($realMime, 'application/x-zip') => MediaType::ARCHIVE,
            default => MediaType::DOCUMENT,
        };
    }

    private function validateSize(UploadedFile $file): ?string
    {
        $mediaType = $this->detectMediaType($file);
        $group = $mediaType->value;
        $maxSize = $this->maxSizes[$group] ?? $this->maxSizes['default'] ?? 10485760;

        if ($file->getSize() > $maxSize) {
            return sprintf('File "%s" (%d bytes) exceeds maximum size of %d bytes for type "%s".', $file->getClientOriginalName(), $file->getSize(), $maxSize, $group);
        }

        return null;
    }

    private function validateExtension(UploadedFile $file): ?string
    {
        $extension = $this->extension($file);

        if ($extension === '') {
            return 'File has no extension.';
        }

        if (in_array($extension, self::DANGEROUS_EXTENSIONS, true)) {
            return sprintf('File extension "%s" is forbidden.', $extension);
        }

        if (!in_array($extension, $this->allowedExtensions, true)) {
            return sprintf('File extension "%s" is not allowed.', $extension);
        }

        return null;
    }

    private function validateMime(UploadedFile $file): ?string
    {
        $realMime = $this->detectRealMime($file);
        if ($realMime === false) {
            return 'Unable to detect MIME type.';
        }

        if (!in_array($realMime, $this->allowedMime, true)) {
            return sprintf('MIME type "%s" is not allowed.', $realMime);
        }

        return null;
    }

    private function detectSandboxReason(UploadedFile $file): ?string
    {
        if (!$this->sandboxEnabled) {
            return null;
        }

        $extension = $this->extension($file);
        if (in_array($extension, $this->sandboxExtensions, true)) {
            return sprintf('File extension "%s" requires sandbox review.', $extension);
        }

        $realMime = $this->detectRealMime($file);
        if ($realMime !== false && in_array($realMime, $this->sandboxMime, true)) {
            return sprintf('MIME type "%s" requires sandbox review.', $realMime);
        }

        return null;
    }

    private function extension(UploadedFile $file): string
    {
        return strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?? '');
    }
}
