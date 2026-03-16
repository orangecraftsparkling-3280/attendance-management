<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Auth\EmailVerificationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- 1. 未ログインでもアクセス可能なルート ---

// トップページ（ログイン状態により振り分け）
Route::get('/', function () {
    return auth()->check() ? redirect()->route('attendance.index') : redirect()->route('login');
});

// 管理者ログイン
Route::get('/admin/login', [AdminLoginController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminLoginController::class, 'login'])->name('admin.login.post');

// 一般ユーザー 登録・ログイン
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.post');
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.post');
});

// A. メール認証が必要な機能
Route::middleware(['auth', 'verified'])->group(function () {


    // 管理者専用機能
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/attendance/list', [AdminAttendanceController::class, 'showAllList'])->name('attendance.list');
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'showDetail'])->name('attendance.detail');
        Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])->name('staff.list');
        Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])->name('attendance.staff');
        Route::patch('/attendance/update/{id}', [AdminAttendanceController::class, 'update'])->name('attendance.update');
        Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'downloadCsv'])->name('attendance.staff.csv');

    });

    Route::middleware('admin')->name('admin.')->group(function () {
        // 表示 (GET)
        Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'showApprove'])
            ->name('stamp_correction_request.approve');

        // 承認実行 (PATCH)
        Route::patch('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminAttendanceController::class, 'approve'])
            ->name('stamp_correction_request.approve.post');
    });

    // 一般ユーザー専用機能
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/list', [AttendanceController::class, 'list'])->name('list');
        Route::get('/detail/{id}', [AttendanceController::class, 'detail'])->name('detail');
        Route::patch('/detail/{id}', [AttendanceController::class, 'update'])->name('update');

        Route::post('/punch-in', [AttendanceController::class, 'punchIn'])->name('punch-in');
        Route::post('/punch-out', [AttendanceController::class, 'punchOut'])->name('punch-out');
        Route::post('/rest-start', [AttendanceController::class, 'restStart'])->name('rest-start');
        Route::post('/rest-end', [AttendanceController::class, 'restEnd'])->name('rest-end');
    });

    // 共通：申請一覧
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'requestList'])
        ->name('stamp_correction_request.list');
});

// B. ログインは必要だが、メール認証前でもアクセスできるルート
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    // 誘導画面の表示（作成したコントローラを使用）
    Route::get('/email/verify', [EmailVerificationController::class, 'show'])
        ->name('verification.notice');

    // メールのリンクをクリックした時の処理
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('attendance.index');
    })->middleware('signed')->name('verification.verify');

    // 認証メールの再送
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', '認証メールを再送しました！');
    })->middleware('throttle:6,1')->name('verification.send');
});
