<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    // 「Undefined property」を防ぐための宣言
    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. 管理者と一般ユーザーを準備
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user', 'name' => 'テストスタッフ']);
    }

    /**
     * 【管理者：全ユーザーの勤怠一覧表示】
     */
    public function test_admin_can_see_all_users_attendance()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 12));

        Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-12',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 2,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/attendance/list');

        $response->assertStatus(200);
        // Attendanceモデルの getFormattedDateAttribute() により「2026年03月12日」と出るはず
        $response->assertSee('2026年03月12日');
        $response->assertSee('テストスタッフ');
    }

    /**
     * 【管理者：スタッフ一覧表示】
     */
    public function test_admin_can_see_staff_list()
    {
        $response = $this->actingAs($this->admin)->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee($this->user->email);
    }

    /**
     * 【管理者：修正申請の承認処理】
     * Attendanceモデルのstatusとreasonを使ってテストします
     */
    /**
     * 【管理者：修正申請の承認処理】
     */
    public function test_admin_can_approve_correction_request()
    {
        // 1. 承認待ちの勤怠データを作成
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-12',
            'start_time' => '10:00',
            'end_time' => '19:00',
            'status' => 1, // 承認待ち
            'reason' => '修正願い'
        ]);

        // 2. 休憩データも紐付けておく
        Rest::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]);

        // 3. 管理者が承認ボタン（PATCH）を押す
        // $admin ではなく $this->admin に修正
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.stamp_correction_request.approve', [
                'attendance_correct_request_id' => $attendance->id
            ]), [
                'reason' => '修正を承認しました'
            ]);

        // 4. アサーション：リダイレクトを確認
        // web.php の定義に合わせて stamp_correction_request.list へリダイレクトすることを期待
        $response->assertRedirect(route('stamp_correction_request.list'));

        // 5. DBのステータスが「2（承認済み）」に更新されているか確認
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 2,
            'start_time' => '10:00:00',
            'end_time' => '19:00:00'
        ]);
    }
}
