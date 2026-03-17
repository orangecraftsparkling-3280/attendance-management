<?php

namespace App\Http\Requests;

class AdminAttendanceRequest extends AttendanceRequest
{

    public function authorize(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        $rules = parent::rules();

        return $rules;
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $start = $this->start_time;
            $end = $this->end_time;

            if ($start && $end && $start >= $end) {
                $validator->errors()->add('time_error', '出勤時間もしくは退勤時間が不適切な値です');
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
            if ($rest['start'] < $start || $rest['start'] > $end) {
                $validator->errors()->add('rest_error', '休憩時間が不適切な値です');
                return;
            }
            if ($rest['end'] > $end) {
                $validator->errors()->add('rest_combined_error', '休憩時間もしくは退勤時間が不適切な値です');
                return;
            }
            if ($rest['start'] >= $rest['end']) {
                $validator->errors()->add('rest_error', '休憩時間が不適切な値です');
                return;
            }
        }
    }
}
