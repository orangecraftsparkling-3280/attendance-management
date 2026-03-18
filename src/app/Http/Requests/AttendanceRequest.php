<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_time' => ['required'],
            'end_time'   => ['required'],
            'reason'     => ['required'],
            'rests.*.start' => ['nullable', 'date_format:H:i'],
            'rests.*.end'   => ['nullable', 'date_format:H:i'],
            'new_rests.*.start' => ['nullable', 'date_format:H:i'],
            'new_rests.*.end'   => ['nullable', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required'     => '備考を記入してください',
            'start_time.required' => '出勤時間を入力してください',
            'end_time.required'   => '退勤時間を入力してください',
            '*.date_format'       => '時刻の形式が正しくありません（例 09:00）',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $start = $this->start_time;
            $end = $this->end_time;

            if ($start && $end && $start >= $end) {
                $validator->errors()->add('time_error', '出勤時間が不適切な値です');
                return;
            }

            $this->checkRests($validator, $start, $end);
        });
    }

    private function checkRests($validator, $start, $end)
    {
        $allRests = array_merge(
            array_filter($this->input('rests', []), fn($r) => !empty($r['start']) && !empty($r['end'])),
            array_filter($this->input('new_rests', []), fn($r) => !empty($r['start']) && !empty($r['end']))
        );

        foreach ($allRests as $rest) {
            $restStart = $rest['start'];
            $restEnd = $rest['end'];

            if ($restStart < $start || $restStart > $end) {
                $validator->errors()->add('rest_error', '休憩時間が不適切な値です');
                return;
            }

            if ($restEnd > $end) {
                $validator->errors()->add('rest_combined_error', '休憩時間もしくは退勤時間が不適切な値です');
                return;
            }

            if ($restStart >= $restEnd) {
                $validator->errors()->add('rest_error', '休憩時間が不適切な値です');
                return;
            }
        }
    }
}
