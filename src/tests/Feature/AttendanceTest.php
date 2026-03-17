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


        $attendance = Attendance::create([
            'user_id'    => $this->user->id,
            'date'       => Carbon::today()->toDateString(),
            'start_time' => '09:00',
            'status'     => 2,
        ]);
        $this->get('/attendance')->assertSee('出勤中');


        Rest::create([
            'attendance_id' => $attendance->id,
            'start_time'    => '12:00',
        ]);
        $this->get('/attendance')->assertSee('休憩中');


        $attendance->update(['end_time' => '18:00']);
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
    }
}
