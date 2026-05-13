<?php

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';

class AuthController
{
    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    public function login(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $v = new Validator();
        $v->required('email', $body['email'] ?? null)
          ->email('email', $body['email'] ?? null)
          ->required('password', $body['password'] ?? null);

        if ($v->fails()) {
            Response::error('Datos inválidos', 422, $v->errors());
        }

        $usuario = $this->model->findByEmail($body['email']);

        if (!$usuario || !password_verify($body['password'], $usuario['password'])) {
            Response::error('Credenciales incorrectas', 401);
        }

        Auth::login($usuario);
        $this->model->updateLastLogin($usuario['id']);

        Response::success([
            'id'     => $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email'  => $usuario['email'],
            'rol_id' => $usuario['rol_id'],
        ], 'Sesión iniciada');
    }

    public function register(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $v = new Validator();
        $v->required('nombre', $body['nombre'] ?? null)
          ->required('email', $body['email'] ?? null)
          ->email('email', $body['email'] ?? null)
          ->required('password', $body['password'] ?? null)
          ->minLength('password', $body['password'] ?? null, 8);

        if ($v->fails()) {
            Response::error('Datos inválidos', 422, $v->errors());
        }

        if ($this->model->findByEmail($body['email'])) {
            Response::error('El email ya está registrado', 409);
        }

        $data = [
            'nombre'   => $body['nombre'],
            'email'    => $body['email'],
            'password' => $body['password'],
            'rol_id'   => ROL_USUARIO,
        ];

        $id = $this->model->create($data);
        Response::success(['id' => $id], 'Registro exitoso', 201);
    }

    public function logout(): void
    {
        Auth::logout();
        Response::success(null, 'Sesión cerrada');
    }

    public function me(): void
    {
        $usuario = Auth::check();
        $data = $this->model->findById($usuario['id']);
        unset($data['password']);
        Response::success($data);
    }
}
