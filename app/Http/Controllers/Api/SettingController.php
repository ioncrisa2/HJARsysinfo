<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Support\AppAccess;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

#[Group('Pengaturan Sistem', 'Pengaturan mode sistem, tema branding, dan manajemen cache.', weight: 15)]
class SettingController extends Controller
{
    use ApiResponse;
    use AuthorizesPermissions;

    #[Endpoint(
        title: 'Lihat seluruh pengaturan sistem',
        description: 'Mengembalikan seluruh konfigurasi sistem internal dan branding (memerlukan permission view_settings).'
    )]
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission('view_settings');

        $settings = SystemSetting::getAll();
        $logo = $settings['app_logo'] ?? null;
        $settings['app_logo_url'] = $logo ? Storage::disk('public')->url($logo) : null;

        $can = AppAccess::capabilityMap($request->user(), [
            'update' => 'update_settings',
            'clearCache' => 'clear_cache',
        ]);

        return $this->success([
            'settings' => $settings,
            'can' => $can,
        ], 'Pengaturan sistem berhasil diambil.');
    }

    #[Endpoint(
        title: 'Perbarui pengaturan sistem',
        description: 'Menyimpan perubahan mode sistem (live/maintenance/off), versi, warna primer, nama perusahaan, email support, dan upload logo baru.'
    )]
    public function update(Request $request): JsonResponse
    {
        $this->authorizePermission('update_settings');

        $validated = $request->validate([
            'system_mode' => 'nullable|string|in:live,maintenance,off',
            'app_version' => 'nullable|string|max:50',
            'primary_color' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'support_email' => 'nullable|email|max:255',
            'app_logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('app_logo')) {
            $oldLogo = SystemSetting::get('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $request->file('app_logo')->store('settings', 'public');
            $validated['app_logo'] = $path;
        }

        foreach ($validated as $key => $value) {
            if ($key === 'app_logo' && ! $request->hasFile('app_logo')) {
                continue;
            }

            SystemSetting::set($key, $value);
        }

        $settings = SystemSetting::getAll();
        $logo = $settings['app_logo'] ?? null;
        $settings['app_logo_url'] = $logo ? Storage::disk('public')->url($logo) : null;

        return $this->success($settings, 'Pengaturan berhasil diperbarui.');
    }

    #[Endpoint(
        title: 'Bersihkan cache aplikasi',
        description: 'Menjalankan pembersihan cache aplikasi, views, routes, dan konfigurasi framework.'
    )]
    public function clearCache(): JsonResponse
    {
        $this->authorizePermission('clear_cache');

        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');

        Cache::forget('system_settings');
        Cache::forget('pembanding_form_options');

        return $this->success(null, 'Semua cache berhasil dibersihkan.');
    }
}
