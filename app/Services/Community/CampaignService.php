<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Services\Notifier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Envía una comunicación de difusión. Sólo llega a contactos activos que aceptaron recibir
 * comunicaciones (accepts_marketing): con cuenta, como aviso en la app; con email y si la
 * campaña lo pide, por correo. Cada destinatario queda registrado.
 */
final class CampaignService
{
    public function __construct(private readonly ContactService $contacts, private readonly Notifier $notifier) {}

    public function recipientsQuery(Campaign $campaign)
    {
        return $this->contacts->query((int) $campaign->restaurant_id, ($campaign->audience ?? []) + ['marketing' => true]);
    }

    /** @return int destinatarios alcanzados */
    public function send(Campaign $campaign): int
    {
        if ($campaign->status !== 'BORRADOR') {
            throw ValidationException::withMessages(['campaign' => 'Esta comunicación ya se envió.']);
        }

        $count = 0;
        $this->recipientsQuery($campaign)->with('user')->chunkById(200, function ($chunk) use ($campaign, &$count) {
            /** @var Contact $contact */
            foreach ($chunk as $contact) {
                $channels = [];
                if ($contact->user && $this->notifier->send($contact->user, 'difusion', $campaign->title, $campaign->body,
                    $campaign->link, "campaign:{$campaign->id}", (int) $campaign->restaurant_id)) {
                    $channels[] = 'app';
                }
                if ($campaign->send_email && filled($contact->email)) {
                    try {
                        Mail::raw($campaign->body.($campaign->link ? "\n\n".$campaign->link : '')."\n\nRecibís este correo porque aceptaste novedades. Para dejar de recibirlas, respondé este correo.",
                            fn ($m) => $m->to($contact->email)->subject($campaign->title));
                        $channels[] = 'email';
                    } catch (Throwable $e) {
                        Log::warning('Campaña: falló el correo', ['contact_id' => $contact->id, 'error' => $e->getMessage()]);
                    }
                }
                CampaignRecipient::query()->updateOrCreate(['campaign_id' => $campaign->id, 'contact_id' => $contact->id], ['channels' => implode(',', $channels)]);
                $count++;
            }
        });

        $campaign->update(['status' => 'ENVIADA', 'sent_at' => now(), 'recipients_count' => $count]);

        return $count;
    }
}
