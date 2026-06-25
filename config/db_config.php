<?php
// VERSAO SISTEMA: 2.0.0 - 2026-05-26
function getDbPdo($database = null) {
    $host = getenv('DB_HOST') ?: '10.15.61.169';
    $name = $database ?: (getenv('DB_NAME') ?: 'controle');
    $user = getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat');
    $pass = getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256');

    $pdo = new PDO(
        "mysql:host=$host;dbname=$name;charset=utf8mb4",
        $user,
        $pass,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        )
    );
    return $pdo;
}

function getDbCredentials() {
    return array(
        'host' => getenv('DB_HOST') ?: '10.15.61.169',
        'name' => getenv('DB_NAME') ?: 'controle',
        'user' => getenv('DB_USER') ?: (getenv('DB_USER') ?: 'controle_mat'),
        'pass' => getenv('DB_PASS') ?: (getenv('DB_PASS') ?: '375256'),
    );
}
