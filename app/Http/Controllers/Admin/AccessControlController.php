<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlController extends Controller
{
    private const GUARD = 'web';

    private const LOCKED_ROLES = [
        'super_admin',
    ];

    private const LOCKED_PERMISSIONS = [
        'can_access_admin',
        'can_access_admin_panel',
    ];

    public function index(): Response
    {
        $roles = Role::query()
            ->where('guard_name', self::GUARD)
            ->with(['permissions:id,name'])
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions_count' => $role->permissions_count,
                'users_count' => $role->users_count,
                'permissions' => $role->permissions
                    ->pluck('name')
                    ->sort()
                    ->values(),
                'is_locked' => in_array($role->name, self::LOCKED_ROLES, true),
            ]);

        $permissions = Permission::query()
            ->where('guard_name', self::GUARD)
            ->withCount(['roles', 'users'])
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission): array => [
                'id' => $permission->id,
                'name' => $permission->name,
                'guard_name' => $permission->guard_name,
                'group' => $this->permissionGroup($permission->name),
                'roles_count' => $permission->roles_count,
                'users_count' => $permission->users_count,
                'is_locked' => in_array($permission->name, self::LOCKED_PERMISSIONS, true),
            ]);

        return inertia('Admin/AccessControl/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'metrics' => [
                'roles' => $roles->count(),
                'permissions' => $permissions->count(),
                'assigned_permissions' => $roles->sum('permissions_count'),
            ],
        ]);
    }

    public function storeRole(Request $request): RedirectResponse
    {
        $validated = $this->validateRole($request);

        $role = Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => self::GUARD,
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);
        $this->clearPermissionCache();

        return redirect()
            ->route('admin.access-control.index')
            ->with('success', 'Role berhasil dibuat.');
    }

    public function updateRole(Request $request, Role $role): RedirectResponse
    {
        $this->ensureWebGuard($role);

        $validated = $this->validateRole($request, $role);

        if (! in_array($role->name, self::LOCKED_ROLES, true)) {
            $role->update(['name' => $validated['name']]);
        }

        $role->syncPermissions($validated['permissions'] ?? []);
        $this->clearPermissionCache();

        return redirect()
            ->route('admin.access-control.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroyRole(Role $role): RedirectResponse
    {
        $this->ensureWebGuard($role);
        $role->loadCount('users');

        if (in_array($role->name, self::LOCKED_ROLES, true)) {
            return redirect()
                ->back()
                ->with('error', 'Role super_admin tidak boleh dihapus.');
        }

        if ($role->users_count > 0) {
            return redirect()
                ->back()
                ->with('error', 'Role masih dipakai oleh user, lepaskan role dari user terlebih dahulu.');
        }

        $role->delete();
        $this->clearPermissionCache();

        return redirect()
            ->route('admin.access-control.index')
            ->with('success', 'Role berhasil dihapus.');
    }

    public function storePermission(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_:\-]+$/',
                Rule::unique('permissions', 'name')
                    ->where(fn ($query) => $query->where('guard_name', self::GUARD)),
            ],
        ]);

        Permission::query()->create([
            'name' => $validated['name'],
            'guard_name' => self::GUARD,
        ]);

        $this->clearPermissionCache();

        return redirect()
            ->route('admin.access-control.index')
            ->with('success', 'Permission berhasil dibuat.');
    }

    public function destroyPermission(Permission $permission): RedirectResponse
    {
        $this->ensureWebGuard($permission);
        $permission->loadCount(['roles', 'users']);

        if (in_array($permission->name, self::LOCKED_PERMISSIONS, true)) {
            return redirect()
                ->back()
                ->with('error', 'Permission akses admin tidak boleh dihapus.');
        }

        if ($permission->roles_count > 0 || $permission->users_count > 0) {
            return redirect()
                ->back()
                ->with('error', 'Permission masih dipakai oleh role atau user.');
        }

        $permission->delete();
        $this->clearPermissionCache();

        return redirect()
            ->route('admin.access-control.index')
            ->with('success', 'Permission berhasil dihapus.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        $ignoreId = $role?->id;

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_:\-]+$/',
                Rule::unique('roles', 'name')
                    ->where(fn ($query) => $query->where('guard_name', self::GUARD))
                    ->ignore($ignoreId),
            ],
            'permissions' => ['array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')
                    ->where(fn ($query) => $query->where('guard_name', self::GUARD)),
            ],
        ]);
    }

    private function permissionGroup(string $name): string
    {
        if (str_contains($name, '::')) {
            return str($name)->after('::')->replace(['-', '_'], ' ')->title()->toString();
        }

        return str($name)->before('_')->replace(['-', '_'], ' ')->title()->toString();
    }

    private function ensureWebGuard(Role|Permission $model): void
    {
        abort_unless($model->guard_name === self::GUARD, 404);
    }

    private function clearPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
