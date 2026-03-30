# PHP_Laravel12_OTP_Manager

## Introduction

PHP_Laravel12_OTP_Manager is a modern, secure, and scalable One-Time Password (OTP) management system built using Laravel 12.

This project demonstrates a real-world implementation of OTP-based authentication by combining Laravel's MVC architecture with a service layer and event-driven design.

It is inspired by package-based OTP systems but extended into a full web application with user interaction, making it ideal for learning, demonstration, and practical implementation.

The system handles OTP generation, verification, rate limiting, and security controls while maintaining clean code structure and separation of concerns.

---

## Project Overview

This project simulates a complete OTP authentication workflow used in modern applications such as login systems, mobile verification, and secure transactions.

The application allows users to:

- Enter a mobile number and request an OTP
- Receive OTP via event-driven system (logged for development)
- Verify OTP using a tracking code
- Handle errors like invalid, expired, or exceeded attempts

Internally, the system follows a structured flow:

User Interface → Routes → Controller → Service Layer → Database → Event → Listener

Key highlights of the architecture:

- Service Layer Pattern for business logic separation
- Event-Driven System for OTP handling
- Middleware for rate limiting and abuse prevention
- Config-driven OTP behavior (expiry, attempts, cooldown)
- Clean and scalable Laravel 12 structure

This makes the project suitable for both learning purposes and real-world application development.

---

## Features

- OTP Generation and Verification
- Event-driven OTP handling
- Rate limiting using middleware
- Configurable OTP settings
- Secure OTP expiration and attempt control
- Clean and scalable architecture
- User-friendly web interface
- Responsive UI using Tailwind CSS

---

## Requirements

- PHP >= 8.2
- Laravel 12
- MySQL

---

## Installation

## Step 1: Create Laravel 12 Project

```bash
composer create-project laravel/laravel PHP_Laravel12_OTP_Manager "12.*"
cd PHP_Laravel12_OTP_Manager
```

---

## Step 2: Database Setup

Update .env:

```.env
DB_DATABASE=otp_manager
DB_USERNAME=root
DB_PASSWORD=
```

Run:

```bash
php artisan migrate
```

---

## Step 3: Create OTP Migration

```bash
php artisan make:migration create_otps_table
```

File: `database/migrations/xxxx_xx_xx_create_otps_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('mobile');
            $table->string('code');
            $table->string('type')->nullable();
            $table->string('tracking_code');
            $table->integer('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
```

Run:

```bash
php artisan migrate
```

---

## Step 4: Create Model

```bash
php artisan make:model Otp
```

File: `app/Models/Otp.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = [
        'mobile',
        'code',
        'type',
        'tracking_code',
        'attempts',
        'expires_at'
    ];
}
```

---

## Step 5: Create Config File

File: `config/otp.php`

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Expiry Time
    |--------------------------------------------------------------------------
    |
    | This value defines how many minutes the OTP will remain valid.
    | After this time, the OTP will be considered expired.
    |
    */

    'expiry' => 5,


    /*
    |--------------------------------------------------------------------------
    | Maximum Verification Attempts
    |--------------------------------------------------------------------------
    |
    | This value defines how many times a user can attempt to verify
    | an OTP before it becomes invalid.
    |
    */

    'max_attempts' => 3,


    /*
    |--------------------------------------------------------------------------
    | OTP Cooldown Time (in seconds)
    |--------------------------------------------------------------------------
    |
    | This defines how long a user must wait before requesting
    | a new OTP again.
    |
    */

    'cooldown' => 60,


    /*
    |--------------------------------------------------------------------------
    | OTP Code Length
    |--------------------------------------------------------------------------
    |
    | Define how many digits the OTP should have.
    |
    */

    'length' => 6,


    /*
    |--------------------------------------------------------------------------
    | OTP Numeric Range
    |--------------------------------------------------------------------------
    |
    | Defines the minimum and maximum range for OTP generation.
    |
    */

    'code_min' => 100000,
    'code_max' => 999999,


    /*
    |--------------------------------------------------------------------------
    | Enable Logging (For Development)
    |--------------------------------------------------------------------------
    |
    | If true, OTP will be logged in laravel.log file.
    | Disable in production.
    |
    */

    'log' => true,

];
```

---

## Step 6: Create Service Layer

File: `app/Services/OtpService.php`

```php
<?php

namespace App\Services;

use App\Models\Otp;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OtpService
{
    public function send($mobile, $type = null)
    {
        if (cache()->has('otp_cooldown_' . $mobile)) {
            throw new \Exception("Please wait before requesting OTP again");
        }
        $code = rand(config('otp.code_min'), config('otp.code_max'));

        $otp = Otp::create([
            'mobile' => $mobile,
            'code' => $code,
            'type' => $type,
            'tracking_code' => (string) Str::uuid(),
            'expires_at' => Carbon::now()->addMinutes(config('otp.expiry')),
        ]);

        cache()->put('otp_cooldown_' . $mobile, true, config('otp.cooldown'));

        event(new \App\Events\OtpPrepared($otp));

        return $otp;
    }
    public function verify($mobile, $code, $trackingCode)
    {
        $otp = Otp::where('mobile', $mobile)
            ->where('tracking_code', $trackingCode)
            ->latest()
            ->first();

        if (!$otp) return ['status' => false, 'message' => 'OTP not found'];

        if ($otp->expires_at < now()) return ['status' => false, 'message' => 'OTP expired'];

        if ($otp->attempts >= config('otp.max_attempts'))
            return ['status' => false, 'message' => 'Max attempts reached'];

        if ($otp->code != $code) {
            $otp->increment('attempts');
            return ['status' => false, 'message' => 'Invalid OTP'];
        }
        $otp->delete();

        return ['status' => true, 'message' => 'OTP verified successfully'];
    }
}
```

---

## Step 7: Create Event

```bash
php artisan make:event OtpPrepared
```

File: `app/Events/OtpPrepared.php`

```php
<?php

namespace App\Events;

use App\Models\Otp;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OtpPrepared
{
    use Dispatchable, SerializesModels;
    public Otp $otp;
    public function __construct(Otp $otp)
    {
        $this->otp = $otp;
    }
}
```

---

## Step 8: Create Listener

```bash
php artisan make:listener SendOtpNotification
```

File: `app/Listeners/SendOtpNotification.php`

```php
<?php

namespace App\Listeners;

use App\Events\OtpPrepared;

class SendOtpNotification
{
    public function handle(OtpPrepared $event): void
    {
        $otp = $event->otp;
        
        if (config('otp.log')) {
            \Log::info("OTP for {$otp->mobile}: {$otp->code}");
        }
    }
}
```

---

## Step 9: Register Event

File: `app/Providers/EventServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        \App\Events\OtpPrepared::class => [
            \App\Listeners\SendOtpNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
```

## Step 10: Create Middleware

```bash
php artisan make:middleware OtpRateLimiter
```

File: `app/Http/Middleware/OtpRateLimiter.php`

```php
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
```

---

## Step 11: Register Middleware 

File: `bootstrap/app.php`

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\OtpRateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
        //  Register custom middleware alias
        $middleware->alias([
            'otp.limit' => OtpRateLimiter::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })

    ->create();
```

---

## Step 12: Create Controller

```bash
php artisan make:controller OtpController
```

File: `app/Http/Controllers/OtpController.php`

```php
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
```

---

## Step 13: Create UI

File: `resources/views/otp.blade.php`

```blade
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
```

---

## Step 14: Routes

File: `routes/web.php`

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OtpController;

Route::get('/', function () {
    return view('otp');
});

Route::middleware(['otp.limit'])->group(function () {
    Route::post('/send-otp', [OtpController::class, 'send'])->name('send.otp');
});

Route::post('/verify-otp', [OtpController::class, 'verify'])->name('verify.otp');
```

---

## Step 15: Run Project

```bash
php artisan serve
```
Open:

```bash
http://127.0.0.1:8000
```

---

## How It Works

1. User enters mobile number and requests OTP
2. OTP is generated and stored in database
3. Event is triggered and OTP is logged (for development)
4. User enters OTP and tracking code
5. System verifies OTP with validation checks:
   - Expiry check
   - Attempt limit check
   - Code match
6. If valid → OTP verified successfully
7. If invalid → proper error message shown

---

## Output

<img src="screenshots/Screenshot 2026-03-30 110222.png" width="1000">

<img src="screenshots/Screenshot 2026-03-30 110233.png" width="1000">

<img src="screenshots/Screenshot 2026-03-30 110309.png" width="1000">

<img src="screenshots/Screenshot 2026-03-30 110349.png" width="1000">

<img src="screenshots/Screenshot 2026-03-30 110359.png" width="1000">

---

## Project Structure

```
PHP_Laravel12_OTP_Manager/
│
├── app/
│   ├── Events/
│   │   └── OtpPrepared.php
│   │
│   ├── Listeners/
│   │   └── SendOtpNotification.php
│   │
│   ├── Services/
│   │   └── OtpService.php
│   │
│   ├── Models/
│   │   └── Otp.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── OtpController.php
│   │   │
│   │   └── Middleware/
│   │       └── OtpRateLimiter.php
│   │
│   └── Providers/
│       └── EventServiceProvider.php
│
├── bootstrap/
│   └── app.php
│
├── config/
│   └── otp.php
│
├── database/
│   └── migrations/
│       └── xxxx_xx_xx_create_otps_table.php
│
├── resources/
│   └── views/
│       └── otp.blade.php
│
├── routes/
│   └── web.php
│
├── storage/
│   └── logs/
│       └── laravel.log
│
├── .env
├── artisan
└── composer.json
```

---

Your PHP_Laravel12_OTP_Manager Project is now ready!


