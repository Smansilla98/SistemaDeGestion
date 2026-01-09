<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Registrar acción en el log de auditoría
     */
    public function log(string $action, ?string $modelType = null, ?int $modelId = null, ?array $changes = null): AuditLog
    {
        return AuditLog::create([
            'restaurant_id' => Auth::user()->restaurant_id ?? null,
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'changes' => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Registrar creación de modelo
     */
    public function logCreate(string $modelType, int $modelId, array $attributes): void
    {
        $this->log("created", $modelType, $modelId, ['attributes' => $attributes]);
    }

    /**
     * Registrar actualización de modelo
     */
    public function logUpdate(string $modelType, int $modelId, array $oldAttributes, array $newAttributes): void
    {
        $changes = [];
        foreach ($newAttributes as $key => $value) {
            if (!isset($oldAttributes[$key]) || $oldAttributes[$key] !== $value) {
                $changes[$key] = [
                    'old' => $oldAttributes[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        if (!empty($changes)) {
            $this->log("updated", $modelType, $modelId, $changes);
        }
    }

    /**
     * Registrar eliminación de modelo
     */
    public function logDelete(string $modelType, int $modelId): void
    {
        $this->log("deleted", $modelType, $modelId);
    }
}

