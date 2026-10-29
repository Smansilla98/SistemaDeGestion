<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AgendaNotice;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Workshop;
use App\Models\WorkshopEnrollment;
use App\Services\Community\ContactService;
use App\Services\Community\WorkshopService;
use App\Support\PublicSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Web pública del espacio: talleres abiertos, próximas fechas e inscripción online.
 */
class PublicSiteController extends Controller
{
    /** Avisos de la agenda que se muestran al público (el resto es interno). */
    private const PUBLIC_NOTICE_KINDS = ['FECHA_ESPECIAL', 'FERIA'];

    public function home(): View
    {
        $org = PublicSite::organization();

        return view('public.home', [
            'org' => $org,
            'info' => PublicSite::info($org),
            'workshops' => $org ? $this->openWorkshops($org) : collect(),
            'upcoming' => $org ? $this->upcoming($org) : collect(),
        ]);
    }

    public function workshop(Workshop $workshop): View
    {
        $org = $this->publicOrgFor($workshop);
        $workshop->load(['schedules', 'teacher', 'category']);

        return view('public.workshop', [
            'org' => $org,
            'info' => PublicSite::info($org),
            'workshop' => $workshop,
            'spotsLeft' => $workshop->capacity === null ? null : max(0, $workshop->capacity - $workshop->activeCount()),
        ]);
    }

    public function enroll(Request $request, Workshop $workshop, ContactService $contacts, WorkshopService $workshops): RedirectResponse
    {
        $org = $this->publicOrgFor($workshop);

        // Campo trampa: las personas no lo ven, los bots lo completan.
        if (filled($request->input('website'))) {
            return redirect()->route('public.workshop', $workshop)->with('enrolled', 'Recibimos tu inscripción.');
        }

        $data = $request->validate([
            'name' => 'required|string|min:3|max:120',
            'phone' => 'required|string|min:6|max:40',
            'email' => 'nullable|email|max:150',
            'note' => 'nullable|string|max:500',
            'accepts_marketing' => 'nullable|boolean',
        ], [], ['name' => 'nombre y apellido', 'phone' => 'teléfono', 'note' => 'comentario']);

        $contact = $contacts->findOrCreate((int) $org->id, $data['name'], $data['email'] ?? null, $data['phone'],
            'ALUMNO', (bool) ($data['accepts_marketing'] ?? false));

        try {
            $enrollment = $workshops->enroll($workshop, $contact, $data['note'] ?? null, null, 'web de '.$org->name);
        } catch (ValidationException $e) {
            // Mensaje pensado para el público, sin datos internos.
            $already = str_contains(implode(' ', $e->validator->errors()->all()), 'ya está inscripto');
            throw ValidationException::withMessages(['name' => $already
                ? 'Ya tenemos tu inscripción a este taller. Si querés cambiar algo, escribinos.'
                : 'Este taller no está tomando inscripciones en este momento.']);
        }

        $message = $enrollment->status === WorkshopEnrollment::ACTIVA
            ? "¡Listo, {$contact->name}! Tu lugar en {$workshop->name} está confirmado. Te vamos a contactar para los detalles."
            : "Gracias, {$contact->name}. El taller está completo: quedaste en lista de espera (#{$enrollment->waitlist_position}) y te avisamos si se libera un lugar.";

        return redirect()->route('public.workshop', $workshop)->with('enrolled', $message);
    }

    /** El taller tiene que ser de la organización de la web y estar abierto al público. */
    private function publicOrgFor(Workshop $workshop): Restaurant
    {
        $org = PublicSite::organization();
        abort_unless($org && (int) $workshop->restaurant_id === (int) $org->id
            && in_array($workshop->status, PublicSite::OPEN_STATUSES, true), 404);

        return $org;
    }

    /** @return Collection<int, Workshop> */
    private function openWorkshops(Restaurant $org): Collection
    {
        return Workshop::withoutGlobalScopes()->where('restaurant_id', $org->id)
            ->whereIn('status', PublicSite::OPEN_STATUSES)
            ->with(['schedules', 'teacher', 'category'])
            ->withCount(['enrollments as active_count' => fn ($q) => $q->where('status', WorkshopEnrollment::ACTIVA)])
            ->orderByRaw("status = 'ABIERTO' desc")->orderBy('name')
            ->get();
    }

    /**
     * Próximos shows, eventos, ferias y fechas especiales (90 días).
     *
     * @return Collection<int, array{date: \Carbon\Carbon, time: ?string, title: string, description: ?string, kind: string, category: ?string, color: ?string}>
     */
    private function upcoming(Restaurant $org): Collection
    {
        $from = now()->startOfDay();
        $to = now()->addDays(90)->endOfDay();

        $events = Event::query()->where('restaurant_id', $org->id)
            ->whereBetween('date', [$from, $to])->where('status', '!=', Event::STATUS_CANCELADO)
            ->with('category')->get()
            ->map(fn (Event $e) => ['date' => $e->date, 'time' => $e->time ? substr((string) $e->time, 0, 5) : null,
                'title' => $e->name, 'description' => $e->description, 'kind' => 'Evento',
                'category' => $e->category?->name, 'color' => $e->category?->color]);

        $notices = AgendaNotice::withoutGlobalScopes()->where('restaurant_id', $org->id)
            ->whereIn('kind', self::PUBLIC_NOTICE_KINDS)->whereBetween('date', [$from, $to])
            ->with('category')->get()
            ->map(fn (AgendaNotice $n) => ['date' => $n->date, 'time' => $n->time ? substr((string) $n->time, 0, 5) : null,
                'title' => $n->title, 'description' => $n->body, 'kind' => AgendaNotice::KINDS[$n->kind] ?? 'Fecha',
                'category' => $n->category?->name, 'color' => $n->category?->color]);

        return $events->concat($notices)->sortBy(fn ($i) => $i['date']->format('Y-m-d').($i['time'] ?? '99'))->values()->take(12);
    }
}
