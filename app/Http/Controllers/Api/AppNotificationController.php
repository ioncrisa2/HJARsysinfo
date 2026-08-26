<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Notifikasi', 'Pengelolaan notifikasi in-app pengguna.', weight: 13)]
class AppNotificationController extends Controller
{
    use ApiResponse;

    #[Endpoint(
        title: 'Lihat daftar notifikasi',
        description: 'Mengembalikan daftar notifikasi pengguna terpaginasi beserta jumlah notifikasi yang belum dibaca.'
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->paginate((int) $request->integer('per_page', 15));

        $data = collect($notifications->items())->map(fn ($notif): array => [
            'id' => $notif->id,
            'type' => $notif->type,
            'data' => $notif->data,
            'read_at' => $notif->read_at?->toIso8601String(),
            'created_at' => $notif->created_at?->toIso8601String(),
        ])->all();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar notifikasi berhasil diambil.',
            'unread_count' => $user->unreadNotifications()->count(),
            'data' => $data,
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
                'total' => $notifications->total(),
                'last_page' => $notifications->lastPage(),
            ],
            'links' => [
                'first' => $notifications->url(1),
                'last' => $notifications->url($notifications->lastPage()),
                'prev' => $notifications->previousPageUrl(),
                'next' => $notifications->nextPageUrl(),
            ],
        ]);
    }

    #[Endpoint(
        title: 'Tandai notifikasi telah dibaca',
        description: 'Menandai satu notifikasi tertentu sebagai sudah dibaca.'
    )]
    public function read(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->success(null, 'Notifikasi telah ditandai sebagai dibaca.');
    }

    #[Endpoint(
        title: 'Tandai semua notifikasi telah dibaca',
        description: 'Menandai seluruh notifikasi yang belum dibaca milik pengguna aktif sebagai sudah dibaca.'
    )]
    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->success(null, 'Semua notifikasi telah ditandai sebagai dibaca.');
    }
}
