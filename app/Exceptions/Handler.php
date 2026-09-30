<?php

namespace App\Exceptions;

use App\Core\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

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
        // Sin Log::error duplicado con trace completo: Laravel ya reporta.
        // Un segundo dump (sobre todo QueryException/OOM) puede agotar memoria en Monolog.
    }

    /**
     * Report or log an exception without blowing up Monolog on huge contexts.
     */
    public function report(Throwable $e): void
    {
        if ($this->isMemoryExhaustion($e)) {
            $this->writeOomBreadcrumb($e);

            return;
        }

        try {
            parent::report($e);
        } catch (Throwable $loggingFailure) {
            // Nunca dejar que el logger mate el request con otro FatalError.
            $this->writeOomBreadcrumb($loggingFailure);
        }
    }

    /**
     * Laravel incluye `['exception' => $e]` por defecto; Monolog intenta
     * normalizar el objeto (SQL + bindings + trace) y puede OOM (128MB).
     *
     * @return array<string, mixed>
     */
    protected function buildExceptionContext(Throwable $e): array
    {
        return array_merge(
            $this->exceptionContext($e),
            $this->context()
        );
    }

    /**
     * Contexto mínimo: evita serializar bindings SQL / modelos / request gigantes.
     *
     * @return array<string, mixed>
     */
    protected function context(): array
    {
        try {
            return array_filter([
                'user_id' => auth()->id(),
                'restaurant_id' => auth()->user()->restaurant_id ?? null,
                'url' => request()->path(),
            ]);
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function exceptionContext(Throwable $e): array
    {
        return [
            'exception' => $e::class,
            'message' => $this->truncate((string) $e->getMessage(), 1500),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ApiException) {
            return ApiResponse::error(
                $exception->getMessage(),
                $exception->getStatusCode(),
                $exception->getErrorCode(),
                $exception->getErrors()
            );
        }

        if ($exception instanceof ValidationException) {
            return $this->handleValidationException($request, $exception);
        }

        if ($exception instanceof QueryException) {
            return $this->handleQueryException($request, $exception);
        }

        if ($exception instanceof NotFoundHttpException) {
            return $this->handleNotFound($request, $exception);
        }

        if ($exception instanceof AccessDeniedHttpException) {
            return $this->handleAccessDenied($request, $exception);
        }

        if ($exception instanceof HttpException) {
            return $this->handleHttpException($request, $exception);
        }

        if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            $msg = $this->getUserFriendlyMessage($exception);
            $debug = config('app.debug') ? $this->truncate($exception->getMessage(), 500) : null;

            return response()->json(array_filter([
                'success' => false,
                'message' => $msg,
                'code' => 'SERVER_ERROR',
                'error' => $debug,
            ]), $this->getStatusCode($exception));
        }

        return parent::render($request, $exception);
    }

    protected function handleValidationException($request, ValidationException $exception)
    {
        if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return ApiResponse::error(
                'Error de validación.',
                422,
                'VALIDATION_ERROR',
                $exception->errors()
            );
        }

        return parent::render($request, $exception);
    }

    protected function handleQueryException($request, QueryException $exception)
    {
        // report() ya corre antes de render; no re-loguear SQL/bindings.

        $code = (int) ($exception->errorInfo[1] ?? 0);
        $message = match ($code) {
            1062 => 'Ese registro ya existe. Refrescá la pantalla y reintentá.',
            1213, 1205 => 'El sistema está ocupado, reintentá en unos segundos.',
            default => 'No pudimos completar la operación. Avisá al encargado.',
        };

        if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'code' => 'DATABASE_ERROR',
                'ref' => $request->header('X-Request-Id'),
            ], $code === 1062 ? 409 : 500);
        }

        return back()->withErrors(['general' => $message])->with('error', $message);
    }

    protected function handleNotFound($request, NotFoundHttpException $exception)
    {
        if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson()) {
            return ApiResponse::error('Recurso no encontrado', 404, 'NOT_FOUND');
        }

        return response()->view('errors.404', [], 404);
    }

    protected function handleAccessDenied($request, AccessDeniedHttpException $exception)
    {
        if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson()) {
            return ApiResponse::error('No tienes permiso para realizar esta acción', 403, 'FORBIDDEN');
        }

        return response()->view('errors.403', [], 403);
    }

    protected function handleHttpException($request, HttpException $exception)
    {
        $statusCode = $exception->getStatusCode();
        $message = $exception->getMessage() ?: 'Error en la solicitud';

        if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson()) {
            return ApiResponse::error($message, $statusCode, 'HTTP_ERROR');
        }

        return response()->view('errors.generic', [
            'statusCode' => $statusCode,
            'message' => $message,
        ], $statusCode);
    }

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

    private function isMemoryExhaustion(Throwable $e): bool
    {
        if ($e instanceof FatalError && str_contains($e->getMessage(), 'Allowed memory size')) {
            return true;
        }

        return str_contains($e->getMessage(), 'Allowed memory size');
    }

    private function writeOomBreadcrumb(Throwable $e): void
    {
        $line = sprintf(
            "[%s] OOM/log-fail %s: %s in %s:%d\n",
            date('c'),
            $e::class,
            $this->truncate($e->getMessage(), 400),
            $e->getFile(),
            $e->getLine()
        );

        @file_put_contents(storage_path('logs/oom.log'), $line, FILE_APPEND | LOCK_EX);
    }

    private function truncate(string $value, int $max): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max).'…';
    }
}
