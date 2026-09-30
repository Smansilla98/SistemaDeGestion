<?php

namespace App\Models\Concerns;

use App\Exceptions\StaleOrderException;

trait OptimisticLocking
{
    public function saveWithVersion(array $attributes = []): bool
    {
        if (! array_key_exists('lock_version', $this->getAttributes()) && ! $this->exists) {
            return $this->fill($attributes)->save();
        }

        $current = (int) ($this->lock_version ?? 0);
        $payload = $attributes + [
            'lock_version' => $current + 1,
            'updated_at' => now(),
        ];

        $affected = static::whereKey($this->getKey())
            ->where('lock_version', $current)
            ->update($payload);

        if ($affected === 0) {
            throw new StaleOrderException('El pedido fue modificado por otro usuario. Recargá y reintentá.');
        }

        $this->fill($payload);
        $this->syncOriginal();

        return true;
    }
}
