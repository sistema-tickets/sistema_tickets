<?php

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    if ($value === false) return $default;
    return match(strtolower($value)) {
        'true'  => true,
        'false' => false,
        'null'  => null,
        default => $value,
    };
}

function loadEnv(string $path): void
{
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

loadEnv(dirname(__DIR__, 2) . '/.env');

$isProduction = env('APP_ENV') === 'production';

if ($isProduction) {
    ini_set('display_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

$sessionName     = env('SESSION_NAME', 'tickets_session');
$sessionLifetime = (int) env('SESSION_LIFETIME', 7200);

session_name($sessionName);
session_set_cookie_params([
    'lifetime' => $sessionLifetime,
    'path'     => '/',
    'secure'   => $isProduction,
    'httponly' => true,
    'samesite' => 'Lax',
]);
