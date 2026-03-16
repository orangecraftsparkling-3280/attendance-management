<?php

namespace App\Http\Requests;

class AdminAttendanceRequest extends AttendanceRequest
{

    public function authorize(): bool
    {
        // 管理者権限があるかチェック
        return auth()->user()->role === 'admin';
    }

    public function rules(): array
    {
        // 親（AttendanceRequest）のルールをそのまま取得
        $rules = parent::rules();

        return $rules;
    }
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $start = $this->start_time;
            $end = $this->end_time;

            // 1. 出退勤の逆転（要件1）
            if ($start && $end && $start >= $end) {
                $validator->errors()->add('time_error', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            // 2 & 3. 休憩時間の妥当性（要件2, 3）
            $this->checkRests($validator, $start, $end);
        });
    }

    private function checkRests($validator, $start, $end)
    {
        // 入力があるものだけを対象にする
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

