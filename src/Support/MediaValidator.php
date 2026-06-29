<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use Illuminate\Http\UploadedFile;
use YuriZoom\MoonShineMediaManager\Exceptions\MediaManagerException;

class MediaValidator
{
    /**
     * Mapping of allowed file extensions to their expected MIME types.
     * Used to verify that the actual file content matches the claimed extension.
     *
     * @var array<string, string[]>
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

    public function __construct(
        private readonly array $allowed = [],
        private readonly int $maxFileSize = 10 * 1024 * 1024,
    ) {
    }

    public function validateUploadedFile(UploadedFile $file): void
    {
        $realExtension = strtolower($file->guessExtension() ?: '');
        $clientExtension = strtolower($file->getClientOriginalExtension());

        if ($this->allowed) {
            if (! in_array($realExtension, $this->allowed) && ! in_array($clientExtension, $this->allowed)) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.file_extension_not_allowed', ['ext' => $file->getClientOriginalExtension()])
                );
            }
        }

        $mimeType = $file->getMimeType();

        if ($realExtension && isset(self::EXTENSION_MIME_MAP[$realExtension])) {
            $expectedMimes = self::EXTENSION_MIME_MAP[$realExtension];

            if (! in_array($mimeType, $expectedMimes, true)) {
                throw new MediaManagerException(
                    __('moonshine-media-manager::media-manager.error.mime_type_mismatch', [
                        'ext' => $realExtension,
                        'mime' => $mimeType,
                    ])
                );
            }
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