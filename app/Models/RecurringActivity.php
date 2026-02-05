<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class RecurringActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'name',
        'description',
        'day_of_week',
        'start_time',
        'end_time',
        'expected_attendance',
        'expected_revenue',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
        'expected_attendance' => 'integer',
        'expected_revenue' => 'decimal:2',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Días de la semana
    const DAY_MONDAY = 'MONDAY';
    const DAY_TUESDAY = 'TUESDAY';
    const DAY_WEDNESDAY = 'WEDNESDAY';
    const DAY_THURSDAY = 'THURSDAY';
    const DAY_FRIDAY = 'FRIDAY';
    const DAY_SATURDAY = 'SATURDAY';
    const DAY_SUNDAY = 'SUNDAY';

    /**
     * Relación: Una actividad recurrente pertenece a un restaurante
     */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Obtener instancias de esta actividad para un rango de fechas
     */
    public function getInstancesForDateRange($startDate, $endDate): array
    {
        if (!$this->is_active) {
            return [];
        }

        $instances = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $activityStart = $this->start_date ? Carbon::parse($this->start_date) : $start;
        $activityEnd = $this->end_date ? Carbon::parse($this->end_date) : $end;

        // Ajustar rango
        $actualStart = $activityStart->greaterThan($start) ? $activityStart : $start;
        $actualEnd = $activityEnd->lessThan($end) ? $activityEnd : $end;

        if ($actualStart->greaterThan($actualEnd)) {
            return [];
        }

        // Obtener el día de la semana como número (0=domingo, 1=lunes, etc.)
        $dayNumber = $this->getDayNumber();

        // Encontrar el primer día de la semana objetivo dentro del rango
        $current = $actualStart->copy();
        $foundFirst = false;
        
        while ($current->lte($actualEnd)) {
            $currentDayNumber = $current->dayOfWeek;
            
            // Si es el día correcto o aún no hemos encontrado el primero
            if ($currentDayNumber == $dayNumber || !$foundFirst) {
                if ($currentDayNumber == $dayNumber) {
                    $foundFirst = true;
                    $instances[] = [
                        'date' => $current->format('Y-m-d'),
                        'day_name' => $current->locale('es')->translatedFormat('l'),
                        'time' => $this->start_time,
                        'end_time' => $this->end_time,
                        'name' => $this->name,
                        'description' => $this->description,
                        'expected_attendance' => $this->expected_attendance,
                        'expected_revenue' => $this->expected_revenue,
                    ];
                    // Avanzar a la próxima semana
                    $current->addWeek();
                } else {
                    // Avanzar al siguiente día hasta encontrar el día objetivo
                    $current->addDay();
                }
            } else {
                // Ya encontramos el primero, avanzar semana por semana
                $current->addWeek();
            }
        }

        return $instances;
    }

    /**
     * Obtener número del día de la semana (0=domingo, 1=lunes, etc.)
     */
    private function getDayNumber(): int
    {
        return match($this->day_of_week) {
            self::DAY_SUNDAY => 0,
            self::DAY_MONDAY => 1,
            self::DAY_TUESDAY => 2,
            self::DAY_WEDNESDAY => 3,
            self::DAY_THURSDAY => 4,
            self::DAY_FRIDAY => 5,
            self::DAY_SATURDAY => 6,
            default => 1,
        };
    }

    /**
     * Obtener etiqueta del día
     */
    public function getDayLabel(): string
    {
        return match($this->day_of_week) {
            self::DAY_MONDAY => 'Lunes',
            self::DAY_TUESDAY => 'Martes',
            self::DAY_WEDNESDAY => 'Miércoles',
            self::DAY_THURSDAY => 'Jueves',
            self::DAY_FRIDAY => 'Viernes',
            self::DAY_SATURDAY => 'Sábado',
            self::DAY_SUNDAY => 'Domingo',
            default => 'Lunes',
        };
    }
}

