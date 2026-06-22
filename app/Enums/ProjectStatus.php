<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case ACTIVE = 'activo';
    case PAUSED = 'en_pausa';
    case COMPLETED = 'completado';
    case CANCELLED = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Activo',
            self::PAUSED => 'En Pausa',
            self::COMPLETED => 'Completado',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::PAUSED => 'warning',
            self::COMPLETED => 'primary',
            self::CANCELLED => 'danger',
        };
    }

    public static function toArray(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($status) => [
            $status->value => $status->label()
        ])->toArray();
    }
}
