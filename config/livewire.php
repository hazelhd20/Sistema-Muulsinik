<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Livewire handles file uploads by storing uploads in a temporary directory
    | before the file is validated and stored permanently.
    |
    | Cuando se usa un disco S3 como almacenamiento predeterminado de la app,
    | Livewire arroja la excepción "S3DoesntSupportMultipleFileUploads" si el
    | input tiene el atributo [multiple]. Para evitar esto y permitir subidas
    | múltiples fluidas, forzamos el almacenamiento temporal al disco 'local'.
    | Una vez validados, el repositorio los moverá al bucket S3 definitivo.
    |
    */

    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TMP_DISK', 'local'),
        'rules' => 'file|max:20480',
        'directory' => 'livewire-tmp',
        'middleware' => null,
        'preview_mimes' => ['png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv', 'mp3', 'm4a', 'jpg', 'jpeg', 'mpga', 'webp', 'wma'],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

];
