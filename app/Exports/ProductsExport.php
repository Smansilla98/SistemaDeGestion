<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected int $restaurantId,
        protected ?string $type = 'PRODUCT'
    ) {}

    public function query()
    {
        return Product::where('restaurant_id', $this->restaurantId)
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->with('category')
            ->orderBy('name');
    }

    public function headings(): array
    {
        return ['Nombre', 'Categoría', 'Precio', 'Tipo', 'Activo', 'Stock', 'Mínimo'];
    }

    public function map($product): array
    {
        $stockQty = $product->has_stock ? $product->getCurrentStock($this->restaurantId) : null;
        return [
            $product->name,
            $product->category?->name ?? '-',
            $product->price,
            $product->type ?? 'PRODUCT',
            $product->is_active ? 'Sí' : 'No',
            $product->has_stock ? $stockQty : '-',
            $product->has_stock ? ($product->stock_minimum ?? 0) : '-',
        ];
    }
}
