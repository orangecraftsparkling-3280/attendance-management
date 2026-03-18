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
            'start_time' => $today . ' 10:00:00',
            'end_time' => $today . ' 19:00:00'
        ]);
    }
}
