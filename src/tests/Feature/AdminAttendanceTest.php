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

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();


        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user', 'name' => 'テストスタッフ']);
    }

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

        $response->assertSee('2026年03月12日');
        $response->assertSee('テストスタッフ');
    }

    public function test_admin_can_see_staff_list()
    {
        $response = $this->actingAs($this->admin)->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee($this->user->email);
    }

    public function test_admin_can_approve_correction_request()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 12));
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-12',
            'start_time' => '10:00',
            'end_time' => '19:00',
            'status' => 1,
            'reason' => '修正願い'
        ]);

        Rest::create([
            'attendance_id' => $attendance->id,
            'start_time' => '12:00',
            'end_time' => '13:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('admin.stamp_correction_request.approve', [
                'attendance_correct_request_id' => $attendance->id
            ]), [
                'reason' => '修正を承認しました'
            ]);

        $response->assertRedirect(route('stamp_correction_request.list'));
        $today = \Carbon\Carbon::today()->toDateString();

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 2,
            'start_time' => \Carbon\Carbon::parse($attendance->date)->format('Y-m-d') . ' 10:00:00',
            'end_time'   => \Carbon\Carbon::parse($attendance->date)->format('Y-m-d') . ' 19:00:00',
        ]);
    }

    public function test_admin_can_navigate_between_days()
    {
        $response = $this->actingAs($this->admin)->get('/admin/attendance/list?date=2026-03-12');
        $response->assertStatus(200);

        $prevDay = '2026-03-11';
        $response->assertSee(route('admin.attendance.list', ['date' => $prevDay]));

        $nextDay = '2026-03-13';
        $response->assertSee(route('admin.attendance.list', ['date' => $nextDay]));
    }

    public function test_admin_sees_today_by_default()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 22));

        $response = $this->actingAs($this->admin)->get('/admin/attendance/list');

        $response->assertStatus(200);

        $response->assertSee('2026年03月22日');
    }

    public function test_admin_only_sees_attendance_for_selected_date()
    {
        Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-12',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 2
        ]);

        $otherUser = User::factory()->create(['name' => '他日のスタッフ']);
        Attendance::create([
            'user_id' => $otherUser->id,
            'date' => '2026-03-13',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 2
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/attendance/list?date=2026-03-12');

        $response->assertSee($this->user->name);
        $response->assertDontSee('他日のスタッフ');
    }

    public function test_admin_can_see_attendance_detail()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-12',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 2,
            'reason' => '既存の備考'
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance.detail', ['id' => $attendance->id]));

        $response->assertStatus(200);
        $response->assertSee('テストスタッフ');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('既存の備考');
    }

    public function test_admin_attendance_update_validation_start_after_end()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-12',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 2,
            'reason' => '修正理由'
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->patch(route('admin.attendance.update', ['id' => $attendance->id]), [
                'start_time' => '18:00',
                'end_time'   => '09:00',
                'reason'     => '修正'
            ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('admin.attendance.detail', ['id' => $attendance->id]));
        $response->assertSessionHasErrors(['start_time']);

        $this->followRedirects($response)
            ->assertSee('出勤時間もしくは退勤時間が不適切な値です');
    }

    public function test_admin_attendance_update_validation_rest_start_after_end()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-12',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 2,
            'reason' => '修正理由'
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->patch(route('admin.attendance.update', ['id' => $attendance->id]), [
                'start_time' => '09:00',
                'end_time'   => '18:00',
                'new_rests' => [
                    ['start' => '19:00', 'end' => '20:00']
                ],
                'reason' => '修正'
            ]);

        $response->assertSessionHasErrors();
        $this->followRedirects($response)
            ->assertSee('休憩時間が不適切な値です');
    }

    public function test_admin_attendance_update_validation_reason_required()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-12',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 2
        ]);

        $response = $this->actingAs($this->admin)
            ->from(route('admin.attendance.detail', ['id' => $attendance->id]))
            ->patch(route('admin.attendance.update', ['id' => $attendance->id]), [
                'start_time' => '09:00',
                'end_time'   => '18:00',
                'reason'     => ''
            ]);

        $response->assertSessionHasErrors(['reason']);
        $this->followRedirects($response)
            ->assertSee('備考を記入してください');
    }

    public function test_admin_can_see_staff_monthly_attendance_with_navigation()
    {
        $thisMonth = Carbon::now()->format('Y-m');
        $prevMonth = Carbon::now()->subMonth()->format('Y-m');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.attendance.staff', ['id' => $this->user->id]));

        $response->assertStatus(200);
        $response->assertSee(Carbon::now()->format('Y年n月'));

        $responsePrev = $this->actingAs($this->admin)
            ->get(route('admin.attendance.staff', ['id' => $this->user->id, 'month' => $prevMonth]));

        $responsePrev->assertStatus(200);
        $responsePrev->assertSee(Carbon::now()->subMonth()->format('Y年n月'));
    }
}
