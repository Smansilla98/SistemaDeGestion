<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DiscountType;
use App\Models\Restaurant;

class DiscountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los restaurantes o crear uno de ejemplo
        $restaurants = Restaurant::all();
        
        if ($restaurants->isEmpty()) {
            // Si no hay restaurantes, crear uno de ejemplo
            $restaurant = Restaurant::create([
                'name' => 'Restaurante Ejemplo',
                'address' => 'Av. Principal 123',
                'phone' => '+54 11 1234-5678',
                'email' => 'info@restaurante.com',
                'is_active' => true,
            ]);
            $restaurants = collect([$restaurant]);
        }

        // Crear tipos de descuentos para cada restaurante
        foreach ($restaurants as $restaurant) {
            $discountTypes = [
                [
                    'name' => 'Alumno UNLA',
                    'percentage' => 20.00,
                    'description' => 'Descuento del 20% para alumnos de la Universidad Nacional de Lanús',
                    'is_active' => true,
                ],
                [
                    'name' => 'Artista',
                    'percentage' => 50.00,
                    'description' => 'Descuento del 50% para artistas',
                    'is_active' => true,
                ],
            ];

            foreach ($discountTypes as $discountTypeData) {
                DiscountType::firstOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'name' => $discountTypeData['name'],
                    ],
                    $discountTypeData
                );
            }
        }
    }
}

