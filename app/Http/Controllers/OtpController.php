<?php

namespace App\Http\Controllers;

use App\Services\OtpService;
use Illuminate\Http\Request;

class OtpController extends Controller
{
    protected $otpService;

    /**
     * Inject OtpService
     */
    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Send OTP
     */
    public function send(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10'
        ]);

        try {
            $otp = $this->otpService->send($request->mobile);

            return back()->with([
                'success' => 'OTP Sent Successfully',
                'tracking_code' => $otp->tracking_code
            ]);

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Verify OTP
     */
    public function verify(Request $request)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
            'code' => 'required|digits:6',
            'tracking_code' => 'required'
        ]);

        $result = $this->otpService->verify(
            $request->mobile,
            $request->code,
            $request->tracking_code
        );

        return $result['status']
            ? back()->with('success', $result['message'])
            : back()->with('error', $result['message']);
    }
}