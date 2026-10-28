<?php

namespace App\Http\Controllers\Community;

use App\Http\Controllers\Concerns\OrganizationContext;
use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Models\UserMessage;
use App\Services\Community\MessageService;
use App\Services\Community\OrganizationDirectory;
use App\Services\Notifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mensajes entre usuarios de la organización y preferencias de avisos de la propia cuenta.
 */
class MessageController extends Controller
{
    use OrganizationContext;

    public function index(Request $request): View
    {
        $sent = $request->input('carpeta') === 'enviados';
        $q = UserMessage::query()->where('restaurant_id', $this->org())->with(['sender', 'recipient'])->latest();
        $sent ? $q->where('sender_id', $this->user()->id) : $q->where('recipient_id', $this->user()->id);

        return view('messages.index', ['messages' => $q->paginate(25)->withQueryString(), 'sent' => $sent, 'canSend' => $this->can('messages.send')]);
    }

    public function create(Request $request, OrganizationDirectory $directory): View
    {
        $this->requirePermission('messages.send');

        return view('messages.create', [
            'people' => $directory->members($this->org())->reject(fn ($m) => $m['user']->id === $this->user()->id)->sortBy(fn ($m) => $m['user']->name),
            'to' => (int) $request->input('para'), 'subject' => (string) $request->input('asunto', ''),
        ]);
    }

    public function store(Request $request, MessageService $service): RedirectResponse
    {
        $this->requirePermission('messages.send');
        $data = $request->validate(['recipient_id' => ['required', 'integer'], 'subject' => ['nullable', 'string', 'max:255'], 'body' => ['required', 'string', 'max:5000']]);
        $service->send($this->user(), $this->org(), (int) $data['recipient_id'], $data['subject'] ?? null, $data['body']);

        return redirect()->route('messages.index', ['carpeta' => 'enviados'])->with('success', 'Mensaje enviado.');
    }

    public function show(UserMessage $message): View
    {
        abort_unless(in_array($this->user()->id, [$message->sender_id, $message->recipient_id], true), 404);
        if ($message->recipient_id === $this->user()->id && $message->read_at === null) {
            $message->update(['read_at' => now()]);
        }

        return view('messages.show', ['message' => $message->load(['sender', 'recipient']), 'canSend' => $this->can('messages.send')]);
    }

    public function preferences(Notifier $notifier): View
    {
        return view('notifications.preferences', [
            'categories' => Notifier::CATEGORIES,
            'prefs' => collect(Notifier::CATEGORIES)->map(fn ($l, $k) => $notifier->preference($this->user(), $k)),
        ]);
    }

    public function savePreferences(Request $request): RedirectResponse
    {
        foreach (array_keys(Notifier::CATEGORIES) as $category) {
            NotificationPreference::query()->updateOrCreate(
                ['user_id' => $this->user()->id, 'category' => $category],
                ['in_app' => $request->boolean("in_app.{$category}"), 'email' => $request->boolean("email.{$category}")],
            );
        }

        return back()->with('success', 'Preferencias guardadas.');
    }
}
