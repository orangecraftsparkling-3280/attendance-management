<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\AttendanceController;

// ルートアクセス時の振り分け
Route::get('/', function () {
    return auth()->check() ? redirect()->route('attendance.index') : redirect()->route('login');
});

// 未ログイン時のみアクセス可能
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate']); // ログイン実行
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']); // 会員登録実行
});

// ログイン済み必須
Route::middleware('auth')->group(function () {
    // ログアウト
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // 勤怠関連をグループ化
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/punch-in', [AttendanceController::class, 'punchIn'])->name('punch-in');
        Route::post('/punch-out', [AttendanceController::class, 'punchOut'])->name('punch-out');
        Route::post('/rest-start', [AttendanceController::class, 'restStart'])->name('rest-start');
        Route::post('/rest-end', [AttendanceController::class, 'restEnd'])->name('rest-end');
    });
});