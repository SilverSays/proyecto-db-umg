<?php

declare(strict_types=1);

/*
 * Conexión a Oracle (Easy Connect: //host:puerto/servicio).
 *   Oracle XE 21c  -> servicio XEPDB1
 *   Oracle 23ai    -> servicio FREEPDB1
 */
return [
    'host' => (string) env('DB_HOST', 'localhost'),
    'port' => (int) env('DB_PORT', 1521),
    'service' => (string) env('DB_SERVICE', 'XEPDB1'),
    'user' => (string) env('DB_USERNAME', 'umg_academico'),
    'password' => (string) env('DB_PASSWORD', ''),
    'charset' => 'AL32UTF8',
];
