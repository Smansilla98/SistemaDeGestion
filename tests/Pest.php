<?php

uses(Tests\TestCase::class)->in('Feature', 'Unit', 'Domain');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOrderNumber', function () {
    return $this->toMatch('/^ORD-\d{4}-\d+$/');
});
