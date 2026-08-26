<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\UserStoreRequest;
use App\Http\Requests\App\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\AppAccess;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

#[Group('Manajemen Pengguna', 'Pengelolaan akun pengguna, status aktivasi, dan assignment role.', weight: 7)]
class UserController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    private const ROLE_LABELS = [
        'pimpinan' => 'Pimpinan',
        'data_contributor' => 'Kontributor Data',
        'super_admin' => 'Super Admin',
    ];

    #[Endpoint(
        title: 'Lihat daftar pengguna',
        description: 'Mengembalikan daftar akun pengguna terpaginasi dengan filter pencarian nama/email, filter role, dan status aktif/nonaktif.'
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('view_any_user');

        $users = User::with('roles')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
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
            ->paginate((int) $request->integer('per_page', 10));

        $can = AppAccess::capabilityMap($request->user(), [
            'create' => 'create_user',
            'update' => 'update_user',
            'delete' => 'delete_user',
            'deleteAny' => 'delete_any_user',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Daftar pengguna berhasil diambil.',
            'data' => UserResource::collection($users->getCollection())->resolve($request),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ],
            'can' => $can,
        ]);
    }

    #[Endpoint(
        title: 'Lihat detail pengguna',
        description: 'Mengembalikan informasi profil dan role akun pengguna tertentu.'
    )]
    public function show(User $user): JsonResponse
    {
        $this->authorizePermission('view_any_user');
        $user->load('roles');

        return $this->success(new UserResource($user), 'Detail pengguna berhasil diambil.');
    }

    #[Endpoint(
        title: 'Tambah pengguna baru',
        description: 'Mendaftarkan akun pengguna baru beserta role awal.'
    )]
    public function store(UserStoreRequest $request): JsonResponse
    {
        $this->authorizePermission('create_user');
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'deactivated_at' => $validated['is_active'] ? null : now(),
        ]);

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        $user->load('roles');

        return $this->success(new UserResource($user), 'Pengguna berhasil dibuat.', 201);
    }

    #[Endpoint(
        title: 'Perbarui pengguna',
        description: 'Memperbarui nama, email, password opsional, status aktivasi, dan role pengguna.'
    )]
    public function update(UserUpdateRequest $request, User $user): JsonResponse
    {
        $this->authorizePermission('update_user');
        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->deactivated_at = $validated['is_active'] ? null : now();

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        if (isset($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        $user->load('roles');

        return $this->success(new UserResource($user), 'Pengguna berhasil diperbarui.');
    }

    #[Endpoint(
        title: 'Ubah status aktif/nonaktif pengguna',
        description: 'Mengaktifkan atau menonaktifkan akun pengguna.'
    )]
    public function toggleStatus(User $user): JsonResponse
    {
        $this->authorizePermission('update_user');

        $user->deactivated_at = $user->deactivated_at ? null : now();
        $user->save();

        $status = $user->deactivated_at ? 'dinonaktifkan' : 'diaktifkan';

        return $this->success([
            'id' => $user->id,
            'is_active' => $user->deactivated_at === null,
            'deactivated_at' => $user->deactivated_at?->toIso8601String(),
        ], "Pengguna berhasil {$status}.");
    }

    #[Endpoint(
        title: 'Hapus pengguna',
        description: 'Menghapus akun pengguna dari sistem.'
    )]
    public function destroy(User $user): JsonResponse
    {
        $this->authorizePermission('delete_user');

        if (auth()->id() === $user->id) {
            return $this->error('Anda tidak dapat menghapus akun Anda sendiri.', 422, null, 'CANNOT_DELETE_SELF');
        }

        $user->delete();

        return $this->success(null, 'Pengguna berhasil dihapus.');
    }

    #[Endpoint(
        title: 'Hapus pengguna massal (bulk delete)',
        description: 'Menghapus beberapa akun pengguna sekaligus berdasarkan daftar ID.'
    )]
    public function bulkDelete(Request $request): JsonResponse
    {
        $this->authorizePermission('delete_any_user');

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:users,id'],
        ]);

        $ids = collect($validated['ids'])->reject(fn ($id) => (int) $id === (int) auth()->id())->values();

        User::whereIn('id', $ids)->delete();

        return $this->success([
            'deleted_count' => $ids->count(),
        ], $ids->count().' pengguna berhasil dihapus.');
    }

    #[Endpoint(
        title: 'Opsi role pengguna',
        description: 'Mengembalikan daftar pilihan role yang tersedia dalam sistem untuk dropdown form.'
    )]
    public function roleOptions(): JsonResponse
    {
        $roles = Role::all()->map(fn ($role) => [
            'value' => $role->name,
            'label' => self::ROLE_LABELS[$role->name] ?? ucwords(str_replace('_', ' ', $role->name)),
        ]);

        return $this->success($roles, 'Daftar opsi role berhasil diambil.');
    }
}
