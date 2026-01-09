<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Printer extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'name',
        'type', // 'kitchen', 'bar', 'cashier', 'invoice'
        'driver', // 'network', 'usb', 'file'
        'connection_type', // 'network', 'usb', 'file'
        'ip_address',
        'port',
        'path', // Para impresoras de archivo
        'is_active',
        'paper_width', // Ancho del papel en mm (58, 80)
        'auto_print', // Imprimir automáticamente
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_print' => 'boolean',
        'port' => 'integer',
        'paper_width' => 'integer',
    ];

    /**
     * Relación con restaurante
     */
    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}

