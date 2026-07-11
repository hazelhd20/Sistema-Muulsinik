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
     * Resuelve la URL óptima para mostrar un archivo inline en el navegador (avatares, logos, imágenes).
     *
     * - S3/Tigris: genera una URL pre-firmada temporal SIN forzar descarga. El navegador
     *   carga la imagen directamente desde el bucket sin pasar por PHP. Cero round-trips extra.
     *
     * - Local/Public: usa Storage::url() que apunta directamente al servidor estático.
     *   En local con storage:link resuelve a /storage/...; en producción con Nginx/Apache es directo.
     *
     * Diferencia con resolveDownloadUrl(): NO añade Content-Disposition: attachment.
     * Usar resolveUrl() para <img src>, resolveDownloadUrl() para <a href download>.
     *
     * @param string|null $path
     * @param string|null $preferredDisk
     * @param int $expirationMinutes Minutos de validez de la URL pre-firmada (solo S3)
     * @return string|null URL lista para embeber en <img src> o <a href> inline
     */
    public static function resolveUrl(?string $path, ?string $preferredDisk = null, int $expirationMinutes = 60): ?string
    {
        if (! $path || str_contains($path, '..')) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, 'data:')) {
            return $path;
        }

        // Intento directo ultrarrápido sin verificación de red (sin self::resolve que ejecuta exists/HeadObject en S3)
        try {
            $diskName = $preferredDisk ?: config('filesystems.default');
            $fs = Storage::disk($diskName);

            if (! in_array($diskName, ['local', 'public'])) {
                return $fs->temporaryUrl($path, now()->addMinutes($expirationMinutes));
            }

            return $fs->url($path);
        } catch (\Throwable $e) {
            // Si el intento directo falla (ej. disco no soportado en local), hacemos resolución con candidatos
            $resolved = self::resolve($path, $preferredDisk);
            if (! $resolved) {
                return null;
            }

            try {
                $actualPath = $resolved['path'] ?? $path;
                $disk = $resolved['disk'];
                $fs = $resolved['filesystem'];

                if (! in_array($disk, ['local', 'public'])) {
                    return $fs->temporaryUrl($actualPath, now()->addMinutes($expirationMinutes));
                }

                return $fs->url($actualPath);
            } catch (\Throwable $e2) {
                return null;
            }
        }
    }
    /**
     * Resuelve la URL de descarga óptima para el archivo dado su disco:
     *
     * - S3/Tigris: genera una URL pre-firmada temporal (el navegador descarga directamente
     *   desde el bucket sin pasar por PHP). Esta es la única estrategia correcta para
     *   archivos pesados en producción con almacenamiento en la nube.
     *
     * - Local/Public: redirige a la URL pública estática (storage_link) o usa
     *   Storage::url() que resuelve correctamente en ambos discos.
     *
     * @param string|null $path
     * @param string|null $preferredDisk
     * @param int $expirationMinutes Minutos de validez de la URL pre-firmada (solo S3)
     * @return string|null URL de descarga lista para redirigir o embeber en <a href>
     */
    public static function resolveDownloadUrl(?string $path, ?string $preferredDisk = null, int $expirationMinutes = 15): ?string
    {
        $resolved = self::resolve($path, $preferredDisk);

        if (! $resolved) {
            return null;
        }

        try {
            $actualPath = $resolved['path'] ?? $path;
            $disk = $resolved['disk'];
            $fs = $resolved['filesystem'];

            // S3 / Tigris / cualquier driver compatible con pre-signed URLs
            if (! in_array($disk, ['local', 'public'])) {
                return $fs->temporaryUrl(
                    $actualPath,
                    now()->addMinutes($expirationMinutes),
                    ['ResponseContentDisposition' => 'attachment; filename="' . basename($actualPath) . '"']
                );
            }

            // Local / Public: la URL pública del disco ya resuelve correctamente
            return $fs->url($actualPath);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Retorna una respuesta HTTP de streaming para previsualización inline (imágenes, PDFs).
     * NO usar para descargas de archivos pesados; usar resolveDownloadUrl() en su lugar.
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

            // Para S3, en vez de hacer stream PHP, redirigir directamente al bucket
            if (! in_array($disk, ['local', 'public'])) {
                try {
                    $temporaryUrl = $fs->temporaryUrl($actualPath, now()->addMinutes(15));
                    return redirect($temporaryUrl);
                } catch (\Throwable $e) {
                    // Fallback: stream manual si temporaryUrl no está disponible
                }
            }

            return response()->file($fs->path($actualPath), [
                'Content-Type' => $mime,
                'Content-Disposition' => $disposition . '; filename="' . basename($actualPath) . '"',
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
