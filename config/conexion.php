<?php
// config/conexion.php · Crea la conexión PDO a partir de config/env.php

declare(strict_types=1);

function conectar(): PDO
{
    $env = require __DIR__ . '/env.php';

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $env['host'], $env['base']);

    return new PDO($dsn, $env['usuario'], $env['clave'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // todo fallo SQL lanza PDOException
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,                  // sentencias preparadas reales
    ]);
}
