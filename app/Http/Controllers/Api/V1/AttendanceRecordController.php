<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧を取得する。
     */
    public function index(
        IndexAttendanceRecordRequest $request
    ): AnonymousResourceCollection {
        $query = Attendance::with(['user', 'breaks']);

        // ユーザーIDで絞り込む。
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        // 日付で絞り込む。
        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        // 月で絞り込む。
        if ($request->filled('month')) {
            $query->whereBetween('date', [
                $request->input('month').'-01',
                Carbon::createFromFormat(
                    'Y-m',
                    $request->input('month')
                )->endOfMonth()->toDateString(),
            ]);
        }

        // 新しい日付を先に表示する。
        $query->orderByDesc('date')->orderByDesc('id');

        $perPage = min($request->integer('per_page', 20), 100);

        $attendances = $query->paginate(
            $perPage,
            ['*'],
            'page',
            $request->integer('page', 1)
        );

        return AttendanceRecordResource::collection($attendances);
    }

    /**
     * 勤怠詳細を取得する。
     */
    public function show(int $attendanceRecord): AttendanceRecordResource
    {
        $attendance = Attendance::with([
            'user',
            'breaks',
            'applications.applicationBreaks',
        ])->findOrFail($attendanceRecord);

        return new AttendanceRecordResource($attendance);
    }

    /**
     * 勤怠情報を登録する。
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $user = $request->user();

        // 同じユーザー・同じ日付の勤怠が存在しないか確認する。
        $duplicate = Attendance::where('user_id', $user->id)
            ->whereDate('date', $request->input('date'))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'date' => 'この日付の勤怠は既に登録されています。',
            ]);
        }

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $request->input('date'),
            'clock_in' => $request->input('clock_in'),
            'clock_out' => $request->input('clock_out'),
            'comment' => $request->input('comment'),
        ]);

        return (new AttendanceRecordResource($attendance))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 勤怠情報を更新する。
     */
    public function update(
        UpdateAttendanceRecordRequest $request,
        int $attendanceRecord
    ): AttendanceRecordResource {
        $attendance = Attendance::findOrFail($attendanceRecord);

        // 本人または管理者かを確認する。
        $this->authorize('update', $attendance);

        $attendance->update($request->validated());

        return new AttendanceRecordResource($attendance->refresh());
    }

    /**
     * 勤怠情報を削除する。
     */
    public function destroy(int $attendanceRecord): Response
    {
        $attendance = Attendance::findOrFail($attendanceRecord);

        // 本人または管理者かを確認する。
        $this->authorize('delete', $attendance);

        $attendance->delete();

        return response()->noContent();
    }
}
