<?php

namespace App\Livewire\Mobile;

use App\Models\Product;
use App\Models\Table;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class TomaPedido extends Component
{
    public ?int $mesa = null;

    public array $items = [];

    public ?int $productoSeleccionado = null;

    /** Variante elegida del producto seleccionado (ej. sabor de la pizza). */
    public ?int $varianteSeleccionada = null;

    /** @var list<array{id:int, name:string, price:float}> */
    public array $variantes = [];

    public int $cantidad = 1;

    public ?string $observaciones = null;

    public ?float $precioActual = null;

    public ?int $stockDisponible = null;

    public string $busqueda = '';

    public bool $enviarCocina = true;

    public function mount(): void
    {
        $this->cantidad = 1;
    }

    public function selectMesa(int $tableId): void
    {
        $this->mesa = $this->mesa === $tableId ? null : $tableId;
    }

    public function selectProducto(int $productId): void
    {
        $this->productoSeleccionado = $productId;
        $this->updatedProductoSeleccionado();
    }

    public function updatedProductoSeleccionado(): void
    {
        if ($this->productoSeleccionado) {
            $product = Product::where('restaurant_id', Auth::user()->restaurant_id)
                ->where('id', $this->productoSeleccionado)
                ->first();

            if ($product) {
                $this->varianteSeleccionada = null;
                $this->variantes = $product->variants()->get()
                    ->map(fn ($v) => ['id' => (int) $v->id, 'name' => $v->name, 'price' => (float) $v->price])
                    ->all();
                // Con variantes no hay precio hasta que se elige una.
                $this->precioActual = $this->variantes === [] ? (float) $product->price : null;
                $this->stockDisponible = $product->has_stock
                    ? $product->getCurrentStock(Auth::user()->restaurant_id)
                    : null;
                $this->cantidad = 1;
            }
        }
    }

    public function selectVariante(int $variantId): void
    {
        $variant = collect($this->variantes)->firstWhere('id', $variantId);
        if ($variant === null) {
            return;
        }

        $this->varianteSeleccionada = $variantId;
        $this->precioActual = (float) $variant['price'];
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

        if ($product->has_stock) {
            $stock = $product->getCurrentStock(Auth::user()->restaurant_id);
            if ($stock < $this->cantidad) {
                session()->flash('error', "Stock insuficiente de {$product->name} (disp. {$stock}).");

                return;
            }
        }

        $variant = collect($this->variantes)->firstWhere('id', $this->varianteSeleccionada);
        if ($this->variantes !== [] && $variant === null) {
            session()->flash('error', "Elegí una variante para {$product->name}.");

            return;
        }

        $precio = (float) ($variant['price'] ?? $product->price);
        $variantId = $variant['id'] ?? null;
        $existing = null;
        foreach ($this->items as $i => $item) {
            if ((int) $item['product_id'] === (int) $product->id && ($item['variant_id'] ?? null) === $variantId) {
                $existing = $i;
                break;
            }
        }

        if ($existing !== null) {
            $this->items[$existing]['quantity'] += $this->cantidad;
            $this->items[$existing]['subtotal'] = $this->items[$existing]['quantity'] * $precio;
        } else {
            $this->items[] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'name' => $variant ? $product->name.' · '.$variant['name'] : $product->name,
                'quantity' => $this->cantidad,
                'price' => $precio,
                'subtotal' => $precio * $this->cantidad,
            ];
        }

        $this->reset(['productoSeleccionado', 'varianteSeleccionada', 'variantes', 'cantidad', 'precioActual', 'stockDisponible']);
        $this->cantidad = 1;
    }

    public function bumpQty(int $index, int $delta): void
    {
        if (! isset($this->items[$index])) {
            return;
        }
        $this->items[$index]['quantity'] = max(1, $this->items[$index]['quantity'] + $delta);
        $this->items[$index]['subtotal'] = $this->items[$index]['quantity'] * $this->items[$index]['price'];
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
            'items.*.variant_id' => 'nullable|integer',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $user = Auth::user();

        $payload = [
            'restaurant_id' => $user->restaurant_id,
            'table_id' => $this->mesa,
            'user_id' => $user->id,
            'observations' => $this->observaciones,
            'ensure_table_occupied' => true,
            'idempotency_key' => (string) Str::ulid(),
            'items' => collect($this->items)->map(fn ($item) => [
                'product_id' => $item['product_id'],
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => $item['quantity'],
            ])->all(),
        ];

        try {
            $order = $orderService->createOrder($payload);
            if ($this->enviarCocina) {
                try {
                    $orderService->sendToKitchen($order);
                } catch (\Throwable) {
                    // pedido ya registrado
                }
            }

            session()->flash('success', 'Pedido Nº '.$order->number.' cargado'
                .($this->enviarCocina ? ' y enviado a cocina.' : '.'));
            $this->reset(['mesa', 'items', 'productoSeleccionado', 'varianteSeleccionada', 'variantes', 'cantidad', 'observaciones', 'precioActual', 'stockDisponible', 'busqueda']);
            $this->cantidad = 1;
            $this->enviarCocina = true;
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'No pudimos abrir el pedido. Reintentá.');
        }
    }

    public function getMesasProperty()
    {
        return Table::sortByNumericGroup(
            Table::where('restaurant_id', Auth::user()->restaurant_id)->get()
        );
    }

    public function getProductosProperty()
    {
        $query = Product::where('restaurant_id', Auth::user()->restaurant_id)
            ->where('type', 'PRODUCT')
            ->where('is_active', true)
            ->orderBy('name');

        if ($this->busqueda) {
            $query->where('name', 'like', '%'.$this->busqueda.'%');
        }

        return $query->limit(60)->get();
    }

    public function render()
    {
        return view('livewire.mobile.toma-pedido', [
            'mesas' => $this->mesas,
            'productos' => $this->productos,
        ]);
    }
}
