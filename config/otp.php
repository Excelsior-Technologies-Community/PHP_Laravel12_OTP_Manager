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