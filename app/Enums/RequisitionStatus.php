<?php

namespace App\Enums;

enum RequisitionStatus: string
{
    case DRAFT = 'borrador';
    case PENDING = 'pendiente';
    case APPROVED = 'aprobada';
    case REJECTED = 'rechazada';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Borrador',
            self::PENDING => 'Pendiente',
            self::APPROVED => 'Aprobada',
            self::REJECTED => 'Rechazada',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'secondary',
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
