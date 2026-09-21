<?php

require_once __DIR__ . '/../models/DashboardModel.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../helpers/Response.php';

class DashboardController
{
    private DashboardModel $model;

    public function __construct()
    {
        $this->model = new DashboardModel();
    }

    public function metrics(): void
    {
        Auth::checkAdmin();
        $data = $this->model->metrics();
        Response::success($data);
    }

    public function pending(): void
    {
        Auth::checkAdmin();
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $limit   = min(50, max(1, (int)($_GET['limit'] ?? 20)));

        $filters = [
            'estado_id'    => $_GET['estado_id']    ?? null,
            'prioridad_id' => $_GET['prioridad_id'] ?? null,
            'busqueda'     => $_GET['busqueda']      ?? null,
            'sla_estado'   => $_GET['sla_estado']    ?? null,
        ];

        $result = $this->model->pending($filters, $page, $limit);
        Response::success($result);
    }
}
