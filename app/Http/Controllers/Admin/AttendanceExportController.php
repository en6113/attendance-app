<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Attendance\FormatAttendanceRecordsAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 管理者用のスタッフ別勤怠情報CSV出力の Controller。
 */
class AttendanceExportController extends Controller
{
    /**
     * 指定したスタッフの月次勤怠一覧をCSVでダウンロードする。
     *
     * @return StreamedResponse CSVファイルのストリーミングレスポンス
     */
    public function export(Request $request, FormatAttendanceRecordsAction $action): StreamedResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'year_month' => ['required', 'date_format:Y-m'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $date = CarbonImmutable::createFromFormat('Y-m', $validated['year_month'])->startOfMonth();

        $formattedAttendanceRecords = $action($user, $date);

        $fileName = "{$user->name}_{$date->format('Y-m')}.csv";

        return response()->streamDownload(function () use ($formattedAttendanceRecords): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['日付', '出勤', '退勤', '休憩', '合計']);

            $formattedAttendanceRecords->each(function (array $record) use ($stream): void {
                fputcsv($stream, [
                    $record['date'],
                    $record['clock_in'],
                    $record['clock_out'],
                    $record['total_break_time'] ? Carbon::parse($record['total_break_time'])->format('G:i') : '',
                    $record['total_time'] ? Carbon::parse($record['total_time'])->format('G:i') : '',
                ]);
            });

            fclose($stream);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
