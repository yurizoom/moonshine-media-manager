<?php

return [
    'disk' => config('filesystems.default', 'public'),
    'allowed_ext' => 'jpg,jpeg,png,gif,webp,avif,svg,bmp,ico,heic,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,7z,tar,gz,txt,md,csv,json,yaml,yml,mp3,wav,ogg,m4a,aac,flac,mp4,avi,mov,mkv,webm',
    'max_file_size' => env('MOONSHINE_MEDIA_MANAGER_MAX_FILE_SIZE', 10 * 1024 * 1024),
    'rename_duplicates' => env('MOONSHINE_MEDIA_MANAGER_RENAME_DUPLICATES', true),
    'auto_menu' => env('MOONSHINE_MEDIA_MANAGER_AUTO_MENU', true),
    // Set to a Gate ability string (e.g. 'manage-media') to require authorization.
    // Define the Gate in AuthServiceProvider: Gate::define('manage-media', fn($user) => ...).
    // null = no extra check (any authenticated MoonShine user has full access).
    'ability' => env('MOONSHINE_MEDIA_MANAGER_ABILITY'),
    'default_view' => 'table',
];
