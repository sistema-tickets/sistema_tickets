<?php

class Cors
{
    public static function handle(): void
    {
        $allowedOrigin = env('APP_URL', 'http://localhost');

        header("Access-Control-Allow-Origin: $allowedOrigin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }
}
