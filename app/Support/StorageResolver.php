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
     * Descarga y copia un archivo en forma de stream desde cualquier disco hacia un archivo local
     * (por ejemplo, en /tmp) con un consumo de RAM casi cero (< 256 KB), ideal para optimizar memoria
     * en workers de colas en Railway al manejar PDFs o imágenes pesadas.
     *
     * @param string|null $path
     * @param string $destinationPath
     * @return bool
     */
    public static function copyToFile(?string $path, string $destinationPath): bool
    {
        $resolved = self::resolve($path);

        if (! $resolved) {
            return false;
        }

        $srcStream = null;
        $destStream = null;
        try {
            $actualPath = $resolved['path'] ?? $path;
            $srcStream = $resolved['filesystem']->readStream($actualPath);

            if (! $srcStream) {
                return false;
            }

            $destStream = @fopen($destinationPath, 'w+b');
            if (! $destStream) {
                return false;
            }

            stream_copy_to_stream($srcStream, $destStream);

            return file_exists($destinationPath) && filesize($destinationPath) > 0;
        } catch (\Throwable $e) {
            return false;
        } finally {
            if (is_resource($srcStream)) fclose($srcStream);
            if (is_resource($destStream)) fclose($destStream);
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

            // Local / Public: generar URL de descarga segura vía la ruta de streaming del sistema (file.preview)
            // Esto garantiza que el archivo se descargue (Content-Disposition: attachment) sin error 404,
            // incluso en entornos locales donde el symlink storage:link no exista o no esté sirviendo archivos.
            return route('file.preview', ['path' => $actualPath, 'disk' => $disk, 'download' => 1]);
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

            // Para S3/Tigris: redirigir directamente con una URL pre-firmada temporal
            if (! in_array($disk, ['local', 'public'])) {
                try {
                    $options = [];
                    if ($disposition === 'attachment') {
                        $options['ResponseContentDisposition'] = 'attachment; filename="' . basename($actualPath) . '"';
                    }
                    $temporaryUrl = $fs->temporaryUrl($actualPath, now()->addMinutes(15), $options);
                    return redirect($temporaryUrl);
                } catch (\Throwable $eTemp) {
                    // Fallback: stream manual desde S3 si temporaryUrl no está disponible en este driver
                    $s3Stream = null;
                    try {
                        $s3Stream = $fs->readStream($actualPath);
                        if (! $s3Stream) {
                            return null;
                        }
                        return response()->stream(function () use ($s3Stream) {
                            fpassthru($s3Stream);
                            if (is_resource($s3Stream)) fclose($s3Stream);
                        }, 200, [
                            'Content-Type'        => $mime,
                            'Content-Disposition' => $disposition . '; filename="' . basename($actualPath) . '"',
                        ]);
                    } catch (\Throwable $eStream) {
                        if (is_resource($s3Stream)) fclose($s3Stream);
                        return null;
                    }
                }
            }

            // Local / Public: servir el archivo directamente
            return response()->file($fs->path($actualPath), [
                'Content-Type'        => $mime,
                'Content-Disposition' => $disposition . '; filename="' . basename($actualPath) . '"',
            ]);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
