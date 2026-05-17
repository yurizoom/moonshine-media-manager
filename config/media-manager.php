<?php

return [
    'auto_menu' => true,
    'disk' => config('filesystems.default', 'public'),
    'allowed_ext' => 'jpg,jpeg,png,pdf,doc,docx,zip',
    'max_file_size' => env('MOONSHINE_MEDIA_MANAGER_MAX_FILE_SIZE', 50 * 1024 * 1024),
    'default_view' => 'table',

    /*
    |-------------------------------------------------------------
    | Authorization gate name. Set to null to disable the check.
    | Register the gate in your AuthServiceProvider:
    |
    |   Gate::define('media-manager', fn ($user) => $user->isSuperUser());
    |-------------------------------------------------------------
    */
    'gate' => env('MOONSHINE_MEDIA_MANAGER_GATE'),
];
