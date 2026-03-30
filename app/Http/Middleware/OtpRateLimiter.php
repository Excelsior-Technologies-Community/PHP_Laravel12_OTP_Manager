<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OtpRateLimiter
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $mobile = $request->input('mobile');

        //  Check if mobile exists
        if (!$mobile) {
            return back()->with('error', 'Mobile number is required');
        }

        //  Rate limit check
        if (cache()->has('otp_limit_' . $mobile)) {
            return back()->with('error', 'Too many OTP requests. Please try again later.');
        }

        //  Store cooldown
        cache()->put('otp_limit_' . $mobile, true, 60);

        return $next($request);
    }
}