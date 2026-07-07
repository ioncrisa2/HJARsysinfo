<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\App\Concerns\AuthorizesPermissions;
use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Support\AppAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingController extends Controller
{
    use AuthorizesPermissions;

    /**
     * Display the settings form.
     */
    public function index()
    {
        $this->authorizePermission('view_settings');

        $settings = SystemSetting::getAll();

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
            'can' => AppAccess::capabilityMap(request()->user(), [
                'update' => 'update_settings',
                'clearCache' => 'clear_cache',
            ]),
        ]);
    }

    /**
     * Update the settings.
     */
    public function update(Request $request)
    {
        $this->authorizePermission('update_settings');

        $validated = $request->validate([
            'system_mode' => 'nullable|string|in:live,maintenance,off',
            'app_version' => 'nullable|string|max:50',
            'primary_color' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
            'support_email' => 'nullable|email|max:255',
            'app_logo' => 'nullable|image|max:2048', // Max 2MB image
        ]);

        // Handle file upload for logo
        if ($request->hasFile('app_logo')) {
            // Delete old logo if exists
            $oldLogo = SystemSetting::get('app_logo');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $request->file('app_logo')->store('settings', 'public');
            $validated['app_logo'] = $path;
        }

        // Save settings
        foreach ($validated as $key => $value) {
            // Skip null values if they were not provided,
            // except if you explicitly want to empty them.
            // In this logic we will update them even if null (to allow clearing settings).
            if ($key === 'app_logo' && ! $request->hasFile('app_logo')) {
                continue; // Do not overwrite logo with null if no new file was uploaded
            }

            SystemSetting::set($key, $value);
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui.');
    }

    /**
     * Clear application cache.
     */
    public function clearCache()
    {
        $this->authorizePermission('clear_cache');

        Artisan::call('cache:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        Artisan::call('config:clear');

        // Also forget our custom cache
        Cache::forget('system_settings');

        return redirect()->back()->with('success', 'Semua cache berhasil dibersihkan.');
    }
}
