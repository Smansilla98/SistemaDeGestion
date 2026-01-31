<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sector;
use App\Models\Table;
use App\Models\Restaurant;

class PredeterminedLayoutsSeeder extends Seeder
{
    /**
     * Crear layouts predeterminados realistas para el salón
     */
    public function run(): void
    {
        $restaurant = Restaurant::first();
        
        if (!$restaurant) {
            $this->command->warn('No se encontró restaurante. Ejecuta DatabaseSeeder primero.');
            return;
        }

        // Layout para Salón Principal
        $salonPrincipal = Sector::where('name', 'Salón Principal')
            ->orWhere('name', 'Salón principal')
            ->where('restaurant_id', $restaurant->id)
            ->first();

        if ($salonPrincipal) {
            $this->createSalonPrincipalLayout($salonPrincipal, $restaurant);
        }

        // Layout para Patio murales
        $patioMurales = Sector::where('name', 'Patio murales')
            ->where('restaurant_id', $restaurant->id)
            ->first();

        if ($patioMurales) {
            $this->createPatioMuralesLayout($patioMurales, $restaurant);
        }

        // Layout para Patio Diego
        $patioDiego = Sector::where('name', 'Patio Diego')
            ->where('restaurant_id', $restaurant->id)
            ->first();

        if ($patioDiego) {
            $this->createPatioDiegoLayout($patioDiego, $restaurant);
        }

        $this->command->info('✅ Layouts predeterminados creados exitosamente!');
    }

    /**
     * Crear layout realista para Salón Principal
     */
    private function createSalonPrincipalLayout(Sector $sector, Restaurant $restaurant): void
    {
        // Eliminar mesas existentes del sector
        Table::where('sector_id', $sector->id)->delete();

        // Layout: 20 mesas de 4 personas + 4 lugares de barra (2 personas cada uno)
        // Distribución en 5 filas de 4 mesas cada una
        // Barra fija a la izquierda con 4 lugares
        // Escenario al fondo

        $tables = [
            // Fila 1 - 4 mesas de 4 personas
            ['number' => 'Mesa 1', 'capacity' => 4, 'x' => 150, 'y' => 150],
            ['number' => 'Mesa 2', 'capacity' => 4, 'x' => 300, 'y' => 150],
            ['number' => 'Mesa 3', 'capacity' => 4, 'x' => 450, 'y' => 150],
            ['number' => 'Mesa 4', 'capacity' => 4, 'x' => 600, 'y' => 150],

            // Fila 2 - 4 mesas de 4 personas
            ['number' => 'Mesa 5', 'capacity' => 4, 'x' => 150, 'y' => 280],
            ['number' => 'Mesa 6', 'capacity' => 4, 'x' => 300, 'y' => 280],
            ['number' => 'Mesa 7', 'capacity' => 4, 'x' => 450, 'y' => 280],
            ['number' => 'Mesa 8', 'capacity' => 4, 'x' => 600, 'y' => 280],

            // Fila 3 - 4 mesas de 4 personas
            ['number' => 'Mesa 9', 'capacity' => 4, 'x' => 150, 'y' => 410],
            ['number' => 'Mesa 10', 'capacity' => 4, 'x' => 300, 'y' => 410],
            ['number' => 'Mesa 11', 'capacity' => 4, 'x' => 450, 'y' => 410],
            ['number' => 'Mesa 12', 'capacity' => 4, 'x' => 600, 'y' => 410],

            // Fila 4 - 4 mesas de 4 personas
            ['number' => 'Mesa 13', 'capacity' => 4, 'x' => 150, 'y' => 540],
            ['number' => 'Mesa 14', 'capacity' => 4, 'x' => 300, 'y' => 540],
            ['number' => 'Mesa 15', 'capacity' => 4, 'x' => 450, 'y' => 540],
            ['number' => 'Mesa 16', 'capacity' => 4, 'x' => 600, 'y' => 540],

            // Fila 5 - 4 mesas de 4 personas
            ['number' => 'Mesa 17', 'capacity' => 4, 'x' => 150, 'y' => 670],
            ['number' => 'Mesa 18', 'capacity' => 4, 'x' => 300, 'y' => 670],
            ['number' => 'Mesa 19', 'capacity' => 4, 'x' => 450, 'y' => 670],
            ['number' => 'Mesa 20', 'capacity' => 4, 'x' => 600, 'y' => 670],

            // Barra - 4 lugares de 2 personas cada uno
            ['number' => 'Barra 1', 'capacity' => 2, 'x' => 50, 'y' => 150],
            ['number' => 'Barra 2', 'capacity' => 2, 'x' => 50, 'y' => 280],
            ['number' => 'Barra 3', 'capacity' => 2, 'x' => 50, 'y' => 410],
            ['number' => 'Barra 4', 'capacity' => 2, 'x' => 50, 'y' => 540],
        ];

        foreach ($tables as $tableData) {
            Table::create([
                'restaurant_id' => $restaurant->id,
                'sector_id' => $sector->id,
                'number' => $tableData['number'],
                'capacity' => $tableData['capacity'],
                'status' => 'LIBRE',
                'position_x' => $tableData['x'],
                'position_y' => $tableData['y'],
            ]);
        }

        // Guardar layout_config con escenario y barra
        $sector->update([
            'layout_config' => [
                'fixtures' => [
                    [
                        'id' => 'escenario',
                        'type' => 'stage',
                        'position_x' => 400,
                        'position_y' => 50,
                    ],
                    [
                        'id' => 'barra',
                        'type' => 'bar',
                        'position_x' => 0,
                        'position_y' => 0,
                    ],
                ],
            ],
        ]);

        $this->command->info("   ✓ Salón Principal: 20 mesas de 4 personas + 4 lugares de barra creadas");
    }

    /**
     * Crear layout para Patio murales
     */
    private function createPatioMuralesLayout(Sector $sector, Restaurant $restaurant): void
    {
        Table::where('sector_id', $sector->id)->delete();

        // 4 mesas para 6 personas cada una
        $tables = [
            ['number' => 'Patio 1', 'capacity' => 6, 'x' => 200, 'y' => 200],
            ['number' => 'Patio 2', 'capacity' => 6, 'x' => 400, 'y' => 200],
            ['number' => 'Patio 3', 'capacity' => 6, 'x' => 200, 'y' => 400],
            ['number' => 'Patio 4', 'capacity' => 6, 'x' => 400, 'y' => 400],
        ];

        foreach ($tables as $tableData) {
            Table::create([
                'restaurant_id' => $restaurant->id,
                'sector_id' => $sector->id,
                'number' => $tableData['number'],
                'capacity' => $tableData['capacity'],
                'status' => 'LIBRE',
                'position_x' => $tableData['x'],
                'position_y' => $tableData['y'],
            ]);
        }

        $this->command->info("   ✓ Patio murales: 4 mesas de 6 personas creadas");
    }

    /**
     * Crear layout para Patio Diego
     */
    private function createPatioDiegoLayout(Sector $sector, Restaurant $restaurant): void
    {
        Table::where('sector_id', $sector->id)->delete();

        // 4 lugares para barra exterior + 4 mesas de 5 personas
        $tables = [
            // Barra exterior - 4 lugares
            ['number' => 'Barra Exterior 1', 'capacity' => 1, 'x' => 100, 'y' => 150],
            ['number' => 'Barra Exterior 2', 'capacity' => 1, 'x' => 100, 'y' => 250],
            ['number' => 'Barra Exterior 3', 'capacity' => 1, 'x' => 100, 'y' => 350],
            ['number' => 'Barra Exterior 4', 'capacity' => 1, 'x' => 100, 'y' => 450],

            // Mesas de 5 personas
            ['number' => 'Diego 1', 'capacity' => 5, 'x' => 300, 'y' => 200],
            ['number' => 'Diego 2', 'capacity' => 5, 'x' => 500, 'y' => 200],
            ['number' => 'Diego 3', 'capacity' => 5, 'x' => 300, 'y' => 400],
            ['number' => 'Diego 4', 'capacity' => 5, 'x' => 500, 'y' => 400],
        ];

        foreach ($tables as $tableData) {
            Table::create([
                'restaurant_id' => $restaurant->id,
                'sector_id' => $sector->id,
                'number' => $tableData['number'],
                'capacity' => $tableData['capacity'],
                'status' => 'LIBRE',
                'position_x' => $tableData['x'],
                'position_y' => $tableData['y'],
            ]);
        }

        $this->command->info("   ✓ Patio Diego: 4 lugares de barra exterior + 4 mesas de 5 personas creadas");
    }
}

