<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * StorageResolver
 *
 * Wrapper centralizado (S.O.L.I.D.) para la resolución de archivos en entornos multi-disco
 * (Local, Public, S3/Tigris). Implementa degradación elegante (try/catch) para evitar errores
 * cuando un driver remoto no esté accesible o no cuente con librerías instaladas en local.
 */
class StorageResolver
{
    /**
     * Resuelve de forma segura el disco y la instancia de Filesystem donde existe el archivo.
     *
     * @param string|null $path Ruta relativa del archivo
     * @param string|null $preferredDisk Disco solicitado o preferido como primera opción
     * @return array{disk: string, filesystem: Filesystem}|null
     */
    public static function resolve(?string $path, ?string $preferredDisk = null): ?array
    {
        if (! $path || str_contains($path, '..')) {
            return null;
        }

        $cleanPath = ltrim(str_replace(['storage/app/public/', 'storage/app/private/', 'storage/', 'public/', 'app/private/'], '', $path), '/');
        $candidates = array_unique(array_filter([
            $path,
            $cleanPath,
            'exports/' . basename($path),
            'public/exports/' . basename($path),
            basename($path)
        ]));

        $disks = array_filter([
            $preferredDisk,
            config('filesystems.default'),
            'public',
            'local',
            's3',
        ]);

        $disks = array_unique($disks);

        foreach ($disks as $diskName) {
            try {
                $fs = Storage::disk($diskName);
                foreach ($candidates as $candidate) {
                    if ($fs->exists($candidate)) {
                        return ['disk' => $diskName, 'filesystem' => $fs, 'path' => $candidate];
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Verifica si el archivo existe en alguno de los discos disponibles sin lanzar errores.
     *
     * @param string|null $path
     * @return bool
     */
    public static function exists(?string $path): bool
    {
        return self::resolve($path) !== null;
    }

    /**
     * Elimina el archivo de forma segura desde el disco en donde se encuentre.
     *
     * @param string|null $path
     * @return bool
     */
    public static function delete(?string $path): bool
    {
        $resolved = self::resolve($path);

        if (! $resolved) {
            return false;
        }

        try {
            return $resolved['filesystem']->delete($path);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Retorna el contenido en bruto (raw content) del archivo desde cualquier disco donde se encuentre.
     *
     * @param string|null $path
     * @return string|null
     */
    public static function getContent(?string $path): ?string
    {
        $resolved = self::resolve($path);

        if (! $resolved) {
            return null;
        }

        try {
            $actualPath = $resolved['path'] ?? $path;
            return $resolved['filesystem']->get($actualPath);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Retorna el contenido del archivo en formato Data URI (Base64), ideal para incrustar
     * imágenes o logotipos en documentos PDF generados con DomPDF o vistas HTML.
     *
     * @param string|null $path
     * @return string|null
     */
    public static function getAsDataUri(?string $path): ?string
    {
        $resolved = self::resolve($path);

        if (! $resolved) {
            return null;
        }

        try {
            $actualPath = $resolved['path'] ?? $path;
            $fs = $resolved['filesystem'];
            $mime = $fs->mimeType($actualPath) ?: 'application/octet-stream';
            $content = $fs->get($actualPath);

            return 'data:'.$mime.';base64,'.base64_encode($content);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Retorna una respuesta HTTP de streaming inline o descarga para previsualización en navegador.
     *
     * @param string|null $path
     * @param string|null $preferredDisk
     * @param string $disposition 'inline' o 'attachment'
     */
    public static function streamResponse(?string $path, ?string $preferredDisk = null, string $disposition = 'inline'): ?Response
    {
        $resolved = self::resolve($path, $preferredDisk);

        if (! $resolved) {
            return null;
        }

        try {
            $actualPath = $resolved['path'] ?? $path;
            $disk = $resolved['disk'];
            $fs = $resolved['filesystem'];
            $mime = $fs->mimeType($actualPath) ?: 'application/octet-stream';

            if (in_array($disk, ['local', 'public'])) {
                return response()->file($fs->path($actualPath), [
                    'Content-Type' => $mime,
                    'Content-Disposition' => $disposition . '; filename="'.basename($actualPath).'"',
                ]);
            }

            return $fs->response($actualPath, basename($actualPath), [
                'Content-Type' => $mime,
            ], $disposition);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
