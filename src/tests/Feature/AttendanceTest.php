<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    // プロパティを宣言してエラーを防止
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        // テスト用の一般ユーザーを作成
        $this->user = User::factory()->create(['role' => 'user']);
    }

    /**
     * 【ステータス確認機能】
     * 状態（勤務外・出勤中など）が正しく表示されるか
     */
    /**
     * 【ステータス確認機能】
     */
    public function test_attendance_status_display()
    {
        $this->actingAs($this->user);

        // 1. 勤務外
        $this->get('/attendance')->assertSee('勤務外');

        // 2. 出勤中
        $attendance = Attendance::create([
            'user_id'    => $this->user->id,
            'date'       => Carbon::today()->toDateString(), // 日付のみにフォーマット
            'start_time' => '09:00',
            'status'     => 2, // 出勤中（あなたの定義に合わせる）
        ]);
        $this->get('/attendance')->assertSee('出勤中');

        // 3. 休憩中
        Rest::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '12:00',
        ]);
        $this->get('/attendance')->assertSee('休憩中');

        // 4. 退勤済
        $attendance->update(['end_time' => '18:00']);
        $this->get('/attendance')->assertSee('退勤済');
    }

    /**
     * 【勤怠詳細情報修正機能：バリデーション】
     */
    public function test_attendance_update_validation()
    {
        $attendance = Attendance::create([
            'user_id'    => $this->user->id,
            'date'       => '2026-03-12',
            'start_time' => '09:00',
            'end_time'   => '18:00',
            'status'     => 2,
        ]);

        $this->actingAs($this->user);

        // postを削除し、patchのみにする。
        // バリデーションで他の項目（時間等）も必須なら、それらも入力した状態で reason だけ空にする。
        $response = $this->patch("/attendance/detail/{$attendance->id}", [
            'start_time' => '09:00',
            'end_time'   => '18:00',
            'reason'     => '',
        ]);

        $response->assertSessionHasErrors(['reason']);
    }

    /**
     * 【修正申請機能】
     * 修正を送ると status が pending（承認待ち）になるか
     */
    public function test_attendance_correction_request_processing()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-12',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 2,
        ]);

        $this->actingAs($this->user);

        // 修正申請を送信
        $this->patch("/attendance/detail/{$attendance->id}", [
            'start_time' => '10:00',
            'end_time' => '19:00',
            'reason' => '電車遅延のため'
        ]);

        // DBのステータスが「pending（承認待ち）」に変わっているか
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 1,
            'reason' => '電車遅延のため',
        ]);
    }
}
