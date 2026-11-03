<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso genérico de cualquier módulo. Lo envía siempre App\Services\Notifier,
 * que decide canales según las preferencias del destinatario.
 */
class SystemNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<string>  $channels
     */
    public function __construct(
        public string $category,
        public string $title,
        public string $message,
        public ?string $url,
        public ?int $restaurantId,
        public array $channels = ['database'],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->category,
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->title.' — '.$this->message,
            'url' => $this->url,
            'restaurant_id' => $this->restaurantId,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->title)->line($this->message);

        return $this->url ? $mail->action('Ver', $this->url) : $mail;
    }
}
