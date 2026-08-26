<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

#[Group('Activity Logs', 'Audit trail rekaman aktivitas pengguna dan perubahan data sistem.', weight: 14)]
class ActivityLogController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    #[Endpoint(
        title: 'Lihat daftar activity logs',
        description: 'Mengembalikan daftar riwayat aktivitas sistem terpaginasi dengan pencarian kata kunci log, deskripsi, atau event.'
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('view_activity_log');

        $query = Activity::with('causer:id,name,email')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('log_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate((int) $request->integer('per_page', 15));

        $data = collect($logs->items())->map(fn (Activity $log): array => [
            'id' => $log->id,
            'log_name' => $log->log_name,
            'description' => $log->description,
            'event' => $log->event,
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'causer' => $log->causer ? [
                'id' => $log->causer->id,
                'name' => $log->causer->name,
                'email' => $log->causer->email,
            ] : null,
            'properties' => $log->properties,
            'created_at' => $log->created_at?->toIso8601String(),
        ])->all();

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar activity log berhasil diambil.',
            'data' => $data,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
                'total' => $logs->total(),
                'last_page' => $logs->lastPage(),
            ],
            'links' => [
                'first' => $logs->url(1),
                'last' => $logs->url($logs->lastPage()),
                'prev' => $logs->previousPageUrl(),
                'next' => $logs->nextPageUrl(),
            ],
        ]);
    }

    #[Endpoint(
        title: 'Lihat detail activity log',
        description: 'Mengembalikan detail lengkap satu rekaman aktivitas audit log beserta snapshot perubahan attributes dan old data.'
    )]
    public function show(string $id): JsonResponse
    {
        $this->authorizePermission('view_activity_log');

        $log = Activity::with('causer:id,name,email')->findOrFail($id);

        return $this->success([
            'id' => $log->id,
            'log_name' => $log->log_name,
            'description' => $log->description,
            'event' => $log->event,
            'subject_type' => $log->subject_type,
            'subject_id' => $log->subject_id,
            'causer' => $log->causer ? [
                'id' => $log->causer->id,
                'name' => $log->causer->name,
                'email' => $log->causer->email,
            ] : null,
            'properties' => $log->properties,
            'created_at' => $log->created_at?->toIso8601String(),
        ], 'Detail activity log berhasil diambil.');
    }
}
