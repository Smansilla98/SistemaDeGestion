<?php

namespace App\Domain\Numbering;

use Illuminate\Support\Facades\DB;

/**
 * Numeración de documentos por local + tipo + período.
 * DEBE llamarse dentro de una transacción abierta.
 */
final class SequenceGenerator
{
    /**
     * Devuelve el próximo valor entero (y avanza la secuencia).
     */
    public function next(int $restaurantId, string $type, int $period): int
    {
        $type = strtolower($type);

        DB::table('document_sequences')->insertOrIgnore([
            'restaurant_id' => $restaurantId,
            'type' => $type,
            'period' => $period,
            'next_value' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('document_sequences')
            ->where('restaurant_id', $restaurantId)
            ->where('type', $type)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();

        if (! $row) {
            DB::table('document_sequences')->insert([
                'restaurant_id' => $restaurantId,
                'type' => $type,
                'period' => $period,
                'next_value' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $row = DB::table('document_sequences')
                ->where('restaurant_id', $restaurantId)
                ->where('type', $type)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                throw new \RuntimeException('No se pudo inicializar la secuencia de pedidos.');
            }
        }

        $value = (int) $row->next_value;

        DB::table('document_sequences')
            ->where('id', $row->id)
            ->update([
                'next_value' => $value + 1,
                'updated_at' => now(),
            ]);

        return $value;
    }

    /**
     * Formato ORD-2026-0001. Alinea next_value con MAX existente si hace falta.
     */
    public function formatted(int $restaurantId, string $prefix, int $period): string
    {
        $type = 'order';
        $displayPrefix = strtoupper($prefix);
        $this->ensureAlignedWithExisting($restaurantId, $type, $period, $displayPrefix);
        $n = $this->next($restaurantId, $type, $period);

        return sprintf('%s-%d-%04d', $displayPrefix, $period, $n);
    }

    private function ensureAlignedWithExisting(int $restaurantId, string $type, int $period, string $prefix): void
    {
        $fmtPrefix = $prefix.'-'.$period.'-';
        $maxExisting = $this->maxSeqFromOrders($restaurantId, $fmtPrefix);

        if ($maxExisting <= 0) {
            return;
        }

        DB::table('document_sequences')->insertOrIgnore([
            'restaurant_id' => $restaurantId,
            'type' => $type,
            'period' => $period,
            'next_value' => $maxExisting + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('document_sequences')
            ->where('restaurant_id', $restaurantId)
            ->where('type', $type)
            ->where('period', $period)
            ->lockForUpdate()
            ->first();

        if ($row && (int) $row->next_value <= $maxExisting) {
            DB::table('document_sequences')
                ->where('id', $row->id)
                ->update([
                    'next_value' => $maxExisting + 1,
                    'updated_at' => now(),
                ]);
        }
    }

    private function maxSeqFromOrders(int $restaurantId, string $prefix): int
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $max = DB::table('orders')
                ->where('restaurant_id', $restaurantId)
                ->where('number', 'like', $prefix.'%')
                ->selectRaw('MAX(CAST(SUBSTRING(number, ?) AS UNSIGNED)) as max_seq', [strlen($prefix) + 1])
                ->value('max_seq');

            return (int) ($max ?? 0);
        }

        $max = 0;
        foreach (DB::table('orders')->where('restaurant_id', $restaurantId)->where('number', 'like', $prefix.'%')->pluck('number') as $number) {
            $suffix = substr((string) $number, strlen($prefix));
            if (ctype_digit($suffix)) {
                $max = max($max, (int) $suffix);
            }
        }

        return $max;
    }
}
