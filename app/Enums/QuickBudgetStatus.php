<?php

namespace App\Enums;

enum QuickBudgetStatus: string
{
    case DRAFT = 'borrador';
    case SENT = 'enviado';
    case APPROVED = 'aprobado';
    case REJECTED = 'rechazado';
    case EXPIRED = 'expirado';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Borrador',
            self::SENT => 'Enviado',
            self::APPROVED => 'Aprobado',
            self::REJECTED => 'Rechazado',
            self::EXPIRED => 'Expirado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SENT => 'blue',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
            self::EXPIRED => 'orange',
        };
    }

    public static function toArray(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($status) => [
            $status->value => $status->label()
        ])->toArray();
    }
}
