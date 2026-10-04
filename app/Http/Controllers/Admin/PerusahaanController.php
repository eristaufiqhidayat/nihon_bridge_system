<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JobCandidate;
use App\Models\JobOrder;
use App\Models\Student;
use App\Services\ProgramService;
use App\Support\Catalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PerusahaanController extends Controller
{
    public function __construct(private ProgramService $program)
    {
    }

    public function index(Request $request): View
    {
        $jobs = JobOrder::with('company', 'candidates.student.user')->orderBy('code')->get();
        $job = $jobs->firstWhere('id', (int) $request->query('job')) ?? $jobs->first();

        return view('admin.perusahaan', [
            'summaries' => $this->program->companySummaries(),
            'jobs' => $jobs,
            'job' => $job,
            'eligible' => $job ? $this->program->eligible($job) : collect(),
            'readonly' => $request->user()->role === 'direktur',
            'companies' => Company::orderBy('nama')->get(),
        ]);
    }

    public function storeJob(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'posisi' => ['required', 'string', 'max:120'],
            'jalur' => ['required', Rule::in(['Tokutei Ginou', 'Magang (Ginou Jisshu)'])],
            'kuota' => ['required', 'integer', 'min:1', 'max:99'],
            'syarat' => ['required', 'string'],
            'interview' => ['required', 'string', 'max:120'],
        ]);
        $job = JobOrder::create($data + ['code' => JobOrder::nextCode(), 'status' => 'Seleksi kandidat']);

        return redirect()->route('perusahaan.index', ['job' => $job->id])->with('toast', "Job order {$job->code} dibuat");
    }

    public function addCandidate(JobOrder $job, Student $student): RedirectResponse
    {
        $this->program->addCandidate($job->load('company'), $student->load('user'));

        return back()->with('toast', 'Kandidat diajukan ke perusahaan');
    }

    public function setInterview(Request $request, JobCandidate $candidate): RedirectResponse
    {
        $hasil = $request->validate(['hasil' => ['required', Rule::in(array_keys(Catalog::INTERVIEW))]])['hasil'];
        $msg = $this->program->setInterview($candidate->load('student.user', 'jobOrder.company'), $hasil);

        return back()->with('toast', $msg);
    }
}
