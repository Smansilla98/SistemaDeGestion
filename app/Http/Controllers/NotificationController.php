<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = auth()->user()
            ->unreadNotifications()
            ->latest()
            ->limit(20)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'notifications' => $notifications->map(fn ($n) => [
                    'id' => $n->id,
                    'type' => $n->data['type'] ?? 'info',
                    'message' => $n->data['message'] ?? '',
                    'url' => $n->data['url'] ?? null,
                    'created_at' => $n->created_at->diffForHumans(),
                ]),
            ]);
        }

        return view('notifications.index', [
            'notifications' => auth()->user()->notifications()->latest()->paginate(20),
        ]);
    }

    public function markAsRead(Request $request, string $id)
    {
        $notification = auth()->user()->unreadNotifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();
        $url = $notification->data['url'] ?? null;
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'url' => $url]);
        }
        return $url ? redirect($url) : redirect()->route('notifications.index');
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();
        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('success', 'Todas las notificaciones marcadas como leídas');
    }
}
