<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Attendance\FormatAttendanceRecordsAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

/**
 * 管理者用のスタッフ一覧表示の Controller。
 */
class StaffController extends Controller
{
    /**
     * 一般ユーザー(管理者を除く)の一覧を氏名の昇順で表示する。
     *
     * @return View スタッフ一覧を含むビュー
     */
    public function index(): View
    {
        return view('admin.staff-list', [
            'users' => User::where('admin_status', false)->orderBy('name')->get(),
        ]);
    }

    /**
     * 指定したスタッフの月次勤怠一覧を表示する。
     *
     * @return View スタッフの月次勤怠一覧を含むビュー
     */
    public function show(User $id, FormatAttendanceRecordsAction $action): View
    {
        $validated = request()->validate([
            'date' => ['nullable', 'date_format:Y-m'],
        ]);

        $date = $validated['date'] ?? null
            ? CarbonImmutable::createFromFormat('Y-m', $validated['date'])->startOfMonth()
            : today()->startOfMonth()->toImmutable();

        return view('admin.staff-attendance-list', [
            'user' => $id,
            'date' => $date,
            'previousMonth' => $date->subMonth()->format('Y-m'),
            'nextMonth' => $date->addMonth()->format('Y-m'),
            'formattedAttendanceRecords' => $action($id, $date),
        ]);
    }
}
