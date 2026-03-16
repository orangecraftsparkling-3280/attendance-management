@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request/list.css') }}">
@endsection

@section('content')
<div class="request-page">
    <div class="list-title">
        <h1>申請一覧</h1>
    </div>

    {{-- 完了メッセージの表示 --}}
    @if(session('success'))
    <div class="alert-success">
        {{ session('success') }}
    </div>
    @endif

    <div class="request-tabs">
        <a href="{{ route('stamp_correction_request.list', ['tab' => 'waiting']) }}"
            class="tab-item {{ request('tab', 'waiting') == 'waiting' ? 'active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('stamp_correction_request.list', ['tab' => 'approved']) }}"
            class="tab-item {{ request('tab') == 'approved' ? 'active' : '' }}">
            承認済み
        </a>
    </div>

    <div class="table-container">
        <table class="request-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $attendance)
                <tr>
                    <td>
                        {{-- ステータスバッジの表示をスッキリ整理 --}}
                        @if($attendance->status == 1)
                        <span class="status-badge waiting">承認待ち</span>
                        @elseif($attendance->status == 2)
                        <span class="status-badge approved">承認済み</span>
                        @endif
                    </td>
                    <td>{{ $attendance->user->name }}</td>
                    {{-- モデルのキャスト/アクセサにより日付のみが表示されます --}}
                    <td>{{ $attendance->display_date }}</td>
                    <td class="reason-cell">{{ $attendance->reason }}</td>
                    <td>{{ $attendance->updated_at->format('Y/m/d') }}</td>
                    <td>
                        {{-- 管理者と一般ユーザーでリンク先を分岐 --}}
                        @php
                        $route = auth()->user()->role === 'admin'
                        ? route('admin.stamp_correction_request.approve', $attendance->id)
                        : route('attendance.detail', $attendance->id);
                        @endphp
                        <a href="{{ $route }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty-message">申請中のデータはありません。</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection