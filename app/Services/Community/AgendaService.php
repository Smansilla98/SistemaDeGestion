<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\AgendaNotice;
use App\Models\Event;
use App\Models\EventStage;
use App\Models\OrderCycle;
use App\Models\RecurringActivity;
use App\Models\User;
use App\Models\WorkshopEnrollment;
use App\Models\WorkshopSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Agenda unificada de una organización: clases, eventos y sus etapas, actividades recurrentes,
 * avisos y fechas especiales, y entregas de ciclos de pedidos. No guarda nada propio: lee
 * cada módulo, así no hay dos fuentes de verdad.
 */
final class AgendaService
{
    /** Roles que sólo ven las clases de sus propios talleres. */
    private const PERSONAL_ROLES = [User::ROLE_ALUMNO, User::ROLE_PROFESOR];

    /** Tipos de ítem de la agenda, para filtrar. */
    public const TYPES = ['Clase' => 'Clases', 'Evento' => 'Eventos', 'Etapa' => 'Etapas de eventos', 'Actividad' => 'Actividades recurrentes',
        'Entrega' => 'Entregas', 'Aviso' => 'Avisos y fechas especiales'];

    /**
     * @param  array{types?: list<string>, category_id?: int|null}  $filters
     * @return Collection<array-key, mixed> fecha (Y-m-d) => colección de items {date, time, kind, type, title, detail, url, cancelled, category, color}
     */
    public function between(User $user, int $restaurantId, CarbonImmutable $from, CarbonImmutable $to, array $filters = []): Collection
    {
        $items = $this->collect($user, $restaurantId, $from, $to);
        $types = array_values(array_intersect($filters['types'] ?? [], array_keys(self::TYPES)));
        if ($types !== []) {
            $items = $items->filter(fn ($i) => in_array($i['type'], $types, true));
        }
        if (! empty($filters['category_id'])) {
            $items = $items->filter(fn ($i) => $i['category_id'] === (int) $filters['category_id']);
        }

        return $items->sortBy(fn ($i) => $i['date'].' '.($i['time'] ?? '00:00'))->groupBy('date');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function collect(User $user, int $restaurantId, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $items = collect();
        $cat = fn ($model) => ['category_id' => $model?->activity_category_id !== null ? (int) $model->activity_category_id : null,
            'category' => $model?->category?->name, 'color' => $model?->category?->color];
        $personal = in_array($user->role, self::PERSONAL_ROLES, true);

        $sessions = WorkshopSession::query()->with('workshop.category')
            ->whereHas('workshop', fn ($q) => $q->where('restaurant_id', $restaurantId))
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()]);
        if ($personal) {
            $sessions->whereHas('workshop', fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('enrollments', fn ($e) => $e->where('status', WorkshopEnrollment::ACTIVA)->whereHas('contact', fn ($c) => $c->where('user_id', $user->id)))
                ->orWhereHas('teacher', fn ($t) => $t->where('user_id', $user->id))));
        }
        foreach ($sessions->get() as $s) {
            $items->push([
                'date' => $s->date->toDateString(), 'time' => substr((string) $s->start_time, 0, 5), 'kind' => 'Clase',
                'title' => $s->workshop->name,
                'detail' => match ($s->status) {
                    WorkshopSession::CANCELADA => 'Suspendida'.($s->note ? ": {$s->note}" : ''),
                    WorkshopSession::REPROGRAMADA => 'Reprogramada'.($s->original_date ? ' (era el '.$s->original_date->format('d/m').')' : ''),
                    default => $s->workshop->room,
                },
                'url' => $personal ? null : route('workshops.show', $s->workshop),
                'cancelled' => $s->status === WorkshopSession::CANCELADA, 'type' => 'Clase',
            ] + $cat($s->workshop));
        }

        if (! $personal) {
            foreach (Event::query()->with('category')->where('restaurant_id', $restaurantId)->whereBetween('date', [$from->toDateString(), $to->toDateString()])->get() as $e) {
                $items->push(['date' => $e->date->toDateString(), 'time' => $e->time ? substr((string) $e->time, 0, 5) : null,
                    'kind' => 'Evento', 'title' => $e->name, 'detail' => null, 'url' => route('events.show', $e),
                    'cancelled' => $e->status === Event::STATUS_CANCELADO, 'type' => 'Evento'] + $cat($e));
            }
            $stages = EventStage::query()->with(['event.category', 'responsible'])->whereHas('event', fn ($q) => $q->where('restaurant_id', $restaurantId))
                ->whereBetween('starts_at', [$from->startOfDay(), $to->endOfDay()])->get();
            foreach ($stages as $st) {
                $items->push(['date' => $st->starts_at->toDateString(), 'time' => $st->starts_at->format('H:i'), 'kind' => 'Etapa',
                    'title' => "{$st->event->name}: {$st->name}", 'detail' => $st->responsible?->name, 'url' => route('events.show', $st->event),
                    'cancelled' => false, 'type' => 'Etapa'] + $cat($st->event));
            }
            foreach (RecurringActivity::query()->where('restaurant_id', $restaurantId)->get() as $ra) {
                foreach ($ra->getInstancesForDateRange($from->toDateString(), $to->toDateString()) as $inst) {
                    $items->push(['date' => $inst['date'], 'time' => $inst['time'] ? substr((string) $inst['time'], 0, 5) : null,
                        'kind' => 'Actividad', 'title' => $inst['name'], 'detail' => null, 'url' => null, 'cancelled' => false, 'type' => 'Actividad'] + $cat(null));
                }
            }
        }

        foreach (OrderCycle::query()->where('restaurant_id', $restaurantId)->whereBetween('delivery_on', [$from->toDateString(), $to->toDateString()])->get() as $c) {
            $items->push(['date' => $c->delivery_on->toDateString(), 'time' => null, 'kind' => 'Entrega',
                'title' => "Entrega de pedidos: {$c->name}", 'detail' => null, 'url' => $personal ? null : route('market.cycles.show', $c), 'cancelled' => false, 'type' => 'Entrega'] + $cat(null));
        }

        $notices = AgendaNotice::query()->with('category')->where('restaurant_id', $restaurantId)
            ->where('date', '<=', $to->toDateString())
            ->where(fn ($q) => $q->where('date', '>=', $from->toDateString())->orWhere('end_date', '>=', $from->toDateString()))
            ->get();
        foreach ($notices as $n) {
            $start = CarbonImmutable::parse($n->date)->max($from);
            $end = CarbonImmutable::parse($n->end_date ?? $n->date)->min($to);
            for ($d = $start; $d->lte($end); $d = $d->addDay()) {
                $items->push(['date' => $d->toDateString(), 'time' => $n->time ? substr((string) $n->time, 0, 5) : null,
                    'kind' => AgendaNotice::KINDS[$n->kind] ?? 'Aviso', 'title' => $n->title, 'detail' => $n->body, 'url' => null, 'cancelled' => false, 'type' => 'Aviso'] + $cat($n));
            }
        }

        return $items;
    }
}
