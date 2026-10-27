<?php

$isProduction = env('APP_ENV') === 'production';

return [

    /*
    |--------------------------------------------------------------------------
    | Demo account passwords
    |--------------------------------------------------------------------------
    |
    | Passwords of the demo accounts the seeder creates (admin@example.com,
    | teacher@example.com, student@example.com...). The fallbacks below are
    | public in the repository, so they only apply outside production. On a
    | production server set DEMO_*_PASSWORD; without them the seeder gives the
    | demo accounts random passwords nobody knows.
    |
    */

    'admin_password' => env('DEMO_ADMIN_PASSWORD') ?: ($isProduction ? null : 'Admin@123'),

    'teacher_password' => env('DEMO_TEACHER_PASSWORD') ?: ($isProduction ? null : 'Teacher@123'),

    'student_password' => env('DEMO_STUDENT_PASSWORD') ?: ($isProduction ? null : 'Student@123'),

    /*
    | Passwords that are public in the repository. On deploy, demo accounts
    | still using one of them get DEMO_*_PASSWORD or a random password.
    */

    'public_passwords' => [
        'admin' => ['Admin@123'],
        'teacher' => ['Teacher@123'],
        'student' => ['Student@123', 'Teacher@123', 'Password@123'],
    ],

    /*
    | Email domains of the demo accounts (the seeded ones and the fake learners).
    */

    'email_domains' => ['example.com', 'example.org', 'example.net'],

];
