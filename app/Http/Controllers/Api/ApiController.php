<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * Controlador base de la API JWT (app móvil).
 */
abstract class ApiController extends BaseController
{
    use AuthorizesRequests;
}
