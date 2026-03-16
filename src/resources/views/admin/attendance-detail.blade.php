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
            {{-- 名前・日付・出退勤は今のままでOK --}}
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
                    <input type="time" name="start_time" class="time-box" value="{{ old('start_time', $attendance->start_time ? date('H:i', strtotime($attendance->start_time)) : '') }}">
                    <span class="range-separator">～</span>
                    <input type="time" name="end_time" class="time-box" value="{{ old('end_time', $attendance->end_time ? date('H:i', strtotime($attendance->end_time)) : '') }}">
                </td>
            </tr>

            {{-- 休憩ループ --}}
            @foreach($attendance->rests as $index => $rest)
            <tr>
                <th>休憩{{ $index + 1 }}</th>
                <td class="time-cell">
                    <input type="time" name="rests[{{ $rest->id }}][start]" class="time-box"
                        value="{{ old('rests.'.$rest->id.'.start', ($rest->getRawOriginal('start_time') && $rest->getRawOriginal('start_time') !== '00:00:00') ? date('H:i', strtotime($rest->start_time)) : '') }}">
                    <span class="range-separator">～</span>
                    <input type="time" name="rests[{{ $rest->id }}][end]" class="time-box"
                        value="{{ old('rests.'.$rest->id.'.end', ($rest->getRawOriginal('end_time') && $rest->getRawOriginal('end_time') !== '00:00:00') ? date('H:i', strtotime($rest->end_time)) : '') }}">
                </td>
            </tr>
            @endforeach

            {{-- 新規休憩枠 (+1) --}}
            <tr>
                <th>休憩{{ count($attendance->rests) + 1 }}</th>
                <td class="time-cell">
                    <input type="time" name="new_rests[0][start]" class="time-box" value="{{ old('new_rests.0.start', '') }}" required>
                    <span class="range-separator">～</span>
                    <input type="time" name="new_rests[0][end]" class="time-box" value="{{ old('new_rests.0.end', '') }}" required>
                </td>
            </tr>

            <tr>
                <th>備考</th>
                <td>
                    <div class="field-wrapper">
                        <textarea name="reason" rows="4" class="detail-textarea">{{ old('reason', $attendance->reason) }}</textarea>
                    </div>
                </td>
            </tr>
        </table>

        {{-- ボタンエリア：エラーを左、ボタンを右に --}}
        <div class="edit-section">
            <div class="form__error">
                @error('time_error') <p class="error-message">{{ $message }}</p> @enderror
                @error('rest_error') <p class="error-message">{{ $message }}</p> @enderror
                @error('rest_combined_error') <p class="error-message">{{ $message }}</p> @enderror
                @error('reason') <p class="error-message">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn-primary">修正</button>
        </div>
    </form>
</div>
@endsection