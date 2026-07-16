<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OTP Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: radial-gradient(circle at top, #312e81, #0f172a 65%); }
        .card { background: rgba(15,23,42,0.85); backdrop-filter: blur(18px); border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 10px 35px rgba(0,0,0,0.45); }
        .glow { box-shadow: 0 0 20px rgba(99,102,241,0.35); }
        .input-field { @apply w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500; }
        #toast { transition: all 0.4s ease; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-8">

<!-- Toast -->
<div id="toast" class="fixed top-5 right-5 z-50 hidden px-5 py-3 rounded-2xl text-sm font-semibold shadow-2xl"></div>

<div class="card rounded-[28px] overflow-hidden w-full max-w-5xl grid md:grid-cols-2">

    <!-- LEFT -->
    <div class="hidden md:flex flex-col justify-between bg-gradient-to-br from-indigo-600 to-purple-700 p-8 relative">
        <div>
            <div class="text-5xl mb-4">🔐</div>
            <h1 class="text-4xl font-black text-white leading-tight">OTP <br>Manager</h1>
            <p class="text-indigo-100 mt-4 text-sm leading-relaxed">
                Secure OTP system with IP/Device fingerprinting, geo-fencing, brute-force protection, live telemetry dashboard and AJAX verification.
            </p>

            <!-- Security Indicators -->
            <div class="mt-6 space-y-2">
                <div class="flex items-center gap-2 text-indigo-100 text-xs">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span> IP Rate Limiting Active
                </div>
                <div class="flex items-center gap-2 text-indigo-100 text-xs">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span> Device Fingerprinting Active
                </div>
                <div class="flex items-center gap-2 text-indigo-100 text-xs">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span> Geo-Fence Tracking Active
                </div>
                <div class="flex items-center gap-2 text-indigo-100 text-xs">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span> Bot Detection Active
                </div>
            </div>
        </div>
        <a href="{{ route('dashboard') }}">
            <button class="bg-white text-indigo-700 px-5 py-3 rounded-2xl font-bold hover:scale-105 transition-all duration-300 shadow-xl w-full">
                🚀 Open Dashboard
            </button>
        </a>
        <div class="absolute -bottom-12 -right-12 w-52 h-52 bg-pink-500 rounded-full blur-3xl opacity-30"></div>
    </div>

    <!-- RIGHT -->
    <div class="p-6 md:p-7 text-white overflow-y-auto max-h-screen">

        <!-- Mobile Header -->
        <div class="md:hidden text-center mb-5">
            <div class="text-4xl mb-2">🔐</div>
            <h1 class="text-2xl font-black">OTP Manager</h1>
        </div>

        <!-- Alert Box -->
        <div id="alertBox" class="hidden px-4 py-3 rounded-2xl mb-4 text-sm"></div>

        @if(session('success'))
            <div class="bg-green-500/20 border border-green-400 text-green-300 px-4 py-3 rounded-2xl mb-4 text-sm">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-red-500/20 border border-red-400 text-red-300 px-4 py-3 rounded-2xl mb-4 text-sm">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="bg-red-500/20 border border-red-400 text-red-300 px-4 py-3 rounded-2xl mb-4 text-sm">
                @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif

        <!-- SEND OTP -->
        <form id="sendForm" class="space-y-4">
            @csrf
            <div>
                <label class="text-sm text-gray-300 mb-2 block">Mobile Number</label>
                <input type="text" name="mobile" id="mobile" maxlength="10" placeholder="Enter 10-digit Mobile Number"
                    class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" id="sendBtn"
                class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 py-3 rounded-2xl font-bold glow hover:scale-[1.01] transition-all duration-300 flex items-center justify-center gap-2">
                <span id="sendBtnText">📩 Send OTP</span>
                <svg id="sendSpinner" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
            </button>
        </form>

        <!-- Cooldown Bar -->
        <div id="cooldownBar" class="hidden mt-3">
            <div class="flex justify-between text-xs text-gray-400 mb-1">
                <span>Cooldown</span><span id="cooldownText">60s</span>
            </div>
            <div class="w-full bg-slate-700 rounded-full h-1.5">
                <div id="cooldownFill" class="bg-indigo-500 h-1.5 rounded-full transition-all duration-1000" style="width:100%"></div>
            </div>
        </div>

        <!-- Divider -->
        <div class="flex items-center my-5">
            <hr class="flex-grow border-slate-700">
            <span class="px-3 text-gray-400 text-xs">VERIFY OTP</span>
            <hr class="flex-grow border-slate-700">
        </div>

        <!-- VERIFY OTP -->
        <form id="verifyForm" class="space-y-4">
            @csrf
            <input type="text" name="mobile" id="verifyMobile" maxlength="10" placeholder="Enter Mobile Number"
                class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">

            <input type="text" name="code" id="otpCode" maxlength="6" placeholder="Enter 6-digit OTP"
                class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">

            <div class="relative">
                <input type="text" name="tracking_code" id="trackingCode"
                    value="{{ session('tracking_code') }}"
                    placeholder="Tracking Code (auto-filled)"
                    readonly
                    class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-4 py-3 text-gray-400 text-sm pr-10">
                <span id="trackingIcon" class="absolute right-3 top-3.5 text-gray-500 text-sm">🔗</span>
            </div>

            <button type="submit" id="verifyBtn"
                class="w-full bg-gradient-to-r from-pink-500 to-purple-600 py-3 rounded-2xl font-bold glow hover:scale-[1.01] transition-all duration-300 flex items-center justify-center gap-2">
                <span id="verifyBtnText">✅ Verify OTP</span>
                <svg id="verifySpinner" class="hidden animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                </svg>
            </button>
        </form>

        <!-- OTP Timer -->
        <div id="timerBox" class="mt-5 bg-slate-900 border border-slate-700 rounded-3xl p-4 text-center hidden">
            <div class="text-gray-400 text-xs uppercase tracking-widest">OTP Expires In</div>
            <div id="timer" class="text-4xl font-black text-red-400 mt-1">05:00</div>
            <div class="w-full bg-slate-700 rounded-full h-1 mt-2">
                <div id="timerBar" class="bg-red-500 h-1 rounded-full transition-all duration-1000" style="width:100%"></div>
            </div>
        </div>

        <!-- Resend -->
        <div id="resendBox" class="hidden mt-4">
            <button id="resendBtn"
                class="w-full bg-gradient-to-r from-green-500 to-emerald-600 py-3 rounded-2xl font-bold text-white hover:scale-[1.01] transition-all duration-300">
                🔄 Resend OTP
            </button>
        </div>

        <!-- OTP History -->
        <div class="mt-5">
            <button id="historyToggle" class="w-full bg-slate-800 border border-slate-700 py-2.5 rounded-2xl text-sm text-gray-300 hover:bg-slate-700 transition">
                📋 View OTP History
            </button>
            <div id="historyPanel" class="hidden mt-3 bg-slate-900 border border-slate-700 rounded-2xl overflow-hidden">
                <div class="p-3 border-b border-slate-700 text-xs text-gray-400 uppercase tracking-widest">Recent OTPs</div>
                <div id="historyList" class="divide-y divide-slate-800 max-h-48 overflow-y-auto text-sm"></div>
            </div>
        </div>

    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let timerInterval = null;
let timerSeconds = {{ config('otp.expiry') * 60 }};
let cooldownInterval = null;

// Toast
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.className = `fixed top-5 right-5 z-50 px-5 py-3 rounded-2xl text-sm font-semibold shadow-2xl ${type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
    t.textContent = msg;
    t.classList.remove('hidden');
    setTimeout(() => t.classList.add('hidden'), 4000);
}

// Alert
function showAlert(msg, type = 'success') {
    const a = document.getElementById('alertBox');
    a.className = `px-4 py-3 rounded-2xl mb-4 text-sm ${type === 'success' ? 'bg-green-500/20 border border-green-400 text-green-300' : 'bg-red-500/20 border border-red-400 text-red-300'}`;
    a.textContent = msg;
    a.classList.remove('hidden');
    setTimeout(() => a.classList.add('hidden'), 5000);
}

// Timer
function startTimer(seconds) {
    clearInterval(timerInterval);
    timerSeconds = seconds;
    const total = seconds;
    const box = document.getElementById('timerBox');
    const display = document.getElementById('timer');
    const bar = document.getElementById('timerBar');
    box.classList.remove('hidden');

    timerInterval = setInterval(() => {
        const m = Math.floor(timerSeconds / 60);
        const s = timerSeconds % 60;
        display.textContent = `${m}:${s < 10 ? '0' : ''}${s}`;
        bar.style.width = ((timerSeconds / total) * 100) + '%';
        timerSeconds--;
        if (timerSeconds < 0) {
            clearInterval(timerInterval);
            display.textContent = 'Expired';
            display.className = 'text-4xl font-black text-gray-500 mt-1';
            bar.style.width = '0%';
            document.getElementById('resendBox').classList.remove('hidden');
        }
    }, 1000);
}

// Cooldown bar
function startCooldown(seconds) {
    const bar = document.getElementById('cooldownBar');
    const fill = document.getElementById('cooldownFill');
    const text = document.getElementById('cooldownText');
    bar.classList.remove('hidden');
    let remaining = seconds;
    fill.style.width = '100%';

    cooldownInterval = setInterval(() => {
        remaining--;
        text.textContent = remaining + 's';
        fill.style.width = ((remaining / seconds) * 100) + '%';
        if (remaining <= 0) {
            clearInterval(cooldownInterval);
            bar.classList.add('hidden');
        }
    }, 1000);
}

// SEND OTP
document.getElementById('sendForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const mobile = document.getElementById('mobile').value;
    const btn = document.getElementById('sendBtn');
    const spinner = document.getElementById('sendSpinner');
    const btnText = document.getElementById('sendBtnText');

    btn.disabled = true;
    spinner.classList.remove('hidden');
    btnText.textContent = 'Sending...';

    try {
        const res = await fetch('{{ route("send.otp") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ mobile })
        });
        const data = await res.json();

        if (data.status) {
            // Autofill tracking code
            document.getElementById('trackingCode').value = data.tracking_code;
            document.getElementById('trackingIcon').textContent = '✅';
            document.getElementById('verifyMobile').value = mobile;

            showAlert('OTP sent successfully! Check logs.', 'success');
            showToast('OTP Sent ✅', 'success');
            startTimer(data.expires_in);
            startCooldown(data.cooldown);
            document.getElementById('resendBox').classList.add('hidden');
        } else {
            showAlert(data.message, 'error');
            showToast(data.message, 'error');
        }
    } catch {
        showAlert('Something went wrong. Try again.', 'error');
    } finally {
        btn.disabled = false;
        spinner.classList.add('hidden');
        btnText.textContent = '📩 Send OTP';
    }
});

// VERIFY OTP
document.getElementById('verifyForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('verifyBtn');
    const spinner = document.getElementById('verifySpinner');
    const btnText = document.getElementById('verifyBtnText');

    btn.disabled = true;
    spinner.classList.remove('hidden');
    btnText.textContent = 'Verifying...';

    const payload = {
        mobile: document.getElementById('verifyMobile').value,
        code: document.getElementById('otpCode').value,
        tracking_code: document.getElementById('trackingCode').value,
    };

    try {
        const res = await fetch('{{ route("verify.otp") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();

        if (data.status) {
            clearInterval(timerInterval);
            document.getElementById('timerBox').classList.add('hidden');
            document.getElementById('resendBox').classList.add('hidden');
            showAlert('✅ ' + data.message, 'success');
            showToast('Verified! ✅', 'success');
            document.getElementById('otpCode').value = '';
        } else {
            showAlert('❌ ' + data.message, 'error');
            showToast(data.message, 'error');
        }
    } catch {
        showAlert('Something went wrong. Try again.', 'error');
    } finally {
        btn.disabled = false;
        spinner.classList.add('hidden');
        btnText.textContent = '✅ Verify OTP';
    }
});

// RESEND
document.getElementById('resendBtn').addEventListener('click', () => {
    document.getElementById('sendForm').dispatchEvent(new Event('submit'));
    document.getElementById('resendBox').classList.add('hidden');
});

// HISTORY
document.getElementById('historyToggle').addEventListener('click', async () => {
    const mobile = document.getElementById('mobile').value || document.getElementById('verifyMobile').value;
    if (!mobile || mobile.length !== 10) { showToast('Enter a valid mobile number first', 'error'); return; }

    const panel = document.getElementById('historyPanel');
    if (!panel.classList.contains('hidden')) { panel.classList.add('hidden'); return; }

    try {
        const res = await fetch(`{{ route("otp.history") }}?mobile=${mobile}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        const list = document.getElementById('historyList');

        if (!data.data.length) {
            list.innerHTML = '<div class="p-4 text-gray-500 text-center">No OTP history found</div>';
        } else {
            list.innerHTML = data.data.map(o => `
                <div class="p-3 flex justify-between items-center">
                    <div>
                        <div class="text-xs text-gray-400">${o.created_at}</div>
                        <div class="text-xs text-gray-500">${o.country ?? 'Unknown'} ${o.city ? '· ' + o.city : ''}</div>
                    </div>
                    <div class="text-right">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold ${
                            o.status === 'verified' ? 'bg-green-600' :
                            o.status === 'failed'   ? 'bg-red-600' :
                            o.status === 'expired'  ? 'bg-gray-600' : 'bg-yellow-500'
                        }">${o.status}</span>
                        <div class="text-xs text-gray-500 mt-0.5">Attempts: ${o.attempts}</div>
                    </div>
                </div>
            `).join('');
        }
        panel.classList.remove('hidden');
    } catch {
        showToast('Failed to load history', 'error');
    }
});

// Auto-start timer if tracking code already in session
@if(session('tracking_code'))
    document.getElementById('timerBox').classList.remove('hidden');
    startTimer({{ config('otp.expiry') * 60 }});
@endif
</script>
</body>
</html>
