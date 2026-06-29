<?php

return [
    'disk' => config('filesystems.default', 'public'),
    'allowed_ext' => 'jpg,jpeg,png,gif,webp,avif,svg,bmp,ico,heic,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,7z,tar,gz,txt,md,csv,json,yaml,yml,mp3,wav,ogg,m4a,aac,flac,mp4,avi,mov,mkv,webm',
    'max_file_size' => env('MOONSHINE_MEDIA_MANAGER_MAX_FILE_SIZE', 10 * 1024 * 1024),
    // When true, uploaded files with a name that already exists are renamed
    // (e.g. "image.jpg" -> "image-1.jpg") instead of being overwritten.
    'rename_duplicates' => env('MOONSHINE_MEDIA_MANAGER_RENAME_DUPLICATES', true),
    // When true, a Media Manager item is automatically added to the MoonShine menu.
    'auto_menu' => env('MOONSHINE_MEDIA_MANAGER_AUTO_MENU', true),
    'default_view' => 'table',
];
