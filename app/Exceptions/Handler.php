<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log todas las excepciones importantes
            if ($this->shouldReport($e)) {
                Log::error('Exception no manejada', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Manejar errores de validación PRIMERO (antes de otros errores)
        if ($exception instanceof ValidationException) {
            return $this->handleValidationException($request, $exception);
        }

        // Manejar errores de base de datos
        if ($exception instanceof QueryException) {
            return $this->handleQueryException($request, $exception);
        }

        // Manejar errores 404
        if ($exception instanceof NotFoundHttpException) {
            return $this->handleNotFound($request, $exception);
        }

        // Manejar errores 403
        if ($exception instanceof AccessDeniedHttpException) {
            return $this->handleAccessDenied($request, $exception);
        }

        // Manejar errores HTTP genéricos
        if ($exception instanceof HttpException) {
            return $this->handleHttpException($request, $exception);
        }

        // Para AJAX/API requests, devolver JSON
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $this->getUserFriendlyMessage($exception),
                'error' => config('app.debug') ? $exception->getMessage() : 'Error interno del servidor'
            ], $this->getStatusCode($exception));
        }

        return parent::render($request, $exception);
    }

    /**
     * Manejar errores de validación
     */
    protected function handleValidationException($request, ValidationException $exception)
    {
        // Para peticiones AJAX/JSON, siempre devolver JSON
        if ($request->expectsJson() || $request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación. Por favor, revisa los datos ingresados.',
                'errors' => $exception->errors()
            ], 422);
        }

        // Para peticiones normales, usar el comportamiento por defecto
        return parent::render($request, $exception);
    }

    /**
     * Manejar errores de base de datos
     */
    protected function handleQueryException($request, QueryException $exception)
    {
        $message = 'Error al procesar la solicitud en la base de datos.';

        if (config('app.debug')) {
            $message = $exception->getMessage();
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], 500);
        }

        return response()->view('errors.500', [
            'message' => $message,
            'exception' => $exception
        ], 500);
    }

    /**
     * Manejar errores 404
     */
    protected function handleNotFound($request, NotFoundHttpException $exception)
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Recurso no encontrado'
            ], 404);
        }

        return response()->view('errors.404', [], 404);
    }

    /**
     * Manejar errores 403
     */
    protected function handleAccessDenied($request, AccessDeniedHttpException $exception)
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permiso para realizar esta acción'
            ], 403);
        }

        return response()->view('errors.403', [], 403);
    }

    /**
     * Manejar errores HTTP genéricos
     */
    protected function handleHttpException($request, HttpException $exception)
    {
        $statusCode = $exception->getStatusCode();
        $message = $exception->getMessage() ?: 'Error en la solicitud';

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message
            ], $statusCode);
        }

        return response()->view('errors.generic', [
            'statusCode' => $statusCode,
            'message' => $message
        ], $statusCode);
    }

    /**
     * Obtener mensaje amigable para el usuario
     */
    protected function getUserFriendlyMessage(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return 'Error de validación. Por favor, revisa los datos ingresados.';
        }

        if ($exception instanceof QueryException) {
            return 'Error al procesar la solicitud. Por favor, intenta nuevamente.';
        }

        return 'Ha ocurrido un error. Por favor, intenta nuevamente o contacta al administrador.';
    }

    /**
     * Obtener código de estado HTTP
     */
    protected function getStatusCode(Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        if ($exception instanceof ValidationException) {
            return 422;
        }

        return 500;
    }
}
