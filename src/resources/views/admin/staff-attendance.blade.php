@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff-attendance.css') }}">
@endsection

@section('content')
<div class="attendance-page">
    <div class="list-title">
        <h1>{{ $user->name }}さんの勤怠</h1>
    </div>

    <div class="attendance-card">
        <div class="nav-container">
            <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $prevMonth]) }}" class="nav-btn">
                <span class="nav-btn__text">←前月</span>
            </a>

            <h2 class="current-month">
                {{ $currentYear }}年{{ $currentMonth }}月
            </h2>

            <a href="{{ route('admin.attendance.staff', ['id' => $user->id, 'month' => $nextMonth]) }}" class="nav-btn">
                <span class="nav-btn__text">翌月→</span>
            </a>
        </div>

        <div class="table-container">
            <table class="attendance-table">
                <thead>
                    <tr class="table-title">
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($calendar as $day)
                    @php
                    $dateStr = $day->format('Y-m-d');
                    $attendance = $attendances->get($dateStr);
                    @endphp
                    <tr>
                        <td>{{ $day->format('m/d') }}({{ $day->isoFormat('ddd') }})</td>
                        <td>{{ $attendance->display_start_time ?? '' }}</td>
                        <td>{{ $attendance->display_end_time ?? '' }}</td>
                        <td>{{ $attendance->total_rest_time ?? '' }}</td>
                        <td>{{ $attendance->total_work_time ?? '' }}</td>
                        <td>
                            <a href="{{ route('admin.attendance.detail', ['id' => $attendance ? $attendance->id : $dateStr]) }}?user_id={{ $user->id }}" class="text-detail">
                                詳細
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="header-actions">
        <a href="{{ route('admin.attendance.staff.csv', ['id' => $user->id, 'month' => request('month')]) }}" class="csv-btn">
            CSV出力
        </a>
    </div>
</div>
@endsection