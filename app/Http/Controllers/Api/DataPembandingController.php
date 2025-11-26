<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pembanding;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class DataPembandingController extends Controller
{
    public function index(): JsonResponse
    {
        $pembandings = Pembanding::query()
            ->with('province:id,name',
            'regency:id,name',
            'district:id,name',
            'village:id,name',
            'creator:id,name,email',
        )->latest()->get();

        return response()->json([]);
    }
}
