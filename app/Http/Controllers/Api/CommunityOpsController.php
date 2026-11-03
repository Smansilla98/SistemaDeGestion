<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Core\ApiResponse;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopAttendance;
use App\Models\WorkshopEnrollment;
use App\Models\WorkshopSession;
use App\Services\Community\AgendaService;
use App\Services\Community\WorkshopService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * API de la app para espacios culturales: agenda, talleres y asistencia.
 * La agenda de alumnos y profes muestra sólo lo suyo (lo resuelve AgendaService).
 */
final class CommunityOpsController extends ApiController
{
    public function agenda(Request $request, AgendaService $agenda): JsonResponse
    {
        $user = $request->user();
        $rid = (int) ($user->restaurant_id ?? 0);
        if ($rid === 0) {
            return ApiResponse::error('Usuario sin organización asignada', 403, 'NO_RESTAURANT');
        }
        $from = CarbonImmutable::parse($request->string('desde')->toString() ?: today()->toDateString())->startOfDay();
        $days = max(1, min(62, $request->integer('dias', 14)));

        $grouped = $agenda->between($user, $rid, $from, $from->addDays($days - 1)->endOfDay());

        return ApiResponse::success([
            'from' => $from->toDateString(),
            'to' => $from->addDays($days - 1)->toDateString(),
            'days' => $grouped->map(fn ($items, $date) => [
                'date' => $date,
                'items' => collect($items)->map(fn ($i) => [
                    'time' => $i['time'], 'kind' => $i['kind'], 'type' => $i['type'], 'title' => $i['title'],
                    'detail' => $i['detail'], 'cancelled' => (bool) $i['cancelled'],
                    'category' => $i['category'], 'color' => $i['color'],
                ])->values()->all(),
            ])->values()->all(),
        ]);
    }

    public function workshops(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = Workshop::withoutGlobalScopes()->where('restaurant_id', (int) $user->restaurant_id)
            ->whereIn('status', ['ABIERTO', 'EN_CURSO'])->with(['schedules', 'teacher'])
            ->withCount(['enrollments as active_count' => fn ($e) => $e->where('status', WorkshopEnrollment::ACTIVA)])
            ->orderBy('name');
        if ($user->role === User::ROLE_PROFESOR) {
            $q->whereHas('teacher', fn ($t) => $t->where('user_id', $user->id));
        }

        return ApiResponse::success($q->get()->map(fn (Workshop $w) => $this->workshopRow($w))->values()->all());
    }

    public function workshop(Request $request, int $id): JsonResponse
    {
        $workshop = $this->visibleWorkshop($request->user(), $id);
        if ($workshop === null) {
            return ApiResponse::error('Taller no encontrado', 404, 'NOT_FOUND');
        }
        $workshop->load(['schedules', 'teacher']);
        $sessions = $workshop->sessions()->whereBetween('date', [today()->subWeeks(2)->toDateString(), today()->addWeeks(4)->toDateString()])
            ->withCount('attendances')->orderBy('date')->orderBy('start_time')->get();

        return ApiResponse::success($this->workshopRow($workshop) + [
            'description' => $workshop->description,
            'sessions' => $sessions->map(fn (WorkshopSession $s) => [
                'id' => $s->id, 'date' => $s->date->toDateString(), 'start' => substr((string) $s->start_time, 0, 5),
                'end' => substr((string) $s->end_time, 0, 5), 'status' => $s->status, 'note' => $s->note,
                'attendance_taken' => $s->attendances_count > 0,
            ])->values()->all(),
        ]);
    }

    public function attendance(Request $request, int $id, int $session): JsonResponse
    {
        [$workshop, $model] = $this->sessionFor($request->user(), $id, $session);
        if ($model === null) {
            return ApiResponse::error('Clase no encontrada', 404, 'NOT_FOUND');
        }
        $marked = WorkshopAttendance::query()->where('workshop_session_id', $model->id)->pluck('status', 'contact_id');

        return ApiResponse::success([
            'session' => ['id' => $model->id, 'date' => $model->date->toDateString(), 'start' => substr((string) $model->start_time, 0, 5), 'status' => $model->status],
            'statuses' => WorkshopAttendance::STATUSES,
            'students' => $workshop->enrollments()->where('status', WorkshopEnrollment::ACTIVA)->with('contact')->get()
                ->sortBy(fn ($e) => $e->contact->name)->map(fn ($e) => [
                    'contact_id' => $e->contact_id, 'name' => $e->contact->name, 'status' => $marked[$e->contact_id] ?? null,
                ])->values()->all(),
        ]);
    }

    public function storeAttendance(Request $request, WorkshopService $workshops, int $id, int $session): JsonResponse
    {
        [, $model] = $this->sessionFor($request->user(), $id, $session);
        if ($model === null) {
            return ApiResponse::error('Clase no encontrada', 404, 'NOT_FOUND');
        }
        $data = $request->validate([
            'statuses' => ['required', 'array'],
            'statuses.*' => ['required', Rule::in(array_keys(WorkshopAttendance::STATUSES))],
        ]);
        $alerts = $workshops->recordAttendance($model, $data['statuses'], $request->user());

        return ApiResponse::success(['saved' => count($data['statuses']), 'alerts' => $alerts], 200, 'Asistencia guardada.');
    }

    /** @return array<string, mixed> */
    private function workshopRow(Workshop $w): array
    {
        return [
            'id' => $w->id, 'name' => $w->name, 'status' => $w->status, 'room' => $w->room,
            'teacher' => $w->teacher?->alias ?: $w->teacher?->name,
            'schedules' => $w->schedules->map(fn ($s) => $s->label())->values()->all(),
            'capacity' => $w->capacity, 'active_count' => (int) ($w->active_count ?? $w->activeCount()),
        ];
    }

    private function visibleWorkshop(User $user, int $id): ?Workshop
    {
        $workshop = Workshop::withoutGlobalScopes()->where('restaurant_id', (int) $user->restaurant_id)->find($id);
        if ($workshop === null || ($user->role === User::ROLE_PROFESOR && ! $workshop->isTaughtBy($user))) {
            return null;
        }

        return $workshop;
    }

    /** @return array{0: Workshop|null, 1: WorkshopSession|null} */
    private function sessionFor(User $user, int $id, int $session): array
    {
        $workshop = $this->visibleWorkshop($user, $id);
        $model = $workshop?->sessions()->whereKey($session)->first();

        return [$workshop, $model];
    }
}
