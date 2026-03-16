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
            {{-- 名前 --}}
            <tr>
                <th>名前</th>
                <td>
                    <div class="field-wrapper">
                        <div class="info-text name-display">{{ $attendance->user->name }}</div>
                    </div>
                </td>
            </tr>

            {{-- 日付 --}}
            <tr>
                <th>日付</th>
                <td>
                    <div class="info-text date-display">
                        {{-- モデルのアクセサ getFormattedDateAttribute を使用 --}}
                        <span class="date-year">{{ $attendance->date->format('Y') }}年</span>
                        <span class="date-spacer"></span>
                        <span class="date-day">{{ $attendance->date->format('n') }}月{{ $attendance->date->format('j') }}日</span>
                    </div>
                </td>
            </tr>

            {{-- 出勤・退勤 --}}
            <tr>
                <th>出勤・退勤</th>
                <td class="time-cell">
                    @if($attendance->status == 1)
                    {{-- アクセサ display_start_time / display_end_time を使用 --}}
                    <span class="time-box no-border">{{ $attendance->display_start_time }}</span>
                    <span class="range-separator">～</span>
                    <span class="time-box no-border">{{ $attendance->display_end_time ?? '勤務中' }}</span>
                    @else
                    <input type="time" name="start_time" class="time-box" value="{{ old('start_time', $attendance->display_start_time) }}">
                    <span class="range-separator">～</span>
                    <input type="time" name="end_time" class="time-box" value="{{ old('end_time', $attendance->display_end_time) }}">
                    @endif
                </td>
            </tr>

            {{-- 既存の休憩 --}}
            @foreach($attendance->rests as $index => $rest)
            <tr>
                <th>休憩{{ $index + 1 }}</th>
                <td class="time-cell">
                    @php
                    // 休憩はモデルが別（Rest.php）なので、秒を消すためにフォーマット
                    $restStart = $rest->start_time ? \Carbon\Carbon::parse($rest->start_time)->format('H:i') : '';
                    $restEnd = $rest->end_time ? \Carbon\Carbon::parse($rest->end_time)->format('H:i') : '';
                    @endphp

                    @if($attendance->status == 1)
                    <span class="time-box no-border">{{ $restStart }}</span>
                    <span class="range-separator">～</span>
                    <span class="time-box no-border">{{ $restEnd ?: '休憩中' }}</span>
                    @else
                    <input type="time" name="rests[{{ $rest->id }}][start]" class="time-box" value="{{ old('rests.'.$rest->id.'.start', $restStart) }}">
                    <span class="range-separator">～</span>
                    <input type="time" name="rests[{{ $rest->id }}][end]" class="time-box" value="{{ old('rests.'.$rest->id.'.end', $restEnd) }}">
                    @endif
                </td>
            </tr>
            @endforeach

            {{-- 以下、新規休憩・備考・エラー表示・ボタン部分は変更なし --}}
            @if($attendance->status != 1)
            <tr>
                <th>休憩{{ count($attendance->rests) + 1 }}</th>
                <td class="time-cell">
                    <input type="time" name="new_rests[0][start]" class="time-box" value="{{ old('new_rests.0.start', '') }}" required>
                    <span class="range-separator">～</span>
                    <input type="time" name="new_rests[0][end]" class="time-box" value="{{ old('new_rests.0.end', '') }}" required>
                </td>
            </tr>
            @endif

            <tr>
                <th>備考</th>
                <td>
                    <div class="field-wrapper">
                        <textarea name="reason" rows="4" class="detail-textarea" {{ $attendance->status == 1 ? 'readonly' : '' }}>{{ old('reason', $attendance->reason) }}</textarea>
                    </div>
                </td>
            </tr>
        </table>

        <div class="edit-section">
            <div class="form__error">
                @error('time_error') <p class="error-message">{{ $message }}</p> @enderror
                @error('rest_error') <p class="error-message">{{ $message }}</p> @enderror
                @error('rest_combined_error') <p class="error-message">{{ $message }}</p> @enderror
                @error('reason') <p class="error-message">{{ $message }}</p> @enderror
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