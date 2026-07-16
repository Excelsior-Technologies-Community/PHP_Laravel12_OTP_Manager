<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\DashboardController;

Route::get('/', fn() => view('otp'));

Route::middleware(['otp.limit'])->group(function () {
    Route::post('/send-otp', [OtpController::class, 'send'])->name('send.otp');
});

Route::post('/verify-otp', [OtpController::class, 'verify'])->name('verify.otp');
Route::get('/otp-history', [OtpController::class, 'history'])->name('otp.history');

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/telemetry', [DashboardController::class, 'telemetry'])->name('dashboard.telemetry');
Route::get('/dashboard/export-csv', [DashboardController::class, 'exportCsv'])->name('dashboard.export');
Route::post('/dashboard/unblock-ip', [DashboardController::class, 'unblockIp'])->name('dashboard.unblock');
