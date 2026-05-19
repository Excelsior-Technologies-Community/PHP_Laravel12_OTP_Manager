<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>OTP Manager</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <style>

        body{
            background:
                radial-gradient(circle at top,
                #312e81,
                #0f172a 65%);
        }

        .card{
            background: rgba(15,23,42,0.82);

            backdrop-filter: blur(18px);

            border: 1px solid rgba(255,255,255,0.08);

            box-shadow:
                0 10px 35px rgba(0,0,0,0.45);
        }

        .glow{
            box-shadow:
                0 0 20px rgba(99,102,241,0.35);
        }

    </style>

</head>

<body class="h-screen overflow-hidden flex items-center justify-center px-4">

    <div class="card rounded-[28px] overflow-hidden w-full max-w-5xl grid md:grid-cols-2">

        <!-- LEFT SIDE -->
        <div class="hidden md:flex flex-col justify-between bg-gradient-to-br from-indigo-600 to-purple-700 p-8 relative">

            <div>

                <div class="text-5xl mb-4">
                    🔐
                </div>

                <h1 class="text-4xl font-black text-white leading-tight">
                    OTP <br>
                    Manager
                </h1>

                <p class="text-indigo-100 mt-4 text-sm leading-relaxed">

                    Secure OTP verification system with
                    analytics dashboard, cooldown timer,
                    attempt protection and premium UI.

                </p>

            </div>

            <a href="{{ route('dashboard') }}">

                <button
                    class="bg-white text-indigo-700 px-5 py-3 rounded-2xl font-bold hover:scale-105 transition-all duration-300 shadow-xl">

                    🚀 Open Dashboard
                </button>

            </a>

            <!-- Blur -->
            <div class="absolute -bottom-12 -right-12 w-52 h-52 bg-pink-500 rounded-full blur-3xl opacity-30"></div>

        </div>

        <!-- RIGHT SIDE -->
        <div class="p-6 md:p-7 text-white">

            <!-- Mobile Header -->
            <div class="md:hidden text-center mb-5">

                <div class="text-4xl mb-2">
                    🔐
                </div>

                <h1 class="text-2xl font-black">
                    OTP Manager
                </h1>

            </div>

            <!-- Success -->
            @if(session('success'))

                <div class="bg-green-500/20 border border-green-400 text-green-300 px-4 py-3 rounded-2xl mb-4 text-sm">

                    {{ session('success') }}

                </div>

            @endif

            <!-- Error -->
            @if(session('error'))

                <div class="bg-red-500/20 border border-red-400 text-red-300 px-4 py-3 rounded-2xl mb-4 text-sm">

                    {{ session('error') }}

                </div>

            @endif

            <!-- Validation -->
            @if ($errors->any())

                <div class="bg-red-500/20 border border-red-400 text-red-300 px-4 py-3 rounded-2xl mb-4 text-sm">

                    @foreach ($errors->all() as $error)

                        <p>{{ $error }}</p>

                    @endforeach

                </div>

            @endif

            <!-- SEND OTP -->
            <form method="POST"
                  action="{{ route('send.otp') }}"
                  class="space-y-4">

                @csrf

                <div>

                    <label class="text-sm text-gray-300 mb-2 block">
                        Mobile Number
                    </label>

                    <input
                        type="text"
                        name="mobile"
                        id="mobile"
                        placeholder="Enter Mobile Number"
                        class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">

                </div>

                <button
                    type="submit"
                    class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 py-3 rounded-2xl font-bold glow hover:scale-[1.01] transition-all duration-300">

                    📩 Send OTP
                </button>

            </form>

            <!-- Divider -->
            <div class="flex items-center my-5">

                <hr class="flex-grow border-slate-700">

                <span class="px-3 text-gray-400 text-xs">
                    VERIFY OTP
                </span>

                <hr class="flex-grow border-slate-700">

            </div>

            <!-- VERIFY OTP -->
            <form method="POST"
                  action="{{ route('verify.otp') }}"
                  class="space-y-4">

                @csrf

                <input
                    type="text"
                    name="mobile"
                    placeholder="Enter Mobile Number"
                    class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">

                <input
                    type="text"
                    name="code"
                    placeholder="Enter OTP"
                    class="w-full bg-slate-800 border border-slate-700 rounded-2xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">

                <input
                    type="text"
                    name="tracking_code"
                    value="{{ session('tracking_code') }}"
                    readonly
                    class="w-full bg-slate-900 border border-slate-700 rounded-2xl px-4 py-3 text-gray-400 text-sm">

                <button
                    type="submit"
                    class="w-full bg-gradient-to-r from-pink-500 to-purple-600 py-3 rounded-2xl font-bold glow hover:scale-[1.01] transition-all duration-300">

                    ✅ Verify OTP
                </button>

            </form>

            <!-- Timer -->
            <div class="mt-5 bg-slate-900 border border-slate-700 rounded-3xl p-4 text-center">

                <div class="text-gray-400 text-xs uppercase tracking-widest">

                    OTP Expires In

                </div>

                <div id="timer"
                     class="text-4xl font-black text-red-400 mt-1">

                    05:00

                </div>

            </div>

            <!-- Resend -->
            <form method="POST"
                  action="{{ route('send.otp') }}"
                  class="mt-4">

                @csrf

                <input
                    type="hidden"
                    name="mobile"
                    id="resend_mobile">

                <button
                    type="submit"
                    id="resendBtn"
                    disabled
                    class="w-full bg-slate-700 py-3 rounded-2xl font-bold text-white hover:scale-[1.01] transition-all duration-300">

                    🔄 Resend OTP
                </button>

            </form>

        </div>

    </div>

    <script>

        // Mobile Copy
        const mobileInput =
            document.getElementById('mobile');

        const resendMobile =
            document.getElementById('resend_mobile');

        mobileInput.addEventListener('keyup', () => {

            resendMobile.value =
                mobileInput.value;

        });

        // Timer
        let time = 300;

        const timer =
            document.getElementById('timer');

        const resendBtn =
            document.getElementById('resendBtn');

        const countdown = setInterval(() => {

            let minutes =
                Math.floor(time / 60);

            let seconds =
                time % 60;

            timer.innerHTML =
                `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

            time--;

            if(time < 0){

                clearInterval(countdown);

                timer.innerHTML = "Expired";

                resendBtn.disabled = false;

                resendBtn.classList.remove('bg-slate-700');

                resendBtn.classList.add(
                    'bg-gradient-to-r',
                    'from-green-500',
                    'to-emerald-600'
                );

            }

        }, 1000);

    </script>

</body>
</html>