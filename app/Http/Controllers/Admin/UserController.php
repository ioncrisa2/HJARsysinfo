<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserStoreRequest;
use App\Http\Requests\Admin\UserUpdateRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
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
                'label' => ucwords(str_replace('_', ' ', $role->name))
            ])
        ]);
    }

    public function toggleStatus(User $user)
    {
        $user->deactivated_at = $user->deactivated_at ? null : now();
        $user->save();

        $status = $user->deactivated_at ? 'deactivated' : 'activated';

        return redirect()->back()
            ->with('success', "User successfully {$status}.");
    }

    public function create()
    {
        return inertia('Admin/Users/Form', [
            'user' => new User(),
            'roles' => Role::all(),
            'userRoles' => []
        ]);
    }

    public function store(UserStoreRequest $request)
    {
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
        return inertia('Admin/Users/Form', [
            'user' => $user,
            'roles' => Role::all(),
            'userRoles' => $user->roles->pluck('name')->toArray()
        ]);
    }

    public function update(UserUpdateRequest $request, User $user)
    {
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
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User successfully deleted.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        User::whereIn('id', $ids)->delete();

        return redirect()->route('admin.users.index')
            ->with('success', count($ids) . ' users successfully deleted.');
    }
}
