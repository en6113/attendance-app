<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\AttendanceActionRequest;
use App\Models\AttendanceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AttendanceRecordController extends Controller
{
    public function index(): View
    {
        return view('user.attendance-register', [
            'user' => auth()->user(),
            'formattedDate' => now()->isoFormat('YYYY年MM月DD日(ddd)'),
            'formattedTime' => now()->format('H:i'),
        ]);
    }

    public function store(AttendanceActionRequest $request): RedirectResponse
    {
        match ($request->action) {
            'clock_in' => $this->clockIn(),
            'clock_out' => $this->clockOut(),
            'break_in' => $this->breakIn(),
            'break_out' => $this->breakOut(),
        };

        return redirect()->route('attendance-register');
    }

    private function clockIn(): void
    {
        AttendanceRecord::create([
            'user_id' => auth()->id(),
            'work_date' => today(),
            'clock_in_time' => now(),
        ]);
    }

    private function clockOut(): void
    {
        $this->openRecord()->update(['clock_out_time' => now()]);
    }

    private function breakIn(): void
    {
        $this->openRecord()->breaks()->create([
            'break_start_time' => now(),
        ]);
    }

    private function breakOut(): void
    {
        $this->openRecord()
            ->breaks()
            ->whereNull('break_end_time')
            ->latest('break_start_time')
            ->first()
            ->update(['break_end_time' => now()]);
    }

    private function openRecord(): AttendanceRecord
    {
        return auth()->user()
            ->attendanceRecords()
            ->whereNull('clock_out_time')
            ->latest('work_date')
            ->firstOrFail();
    }
}
