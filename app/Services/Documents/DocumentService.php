<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Entrepreneur;
use App\Models\User;
use App\Services\Community\OrganizationDirectory;
use App\Services\Notifier;
use App\Services\PermissionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Documentos con versiones en disco privado. La descarga pasa siempre por canView().
 */
final class DocumentService
{
    public const DISK = 'local';

    public const MAX_KB = 20480;

    public const MIMES = 'pdf,png,jpg,jpeg,webp,svg,doc,docx,xls,xlsx,ppt,pptx,odt,ods,txt,zip';

    public function __construct(
        private readonly PermissionService $permissions,
        private readonly Notifier $notifier,
        private readonly OrganizationDirectory $directory,
    ) {}

    public function canManage(User $user): bool
    {
        return $this->permissions->allowed($user, 'documents.update');
    }

    public function canView(User $user, Document $document): bool
    {
        if ((int) $document->restaurant_id !== (int) $user->restaurant_id && ! $user->isSuperAdmin()) {
            return false;
        }
        if ($this->canManage($user)) {
            return true;
        }
        if ($document->entrepreneur_id !== null && $document->entrepreneur()->withoutGlobalScopes()->value('user_id') === $user->id) {
            return true; // el emprendedor ve los suyos, publicados o no
        }

        return $document->status === 'PUBLICADO' && $document->visibility === 'MIEMBROS'
            && $this->permissions->allowed($user, 'documents.view');
    }

    public function addVersion(Document $document, UploadedFile $file, User $by, ?string $note = null): DocumentVersion
    {
        return DB::transaction(function () use ($document, $file, $by, $note) {
            $next = ((int) DocumentVersion::query()->where('document_id', $document->id)->lockForUpdate()->max('version')) + 1;
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
            $path = $file->storeAs("documents/{$document->restaurant_id}/{$document->id}", "v{$next}-".Str::random(12).".{$ext}", self::DISK);

            return $document->versions()->create([
                'version' => $next, 'path' => $path, 'original_name' => mb_substr($file->getClientOriginalName(), 0, 250),
                'mime' => $file->getClientMimeType(), 'size' => (int) $file->getSize(), 'note' => $note, 'uploaded_by' => $by->id,
            ]);
        });
    }

    public function publish(Document $document): int
    {
        $wasPublished = $document->status === 'PUBLICADO';
        $document->update(['status' => 'PUBLICADO', 'published_at' => $document->published_at ?? now()]);
        if ($wasPublished || $document->visibility !== 'MIEMBROS') {
            return 0;
        }

        $users = $this->directory->members((int) $document->restaurant_id)
            ->filter(fn ($m) => $this->permissions->allowedForRole($m['role'], 'documents.view'))
            ->map(fn ($m) => $m['user']);

        return $this->notifier->sendMany($users, 'documentos', "Nuevo documento: {$document->title}",
            Document::CATEGORIES[$document->category] ?? 'Documento', route('documents.show', $document),
            "document:{$document->id}", (int) $document->restaurant_id);
    }

    /**
     * Genera el manual de marca del emprendedor en PDF como nueva versión de su documento
     * «Manual de marca» (lo crea si no existe).
     */
    public function generateBrandManual(Entrepreneur $entrepreneur, User $by): DocumentVersion
    {
        $brand = $entrepreneur->brand ?? [];
        $logo = null;
        $logoDoc = Document::query()->where('restaurant_id', $entrepreneur->restaurant_id)
            ->where('entrepreneur_id', $entrepreneur->id)->where('category', 'LOGO')->latest('id')->first();
        $logoVersion = $logoDoc?->latestVersion();
        if ($logoVersion && in_array($logoVersion->mime, ['image/png', 'image/jpeg', 'image/webp'], true) && Storage::disk(self::DISK)->exists($logoVersion->path)) {
            $logo = 'data:'.$logoVersion->mime.';base64,'.base64_encode((string) Storage::disk(self::DISK)->get($logoVersion->path));
        }

        $pdf = Pdf::loadView('documents.brand-manual-pdf', [
            'entrepreneur' => $entrepreneur, 'brand' => $brand, 'logo' => $logo,
            'colors' => array_values(array_filter((array) ($brand['colors'] ?? []), fn ($c) => is_string($c) && preg_match('/^#[0-9a-fA-F]{6}$/', $c))),
        ])->setPaper('a4');
        $binary = $pdf->output();

        return DB::transaction(function () use ($entrepreneur, $by, $binary) {
            $document = Document::query()->firstOrCreate(
                ['restaurant_id' => $entrepreneur->restaurant_id, 'entrepreneur_id' => $entrepreneur->id, 'category' => 'MANUAL_MARCA'],
                ['title' => "Manual de marca — {$entrepreneur->business_name}", 'visibility' => 'GESTION', 'status' => 'BORRADOR', 'created_by' => $by->id],
            );
            $next = ((int) DocumentVersion::query()->where('document_id', $document->id)->lockForUpdate()->max('version')) + 1;
            $path = "documents/{$document->restaurant_id}/{$document->id}/v{$next}-".Str::random(12).'.pdf';
            Storage::disk(self::DISK)->put($path, $binary);

            return $document->versions()->create([
                'version' => $next, 'path' => $path, 'original_name' => Str::slug($entrepreneur->business_name).'-manual-de-marca.pdf',
                'mime' => 'application/pdf', 'size' => strlen($binary), 'note' => 'Generado desde la ficha del emprendedor', 'uploaded_by' => $by->id,
            ]);
        });
    }
}
