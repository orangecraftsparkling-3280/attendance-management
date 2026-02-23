<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rest extends Model
{
    use HasFactory;

    // 保存を許可するカラムを指定
    protected $fillable = [
        'attendance_id',
        'start_time',
        'end_time',
    ];

    // 出勤データとの紐付け
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
