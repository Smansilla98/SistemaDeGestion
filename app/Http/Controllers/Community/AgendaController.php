<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\ActivityCategory;
use App\Models\AgendaNotice;
use App\Services\Community\AgendaService;
use App\Services\Community\OrganizationDirectory;
use App\Services\Notifier;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgendaController extends Controller
{
    use OrganizationContext;

    public function index(Request $request, AgendaService $agenda): View
    {
        $this->requirePermission('agenda.view');
        $view = $request->input('vista') === 'mes' ? 'mes' : 'semana';
        $ref = CarbonImmutable::parse($request->input('desde', today()->toDateString()));
        [$from, $to] = $view === 'mes'
            ? [$ref->startOfMonth(), $ref->endOfMonth()->startOfDay()]
            : [$ref->startOfWeek(), $ref->startOfWeek()->addDays(6)];

        $filters = ['types' => array_values(array_filter((array) $request->input('tipo', []), 'is_string')),
            'category_id' => $request->integer('categoria') ?: null];

        return view('agenda.index', [
            'days' => $agenda->between($this->user(), $this->org(), $from, $to, $filters),
            'filters' => $filters,
            'categories' => ActivityCategory::query()->where('restaurant_id', $this->org())->orderBy('name')->get(),
            'from' => $from, 'to' => $to, 'view' => $view,
            'prev' => $view === 'mes' ? $from->subMonth() : $from->subWeek(),
            'next' => $view === 'mes' ? $from->addMonth() : $from->addWeek(),
            'canCreate' => $this->can('agenda.create'),
            'quick' => array_filter([
                'workshop' => $this->can('workshops.create') && $this->moduleOn('workshops') ? route('workshops.create') : null,
                'event' => $this->can('events.create') && $this->moduleOn('events') ? route('events.create') : null,
                'forms' => $this->can('contacts.create') && $this->moduleOn('contacts') ? route('forms.index') : null,
                'cycle' => $this->can('market.create') && $this->moduleOn('market') ? route('market.cycles.index') : null,
                'entrepreneur' => $this->can('market.create') && $this->moduleOn('market') ? route('market.entrepreneurs.create') : null,
            ]),
            'notices' => $this->can('agenda.create') ? AgendaNotice::query()->where('restaurant_id', $this->org())
                ->whereDate('date', '>=', today()->subDays(30))->orderBy('date')->get() : collect(),
        ]);
    }

    private function moduleOn(string $key): bool
    {
        return app(\App\Services\ModuleLicenseService::class)->enabledForUser($this->user(), $key);
    }

    public function storeNotice(Request $request, OrganizationDirectory $directory, Notifier $notifier): RedirectResponse
    {
        $this->requirePermission('agenda.create');
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(AgendaNotice::KINDS))], 'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:2000'], 'date' => ['required', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:date'],
            'time' => ['nullable', 'date_format:H:i'], 'notify' => ['nullable', 'boolean'],
            'activity_category_id' => ['nullable', 'integer', Rule::exists('activity_categories', 'id')->where('restaurant_id', $this->org())],
        ]);
        $notice = AgendaNotice::query()->create(collect($data)->except('notify')->all() + ['restaurant_id' => $this->org(), 'created_by' => $this->user()->id]);

        $sent = 0;
        if ($request->boolean('notify')) {
            $sent = $notifier->sendMany($directory->members($this->org())->map(fn ($m) => $m['user']), 'agenda', $notice->title,
                ($notice->body ?: AgendaNotice::KINDS[$notice->kind]).' — '.$notice->date->format('d/m/Y'), route('agenda.index', ['desde' => $notice->date->toDateString()]),
                "notice-now:{$notice->id}", $this->org());
        }

        return back()->with('success', 'Aviso agregado a la agenda.'.($sent ? " Se avisó a {$sent} personas." : ''));
    }

    public function destroyNotice(AgendaNotice $notice): RedirectResponse
    {
        $this->ensureOrg($notice);
        $this->requirePermission('agenda.create');
        $notice->delete();

        return back()->with('success', 'Aviso quitado de la agenda.');
    }
}
