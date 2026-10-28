<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\ActivityEntry;
use App\Models\DistributionPayout;
use App\Models\DistributionRule;
use App\Models\Event;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ingresos, gastos, repartos y liquidaciones de un taller o evento. Todo en centavos y en el
 * servidor. Los movimientos no se borran: se anulan, para conservar el historial.
 */
final class ActivityFinanceService
{
    /**
     * @param  array<int, array{label?: mixed, percent?: mixed}>  $parties
     * @return list<array{label: string, percent: float}>
     */
    public static function normalizeParties(array $parties): array
    {
        $clean = [];
        foreach ($parties as $p) {
            $label = trim((string) ($p['label'] ?? ''));
            $percent = round((float) str_replace(',', '.', (string) ($p['percent'] ?? 0)), 2);
            if ($label === '' && $percent == 0.0) {
                continue;
            }
            if ($label === '' || $percent <= 0) {
                throw ValidationException::withMessages(['parties' => 'Cada parte necesita un nombre y un porcentaje mayor a cero.']);
            }
            $clean[] = ['label' => mb_substr($label, 0, 60), 'percent' => $percent];
        }
        if (count($clean) < 2) {
            throw ValidationException::withMessages(['parties' => 'El reparto necesita al menos dos partes.']);
        }
        if (count(array_unique(array_map(fn ($p) => mb_strtolower($p['label']), $clean))) !== count($clean)) {
            throw ValidationException::withMessages(['parties' => 'Hay dos partes con el mismo nombre.']);
        }
        if (abs(array_sum(array_column($clean, 'percent')) - 100) > 0.001) {
            throw ValidationException::withMessages(['parties' => 'Los porcentajes tienen que sumar 100.']);
        }

        return $clean;
    }

    public function record(Workshop|Event $activity, string $type, string $concept, int $amountCents, string $date, User $by, array $extra = []): ActivityEntry
    {
        if (! in_array($type, ['INGRESO', 'GASTO'], true) || $amountCents <= 0) {
            throw ValidationException::withMessages(['amount' => 'Importe o tipo inválido.']);
        }
        if (isset($extra['workshop_enrollment_id']) && ! ($activity instanceof Workshop
            && WorkshopEnrollment::query()->whereKey($extra['workshop_enrollment_id'])->where('workshop_id', $activity->id)->exists())) {
            throw ValidationException::withMessages(['workshop_enrollment_id' => 'La inscripción no es de este taller.']);
        }
        if (isset($extra['event_stage_id']) && ! ($activity instanceof Event && $activity->stages()->whereKey($extra['event_stage_id'])->exists())) {
            throw ValidationException::withMessages(['event_stage_id' => 'La etapa no es de este evento.']);
        }

        return $activity->entries()->create([
            'restaurant_id' => $activity->restaurant_id, 'type' => $type, 'concept' => $concept, 'amount_cents' => $amountCents,
            'date' => $date, 'recorded_by' => $by->id,
        ] + array_intersect_key($extra, array_flip(['contact_id', 'workshop_enrollment_id', 'event_stage_id', 'method'])));
    }

    public function void(ActivityEntry $entry, string $reason): void
    {
        if ($entry->voided_at !== null) {
            throw ValidationException::withMessages(['entry' => 'El movimiento ya estaba anulado.']);
        }
        $entry->update(['voided_at' => now(), 'void_reason' => mb_substr($reason, 0, 250)]);
    }

    /**
     * @return array{income_cents: int, expense_cents: int, result_cents: int, base_cents: int, rule: ?DistributionRule,
     *               parties: list<array{label: string, percent: float, due_cents: int, paid_cents: int, pending_cents: int}>}
     */
    public function settlement(Workshop|Event $activity): array
    {
        $entries = $activity->entries()->whereNull('voided_at')->get();
        $income = (int) $entries->where('type', 'INGRESO')->sum('amount_cents');
        $expense = (int) $entries->where('type', 'GASTO')->sum('amount_cents');
        $rule = $activity->distributionRule;
        $base = $rule === null ? 0 : ($rule->base === 'BRUTO' ? $income : max(0, $income - $expense));

        $parties = [];
        if ($rule) {
            $paid = $activity->payouts()->whereNull('voided_at')->get()->groupBy('party')->map(fn ($g) => (int) $g->sum('amount_cents'));
            $shares = self::split($base, array_column($rule->parties, 'percent'));
            foreach ($rule->parties as $i => $p) {
                $partyPaid = (int) ($paid[$p['label']] ?? 0);
                $parties[] = ['label' => $p['label'], 'percent' => (float) $p['percent'], 'due_cents' => $shares[$i],
                    'paid_cents' => $partyPaid, 'pending_cents' => $shares[$i] - $partyPaid];
            }
        }

        return ['income_cents' => $income, 'expense_cents' => $expense, 'result_cents' => $income - $expense,
            'base_cents' => $base, 'rule' => $rule, 'parties' => $parties];
    }

    public function payout(Workshop|Event $activity, string $party, int $amountCents, string $paidOn, User $by, ?string $note = null): DistributionPayout
    {
        return DB::transaction(function () use ($activity, $party, $amountCents, $paidOn, $by, $note) {
            $activity::withoutGlobalScopes()->whereKey($activity->id)->lockForUpdate()->first();
            $row = collect($this->settlement($activity)['parties'])->firstWhere('label', $party);
            if ($row === null) {
                throw ValidationException::withMessages(['party' => 'Esa parte no está en la regla de reparto.']);
            }
            if ($amountCents <= 0 || $amountCents > $row['pending_cents']) {
                throw ValidationException::withMessages(['amount' => 'El pago no puede superar lo pendiente ($'.number_format(max(0, $row['pending_cents']) / 100, 2, ',', '.').').']);
            }

            return $activity->payouts()->create([
                'restaurant_id' => $activity->restaurant_id, 'party' => $party, 'amount_cents' => $amountCents,
                'paid_on' => $paidOn, 'note' => $note, 'recorded_by' => $by->id,
            ]);
        });
    }

    /**
     * Saldo de cada inscripción activa contra el arancel del taller (pagos parciales).
     *
     * @return array<int, array{fee_cents: int, paid_cents: int, pending_cents: int}> enrollment_id => saldo
     */
    public function enrollmentBalances(Workshop $workshop): array
    {
        $paid = $workshop->entries()->whereNull('voided_at')->where('type', 'INGRESO')->whereNotNull('workshop_enrollment_id')
            ->get()->groupBy('workshop_enrollment_id')->map(fn ($g) => (int) $g->sum('amount_cents'));
        $out = [];
        foreach ($workshop->enrollments()->whereIn('status', [WorkshopEnrollment::ACTIVA, WorkshopEnrollment::FINALIZADA])->get() as $e) {
            $p = (int) ($paid[$e->id] ?? 0);
            $out[$e->id] = ['fee_cents' => (int) $workshop->fee_cents, 'paid_cents' => $p, 'pending_cents' => max(0, (int) $workshop->fee_cents - $p)];
        }

        return $out;
    }

    /**
     * Presupuesto, pagado y saldo por etapa de un evento.
     *
     * @return array{stages: list<array{id: int, name: string, budget_cents: int, paid_cents: int, pending_cents: int}>, budget_cents: int, paid_cents: int, pending_cents: int}
     */
    public function stagesSummary(Event $event): array
    {
        $paid = $event->entries()->whereNull('voided_at')->where('type', 'GASTO')->whereNotNull('event_stage_id')
            ->get()->groupBy('event_stage_id')->map(fn ($g) => (int) $g->sum('amount_cents'));
        $stages = [];
        foreach ($event->stages()->get() as $s) {
            $p = (int) ($paid[$s->id] ?? 0);
            $stages[] = ['id' => $s->id, 'name' => $s->name, 'budget_cents' => $s->budget_cents, 'paid_cents' => $p, 'pending_cents' => max(0, $s->budget_cents - $p)];
        }

        return ['stages' => $stages, 'budget_cents' => array_sum(array_column($stages, 'budget_cents')),
            'paid_cents' => array_sum(array_column($stages, 'paid_cents')), 'pending_cents' => array_sum(array_column($stages, 'pending_cents'))];
    }

    /**
     * Reparte centavos por porcentaje sin perder ni inventar centavos (resto mayor).
     *
     * @param  list<float>  $percents
     * @return list<int>
     */
    public static function split(int $cents, array $percents): array
    {
        $raw = array_map(fn ($p) => $cents * $p / 100, $percents);
        $floor = array_map(fn ($r) => (int) floor($r), $raw);
        $left = $cents - array_sum($floor);
        $order = array_keys($raw);
        usort($order, fn ($a, $b) => ($raw[$b] - $floor[$b]) <=> ($raw[$a] - $floor[$a]) ?: $a <=> $b);
        foreach (array_slice($order, 0, max(0, $left)) as $i) {
            $floor[$i]++;
        }

        return $floor;
    }

    public static function money(int $cents): string
    {
        return '$'.number_format($cents / 100, 2, ',', '.');
    }

    /** Convierte "1.234,50" / "1234.5" en centavos. */
    public static function toCents(mixed $value): int
    {
        $s = trim((string) $value);
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/', $s) || str_contains($s, ',')) {
            $s = str_replace(['.', ','], ['', '.'], $s);
        }

        return (int) round(((float) $s) * 100);
    }
}
