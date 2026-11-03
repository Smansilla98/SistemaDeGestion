<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/** Backups de la base: crear uno en el momento y descargar los guardados. */
class BackupController extends Controller
{
    public function store(DatabaseBackupService $backups): RedirectResponse
    {
        abort_unless(DatabaseBackupService::canDownload(auth()->user()), 403);
        try {
            $r = $backups->create();
        } catch (Throwable $e) {
            return back()->with('error', 'No se pudo hacer el backup: '.$e->getMessage());
        }

        return back()->with('success', sprintf('Backup listo: %d tablas, %.1f MB.', $r['tables'], $r['bytes'] / 1048576));
    }

    public function download(string $file): StreamedResponse
    {
        abort_unless(DatabaseBackupService::canDownload(auth()->user()), 403);
        $path = DatabaseBackupService::DIR.'/'.basename($file);
        abort_unless(in_array($path, DatabaseBackupService::files(), true), 404);

        return Storage::disk(DatabaseBackupService::DISK)->download($path);
    }
}
