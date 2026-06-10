<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group', 'type', 'label', 'description'];

    /**
     * Obtener un valor de configuración por clave.
     * Usa caché para evitar consultas repetidas.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = "setting_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = self::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return self::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Establecer un valor de configuración.
     */
    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $setting = self::where('key', $key)->first();

        if ($setting) {
            $setting->update(['value' => $value, 'type' => $type]);
        }

        Cache::forget("setting_{$key}");
    }

    /**
     * Convertir el valor según su tipo.
     */
    private static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => (bool) $value,
            'number' => is_numeric($value) ? (float) $value : $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Obtener todas las configuraciones por grupo.
     */
    public static function byGroup(string $group): array
    {
        return self::where('group', $group)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->key => self::castValue($s->value, $s->type)])
            ->toArray();
    }

    /**
     * Limpiar caché de configuraciones.
     */
    public static function clearCache(): void
    {
        foreach (self::pluck('key') as $key) {
            Cache::forget("setting_{$key}");
        }
    }
}
