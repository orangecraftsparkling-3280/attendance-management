<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'reason',
        'status',
    ];

    // キャストを datetime だけにしておくと、
    // $this->start_time が自動的に Carbon インスタンスになります。
    protected $casts = [
        'date'       => 'date',
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rests()
    {
        return $this->hasMany(Rest::class);
    }

    /**
     * 合計休憩時間 (分単位)
     * ロジックをシンプルに保持
     */
    public function getTotalRestMinutes(): int
    {
        return $this->rests->reduce(function ($carry, $rest) {
            if ($rest->start_time && $rest->end_time) {
                return $carry + Carbon::parse($rest->start_time)->diffInMinutes(Carbon::parse($rest->end_time));
            }
            return $carry;
        }, 0);
    }

    /* -------------------------------------------------------------------------- */
    /* アクセサ (表示用の変換)
    /* -------------------------------------------------------------------------- */

    // 00:00 形式の休憩時間
    public function getTotalRestTimeAttribute(): string
    {
        $totalMinutes = $this->getTotalRestMinutes();
        return sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
    }

    // 00:00 形式の実働時間
    public function getTotalWorkTimeAttribute(): string
    {
        if (!$this->start_time || !$this->end_time) {
            return '--:--';
        }

        $diffMinutes = $this->start_time->diffInMinutes($this->end_time);
        $workMinutes = max(0, $diffMinutes - $this->getTotalRestMinutes());

        return sprintf('%02d:%02d', floor($workMinutes / 60), $workMinutes % 60);
    }

    // 日本語表示用の日付
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('Y年m月d日');
    }

    // スラッシュ区切りの日付
    public function getDisplayDateAttribute(): string
    {
        return $this->date->format('Y/m/d');
    }

    // H:i 形式の開始時間
    public function getDisplayStartTimeAttribute(): ?string
    {
        return $this->start_time ? $this->start_time->format('H:i') : null;
    }

    // H:i 形式の終了時間
    public function getDisplayEndTimeAttribute(): ?string
    {
        return $this->end_time ? $this->end_time->format('H:i') : null;
    }
}
