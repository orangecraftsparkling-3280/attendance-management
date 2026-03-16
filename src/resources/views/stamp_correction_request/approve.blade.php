@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request/approve.css') }}">
@endsection

@section('content')
<div class="attendance-page">
    <div class="list-title">
        <h1>勤怠詳細</h1>
    </div>

    <table class="detail-table">
        {{-- 1. 名前 --}}
        <tr>
            <th>名前</th>
            <td>
                <div class="field-wrapper">
                    <div class="info-text name-display">{{ $attendance->user->name }}</div>
                </div>
            </td>
        </tr>

        {{-- 2. 日付 --}}
        <tr>
            <th>日付</th>
            <td>
                <div class="date-display">
                    <span class="date-year">{{ date('Y', strtotime($attendance->date)) }}年</span>
                    <span class="date-spacer">～</span> {{-- ←必ず入れてください。CSSで透明になります --}}
                    <span class="date-day">{{ date('m', strtotime($attendance->date)) }}月{{ date('d', strtotime($attendance->date)) }}日</span>
                </div>
            </td>
        </tr>

        {{-- 出勤・退勤 --}}
        <tr>
            <th>出退勤</th>
            <td>
                <div class="time-cell">
                    {{-- no-borderクラスで枠なし・左15px空けを適用 --}}
                    <div class="time-box no-border">{{ $attendance->start_time ? date('H:i', strtotime($attendance->start_time)) : '--:--' }}</div>
                    <div class="range-separator">～</div>
                    <div class="time-box no-border">{{ $attendance->end_time ? date('H:i', strtotime($attendance->end_time)) : '--:--' }}</div>
                </div>
            </td>
        </tr>

        {{-- 休憩時間 --}}
        @foreach($attendance->rests as $index => $rest)
        <tr>
            <th>休憩{{ $index + 1 }}</th>
            <td>
                <div class="time-cell">
                    <div class="time-box no-border">{{ $rest->start_time ? date('H:i', strtotime($rest->start_time)) : '--:--' }}</div>
                    <div class="range-separator">～</div>
                    <div class="time-box no-border">{{ $rest->end_time ? date('H:i', strtotime($rest->end_time)) : '--:--' }}</div>
                </div>
            </td>
        </tr>
        @endforeach

        {{-- 備考 --}}
        <tr>
            <th>備考</th>
            <td>
                <div class="field-wrapper">
                    <textarea class="detail-textarea" readonly>{{ $attendance->reason ?? 'なし' }}</textarea>
                </div>
            </td>
        </tr>
    </table>

    <div class="edit-section" id="status-container">
        @if($attendance->status == 1) {{-- 1: 承認待ち --}}
        <button type="button" class="btn-primary" id="approve-btn" onclick="approveRequest('{{ $attendance->id }}')">
            承認
        </button>
        @else
        <div class="pending-message">
            <p>承認済み</p>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
    function approveRequest(attendanceId) {

        // ボタンを連打できないように無効化
        const btn = document.getElementById('approve-btn');
        btn.disabled = true;
        btn.innerText = '処理中...';

        fetch(`/stamp_correction_request/approve/${attendanceId}`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', // Laravelのセキュリティ対策
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
            })
            .then(async response => {
                const data = await response.json();

                if (response.ok) {
                    // 成功：ボタンエリアを「承認済み」というテキストに書き換える
                    const container = document.getElementById('status-container');
                    container.innerHTML = '<div class="pending-message"><p>承認済み</p></div>';
                } else {
                    alert(data.message || '承認に失敗しました');
                    btn.disabled = false;
                    btn.innerText = '承認';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('通信エラーが発生しました');
                btn.disabled = false;
                btn.innerText = '承認';
            });
    }
</script>
@endpush