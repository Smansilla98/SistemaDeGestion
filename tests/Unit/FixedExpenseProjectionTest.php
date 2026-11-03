<?php

use App\Models\FixedExpense;

it('un gasto diario cuenta los días de calendario del período', function () {
    $expense = new FixedExpense(['amount' => 1000, 'frequency' => FixedExpense::FREQUENCY_DIARIO, 'start_date' => '2026-01-01', 'is_active' => true]);

    expect($expense->getProjectedAmountForPeriod('2026-11-01', '2026-11-30'))->toBe(30000.0)
        ->and($expense->getProjectedAmountForPeriod('2026-11-10', '2026-11-10'))->toBe(1000.0);
});

it('un gasto semanal cuenta las semanas que caen en el período', function () {
    $expense = new FixedExpense(['amount' => 500, 'frequency' => FixedExpense::FREQUENCY_SEMANAL, 'start_date' => '2026-01-01', 'is_active' => true]);

    // 1 al 30 de noviembre: 29 días → 5 ocurrencias (días 1, 8, 15, 22 y 29).
    expect($expense->getProjectedAmountForPeriod('2026-11-01', '2026-11-30'))->toBe(2500.0);
});
