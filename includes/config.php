<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$host = getenv('DB_HOST') ?: 'db';
$db   = getenv('DB_NAME') ?: 'avanza_centrovet';
$user = getenv('DB_USER') ?: 'avanza';
$pass = getenv('DB_PASS') ?: 'avanza123';
$port = getenv('DB_PORT') ?: '3306';

try {

    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

} catch (PDOException $e) {

    die('Error de conexión a la base de datos.');
}

define('APP_NAME', 'Avanza.CentroVet');

define(
    'APP_URL',
    getenv('APP_URL') ?: 'http://localhost:8080'
);