<?php

namespace App\Enums;

enum TableStatus: string
{
    case LIBRE = 'LIBRE';
    case OCUPADA = 'OCUPADA';
    case RESERVADA = 'RESERVADA';
    case CERRADA = 'CERRADA';

    /**
     * Obtener todos los valores
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

