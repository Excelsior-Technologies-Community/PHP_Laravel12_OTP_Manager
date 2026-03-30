<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OTP Manager</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gradient-to-br from-blue-100 to-indigo-200 min-h-screen flex items-center justify-center">

<div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-md">

    <!-- Title -->
    <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">
        📱 OTP Manager
    </h2>

    <!-- Success -->
    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Error -->
    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Validation Errors -->
    @if ($errors->any())
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4 text-sm">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- SEND OTP -->
    <form method="POST" action="{{ route('send.otp') }}" class="space-y-4">
        @csrf

        <input 
            type="text" 
            name="mobile" 
            placeholder="Enter Mobile Number"
            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
            required
        >

        <button 
            type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition"
        >
            Send OTP
        </button>
    </form>

    <!-- Divider -->
    <div class="flex items-center my-6">
        <hr class="flex-grow border-gray-300">
        <span class="px-3 text-gray-500 text-sm">Verify OTP</span>
        <hr class="flex-grow border-gray-300">
    </div>

    <!-- VERIFY OTP -->
    <form method="POST" action="{{ route('verify.otp') }}" class="space-y-4">
        @csrf

        <input 
            type="text" 
            name="mobile" 
            placeholder="Enter Mobile Number"
            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400"
            required
        >

        <input 
            type="text" 
            name="code" 
            placeholder="Enter OTP"
            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-400"
            required
        >

        <input 
            type="text" 
            name="tracking_code"
            value="{{ session('tracking_code') }}"
            placeholder="Tracking Code"
            readonly
            class="w-full px-4 py-2 border rounded-lg bg-gray-100 text-gray-500"
        >

        <button 
            type="submit"
            class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition"
        >
            Verify OTP
        </button>
    </form>

</div>

</body>
</html>