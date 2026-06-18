<?php

namespace App\Services;

use App\Models\FixedExpense;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FixedExpenseService
{
    /**
     * @return array{
     *     gastos: float,
     *     ingresos: float,
     *     neto: float,
     *     month_label: string,
     *     month_key: string
     * }
     */
    public function monthlySummary(int $restaurantId, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $gastos = 0.0;
        $ingresos = 0.0;

        foreach ($this->activeForRestaurant($restaurantId) as $item) {
            $projected = $item->getProjectedAmountForPeriod($start, $end);

            if ($item->type === FixedExpense::TYPE_GASTO) {
                $gastos += $projected;
            } else {
                $ingresos += $projected;
            }
        }

        return [
            'gastos' => round($gastos, 2),
            'ingresos' => round($ingresos, 2),
            'neto' => round($ingresos - $gastos, 2),
            'month_label' => $month->copy()->locale('es')->translatedFormat('F Y'),
            'month_key' => $month->format('Y-m'),
        ];
    }

    /**
     * @return Collection<int, array{
     *     item: FixedExpense,
     *     amount: float,
     *     monthly_equivalent: float,
     *     due_date: ?Carbon
     * }>
     */
    public function incomeBreakdown(int $restaurantId, Carbon $month): Collection
    {
        return $this->breakdownByType($restaurantId, $month, FixedExpense::TYPE_INGRESO);
    }

    /**
     * @return Collection<int, array{
     *     item: FixedExpense,
     *     amount: float,
     *     monthly_equivalent: float,
     *     due_date: ?Carbon
     * }>
     */
    public function expenseBreakdown(int $restaurantId, Carbon $month): Collection
    {
        return $this->breakdownByType($restaurantId, $month, FixedExpense::TYPE_GASTO);
    }

    /**
     * @return array<string, float>
     */
    public function totalsByCategory(int $restaurantId, Carbon $month, ?string $type = null): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $totals = [];

        $items = $this->activeForRestaurant($restaurantId);

        if ($type !== null) {
            $items = $items->where('type', $type);
        }

        foreach ($items as $item) {
            $projected = $item->getProjectedAmountForPeriod($start, $end);
            $label = $item->getCategoryLabel();
            $totals[$label] = ($totals[$label] ?? 0) + $projected;
        }

        arsort($totals);

        return array_map(fn (float $v) => round($v, 2), $totals);
    }

    /**
     * @return list<array{month: string, month_name: string, amount: float}>
     */
    public function projectForMonths(FixedExpense $item, Carbon $fromMonth, int $months = 12): array
    {
        $projections = [];
        $cursor = $fromMonth->copy()->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $monthStart = $cursor->copy();
            $monthEnd = $cursor->copy()->endOfMonth();

            $projections[] = [
                'month' => $monthStart->format('Y-m'),
                'month_name' => $monthStart->locale('es')->translatedFormat('F Y'),
                'amount' => $item->getProjectedAmountForPeriod($monthStart, $monthEnd),
            ];

            $cursor->addMonth();
        }

        return $projections;
    }

    /**
     * @return Collection<int, FixedExpense>
     */
    private function activeForRestaurant(int $restaurantId): Collection
    {
        return FixedExpense::query()
            ->where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('category')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, array{
     *     item: FixedExpense,
     *     amount: float,
     *     monthly_equivalent: float,
     *     due_date: ?Carbon
     * }>
     */
    private function breakdownByType(int $restaurantId, Carbon $month, string $type): Collection
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        return $this->activeForRestaurant($restaurantId)
            ->where('type', $type)
            ->map(function (FixedExpense $item) use ($start, $end, $month) {
                return [
                    'item' => $item,
                    'amount' => $item->getProjectedAmountForPeriod($start, $end),
                    'monthly_equivalent' => $item->getMonthlyEquivalent(),
                    'due_date' => $item->getDueDateForMonth($month),
                ];
            })
            ->filter(fn (array $row) => $row['amount'] > 0)
            ->sort(function (array $a, array $b) {
                $dayA = $a['due_date']?->day ?? 32;
                $dayB = $b['due_date']?->day ?? 32;
                if ($dayA !== $dayB) {
                    return $dayA <=> $dayB;
                }

                return $b['amount'] <=> $a['amount'];
            })
            ->values();
    }
}
