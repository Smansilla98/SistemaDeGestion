<?php

namespace App\Domain\Money;

final class Money implements \JsonSerializable
{
    private function __construct(public readonly int $cents) {}

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function fromDecimal(string|float|int $amount): self
    {
        return new self((int) round(((float) $amount) * 100));
    }

    public function times(int $qty): self
    {
        return new self($this->cents * $qty);
    }

    public function plus(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function minus(self $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function toDecimal(): string
    {
        return number_format($this->cents / 100, 2, '.', '');
    }

    public function toFloat(): float
    {
        return round($this->cents / 100, 2);
    }

    public function jsonSerialize(): mixed
    {
        return ['cents' => $this->cents, 'formatted' => '$'.$this->toDecimal()];
    }
}
