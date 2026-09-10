<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Attendance\ArchiveAttendanceRecordAction;
use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrectRequest;
use App\Models\ProposalBreak;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 管理者用の修正申請の詳細表示・承認を提供する Controller。
 */
class AdminApplicationController extends Controller
{
    /**
     * 修正申請の詳細を表示する。
     *
     * @return View 修正申請の詳細を含むビュー
     */
    public function show(AttendanceCorrectRequest $attendance_correct_request_id): View
    {
        return view('admin.admin-application-detail', [
            'user' => $attendance_correct_request_id->user,
            'application' => $attendance_correct_request_id->load('proposalBreaks'),
        ]);
    }

    /**
     * 修正申請を承認する。上書きされる勤怠記録を履歴として保存したうえで、申請内容を反映する。
     *
     * @return RedirectResponse 承認後の詳細画面へのリダイレクト
     */
    public function update(AttendanceCorrectRequest $attendance_correct_request_id, ArchiveAttendanceRecordAction $archive): RedirectResponse
    {
        $archive($attendance_correct_request_id->attendanceRecord, $attendance_correct_request_id);

        $attendance_correct_request_id->attendanceRecord->update([
            'date' => $attendance_correct_request_id->new_date,
            'clock_in_time' => $attendance_correct_request_id->new_date->format('Y-m-d').' '.$attendance_correct_request_id->new_clock_in,
            'clock_out_time' => $attendance_correct_request_id->new_date->format('Y-m-d').' '.$attendance_correct_request_id->new_clock_out,
            'comment' => $attendance_correct_request_id->comment,
        ]);

        $attendance_correct_request_id->attendanceRecord->breaks()->delete();

        $attendance_correct_request_id->proposalBreaks->each(fn (ProposalBreak $break) => $attendance_correct_request_id->attendanceRecord->breaks()->create([
            'break_start_time' => $attendance_correct_request_id->new_date->format('Y-m-d').' '.$break->break_in,
            'break_end_time' => $break->break_out ? $attendance_correct_request_id->new_date->format('Y-m-d').' '.$break->break_out : null,
        ]));

        $attendance_correct_request_id->update(['approved_at' => now()]);

        return redirect()->route('application.index')->with('message', '承認しました');
    }
}
