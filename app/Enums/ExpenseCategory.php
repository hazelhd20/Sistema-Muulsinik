<?php

namespace App\Enums;

enum ExpenseCategory: string
{
    case MATERIALES = 'materiales';
    case MANO_DE_OBRA = 'mano_de_obra';
    case EQUIPO = 'equipo';
    case TRANSPORTE = 'transporte';
    case SERVICIOS = 'servicios';
    case ADMINISTRATIVOS = 'administrativos';
    case OTROS = 'otros';

    public function label(): string
    {
        return match ($this) {
            self::MATERIALES => 'Materiales',
            self::MANO_DE_OBRA => 'Mano de Obra',
            self::EQUIPO => 'Equipo y Maquinaria',
            self::TRANSPORTE => 'Transporte',
            self::SERVICIOS => 'Servicios Profesionales',
            self::ADMINISTRATIVOS => 'Gastos Administrativos',
            self::OTROS => 'Otros',
        };
    }

    public static function toArray(): array
    {
        $array = [];
        foreach (self::cases() as $case) {
            $array[$case->value] = $case->label();
        }
        return $array;
    }
}
