<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

trait OptimisticLocking
{
    /**
     * Actualizar modelo con optimistic locking
     * Previene conflictos cuando dos usuarios editan simultáneamente
     */
    protected function updateWithLock(Model $model, array $attributes, int $expectedVersion = null): bool
    {
        return DB::transaction(function () use ($model, $attributes, $expectedVersion) {
            // Recargar el modelo para obtener la versión actual
            $model->refresh();

            // Si se especifica una versión esperada, verificar
            if ($expectedVersion !== null && isset($model->version)) {
                if ($model->version != $expectedVersion) {
                    throw new \Exception('El registro ha sido modificado por otro usuario. Por favor, recarga la página e intenta nuevamente.');
                }
            }

            // Actualizar atributos
            foreach ($attributes as $key => $value) {
                $model->$key = $value;
            }

            // Incrementar versión si existe
            if (isset($model->version)) {
                $model->version = ($model->version ?? 0) + 1;
            }

            return $model->save();
        });
    }

    /**
     * Verificar si un modelo ha sido modificado
     */
    protected function isModelModified(Model $model, int $expectedVersion): bool
    {
        if (!isset($model->version)) {
            return false;
        }

        $model->refresh();
        return $model->version != $expectedVersion;
    }
}

