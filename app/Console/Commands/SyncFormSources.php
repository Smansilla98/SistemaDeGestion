<?php

namespace App\Console\Commands;

use App\Models\FormSource;
use App\Services\Forms\FormImportService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Sincroniza los formularios de Google con sincronización automática activada.
 */
class SyncFormSources extends Command
{
    protected $signature = 'forms:sync';

    protected $description = 'Importa las respuestas nuevas de los formularios de Google conectados';

    public function handle(FormImportService $service): int
    {
        $sources = FormSource::withoutGlobalScopes()->where('is_active', true)->where('auto_sync', true)->whereNotNull('sheet_url')->whereNotNull('mapping')->get();
        foreach ($sources as $source) {
            try {
                $r = $service->sync($source);
                $this->line("{$source->name}: {$r['imported']} nuevas, {$r['errors']} para revisar.");
            } catch (ValidationException $e) {
                $this->warn("{$source->name}: ".collect($e->errors())->flatten()->first());
            }
        }

        return self::SUCCESS;
    }
}
