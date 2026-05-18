<?php

return [
    'disk' => config('filesystem.default', 'public'),
    'allowed_ext' => 'jpg,jpeg,png,gif,webp,avif,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,rar,txt,mp3,mp4,wav,avi,mov',
    'max_file_size' => env('MOONSHINE_MEDIA_MANAGER_MAX_FILE_SIZE', 10 * 1024 * 1024),
    'default_view' => 'table',
];
