<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\AdminAttendanceRequest;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{

    public function showAllList(Request $request)
    {
        $dateParam = $request->query('date', Carbon::today()->format('Y-m-d'));
        $currentDate = Carbon::parse($dateParam);

        $prevDate = $currentDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $currentDate->copy()->addDay()->format('Y-m-d');

        $attendances = Attendance::with(['user', 'rests'])
            ->whereDate('date', $currentDate)
            ->get();

        return view('admin.attendance-list', compact(
            'attendances',
            'currentDate',
            'prevDate',
            'nextDate'
        ));
    }

    public function showDetail(Request $request, $id)
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $id)) {
            $userId = $request->query('user_id');
            $targetDate = $id;

            $attendance = Attendance::with(['user', 'rests'])
                ->where('user_id', $userId)
                ->whereDate('date', $targetDate)
                ->first();

            if (!$attendance) {
                $user = User::findOrFail($userId);
                $attendance = new Attendance([
                    'user_id' => $userId,
                    'date'    => $targetDate,
                    'status'  => 0,
                ]);
                $attendance->setRelation('user', $user);
                $attendance->setRelation('rests', collect());
            }
        } else {
            $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);
        }
        return view('admin.attendance-detail', compact('attendance'));
    }

    public function showApprove($attendance_correct_request_id)
    {
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($attendance_correct_request_id);

        return view('stamp_correction_request.approve', compact('attendance'));
    }

    public function approve(Request $request, $attendance_correct_request_id)
    {
        $attendance = Attendance::findOrFail($attendance_correct_request_id);

        $attendance->update(['status' => 2]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '承認が完了しました'
            ]);
        }

        return redirect()->route('stamp_correction_request.list')
            ->with('success', '承認が完了しました');
    }

    public function staffList()
    {
        $users = \App\Models\User::where('role', 'user')->get();

        return view('admin.staff-list', compact('users'));
    }

    public function staffAttendance(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $targetMonth = $request->query('month', Carbon::now()->format('Y-m'));
        $currentDate = Carbon::parse($targetMonth)->startOfMonth();

        $calendar = [];
        $daysInMonth = $currentDate->daysInMonth;
        for ($i = 0; $i < $daysInMonth; $i++) {
            $calendar[] = $currentDate->copy()->addDays($i);
        }

        $attendances = Attendance::where('user_id', $id)
            ->whereMonth('date', $currentDate->month)
            ->whereYear('date', $currentDate->year)
            ->get()
            ->keyBy(function ($item) {
                $rawDate = $item->getAttributes()['date'];
                return Carbon::parse($rawDate)->format('Y-m-d');
            });

        $prevMonth = $currentDate->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentDate->copy()->addMonth()->format('Y-m');

        return view('admin.staff-attendance', [
            'user' => $user,
            'calendar' => $calendar,
            'attendances' => $attendances,
            'currentYear' => $currentDate->year,
            'currentMonth' => $currentDate->month,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
        ]);
    }

    public function downloadCsv(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $targetMonth = $request->query('month', Carbon::now()->format('Y-m'));
        $currentDate = Carbon::parse($targetMonth);
        $attendances = Attendance::with('rests')
            ->where('user_id', $id)
            ->whereMonth('date', $currentDate->month)
            ->whereYear('date', $currentDate->year)
            ->orderBy('date', 'asc')
            ->get();

        $callback = function () use ($attendances, $user, $targetMonth) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['日付', '出勤', '退勤', '休憩詳細', '休憩合計', '実労働時間']);
            foreach ($attendances as $atd) {
                $restDetails = $atd->rests->map(function ($rest) {
                    $start = $rest->start_time ?? '--:--';
                    $end = $rest->end_time ?? '--:--';
                    return "{$start}~{$end}";
                })->implode(' / ');

                fputcsv($file, [
                    $atd->date,
                    $atd->start_time ?? '',
                    $atd->end_time ?? '',
                    $restDetails,
                    $atd->total_rest_time,
                    $atd->total_work_time,
                ]);
            }
            fclose($file);
        };

        $fileName = "勤怠_{$user->name}_{$targetMonth}.csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        return response()->stream($callback, 200, $headers);
    }

    public function update(AdminAttendanceRequest $request, $id)
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $id)) {
            $attendance = Attendance::where('user_id', $request->query('user_id'))
                ->whereDate('date', $id)
                ->firstOrFail();
        } else {
            $attendance = Attendance::findOrFail($id);
        }

        $attendance->update([
            'start_time' => $request->start_time ?: null,
            'end_time'   => $request->end_time ?: null,
            'reason'     => $request->reason,
            'status'     => 2,
        ]);

        if ($request->has('rests')) {
            foreach ($request->rests as $restId => $restData) {
                $attendance->rests()->where('id', $restId)->update([
                    'start_time' => $restData['start'] ?: null,
                    'end_time'   => $restData['end'] ?: null,
                ]);
            }
        }

        if ($request->has('new_rests')) {
            foreach ($request->new_rests as $newRest) {
                if (!empty($newRest['start']) && !empty($newRest['end'])) {
                    $attendance->rests()->create([
                        'start_time' => $newRest['start'],
                        'end_time'   => $newRest['end'],
                    ]);
                }
            }
        }

        return redirect()->route('admin.attendance.list', ['id' => $attendance->id]);
    }
}
