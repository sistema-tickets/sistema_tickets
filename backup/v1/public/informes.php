<?php
require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/config/constants.php';
require_once __DIR__ . '/../backend/middleware/Auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$usuario = Auth::usuario();
if (!$usuario || (int)$usuario['rol_id'] !== ROL_ADMIN) {
    header('Location: /sistema_tickets/public/index.php');
    exit;
}

$db     = getDB();
$nombre = $usuario['nombre'];

// ── Filtros ───────────────────────────────────────────────────────────────────
$tipoId      = isset($_GET['tipo_solicitud_id']) ? (int)$_GET['tipo_solicitud_id'] : 0;
$prioridadId = isset($_GET['prioridad_id'])      ? (int)$_GET['prioridad_id']      : 0;
$periodo     = in_array((int)($_GET['periodo'] ?? 90), [30, 60, 90, 180]) ? (int)($_GET['periodo'] ?? 90) : 90;

$where  = ['1=1'];
$params = [];
if ($tipoId)      { $where[] = 't.tipo_solicitud_id = ?'; $params[] = $tipoId; }
if ($prioridadId) { $where[] = 't.prioridad_id = ?';      $params[] = $prioridadId; }
$ws = implode(' AND ', $where);

// ── KPIs ──────────────────────────────────────────────────────────────────────
$st = $db->prepare("SELECT COUNT(*) FROM tickets t WHERE $ws");
$st->execute($params); $total = (int)$st->fetchColumn();

$st = $db->prepare("SELECT COUNT(*) FROM tickets t JOIN estados e ON e.id = t.estado_id WHERE $ws AND e.nombre NOT IN ('Atendido','Cerrado','Rechazado')");
$st->execute($params); $activos = (int)$st->fetchColumn();

$st = $db->prepare("SELECT COUNT(*) FROM tickets t JOIN estados e ON e.id = t.estado_id WHERE $ws AND t.admin_id IS NULL AND e.nombre NOT IN ('Atendido','Cerrado','Rechazado')");
$st->execute($params); $sinAsignar = (int)$st->fetchColumn();

$st = $db->prepare("SELECT COUNT(*) FROM tickets t JOIN estados e ON e.id = t.estado_id WHERE $ws AND e.nombre IN ('Atendido','Cerrado')");
$st->execute($params); $cerrados = (int)$st->fetchColumn();

$st = $db->prepare("SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at)) / 60, 1) FROM tickets t WHERE $ws AND t.estado_id IN (7,8) AND t.closed_at IS NOT NULL");
$st->execute($params);
$raw = $st->fetchColumn();
$promCierre = $raw !== null && $raw !== false ? (float)$raw : null;

$st = $db->prepare("SELECT COUNT(*) FROM tickets t LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1 WHERE $ws AND t.estado_id NOT IN (8,9) AND sc.horas_resolucion IS NOT NULL AND TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) > (sc.horas_resolucion * 60)");
$st->execute($params); $vencidosActivos = (int)$st->fetchColumn();

// SLA global (tickets cerrados/atendidos histórico)
$slaRow    = $db->query("SELECT COUNT(*) AS total, SUM(CASE WHEN TIMESTAMPDIFF(HOUR, t.created_at, t.closed_at) <= sc.horas_resolucion THEN 1 ELSE 0 END) AS dentro FROM tickets t JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1 WHERE t.estado_id IN (7,8) AND t.closed_at IS NOT NULL")->fetch();
$totalSLA  = (int)($slaRow['total'] ?? 0);
$dentroSLA = (int)($slaRow['dentro'] ?? 0);
$pctSLA    = $totalSLA > 0 ? round($dentroSLA / $totalSLA * 100) : null;
$slaColor  = $pctSLA !== null ? ($pctSLA >= 80 ? '#1a7a4a' : ($pctSLA >= 60 ? '#e69c18' : '#c9310f')) : '#888';

// ── Chart: Tendencia ──────────────────────────────────────────────────────────
$st = $db->prepare(
    "SELECT DATE_FORMAT(fecha, '%Y-%m-%d') AS fecha, SUM(creados) AS creados, SUM(cerrados) AS cerrados
     FROM (
         SELECT DATE(t.created_at) AS fecha, COUNT(*) AS creados, 0 AS cerrados
         FROM tickets t WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(t.created_at)
         UNION ALL
         SELECT DATE(t.closed_at) AS fecha, 0 AS creados, COUNT(*) AS cerrados
         FROM tickets t WHERE t.closed_at IS NOT NULL AND t.closed_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
         GROUP BY DATE(t.closed_at)
     ) subq GROUP BY fecha ORDER BY fecha ASC"
);
$st->execute([$periodo, $periodo]);
$rowsTend    = $st->fetchAll();
$tendLabels  = array_column($rowsTend, 'fecha');
$tendCreados = array_map('intval', array_column($rowsTend, 'creados'));
$tendCerr    = array_map('intval', array_column($rowsTend, 'cerrados'));

// ── Chart: Estado ─────────────────────────────────────────────────────────────
$st = $db->prepare("SELECT e.nombre AS estado, e.id AS estado_id, COUNT(t.id) AS total FROM tickets t JOIN estados e ON e.id = t.estado_id WHERE $ws GROUP BY e.id, e.nombre ORDER BY total DESC");
$st->execute($params);
$rowsEst   = $st->fetchAll();
$estLabels = array_column($rowsEst, 'estado');
$estData   = array_map('intval', array_column($rowsEst, 'total'));
$estIds    = array_map('intval', array_column($rowsEst, 'estado_id'));

// ── Chart: Prioridad ──────────────────────────────────────────────────────────
$st = $db->prepare("SELECT p.nombre AS prioridad, COUNT(t.id) AS total FROM tickets t JOIN prioridades p ON p.id = t.prioridad_id WHERE $ws GROUP BY p.id, p.nombre, p.orden ORDER BY p.orden");
$st->execute($params);
$rowsPri   = $st->fetchAll();
$priLabels = array_column($rowsPri, 'prioridad');
$priData   = array_map('intval', array_column($rowsPri, 'total'));

// ── Chart: Tipo de Solicitud ──────────────────────────────────────────────────
$st = $db->prepare("SELECT ts.nombre AS tipo, COUNT(t.id) AS total FROM tickets t JOIN tipos_solicitud ts ON ts.id = t.tipo_solicitud_id WHERE $ws GROUP BY ts.id, ts.nombre ORDER BY total DESC");
$st->execute($params);
$rowsTipoC   = $st->fetchAll();
$tipoClabels = array_column($rowsTipoC, 'tipo');
$tipoCdata   = array_map('intval', array_column($rowsTipoC, 'total'));

// ── Chart: SLA por Prioridad ──────────────────────────────────────────────────
$rowsSla = $db->query(
    "SELECT p.nombre AS prioridad, COUNT(t.id) AS total,
            SUM(CASE WHEN TIMESTAMPDIFF(MINUTE, t.created_at, t.closed_at) <= (COALESCE(sc.horas_resolucion, 48) * 60) THEN 1 ELSE 0 END) AS dentro_sla
     FROM tickets t
     JOIN prioridades p ON p.id = t.prioridad_id
     LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1
     WHERE t.estado_id IN (7,8) AND t.closed_at IS NOT NULL
     GROUP BY p.id, p.nombre, p.orden ORDER BY p.orden"
)->fetchAll();
$slaLabels = array_column($rowsSla, 'prioridad');
$slaDentro = array_map('intval', array_column($rowsSla, 'dentro_sla'));
$slaTotArr = array_map('intval', array_column($rowsSla, 'total'));
$slaFuera  = array_map(fn($t, $d) => max(0, $t - $d), $slaTotArr, $slaDentro);

// ── Chart: Top Asuntos ────────────────────────────────────────────────────────
$st = $db->prepare(
    "SELECT a.nombre AS asunto, COUNT(t.id) AS total
     FROM tickets t JOIN asuntos a ON a.id = t.asunto_id
     WHERE $ws GROUP BY a.id, a.nombre ORDER BY total DESC LIMIT 8"
);
$st->execute($params);
$rowsAsu   = $st->fetchAll();
$asuLabels = array_map(fn($s) => mb_strlen($s) > 30 ? mb_substr($s, 0, 27) . '…' : $s, array_column($rowsAsu, 'asunto'));
$asuData   = array_map('intval', array_column($rowsAsu, 'total'));

// ── Tabla: Demora promedio por estado ─────────────────────────────────────────
$st = $db->prepare(
    "SELECT e.nombre AS estado, e.id AS estado_id, COUNT(t.id) AS tickets,
            ROUND(AVG(TIMESTAMPDIFF(MINUTE, t.created_at, COALESCE(t.closed_at, NOW()))) / 60, 1) AS prom_h,
            ROUND(MAX(TIMESTAMPDIFF(MINUTE, t.created_at, COALESCE(t.closed_at, NOW()))) / 60, 1) AS max_h
     FROM tickets t JOIN estados e ON e.id = t.estado_id
     WHERE $ws GROUP BY e.id, e.nombre ORDER BY prom_h DESC"
);
$st->execute($params);
$rowsTabla = $st->fetchAll();
$maxHoras  = max(array_column($rowsTabla, 'max_h') ?: [1]);

// ── Catálogos ──────────────────────────────────────────────────────────────────
$tipos       = $db->query("SELECT id, nombre FROM tipos_solicitud ORDER BY nombre")->fetchAll();
$estados     = $db->query("SELECT id, nombre FROM estados ORDER BY id")->fetchAll();
$admins      = $db->query("SELECT id, nombre FROM usuarios WHERE rol_id = 2 AND activo = 1 ORDER BY nombre")->fetchAll();
$prioridades = $db->query("SELECT id, nombre FROM prioridades ORDER BY orden")->fetchAll();

function estadoBadgeInf(int $id): string {
    return match($id) {
        2,5     => 'est-proceso',
        4,7     => 'est-resuelto',
        1,3,6   => 'est-pendiente',
        8,9     => 'est-cancelado',
        default => 'est-pendiente',
    };
}
function estadoColorInf(int $id): string {
    return match($id) {
        1 => '#c9310f', 2 => '#1a7a4a', 3 => '#e69c18', 4 => '#1a5a9a',
        5 => '#7c3aed', 6 => '#e91e8c', 7 => '#0f766e', 8 => '#374151', 9 => '#b91c1c',
        default => '#888',
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas — Sistema de Tickets</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
        .inf-content { flex:1; min-width:0; padding:22px 26px 48px; display:flex; flex-direction:column; gap:18px; overflow-y:auto; }

        /* Page header */
        .inf-page-hdr { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .inf-title    { font-size:21px; font-weight:700; color:var(--text-primary); letter-spacing:-.02em; margin:0; }
        .inf-badge    { font-size:11px; color:var(--text-secondary); background:#f1f3f6; border-radius:20px; padding:4px 12px; font-weight:500; }

        /* Filters */
        .inf-filters  { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        .inf-select {
            padding:8px 30px 8px 11px;
            border:1px solid var(--border);
            border-radius:8px;
            font-size:13px; font-weight:500;
            color:var(--text-primary);
            background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='7' viewBox='0 0 11 7'%3E%3Cpath d='M1 1l4.5 4.5L10 1' stroke='%236b7a95' stroke-width='1.6' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 9px center;
            appearance:none; cursor:pointer; outline:none;
            font-family:inherit; transition:border-color .15s;
        }
        .inf-select:focus { border-color:var(--sidebar-bg); }
        .inf-btn-apply {
            padding:8px 16px; background:var(--sidebar-bg); color:#fff;
            border:none; border-radius:8px; font-size:13px; font-weight:600;
            cursor:pointer; font-family:inherit; transition:opacity .15s;
        }
        .inf-btn-apply:hover { opacity:.86; }
        .inf-btn-clear { font-size:12px; color:var(--text-secondary); text-decoration:none; padding:8px 2px; }

        /* KPI grid */
        .inf-kpi-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:13px; }
        .inf-kpi-card {
            background:#fff; border:1px solid var(--border); border-radius:14px;
            padding:17px 18px; display:flex; flex-direction:column; gap:7px;
            box-shadow:var(--shadow-card);
        }
        .inf-kpi-row    { display:flex; align-items:center; justify-content:space-between; }
        .inf-kpi-label  { font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-secondary); }
        .inf-kpi-icon   { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:12px; }
        .inf-kpi-num    { font-size:30px; font-weight:700; color:var(--text-primary); line-height:1; font-family:'DM Mono',monospace; }
        .inf-kpi-sub    { font-size:11px; color:var(--text-secondary); }
        .inf-kpi-prog   { height:3px; border-radius:2px; background:#f0f0f0; overflow:hidden; }
        .inf-kpi-prog-f { height:100%; border-radius:2px; }

        .ki-blue   .inf-kpi-icon { background:#eff6ff; color:#3b82f6; }
        .ki-green  .inf-kpi-icon { background:#f0fdf4; color:#16a34a; }
        .ki-orange .inf-kpi-icon { background:#fff7ed; color:#ea580c; }
        .ki-teal   .inf-kpi-icon { background:#f0fdfa; color:#0d9488; }
        .ki-red    .inf-kpi-icon { background:#fef2f2; color:#dc2626; }
        .ki-purple .inf-kpi-icon { background:#faf5ff; color:#9333ea; }

        /* Chart cards */
        .inf-chart-card { background:#fff; border:1px solid var(--border); border-radius:14px; padding:18px 20px; box-shadow:var(--shadow-card); }
        .inf-ctitle { font-size:13px; font-weight:700; color:var(--text-primary); margin-bottom:14px; display:flex; align-items:center; justify-content:space-between; }
        .inf-ctitle-sub { font-size:11px; font-weight:500; color:var(--text-secondary); }
        .inf-cbox-lg { height:280px; position:relative; }
        .inf-cbox-md { height:250px; position:relative; }
        .inf-cbox-sm { height:220px; position:relative; }
        .inf-cbox-xl { height:320px; position:relative; }

        .inf-grid2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .inf-grid3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }

        /* Table */
        .inf-table-card { background:#fff; border:1px solid var(--border); border-radius:14px; padding:18px 20px; box-shadow:var(--shadow-card); }
        .inf-table { width:100%; border-collapse:collapse; font-size:13px; }
        .inf-table th { padding:9px 12px; text-align:left; font-size:10px; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:var(--text-secondary); border-bottom:1px solid var(--border); background:#f8f9fb; }
        .inf-table td { padding:10px 12px; border-bottom:1px solid #f0f1f3; vertical-align:middle; }
        .inf-table tbody tr:last-child td { border-bottom:none; }
        .inf-table tbody tr:hover { background:#f8f9fb; }
        .dist-wrap { display:flex; align-items:center; gap:8px; }
        .dist-bg   { flex:1; background:#f0f0f0; border-radius:999px; height:6px; overflow:hidden; }
        .dist-fill { height:100%; border-radius:999px; }
        .dist-pct  { font-size:10px; font-weight:600; color:var(--text-secondary); min-width:32px; text-align:right; }

        @media(max-width:1100px) { .inf-grid3 { grid-template-columns:1fr 1fr; } }
        @media(max-width:860px)  { .inf-grid2,.inf-grid3 { grid-template-columns:1fr; } .inf-kpi-grid { grid-template-columns:repeat(2,1fr); } }
        @media(max-width:600px)  { .inf-kpi-grid { grid-template-columns:repeat(2,1fr); } .inf-content { padding:12px 14px 32px; } }
    </style>
</head>
<body class="dash-body">
<div class="dash-wrapper">

    <!-- HEADER -->
    <header class="dash-header">
        <button class="sidebar-toggle" id="sidebarToggle" title="Mostrar/Ocultar sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="dash-header-logo">
            <img src="assets/img/logo.jpg" alt="Logo">
        </div>
        <span class="dash-header-title">Sistema de Incidencia y Requerimiento</span>
        <div class="dash-header-right">
            <div class="user-wrap">
                <button class="user-btn" id="userBtn">
                    <i class="fa-solid fa-user"></i>
                    <span><?= htmlspecialchars($nombre) ?></span>
                    <i class="fa-solid fa-angle-down"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-dropdown-header">
                        <i class="fa-solid fa-user"></i>
                        <span><?= htmlspecialchars($nombre) ?></span>
                    </div>
                    <a href="logout.php" class="user-dropdown-item">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>Cerrar sesión</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="dash-body-inner">

        <!-- SIDEBAR -->
        <aside class="dash-sidebar" id="dashSidebar">
            <nav class="dash-nav">
                <a href="index.php" class="dash-nav-item">
                    <i class="fa-solid fa-inbox"></i>
                    <span>Total Tickets</span>
                </a>
                <div class="dd-wrap">
                    <button class="dash-nav-item" id="asignadosBtn">
                        <i class="fa-solid fa-user"></i>
                        <span>Asignados a</span>
                        <i class="fa-solid fa-angle-right nav-chevron dd-chevron"></i>
                    </button>
                    <div class="dd-dropdown" id="asignadosDD">
                        <?php foreach ($admins as $adm): ?>
                        <a class="dd-dropdown-item" href="index.php?asignado_id=<?= $adm['id'] ?>">
                            <?= htmlspecialchars($adm['nombre']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="dd-wrap">
                    <button class="dash-nav-item" id="estadoBtn">
                        <i class="fa-solid fa-clock"></i>
                        <span>Estado</span>
                        <i class="fa-solid fa-angle-right nav-chevron dd-chevron"></i>
                    </button>
                    <div class="dd-dropdown" id="estadoDD">
                        <?php foreach ($estados as $est): ?>
                        <a class="dd-dropdown-item" href="index.php?estado_id=<?= $est['id'] ?>">
                            <?= htmlspecialchars($est['nombre']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="dd-wrap">
                    <button class="dash-nav-item" id="prioridadBtn">
                        <i class="fa-solid fa-sort"></i>
                        <span>Prioridad</span>
                        <i class="fa-solid fa-angle-right nav-chevron dd-chevron"></i>
                    </button>
                    <div class="dd-dropdown" id="prioridadDD">
                        <?php foreach ($prioridades as $pri): ?>
                        <a class="dd-dropdown-item" href="index.php?prioridad_id=<?= $pri['id'] ?>">
                            <?= htmlspecialchars($pri['nombre']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="dd-wrap">
                    <button class="dash-nav-item" id="tipoBtn">
                        <i class="fa-solid fa-list-check"></i>
                        <span>Tipo de solicitud</span>
                        <i class="fa-solid fa-angle-right nav-chevron dd-chevron"></i>
                    </button>
                    <div class="dd-dropdown" id="tipoDD">
                        <?php foreach ($tipos as $tip): ?>
                        <a class="dd-dropdown-item" href="index.php?tipo_solicitud_id=<?= $tip['id'] ?>">
                            <?= htmlspecialchars($tip['nombre']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <a href="pages/tickets/crear.html" class="dash-nav-item">
                    <i class="fa-solid fa-plus"></i>
                    <span>Crear Ticket</span>
                </a>
                <a href="informes.php" class="dash-nav-item active">
                    <i class="fa-solid fa-chart-bar"></i>
                    <span>Métricas</span>
                    <i class="fa-solid fa-angle-right nav-chevron"></i>
                </a>
            </nav>
        </aside>

        <!-- CONTENT -->
        <div class="inf-content">

            <!-- Page header -->
            <div class="inf-page-hdr">
                <h1 class="inf-title">
                    <i class="fa-solid fa-chart-line" style="color:var(--sidebar-bg);margin-right:10px;font-size:18px"></i>
                    Métricas y Análisis
                </h1>
                <span class="inf-badge">
                    <i class="fa-regular fa-calendar" style="margin-right:5px"></i>
                    <?= date('d/m/Y H:i') ?>
                </span>
            </div>

            <!-- Filters -->
            <form method="GET" class="inf-filters">
                <select name="tipo_solicitud_id" class="inf-select">
                    <option value="">Tipo de solicitud</option>
                    <?php foreach ($tipos as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $tipoId === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select name="prioridad_id" class="inf-select">
                    <option value="">Todas las prioridades</option>
                    <?php foreach ($prioridades as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= $prioridadId === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select name="periodo" class="inf-select">
                    <option value="30"  <?= $periodo === 30  ? 'selected' : '' ?>>Tendencia: 30 días</option>
                    <option value="60"  <?= $periodo === 60  ? 'selected' : '' ?>>Tendencia: 60 días</option>
                    <option value="90"  <?= $periodo === 90  ? 'selected' : '' ?>>Tendencia: 90 días</option>
                    <option value="180" <?= $periodo === 180 ? 'selected' : '' ?>>Tendencia: 180 días</option>
                </select>

                <button type="submit" class="inf-btn-apply">
                    <i class="fa-solid fa-filter" style="margin-right:5px"></i>Aplicar
                </button>
                <?php if ($tipoId || $prioridadId || $periodo !== 90): ?>
                <a href="informes.php" class="inf-btn-clear">Limpiar</a>
                <?php endif; ?>
            </form>

            <!-- KPI Cards -->
            <div class="inf-kpi-grid">
                <!-- Total -->
                <div class="inf-kpi-card ki-blue">
                    <div class="inf-kpi-row">
                        <div class="inf-kpi-label">Total Tickets</div>
                        <div class="inf-kpi-icon"><i class="fa-solid fa-ticket"></i></div>
                    </div>
                    <div class="inf-kpi-num"><?= number_format($total) ?></div>
                    <div class="inf-kpi-sub"><?= $tipoId || $prioridadId ? 'con filtro activo' : 'histórico total' ?></div>
                </div>

                <!-- Activos -->
                <div class="inf-kpi-card ki-orange">
                    <div class="inf-kpi-row">
                        <div class="inf-kpi-label">Activos</div>
                        <div class="inf-kpi-icon"><i class="fa-solid fa-spinner"></i></div>
                    </div>
                    <div class="inf-kpi-num"><?= number_format($activos) ?></div>
                    <div class="inf-kpi-sub"><?= $total > 0 ? round($activos / $total * 100) . '% del total' : '—' ?></div>
                </div>

                <!-- Sin asignar -->
                <div class="inf-kpi-card ki-red">
                    <div class="inf-kpi-row">
                        <div class="inf-kpi-label">Sin Asignar</div>
                        <div class="inf-kpi-icon"><i class="fa-solid fa-user-slash"></i></div>
                    </div>
                    <div class="inf-kpi-num" style="color:<?= $sinAsignar > 0 ? '#dc2626' : 'inherit' ?>"><?= number_format($sinAsignar) ?></div>
                    <div class="inf-kpi-sub"><?= $activos > 0 ? round($sinAsignar / $activos * 100) . '% de activos' : '—' ?></div>
                </div>

                <!-- Cerrados -->
                <div class="inf-kpi-card ki-green">
                    <div class="inf-kpi-row">
                        <div class="inf-kpi-label">Cerrados / Atendidos</div>
                        <div class="inf-kpi-icon"><i class="fa-solid fa-circle-check"></i></div>
                    </div>
                    <div class="inf-kpi-num"><?= number_format($cerrados) ?></div>
                    <div class="inf-kpi-sub"><?= $total > 0 ? round($cerrados / $total * 100) . '% del total' : '—' ?></div>
                </div>

                <!-- SLA -->
                <div class="inf-kpi-card ki-teal">
                    <div class="inf-kpi-row">
                        <div class="inf-kpi-label">Cumplimiento SLA</div>
                        <div class="inf-kpi-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                    </div>
                    <div class="inf-kpi-num" style="color:<?= $slaColor ?>"><?= $pctSLA !== null ? $pctSLA . '%' : '—' ?></div>
                    <?php if ($pctSLA !== null): ?>
                    <div class="inf-kpi-prog"><div class="inf-kpi-prog-f" style="width:<?= $pctSLA ?>%;background:<?= $slaColor ?>"></div></div>
                    <div class="inf-kpi-sub"><?= number_format($dentroSLA) ?> / <?= number_format($totalSLA) ?> tickets</div>
                    <?php else: ?>
                    <div class="inf-kpi-sub">sin datos</div>
                    <?php endif; ?>
                </div>

                <!-- Vencidos -->
                <div class="inf-kpi-card ki-purple">
                    <div class="inf-kpi-row">
                        <div class="inf-kpi-label">Vencidos Activos</div>
                        <div class="inf-kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    </div>
                    <div class="inf-kpi-num" style="color:<?= $vencidosActivos > 0 ? '#dc2626' : 'inherit' ?>"><?= number_format($vencidosActivos) ?></div>
                    <div class="inf-kpi-sub">
                        <?php if ($promCierre !== null): ?>
                            Prom. cierre: <?= number_format($promCierre, 1) ?> h
                        <?php else: ?>
                            sin datos de cierre
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Chart: Tendencia -->
            <div class="inf-chart-card">
                <div class="inf-ctitle">
                    <span><i class="fa-solid fa-chart-line" style="margin-right:7px;color:var(--sidebar-bg)"></i>Tendencia de Actividad</span>
                    <span class="inf-ctitle-sub">Últimos <?= $periodo ?> días &mdash; creados vs cerrados</span>
                </div>
                <div class="inf-cbox-lg"><canvas id="chartTend"></canvas></div>
            </div>

            <!-- Charts row 1: Estado | Prioridad | Tipo -->
            <div class="inf-grid3">
                <div class="inf-chart-card">
                    <div class="inf-ctitle">
                        <span>Por Estado</span>
                        <span class="inf-ctitle-sub"><?= number_format(array_sum($estData)) ?> tickets</span>
                    </div>
                    <div class="inf-cbox-sm"><canvas id="chartEstado"></canvas></div>
                </div>
                <div class="inf-chart-card">
                    <div class="inf-ctitle">
                        <span>Por Prioridad</span>
                    </div>
                    <div class="inf-cbox-sm"><canvas id="chartPrioridad"></canvas></div>
                </div>
                <div class="inf-chart-card">
                    <div class="inf-ctitle">
                        <span>Tipo de Solicitud</span>
                    </div>
                    <div class="inf-cbox-sm"><canvas id="chartTipo"></canvas></div>
                </div>
            </div>

            <!-- Charts row 2: SLA | Top Asuntos -->
            <div class="inf-grid2">
                <div class="inf-chart-card">
                    <div class="inf-ctitle">
                        <span><i class="fa-solid fa-gauge-high" style="margin-right:6px;color:#1a7a4a"></i>SLA por Prioridad</span>
                        <span class="inf-ctitle-sub">tickets cerrados/atendidos</span>
                    </div>
                    <div class="inf-cbox-md"><canvas id="chartSla"></canvas></div>
                </div>
                <div class="inf-chart-card">
                    <div class="inf-ctitle">
                        <span><i class="fa-solid fa-ranking-star" style="margin-right:6px;color:#3b82f6"></i>Top Asuntos</span>
                        <span class="inf-ctitle-sub">por volumen</span>
                    </div>
                    <div class="inf-cbox-xl"><canvas id="chartAsuntos"></canvas></div>
                </div>
            </div>

            <!-- Tabla Demora -->
            <div class="inf-table-card">
                <div class="inf-ctitle" style="margin-bottom:14px">
                    <span><i class="fa-solid fa-table" style="margin-right:7px;color:var(--sidebar-bg)"></i>Demora Promedio por Estado</span>
                    <span class="inf-ctitle-sub">tiempo desde creación hasta cierre o ahora</span>
                </div>
                <table class="inf-table">
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Tickets</th>
                            <th>Tiempo promedio</th>
                            <th>Tiempo máximo</th>
                            <th style="min-width:160px">Distribución relativa</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rowsTabla as $row):
                            $pct   = $maxHoras > 0 ? min(100, round((float)$row['prom_h'] / $maxHoras * 100)) : 0;
                            $color = estadoColorInf((int)$row['estado_id']);
                        ?>
                        <tr>
                            <td>
                                <span class="estado-badge <?= estadoBadgeInf((int)$row['estado_id']) ?>">
                                    <?= htmlspecialchars($row['estado']) ?>
                                </span>
                            </td>
                            <td style="font-weight:600;font-family:'DM Mono',monospace"><?= number_format((int)$row['tickets']) ?></td>
                            <td style="font-family:'DM Mono',monospace"><?= $row['prom_h'] !== null ? number_format((float)$row['prom_h'], 1) . ' h' : '—' ?></td>
                            <td style="font-family:'DM Mono',monospace;color:var(--text-secondary)"><?= $row['max_h'] !== null ? number_format((float)$row['max_h'], 1) . ' h' : '—' ?></td>
                            <td>
                                <div class="dist-wrap">
                                    <div class="dist-bg">
                                        <div class="dist-fill" style="width:<?= $pct ?>%;background:<?= $color ?>"></div>
                                    </div>
                                    <span class="dist-pct"><?= $pct ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rowsTabla)): ?>
                        <tr><td colspan="5" style="text-align:center;color:var(--text-secondary);padding:28px">Sin datos para mostrar</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div><!-- /.inf-content -->
    </div><!-- /.dash-body-inner -->
</div><!-- /.dash-wrapper -->

<script>
(function () {
    // ── UI: User dropdown ────────────────────────────────────────────────────
    var userBtn = document.getElementById('userBtn');
    var userDD  = document.getElementById('userDropdown');
    if (userBtn) {
        userBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userDD.classList.toggle('open');
            userBtn.classList.toggle('open');
        });
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.user-wrap')) {
                userDD.classList.remove('open');
                userBtn.classList.remove('open');
            }
        });
    }

    // ── UI: Sidebar toggle ───────────────────────────────────────────────────
    var sidebar  = document.getElementById('dashSidebar');
    var togBtn   = document.getElementById('sidebarToggle');
    if (sidebar && togBtn) {
        if (localStorage.getItem('sidebar_collapsed') === 'true') sidebar.classList.add('collapsed');
        togBtn.addEventListener('click', function () {
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebar_collapsed', sidebar.classList.contains('collapsed'));
        });
    }

    // ── UI: Sidebar dropdowns ────────────────────────────────────────────────
    document.querySelectorAll('.dd-wrap').forEach(function (wrap) {
        var btn = wrap.querySelector('.dash-nav-item');
        var dd  = wrap.querySelector('.dd-dropdown');
        var ch  = wrap.querySelector('.dd-chevron');
        if (!btn || !dd) return;
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = dd.classList.contains('open');
            document.querySelectorAll('.dd-dropdown').forEach(function (d) { d.classList.remove('open'); });
            document.querySelectorAll('.dd-chevron').forEach(function (c) { c.classList.remove('open'); });
            if (!open) { dd.classList.add('open'); if (ch) ch.classList.add('open'); }
        });
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.dd-wrap')) {
            document.querySelectorAll('.dd-dropdown').forEach(function (d) { d.classList.remove('open'); });
            document.querySelectorAll('.dd-chevron').forEach(function (c) { c.classList.remove('open'); });
        }
    });

    // ── Chart.js defaults ────────────────────────────────────────────────────
    Chart.defaults.font.family = "'DM Sans', system-ui, sans-serif";
    Chart.defaults.font.size   = 12;

    var EST_COLORS = {1:'#c9310f',2:'#1a7a4a',3:'#e69c18',4:'#1a5a9a',5:'#7c3aed',6:'#e91e8c',7:'#0f766e',8:'#374151',9:'#b91c1c'};
    var PRI_COLORS = ['#22c55e','#f59e0b','#f97316','#ef4444'];
    var TIPO_COLORS = ['#3b82f6','#6366f1','#06b6d4','#10b981'];

    // ── Chart 1: Tendencia ───────────────────────────────────────────────────
    var ctxTend = document.getElementById('chartTend');
    if (ctxTend) {
        new Chart(ctxTend, {
            type: 'line',
            data: {
                labels: <?= json_encode($tendLabels) ?>,
                datasets: [
                    {
                        label: 'Creados',
                        data: <?= json_encode($tendCreados) ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59,130,246,0.10)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderWidth: 2,
                    },
                    {
                        label: 'Cerrados',
                        data: <?= json_encode($tendCerr) ?>,
                        borderColor: '#1a7a4a',
                        backgroundColor: 'rgba(26,122,74,0.10)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        position: 'top',
                        align: 'end',
                        labels: { boxWidth: 12, boxHeight: 12, padding: 16, usePointStyle: true, pointStyle: 'circle' }
                    },
                    tooltip: { padding: 10 }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 12, maxRotation: 0, font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { font: { size: 11 }, stepSize: 1 }
                    }
                }
            }
        });
    }

    // ── Chart 2: Estado (doughnut) ───────────────────────────────────────────
    var ctxEst = document.getElementById('chartEstado');
    if (ctxEst) {
        var estIds    = <?= json_encode($estIds) ?>;
        var estColors = estIds.map(function (id) { return EST_COLORS[id] || '#888'; });
        new Chart(ctxEst, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($estLabels) ?>,
                datasets: [{
                    data: <?= json_encode($estData) ?>,
                    backgroundColor: estColors,
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, padding: 10, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' } },
                    tooltip: { callbacks: { label: function (c) { var pct = Math.round(c.parsed / c.dataset.data.reduce(function(a,b){return a+b},0) * 100); return ' ' + c.label + ': ' + c.parsed.toLocaleString() + ' (' + pct + '%)'; } } }
                }
            }
        });
    }

    // ── Chart 3: Prioridad (horizontal bar) ──────────────────────────────────
    var ctxPri = document.getElementById('chartPrioridad');
    if (ctxPri) {
        new Chart(ctxPri, {
            type: 'bar',
            data: {
                labels: <?= json_encode($priLabels) ?>,
                datasets: [{
                    label: 'Tickets',
                    data: <?= json_encode($priData) ?>,
                    backgroundColor: PRI_COLORS.slice(0, <?= count($priLabels) ?>),
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (c) { return ' ' + c.parsed.x.toLocaleString() + ' tickets'; } } }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11 } } },
                    y: { grid: { display: false }, ticks: { font: { size: 12 } } }
                }
            }
        });
    }

    // ── Chart 4: Tipo Solicitud (doughnut) ───────────────────────────────────
    var ctxTipo = document.getElementById('chartTipo');
    if (ctxTipo) {
        new Chart(ctxTipo, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($tipoClabels) ?>,
                datasets: [{
                    data: <?= json_encode($tipoCdata) ?>,
                    backgroundColor: TIPO_COLORS.slice(0, <?= count($tipoClabels) ?>),
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverOffset: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, padding: 10, font: { size: 11 }, usePointStyle: true, pointStyle: 'circle' } },
                    tooltip: { callbacks: { label: function (c) { var pct = Math.round(c.parsed / c.dataset.data.reduce(function(a,b){return a+b},0) * 100); return ' ' + c.label + ': ' + c.parsed.toLocaleString() + ' (' + pct + '%)'; } } }
                }
            }
        });
    }

    // ── Chart 5: SLA por Prioridad (stacked horizontal bar) ──────────────────
    var ctxSla = document.getElementById('chartSla');
    if (ctxSla) {
        new Chart(ctxSla, {
            type: 'bar',
            data: {
                labels: <?= json_encode($slaLabels) ?>,
                datasets: [
                    {
                        label: 'Dentro SLA',
                        data: <?= json_encode($slaDentro) ?>,
                        backgroundColor: '#1a7a4a',
                        borderRadius: { topLeft: 0, topRight: 4, bottomLeft: 0, bottomRight: 4 },
                        borderSkipped: false,
                    },
                    {
                        label: 'Fuera SLA',
                        data: <?= json_encode($slaFuera) ?>,
                        backgroundColor: '#c9310f',
                        borderRadius: { topLeft: 0, topRight: 4, bottomLeft: 0, bottomRight: 4 },
                        borderSkipped: false,
                    }
                ]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', align: 'end', labels: { boxWidth: 10, boxHeight: 10, padding: 14, usePointStyle: true, pointStyle: 'circle' } },
                    tooltip: {
                        callbacks: {
                            label: function (c) {
                                var total = c.dataset.data.reduce(function(a,b){return a+b},0);
                                var pct = total > 0 ? Math.round(c.parsed.x / total * 100) : 0;
                                return ' ' + c.dataset.label + ': ' + c.parsed.x.toLocaleString() + ' (' + pct + '%)';
                            }
                        }
                    }
                },
                scales: {
                    x: { stacked: true, beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11 } } },
                    y: { stacked: true, grid: { display: false }, ticks: { font: { size: 12 } } }
                }
            }
        });
    }

    // ── Chart 6: Top Asuntos (horizontal bar) ────────────────────────────────
    var ctxAsu = document.getElementById('chartAsuntos');
    if (ctxAsu) {
        new Chart(ctxAsu, {
            type: 'bar',
            data: {
                labels: <?= json_encode($asuLabels) ?>,
                datasets: [{
                    label: 'Tickets',
                    data: <?= json_encode($asuData) ?>,
                    backgroundColor: 'rgba(59,130,246,0.80)',
                    borderRadius: 5,
                    borderSkipped: false,
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (c) { return ' ' + c.parsed.x.toLocaleString() + ' tickets'; } } }
                },
                scales: {
                    x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { font: { size: 11 } } },
                    y: { grid: { display: false }, ticks: { font: { size: 11 } } }
                }
            }
        });
    }
})();
</script>
</body>
</html>
