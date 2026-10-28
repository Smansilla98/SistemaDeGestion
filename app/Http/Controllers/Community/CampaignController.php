<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\ContactTag;
use App\Models\Workshop;
use App\Services\Community\CampaignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CampaignController extends Controller
{
    use OrganizationContext;

    public function __construct(private readonly CampaignService $service) {}

    public function index(): View
    {
        $this->requirePermission('contacts.campaign');

        return view('campaigns.index', ['campaigns' => Campaign::query()->where('restaurant_id', $this->org())->latest()->paginate(20)]);
    }

    public function create(): View
    {
        $this->requirePermission('contacts.campaign');

        return view('campaigns.create', [
            'tags' => ContactTag::query()->where('restaurant_id', $this->org())->orderBy('name')->get(),
            'groups' => ContactGroup::query()->where('restaurant_id', $this->org())->orderBy('name')->get(),
            'workshops' => Workshop::query()->where('restaurant_id', $this->org())->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requirePermission('contacts.campaign');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'], 'body' => ['required', 'string', 'max:5000'], 'link' => ['nullable', 'url', 'max:255'],
            'send_email' => ['nullable', 'boolean'],
            'kind' => ['nullable', 'array'], 'kind.*' => [Rule::in(array_keys(Contact::KINDS))],
            'tag_id' => ['nullable', 'array'], 'tag_id.*' => ['integer'], 'group_id' => ['nullable', 'array'], 'group_id.*' => ['integer'],
            'workshop_id' => ['nullable', 'array'], 'workshop_id.*' => ['integer'],
        ]);
        $campaign = Campaign::query()->create([
            'restaurant_id' => $this->org(), 'created_by' => $this->user()->id, 'title' => $data['title'], 'body' => $data['body'],
            'link' => $data['link'] ?? null, 'send_email' => $request->boolean('send_email'),
            'audience' => array_filter(['kind' => $data['kind'] ?? null, 'tag_id' => $data['tag_id'] ?? null,
                'group_id' => $data['group_id'] ?? null, 'workshop_id' => $data['workshop_id'] ?? null]),
        ]);

        return redirect()->route('campaigns.show', $campaign)->with('success', 'Borrador guardado. Revisá los destinatarios y enviá.');
    }

    public function show(Campaign $campaign): View
    {
        $this->ensureOrg($campaign);
        $this->requirePermission('contacts.campaign');
        $sent = $campaign->status !== 'BORRADOR';

        return view('campaigns.show', [
            'campaign' => $campaign,
            'recipients' => $sent ? $campaign->recipients()->with('contact')->paginate(50) : $this->service->recipientsQuery($campaign)->orderBy('name')->paginate(50),
            'sent' => $sent,
        ]);
    }

    public function send(Campaign $campaign): RedirectResponse
    {
        $this->ensureOrg($campaign);
        $this->requirePermission('contacts.campaign');
        $n = $this->service->send($campaign);

        return redirect()->route('campaigns.show', $campaign)->with('success', "Comunicación enviada a {$n} contactos.");
    }
}
