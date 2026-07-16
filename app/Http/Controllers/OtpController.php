<?php

namespace App\Http\Controllers;

use App\Models\Otp;
use App\Services\OtpService;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    public function send(Request $request)
    {
        $request->validate(['mobile' => 'required|digits:10']);

        try {
            $otp = $this->otpService->send($request->mobile, null, $request);

            if ($request->expectsJson()) {
                return response()->json([
                    'status'        => true,
                    'message'       => 'OTP Sent Successfully',
                    'tracking_code' => $otp->tracking_code,
                    'expires_in'    => config('otp.expiry') * 60,
                    'cooldown'      => config('otp.cooldown'),
                ]);
            }

            return back()->with([
                'success'       => 'OTP Sent Successfully',
                'tracking_code' => $otp->tracking_code,
            ]);

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage());
        }
    }

    public function verify(Request $request)
    {
        $request->validate([
            'mobile'        => 'required|digits:10',
            'code'          => 'required|digits:6',
            'tracking_code' => 'required',
        ]);

        $result = $this->otpService->verify(
            $request->mobile,
            $request->code,
            $request->tracking_code,
            $request
        );

        if ($request->expectsJson()) {
            return response()->json($result, $result['status'] ? 200 : 422);
        }

        return $result['status']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }

    public function history(Request $request)
    {
        $request->validate(['mobile' => 'required|digits:10']);

        $otps = Otp::where('mobile', $request->mobile)
            ->latest()
            ->take(10)
            ->get(['tracking_code', 'status', 'attempts', 'expires_at', 'created_at', 'country', 'city']);

        return response()->json(['status' => true, 'data' => $otps]);
    }
}
