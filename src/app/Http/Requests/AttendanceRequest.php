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
            'start_time' => ['required', 'date_format:H:i'],
            'end_time'   => ['required', 'date_format:H:i'],
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
                $validator->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            $this->checkRests($validator, $start, $end);
        });
    }

    private function checkRests($validator, $start, $end)
    {
        foreach ($this->input('rests', []) as $key => $rest) {
            if (empty($rest['start']) || empty($rest['end'])) continue;
            $this->validateRestPair($validator, $rest, $start, $end, "rests.{$key}");
        }

        foreach ($this->input('new_rests', []) as $key => $rest) {
            if (empty($rest['start']) || empty($rest['end'])) continue;
            $this->validateRestPair($validator, $rest, $start, $end, "new_rests.{$key}");
        }
    }

    private function validateRestPair($validator, $rest, $start, $end, $baseKey)
    {
        $restStart = $rest['start'];
        $restEnd = $rest['end'];

        if ($restStart < $start || $restStart > $end) {
            $validator->errors()->add("{$baseKey}.start", '休憩時間が不適切な値です');
        }

        if ($restEnd > $end) {
            $validator->errors()->add("{$baseKey}.end", '休憩時間もしくは退勤時間が不適切な値です');
        }

        if ($restStart >= $restEnd) {
            $validator->errors()->add("{$baseKey}.start", '休憩時間が不適切な値です');
        }
    }
}
