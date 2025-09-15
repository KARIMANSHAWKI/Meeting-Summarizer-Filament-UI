<?php

return [
    'temporary_file_upload' => [
        // Use the public disk so temporary and final uploads share the same filesystem, avoiding size/path mismatches
        'disk' => env('LIVEWIRE_UPLOAD_DISK', 'public'),
        'rules' => null,
        'directory' => env('LIVEWIRE_UPLOAD_DIR', 'livewire-tmp'),
        'middleware' => ['web'],
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'mpg', 'mpeg', 'wmv', 'mp3', 'm4a', 'wma', 'webm', 'mkv', 'aac', 'ogg'
        ],
        'max_upload_time' => env('LIVEWIRE_MAX_UPLOAD_MINUTES', 120),
    ],
];
