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
    /**
     * 指定月（dateパラメータ、未指定時は当月）の勤怠一覧を表示する。
     *
     * @return View 当月/指定月の勤怠一覧を含むビュー
     */
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

    /**
     * 勤怠詳細を表示する。承認待ちの修正申請がある場合は、その申請内容を表示する。
     *
     * @return View 勤怠詳細を含むビュー
     */
    public function show(AttendanceRecord $attendanceRecord, FormatAttendanceDetailAction $action): View
    {
        $this->authorize('view', $attendanceRecord);

        return view('user.user-detail', [
            'user' => auth()->user(),
            'data' => $action($attendanceRecord),
        ]);
    }

    /**
     * 勤怠記録の修正申請を作成する。休憩の修正がある場合は、休憩ごとの修正申請も併せて作成する。
     *
     * @return RedirectResponse 申請後の勤怠詳細画面へのリダイレクト
     */
    public function store(StoreRequest $request, AttendanceRecord $attendanceRecord): RedirectResponse
    {
        $correctRequest = $attendanceRecord->correctRequests()->create([
            'new_date' => $attendanceRecord->date,
            'new_clock_in' => $request->new_clock_in,
            'new_clock_out' => $request->new_clock_out,
            'comment' => $request->comment,
            'application_date' => today(),
        ]);

        collect($request->new_break_in ?? [])
            ->filter(fn (?string $breakIn, int $index): bool => filled($breakIn) && filled($request->new_break_out[$index] ?? null))
            ->each(fn (string $breakIn, int $index) => $correctRequest->proposalBreaks()->create([
                'break_in' => $breakIn,
                'break_out' => $request->new_break_out[$index],
            ]));

        return redirect()->route('attendance.show', $attendanceRecord);
    }
}
