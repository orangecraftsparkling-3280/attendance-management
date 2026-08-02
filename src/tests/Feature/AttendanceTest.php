<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;


    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
    }

    public function test_attendance_status_display()
    {
        $this->actingAs($this->user);

        $this->get('/attendance')->assertSee('勤務外');

        $today = Carbon::today()->format('Y-m-d');
        $attendanceId = \Illuminate\Support\Facades\DB::table('attendances')->insertGetId([
            'user_id'    => $this->user->id,
            'date'       => $today,
            'start_time' => '09:00:00',
            'status'     => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/attendance')->assertSee('出勤中');

        \Illuminate\Support\Facades\DB::table('rests')->insert([
            'attendance_id' => $attendanceId,
            'start_time'    => '12:00:00',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        $this->get('/attendance')->assertSee('休憩中');

        \Illuminate\Support\Facades\DB::table('rests')
            ->where('attendance_id', $attendanceId)
            ->update(['end_time' => '13:00']);

        \Illuminate\Support\Facades\DB::table('attendances')
            ->where('id', $attendanceId)
            ->update(['end_time' => '18:00:00']);

        $this->get('/attendance')->assertSee('退勤済');
    }

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



        $response = $this->patch("/attendance/detail/{$attendance->id}", [
            'start_time' => '09:00',
            'end_time'   => '18:00',
            'reason'     => '',
        ]);

        $response->assertSessionHasErrors(['reason']);
    }

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

        $this->patch("/attendance/detail/{$attendance->id}", [
            'start_time' => '10:00',
            'end_time' => '19:00',
            'reason' => '電車遅延のため'
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 1,
            'reason' => '電車遅延のため',
        ]);

        $response = $this->patch("/attendance/detail/{$attendance->id}", [
            'start_time' => '18:00',
            'end_time'   => '09:00',
            'reason'     => 'テスト',
        ]);
        $response->assertSessionHasErrors(['start_time']);
    }

    public function test_current_date_display()
    {
        $this->actingAs($this->user);

        $response = $this->get('/attendance');

        $response->assertSee('function updateTime()', false);
        $response->assertSee('now.getFullYear()', false);
        $response->assertSee('now.getMonth() + 1', false);
        $response->assertSee('document.getElementById(\'current-date\')', false);

        $response->assertSee('id="current-date"', false);
        $response->assertSee('id="active-time"', false);
    }

    public function test_user_can_see_own_attendance_list()
    {
        Carbon::setTestNow('2026-03-25'); // 「今日」を3月に固定

        $otherUser = User::factory()->create();
        Attendance::create([
            'user_id' => $otherUser->id,
            'date' => '2026-03-20',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => 2,
        ]);

        Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-21',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 2,
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/attendance/list');

        $response->assertStatus(200);
        $response->assertSee('03/21');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('03/20');

        Carbon::setTestNow(); // 固定解除、他テストに影響させない
    }

    public function test_attendance_detail_page_displays_correct_info()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-01',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'status' => 2,
        ]);

        $this->actingAs($this->user);
        $response = $this->get("/attendance/detail/{$attendance->id}");

        $response->assertStatus(200);
        $response->assertSee($this->user->name);
        $response->assertSee('2026');
        $response->assertSee('3月1日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    public function test_rest_time_validation()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-01',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 2,
        ]);

        $this->actingAs($this->user);

        $response = $this->patch("/attendance/detail/{$attendance->id}", [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'new_rests' => [
                [
                    'start' => '19:00',
                    'end' => '20:00',
                ]
            ],
            'reason' => 'テスト',
        ]);
        $response->assertSessionHasErrors(['new_rests.0.start']);

        $this->get("/attendance/detail/{$attendance->id}")
            ->assertSee('休憩時間が不適切な値です');
    }

    public function test_attendance_rest_end_validation()
    {
        $attendance = Attendance::create([
            'user_id' => $this->user->id,
            'date' => '2026-03-01',
            'new_rests' => [
                [
                    'start' => '9:00',
                    'end' => '18:00',
                ]
            ],
            'status' => 2,
        ]);

        $this->actingAs($this->user);

        $response = $this->patch("/attendance/detail/{$attendance->id}", [
            'start_time' => '09:00',
            'end_time' => '18:00',
            'new_rests' => [
                ['start' => '12:00', 'end' => '19:00']
            ],
            'reason' => 'テスト',
        ]);

        $response->assertSessionHasErrors(['new_rests.0.end']);

        $this->get("/attendance/detail/{$attendance->id}")
            ->assertSee('休憩時間もしくは退勤時間が不適切な値です');
    }

    public function test_full_attendance_flow_punch_in_rest_out()
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 10, 9, 0));
        $this->actingAs($this->user);

        $this->post('/attendance/punch-in');
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'date' => '2026-03-10 00:00:00',
            'start_time' => '2026-03-10 09:00:00',
        ]);

        Carbon::setTestNow(Carbon::create(2026, 3, 10, 12, 0));
        $this->post('/attendance/rest-start');
        $this->assertDatabaseHas('rests', ['start_time' => '12:00']);

        Carbon::setTestNow(Carbon::create(2026, 3, 10, 13, 0));
        $this->post('/attendance/rest-end');
        $this->assertDatabaseHas('rests', ['end_time' => '13:00']);

        Carbon::setTestNow(Carbon::create(2026, 3, 10, 18, 0));
        $this->post('/attendance/punch-out');
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->user->id,
            'date' => '2026-03-10 00:00:00',
            'end_time' => '2026-03-10 18:00:00',
        ]);

        Carbon::setTestNow();
    }

    public function test_punch_out_without_punch_in_does_not_error()
    {
        $this->actingAs($this->user);

        $response = $this->post('/attendance/punch-out');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_rest_start_without_punch_in_does_not_error()
    {
        $this->actingAs($this->user);

        $response = $this->post('/attendance/rest-start');

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_rest_end_without_rest_start_does_not_error()
    {
        Carbon::setTestNow('2026-03-10');
        $this->actingAs($this->user);

        $this->post('/attendance/punch-in');
        $response = $this->post('/attendance/rest-end');

        $response->assertRedirect();
        $response->assertSessionHas('error');

        Carbon::setTestNow();
    }

    public function test_duplicate_punch_in_is_rejected()
    {
        Carbon::setTestNow('2026-03-10');
        $this->actingAs($this->user);

        $this->post('/attendance/punch-in');
        $response = $this->post('/attendance/punch-in');

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('attendances', 1);

        Carbon::setTestNow();
    }

    public function test_user_cannot_view_other_users_attendance_detail()
    {
        $otherUser = User::factory()->create();
        $otherAttendance = Attendance::create([
            'user_id' => $otherUser->id,
            'date' => '2026-03-05',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 2,
        ]);

        $this->actingAs($this->user);
        $response = $this->get("/attendance/detail/{$otherAttendance->id}");

        $response->assertStatus(200);
        $response->assertDontSee($otherUser->name);
    }

    public function test_user_cannot_update_other_users_attendance()
    {
        $otherUser = User::factory()->create();
        $otherAttendance = Attendance::create([
            'user_id' => $otherUser->id,
            'date' => '2026-03-05',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 2,
        ]);

        $originalStartTime = $otherAttendance->getAttributes()['start_time'];

        $this->actingAs($this->user);
        $response = $this->patch("/attendance/detail/{$otherAttendance->id}", [
            'start_time' => '10:00',
            'end_time' => '19:00',
            'reason' => '不正アクセステスト',
        ]);

        $response->assertStatus(404);
        $this->assertSame($originalStartTime, $otherAttendance->fresh()->getAttributes()['start_time']);
    }
}