<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditService
{
    /**
     * Registrar acción en el log de auditoría.
     *
     * Escribe en las dos formas del esquema (histórica y nueva) según las columnas
     * que realmente existan, y nunca lanza: este método se invoca desde observers
     * que corren dentro de la transacción de cobro, y una falla de auditoría no
     * puede tumbar el cierre de una mesa (SQLSTATE 1364 en `action`).
     */
    public function log(string $action, ?string $modelType = null, ?int $modelId = null, ?array $changes = null): ?AuditLog
    {
        try {
            if (! Schema::hasTable('audit_logs')) {
                return null;
            }

            $payload = [
                'restaurant_id' => Auth::user()->restaurant_id ?? null,
                'user_id' => Auth::id(),
                'created_at' => now(),
            ];

            if (AuditLog::hasAuditColumn('action')) {
                $payload['action'] = $action;
                $payload['model_type'] = $modelType;
                $payload['model_id'] = $modelId;
                $payload['changes'] = $changes;
            }

            if (AuditLog::hasAuditColumn('ip_address')) {
                $payload['ip_address'] = request()?->ip();
                $payload['user_agent'] = request()?->userAgent();
            }

            if (AuditLog::hasAuditColumn('auditable_type')) {
                $payload['auditable_type'] = $modelType;
                $payload['auditable_id'] = $modelId;
                $payload['event'] = $action;
                $payload['new_values'] = $changes;
                $payload['ip'] = request()?->ip();
            }

            return AuditLog::create($payload);
        } catch (\Throwable $e) {
            Log::warning('AuditService::log falló', [
                'action' => $action,
                'model_type' => $modelType,
                'model_id' => $modelId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Registrar creación de modelo
     */
    public function logCreate(string $modelType, int $modelId, array $attributes): void
    {
        $this->log('created', $modelType, $modelId, ['attributes' => $attributes]);
    }

    /**
     * Registrar actualización de modelo
     */
    public function logUpdate(string $modelType, int $modelId, array $oldAttributes, array $newAttributes): void
    {
        $changes = [];
        foreach ($newAttributes as $key => $value) {
            if (! isset($oldAttributes[$key]) || $oldAttributes[$key] !== $value) {
                $changes[$key] = [
                    'old' => $oldAttributes[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        if (! empty($changes)) {
            $this->log('updated', $modelType, $modelId, $changes);
        }
    }

    /**
     * Registrar eliminación de modelo
     */
    public function logDelete(string $modelType, int $modelId): void
    {
        $this->log('deleted', $modelType, $modelId);
    }
}
