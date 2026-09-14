<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Controllers\Controller;
use App\Core\ApiResponse;
use App\Jobs\SendExpoPushNotification;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class DeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return ApiResponse::error('No autenticado', 401, 'UNAUTHENTICATED');
        }

        $validated = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['required', Rule::in(['ios', 'android', 'web'])],
            'app_version' => ['nullable', 'string', 'max:32'],
        ]);

        $device = DeviceToken::query()->updateOrCreate(
            ['token' => $validated['token']],
            [
                'user_id' => $user->id,
                'restaurant_id' => $user->restaurant_id,
                'platform' => $validated['platform'],
                'app_version' => $validated['app_version'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return ApiResponse::success([
            'id' => $device->id,
            'platform' => $device->platform,
        ], 201, 'Dispositivo registrado');
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        DeviceToken::query()
            ->where('user_id', $request->user()->id)
            ->where('token', $validated['token'])
            ->delete();

        return ApiResponse::success(['deleted' => true]);
    }

    /**
     * Endpoint de prueba interno (solo ADMIN) — encola push a los devices del usuario.
     */
    public function testPush(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! in_array($user->role, ['ADMIN', 'SUPERADMIN', 'GERENTE'], true)) {
            return ApiResponse::error('Forbidden', 403, 'FORBIDDEN');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:500'],
        ]);

        $tokens = DeviceToken::query()->where('user_id', $user->id)->pluck('token')->all();
        if ($tokens === []) {
            return ApiResponse::error('Sin devices registrados', 422, 'NO_DEVICES');
        }

        SendExpoPushNotification::dispatch($tokens, $validated['title'], $validated['body']);

        return ApiResponse::success(['queued' => count($tokens)]);
    }
}
