<?php

declare(strict_types=1);

namespace App\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Controlador base de la capa App\Controllers (API y futuras rutas web migradas).
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
}
