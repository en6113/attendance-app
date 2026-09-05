<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\FormatAttendanceDetailAction;
use App\Actions\Attendance\FormatAttendanceRecordsAction;
use App\Http\Requests\Attendance\StoreRequest;
use App\Models\AttendanceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 一般ユーザー用の勤怠一覧、詳細表示、修正申請を提供する Controller。
 *
 * 複雑なロジックはActionに分離。
 * index : Actions/Attendance/FormatAttendanceRecordsAction
 * show : Actions/Attendance/FormatAttendanceDetailAction
 */
class AttendanceController extends Controller
{
    public function index(FormatAttendanceRecordsAction $action): View
    {
        $date = request('date')
            ? CarbonImmutable::createFromFormat('Y-m', request('date'))->startOfMonth()
            : today()->startOfMonth()->toImmutable();

        return view('user.user-attendance-list', [
            'date' => $date,
            'previousMonth' => $date->subMonth()->format('Y-m'),
            'nextMonth' => $date->addMonth()->format('Y-m'),
            'formattedAttendanceRecords' => $action(auth()->user(), $date),
        ]);
    }

    public function show(AttendanceRecord $id, FormatAttendanceDetailAction $action): View
    {
        $this->authorize('view', $id);

        return view('user.user-detail', [
            'user' => auth()->user(),
            'data' => $action($id),
        ]);
    }

    public function store(StoreRequest $request, AttendanceRecord $id): RedirectResponse
    {
        $correctRequest = $id->correctRequests()->create([
            'new_date' => $id->work_date,
            'new_clock_in' => $request->new_clock_in,
            'new_clock_out' => $request->new_clock_out,
            'comment' => $request->comment,
        ]);

        collect($request->new_break_in ?? [])
            ->filter(fn (?string $breakIn, int $index): bool => filled($breakIn) && filled($request->new_break_out[$index] ?? null))
            ->each(fn (string $breakIn, int $index) => $correctRequest->proposalBreaks()->create([
                'break_in' => $breakIn,
                'break_out' => $request->new_break_out[$index],
            ]));

        return redirect()->route('attendance.show', $id);
    }
}
