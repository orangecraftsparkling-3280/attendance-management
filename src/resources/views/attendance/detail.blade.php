@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/detail.css') }}">
@endsection

@section('content')
<div class="attendance-page">
    <div class="list-title">
        <h1>勤怠詳細</h1>
    </div>

    <form action="{{ route('attendance.update', ['id' => $attendance->id ?? $attendance->getRawOriginal('date')]) }}" method="POST" novalidate>
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
                        <span class="date-year">{{ $attendance->date->format('Y') }}年</span>
                        <span class="date-spacer"></span>
                        <span class="date-day">{{ $attendance->date->format('n') }}月{{ $attendance->date->format('j') }}日</span>
                    </div>
                </td>
            </tr>

            <tr>
                <th>出勤・退勤</th>
                <td class="time-cell">
                    @if($attendance->status == 1)
                    <span class="time-box no-border">{{ $attendance->display_start_time }}</span>
                    <span class="range-separator">～</span>
                    <span class="time-box no-border">{{ $attendance->display_end_time ?? '勤務中' }}</span>
                    @else
                    <input type="time" name="start_time" class="time-box" aria-label="出勤時刻" value="{{ old('start_time', $attendance->display_start_time) }}">
                    <span class="range-separator">～</span>
                    <input type="time" name="end_time" class="time-box" aria-label="退勤時刻" value="{{ old('end_time', $attendance->display_end_time) }}">
                    @endif
                </td>
            </tr>

            @foreach($attendance->rests as $index => $rest)
            <tr>
                <th>休憩{{ $index + 1 }}</th>
                <td class="time-cell">
                    @php
                    $restStart = $rest->start_time ? \Carbon\Carbon::parse($rest->start_time)->format('H:i') : '';
                    $restEnd = $rest->end_time ? \Carbon\Carbon::parse($rest->end_time)->format('H:i') : '';
                    @endphp

                    @if($attendance->status == 1)
                    <span class="time-box no-border">{{ $restStart }}</span>
                    <span class="range-separator">～</span>
                    <span class="time-box no-border">{{ $restEnd ?: '休憩中' }}</span>
                    @else
                    <input type="time" name="rests[{{ $rest->id }}][start]" class="time-box" aria-label="休憩{{ $index + 1 }}開始時刻" value="{{ old('rests.'.$rest->id.'.start', $restStart) }}">
                    <span class="range-separator">～</span>
                    <input type="time" name="rests[{{ $rest->id }}][end]" class="time-box" aria-label="休憩{{ $index + 1 }}終了時刻" value="{{ old('rests.'.$rest->id.'.end', $restEnd) }}">
                    @endif
                </td>
            </tr>
            @endforeach

            @if($attendance->status != 1)
            <tr>
                <th>休憩{{ count($attendance->rests) + 1 }}</th>
                <td class="time-cell">
                    <input type="time" name="new_rests[0][start]" class="time-box" aria-label="新しい休憩の開始時刻" value="{{ old('new_rests.0.start', '') }}" required>
                    <span class="range-separator">～</span>
                    <input type="time" name="new_rests[0][end]" class="time-box" aria-label="新しい休憩の終了時刻" value="{{ old('new_rests.0.end', '') }}" required>
                </td>
            </tr>
            @endif

            <tr>
                <th>備考</th>
                <td>
                    <div class="field-wrapper">
                        <textarea name="reason" rows="4" class="detail-textarea" aria-label="備考" {{ $attendance->status == 1 ? 'readonly' : '' }}>{{ old('reason', $attendance->reason) }}</textarea>
                    </div>
                </td>
            </tr>
        </table>

        <div class="edit-section">
            <div class="form_error">
                @if ($errors->any())
                @foreach ($errors->all() as $error)
                <p class="error-message">{{ $error }}</p>
                @endforeach
                @endif
            </div>

            @if($attendance->status == 1)
            <div class="pending-message">
                <p>*承認待ちのため修正はできません。</p>
            </div>
            @else
            <button type="submit" class="btn-primary">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection