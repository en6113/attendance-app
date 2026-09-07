<?php

namespace App\Http\Controllers;

use App\Models\AttendanceCorrectRequest;
use Illuminate\View\View;

/**
 * 一般ユーザーの申請一覧用の Controller。
 */
class ApplicationController extends Controller
{
    /**
     * ログイン中のユーザーが行った修正申請の一覧を取得し、申請一覧画面を表示する。
     *
     * @return View 承認待ち・承認済みに分類された申請一覧を含むビュー
     */
    public function index(): View
    {
        $formattedApplications = AttendanceCorrectRequest::query()
            ->whereHas('attendanceRecord', fn ($query) => $query->where('user_id', auth()->id()))
            ->latest()
            ->get()
            ->map(fn (AttendanceCorrectRequest $request): array => [
                'id' => $request->attendance_record_id,
                'approval_status' => $request->approval_status,
                'date' => $request->new_date->isoFormat('YYYY/MM/DD'),
                'comment' => $request->comment,
                'application_date' => $request->created_at->format('Y/m/d'),
            ]);

        return view('user.user-application-list', [
            'user' => auth()->user(),
            'formattedApplications' => $formattedApplications,
        ]);
    }
}
