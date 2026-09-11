<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\BuildAttendanceReportAction;
use Illuminate\View\View;

class AttendanceReportController extends Controller
{
    public function index(BuildAttendanceReportAction $action): View
    {
        return view('reports.index', $action(auth()->user(), today()->startOfMonth()->toImmutable()));
    }
}
