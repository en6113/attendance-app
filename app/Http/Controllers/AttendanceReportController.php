<?php

namespace App\Http\Controllers;

use App\Actions\Attendance\BuildAttendanceReportAction;
use Illuminate\View\View;

/**
 * 一般ユーザー用のマイ勤怠レポートを提供する Controller。
 *
 * 統計用のロジックはActionに分離し、他実装でも使用するロジックはAttendanceRecordモデルにアクセサとして記載。
 */
class AttendanceReportController extends Controller
{
    /**
     * 当月のマイ勤怠レポート（統計情報）を表示する。
     *
     * @return View 当月の勤怠統計を含むビュー
     */
    public function index(BuildAttendanceReportAction $action): View
    {
        return view('reports.index', $action(auth()->user(), today()->startOfMonth()->toImmutable()));
    }
}
