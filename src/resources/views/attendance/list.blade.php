@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance/list.css') }}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
@endsection

@section('content')
<div class="attendance-page">
    <div class="list-title">
        <h1>勤怠一覧</h1>
    </div>

    <div class="attendance-card">
        <div class="nav-container">
            {{-- 前月ボタン --}}
            <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="nav-btn">
                <span class="nav-btn__text">←前月</span>
            </a>

            {{-- 現在の年月 --}}
            <h2 class="current-month">
                <span class="material-symbols-outlined calendar-icon">calendar_month</span>
                {{ $currentYear }}年{{ $currentMonth }}月
            </h2>

            {{-- 翌月ボタン --}}
            <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="nav-btn">
                <span class="nav-btn__text">翌月→</span>
            </a>
        </div>

        <div class="table-container">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($calendar as $day)
                    @php
                    $atd = $attendances->get($day->format('Y-m-d'));
                    @endphp
                    <tr class="{{ $day->isWeekend() ? 'is-weekend' : '' }}">
                        {{-- 日付 --}}
                        <td class="{{ $day->isSaturday() ? 'text-saturday' : ($day->isSunday() ? 'text-sunday' : '') }}">
                            {{ $day->format('m/d') }}({{ $day->isoFormat('ddd') }})
                        </td>

                        {{-- 出勤・退勤 (アクセサを使用してスッキリ表記) --}}
                        <td>{{ $atd->display_start_time ?? '' }}</td>
                        <td>{{ $atd->display_end_time ?? '' }}</td>

                        {{-- 休憩・合計 (モデル内のロジックで完結) --}}
                        <td>{{ $atd->total_rest_time ?? '' }}</td>
                        <td>{{ $atd->total_work_time ?? '' }}</td>

                        {{-- 詳細 --}}
                        <td>
                            <a href="{{ route('attendance.detail', ['id' => $atd->id ?? $day->format('Y-m-d')]) }}" class="text-detail">
                                詳細
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection