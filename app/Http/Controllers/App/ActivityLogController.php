<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    use AuthorizesPermissions;

    /**
     * Display a listing of the activity logs.
     */
    public function index(Request $request)
    {
        $this->authorizePermission('view_activity_log');

        $query = Activity::with('causer')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('log_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(15)->withQueryString();

        return Inertia::render('ActivityLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Display the specified activity log detail.
     */
    public function show($id)
    {
        $this->authorizePermission('view_activity_log');

        $log = Activity::with('causer')->findOrFail($id);

        return Inertia::render('ActivityLogs/Show', [
            'log' => $log,
        ]);
    }
}
