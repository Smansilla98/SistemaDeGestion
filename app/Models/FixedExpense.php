<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class FixedExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'name',
        'description',
        'type',
        'category',
        'amount',
        'frequency',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Tipos
    const TYPE_GASTO = 'GASTO';
    const TYPE_INGRESO = 'INGRESO';

    // Categorías
    const CATEGORY_ALQUILER = 'ALQUILER';
    const CATEGORY_SERVICIOS = 'SERVICIOS';
    const CATEGORY_PERSONAL = 'PERSONAL';
    const CATEGORY_OPERATIVOS = 'OPERATIVOS';
    const CATEGORY_TALLER = 'TALLER';
    const CATEGORY_OTROS = 'OTROS';

    // Frecuencias
    const FREQUENCY_MENSUAL = 'MENSUAL';
    const FREQUENCY_QUINCENAL = 'QUINCENAL';
    const FREQUENCY_SEMANAL = 'SEMANAL';
    const FREQUENCY_DIARIO = 'DIARIO';
    const FREQUENCY_ANUAL = 'ANUAL';

    /**
     * Relación: Un gasto fijo pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Obtener el monto proyectado para un período
     */
    public function getProjectedAmountForPeriod($startDate, $endDate): float
    {
        if (!$this->is_active) {
            return 0;
        }

        $periodStart = Carbon::parse($startDate);
        $periodEnd = Carbon::parse($endDate);
        $expenseStart = Carbon::parse($this->start_date);
        $expenseEnd = $this->end_date ? Carbon::parse($this->end_date) : null;

        // Verificar si el período se superpone
        if ($expenseEnd && $expenseEnd < $periodStart) {
            return 0;
        }
        if ($expenseStart > $periodEnd) {
            return 0;
        }

        // Calcular cantidad de ocurrencias según frecuencia
        $occurrences = $this->calculateOccurrences($periodStart, $periodEnd, $expenseStart, $expenseEnd);

        return $this->amount * $occurrences;
    }

    /**
     * Calcular cantidad de ocurrencias en un período
     */
    private function calculateOccurrences($periodStart, $periodEnd, $expenseStart, $expenseEnd): int
    {
        $actualStart = $expenseStart->greaterThan($periodStart) ? $expenseStart : $periodStart;
        $actualEnd = $expenseEnd && $expenseEnd->lessThan($periodEnd) ? $expenseEnd : $periodEnd;

        switch ($this->frequency) {
            case self::FREQUENCY_DIARIO:
                return $actualStart->diffInDays($actualEnd) + 1;
            case self::FREQUENCY_SEMANAL:
                return floor($actualStart->diffInWeeks($actualEnd)) + 1;
            case self::FREQUENCY_QUINCENAL:
                return floor($actualStart->diffInWeeks($actualEnd) / 2) + 1;
            case self::FREQUENCY_MENSUAL:
                return $actualStart->diffInMonths($actualEnd) + 1;
            case self::FREQUENCY_ANUAL:
                return $actualStart->diffInYears($actualEnd) + 1;
            default:
                return 1;
        }
    }

    /**
     * Obtener etiqueta de categoría
     */
    public function getCategoryLabel(): string
    {
        return match($this->category) {
            self::CATEGORY_ALQUILER => 'Alquiler',
            self::CATEGORY_SERVICIOS => 'Servicios',
            self::CATEGORY_PERSONAL => 'Personal',
            self::CATEGORY_OPERATIVOS => 'Operativos',
            self::CATEGORY_TALLER => 'Taller',
            default => 'Otros',
        };
    }
}

