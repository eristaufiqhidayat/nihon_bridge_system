<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Services\LearningService;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MateriAdminController extends Controller
{
    public function __construct(private LearningService $learning)
    {
    }

    public function index(Request $request): View
    {
        $level = in_array($request->query('level'), Catalog::LEVELS, true) ? $request->query('level') : 'N4';
        $chapters = Chapter::with('lessons')->where('level', $level)->orderBy('no')->get();
        $chapter = $chapters->firstWhere('id', (int) $request->query('bab')) ?? $chapters->firstWhere('no', 15) ?? $chapters->first();

        return view('shared.materi-admin', compact('level', 'chapters', 'chapter'));
    }

    public function storeChapter(Request $request): RedirectResponse
    {
        $level = $request->validate(['level' => ['required', Rule::in(Catalog::LEVELS)]])['level'];
        $c = $this->learning->addChapter($level);

        return redirect()->route('materi-admin.index', ['level' => $level, 'bab' => $c->id])->with('toast', "Bab {$c->no} dibuat");
    }

    public function updateChapter(Request $request, Chapter $chapter): RedirectResponse
    {
        $data = $request->validate(['judul' => ['sometimes', 'required', 'string', 'max:120'], 'is_locked' => ['sometimes', 'boolean']]);
        $chapter->update($data);
        $msg = array_key_exists('is_locked', $data)
            ? ($chapter->is_locked ? 'Bab dikunci sampai bab sebelumnya selesai' : 'Bab bisa dibuka kapan saja')
            : 'Judul bab disimpan';

        return back()->with('toast', $msg);
    }

    public function storeLesson(Request $request, Chapter $chapter): RedirectResponse
    {
        $data = $request->validate([
            'judul' => ['required', 'string', 'max:160'],
            'jenis' => ['required', Rule::in(array_keys(Catalog::LESSON_TYPES))],
            'durasi' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(['Terbit', 'Draf'])],
            'berkas' => ['nullable', 'file', 'mimes:mp4,webm,mov,mp3,m4a,wav,pdf,ppt,pptx', 'max:1048576'],
        ], ['judul.required' => 'Judul bagian wajib diisi.']);
        $l = $this->learning->addLesson($chapter, $data, $request->file('berkas'));

        return redirect()->route('materi-admin.index', ['level' => $chapter->level, 'bab' => $chapter->id])
            ->with('toast', "\"{$l->judul}\" tersimpan" . ($l->status === 'Terbit' ? ' dan terbit' : ' sebagai draf'));
    }

    public function toggle(Lesson $lesson): RedirectResponse
    {
        $this->learning->toggleStatus($lesson);

        return back()->with('toast', "{$lesson->judul}: {$lesson->status}");
    }

    public function move(Request $request, Lesson $lesson): RedirectResponse
    {
        $this->learning->move($lesson, $request->input('arah') === 'naik' ? -1 : 1);

        return back();
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $this->learning->delete($lesson);

        return back()->with('toast', 'Bagian dihapus');
    }
}
