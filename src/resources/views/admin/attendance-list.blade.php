@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/list.css') }}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
@endsection

@section('content')
<div class="attendance-page">
    <div class="list-title">
        <h1>{{ $currentDate->format('Y年m月d日') }}の勤怠</h1>
    </div>

    @if(session('success'))
    <div class="alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="attendance-card">
        <div class="nav-container">
            <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}" class="nav-btn">← 前日</a>

            <h2 class="current-display">
                <span class="material-symbols-outlined">calendar_today</span>
                {{ $currentDate->format('Y年m月d日') }}
            </h2>

            <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="nav-btn">翌日 →</a>
        </div>

        <div class="table-container">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th class="text-left">名前</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendances as $attendance)
                    <tr>
                        <td data-label="名前">{{ $attendance->user->name }}</td>
                        <td data-label="出勤">{{ $attendance->display_start_time ?? '-' }}</td>
                        <td data-label="退勤">{{ $attendance->display_end_time ?? '勤務中' }}</td>
                        <td data-label="休憩">{{ $attendance->total_rest_time }}</td>
                        <td data-label="合計">{{ $attendance->total_work_time }}</td>
                        <td data-label="詳細">
                            <a href="{{ route('admin.attendance.detail', $attendance->id) }}" class="text-detail">詳細</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="no-data">打刻データがありません。</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection