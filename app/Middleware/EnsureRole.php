<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Middleware\CheckRole;

/**
 * Alias de capa de aplicación sobre CheckRole (validación de roles en web y API).
 */
class EnsureRole extends CheckRole {}
