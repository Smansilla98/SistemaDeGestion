<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envía notificaciones via Expo Push API (FCM/APNs detrás de Expo).
 */
class SendExpoPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  list<string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public array $tokens,
        public string $title,
        public string $body,
        public array $data = [],
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $messages = [];
        foreach ($this->tokens as $token) {
            if (! is_string($token) || $token === '') {
                continue;
            }
            $messages[] = [
                'to' => $token,
                'sound' => 'default',
                'title' => $this->title,
                'body' => $this->body,
                'data' => $this->data,
            ];
        }

        if ($messages === []) {
            return;
        }

        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->post('https://exp.host/--/api/v2/push/send', $messages);

            if (! $response->successful()) {
                Log::warning('Expo push falló', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Expo push excepción: '.$e->getMessage());
            throw $e;
        }
    }
}
