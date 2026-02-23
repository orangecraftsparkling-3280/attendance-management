<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * 打刻ページ表示 & ステータス判定
     */
    public function index()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', Carbon::today())
            ->first();

        $status = '勤務外';

        if ($attendance) {
            if ($attendance->end_time) {
                $status = '退勤済';
            }
            // 紐付く休憩の中で終了時間(end_time)が空のものが1つでもあるか
            elseif ($attendance->rests()->whereNull('end_time')->exists()) {
                $status = '休憩中';
            } else {
                $status = '出勤中';
            }
        }

        return view('attendance', compact('status'));
    }

    /**
     * 出勤処理
     */
    public function punchIn()
    {
        Attendance::create([
            'user_id'    => Auth::id(),
            'date'       => Carbon::today(),
            'start_time' => Carbon::now()->format('H:i'),
        ]);

        return redirect()->back();
    }

    /**
     * 休憩開始処理（複数回対応）
     */
    public function restStart()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', Carbon::today())
            ->first();

        if ($attendance) {
            // 新しい休憩レコードを作成
            $attendance->rests()->create([
                'start_time' => Carbon::now()->format('H:i'),
            ]);
        }

        return redirect()->back();
    }

    /**
     * 休憩終了処理（複数回対応）
     */
    public function restEnd()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', Carbon::today())
            ->first();

        if ($attendance) {
            // まだ終わっていない（end_timeがNULL）最新の休憩を1件取得
            $latestRest = $attendance->rests()
                ->whereNull('end_time')
                ->latest()
                ->first();

            if ($latestRest) {
                $latestRest->update([
                    'end_time' => Carbon::now()->format('H:i'),
                ]);
            }
        }

        return redirect()->back();
    }

    /**
     * 退勤処理
     */
    public function punchOut()
    {
        $attendance = Attendance::where('user_id', Auth::id())
            ->where('date', Carbon::today())
            ->first();

        if ($attendance) {
            $attendance->update([
                'end_time' => Carbon::now()->format('H:i'),
            ]);
        }

        return redirect()->back();
    }
}
