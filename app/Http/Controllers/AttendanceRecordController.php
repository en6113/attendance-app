<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\ActionRequest;
use App\Models\AttendanceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * 一般ユーザーの勤怠打刻（出勤・休憩入・休憩戻・退勤）用の Controller。
 *
 * ActionRequestで勤怠ステータスの矛盾をチェックする。
 */
class AttendanceRecordController extends Controller
{
    /**
     * 勤怠登録画面（打刻画面）を表示する。
     *
     * @return View 現在日時を含む勤怠登録画面のビュー
     */
    public function index(): View
    {
        return view('user.attendance-register', [
            'user' => auth()->user(),
            'formattedDate' => now()->isoFormat('YYYY年MM月DD日(ddd)'),
            'formattedTime' => now()->format('H:i'),
        ]);
    }

    /**
     * 打刻リクエストを受け付け、actionの値（出勤・退勤・休憩入・休憩戻）に応じて処理を振り分ける。
     *
     * @return RedirectResponse 勤怠登録画面へのリダイレクト
     */
    public function store(ActionRequest $request): RedirectResponse
    {
        match ($request->action) {
            'clock_in' => $this->clockIn(),
            'clock_out' => $this->clockOut(),
            'break_in' => $this->breakIn(),
            'break_out' => $this->breakOut(),
        };

        return redirect()->route('attendance-register');
    }

    /**
     * 出勤を記録する。当日の勤怠記録を新規作成し、出勤時刻を現在時刻で登録する。
     */
    private function clockIn(): void
    {
        AttendanceRecord::create([
            'user_id' => auth()->id(),
            'date' => today(),
            'clock_in_time' => now(),
        ]);
    }

    /**
     * 退勤を記録する。打刻中の勤怠記録の退勤時刻を現在時刻で更新する。
     */
    private function clockOut(): void
    {
        $this->openRecord()->update(['clock_out_time' => now()]);
    }

    /**
     * 休憩開始を記録する。打刻中の勤怠記録に紐づく休憩記録を新規作成し、休憩開始時刻を現在時刻で登録する。
     */
    private function breakIn(): void
    {
        $this->openRecord()->breaks()->create([
            'break_start_time' => now(),
        ]);
    }

    /**
     * 休憩終了を記録する。打刻中の勤怠記録のうち、終了していない直近の休憩記録の休憩終了時刻を現在時刻で更新する。
     */
    private function breakOut(): void
    {
        $this->openRecord()
            ->breaks()
            ->whereNull('break_end_time')
            ->latest('break_start_time')
            ->first()
            ->update(['break_end_time' => now()]);
    }

    /**
     * 退勤済みでない、認証中ユーザーの直近の勤怠記録を取得する。存在しない場合は404を返す。
     *
     * @return AttendanceRecord 打刻中（未退勤）の勤怠記録
     */
    private function openRecord(): AttendanceRecord
    {
        return auth()->user()
            ->attendanceRecords()
            ->whereNull('clock_out_time')
            ->latest('date')
            ->firstOrFail();
    }
}
