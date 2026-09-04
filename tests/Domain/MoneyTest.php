<?php

use App\Domain\Money\Money;

it('Money opera en centavos sin float', function () {
    $a = Money::fromDecimal('10.50');
    $b = Money::fromCents(250);

    expect($a->cents)->toBe(1050)
        ->and($a->times(2)->cents)->toBe(2100)
        ->and($a->plus($b)->toDecimal())->toBe('13.00');
});
