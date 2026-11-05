<?php

declare(strict_types=1);

return [
    'name' => (string) env('APP_NAME', 'Sistema Académico UMG'),
    'short_name' => 'SIA · UMG',
    'institution' => 'Universidad Mariano Gálvez de Guatemala',
    'env' => (string) env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'timezone' => 'America/Guatemala',
    'version' => '1.0.0',
    'author' => 'Samuel Isaac Guzman Citalan',

    // Muestra las credenciales de demostración en la pantalla de login (solo para la demo).
    'show_demo_credentials' => (bool) env('APP_SHOW_DEMO_CREDENTIALS', false),

    'per_page' => 10,

    // Nota mínima de aprobación (escala 0-100).
    'nota_aprobacion' => 61,

    'session' => [
        'name' => 'UMG_SIA_SESSID',
        'idle_timeout' => (int) env('SESSION_IDLE_TIMEOUT', 1800), // segundos
    ],

    'login' => [
        'max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'lock_minutes' => (int) env('LOGIN_LOCK_MINUTES', 15),
    ],
];
