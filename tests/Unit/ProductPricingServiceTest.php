<?php

namespace Tests\Unit;

use App\Services\ProductPricingService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ProductPricingServiceTest extends TestCase
{
    private ProductPricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pricing = new ProductPricingService();
    }

    public function test_calculates_one_hundred_percent_profit_margin(): void
    {
        $this->assertSame(100.0, $this->pricing->profitMargin(1, 2));
    }

    public function test_calculates_sale_price_from_two_hundred_percent_margin(): void
    {
        $this->assertSame(3.0, $this->pricing->salePrice(1, 200));
    }

    public function test_margin_input_recalculates_sale_price(): void
    {
        $result = $this->pricing->apply([
            'cost_price' => 1000,
            'price' => 1500,
            'profit_margin' => 200,
        ]);

        $this->assertSame(3000.0, $result['price']);
        $this->assertArrayNotHasKey('profit_margin', $result);
    }

    public function test_margin_is_undefined_when_cost_is_zero(): void
    {
        $this->assertNull($this->pricing->profitMargin(0, 100));
    }

    public function test_rejects_sale_calculation_without_positive_cost(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->pricing->salePrice(0, 100);
    }
}
