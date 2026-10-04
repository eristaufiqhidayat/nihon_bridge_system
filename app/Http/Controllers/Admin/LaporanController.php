<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Support\Catalog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function __construct(private ReportService $reports)
    {
    }

    public function index(Request $request): View
    {
        $years = $this->reports->years();
        $year = in_array((int) $request->query('tahun'), $years, true) ? (int) $request->query('tahun') : ($years[0] ?? now()->year);
        $level = in_array($request->query('level'), Catalog::LEVELS, true) ? $request->query('level') : 'Semua';

        return view('admin.laporan', $this->reports->forYear($year) + [
            'years' => $years,
            'year' => $year,
            'level' => $level,
            'trend' => $this->reports->trend(),
        ]);
    }

    public function export(Request $request): Response
    {
        $year = (int) $request->query('tahun', now()->year);

        return response($this->reports->csv($year), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"laporan-nihon-bridge-$year.csv\"",
        ]);
    }
}
