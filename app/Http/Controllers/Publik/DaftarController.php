<?php

namespace App\Http\Controllers\Publik;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Services\ApplicantService;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Formulir pendaftaran publik 4 langkah. Data disimpan di sesi sampai dikirim.
 */
class DaftarController extends Controller
{
    private const DEFAULTS = [
        'nama' => '', 'nik' => '', 'ttl' => '', 'hp' => '', 'email' => '', 'pend' => 'SMA/SMK',
        'prog' => 'Tokutei Ginou · Kaigo', 'level' => 'Belum pernah belajar', 'ref' => 'Media sosial',
    ];

    public function show(Request $request): View|RedirectResponse
    {
        $step = (int) $request->session()->get('daftar.step', 1);

        return view('publik.daftar', [
            'step' => $step,
            'd' => $request->session()->get('daftar.data', self::DEFAULTS),
            'files' => $request->session()->get('daftar.files', []),
        ]);
    }

    public function store(Request $request, ApplicantService $service): RedirectResponse
    {
        $s = $request->session();
        $step = (int) $s->get('daftar.step', 1);
        $d = $s->get('daftar.data', self::DEFAULTS);
        $files = $s->get('daftar.files', []);

        if ($request->input('aksi') === 'kembali') {
            $s->put('daftar.data', array_merge($d, $request->only(array_keys(self::DEFAULTS))));
            $s->put('daftar.step', max(1, $step - 1));

            return redirect()->route('daftar');
        }

        if ($step === 1) {
            $d = array_merge($d, $request->validate([
                'nama' => ['required', 'string', 'max:120'],
                'nik' => ['required', 'regex:/^\d{16}$/'],
                'ttl' => ['required', 'string', 'max:120'],
                'hp' => ['required', 'string', 'max:30'],
                'email' => ['nullable', 'email'],
                'pend' => ['required', Rule::in(Catalog::EDUCATION)],
            ], [
                'nama.required' => 'Isi nama, tempat tanggal lahir, dan nomor WhatsApp.',
                'ttl.required' => 'Isi nama, tempat tanggal lahir, dan nomor WhatsApp.',
                'hp.required' => 'Isi nama, tempat tanggal lahir, dan nomor WhatsApp.',
                'nik.required' => 'NIK harus 16 digit angka.',
                'nik.regex' => 'NIK harus 16 digit angka.',
                'email.email' => 'Format email belum benar.',
            ]));
        } elseif ($step === 2) {
            $d = array_merge($d, $request->validate([
                'prog' => ['required', Rule::in(array_keys(Catalog::PROGRAMS))],
                'level' => ['required', Rule::in(Catalog::JP_LEVEL)],
                'ref' => ['required', Rule::in(Catalog::REFERRAL)],
            ]));
        } elseif ($step === 3) {
            $max = config('nihonbridge.upload_max_kb');
            $request->validate(collect(Catalog::APP_FILES)->mapWithKeys(fn ($f) => ["berkas.$f[0]" => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', "max:$max"]])->all(), [
                'berkas.*.max' => 'Berkas lebih dari 2 MB. Kecilkan ukurannya lalu unggah lagi.',
                'berkas.*.mimes' => 'Unggah foto (JPG/PNG) atau PDF.',
            ]);
            foreach ($request->file('berkas', []) as $key => $file) {
                if (! empty($files[$key]['path'])) {
                    Storage::delete($files[$key]['path']);
                }
                $files[$key] = [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize() > 1048576 ? number_format($file->getSize() / 1048576, 1) . ' MB' : max(1, round($file->getSize() / 1024)) . ' KB',
                    'path' => $file->store('pendaftaran/tmp'),
                ];
            }
            $s->put('daftar.files', $files);
            $missing = collect(Catalog::APP_FILES)->filter(fn ($f) => $f[2] && empty($files[$f[0]]))->map(fn ($f) => mb_strtolower($f[1]))->all();
            if ($missing && $request->input('aksi') !== 'unggah') {
                return back()->withErrors(['berkas' => 'Berkas wajib belum diunggah: ' . implode(', ', $missing) . '.']);
            }
            if ($request->input('aksi') === 'unggah') {
                return redirect()->route('daftar');
            }
        } elseif ($step === 4) {
            $request->validate(['setuju' => ['accepted']], ['setuju.accepted' => 'Centang pernyataan persetujuan untuk mengirim.']);
            $a = $service->register($d, $this->finalizeFiles($files));
            $s->forget(['daftar.step', 'daftar.data', 'daftar.files']);
            $s->put('daftar.done', $a->id);

            return redirect()->route('daftar.selesai');
        }

        $s->put('daftar.data', $d);
        $s->put('daftar.step', $step + 1);

        return redirect()->route('daftar');
    }

    private function finalizeFiles(array $files): array
    {
        foreach ($files as $k => $f) {
            if (! empty($f['path']) && Storage::exists($f['path'])) {
                $new = 'pendaftaran/' . basename($f['path']);
                Storage::move($f['path'], $new);
                $files[$k]['path'] = $new;
            }
        }

        return $files;
    }

    public function done(Request $request): View|RedirectResponse
    {
        $a = Applicant::find($request->session()->get('daftar.done'));
        if (! $a) {
            return redirect()->route('daftar');
        }

        return view('publik.daftar-selesai', ['a' => $a]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget(['daftar.step', 'daftar.data', 'daftar.files', 'daftar.done']);

        return redirect()->route('daftar');
    }
}
