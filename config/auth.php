<?php

return [

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'karyawan' => [
            'driver' => 'session',
            'provider' => 'karyawans',
        ],

        'user' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        // Guard untuk login dari view v_login
        'v_login' => [
            'driver' => 'session',
            'provider' => 'vlogin_users',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        'karyawans' => [
            'driver' => 'eloquent',
            'model' => App\Models\Karyawan::class,
        ],

        // Provider untuk guard v_login
        'vlogin_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\VLogin::class,
        ],

        // Jika kamu ingin pakai driver database langsung (opsional)
        // 'vlogin_users' => [
        //     'driver' => 'database',
        //     'table' => 'v_login',
        // ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_resets',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,

];
