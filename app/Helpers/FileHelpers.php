<?php

namespace App\Helpers;

class FileHelpers
{
    /**
     * Validates if a file's Magic Bytes match its claimed extension/type.
     * Allowed types: PDF, JPEG, PNG, Excel (xls, xlsx)
     * 
     * @param string $filePath
     * @return bool
     */
    public static function validateMagicBytes(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        $allowedMimes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return in_array($mimeType, $allowedMimes);
    }
}
