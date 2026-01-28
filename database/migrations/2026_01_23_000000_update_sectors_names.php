<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar nombres de sectores existentes
        DB::table('sectors')->where('name', 'Salón Principal')->update(['name' => 'Salón principal']);
        DB::table('sectors')->where('name', 'Terraza')->update(['name' => 'Patio murales']);
        DB::table('sectors')->where('name', 'Sector VIP')->update(['name' => 'Patio Diego']);
        
        // Crear sector Barra si no existe
        $restaurants = DB::table('restaurants')->pluck('id');
        foreach ($restaurants as $restaurantId) {
            $barraExists = DB::table('sectors')
                ->where('restaurant_id', $restaurantId)
                ->where('name', 'Barra')
                ->exists();
            
            if (!$barraExists) {
                DB::table('sectors')->insert([
                    'restaurant_id' => $restaurantId,
                    'name' => 'Barra',
                    'description' => 'Barra fija con 4 lugares disponibles',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios
        DB::table('sectors')->where('name', 'Salón principal')->update(['name' => 'Salón Principal']);
        DB::table('sectors')->where('name', 'Patio murales')->update(['name' => 'Terraza']);
        DB::table('sectors')->where('name', 'Patio Diego')->update(['name' => 'Sector VIP']);
        DB::table('sectors')->where('name', 'Barra')->delete();
    }
};

