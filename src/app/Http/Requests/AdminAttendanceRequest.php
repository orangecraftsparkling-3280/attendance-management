<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_time' => 'required',
            'end_time'   => 'required',
            'reason'     => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $start = $this->start_time;
            $end = $this->end_time;

            if ($start && $end && $start >= $end) {
                $validator->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
            }

            $this->checkRests($validator, $start, $end);
        });
    }

    private function checkRests($validator, $start, $end)
    {
        $rests = $this->input('rests', []);
        $newRests = $this->input('new_rests', []);
        $allRests = array_merge($rests, $newRests);

        foreach ($allRests as $rest) {
            $rStart = $rest['start'] ?? $rest['start_time'] ?? null;
            $rEnd = $rest['end'] ?? $rest['end_time'] ?? null;

            if (!$rStart || !$rEnd) continue;

            if ($rStart >= $rEnd || $rStart < $start || $rStart > $end) {
                $validator->errors()->add('start_time', '休憩時間が不適切な値です');
                return;
            }

            if ($rEnd > $end) {
                $validator->errors()->add('start_time', '休憩時間もしくは退勤時間が不適切な値です');
                return;
            }
        }
    }
}
