<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use Illuminate\Http\UploadedFile;

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
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'ppt' => ['application/vnd.ms-powerpoint'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
        'rar' => ['application/x-rar-compressed'],
        'tar' => ['application/x-tar'],
        'gz' => ['application/gzip', 'application/x-gzip'],
        'txt' => ['text/plain'],
        'csv' => ['text/csv', 'text/plain'],
        'mp3' => ['audio/mpeg'],
        'wav' => ['audio/wav'],
        'ogg' => ['audio/ogg'],
        'mp4' => ['video/mp4'],
        'avi' => ['video/x-msvideo'],
        'mov' => ['video/quicktime'],
        'mkv' => ['video/x-matroska'],
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
                throw new \RuntimeException(
                    __('moonshine-media-manager::media-manager.error.file_extension_not_allowed', ['ext' => $file->getClientOriginalExtension()])
                );
            }
        }

        $mimeType = $file->getMimeType();

        if ($realExtension && isset(self::EXTENSION_MIME_MAP[$realExtension])) {
            $expectedMimes = self::EXTENSION_MIME_MAP[$realExtension];

            if (! in_array($mimeType, $expectedMimes, true)) {
                throw new \RuntimeException(
                    __('moonshine-media-manager::media-manager.error.mime_type_mismatch', [
                        'ext' => $realExtension,
                        'mime' => $mimeType,
                    ])
                );
            }
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new \RuntimeException(
                __('moonshine-media-manager::media-manager.error.file_too_large', [
                    'max' => MediaFormatter::formatBytes($this->maxFileSize),
                ])
            );
        }
    }
}