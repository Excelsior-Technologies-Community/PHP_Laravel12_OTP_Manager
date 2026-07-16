<?php

namespace App\Http\Controllers;

use App\Models\BlockedIp;
use App\Models\Otp;
use App\Models\OtpSecurityLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Otp::query();

        if ($request->filled('mobile')) {
            $query->where('mobile', 'like', '%' . $request->mobile . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $otps = $query->latest()->paginate(15)->withQueryString();

        // Telemetry counters
        $stats = [
            'total'      => Otp::count(),
            'verified'   => Otp::where('status', 'verified')->count(),
            'pending'    => Otp::where('status', 'pending')->count(),
            'failed'     => Otp::where('status', 'failed')->count(),
            'expired'    => Otp::where('status', 'expired')->count(),
            'blocked_ips'=> BlockedIp::count(),
        ];

        // Last 7 days chart data
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartData[] = [
                'date'     => now()->subDays($i)->format('M d'),
                'sent'     => Otp::whereDate('created_at', $date)->count(),
                'verified' => Otp::whereDate('created_at', $date)->where('status', 'verified')->count(),
                'failed'   => Otp::whereDate('created_at', $date)->whereIn('status', ['failed', 'expired'])->count(),
            ];
        }

        // Recent security logs
        $securityLogs = OtpSecurityLog::latest()->take(20)->get();

        // Active blocked IPs
        $blockedIps = BlockedIp::where(function ($q) {
            $q->whereNull('blocked_until')->orWhere('blocked_until', '>', now());
        })->latest()->take(10)->get();

        // Top countries
        $topCountries = Otp::whereNotNull('country')
            ->selectRaw('country, count(*) as total')
            ->groupBy('country')
            ->orderByDesc('total')
            ->take(5)
            ->pluck('total', 'country');

        return view('dashboard', compact(
            'otps', 'stats', 'chartData', 'securityLogs', 'blockedIps', 'topCountries'
        ));
    }

    public function telemetry()
    {
        return response()->json([
            'total'      => Otp::count(),
            'verified'   => Otp::where('status', 'verified')->count(),
            'pending'    => Otp::where('status', 'pending')->count(),
            'failed'     => Otp::where('status', 'failed')->count(),
            'expired'    => Otp::where('status', 'expired')->count(),
            'blocked_ips'=> BlockedIp::count(),
            'recent_logs'=> OtpSecurityLog::latest()->take(5)->get(['event_type', 'status', 'ip_address', 'country', 'mobile', 'created_at']),
        ]);
    }

    public function exportCsv(Request $request)
    {
        $query = Otp::query();

        if ($request->filled('mobile')) $query->where('mobile', 'like', '%' . $request->mobile . '%');
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('date'))   $query->whereDate('created_at', $request->date);

        $otps = $query->latest()->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="otp_logs_' . now()->format('Ymd_His') . '.csv"',
        ];

        $callback = function () use ($otps) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Mobile', 'Status', 'Attempts', 'Country', 'City', 'IP Address', 'Expires At', 'Created At']);
            foreach ($otps as $otp) {
                fputcsv($handle, [
                    $otp->id, $otp->mobile, $otp->status, $otp->attempts,
                    $otp->country, $otp->city, $otp->ip_address,
                    $otp->expires_at, $otp->created_at,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function unblockIp(Request $request)
    {
        BlockedIp::where('ip_address', $request->ip_address)->delete();
        return back()->with('success', 'IP unblocked successfully.');
    }
}
