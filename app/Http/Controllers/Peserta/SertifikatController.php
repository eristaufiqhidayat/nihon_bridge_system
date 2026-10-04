<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SertifikatController extends Controller
{
    public function index(Request $request, ?Certificate $certificate = null): View
    {
        $certs = $request->user()->certificates()->orderByDesc('issued_at')->orderByDesc('id')->get();
        if ($certificate) {
            abort_unless($certificate->user_id === $request->user()->id, 404);
        }

        return view('peserta.sertifikat', ['certs' => $certs, 'cert' => $certificate ?? $certs->first()]);
    }

    /** Versi cetak / simpan sebagai PDF lewat dialog cetak browser. */
    public function print(Request $request, Certificate $certificate): View
    {
        abort_unless($certificate->user_id === $request->user()->id, 404);

        return view('peserta.sertifikat-cetak', ['cert' => $certificate->load('user')]);
    }

    public function email(Request $request, Certificate $certificate): RedirectResponse
    {
        $user = $request->user();
        abort_unless($certificate->user_id === $user->id, 404);
        Mail::raw(
            "Halo {$user->name},\n\nBerikut sertifikat Anda dari LPK Nihon Bridge:\n{$certificate->title} · No. {$certificate->number} · Nilai {$certificate->score}\n\nVerifikasi: {$certificate->verifyUrl()}\nCetak/unduh: " . route('sertifikat.print', $certificate),
            fn ($m) => $m->to($user->email)->subject("Sertifikat {$certificate->number}")
        );

        return back()->with('toast', "Sertifikat dikirim ke {$user->email}");
    }
}
