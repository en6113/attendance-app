<?php

namespace App\Policies;

use App\Models\AttendanceRecord;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * 管理者は全操作を許可する。管理者でなければ各メソッドの判定に委ねる。
     */
    public function before(User $user, string $ability): ?bool
    {
        return $user->admin_status ? true : null;
    }

    /**
     * 本人の勤怠情報のみ閲覧を許可する。
     */
    public function view(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 本人の勤怠情報のみ更新を許可する。
     */
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 本人の勤怠情報のみ削除を許可する。
     */
    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        return $user->id === $attendanceRecord->user_id;
    }
}
