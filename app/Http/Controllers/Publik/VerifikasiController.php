<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerifikasiController extends Controller
{
    public function __invoke(Request $request, ?string $no = null): View
    {
        $no = strtoupper(trim((string) ($no ?? $request->query('no', ''))));

        return view('publik.verifikasi', [
            'no' => $no,
            'checked' => $no !== '',
            'cert' => $no !== '' ? Certificate::with('user')->where('number', $no)->first() : null,
        ]);
    }
}
