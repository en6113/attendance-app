<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Models\AttendanceRecord;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * 外部アプリケーションが勤怠データを取得・操作するための公開API用の Controller。
 */
class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧をJSONで取得する。
     *
     * @return AnonymousResourceCollection 勤怠一覧のページネーション付きレスポンス
     */
    public function index(IndexAttendanceRecordRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();

        $attendanceRecords = AttendanceRecord::with('user', 'breaks')
            ->when($validated['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->when($validated['date'] ?? null, fn ($query, $date) => $query->whereDate('date', $date))
            ->when($validated['month'] ?? null, function ($query, $month): void {
                $month = CarbonImmutable::createFromFormat('Y-m', $month);
                $query->whereBetween('date', [$month->startOfMonth()->toDateString(), $month->endOfMonth()->toDateString()]);
            })
            ->latest('date')
            ->paginate($validated['per_page'] ?? 20);

        return AttendanceRecordResource::collection($attendanceRecords);
    }

    /**
     * 勤怠詳細をJSONで取得する。
     *
     * @return AttendanceRecordResource 勤怠詳細のレスポンス
     */
    public function show(AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $attendanceRecord->load(['user', 'breaks', 'correctRequests']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠を新規登録する。
     *
     * @return JsonResponse 登録した勤怠のレスポンス（201）
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $attendanceRecord = $request->user()->attendanceRecords()->create([
            'date' => $validated['date'],
            'clock_in_time' => $validated['date'].' '.$validated['clock_in'],
            'clock_out_time' => isset($validated['clock_out']) ? $validated['date'].' '.$validated['clock_out'] : null,
            'comment' => $validated['comment'] ?? null,
        ]);

        $attendanceRecord->load(['user', 'breaks']);

        return (new AttendanceRecordResource($attendanceRecord))->response()->setStatusCode(201);
    }

    /**
     * 勤怠を更新する。
     *
     * @return AttendanceRecordResource 更新後の勤怠のレスポンス
     */
    public function update(UpdateAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord): AttendanceRecordResource
    {
        $this->authorize('update', $attendanceRecord);

        $validated = $request->validated();
        $date = $validated['date'] ?? $attendanceRecord->date->format('Y-m-d');

        $attendanceRecord->update([
            'date' => $date,
            'clock_in_time' => isset($validated['clock_in']) ? $date.' '.$validated['clock_in'] : $attendanceRecord->clock_in_time,
            'clock_out_time' => array_key_exists('clock_out', $validated)
                ? ($validated['clock_out'] !== null ? $date.' '.$validated['clock_out'] : null)
                : $attendanceRecord->clock_out_time,
            'comment' => array_key_exists('comment', $validated) ? $validated['comment'] : $attendanceRecord->comment,
        ]);

        $attendanceRecord->load(['user', 'breaks']);

        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠を削除する。
     */
    public function destroy(AttendanceRecord $attendanceRecord): Response
    {
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        return response()->noContent();
    }
}
