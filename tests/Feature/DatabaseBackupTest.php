<?php

use App\Models\Restaurant;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Storage;

require_once __DIR__.'/Community/helpers.php';

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    config(['app.demo_entry' => false]);
    Storage::fake(DatabaseBackupService::DISK);
});

it('el comando vuelca la base comprimida, deja la marca y conserva sólo los últimos', function () {
    Restaurant::factory()->create();

    $this->artisan('backup:database', ['--keep' => 2])->assertSuccessful();
    $this->travel(1)->seconds();
    $this->artisan('backup:database', ['--keep' => 2])->assertSuccessful();
    $this->travel(1)->seconds();
    $this->artisan('backup:database', ['--keep' => 2])->assertSuccessful();

    $files = DatabaseBackupService::files();
    expect($files)->toHaveCount(2)
        ->and(DatabaseBackupService::last())->toMatchArray(['ok' => true]);
    $sql = gzdecode(Storage::disk(DatabaseBackupService::DISK)->get($files[0]));
    expect($sql)->toContain('CREATE TABLE')->toContain('INSERT INTO `restaurants`');
});

it('el admin de una instalación de una sola organización baja el backup', function () {
    $org = communityOrg();
    $admin = orgUser($org, 'ADMIN');
    $this->actingAs($admin)->post(route('configuration.backups.store'))->assertSessionHas('success');

    $file = basename(DatabaseBackupService::files()[0]);
    $this->actingAs($admin)->get(route('configuration.index'))->assertOk()->assertSee($file);
    $this->actingAs($admin)->get(route('configuration.backups.download', $file))->assertOk()->assertDownload($file);
});

it('con varias organizaciones sólo el superadmin ve y baja los backups', function () {
    $org = communityOrg();
    communityOrg();
    $admin = orgUser($org, 'ADMIN');
    $super = orgUser($org, 'SUPERADMIN');

    $this->actingAs($admin)->post(route('configuration.backups.store'))->assertForbidden();
    $this->actingAs($admin)->get(route('configuration.index'))->assertOk()->assertDontSee('Copias de seguridad');

    $branch = [\App\Http\Middleware\ResolveActiveRestaurant::SESSION_KEY => $org->id];
    $this->actingAs($super)->withSession($branch)->post(route('configuration.backups.store'))->assertSessionHas('success');
    $file = basename(DatabaseBackupService::files()[0]);
    $this->actingAs($admin)->get(route('configuration.backups.download', $file))->assertForbidden();
    $this->actingAs($super)->withSession($branch)->get(route('configuration.backups.download', $file))->assertOk();
    $this->actingAs($super)->withSession($branch)->get('/configuration/backups/..%2F..%2F.env')->assertNotFound();
});
