<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rest>
 */
class RestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // 12:00 〜 12:30 の間でランダムに開始
        $start = fake()->dateTimeBetween('12:00', '12:30');

        // 開始時間の 45分後 〜 90分後 の間でランダムに終了（休憩の長さがバラバラになる）
        $end = (clone $start)->modify('+' . fake()->numberBetween(45, 90) . ' minutes');

        return [
            'start_time' => $start->format('H:i'),
            'end_time'   => $end->format('H:i'),
        ];
    }
}