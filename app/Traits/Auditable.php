<?php

namespace App\Traits;

use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Registrar acción en auditoría
     */
    protected function audit(string $action, ?string $modelType = null, ?int $modelId = null, ?array $changes = null): void
    {
        if (!Auth::check()) {
            return;
        }

        try {
            $auditService = app(AuditService::class);
            $auditService->log($action, $modelType, $modelId, $changes);
        } catch (\Exception $e) {
            // No fallar la operación si falla la auditoría
            \Log::warning('Error al registrar auditoría: ' . $e->getMessage());
        }
    }

    /**
     * Registrar creación
     */
    protected function auditCreate($model, array $attributes): void
    {
        $this->audit('created', get_class($model), $model->id, ['attributes' => $attributes]);
    }

    /**
     * Registrar actualización
     */
    protected function auditUpdate($model, array $oldAttributes, array $newAttributes): void
    {
        $changes = [];
        foreach ($newAttributes as $key => $value) {
            if (!isset($oldAttributes[$key]) || $oldAttributes[$key] != $value) {
                $changes[$key] = [
                    'old' => $oldAttributes[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        if (!empty($changes)) {
            $this->audit('updated', get_class($model), $model->id, $changes);
        }
    }

    /**
     * Registrar eliminación
     */
    protected function auditDelete($model): void
    {
        $this->audit('deleted', get_class($model), $model->id);
    }
}

