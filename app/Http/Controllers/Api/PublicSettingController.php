<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Traits\ApiResponse;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

#[Group('Pengaturan Publik', 'Informasi branding dan pengaturan publik aplikasi yang dapat diakses tanpa autentikasi.', weight: 16)]
class PublicSettingController extends Controller
{
    use ApiResponse;

    #[Endpoint(
        title: 'Lihat pengaturan publik aplikasi',
        description: 'Mengembalikan informasi branding aman seperti nama aplikasi, logo, versi aplikasi, nama perusahaan, dan email support.'
    )]
    public function show(): JsonResponse
    {
        $logo = SystemSetting::get('app_logo');
        $logoUrl = $logo ? Storage::disk('public')->url($logo) : null;

        $publicSettings = [
            'app_name' => config('app.name', 'HJAR Sysinfo'),
            'app_logo' => $logo,
            'app_logo_url' => $logoUrl,
            'app_version' => SystemSetting::get('app_version', env('API_VERSION', '1.0.0')),
            'company_name' => SystemSetting::get('company_name', 'KJPP HJAR'),
            'support_email' => SystemSetting::get('support_email', 'support@kjpp-hjar.co.id'),
        ];

        return $this->success($publicSettings, 'Pengaturan publik berhasil diambil.');
    }
}
