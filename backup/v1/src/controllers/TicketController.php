<?php

require_once __DIR__ . '/../models/TicketModel.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Validator.php';
require_once __DIR__ . '/../config/constants.php';

class TicketController
{
    private TicketModel $model;

    public function __construct()
    {
        $this->model = new TicketModel();
    }

    public function index(): void
    {
        $usuario  = Auth::check();
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $limit    = min(50, max(1, (int)($_GET['limit'] ?? 20)));

        $filters = [
            'estado_id'    => $_GET['estado_id']    ?? null,
            'prioridad_id' => $_GET['prioridad_id'] ?? null,
            'busqueda'     => $_GET['busqueda']      ?? null,
        ];

        // Los usuarios solo ven sus propios tickets
        if ((int)$usuario['rol_id'] === ROL_USUARIO) {
            $filters['creado_por'] = $usuario['id'];
        }

        $tickets = $this->model->list($filters, $page, $limit);
        $total   = $this->model->countFiltered($filters);

        Response::success([
            'tickets'    => $tickets,
            'total'      => $total,
            'page'       => $page,
            'total_pages'=> ceil($total / $limit),
        ]);
    }

    public function show(int $id): void
    {
        Auth::check();
        $ticket = $this->model->findById($id);
        if (!$ticket) Response::notFound('Ticket no encontrado');
        Response::success($ticket);
    }

    public function store(): void
    {
        $usuario = Auth::check();
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];

        $v = new Validator();
        $v->required('titulo', $body['titulo'] ?? null)
          ->maxLength('titulo', $body['titulo'] ?? null, 255)
          ->required('descripcion', $body['descripcion'] ?? null);

        if ($v->fails()) {
            Response::error('Datos inválidos', 422, $v->errors());
        }

        $body['creado_por'] = $usuario['id'];
        $body['sitio_id']   = $body['sitio_id'] ?? $usuario['sitio_id'];

        $id = $this->model->create($body);
        Response::success(['id' => $id], 'Ticket creado', 201);
    }

    public function updateEstado(int $id): void
    {
        $usuario = Auth::checkAdmin();
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];

        $v = new Validator();
        $v->required('estado_id', $body['estado_id'] ?? null)
          ->inArray('estado_id', $body['estado_id'] ?? null, range(1, 9));

        if ($v->fails()) {
            Response::error('Datos inválidos', 422, $v->errors());
        }

        $ok = $this->model->updateEstado($id, (int)$body['estado_id'], $usuario['id'], $body['comentario'] ?? null);
        $ok ? Response::success(null, 'Estado actualizado') : Response::error('No se pudo actualizar', 500);
    }

    public function asignar(int $id): void
    {
        $usuario = Auth::checkAdmin();
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];

        $ok = $this->model->asignar($id, (int)($body['admin_id'] ?? $usuario['id']), $body['comentario'] ?? null);
        $ok ? Response::success(null, 'Ticket asignado') : Response::error('No se pudo asignar', 500);
    }

    public function stats(): void
    {
        Auth::checkAdmin();
        Response::success($this->model->statsResumen());
    }
}
