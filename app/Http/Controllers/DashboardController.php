<?php

namespace App\Http\Controllers;

use App\Models\Otp;

class DashboardController extends Controller
{
    public function index()
    {
        $otps = Otp::latest()->paginate(4);

        return view('dashboard', [
            'otps' => $otps,
            'total' => Otp::count(),
            'verified' => Otp::where('status', 'verified')->count(),
            'pending' => Otp::where('status', 'pending')->count(),
            'failed' => Otp::where('status', 'failed')->count(),
        ]);
    }
}