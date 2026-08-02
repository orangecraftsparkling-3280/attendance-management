@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/detail.css') }}">
@endsection

@section('content')
<div class="attendance-page">
    <div class="list-title">
        <h1>勤怠詳細</h1>
    </div>

    <form action="{{ route('admin.attendance.update', ['id' => $attendance->id ?: $attendance->date]) }}?user_id={{ $attendance->user_id }}" method="POST" novalidate>
        @csrf
        @method('PATCH')

        <table class="detail-table">
            <tr>
                <th>名前</th>
                <td>
                    <div class="field-wrapper">
                        <div class="info-text name-display">{{ $attendance->user->name }}</div>
                    </div>
                </td>
            </tr>
            <tr>
                <th>日付</th>
                <td>
                    <div class="info-text date-display">
                        <span class="date-year">{{ substr($attendance->date, 0, 4) }}年</span>
                        <span class="date-spacer"></span>
                        <span class="date-day">{{ substr($attendance->date, 5, 2) }}月{{ substr($attendance->date, 8, 2) }}日</span>
                    </div>
                </td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td class="time-cell">
                    <input type="time" name="start_time" class="time-box" aria-label="出勤時刻" value="{{ old('start_time', $attendance->start_time ? date('H:i', strtotime($attendance->start_time)) : '') }}">
                    <span class="range-separator">～</span>
                    <input type="time" name="end_time" class="time-box" aria-label="退勤時刻" value="{{ old('end_time', $attendance->end_time ? date('H:i', strtotime($attendance->end_time)) : '') }}">
                </td>
            </tr>

            @foreach($attendance->rests as $index => $rest)
            <tr>
                <th>休憩{{ $index + 1 }}</th>
                <td class="time-cell">
                    <input type="time" name="rests[{{ $rest->id }}][start]" class="time-box" aria-label="休憩{{ $index + 1 }}開始時刻"
                        value="{{ old('rests.'.$rest->id.'.start', ($rest->getRawOriginal('start_time') && $rest->getRawOriginal('start_time') !== '00:00:00') ? date('H:i', strtotime($rest->start_time)) : '') }}">
                    <span class="range-separator">～</span>
                    <input type="time" name="rests[{{ $rest->id }}][end]" class="time-box" aria-label="休憩{{ $index + 1 }}終了時刻"
                        value="{{ old('rests.'.$rest->id.'.end', ($rest->getRawOriginal('end_time') && $rest->getRawOriginal('end_time') !== '00:00:00') ? date('H:i', strtotime($rest->end_time)) : '') }}">
                </td>
            </tr>
            @endforeach

            <tr>
                <th>休憩{{ count($attendance->rests) + 1 }}</th>
                <td class="time-cell">
                    <input type="time" name="new_rests[0][start]" class="time-box" aria-label="新しい休憩の開始時刻" value="{{ old('new_rests.0.start', '') }}" required>
                    <span class="range-separator">～</span>
                    <input type="time" name="new_rests[0][end]" class="time-box" aria-label="新しい休憩の終了時刻" value="{{ old('new_rests.0.end', '') }}" required>
                </td>
            </tr>

            <tr>
                <th>備考</th>
                <td>
                    <div class="field-wrapper">
                        <textarea name="reason" rows="4" class="detail-textarea" aria-label="備考">{{ old('reason', $attendance->reason) }}</textarea>
                    </div>
                </td>
            </tr>
        </table>

        <div class="edit-section">
            <div class="form__error">
                @if ($errors->any())
                @foreach ($errors->all() as $error)
                <p class="error-message">{{ $error }}</p>
                @endforeach
                @endif
            </div>

            <button type="submit" class="btn-primary">修正</button>
        </div>
</form>
</div>
@endsection