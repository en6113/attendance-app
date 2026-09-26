<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Attendance\BuildOldAttendanceSnapshotAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\StoreRequest;
use App\Models\AttendanceRecord;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 管理者用の日次勤怠一覧・詳細表示・直接修正の Controller。
 */
class AdminAttendanceController extends Controller
{
    /**
     * 指定日の全ユーザーの勤怠情報を一覧表示する。
     *
     * @return View その日の勤怠一覧を含むビュー
     */
    public function index(): View
    {
        $validated = request()->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $date = $validated['date'] ?? null
            ? CarbonImmutable::createFromFormat('Y-m-d', $validated['date'])
            : today()->toImmutable();

        return view('admin.admin-attendance-list', [
            'date' => $date,
            'previousDay' => $date->subDay()->toDateString(),
            'nextDay' => $date->addDay()->toDateString(),
            'users' => User::all(),
            'attendanceRecords' => AttendanceRecord::whereDate('date', $date)->with('breaks')->get()->keyBy('user_id'),
        ]);
    }

    /**
     * 指定した勤怠記録の詳細を表示する。
     * 承認待ちの修正申請がある場合は、一般ユーザー用の勤怠詳細画面（読み取り専用）にリダイレクトする。
     *
     * @return View|RedirectResponse 勤怠詳細を含むビュー、または一般ユーザー詳細画面へのリダイレクト
     */
    public function show(AttendanceRecord $attendanceRecord): View|RedirectResponse
    {
        if ($attendanceRecord->correctRequests()->whereNull('approved_at')->exists()) {
            return redirect('/attendance/detail/'.$attendanceRecord->id);
        }

        return view('admin.admin-detail', [
            'user' => $attendanceRecord->user,
            'attendanceRecord' => [
                'id' => $attendanceRecord->id,
                'year' => $attendanceRecord->date->format('Y').'年',
                'date' => $attendanceRecord->date->isoFormat('M月D日'),
                'clock_in' => $attendanceRecord->clock_in,
                'clock_out' => $attendanceRecord->clock_out,
                'breaks' => $attendanceRecord->breaks->map(fn (BreakTime $break): array => [
                    'break_in' => $break->break_start_time?->format('H:i') ?? '',
                    'break_out' => $break->break_end_time?->format('H:i') ?? '',
                ])->all(),
                'comment' => $attendanceRecord->comment ?? '',
            ],
        ]);
    }

    /**
     * 管理者が勤怠記録を直接修正する。
     * attendance_correct_requestに直接修正として記録を残したうえで、
     * 承認フローを経由せず、AttendanceRecordを即座に更新する。
     *
     * @return RedirectResponse 修正後の詳細画面へのリダイレクト
     */
    public function update(StoreRequest $request, AttendanceRecord $attendanceRecord, BuildOldAttendanceSnapshotAction $buildSnapshot): RedirectResponse
    {
        if ($attendanceRecord->correctRequests()->whereNull('approved_at')->exists()) {
            return redirect('/attendance/detail/'.$attendanceRecord->id);
        }

        $attendanceRecord->correctRequests()->create($buildSnapshot($attendanceRecord) + [
            'is_direct_edit' => true,
            'new_date' => $attendanceRecord->date,
            'new_clock_in' => $request->new_clock_in,
            'new_clock_out' => $request->new_clock_out,
            'comment' => $request->comment,
            'approved_at' => now(),
            'application_date' => today(),
        ]);

        $attendanceRecord->update([
            'clock_in_time' => $attendanceRecord->date->format('Y-m-d').' '.$request->new_clock_in,
            'clock_out_time' => $attendanceRecord->date->format('Y-m-d').' '.$request->new_clock_out,
            'comment' => $request->comment,
        ]);

        $attendanceRecord->breaks()->delete();

        collect($request->new_break_in ?? [])
            ->filter(fn (?string $breakIn, int $index): bool => filled($breakIn) && filled($request->new_break_out[$index] ?? null))
            ->each(fn (string $breakIn, int $index) => $attendanceRecord->breaks()->create([
                'break_start_time' => $attendanceRecord->date->format('Y-m-d').' '.$breakIn,
                'break_end_time' => $attendanceRecord->date->format('Y-m-d').' '.$request->new_break_out[$index],
            ]));

        return redirect('/admin/attendance/'.$attendanceRecord->id)->with('message', '修正しました');
    }
}
