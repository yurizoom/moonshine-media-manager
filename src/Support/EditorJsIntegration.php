<?php

declare(strict_types=1);

namespace YuriZoom\MoonShineMediaManager\Support;

use Illuminate\Support\Facades\Storage;
use Sckatik\MoonshineEditorJs\Support\EditorJsToolRegistry;
use Throwable;

final class EditorJsIntegration
{
    public static function register(EditorJsToolRegistry $registry): void
    {
        $registry->registerBlock(
            'mediaImage',
            'moonshine-media-manager::blocks.editorjs-media-image',
            [
                'files' => [
                    'type' => 'array',
                    'required' => false,
                    'data' => [
                        '-' => [
                            'type' => 'array',
                            'data' => [
                                'url' => 'string',
                                'path' => ['type' => 'string', 'required' => false],
                            ],
                        ],
                    ],
                ],
                'file' => [
                    'type' => 'array',
                    'required' => false,
                    'data' => [
                        'url' => 'string',
                        'path' => ['type' => 'string', 'required' => false],
                    ],
                ],
                'caption' => [
                    'type' => 'string',
                    'allowedTags' => 'i,b,a[href]',
                    'required' => false,
                ],
            ],
        );

        $registry->registerToolSettings('mediaImage', [
            'activated' => true,
            'baseUrl' => self::baseUrl(),
            'caption' => (bool) config('moonshine.media_manager.editorjs.caption', false),
            'title' => __('moonshine-media-manager::media-manager.editorjs_block_title'),
            'button' => __('moonshine-media-manager::media-manager.editorjs_block_button'),
            'caption_placeholder' => __('moonshine-media-manager::media-manager.editorjs_block_caption'),
            'remove' => __('moonshine-media-manager::media-manager.remove'),
            'not_exists' => __('moonshine-media-manager::media-manager.error.file_not_exists', ['path' => '']),
        ]);
    }

    private static function baseUrl(): string
    {
        $disk = config('moonshine.media_manager.disk', 'public');

        try {
            return rtrim(Storage::disk($disk)->url(''), '/');
        } catch (Throwable) {
            return rtrim(url('/storage'), '/');
        }
    }
}
