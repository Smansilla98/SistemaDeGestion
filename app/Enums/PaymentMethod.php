<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case EFECTIVO = 'EFECTIVO';
    case DEBITO = 'DEBITO';
    case CREDITO = 'CREDITO';
    case TRANSFERENCIA = 'TRANSFERENCIA';

    /**
     * Obtener todos los valores
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

