<?php

namespace App\Enums;

enum OrderStatus: string
{
    case ABIERTO = 'ABIERTO';
    case ENVIADO = 'ENVIADO';
    case EN_PREPARACION = 'EN_PREPARACION';
    case LISTO = 'LISTO';
    case ENTREGADO = 'ENTREGADO';
    case CERRADO = 'CERRADO';
    case CANCELADO = 'CANCELADO';

    /**
     * Obtener todos los valores
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Verificar si el estado permite edición
     */
    public function allowsEditing(): bool
    {
        return in_array($this, [self::ABIERTO, self::ENVIADO]);
    }

    /**
     * Verificar si el pedido está cerrado
     */
    public function isClosed(): bool
    {
        return $this === self::CERRADO || $this === self::CANCELADO;
    }
}

