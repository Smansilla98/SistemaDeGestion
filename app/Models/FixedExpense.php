<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'due_day',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_day' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    const TYPE_GASTO = 'GASTO';

    const TYPE_INGRESO = 'INGRESO';

    const CATEGORY_ALQUILER = 'ALQUILER';

    const CATEGORY_SERVICIOS = 'SERVICIOS';

    const CATEGORY_PERSONAL = 'PERSONAL';

    const CATEGORY_OPERATIVOS = 'OPERATIVOS';

    const CATEGORY_TALLER = 'TALLER';

    const CATEGORY_CANON = 'CANON';

    const CATEGORY_SUBSIDIO = 'SUBSIDIO';

    const CATEGORY_CONTRATO = 'CONTRATO';

    const CATEGORY_OTROS = 'OTROS';

    const FREQUENCY_MENSUAL = 'MENSUAL';

    const FREQUENCY_QUINCENAL = 'QUINCENAL';

    const FREQUENCY_SEMANAL = 'SEMANAL';

    const FREQUENCY_DIARIO = 'DIARIO';

    const FREQUENCY_ANUAL = 'ANUAL';

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * @return array<string, string>
     */
    public static function categoriesForType(string $type): array
    {
        if ($type === self::TYPE_INGRESO) {
            return [
                self::CATEGORY_CANON => 'Canon / Alquiler cobrado',
                self::CATEGORY_TALLER => 'Taller / Actividades',
                self::CATEGORY_CONTRATO => 'Contrato fijo',
                self::CATEGORY_SUBSIDIO => 'Subsidio / Aporte',
                self::CATEGORY_OTROS => 'Otros ingresos',
            ];
        }

        return [
            self::CATEGORY_ALQUILER => 'Alquiler',
            self::CATEGORY_SERVICIOS => 'Servicios',
            self::CATEGORY_PERSONAL => 'Personal',
            self::CATEGORY_OPERATIVOS => 'Operativos',
            self::CATEGORY_OTROS => 'Otros gastos',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function frequencyLabels(): array
    {
        return [
            self::FREQUENCY_MENSUAL => 'Mensual',
            self::FREQUENCY_QUINCENAL => 'Quincenal',
            self::FREQUENCY_SEMANAL => 'Semanal',
            self::FREQUENCY_DIARIO => 'Diario',
            self::FREQUENCY_ANUAL => 'Anual',
        ];
    }

    /**
     * @return list<string>
     */
    public static function allCategoryKeys(): array
    {
        return array_keys(array_merge(
            self::categoriesForType(self::TYPE_GASTO),
            self::categoriesForType(self::TYPE_INGRESO)
        ));
    }

    public function getCategoryLabel(): string
    {
        $labels = array_merge(
            self::categoriesForType(self::TYPE_GASTO),
            self::categoriesForType(self::TYPE_INGRESO)
        );

        return $labels[$this->category] ?? 'Otros';
    }

    public function getFrequencyLabel(): string
    {
        return self::frequencyLabels()[$this->frequency] ?? $this->frequency;
    }

    /**
     * Monto normalizado a un mes de referencia (para comparar y planificar).
     */
    public function getMonthlyEquivalent(): float
    {
        $amount = (float) $this->amount;

        return round(match ($this->frequency) {
            self::FREQUENCY_MENSUAL => $amount,
            self::FREQUENCY_QUINCENAL => $amount * 2,
            self::FREQUENCY_SEMANAL => $amount * (52 / 12),
            self::FREQUENCY_DIARIO => $amount * 30,
            self::FREQUENCY_ANUAL => $amount / 12,
            default => $amount,
        }, 2);
    }

    public function getDueDayLabel(): ?string
    {
        if ($this->due_day === null) {
            return null;
        }

        return 'Día '.$this->due_day.' de cada mes';
    }

    /**
     * Fecha estimada de cobro/pago dentro de un mes dado.
     */
    public function getDueDateForMonth(Carbon $month): ?Carbon
    {
        if ($this->due_day === null) {
            return null;
        }

        $daysInMonth = $month->copy()->endOfMonth()->day;
        $day = min($this->due_day, $daysInMonth);

        return $month->copy()->startOfMonth()->day($day);
    }

    public function getProjectedAmountForPeriod($startDate, $endDate): float
    {
        if (! $this->is_active) {
            return 0;
        }

        $periodStart = Carbon::parse($startDate)->startOfDay();
        $periodEnd = Carbon::parse($endDate)->endOfDay();
        $expenseStart = Carbon::parse($this->start_date)->startOfDay();
        $expenseEnd = $this->end_date ? Carbon::parse($this->end_date)->endOfDay() : null;

        if ($expenseEnd && $expenseEnd->lt($periodStart)) {
            return 0;
        }

        if ($expenseStart->gt($periodEnd)) {
            return 0;
        }

        $occurrences = $this->countOccurrencesInPeriod($periodStart, $periodEnd, $expenseStart, $expenseEnd);

        return round((float) $this->amount * $occurrences, 2);
    }

    private function countOccurrencesInPeriod(
        Carbon $periodStart,
        Carbon $periodEnd,
        Carbon $expenseStart,
        ?Carbon $expenseEnd
    ): int {
        $overlapStart = $expenseStart->greaterThan($periodStart) ? $expenseStart->copy() : $periodStart->copy();
        $overlapEnd = ($expenseEnd && $expenseEnd->lessThan($periodEnd)) ? $expenseEnd->copy() : $periodEnd->copy();

        if ($overlapStart->greaterThan($overlapEnd)) {
            return 0;
        }

        return match ($this->frequency) {
            self::FREQUENCY_DIARIO => $overlapStart->diffInDays($overlapEnd) + 1,
            self::FREQUENCY_SEMANAL => (int) floor($overlapStart->diffInDays($overlapEnd) / 7) + 1,
            self::FREQUENCY_QUINCENAL => $this->countActiveMonths($periodStart, $periodEnd, $expenseStart, $expenseEnd) * 2,
            self::FREQUENCY_MENSUAL => $this->countActiveMonths($periodStart, $periodEnd, $expenseStart, $expenseEnd),
            self::FREQUENCY_ANUAL => $this->countAnnualOccurrencesInPeriod($periodStart, $periodEnd, $expenseStart, $expenseEnd),
            default => 1,
        };
    }

    private function countActiveMonths(
        Carbon $periodStart,
        Carbon $periodEnd,
        Carbon $expenseStart,
        ?Carbon $expenseEnd
    ): int {
        $count = 0;
        $cursor = $periodStart->copy()->startOfMonth();
        $end = $periodEnd->copy()->startOfMonth();

        while ($cursor->lte($end)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();

            if ($this->isActiveBetween($monthStart, $monthEnd, $expenseStart, $expenseEnd)) {
                $count++;
            }

            $cursor->addMonth();
        }

        return $count;
    }

    private function countAnnualOccurrencesInPeriod(
        Carbon $periodStart,
        Carbon $periodEnd,
        Carbon $expenseStart,
        ?Carbon $expenseEnd
    ): int {
        $count = 0;

        for ($year = $periodStart->year; $year <= $periodEnd->year; $year++) {
            $day = min($expenseStart->day, Carbon::create($year, $expenseStart->month, 1)->daysInMonth);
            $anniversary = Carbon::create($year, $expenseStart->month, $day)->startOfDay();

            if ($anniversary->between($periodStart, $periodEnd)
                && $this->isActiveBetween($anniversary, $anniversary, $expenseStart, $expenseEnd)) {
                $count++;
            }
        }

        return $count;
    }

    private function isActiveBetween(
        Carbon $rangeStart,
        Carbon $rangeEnd,
        Carbon $expenseStart,
        ?Carbon $expenseEnd
    ): bool {
        if ($expenseEnd && $expenseEnd->lt($rangeStart)) {
            return false;
        }

        if ($expenseStart->gt($rangeEnd)) {
            return false;
        }

        return true;
    }
}
