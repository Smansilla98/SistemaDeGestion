<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendExpoPushNotification;
use App\Models\DeviceToken;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\SystemNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Punto único para avisar a usuarios desde cualquier módulo.
 *
 * - Respeta las preferencias por categoría (en la app / por mail).
 * - Con una clave de negocio (ej. "waitlist:12:340") el mismo aviso no llega dos veces
 *   (patrón de adminChilingo: tabla notification_dispatches con índice único).
 * - Usa la tabla notifications de Laravel, la misma que muestra la campana.
 */
final class Notifier
{
    public const CATEGORIES = [
        'inscripciones' => 'Inscripciones, confirmaciones y cancelaciones',
        'clases' => 'Clases, horarios y recordatorios',
        'agenda' => 'Avisos especiales de agenda',
        'mensajes' => 'Mensajes de otros usuarios',
        'administrativo' => 'Avisos administrativos',
        'stock' => 'Alertas de stock',
        'pedidos' => 'Pedidos y entregas',
        'consignaciones' => 'Consignaciones y liquidaciones',
        'pagos' => 'Recordatorios de pagos',
        'documentos' => 'Documentos nuevos',
        'difusion' => 'Comunicaciones y novedades',
    ];

    /**
     * @return bool true si se envió; false si estaba desactivado, duplicado o falló
     */
    public function send(User $user, string $category, string $title, string $message, ?string $url = null, ?string $key = null, ?int $restaurantId = null): bool
    {
        if (! $user->is_active) {
            return false;
        }

        $pref = $this->preference($user, $category);
        $channels = [];
        if ($pref['in_app']) {
            $channels[] = 'database';
        }
        if ($pref['email'] && filled($user->email) && ! str_ends_with((string) $user->email, '.local')) {
            $channels[] = 'mail';
        }
        if ($channels === []) {
            return false;
        }

        if ($key !== null && ! $this->reserve($user, $key)) {
            return false;
        }

        try {
            $user->notify(new SystemNotification($category, $title, $message, $url, $restaurantId ?? $user->restaurant_id, $channels));
            if ($pref['in_app']) {
                $this->push($user, $category, $title, $message, $url);
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Notifier: no se pudo avisar', ['user_id' => $user->id, 'category' => $category, 'error' => $e->getMessage()]);

            return false;
        }
    }

    /**
     * @param  iterable<User>  $users
     * @return int cantidad de avisos enviados
     */
    public function sendMany(iterable $users, string $category, string $title, string $message, ?string $url = null, ?string $keyPrefix = null, ?int $restaurantId = null): int
    {
        $sent = 0;
        $seen = [];
        foreach ($users as $user) {
            if (isset($seen[$user->id])) {
                continue;
            }
            $seen[$user->id] = true;
            $key = $keyPrefix !== null ? $keyPrefix.':'.$user->id : null;
            $sent += $this->send($user, $category, $title, $message, $url, $key, $restaurantId) ? 1 : 0;
        }

        return $sent;
    }

    /**
     * @return array{in_app: bool, email: bool}
     */
    public function preference(User $user, string $category): array
    {
        $row = NotificationPreference::query()->where('user_id', $user->id)->where('category', $category)->first();

        return ['in_app' => $row?->in_app ?? true, 'email' => $row?->email ?? false];
    }

    /**
     * Push a la app (Expo) en los dispositivos de la persona, si tiene la app instalada.
     */
    private function push(User $user, string $category, string $title, string $message, ?string $url): void
    {
        $tokens = DeviceToken::query()->where('user_id', $user->id)->pluck('token')->filter()->unique()->values()->all();
        if ($tokens === []) {
            return;
        }
        try {
            SendExpoPushNotification::dispatch($tokens, $title, $message, ['type' => $category, 'url' => $url]);
        } catch (Throwable $e) {
            Log::warning('Notifier: falló el push', ['user_id' => $user->id, 'error' => $e->getMessage()]);
        }
    }

    private function reserve(User $user, string $key): bool
    {
        try {
            DB::table('notification_dispatches')->insert(['user_id' => $user->id, 'key' => mb_substr($key, 0, 191), 'created_at' => now()]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
