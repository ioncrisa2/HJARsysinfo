<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\AuthorizesAdminPermissions;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use App\Support\AdminAccess;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use AuthorizesAdminPermissions;

    private const ROLE_LABELS = [
        'pimpinan' => 'Pimpinan',
        'data_contributor' => 'Kontributor Data',
        'super_admin' => 'Super Admin',
    ];

    public function index(Request $request)
    {
        $this->authorizeAdmin('view_any_user');

        $users = User::with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->role, function ($query, $role) {
                $query->role($role);
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'active') {
                    $query->whereNull('deactivated_at');
                } elseif ($status === 'inactive') {
                    $query->whereNotNull('deactivated_at');
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return inertia('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'status']),
            'roles' => Role::all()->map(fn($role) => [
                'value' => $role->name,
                'label' => self::ROLE_LABELS[$role->name] ?? ucwords(str_replace('_', ' ', $role->name))
            ]),
            'can' => AdminAccess::capabilityMap($request->user(), [
                'create' => 'create_user',
                'update' => 'update_user',
                'delete' => 'delete_user',
                'deleteAny' => 'delete_any_user',
            ]),
        ]);
    }

    public function toggleStatus(User $user)
    {
        $this->authorizeAdmin('update_user');

        $user->deactivated_at = $user->deactivated_at ? null : now();
        $user->save();

        $status = $user->deactivated_at ? 'deactivated' : 'activated';

        return redirect()->back()
            ->with('success', "User successfully {$status}.");
    }

    public function create()
    {
        $this->authorizeAdmin('create_user');

        return inertia('Admin/Users/Form', [
            'user' => new User(),
            'roles' => Role::all(),
            'userRoles' => [],
            'can' => AdminAccess::capabilityMap(request()->user(), [
                'assignRoles' => 'update_user',
            ]),
        ]);
    }

    public function store(UserStoreRequest $request)
    {
        $this->authorizeAdmin('create_user');

        $validated = $request->validated();
        
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'deactivated_at' => $validated['is_active'] ? null : now(),
        ]);

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User successfully created.');
    }

    public function edit(User $user)
    {
        $this->authorizeAdmin('update_user');

        return inertia('Admin/Users/Form', [
            'user' => $user,
            'roles' => Role::all(),
            'userRoles' => $user->roles->pluck('name')->toArray(),
            'can' => AdminAccess::capabilityMap(request()->user(), [
                'assignRoles' => 'update_user',
            ]),
        ]);
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $this->authorizeAdmin('update_user');

        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->deactivated_at = $validated['is_active'] ? null : now();

        if (!empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User successfully updated.');
    }

    public function destroy(User $user)
    {
        $this->authorizeAdmin('delete_user');

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User successfully deleted.');
    }

    public function bulkDelete(Request $request)
    {
        $this->authorizeAdmin('delete_any_user');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:users,id'],
        ]);

        $ids = $validated['ids'];
        User::whereIn('id', $ids)->delete();

        return redirect()->route('admin.users.index')
            ->with('success', count($ids) . ' users successfully deleted.');
    }
}
