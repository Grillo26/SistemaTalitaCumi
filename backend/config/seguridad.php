<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seguridad — módulo 02
    |--------------------------------------------------------------------------
    |
    | `forzar_https` se activa en producción detrás de TLS. En desarrollo
    | queda en false para no romper http://localhost:8080.
    |
    | `rutas_sin_cambio_password` son las únicas rutas API que un usuario
    | con contraseña temporal puede usar antes de cambiarla (RF-A13).
    |
    */

    'forzar_https' => env('FORCE_HTTPS', false),

    'rutas_sin_cambio_password' => [
        'api/auth/me',
        'api/auth/logout',
        'api/auth/cambiar-password',
    ],

];
