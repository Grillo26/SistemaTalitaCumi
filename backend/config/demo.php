<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Usuarios demo — solo desarrollo
    |--------------------------------------------------------------------------
    |
    | Este archivo lo consume SeguridadDemoUsersSeeder. No se ejecuta desde
    | DatabaseSeeder: los usuarios demo se crean explícitamente y el seeder
    | se bloquea en producción.
    |
    | La contraseña es compartida por los tres usuarios demo. Debe cumplir la
    | política vigente; `Password12` cumple la política inicial (10 caracteres,
    | mayúscula, minúscula y número).
    |
    */

    'password' => env('DEMO_PASSWORD') ?: 'Password12',

    'force_password_change' => (bool) env('DEMO_FORCE_PASSWORD_CHANGE', false),

    'branch' => [
        'codigo' => 'DEMO-CENTRAL',
        'nombre' => 'Sucursal Demo',
    ],

    'users' => [
        'superadmin' => [
            'name' => 'Superadmin Demo',
            'email' => env('DEMO_SUPERADMIN_EMAIL') ?: 'superadmin.demo@talitacumi.test',
            'username' => env('DEMO_SUPERADMIN_USERNAME') ?: 'superadmin.demo',
            'documento' => env('DEMO_SUPERADMIN_DOCUMENTO') ?: '90000001',
            'role' => 'superadmin',
        ],
        'administracion' => [
            'name' => 'Administración Demo',
            'email' => env('DEMO_ADMIN_EMAIL') ?: 'admin.demo@talitacumi.test',
            'username' => env('DEMO_ADMIN_USERNAME') ?: 'admin.demo',
            'documento' => env('DEMO_ADMIN_DOCUMENTO') ?: '90000002',
            'role' => 'administracion',
        ],
        'recepcion' => [
            'name' => 'Recepción Demo',
            'email' => env('DEMO_RECEPCION_EMAIL') ?: 'recepcion.demo@talitacumi.test',
            'username' => env('DEMO_RECEPCION_USERNAME') ?: 'recepcion.demo',
            'documento' => env('DEMO_RECEPCION_DOCUMENTO') ?: '90000003',
            'role' => 'recepcion',
        ],
    ],

];
