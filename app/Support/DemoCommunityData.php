<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\AgendaNotice;
use App\Models\Contact;
use App\Models\ContactTag;
use App\Models\DistributionRule;
use App\Models\Entrepreneur;
use App\Models\Event;
use App\Models\MarketProduct;
use App\Models\OrderCycle;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopSchedule;
use App\Services\Community\WorkshopService;
use App\Services\Finance\ActivityFinanceService;
use App\Services\Market\ConsignmentService;
use App\Services\Market\MarketStockService;
use Illuminate\Support\Facades\DB;

/**
 * Datos de ejemplo de un centro cultural para la demo: un taller con clases e inscriptos,
 * un evento por etapas, una huerta en consignación, un ciclo de pedidos y avisos de agenda.
 * Sólo corre con la entrada de demo activa y una sola vez por organización.
 */
final class DemoCommunityData
{
    public const MARKER = 'Percusión inicial (ejemplo)';

    public static function ensure(User $user): void
    {
        $orgId = $user->restaurant_id;
        if (! config('app.demo_entry', true) || $orgId === null
            || Workshop::withoutGlobalScopes()->where('restaurant_id', $orgId)->where('name', self::MARKER)->exists()) {
            return;
        }

        DB::transaction(function () use ($user, $orgId) {
            $org = (int) $orgId;
            $rule = DistributionRule::query()->create(['restaurant_id' => $org, 'name' => 'Talleres 70/30', 'base' => 'NETO',
                'parties' => [['label' => 'Docente', 'percent' => 70], ['label' => 'Espacio', 'percent' => 30]]]);
            $profe = Contact::query()->create(['restaurant_id' => $org, 'name' => 'Marta Ríos (ejemplo)', 'kind' => 'PROFESOR', 'phone' => '11 4000 1000']);
            $tag = ContactTag::query()->firstOrCreate(['restaurant_id' => $org, 'name' => 'zona sur']);

            $workshop = Workshop::query()->create(['restaurant_id' => $org, 'name' => self::MARKER, 'status' => 'ABIERTO',
                'description' => 'Taller abierto a la comunidad. Ejemplo para recorrer inscripciones, clases y asistencia.',
                'capacity' => 4, 'fee_cents' => 1500000, 'teacher_contact_id' => $profe->id, 'room' => 'Sala grande',
                'starts_on' => today()->subWeeks(3), 'distribution_rule_id' => $rule->id]);
            WorkshopSchedule::query()->create(['workshop_id' => $workshop->id, 'weekday' => 2, 'start_time' => '18:00:00', 'end_time' => '20:00:00']);
            WorkshopSchedule::query()->create(['workshop_id' => $workshop->id, 'weekday' => 4, 'start_time' => '18:00:00', 'end_time' => '20:00:00']);
            $svc = app(WorkshopService::class);
            $svc->generateSessions($workshop, today()->subWeeks(2), today()->addWeeks(6));

            foreach (['Ana Gómez', 'Bruno Díaz', 'Caro Ruiz', 'Dani López', 'Eli Fernández'] as $i => $name) {
                $c = Contact::query()->create(['restaurant_id' => $org, 'name' => "{$name} (ejemplo)", 'kind' => 'ALUMNO',
                    'phone' => '11 5000 10'.$i.$i, 'accepts_marketing' => $i % 2 === 0, 'marketing_consent_at' => $i % 2 === 0 ? now() : null]);
                $c->tags()->sync([$tag->id]);
                $svc->enroll($workshop, $c); // el quinto queda en lista de espera
            }

            $finance = app(ActivityFinanceService::class);
            $finance->record($workshop, 'INGRESO', 'Cuotas del mes', 4500000, today()->toDateString(), $user);
            $finance->record($workshop, 'GASTO', 'Parches y baquetas', 600000, today()->toDateString(), $user);

            $event = Event::query()->create(['restaurant_id' => $org, 'created_by' => $user->id, 'name' => 'Peña de primavera (ejemplo)',
                'date' => today()->addWeeks(2), 'time' => '20:00', 'status' => Event::STATUS_PROGRAMADO, 'expected_attendance' => 150]);
            foreach ([['Armado y sonido', 20000000], ['Difusión', 3000000], ['Artistas', 25000000]] as $pos => [$name, $budget]) {
                $event->stages()->create(['name' => $name, 'position' => $pos, 'budget_cents' => $budget, 'status' => 'PENDIENTE',
                    'starts_at' => today()->addWeeks(2)->setTime(16 + $pos, 0), 'responsible_contact_id' => $profe->id]);
            }
            $finance->record($event, 'GASTO', 'Seña sonido', 8000000, today()->toDateString(), $user, ['event_stage_id' => $event->stages()->value('id')]);

            $huerta = Entrepreneur::query()->create(['restaurant_id' => $org, 'business_name' => 'Huerta del Sur (ejemplo)',
                'payment_alias' => 'huerta.sur', 'description' => 'Verdura agroecológica de la zona.',
                'brand' => ['tagline' => 'De la tierra a tu mesa', 'colors' => ['#2E7D32', '#F9A825', '#5D4037'], 'font_primary' => 'Lora']]);
            $bolson = MarketProduct::query()->create(['restaurant_id' => $org, 'entrepreneur_id' => $huerta->id, 'name' => 'Bolsón de verdura',
                'category' => 'Verdura', 'unit' => 'bolsón', 'price_cents' => 900000, 'min_stock' => 3, 'is_active' => true]);
            MarketProduct::query()->create(['restaurant_id' => $org, 'entrepreneur_id' => $huerta->id, 'name' => 'Miel',
                'category' => 'Almacén', 'unit' => 'frasco', 'price_cents' => 650000, 'is_active' => true]);
            app(ConsignmentService::class)->receive($huerta, today()->subDays(5)->toDateString(), 20, [['market_product_id' => $bolson->id, 'quantity' => 12]], $user);
            app(MarketStockService::class)->sell($bolson, 4, $user, 'Venta en feria');

            OrderCycle::query()->create(['restaurant_id' => $org, 'name' => 'Nodo — pedidos de la quincena (ejemplo)',
                'opens_at' => now()->subDay(), 'closes_at' => now()->addDays(4), 'delivery_on' => today()->addDays(5), 'status' => 'ABIERTO']);

            AgendaNotice::query()->create(['restaurant_id' => $org, 'kind' => 'FECHA_ESPECIAL', 'title' => 'Menú especial por fecha patria (ejemplo)',
                'date' => today()->addDays(3), 'created_by' => $user->id]);
            AgendaNotice::query()->create(['restaurant_id' => $org, 'kind' => 'REUNION', 'title' => 'Reunión de coordinación (ejemplo)',
                'date' => today()->addDay(), 'time' => '19:00', 'created_by' => $user->id]);
        });
    }
}
