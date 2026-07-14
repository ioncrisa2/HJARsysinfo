<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PembandingExportController extends Controller
{
    public function byFilter(Request $request): RedirectResponse
    {
        return redirect()->route('app.export.download', [
            ...$request->query(),
            'format' => $request->query('format', 'excel'),
            'scope' => 'filtered',
        ]);
    }
}
