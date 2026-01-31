<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\Sector;
use App\Models\Category;
use App\Models\Product;
use App\Models\Table;
use App\Models\CashRegister;
use App\Models\Stock;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear restaurante de ejemplo
        $restaurant = Restaurant::create([
            'name' => 'Restaurante Ejemplo',
            'address' => 'Av. Principal 123',
            'phone' => '+54 11 1234-5678',
            'email' => 'info@restaurante.com',
            'is_active' => true,
        ]);

        // Crear usuarios de ejemplo
        $users = [
            [
                'name' => 'Administrador',
                'email' => 'admin@restaurante.com',
                'password' => Hash::make('admin123'),
                'role' => 'ADMIN',
            ],
            [
                'name' => 'Juan Pérez',
                'email' => 'mozo@restaurante.com',
                'password' => Hash::make('mozo123'),
                'role' => 'MOZO',
            ],
            [
                'name' => 'María García',
                'email' => 'cocina@restaurante.com',
                'password' => Hash::make('cocina123'),
                'role' => 'COCINA',
            ],
            [
                'name' => 'Carlos López',
                'email' => 'caja@restaurante.com',
                'password' => Hash::make('caja123'),
                'role' => 'CAJERO',
            ],
        ];

        foreach ($users as $userData) {
            User::create(array_merge($userData, [
                'restaurant_id' => $restaurant->id,
                'is_active' => true,
            ]));
        }

        // Crear sectores
        $sectors = [
            ['name' => 'Barra', 'description' => 'Barra fija con 4 lugares disponibles'],
            ['name' => 'Salón principal', 'description' => 'Salón principal del restaurante'],
            ['name' => 'Patio murales', 'description' => 'Patio con murales'],
            ['name' => 'Patio Diego', 'description' => 'Patio Diego'],
        ];

        foreach ($sectors as $sectorData) {
            Sector::create(array_merge($sectorData, [
                'restaurant_id' => $restaurant->id,
                'is_active' => true,
            ]));
        }

        // Crear categorías
        $categories = [
            ['name' => 'Entradas', 'display_order' => 1],
            ['name' => 'Platos Principales', 'display_order' => 2],
            ['name' => 'Postres', 'display_order' => 3],
            ['name' => 'Bebidas', 'display_order' => 4],
        ];

        $categoryModels = [];
        foreach ($categories as $categoryData) {
            $categoryModels[] = Category::create(array_merge($categoryData, [
                'restaurant_id' => $restaurant->id,
                'is_active' => true,
            ]));
        }

        // Crear productos
        $products = [
            // Entradas
            ['category' => 0, 'name' => 'Ensalada César', 'price' => 850.00, 'has_stock' => false],
            ['category' => 0, 'name' => 'Bruschettas', 'price' => 650.00, 'has_stock' => false],
            ['category' => 0, 'name' => 'Empanadas (x6)', 'price' => 1200.00, 'has_stock' => true, 'stock_minimum' => 20],
            
            // Platos Principales
            ['category' => 1, 'name' => 'Bife de Chorizo', 'price' => 3500.00, 'has_stock' => true, 'stock_minimum' => 10],
            ['category' => 1, 'name' => 'Pollo al Disco', 'price' => 2800.00, 'has_stock' => true, 'stock_minimum' => 8],
            ['category' => 1, 'name' => 'Pasta Carbonara', 'price' => 2200.00, 'has_stock' => false],
            ['category' => 1, 'name' => 'Pizza Margarita', 'price' => 1800.00, 'has_stock' => true, 'stock_minimum' => 5],
            
            // Postres
            ['category' => 2, 'name' => 'Tiramisú', 'price' => 950.00, 'has_stock' => false],
            ['category' => 2, 'name' => 'Flan Casero', 'price' => 650.00, 'has_stock' => false],
            ['category' => 2, 'name' => 'Helado (x3 bochas)', 'price' => 800.00, 'has_stock' => false],
            
            // Bebidas
            ['category' => 3, 'name' => 'Coca Cola 500ml', 'price' => 450.00, 'has_stock' => true, 'stock_minimum' => 50],
            ['category' => 3, 'name' => 'Agua Mineral', 'price' => 350.00, 'has_stock' => true, 'stock_minimum' => 30],
            ['category' => 3, 'name' => 'Cerveza Artesanal', 'price' => 750.00, 'has_stock' => true, 'stock_minimum' => 40],
        ];

        foreach ($products as $productData) {
            $categoryIndex = $productData['category'];
            $hasStock = $productData['has_stock'] ?? false;
            $minimumStock = $productData['stock_minimum'] ?? 0;

            unset($productData['category'], $productData['stock_minimum']);

            $product = Product::create(array_merge($productData, [
                'restaurant_id' => $restaurant->id,
                'category_id' => $categoryModels[$categoryIndex]->id,
                'is_active' => true,
            ]));

            // 👉 Crear stock SOLO si el producto maneja stock
            if ($hasStock) {
                Stock::create([
                    'restaurant_id' => $restaurant->id,
                    'product_id' => $product->id,
                    'quantity' => rand($minimumStock, $minimumStock + 20),
                    'minimum_stock' => $minimumStock,
                ]);
            }
        }


        // Crear 4 lugares fijos en la Barra (sector separado)
        $sectorBarra = Sector::where('name', 'Barra')->first();
        if ($sectorBarra) {
            for ($i = 1; $i <= 4; $i++) {
                Table::create([
                    'restaurant_id' => $restaurant->id,
                    'sector_id' => $sectorBarra->id,
                    'number' => "Barra {$i}",
                    'capacity' => 1,
                    'status' => 'LIBRE',
                    'position_x' => 50 + ($i * 100),
                    'position_y' => 50,
                ]);
            }
        }

        // Los layouts predeterminados del salón se crearán con PredeterminedLayoutsSeeder
        // para tener una distribución más realista

        // Crear caja registradora
        CashRegister::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Caja Principal',
            'is_active' => true,
        ]);

        $this->command->info('✅ Base de datos poblada exitosamente!');
        $this->command->info('');
        $this->command->info('👤 Usuarios creados:');
        $this->command->info('   - Admin: admin@restaurante.com / admin123');
        $this->command->info('   - Mozo: mozo@restaurante.com / mozo123');
        $this->command->info('   - Cocina: cocina@restaurante.com / cocina123');
        $this->command->info('   - Caja: caja@restaurante.com / caja123');
    }
}
