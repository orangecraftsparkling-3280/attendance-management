<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AttendanceRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');

        $attendance = Attendance::with('rests')
            ->where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        $status = '勤務外';

        if ($attendance) {
            if ($attendance->end_time) {
                $status = '退勤済';
            } else {
                $latestRest = $attendance->rests->last();

                if ($latestRest && is_null($latestRest->end_time)) {
                    $status = '休憩中';
                } else {
                    $status = '出勤中';
                }
            }
        }

        return view('attendance/index', compact('attendance', 'status'));
    }
    public function punchIn()
    {
        Attendance::create([
            'user_id' => auth()->id(),
            'date' => Carbon::today(),
            'start_time' => Carbon::now()->format('H:i'),
            'status' => 0,
        ]);
        return redirect()->back();
    }

    public function punchOut()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->where('date', Carbon::today())->first();
        $attendance->update(['end_time' => Carbon::now()->format('H:i')]);
        return redirect()->back();
    }

    public function restStart()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->where('date', Carbon::today())->first();
        $attendance->rests()->create(['start_time' => Carbon::now()->format('H:i')]);
        return redirect()->back();
    }

    public function restEnd()
    {
        $attendance = Attendance::where('user_id', auth()->id())
            ->where('date', Carbon::today())->first();
        $rest = $attendance->rests()->whereNull('end_time')->first();
        $rest->update(['end_time' => Carbon::now()->format('H:i')]);
        return redirect()->back();
    }

    public function list(Request $request)
    {
        $monthParam = $request->query('month', now()->format('Y-m'));
        $date = Carbon::parse($monthParam);

        $currentYear = $date->year;
        $currentMonth = $date->month;

        $prevMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        $calendar = [];
        $daysInMonth = $date->daysInMonth;
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $calendar[] = $date->copy()->day($i);
        }

        $attendances = Attendance::with('rests')
            ->where('user_id', auth()->id())
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->get()
            ->keyBy(function ($item) {
                return \Carbon\Carbon::parse($item->getAttributes()['date'])->format('Y-m-d');
            });

        return view('attendance.list', compact(
            'currentYear',
            'currentMonth',
            'prevMonth',
            'nextMonth',
            'calendar',
            'attendances'
        ));
    }

    public function detail($id)
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $id)) {
            $attendance = Attendance::with(['user', 'rests'])
                ->where('user_id', auth()->id())
                ->where('date', $id)
                ->first();
            $targetDate = $id;
        } else {
            $attendance = Attendance::with(['user', 'rests'])
                ->where('user_id', auth()->id())
                ->find($id);
            $targetDate = $attendance ? $attendance->getAttributes()['date'] : now()->format('Y-m-d');
        }

        if (!$attendance) {
            $attendance = new Attendance([
                'user_id' => auth()->id(),
                'date'    => $targetDate,
                'status'  => 0,
            ]);

            $attendance->setRawAttributes([
                'date'    => $targetDate,
                'user_id' => auth()->id(),
                'status'  => 0,
            ], true);

            $attendance->setRelation('user', auth()->user());
            $attendance->setRelation('rests', collect());
        }

        return view('attendance.detail', compact('attendance'));
    }

    public function update(AttendanceRequest $request, $id)
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $id)) {
            $targetDate = $id;
        } else {
            $targetDate = Attendance::where('user_id', auth()->id())
                ->findOrFail($id)
                ->getAttributes()['date'];
        }

        $attendance = Attendance::updateOrCreate(
            ['user_id' => auth()->id(), 'date' => $targetDate],
            [
                'start_time' => $request->start_time,
                'end_time'   => $request->end_time,
                'reason'     => $request->reason,
                'status'     => 1,
            ]
        );

        if ($request->has('rests')) {
            foreach ($request->rests as $restId => $restData) {
                $attendance->rests()->where('id', $restId)->update([
                    'start_time' => $restData['start'],
                    'end_time'   => $restData['end'],
                ]);
            }
        }

        if ($request->has('new_rests')) {
            foreach ($request->new_rests as $newData) {
                if (!empty($newData['start']) && !empty($newData['end'])) {
                    $attendance->rests()->create([
                        'start_time' => $newData['start'],
                        'end_time'   => $newData['end'],
                    ]);
                }
            }
        }

        return redirect()->route('stamp_correction_request.list')
            ->with('success', '修正申請を保存しました。管理者の承認をお待ちください。');
    }

    public function requestList(Request $request)
    {
        $tab = $request->query('tab', 'waiting');
        $status = ($tab === 'approved') ? 2 : 1;

        $query = Attendance::with('user')
            ->whereNotNull('reason')
            ->where('status', $status)
            ->orderBy('updated_at', 'desc');

        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', auth()->id());
        }

        $attendances = $query->get();

        return view('stamp_correction_request/list', compact('attendances', 'tab'));
    }
}
