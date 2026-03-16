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

            // --- 1. 出退勤の前後関係チェック ---
            if ($start && $end && $start >= $end) {
                $validator->errors()->add('time_error', '出勤時間もしくは退勤時間が不適切な値です');
                return;
            }

            // --- 2 & 3. 休憩時間の妥当性チェック ---
            $this->checkRests($validator, $start, $end);
        });
    }

    private function checkRests($validator, $start, $end)
    {
        // 入力がある休憩のみをフィルタリングしてマージ
        $allRests = array_merge(
            array_filter($this->input('rests', []), fn($r) => !empty($r['start']) && !empty($r['end'])),
            array_filter($this->input('new_rests', []), fn($r) => !empty($r['start']) && !empty($r['end']))
        );

        foreach ($allRests as $rest) {
            $restStart = $rest['start'];
            $restEnd = $rest['end'];

            // 要件2: 休憩開始が出勤前、または退勤後
            if ($restStart < $start || $restStart > $end) {
                $validator->errors()->add('rest_error', '休憩時間が不適切な値です');
                return;
            }

            // 要件3: 休憩終了が退勤より後、または休憩開始より前
            if ($restEnd > $end) {
                $validator->errors()->add('rest_combined_error', '休憩時間もしくは退勤時間が不適切な値です');
                return;
            }

            // 休憩開始 > 休憩終了 の矛盾（要件2のメッセージを流用）
            if ($restStart >= $restEnd) {
                $validator->errors()->add('rest_error', '休憩時間が不適切な値です');
                return;
            }
        }
    }
}
