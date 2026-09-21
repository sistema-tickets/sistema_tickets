<?php

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../helpers/Response.php';

class Auth
{
    public static function check(): array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (empty($_SESSION['usuario'])) {
            Response::unauthorized('Sesión expirada. Por favor inicia sesión.');
        }
        return $_SESSION['usuario'];
    }

    public static function checkAdmin(): array
    {
        $usuario = self::check();
        if (!in_array((int)$usuario['rol_id'], [ROL_ADMIN, ROL_SUPERADMIN])) {
            Response::forbidden('Se requiere rol de administrador.');
        }
        return $usuario;
    }

    public static function checkSuperAdmin(): array
    {
        $usuario = self::check();
        if ((int)$usuario['rol_id'] !== ROL_SUPERADMIN) {
            Response::forbidden('Se requiere rol de superadministrador.');
        }
        return $usuario;
    }

    public static function login(array $usuario): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id'               => $usuario['id'],
            'nombre'           => $usuario['nombre'],
            'email'            => $usuario['email'],
            'rol_id'           => $usuario['rol_id'],
            'sitio_id'         => $usuario['sitio_id'],
            'linea_negocio_id' => $usuario['linea_negocio_id'] ?? null,
        ];
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION = [];
        session_destroy();
    }

    public static function usuario(): ?array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['usuario'] ?? null;
    }
}
