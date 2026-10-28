<?php

declare(strict_types=1);

namespace App\Services\Community;

use App\Models\User;
use App\Models\UserMessage;
use App\Services\Notifier;
use Illuminate\Validation\ValidationException;

/**
 * Mensajes entre usuarios de una misma organización. Quien recibe ve un aviso (categoría «mensajes»).
 */
final class MessageService
{
    public function __construct(private readonly OrganizationDirectory $directory, private readonly Notifier $notifier) {}

    public function send(User $from, int $restaurantId, int $toUserId, ?string $subject, string $body): UserMessage
    {
        $to = User::query()->find($toUserId);
        if (! $to || $to->id === $from->id || ! $this->directory->isMember($to, $restaurantId)) {
            throw ValidationException::withMessages(['recipient_id' => 'Elegí una persona de esta organización.']);
        }

        $message = UserMessage::query()->create([
            'restaurant_id' => $restaurantId, 'sender_id' => $from->id, 'recipient_id' => $to->id,
            'subject' => $subject, 'body' => $body,
        ]);

        $this->notifier->send($to, 'mensajes', "Mensaje de {$from->name}", $subject ?: mb_strimwidth($body, 0, 80, '…'),
            route('messages.show', $message), "message:{$message->id}", $restaurantId);

        return $message;
    }
}
