<?php

namespace App\Http\Controllers\Admin;

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
        $date = request('date')
            ? CarbonImmutable::createFromFormat('Y-m-d', request('date'))
            : today()->toImmutable();

        return view('admin.admin-attendance-list', [
            'date' => $date,
            'previousDay' => $date->subDay()->toDateString(),
            'nextDay' => $date->addDay()->toDateString(),
            'users' => User::all(),
            'attendanceRecords' => AttendanceRecord::whereDate('work_date', $date)->with('breaks')->get(),
        ]);
    }

    /**
     * 指定した勤怠記録の詳細を表示する。
     * 承認待ちの修正申請がある場合は、一般ユーザー用の勤怠詳細画面（読み取り専用）にリダイレクトする。
     *
     * @return View|RedirectResponse 勤怠詳細を含むビュー、または一般ユーザー詳細画面へのリダイレクト
     */
    public function show(AttendanceRecord $id): View|RedirectResponse
    {
        if ($id->correctRequests()->whereNull('approved_at')->exists()) {
            return redirect('/attendance/detail/'.$id->id);
        }

        return view('admin.admin-detail', [
            'user' => $id->user,
            'attendanceRecord' => [
                'id' => $id->id,
                'year' => $id->work_date->format('Y').'年',
                'date' => $id->work_date->isoFormat('M月D日'),
                'clock_in' => $id->clock_in,
                'clock_out' => $id->clock_out,
                'breaks' => $id->breaks->map(fn (BreakTime $break): array => [
                    'break_in' => $break->break_start_time?->format('H:i') ?? '',
                    'break_out' => $break->break_end_time?->format('H:i') ?? '',
                ])->all(),
                'comment' => $id->comment ?? '',
            ],
        ]);
    }

    /**
     * 管理者が勤怠記録を直接修正する。承認フローを経由せず、AttendanceRecordを即座に更新する。
     *
     * @return RedirectResponse 修正後の詳細画面へのリダイレクト
     */
    public function update(StoreRequest $request, AttendanceRecord $id): RedirectResponse
    {
        $id->update([
            'clock_in_time' => $id->work_date->format('Y-m-d').' '.$request->new_clock_in,
            'clock_out_time' => $id->work_date->format('Y-m-d').' '.$request->new_clock_out,
            'comment' => $request->comment,
        ]);

        $id->breaks()->delete();

        collect($request->new_break_in ?? [])
            ->filter(fn (?string $breakIn, int $index): bool => filled($breakIn) && filled($request->new_break_out[$index] ?? null))
            ->each(fn (string $breakIn, int $index) => $id->breaks()->create([
                'break_start_time' => $id->work_date->format('Y-m-d').' '.$breakIn,
                'break_end_time' => $id->work_date->format('Y-m-d').' '.$request->new_break_out[$index],
            ]));

        return redirect('/admin/attendance/'.$id->id)->with('message', '修正しました');
    }
}
