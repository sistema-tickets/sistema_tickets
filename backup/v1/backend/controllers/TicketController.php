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
        $usuario = Auth::check();
        $page    = max(1, (int)($_GET['page']  ?? 1));
        $limit   = min(50, max(1, (int)($_GET['limit'] ?? 20)));

        $filters = [
            'estado_id'        => $_GET['estado_id']        ?? null,
            'prioridad_id'     => $_GET['prioridad_id']     ?? null,
            'tipo_solicitud_id'=> $_GET['tipo_solicitud_id']?? null,
            'busqueda'         => $_GET['busqueda']         ?? null,
        ];

        if ((int)$usuario['rol_id'] === ROL_USUARIO) {
            $filters['usuario_id'] = $usuario['id'];
        }

        $tickets = $this->model->list($filters, $page, $limit);
        $total   = $this->model->countFiltered($filters);

        Response::success([
            'tickets'     => $tickets,
            'total'       => $total,
            'page'        => $page,
            'total_pages' => ceil($total / $limit),
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
        $isAdmin = in_array((int)$usuario['rol_id'], [ROL_ADMIN, ROL_SUPERADMIN]);

        $v = new Validator();
        $v->required('descripcion', $body['descripcion'] ?? null);

        if ($v->fails()) {
            Response::error('Datos inválidos', 422, $v->errors());
        }

        // Admin puede crear a nombre de otro usuario
        $body['usuario_id'] = ($isAdmin && !empty($body['usuario_id']))
            ? (int)$body['usuario_id']
            : (int)$usuario['id'];

        $body['sitio_id'] = $body['sitio_id'] ?? $usuario['sitio_id'] ?? null;

        // Solo admin puede fijar admin_id y estado inicial
        if (!$isAdmin) {
            unset($body['admin_id'], $body['estado_id']);
        }

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

        $prioridadId = isset($body['prioridad_id']) ? (int)$body['prioridad_id'] : null;
        $ok = $this->model->updateEstado($id, (int)$body['estado_id'], $usuario['id'], $body['nota'] ?? null, $prioridadId);
        $ok ? Response::success(null, 'Estado actualizado') : Response::error('No se pudo actualizar', 500);
    }

    public function asignar(int $id): void
    {
        $usuario = Auth::checkAdmin();
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];

        $adminId = (int)($body['admin_id'] ?? $usuario['id']);
        $ok = $this->model->asignar($id, $adminId, $usuario['id'], $body['motivo'] ?? null);
        $ok ? Response::success(null, 'Ticket asignado') : Response::error('No se pudo asignar', 500);
    }

    public function stats(): void
    {
        Auth::checkAdmin();
        Response::success($this->model->statsResumen());
    }

    public function responder(int $id): void
    {
        $usuario = Auth::check();
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];

        $v = new Validator();
        $v->required('contenido', $body['contenido'] ?? null);
        if ($v->fails()) Response::error('Datos inválidos', 422, $v->errors());

        $ticket = $this->model->findById($id);
        if (!$ticket) Response::notFound('Ticket no encontrado');

        $respId = $this->model->crearRespuesta($id, $usuario['id'], $body['contenido']);
        Response::success(['id' => $respId], 'Respuesta guardada', 201);
    }

    public function updateAsunto(int $id): void
    {
        Auth::checkAdmin();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $asuntoId = isset($body['asunto_id']) ? (int)$body['asunto_id'] : null;
        if ($asuntoId === null) {
            Response::error('asunto_id es requerido', 422);
        }

        $ok = $this->model->updateAsunto($id, $asuntoId);
        $ok ? Response::success(null, 'Asunto actualizado') : Response::error('No se pudo actualizar el asunto', 500);
    }

    public function timeline(int $id): void
    {
        Auth::check();
        $data = $this->model->getTimeline($id);
        Response::success($data);
    }
}
