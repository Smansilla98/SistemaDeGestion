<?php

namespace App\Livewire\Mobile;

use App\Models\Product;
use App\Models\Table;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TomaPedido extends Component
{
    public ?int $mesa = null;
    public array $items = [];
    public ?int $productoSeleccionado = null;
    public int $cantidad = 1;
    public ?string $observaciones = null;

    public ?float $precioActual = null;
    public ?int $stockDisponible = null;
    public string $busqueda = '';

    public function mount(): void
    {
        $this->cantidad = 1;
    }

    public function updatedProductoSeleccionado(): void
    {
        if ($this->productoSeleccionado) {
            $product = Product::where('restaurant_id', Auth::user()->restaurant_id)
                ->where('id', $this->productoSeleccionado)
                ->first();

            if ($product) {
                $this->precioActual = (float) $product->price;
                $this->stockDisponible = $product->has_stock ? $product->getCurrentStock(Auth::user()->restaurant_id) : null;
                $this->cantidad = 1;
            }
        }
    }

    public function agregarItem(): void
    {
        $this->validate([
            'productoSeleccionado' => 'required|exists:products,id',
            'cantidad' => 'required|integer|min:1',
        ]);

        $product = Product::where('restaurant_id', Auth::user()->restaurant_id)
            ->where('id', $this->productoSeleccionado)
            ->firstOrFail();

        $precio = (float) $product->price;
        $subtotal = $precio * $this->cantidad;

        $this->items[] = [
            'product_id' => $product->id,
            'name' => $product->name,
            'quantity' => $this->cantidad,
            'price' => $precio,
            'subtotal' => $subtotal,
        ];

        $this->reset(['productoSeleccionado', 'cantidad', 'precioActual', 'stockDisponible']);
        $this->cantidad = 1;
    }

    public function quitarItem(int $index): void
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        }
    }

    public function confirmarPedido(OrderService $orderService)
    {
        $this->validate([
            'mesa' => 'required|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        $payload = [
            'restaurant_id' => $user->restaurant_id,
            'table_id' => $this->mesa,
            'user_id' => $user->id,
            'observations' => $this->observaciones,
            'items' => collect($this->items)->map(function ($item) {
                return [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ];
            })->all(),
        ];

        try {
            $order = $orderService->createOrder($payload);

            foreach ($payload['items'] as $itemData) {
                $orderService->addItem($order, $itemData);
            }

            session()->flash('success', 'Pedido cargado correctamente (Nº ' . $order->number . ').');
            $this->reset(['mesa', 'items', 'productoSeleccionado', 'cantidad', 'observaciones', 'precioActual', 'stockDisponible', 'busqueda']);
            $this->cantidad = 1;
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'Error al confirmar el pedido: ' . $e->getMessage());
        }
    }

    public function getMesasProperty()
    {
        return Table::where('restaurant_id', Auth::user()->restaurant_id)
            ->orderBy('number')
            ->get();
    }

    public function getProductosProperty()
    {
        $query = Product::where('restaurant_id', Auth::user()->restaurant_id)
            ->where('is_active', true)
            ->orderBy('name');

        if ($this->busqueda) {
            $term = '%' . $this->busqueda . '%';
            $query->where('name', 'like', $term);
        }

        return $query->limit(50)->get();
    }

    public function render()
    {
        return view('livewire.mobile.toma-pedido', [
            'mesas' => $this->mesas,
            'productos' => $this->productos,
        ]);
    }
}

