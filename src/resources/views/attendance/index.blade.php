@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/index.css') }}">
@endsection

@section('content')
<div class="attendance__content">
    <div class="attendance__panel">
        <div class="attendance__status">
            <p id="current-status">{{ $status ?? '勤務外' }}</p>
        </div>

        {{-- 日付表示 --}}
        <div class="attendance__date">
            <h1 id="current-date">0000年00月00日</h1>
        </div>

        <div class="attendance__time">
            <h2 id="active-time">00:00</h2>
        </div>

        <div class="attendance__button-group">
            @if($status === '勤務外')
            <form action="{{ route('attendance.punch-in') }}" method="POST">
                @csrf
                <button type="submit" class="attendance__button-submit">出勤</button>
            </form>

            @elseif($status === '出勤中')
            <div class="attendance__button-row">
                <form action="{{ route('attendance.punch-out') }}" method="POST">
                    @csrf
                    <button type="submit" class="attendance__button-primary">退勤</button>
                </form>
                <form action="{{ route('attendance.rest-start') }}" method="POST">
                    @csrf
                    <button type="submit" class="attendance__button-secondary">休憩入</button>
                </form>
            </div>

            @elseif($status === '休憩中')
            {{-- 中央に半分：form-half により 50%幅で中央寄せ --}}
            <div class="attendance__button-row">
                <form action="{{ route('attendance.rest-end') }}" method="POST" class="form-half">
                    @csrf
                    <button type="submit" class="attendance__button-secondary">休憩戻</button>
                </form>
            </div>

            @elseif($status === '退勤済')
            <p>お疲れ様でした。</p>
            @endif
        </div>
    </div>
</div>

<script>
    function updateTime() {
        const now = new Date();

        // 日付の表示更新
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const date = String(now.getDate()).padStart(2, '0');
        const days = ['日', '月', '火', '水', '木', '金', '土'];
        document.getElementById('current-date').textContent = `${year}年${month}月${date}日(${days[now.getDay()]})`;

        // 時刻の表示更新
        const hour = String(now.getHours()).padStart(2, '0');
        const min = String(now.getMinutes()).padStart(2, '0');
        document.getElementById('active-time').innerHTML = `${hour}<span class="blink">:</span>${min}`;
    }
    setInterval(updateTime, 1000);
    updateTime();
</script>
@endsection