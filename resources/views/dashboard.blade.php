<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>OTP Dashboard</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-950 text-white min-h-screen p-8">

    <h1 class="text-4xl font-bold mb-8">
        🚀 OTP Analytics Dashboard
    </h1>

    <!-- Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">

        <div class="bg-blue-600 p-6 rounded-2xl shadow-xl">
            <h2 class="text-xl">Total OTPs</h2>
            <p class="text-4xl font-bold mt-2">{{ $total }}</p>
        </div>

        <div class="bg-green-600 p-6 rounded-2xl shadow-xl">
            <h2 class="text-xl">Verified</h2>
            <p class="text-4xl font-bold mt-2">{{ $verified }}</p>
        </div>

        <div class="bg-yellow-500 p-6 rounded-2xl shadow-xl">
            <h2 class="text-xl">Pending</h2>
            <p class="text-4xl font-bold mt-2">{{ $pending }}</p>
        </div>

        <div class="bg-red-600 p-6 rounded-2xl shadow-xl">
            <h2 class="text-xl">Failed</h2>
            <p class="text-4xl font-bold mt-2">{{ $failed }}</p>
        </div>

    </div>

    <!-- Table -->
    <div class="bg-gray-900 rounded-2xl overflow-hidden shadow-2xl">

        <table class="w-full">

            <thead class="bg-gray-800">
                <tr>
                    <th class="p-4 text-left">Mobile</th>
                    <th class="p-4 text-left">OTP</th>
                    <th class="p-4 text-left">Attempts</th>
                    <th class="p-4 text-left">Status</th>
                    <th class="p-4 text-left">Expiry</th>
                </tr>
            </thead>

            <tbody>

                @foreach($otps as $otp)

                    <tr class="border-b border-gray-800 hover:bg-gray-800">

                        <td class="p-4">{{ $otp->mobile }}</td>

                        <td class="p-4">{{ $otp->code }}</td>

                        <td class="p-4">{{ $otp->attempts }}</td>

                        <td class="p-4">

                            @if($otp->status == 'verified')
                                <span class="bg-green-600 px-3 py-1 rounded-full text-sm">
                                    Verified
                                </span>

                            @elseif($otp->status == 'failed')
                                <span class="bg-red-600 px-3 py-1 rounded-full text-sm">
                                    Failed
                                </span>

                            @else
                                <span class="bg-yellow-500 px-3 py-1 rounded-full text-sm">
                                    Pending
                                </span>
                            @endif

                        </td>

                        <td class="p-4">
                            {{ $otp->expires_at }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $otps->links() }}
    </div>

</body>
</html>