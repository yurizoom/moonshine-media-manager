<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use Illuminate\Http\UploadedFile;
use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;

class MediaValidator
{
    /**
     * Mapping of allowed file extensions to their expected MIME types.
     * Used to verify that the actual file content matches the stored extension.
     *
     * @var array<string, list<string>>
     */
    private const EXTENSION_MIME_MAP = [
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'gif' => ['image/gif'],
        'webp' => ['image/webp'],
        'avif' => ['image/avif'],
        'bmp' => ['image/bmp'],
        'svg' => ['image/svg+xml'],
        'ico' => ['image/x-icon', 'image/vnd.microsoft.icon'],
        'heic' => ['image/heic', 'image/heif'],
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'rar' => ['application/x-rar-compressed', 'application/vnd.rar'],
        '7z' => ['application/x-7z-compressed'],
        'tar' => ['application/x-tar'],
        'gz' => ['application/gzip', 'application/x-gzip'],
        'txt' => ['text/plain'],
        'md' => ['text/markdown', 'text/x-markdown'],
        'csv' => ['text/csv', 'text/plain', 'application/csv'],
        'json' => ['application/json', 'text/json'],
        'yaml' => ['application/yaml', 'text/yaml', 'application/x-yaml'],
        'yml' => ['application/yaml', 'text/yaml', 'application/x-yaml'],
        'mp3' => ['audio/mpeg'],
        'wav' => ['audio/wav'],
        'ogg' => ['audio/ogg', 'application/ogg'],
        'm4a' => ['audio/mp4', 'audio/x-m4a'],
        'aac' => ['audio/aac'],
        'opus' => ['audio/opus'],
        'flac' => ['audio/flac'],
        'mp4' => ['video/mp4'],
        'avi' => ['video/x-msvideo'],
        'mov' => ['video/quicktime'],
        'mkv' => ['video/x-matroska'],
        'webm' => ['video/webm'],
    ];

    /**
     * @param  list<string>  $allowed  allowed extensions (normalized: trimmed, lowercased)
     */
    public function __construct(
        private readonly array $allowed = [],
        private readonly int $maxFileSize = 10 * 1024 * 1024,
    ) {
    }

    /**
     * Normalize a raw extension whitelist from config into a comparable list.
     *
     * @param  list<string>  $allowed
     * @return self
     */
    public static function fromConfig(array $allowed, int $maxFileSize): self
    {
        return new self(
            array_values(array_filter(array_map(
                static fn ($ext): string => strtolower(trim((string) $ext)),
                $allowed,
            ))),
            $maxFileSize,
        );
    }

    /**
     * Validate an uploaded file against the strict extension policy.
     *
     * The FINAL stored extension is authoritative: it must be explicitly
     * whitelisted, the content-sniffed extension must not contradict it, and
     * the MIME type must match the expected mapping. An empty whitelist
     * denies everything (deny-by-default).
     *
     * @param  UploadedFile  $file  the uploaded file
     * @param  string|null  $storedExtension  extension of the sanitized name that will be used for storage
     *
     * @throws MediaManagerException when any check fails
     */
    public function validateUploadedFile(UploadedFile $file, ?string $storedExtension = null): void
    {
        $extension = strtolower(trim((string) ($storedExtension ?? pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION))));

        if ($this->allowed === []) {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.allowed_ext_not_configured')
            );
        }

        if ($extension === '' || ! in_array($extension, $this->allowed, true)) {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.file_extension_not_allowed', ['ext' => $extension])
            );
        }

        $guessedExtension = strtolower((string) ($file->guessExtension() ?: ''));

        // Content sniffed as a different known type than the stored extension
        // (e.g. PHP payload named .jpg) — reject the contradiction.
        if ($guessedExtension !== '' && $guessedExtension !== $extension && isset(self::EXTENSION_MIME_MAP[$guessedExtension])) {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.mime_type_mismatch', [
                    'ext' => $extension,
                    'mime' => $file->getMimeType() ?? 'unknown',
                ])
            );
        }

        $mimeType = (string) ($file->getMimeType() ?: '');

        if (isset(self::EXTENSION_MIME_MAP[$extension])) {
            if (! in_array($mimeType, self::EXTENSION_MIME_MAP[$extension], true)) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.mime_type_mismatch', [
                        'ext' => $extension,
                        'mime' => $mimeType,
                    ])
                );
            }
        } elseif ($guessedExtension !== '' && $guessedExtension !== $extension) {
            // Extension is whitelisted but has no MIME mapping: still require
            // the sniffed type to agree instead of skipping validation.
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.mime_type_mismatch', [
                    'ext' => $extension,
                    'mime' => $mimeType,
                ])
            );
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new MediaManagerException(
                __('moonshine-media-manager::media-manager.error.file_too_large', [
                    'max' => MediaFormatter::formatBytes($this->maxFileSize),
                ])
            );
        }
    }
}
