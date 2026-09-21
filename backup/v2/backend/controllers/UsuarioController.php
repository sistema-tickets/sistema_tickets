<?php

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';

class UsuarioController
{
    private UsuarioModel $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    public function index(): void
    {
        Auth::checkSuperAdmin();
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = min(50, max(1, (int)($_GET['limit'] ?? 20)));
        $usuarios = $this->model->list($page, $limit);
        $total    = $this->model->count();

        Response::success([
            'usuarios'    => $usuarios,
            'total'       => $total,
            'page'        => $page,
            'total_pages' => ceil($total / $limit),
        ]);
    }

    public function store(): void
    {
        Auth::checkSuperAdmin();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $v = new Validator();
        $v->required('nombre', $body['nombre'] ?? null)
          ->required('email', $body['email'] ?? null)
          ->email('email', $body['email'] ?? null)
          ->required('password', $body['password'] ?? null)
          ->minLength('password', $body['password'] ?? null, 8)
          ->required('rol_id', $body['rol_id'] ?? null);

        if ($v->fails()) {
            Response::error('Datos inválidos', 422, $v->errors());
        }

        if ($this->model->findByEmail($body['email'])) {
            Response::error('El email ya está registrado', 409);
        }

        $id = $this->model->create($body);
        Response::success(['id' => $id], 'Usuario creado', 201);
    }

    public function update(int $id): void
    {
        Auth::checkSuperAdmin();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $ok   = $this->model->update($id, $body);
        $ok ? Response::success(null, 'Usuario actualizado') : Response::error('No se pudo actualizar', 500);
    }

    public function destroy(int $id): void
    {
        Auth::checkSuperAdmin();
        $ok = $this->model->update($id, ['activo' => 0]);
        $ok ? Response::success(null, 'Usuario desactivado') : Response::error('No se pudo desactivar', 500);
    }
}
