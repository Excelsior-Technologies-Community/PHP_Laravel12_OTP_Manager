<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OTP Security Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { background: #030712; }
        .card { background: rgba(15,23,42,0.9); border: 1px solid rgba(255,255,255,0.07); }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        ::-webkit-scrollbar { width: 4px; } ::-webkit-scrollbar-track { background: #0f172a; } ::-webkit-scrollbar-thumb { background: #4f46e5; border-radius: 4px; }
    </style>
</head>
<body class="text-white min-h-screen p-4 md:p-8">

    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-black">🛡️ OTP Security Dashboard</h1>
            <p class="text-gray-400 text-sm mt-1">Live telemetry · IP/Device monitoring · Geo-fence insights</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <div class="flex items-center gap-2 bg-green-500/10 border border-green-500/30 px-3 py-1.5 rounded-full text-xs text-green-400">
                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span> Live Monitoring
            </div>
            <a href="{{ route('dashboard.export') }}{{ request()->getQueryString() ? '?' . request()->getQueryString() : '' }}"
               class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-xl text-sm font-semibold transition">
                📥 Export CSV
            </a>
            <a href="{{ route('dashboard') }}" class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-xl text-sm font-semibold transition">
                🔄 Refresh
            </a>
            <a href="/" class="bg-purple-700 hover:bg-purple-600 px-4 py-2 rounded-xl text-sm font-semibold transition">
                📱 OTP Page
            </a>
        </div>
    </div>

    <!-- Live Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <div class="stat-card card rounded-2xl p-4 border-l-4 border-blue-500">
            <div class="text-gray-400 text-xs uppercase tracking-widest">Total Sent</div>
            <div class="text-3xl font-black text-blue-400 mt-1" id="stat-total">{{ $stats['total'] }}</div>
        </div>
        <div class="stat-card card rounded-2xl p-4 border-l-4 border-green-500">
            <div class="text-gray-400 text-xs uppercase tracking-widest">Verified</div>
            <div class="text-3xl font-black text-green-400 mt-1" id="stat-verified">{{ $stats['verified'] }}</div>
        </div>
        <div class="stat-card card rounded-2xl p-4 border-l-4 border-yellow-500">
            <div class="text-gray-400 text-xs uppercase tracking-widest">Pending</div>
            <div class="text-3xl font-black text-yellow-400 mt-1" id="stat-pending">{{ $stats['pending'] }}</div>
        </div>
        <div class="stat-card card rounded-2xl p-4 border-l-4 border-red-500">
            <div class="text-gray-400 text-xs uppercase tracking-widest">Failed</div>
            <div class="text-3xl font-black text-red-400 mt-1" id="stat-failed">{{ $stats['failed'] }}</div>
        </div>
        <div class="stat-card card rounded-2xl p-4 border-l-4 border-gray-500">
            <div class="text-gray-400 text-xs uppercase tracking-widest">Expired</div>
            <div class="text-3xl font-black text-gray-400 mt-1" id="stat-expired">{{ $stats['expired'] }}</div>
        </div>
        <div class="stat-card card rounded-2xl p-4 border-l-4 border-orange-500">
            <div class="text-gray-400 text-xs uppercase tracking-widest">Blocked IPs</div>
            <div class="text-3xl font-black text-orange-400 mt-1" id="stat-blocked">{{ $stats['blocked_ips'] }}</div>
        </div>
    </div>

    <!-- Chart + Geo + Security Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- 7-Day Chart -->
        <div class="lg:col-span-2 card rounded-2xl p-5">
            <h2 class="text-sm font-bold text-gray-300 uppercase tracking-widest mb-4">📊 7-Day OTP Activity</h2>
            <canvas id="activityChart" height="120"></canvas>
        </div>

        <!-- Top Countries + Live Feed -->
        <div class="space-y-4">

            <!-- Top Countries -->
            <div class="card rounded-2xl p-5">
                <h2 class="text-sm font-bold text-gray-300 uppercase tracking-widest mb-3">🌍 Top Countries</h2>
                @forelse($topCountries as $country => $count)
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm text-gray-300">{{ $country }}</span>
                        <span class="bg-indigo-600/30 text-indigo-300 text-xs px-2 py-0.5 rounded-full">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-gray-500 text-sm">No geo data yet</p>
                @endforelse
            </div>

            <!-- Live Security Feed -->
            <div class="card rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-bold text-gray-300 uppercase tracking-widest">⚡ Live Feed</h2>
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                </div>
                <div id="liveFeed" class="space-y-2 max-h-36 overflow-y-auto">
                    @foreach($securityLogs->take(5) as $log)
                        <div class="flex justify-between items-center text-xs">
                            <div>
                                <span class="text-gray-300">{{ $log->ip_address }}</span>
                                <span class="text-gray-500 ml-1">{{ $log->country ?? 'Unknown' }}</span>
                            </div>
                            <span class="px-1.5 py-0.5 rounded text-xs {{ $log->status === 'success' ? 'bg-green-600/30 text-green-400' : ($log->status === 'blocked' ? 'bg-orange-600/30 text-orange-400' : 'bg-red-600/30 text-red-400') }}">
                                {{ $log->status }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card rounded-2xl p-5 mb-6">
        <form method="GET" action="{{ route('dashboard') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="text-xs text-gray-400 block mb-1">Mobile</label>
                <input type="text" name="mobile" value="{{ request('mobile') }}" placeholder="Search mobile..."
                    class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 w-44">
            </div>
            <div>
                <label class="text-xs text-gray-400 block mb-1">Status</label>
                <select name="status" class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">All</option>
                    @foreach(['pending','verified','failed','expired'] as $s)
                        <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-400 block mb-1">Date</label>
                <input type="date" name="date" value="{{ request('date') }}"
                    class="bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 px-4 py-2 rounded-xl text-sm font-semibold transition">🔍 Filter</button>
            <a href="{{ route('dashboard') }}" class="bg-slate-700 hover:bg-slate-600 px-4 py-2 rounded-xl text-sm font-semibold transition">✕ Clear</a>
        </form>
    </div>

    <!-- OTP Logs Table -->
    <div class="card rounded-2xl overflow-hidden mb-8">
        <div class="p-4 border-b border-slate-800 flex items-center justify-between">
            <h2 class="font-bold text-gray-200">📋 OTP Logs</h2>
            <span class="text-xs text-gray-500">{{ $otps->total() }} records</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-800/60">
                    <tr class="text-gray-400 text-xs uppercase tracking-wider">
                        <th class="p-4 text-left">Mobile</th>
                        <th class="p-4 text-left">Status</th>
                        <th class="p-4 text-left">Attempts</th>
                        <th class="p-4 text-left">IP Address</th>
                        <th class="p-4 text-left">Location</th>
                        <th class="p-4 text-left">Expires At</th>
                        <th class="p-4 text-left">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($otps as $otp)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="p-4 font-mono text-gray-200">{{ $otp->mobile }}</td>
                            <td class="p-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold
                                    {{ $otp->status === 'verified' ? 'bg-green-600/20 text-green-400 border border-green-600/30' :
                                       ($otp->status === 'failed'  ? 'bg-red-600/20 text-red-400 border border-red-600/30' :
                                       ($otp->status === 'expired' ? 'bg-gray-600/20 text-gray-400 border border-gray-600/30' :
                                        'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30')) }}">
                                    {{ ucfirst($otp->status) }}
                                </span>
                            </td>
                            <td class="p-4 text-gray-300">{{ $otp->attempts }}/{{ config('otp.max_attempts') }}</td>
                            <td class="p-4 font-mono text-gray-400 text-xs">{{ $otp->ip_address ?? '—' }}</td>
                            <td class="p-4 text-gray-400 text-xs">
                                {{ $otp->country ?? '—' }}{{ $otp->city ? ' · ' . $otp->city : '' }}
                            </td>
                            <td class="p-4 text-gray-400 text-xs">{{ $otp->expires_at?->format('M d, H:i') ?? '—' }}</td>
                            <td class="p-4 text-gray-400 text-xs">{{ $otp->created_at->format('M d, H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-8 text-center text-gray-500">No OTP records found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-800">
            {{ $otps->links() }}
        </div>
    </div>

    <!-- Blocked IPs -->
    @if($blockedIps->count())
    <div class="card rounded-2xl overflow-hidden mb-8">
        <div class="p-4 border-b border-slate-800">
            <h2 class="font-bold text-orange-400">🚫 Active Blocked IPs</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-800/60">
                    <tr class="text-gray-400 text-xs uppercase tracking-wider">
                        <th class="p-4 text-left">IP Address</th>
                        <th class="p-4 text-left">Reason</th>
                        <th class="p-4 text-left">Hit Count</th>
                        <th class="p-4 text-left">Blocked Until</th>
                        <th class="p-4 text-left">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @foreach($blockedIps as $blocked)
                        <tr class="hover:bg-slate-800/40">
                            <td class="p-4 font-mono text-orange-300">{{ $blocked->ip_address }}</td>
                            <td class="p-4 text-gray-400 text-xs">{{ $blocked->reason ?? '—' }}</td>
                            <td class="p-4">
                                <span class="bg-red-600/20 text-red-400 px-2 py-0.5 rounded-full text-xs">{{ $blocked->hit_count }}x</span>
                            </td>
                            <td class="p-4 text-gray-400 text-xs">{{ $blocked->blocked_until?->format('M d, H:i') ?? 'Permanent' }}</td>
                            <td class="p-4">
                                <form method="POST" action="{{ route('dashboard.unblock') }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="ip_address" value="{{ $blocked->ip_address }}">
                                    <button type="submit" class="bg-green-600/20 text-green-400 border border-green-600/30 px-3 py-1 rounded-lg text-xs hover:bg-green-600/40 transition">
                                        Unblock
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Security Event Log -->
    <div class="card rounded-2xl overflow-hidden">
        <div class="p-4 border-b border-slate-800">
            <h2 class="font-bold text-gray-200">🔍 Security Event Log</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-800/60">
                    <tr class="text-gray-400 text-xs uppercase tracking-wider">
                        <th class="p-4 text-left">Event</th>
                        <th class="p-4 text-left">Status</th>
                        <th class="p-4 text-left">Mobile</th>
                        <th class="p-4 text-left">IP</th>
                        <th class="p-4 text-left">Location</th>
                        <th class="p-4 text-left">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800">
                    @forelse($securityLogs as $log)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="p-4">
                                <span class="text-xs font-mono text-indigo-300">{{ str_replace('_', ' ', $log->event_type) }}</span>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold
                                    {{ $log->status === 'success' ? 'bg-green-600/20 text-green-400' :
                                       ($log->status === 'blocked' ? 'bg-orange-600/20 text-orange-400' : 'bg-red-600/20 text-red-400') }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="p-4 font-mono text-gray-300 text-xs">{{ $log->mobile ?? '—' }}</td>
                            <td class="p-4 font-mono text-gray-400 text-xs">{{ $log->ip_address }}</td>
                            <td class="p-4 text-gray-400 text-xs">{{ $log->country ?? '—' }}{{ $log->city ? ' · ' . $log->city : '' }}</td>
                            <td class="p-4 text-gray-500 text-xs">{{ $log->created_at->format('M d, H:i:s') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-8 text-center text-gray-500">No security events yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

<script>
// Chart
const ctx = document.getElementById('activityChart').getContext('2d');
const chartData = @json($chartData);

new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartData.map(d => d.date),
        datasets: [
            { label: 'Sent',     data: chartData.map(d => d.sent),     backgroundColor: 'rgba(99,102,241,0.7)',  borderRadius: 6 },
            { label: 'Verified', data: chartData.map(d => d.verified), backgroundColor: 'rgba(34,197,94,0.7)',  borderRadius: 6 },
            { label: 'Failed',   data: chartData.map(d => d.failed),   backgroundColor: 'rgba(239,68,68,0.7)',  borderRadius: 6 },
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { labels: { color: '#9ca3af', font: { size: 11 } } } },
        scales: {
            x: { ticks: { color: '#6b7280' }, grid: { color: 'rgba(255,255,255,0.04)' } },
            y: { ticks: { color: '#6b7280' }, grid: { color: 'rgba(255,255,255,0.04)' }, beginAtZero: true }
        }
    }
});

// Live telemetry polling every 10 seconds
async function pollTelemetry() {
    try {
        const res = await fetch('{{ route("dashboard.telemetry") }}', { headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        document.getElementById('stat-total').textContent    = data.total;
        document.getElementById('stat-verified').textContent = data.verified;
        document.getElementById('stat-pending').textContent  = data.pending;
        document.getElementById('stat-failed').textContent   = data.failed;
        document.getElementById('stat-expired').textContent  = data.expired;
        document.getElementById('stat-blocked').textContent  = data.blocked_ips;

        // Update live feed
        const feed = document.getElementById('liveFeed');
        if (data.recent_logs && data.recent_logs.length) {
            feed.innerHTML = data.recent_logs.map(log => `
                <div class="flex justify-between items-center text-xs">
                    <div>
                        <span class="text-gray-300">${log.ip_address}</span>
                        <span class="text-gray-500 ml-1">${log.country ?? 'Unknown'}</span>
                    </div>
                    <span class="px-1.5 py-0.5 rounded text-xs ${
                        log.status === 'success' ? 'bg-green-600/30 text-green-400' :
                        log.status === 'blocked' ? 'bg-orange-600/30 text-orange-400' : 'bg-red-600/30 text-red-400'
                    }">${log.status}</span>
                </div>
            `).join('');
        }
    } catch {}
}

setInterval(pollTelemetry, 10000);
</script>
</body>
</html>
