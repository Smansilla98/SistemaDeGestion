<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ModuleLicenseService;
use App\Support\CommercialQuote;
use App\Support\DemoEntry;
use Tests\TestCase;

class ModuleLicenseTest extends TestCase
{
    public function test_mapea_rutas_web_y_api_al_modulo_vendible(): void
    {
        $licenses = app(ModuleLicenseService::class);

        $this->assertSame('tables', $licenses->moduleForWebRoute('tables.index'));
        $this->assertSame('tables', $licenses->moduleForWebRoute('sectors.update'));
        $this->assertSame('orders', $licenses->moduleForWebRoute('orders.quick.store'));
        $this->assertSame('kitchen', $licenses->moduleForWebRoute('kitchen.mark-ready'));
        $this->assertSame('kitchen', $licenses->moduleForWebRoute('api.ready-orders'));
        $this->assertSame('catalog', $licenses->moduleForWebRoute('discount-types.index'));
        $this->assertSame('reports', $licenses->moduleForWebRoute('reports.sales'));
        $this->assertSame('fixed-expenses', $licenses->moduleForWebRoute('fixed-expenses.index'));
        $this->assertSame('events', $licenses->moduleForWebRoute('recurring-activities.index'));
        $this->assertNull($licenses->moduleForWebRoute('dashboard'));
        $this->assertNull($licenses->moduleForWebRoute('users.index'));

        $this->assertSame('tables', $licenses->moduleForApiPath('api/catalog/sectors'));
        $this->assertSame('catalog', $licenses->moduleForApiPath('api/catalog/categories'));
        $this->assertSame('catalog', $licenses->moduleForApiPath('/clients/4'));
        $this->assertSame('kitchen', $licenses->moduleForApiPath('api/notifications/ready-orders'));
        $this->assertSame('orders', $licenses->moduleForApiPath('api/orders/9/close'));
        $this->assertSame('cash', $licenses->moduleForApiPath('payment-method-configurations/active'));
        $this->assertNull($licenses->moduleForApiPath('api/dashboard'));
        $this->assertNull($licenses->moduleForApiPath('api/permissions/modules'));
    }

    public function test_sin_mapa_guardado_todos_los_modulos_quedan_habilitados(): void
    {
        $licenses = app(ModuleLicenseService::class);
        $map = $licenses->mapFor(null);

        $this->assertNotEmpty($map);
        foreach ($map as $enabled) {
            $this->assertTrue($enabled);
        }
        $this->assertTrue($licenses->isEnabled(null, 'stock'));
        $this->assertTrue($licenses->isEnabled(null, 'no-existe'));
    }

    public function test_la_eleccion_de_entrada_define_que_modulos_se_ven(): void
    {
        $user = new User(['role' => User::ROLE_SUPERADMIN]);
        $licenses = app(ModuleLicenseService::class);
        $session = app('session.store');
        $session->start();
        request()->setLaravelSession($session);

        $session->put('entry_mode', DemoEntry::DEMO);
        $this->assertTrue($licenses->enabledForUser($user, 'events'));
        $this->assertTrue($licenses->enabledForUser($user, 'tables'));

        $session->put('entry_mode', DemoEntry::CUSTOM);
        $session->put('preview_modules', ['tables' => true, 'orders' => false]);
        $this->assertTrue($licenses->enabledForUser($user, 'tables'));
        $this->assertFalse($licenses->enabledForUser($user, 'orders'));
        $this->assertFalse($licenses->enabledForUser($user, 'events'));
        $this->assertFalse($licenses->enabledForUser($user, 'fixed-expenses'));

        $offered = array_keys($licenses->offered());
        $this->assertContains('tables', $offered);
        $this->assertContains('reports', $offered);
        $this->assertNotContains('events', $offered);
        $this->assertNotContains('fixed-expenses', $offered);
    }

    public function test_el_presupuesto_suma_modulos_y_no_lleva_cliente(): void
    {
        $quotes = app(CommercialQuote::class);
        $partial = $quotes->present(['tables', 'orders']);

        $this->assertSame(4200, $partial['offer_usd']);
        $this->assertSame(90, $partial['hosting_usd']);
        $this->assertSame(2, $partial['count']);

        $full = $quotes->present(null);
        $this->assertSame(10900, $full['offer_usd']);
        $this->assertSame(220, $full['hosting_usd']);
        $keys = array_column($full['lines'], 'key');
        $this->assertNotContains('events', $keys);
        $this->assertNotContains('fixed-expenses', $keys);
        $this->assertSame(['tables', 'orders'], $quotes->sanitizeKeys(['tables', 'orders', 'events', 'no-existe']));
    }
}
