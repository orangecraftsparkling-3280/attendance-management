<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 出勤時間を 08:00〜 10:00 の間でランダム生成
        $startTime = fake()->dateTimeBetween('08:00', '10:00')->format('H:i');

        // 退勤時間を 17:00 〜 21:00 の間でランダム生成
        $endTime = fake()->dateTimeBetween('17:00', '21:00')->format('H:i');

        return [
            'date' => fake()->unique()->dateTimeBetween('-1 month', '-1 day')->format('Y-m-d'),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => fake()->randomElement([1, 2]), // 承認待ち(1) or 承認済み(2)
            'reason' => fake()->randomElement(['通常勤務', '通院のため遅刻', '残業', 'リモートワーク']),
        ];
    }
}
