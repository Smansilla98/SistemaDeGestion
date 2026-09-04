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

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** Estados visibles en el tablero KDS. */
    public static function kitchenBoard(): array
    {
        return [
            self::ENVIADO->value,
            self::EN_PREPARACION->value,
            self::LISTO->value,
        ];
    }

    /** @return array<int, self> */
    public function allowedNext(): array
    {
        return match ($this) {
            self::ABIERTO => [self::ENVIADO, self::ENTREGADO, self::CANCELADO, self::CERRADO],
            self::ENVIADO => [self::EN_PREPARACION, self::LISTO, self::ENTREGADO, self::CANCELADO],
            self::EN_PREPARACION => [self::LISTO, self::ENVIADO, self::ENTREGADO, self::CANCELADO],
            self::LISTO => [self::ENTREGADO, self::EN_PREPARACION, self::CANCELADO],
            self::ENTREGADO => [self::CERRADO, self::CANCELADO],
            self::CERRADO, self::CANCELADO => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNext(), true);
    }

    public function isFinal(): bool
    {
        return $this->allowedNext() === [];
    }

    public function allowsEditing(): bool
    {
        return in_array($this, [self::ABIERTO, self::ENVIADO, self::EN_PREPARACION], true);
    }

    public function isClosed(): bool
    {
        return $this === self::CERRADO || $this === self::CANCELADO;
    }

    public static function tryFromLoose(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        $normalized = strtoupper(str_replace(' ', '_', trim($value)));

        return self::tryFrom($normalized);
    }
}
