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
    /**
     * 全ユーザーの勤怠一覧表示
     */
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

    /**
     * 勤怠詳細画面表示
     */
    /**
     * 勤怠詳細画面表示（管理者用）
     */
    public function showDetail(Request $request, $id)
    {
        // 1. $id が 日付(Y-m-d) か ID(数字) かを判定
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $id)) {
            // 日付の場合：クエリパラメータから user_id を取得
            $userId = $request->query('user_id');
            $targetDate = $id;

            $attendance = Attendance::with(['user', 'rests'])
                ->where('user_id', $userId)
                ->whereDate('date', $targetDate)
                ->first();

            // レコードがない場合は、空のインスタンスを作る
            if (!$attendance) {
                $user = User::findOrFail($userId);
                $attendance = new Attendance([
                    'user_id' => $userId,
                    'date'    => $targetDate,
                    'status'  => 0, // 未作成状態
                ]);
                $attendance->setRelation('user', $user);
                $attendance->setRelation('rests', collect());
            }
        } else {
            // IDの場合：既存レコードを取得
            $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);
        }

        // 管理者用の詳細画面を表示
        return view('admin.attendance-detail', compact('attendance'));
    }

    /**
     * 修正申請承認画面を表示（管理者用）
     * パス: /stamp_correction_request/approve/{attendance_correct_request_id}
     */
    /**
     * 修正申請承認画面を表示
     */
    /**
     * 修正申請承認画面を表示 (GET)
     * ルート名: admin.stamp_correction_request.approve
     */
    public function showApprove($attendance_correct_request_id)
    {
        // 引数名をルート定義の {attendance_correct_request_id} と一致させる
        // 既存の Attendance モデルから、このIDに一致する勤怠データを取得
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($attendance_correct_request_id);

        // resources/views/stamp_correction_request/approve.blade.php を表示
        return view('stamp_correction_request.approve', compact('attendance'));
    }

    /**
     * 修正申請を承認する実行処理 (PATCH)
     */
    public function approve(Request $request, $attendance_correct_request_id)
    {
        // こちらも引数名を一致させる
        $attendance = Attendance::findOrFail($attendance_correct_request_id);

        // ステータスを「承認済み(2)」に更新
        $attendance->update(['status' => 2]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '承認が完了しました'
            ]);
        }

        // 共通の申請一覧画面（/stamp_correction_request/list）へ戻る
        return redirect()->route('stamp_correction_request.list')
            ->with('success', '承認が完了しました');
    }

    public function staffList()
    {
        // ロールが 'user' のスタッフのみ取得（管理者は除外する場合）
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
                // getAttributes()['date'] を使って、加工前の '2026-03-05' を取得する
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

        // 1. 対象月のデータを取得
        $attendances = Attendance::with('rests')
            ->where('user_id', $id)
            ->whereMonth('date', $currentDate->month)
            ->whereYear('date', $currentDate->year)
            ->orderBy('date', 'asc')
            ->get();

        // 2. CSVデータの作成
        $callback = function () use ($attendances, $user, $targetMonth) {
            $file = fopen('php://output', 'w');
            // 文字化け防止（Excel用BOM）
            fputs($file, $bom = chr(0xEF) . chr(0xBB) . chr(0xBF));

            // ヘッダー行
            fputcsv($file, ['日付', '出勤', '退勤', '休憩詳細', '休憩合計', '実労働時間']);

            // データ行
            // downloadCsv メソッド内の foreach 部分
            foreach ($attendances as $atd) {
                // 休憩時間のフォーマット
                $restDetails = $atd->rests->map(function ($rest) {
                    // すでにアクセサで文字列なら、そのまま使うか ?? '--:--' で受ける
                    $start = $rest->start_time ?? '--:--';
                    $end = $rest->end_time ?? '--:--';
                    return "{$start}~{$end}";
                })->implode(' / ');

                fputcsv($file, [
                    $atd->date,             // すでに「Y年m月d日」
                    $atd->start_time ?? '',  // すでに「H:i」
                    $atd->end_time ?? '',    // すでに「H:i」
                    $restDetails,
                    $atd->total_rest_time,
                    $atd->total_work_time,
                ]);
            }
            fclose($file);
        };

        // 3. レスポンスヘッダーの設定
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
        // $id が日付(Y-m-d)かIDかを判定して取得
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $id)) {
            $attendance = Attendance::where('user_id', $request->query('user_id'))
                ->whereDate('date', $id)
                ->firstOrFail();
        } else {
            $attendance = Attendance::findOrFail($id);
        }

        // 1. 出退勤・備考の更新
        // フォームから空で送られてきた場合は null として保存することで --:-- を防ぐ
        $attendance->update([
            'start_time' => $request->start_time ?: null,
            'end_time'   => $request->end_time ?: null,
            'reason'     => $request->reason,
            'status'     => 2,
        ]);

        // 2. 既存の休憩(rests)の更新
        if ($request->has('rests')) {
            foreach ($request->rests as $restId => $restData) {
                // 開始・終了どちらも空の場合は、レコードを削除するか、null更新する
                // ここでは一貫性を保つため null 更新（または値がある場合のみ更新）
                $attendance->rests()->where('id', $restId)->update([
                    'start_time' => $restData['start'] ?: null,
                    'end_time'   => $restData['end'] ?: null,
                ]);
            }
        }

        // 3. 新規追加分の休憩(new_rests)の登録
        if ($request->has('new_rests')) {
            foreach ($request->new_rests as $newRest) {
                // 【重要】開始と終了の両方が入力されている場合のみ保存
                if (!empty($newRest['start']) && !empty($newRest['end'])) {
                    $attendance->rests()->create([
                        'start_time' => $newRest['start'],
                        'end_time'   => $newRest['end'],
                    ]);
                }
            }
        }

        // 保存後に詳細画面へ戻る（日付でもIDでも対応可能にする）
        return redirect()->route('admin.attendance.list', ['id' => $attendance->id]);
    }
}
