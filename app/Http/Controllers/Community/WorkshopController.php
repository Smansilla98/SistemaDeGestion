<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\DistributionRule;
use App\Models\Workshop;
use App\Models\WorkshopAttendance;
use App\Models\WorkshopEnrollment;
use App\Models\WorkshopEvaluation;
use App\Models\WorkshopSchedule;
use App\Models\WorkshopSession;
use App\Services\Community\WorkshopService;
use App\Services\Finance\ActivityFinanceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkshopController extends Controller
{
    use OrganizationContext;

    public function __construct(private readonly WorkshopService $service) {}

    public function index(Request $request): View
    {
        $q = Workshop::query()->where('restaurant_id', $this->org())->with(['teacher', 'schedules'])
            ->withCount([
                'enrollments as active_count' => fn ($e) => $e->where('status', WorkshopEnrollment::ACTIVA),
                'enrollments as waiting_count' => fn ($e) => $e->where('status', WorkshopEnrollment::EN_ESPERA),
            ])->orderByRaw("FIELD(status, 'ABIERTO', 'EN_CURSO', 'PROPUESTA', 'CERRADO', 'FINALIZADO')")->orderBy('name');
        if ($this->isTeacherOnly()) {
            $q->whereHas('teacher', fn ($t) => $t->where('user_id', $this->user()->id));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }
        if ($request->filled('categoria')) {
            $q->where('activity_category_id', $request->integer('categoria'));
        }

        return view('workshops.index', ['workshops' => $q->with('category')->get(), 'canCreate' => $this->can('workshops.create'),
            'categories' => \App\Models\ActivityCategory::query()->where('restaurant_id', $this->org())->orderBy('name')->get()]);
    }

    public function create(): View
    {
        $this->requirePermission('workshops.create');

        return view('workshops.form', $this->formData(new Workshop(['status' => 'ABIERTO'])));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('workshops.create');
        $workshop = Workshop::query()->create($this->validated($request) + ['restaurant_id' => $this->org()]);
        $this->syncSchedules($workshop, $request);

        return redirect()->route('workshops.show', $workshop)->with('success', 'Taller creado. Generá las clases desde «Clases».');
    }

    public function edit(Workshop $workshop): View
    {
        $this->ensureOrg($workshop);
        $this->requirePermission('workshops.update');

        return view('workshops.form', $this->formData($workshop));
    }

    public function update(Request $request, Workshop $workshop): RedirectResponse
    {
        $this->ensureOrg($workshop);
        $this->requirePermission('workshops.update');
        $workshop->update($this->validated($request));
        $this->syncSchedules($workshop, $request);

        return redirect()->route('workshops.show', $workshop)->with('success', 'Taller actualizado.');
    }

    public function show(Workshop $workshop, ActivityFinanceService $finance): View
    {
        $this->authorizeView($workshop);
        $workshop->load(['teacher', 'schedules', 'distributionRule']);

        return view('workshops.show', [
            'workshop' => $workshop,
            'enrollments' => $workshop->enrollments()->with('contact')->orderByRaw("FIELD(status, 'ACTIVA', 'EN_ESPERA', 'FINALIZADA', 'CANCELADA')")
                ->orderBy('waitlist_position')->get(),
            'sessions' => $workshop->sessions()->withCount(['attendances as present_count' => fn ($q) => $q->whereIn('status', ['PRESENTE', 'TARDE'])])
                ->whereBetween('date', [today()->subDays(21), today()->addDays(35)])->get(),
            'evaluations' => $workshop->evaluations()->with('contact')->latest('evaluated_on')->get(),
            'absences' => $this->service->consecutiveAbsences($workshop),
            'balances' => $finance->enrollmentBalances($workshop),
            'contacts' => $this->can('workshops.update') ? Contact::query()->where('restaurant_id', $this->org())->where('is_active', true)->orderBy('name')->get(['id', 'name', 'kind']) : collect(),
            'canManage' => $this->can('workshops.update'),
            'canAttend' => $this->can('workshops.attendance'),
            'canFinance' => $this->can('finance.view'),
        ]);
    }

    // ── Inscripciones ─────────────────────────────────────────────────────────

    public function enroll(Request $request, Workshop $workshop): RedirectResponse
    {
        $this->ensureOrg($workshop);
        $this->requirePermission('workshops.update');
        $data = $request->validate(['contact_id' => ['nullable', 'integer'], 'new_name' => ['nullable', 'string', 'max:255'],
            'new_phone' => ['nullable', 'string', 'max:40'], 'new_email' => ['nullable', 'email', 'max:255'], 'note' => ['nullable', 'string', 'max:255']]);

        if (! empty($data['contact_id'])) {
            $contact = Contact::query()->where('restaurant_id', $this->org())->findOrFail($data['contact_id']);
        } else {
            $request->validate(['new_name' => ['required']], ['new_name.required' => 'Elegí un contacto o escribí el nombre de la persona.']);
            $contact = Contact::query()->create(['restaurant_id' => $this->org(), 'kind' => 'ALUMNO', 'name' => $data['new_name'],
                'phone' => $data['new_phone'] ?? null, 'email' => $data['new_email'] ?? null]);
        }
        $enrollment = $this->service->enroll($workshop, $contact, $data['note'] ?? null, null, 'cargada por '.$this->user()->name);

        return back()->with('success', $enrollment->status === WorkshopEnrollment::ACTIVA
            ? "{$contact->name} quedó inscripto/a."
            : "El taller está completo: {$contact->name} quedó en lista de espera (lugar {$enrollment->waitlist_position}).");
    }

    public function cancelEnrollment(Workshop $workshop, WorkshopEnrollment $enrollment): RedirectResponse
    {
        $this->ensureOrg($workshop);
        $this->requirePermission('workshops.update');
        abort_unless($enrollment->workshop_id === $workshop->id, 404);
        $promoted = $this->service->cancel($enrollment);

        return back()->with('success', 'Inscripción cancelada.'.($promoted ? " {$promoted->contact->name} pasó de la lista de espera a inscripto/a." : ''));
    }

    /** Corrige una inscripción mal cargada: datos de la persona, estado, fecha y nota. */
    public function updateEnrollment(Request $request, Workshop $workshop, WorkshopEnrollment $enrollment): RedirectResponse
    {
        $this->ensureOrg($workshop);
        $this->requirePermission('workshops.update');
        abort_unless($enrollment->workshop_id === $workshop->id, 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'phone' => ['nullable', 'string', 'max:40'], 'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', Rule::in(array_keys(WorkshopEnrollment::LABELS))],
            'enrolled_at' => ['nullable', 'date'], 'note' => ['nullable', 'string', 'max:255'],
        ]);

        $contact = Contact::query()->where('restaurant_id', $this->org())->findOrFail($enrollment->contact_id);
        $contact->update(['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null]);

        $extra = '';
        if ($data['status'] === WorkshopEnrollment::CANCELADA && $enrollment->status !== WorkshopEnrollment::CANCELADA) {
            $promoted = $this->service->cancel($enrollment);
            $extra = $promoted ? " {$promoted->contact->name} pasó de la lista de espera a inscripto/a." : '';
        } elseif ($data['status'] !== $enrollment->status) {
            $waitlist = $data['status'] === WorkshopEnrollment::EN_ESPERA
                ? (int) WorkshopEnrollment::query()->where('workshop_id', $workshop->id)->where('status', WorkshopEnrollment::EN_ESPERA)->max('waitlist_position') + 1
                : null;
            $enrollment->update(['status' => $data['status'], 'waitlist_position' => $waitlist,
                'cancelled_at' => null]);
            $active = WorkshopEnrollment::query()->where('workshop_id', $workshop->id)->where('status', WorkshopEnrollment::ACTIVA)->count();
            if ($data['status'] === WorkshopEnrollment::ACTIVA && $workshop->capacity !== null && $active > $workshop->capacity) {
                $extra = " Ojo: el taller quedó con {$active} inscriptos para {$workshop->capacity} lugares.";
            }
        }
        $enrollment->update(['note' => $data['note'] ?? null]
            + (! empty($data['enrolled_at']) ? ['enrolled_at' => CarbonImmutable::parse($data['enrolled_at'])] : []));

        return back()->with('success', "Inscripción de {$contact->name} corregida.".$extra);
    }

    /** Borra una inscripción cargada por error (sin pagos registrados). */
    public function destroyEnrollment(Workshop $workshop, WorkshopEnrollment $enrollment): RedirectResponse
    {
        $this->ensureOrg($workshop);
        $this->requirePermission('workshops.update');
        abort_unless($enrollment->workshop_id === $workshop->id, 404);
        if ($workshop->entries()->whereNull('voided_at')->where('workshop_enrollment_id', $enrollment->id)->exists()) {
            return back()->with('error', 'Esta inscripción tiene pagos registrados: anulalos primero desde Finanzas o cancelala.');
        }
        // Se borra sin avisar a la persona: fue un error de carga, no una baja.
        $name = $enrollment->contact?->name;
        $enrollment->delete();

        return back()->with('success', "Se borró la inscripción de {$name}.");
    }

    // ── Clases ────────────────────────────────────────────────────────────────

    public function generateSessions(Request $request, Workshop $workshop): RedirectResponse
    {
        $this->ensureOrg($workshop);
        $this->requirePermission('workshops.update');
        $data = $request->validate(['from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from']]);
        $n = $this->service->generateSessions($workshop, CarbonImmutable::parse($data['from']), CarbonImmutable::parse($data['to']));

        return back()->with('success', $n > 0 ? "Se crearon {$n} clases." : 'No había clases nuevas para crear en ese período.');
    }

    public function updateSession(Request $request, Workshop $workshop, WorkshopSession $session): RedirectResponse
    {
        $this->ensureOrg($workshop);
        $this->requirePermission('workshops.update');
        abort_unless($session->workshop_id === $workshop->id, 404);
        $data = $request->validate([
            'action' => ['required', Rule::in(['reschedule', 'cancel'])],
            'date' => ['required_if:action,reschedule', 'nullable', 'date'],
            'start_time' => ['required_if:action,reschedule', 'nullable', 'date_format:H:i'],
            'end_time' => ['required_if:action,reschedule', 'nullable', 'date_format:H:i', 'after:start_time'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        if ($data['action'] === 'cancel') {
            $this->service->cancelSession($session, $data['note'] ?? null);

            return back()->with('success', 'Clase suspendida. Se avisó a las personas inscriptas.');
        }
        $this->service->reschedule($session, $data['date'], $data['start_time'], $data['end_time'], $data['note'] ?? null);

        return back()->with('success', 'Clase reprogramada. Se avisó a las personas inscriptas.');
    }

    public function attendance(Workshop $workshop, WorkshopSession $session): View
    {
        $this->authorizeAttendance($workshop, $session);

        return view('workshops.attendance', [
            'workshop' => $workshop, 'session' => $session,
            'enrollments' => $workshop->enrollments()->where('status', WorkshopEnrollment::ACTIVA)->with('contact')->get()->sortBy('contact.name'),
            'current' => $session->attendances()->pluck('status', 'contact_id'),
            'statuses' => WorkshopAttendance::STATUSES,
        ]);
    }

    public function storeAttendance(Request $request, Workshop $workshop, WorkshopSession $session): RedirectResponse
    {
        $this->authorizeAttendance($workshop, $session);
        $data = $request->validate(['status' => ['required', 'array'], 'status.*' => [Rule::in(array_keys(WorkshopAttendance::STATUSES))]]);
        $alerts = $this->service->recordAttendance($session, $data['status'], $this->user());

        return redirect()->route('workshops.show', $workshop)->with('success', 'Asistencia guardada.'.($alerts > 0 ? ' Hay alumnos con varias faltas seguidas: se avisó a la coordinación.' : ''));
    }

    public function storeEvaluation(Request $request, Workshop $workshop): RedirectResponse
    {
        $this->authorizeAttendance($workshop, null);
        $data = $request->validate([
            'contact_id' => ['required', 'integer', Rule::exists('workshop_enrollments', 'contact_id')->where('workshop_id', $workshop->id)],
            'title' => ['required', 'string', 'max:255'], 'grade' => ['nullable', 'string', 'max:30'],
            'comment' => ['nullable', 'string', 'max:2000'], 'evaluated_on' => ['required', 'date'],
        ]);
        WorkshopEvaluation::query()->create($data + ['workshop_id' => $workshop->id, 'recorded_by' => $this->user()->id]);

        return back()->with('success', 'Evaluación registrada.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function authorizeView(Workshop $workshop): void
    {
        $this->ensureOrg($workshop);
        $this->requirePermission('workshops.view');
        if ($this->isTeacherOnly()) {
            abort_unless($workshop->isTaughtBy($this->user()), 403, 'Sólo ves los talleres que dictás.');
        }
    }

    private function authorizeAttendance(Workshop $workshop, ?WorkshopSession $session): void
    {
        $this->authorizeView($workshop);
        $this->requirePermission('workshops.attendance');
        abort_if($session !== null && $session->workshop_id !== $workshop->id, 404);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:5000'],
            'teacher_contact_id' => ['nullable', 'integer', Rule::exists('contacts', 'id')->where('restaurant_id', $this->org())],
            'room' => ['nullable', 'string', 'max:255'], 'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'fee' => ['nullable', 'string', 'max:20'], 'starts_on' => ['nullable', 'date'], 'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'status' => ['required', Rule::in(array_keys(Workshop::STATUSES))],
            'distribution_rule_id' => ['nullable', 'integer', Rule::exists('distribution_rules', 'id')->where('restaurant_id', $this->org())],
            'activity_category_id' => ['nullable', 'integer', Rule::exists('activity_categories', 'id')->where('restaurant_id', $this->org())],
            'schedules' => ['nullable', 'array', 'max:14'],
            'schedules.*.weekday' => ['nullable', 'integer', 'between:1,7'],
            'schedules.*.start_time' => ['nullable', 'date_format:H:i'],
            'schedules.*.end_time' => ['nullable', 'date_format:H:i'],
        ]);
        $data['fee_cents'] = ActivityFinanceService::toCents($data['fee'] ?? 0);
        unset($data['fee'], $data['schedules']);

        return $data;
    }

    private function syncSchedules(Workshop $workshop, Request $request): void
    {
        $rows = collect($request->input('schedules', []))
            ->filter(fn ($r) => ! empty($r['weekday']) && ! empty($r['start_time']) && ! empty($r['end_time']) && $r['end_time'] > $r['start_time']);
        // Se conservan los horarios que no cambiaron (las clases reprogramadas siguen ligadas
        // a su horario original); los quitados se borran y las clases ya creadas quedan.
        $keep = [];
        foreach ($rows as $r) {
            $keep[] = WorkshopSchedule::query()->firstOrCreate([
                'workshop_id' => $workshop->id, 'weekday' => (int) $r['weekday'],
                'start_time' => $r['start_time'].':00', 'end_time' => $r['end_time'].':00',
            ])->id;
        }
        $workshop->schedules()->whereNotIn('id', $keep)->delete();
    }

    private function formData(Workshop $workshop): array
    {
        return [
            'workshop' => $workshop,
            'teachers' => Contact::query()->where('restaurant_id', $this->org())->where('is_active', true)
                ->orderByRaw("kind = 'PROFESOR' DESC")->orderBy('name')->get(['id', 'name', 'kind']),
            'rules' => DistributionRule::query()->where('restaurant_id', $this->org())->orderBy('name')->get(),
            'categories' => \App\Models\ActivityCategory::query()->where('restaurant_id', $this->org())->orderBy('name')->get(),
            'schedules' => $workshop->exists ? $workshop->schedules()->get() : collect(),
        ];
    }
}
