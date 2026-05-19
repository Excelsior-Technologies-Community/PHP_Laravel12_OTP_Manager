<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('otp');
});

Route::middleware(['otp.limit'])->group(function () {

    Route::post('/send-otp', [
        OtpController::class,
        'send'
    ])->name('send.otp');

});

Route::post('/verify-otp', [
    OtpController::class,
    'verify'
])->name('verify.otp');

Route::get('/dashboard', [
    DashboardController::class,
    'index'
])->name('dashboard');