<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function __invoke(Request $request): View
    {
        $student = $request->user()->student()->with('documents', 'classroom', 'user', 'candidacies.jobOrder.company')->firstOrFail();

        return view('peserta.program', ['student' => $student, 'lastAttempt' => $student->lastAttempt()]);
    }
}
