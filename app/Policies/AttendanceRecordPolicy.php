<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendanceRecordPolicy
{
    /**
     * 勤怠記録の一覧を閲覧できるか。
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 勤怠記録の詳細を閲覧できるか。
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return true;
    }

    /**
     * 勤怠記録を作成できるか。
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * 勤怠記録を更新できるか。
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $user->id === $attendance->user_id
            || $user->admin_status;
    }

    /**
     * 勤怠記録を削除できるか。
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->id === $attendance->user_id
            || $user->admin_status;
    }

    /**
     * 勤怠記録を復元できるか。
     */
    public function restore(User $user, Attendance $attendance): bool
    {
        return $user->id === $attendance->user_id
            || $user->admin_status;
    }

    /**
     * 勤怠記録を完全削除できるか。
     */
    public function forceDelete(User $user, Attendance $attendance): bool
    {
        return $user->admin_status;
    }
}
