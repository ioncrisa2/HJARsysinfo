<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Support\AppAccess;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

#[Group('Access Control', 'Manajemen hak akses: Role, Permission, dan penugasan kewenangan.', weight: 8)]
class AccessControlController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    private const GUARD = 'web';

    private const LOCKED_ROLES = [
        'super_admin',
    ];

    private const LOCKED_PERMISSIONS = [];

    #[Endpoint(
        title: 'Lihat daftar role',
        description: 'Mengembalikan seluruh role yang terdaftar beserta daftar permissions dan jumlah pengguna yang memegang role tersebut.'
    )]
    public function roles(Request $request): JsonResponse
    {
        $this->authorizePermission('view_access_control');

        // Sanctum changes the default guard; users() must resolve the web user model.
        $roles = (new Role(['guard_name' => self::GUARD]))->newQuery()
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

        return $this->success($roles, 'Daftar role berhasil diambil.');
    }

    #[Endpoint(
        title: 'Tambah role baru',
        description: 'Membuat role baru dan menetapkan daftar permissions awalnya.'
    )]
    public function storeRole(Request $request): JsonResponse
    {
        $this->authorizePermission('create_role');

        $validated = $this->validateRole($request);

        $role = Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => self::GUARD,
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);
        $this->clearPermissionCache();

        $role->load('permissions:id,name');

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->sort()->values(),
        ], 'Role berhasil dibuat.', 201);
    }

    #[Endpoint(
        title: 'Perbarui role',
        description: 'Memperbarui nama role dan menyinkronkan daftar permission yang dimiliki.'
    )]
    public function updateRole(Request $request, Role $role): JsonResponse
    {
        $this->authorizePermission('update_role');
        $this->ensureWebGuard($role);

        $validated = $this->validateRole($request, $role);

        if (! in_array($role->name, self::LOCKED_ROLES, true)) {
            $role->update(['name' => $validated['name']]);
        }

        $role->syncPermissions($validated['permissions'] ?? []);
        $this->clearPermissionCache();

        $role->load('permissions:id,name');

        return $this->success([
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->sort()->values(),
        ], 'Role berhasil diperbarui.');
    }

    #[Endpoint(
        title: 'Hapus role',
        description: 'Menghapus role jika tidak termasuk sistem terkunci (super_admin) dan tidak lagi digunakan oleh akun pengguna mana pun.'
    )]
    public function destroyRole(Role $role): JsonResponse
    {
        $this->authorizePermission('delete_role');
        $this->ensureWebGuard($role);
        $role->loadCount('users');

        if (in_array($role->name, self::LOCKED_ROLES, true)) {
            return $this->error('Role super_admin tidak boleh dihapus.', 422, null, 'LOCKED_ROLE');
        }

        if ($role->users_count > 0) {
            return $this->error('Role masih dipakai oleh user, lepaskan role dari user terlebih dahulu.', 422, null, 'ROLE_IN_USE');
        }

        $role->delete();
        $this->clearPermissionCache();

        return $this->success(null, 'Role berhasil dihapus.');
    }

    #[Endpoint(
        title: 'Lihat daftar permission',
        description: 'Mengembalikan seluruh permission aplikasi yang dikelompokkan menurut grup domain.'
    )]
    public function permissions(): JsonResponse
    {
        $this->authorizePermission('view_access_control');

        // A WHERE clause alone does not set the guard used by the users() relation.
        $permissions = (new Permission(['guard_name' => self::GUARD]))->newQuery()
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

        return $this->success($permissions, 'Daftar permission berhasil diambil.');
    }

    #[Endpoint(
        title: 'Tambah custom permission',
        description: 'Menambahkan permission baru ke guard web.'
    )]
    public function storePermission(Request $request): JsonResponse
    {
        $this->authorizePermission('create_permission');

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z0-9_:\-]+$/',
                Rule::unique('permissions', 'name')
                    ->where(fn ($query) => $query->where('guard_name', self::GUARD)),
            ],
        ], [
            'name.regex' => 'Permission hanya boleh memakai huruf, angka, underscore, titik dua, atau strip.',
        ]);

        $permission = Permission::query()->create([
            'name' => $validated['name'],
            'guard_name' => self::GUARD,
        ]);

        $this->clearPermissionCache();

        return $this->success([
            'id' => $permission->id,
            'name' => $permission->name,
            'group' => $this->permissionGroup($permission->name),
        ], 'Permission berhasil dibuat.', 201);
    }

    #[Endpoint(
        title: 'Hapus custom permission',
        description: 'Menghapus permission jika tidak sedang dipetakan pada role atau pengguna mana pun.'
    )]
    public function destroyPermission(Permission $permission): JsonResponse
    {
        $this->authorizePermission('delete_permission');
        $this->ensureWebGuard($permission);
        $permission->loadCount(['roles', 'users']);

        if (in_array($permission->name, self::LOCKED_PERMISSIONS, true)) {
            return $this->error('Permission sistem ini tidak boleh dihapus.', 422, null, 'LOCKED_PERMISSION');
        }

        if ($permission->roles_count > 0 || $permission->users_count > 0) {
            return $this->error('Permission masih dipakai oleh role atau user.', 422, null, 'PERMISSION_IN_USE');
        }

        $permission->delete();
        $this->clearPermissionCache();

        return $this->success(null, 'Permission berhasil dihapus.');
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        $ignoreId = $role?->id;
        $request->merge([
            'name' => str((string) $request->input('name'))->squish()->toString(),
        ]);

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9_:\- ]+$/',
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
        ], [
            'name.regex' => 'Role hanya boleh memakai huruf kecil, angka, spasi, underscore, titik dua, atau strip.',
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
