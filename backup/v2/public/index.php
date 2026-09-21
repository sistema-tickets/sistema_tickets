<?php
require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/config/constants.php';
require_once __DIR__ . '/../backend/middleware/Auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$usuario = Auth::usuario();
if (!$usuario) {
    header('Location: /sistema_tickets/public/pages/login.html');
    exit;
}

$rol_id   = (int) $usuario['rol_id'];
$nombre   = $usuario['nombre'];
$db       = getDB();

$baseWhere  = ['1=1'];
$baseParams = [];
if ($rol_id === ROL_USUARIO) {
    $baseWhere[] = 't.usuario_id = ?';
    $baseParams[] = $usuario['id'];
}

$where  = $baseWhere;
$params = $baseParams;

// Advanced filters
$filter = $_GET['filter'] ?? 'all';
$filterValue = $_GET['fv'] ?? '';
if ($filter !== 'all' && $filterValue !== '') {
    switch ($filter) {
        case 'estado':
            $where[] = 'LOWER(e.nombre) = LOWER(?)';
            $params[] = $filterValue;
            break;
        case 'prioridad':
            $where[] = 'LOWER(p.nombre) = LOWER(?)';
            $params[] = $filterValue;
            break;
        case 'tipo':
            $where[] = 'LOWER(ts.nombre) = LOWER(?)';
            $params[] = $filterValue;
            break;
        case 'asignado':
            $where[] = 'LOWER(a.nombre) = LOWER(?)';
            $params[] = $filterValue;
            break;
    }
}

// Multi-filter (sidebar form)
$afEstadoId    = (int)($_GET['af_estado'] ?? 0);
$afPrioridadId = (int)($_GET['af_prioridad'] ?? 0);
$afAdminId     = (int)($_GET['af_admin'] ?? 0);
$afTipoId      = (int)($_GET['af_tipo'] ?? 0);
$afFechaDesde  = trim($_GET['af_fd'] ?? '');
$afFechaHasta  = trim($_GET['af_fh'] ?? '');
if ($afEstadoId > 0)    { $where[] = 't.estado_id = ?';    $params[] = $afEstadoId; }
if ($afPrioridadId > 0) { $where[] = 't.prioridad_id = ?'; $params[] = $afPrioridadId; }
if ($afAdminId === -1)  { $where[] = 't.admin_id IS NULL'; }
elseif ($afAdminId > 0) { $where[] = 't.admin_id = ?';     $params[] = $afAdminId; }
if ($afTipoId > 0)      { $where[] = 't.tipo_solicitud_id = ?'; $params[] = $afTipoId; }
if ($afFechaDesde !== '') { $where[] = 'DATE(t.created_at) >= ?'; $params[] = $afFechaDesde; }
if ($afFechaHasta !== '') { $where[] = 'DATE(t.created_at) <= ?'; $params[] = $afFechaHasta; }

// Tab filter
$tab = $_GET['tab'] ?? 'all';
if ($tab === 'abiertos')    { $where[] = 't.estado_id IN (1,5)'; }
elseif ($tab === 'en_proceso') { $where[] = 't.estado_id IN (2,3,4,6)'; }
elseif ($tab === 'cerrados')   { $where[] = 't.estado_id IN (7,8,9)'; }

// Server-side search
$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $likeQ = '%' . $q . '%';
    $searchCols = [
        't.numero_ticket',
        'e.nombre',
        'u.nombre',
        'p.nombre',
        's.nombre',
        'ln.nombre',
        'a.nombre',
        'ts.nombre',
    ];
    $searchOr = [];
    foreach ($searchCols as $col) {
        $searchOr[] = "$col LIKE ?";
        $params[] = $likeQ;
    }
    $searchOr[] = "DATE_FORMAT(t.created_at, '%d/%m/%Y') LIKE ?";
    $params[] = $likeQ;
    $searchOr[] = "DATE_FORMAT(t.updated_at, '%d/%m/%Y %H:%i') LIKE ?";
    $params[] = $likeQ;
    // SLA (computed column)
    $slaExpr = "CASE WHEN t.estado_id IN (8,9) THEN NULL WHEN sc.horas_resolucion IS NULL THEN 'sin_sla' WHEN TIMESTAMPDIFF(MINUTE,t.created_at,NOW())>(sc.horas_resolucion*60) THEN 'vencido' WHEN TIMESTAMPDIFF(MINUTE,t.created_at,NOW())>(sc.horas_resolucion*60*0.8) THEN 'en_riesgo' ELSE 'ok' END";
    $searchOr[] = "$slaExpr LIKE ?";
    $params[] = $likeQ;
    $where[] = '(' . implode(' OR ', $searchOr) . ')';
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 7;
$offset  = ($page - 1) * $perPage;

// Count total tickets
$countFrom = "FROM tickets t
    LEFT JOIN estados         e  ON e.id  = t.estado_id
    LEFT JOIN prioridades     p  ON p.id  = t.prioridad_id
    LEFT JOIN usuarios        u  ON u.id  = t.usuario_id
    LEFT JOIN usuarios        a  ON a.id  = t.admin_id
    LEFT JOIN sitios          s  ON s.id  = t.sitio_id
    LEFT JOIN tipos_solicitud ts ON ts.id = t.tipo_solicitud_id
    LEFT JOIN lineas_negocio  ln ON ln.id = t.linea_negocio_id
    LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1";
$countStmt = $db->prepare("SELECT COUNT(*) $countFrom WHERE " . implode(' AND ', $where));
$countStmt->execute($params);
$totalTickets = (int)$countStmt->fetchColumn();
$totalPages   = max(1, ceil($totalTickets / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

$sql = "SELECT t.id, t.numero_ticket, t.created_at, t.updated_at,
               e.nombre  AS estado,
               p.nombre  AS prioridad,
               u.nombre  AS solicitante,
               a.nombre  AS asignado_a,
               s.nombre  AS sitio_nombre,
               ts.nombre AS tipo_solicitud,
               ln.nombre AS linea_negocio,
               CASE
                   WHEN t.estado_id IN (8, 9) THEN NULL
                   WHEN sc.horas_resolucion IS NULL THEN 'sin_sla'
                   WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) > (sc.horas_resolucion * 60) THEN 'vencido'
                   WHEN TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) > (sc.horas_resolucion * 60 * 0.8) THEN 'en_riesgo'
                   ELSE 'ok'
               END AS sla_estado,
               CASE
                   WHEN t.estado_id IN (8, 9) THEN NULL
                   WHEN sc.horas_resolucion IS NULL THEN NULL
                   ELSE ROUND(GREATEST(sc.horas_resolucion - (TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) / 60), 0), 1)
               END AS sla_horas_restantes
        FROM tickets t
        LEFT JOIN estados         e  ON e.id  = t.estado_id
        LEFT JOIN prioridades     p  ON p.id  = t.prioridad_id
        LEFT JOIN usuarios        u  ON u.id  = t.usuario_id
        LEFT JOIN usuarios        a  ON a.id  = t.admin_id
        LEFT JOIN sitios          s  ON s.id  = t.sitio_id
        LEFT JOIN tipos_solicitud ts ON ts.id = t.tipo_solicitud_id
        LEFT JOIN lineas_negocio  ln ON ln.id = t.linea_negocio_id
        LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1
        WHERE " . implode(' AND ', $where) . "
        ORDER BY t.created_at DESC
        LIMIT $perPage OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

function estadoBadge(string $e): string {
    return match($e) {
        'En espera'            => 'est-en-espera',
        'En proceso'           => 'est-en-proceso',
        'Esperando respuesta'  => 'est-esperando-respuesta',
        'Contestado'           => 'est-contestado',
        'Reabierto'            => 'est-reabierto',
        'Reprogramado'         => 'est-reprogramado',
        'Atendido'             => 'est-atendido',
        'Cerrado'              => 'est-cerrado',
        'Rechazado'            => 'est-rechazado',
        default                => 'est-pendiente',
    };
}

function tipoBadge(string $t): string {
    $t = strtolower($t);
    if (str_contains($t, 'incidencia'))                              return 'tipo-incidencia';
    if (str_contains($t, 'desarrollo') || str_contains($t, 'desarrollo')) return 'tipo-desarrollo';
    if (str_contains($t, 'trino'))                           return 'tipo-trino';
    if (str_contains($t, 'requerimiento') || str_contains($t, 'requrimiento')) return 'tipo-requerimiento';
    return 'tipo-solicitud';
}

function prioBadge(string $p): string {
    return match($p) {
        'Baja'    => 'prio-baja',
        'Media'   => 'prio-media',
        'Alta'    => 'prio-alta',
        default   => 'prio-baja',
    };
}

function sitioBadge(string $s): string {
    $map = [
        'Bogotá'           => 'bogota',
        'Medellín'         => 'medellin',
        'Cali'             => 'cali',
        'Barrancabermeja'  => 'barrancabermeja',
        'Bucaramanga'      => 'bucaramanga',
        'Popayán'          => 'popayan',
        'Pasto'            => 'pasto',
        'Neiva'            => 'neiva',
        'Pereira'          => 'pereira',
        'Ibagué'           => 'ibague',
        'Florencia'        => 'florencia',
        'Cúcuta'           => 'cucuta',
        'Girardot'         => 'girardot',
        'Girón'            => 'giron',
        'Soacha'           => 'soacha',
        'Facatativá'       => 'facatativa',
        'Cajicá'           => 'cajica',
        'Chía'             => 'chia',
        'Madrid'           => 'madrid',
        'Mosquera'         => 'mosquera',
        'Funza'            => 'funza',
        'Fusagasugá'       => 'fusagasuga',
        'Zipaquirá'        => 'zipaquira',
        'Tunja'            => 'tunja',
        'Duitama'          => 'duitama',
        'Sogamoso'         => 'sogamoso',
        'Paipa'            => 'paipa',
        'Chiquinquirá'     => 'chiquinquira',
        'Boyacá'           => 'boyaca',
        'Villa'            => 'villa',
        'Piedecuesta'      => 'piedecuesta',
        'Floridablanca'    => 'floridablanca',
        'Barichara'        => 'barichara',
        'Puerto'           => 'puerto',
        'Espinal'          => 'espinal',
        'San Andrés'       => 'san-andres',
    ];
    return 'sitio-' . ($map[$s] ?? 'default');
}

// KPI cards (aggregate queries over ALL tickets, ignoring current filter)
$baseWhereStr = implode(' AND ', $baseWhere);
$st = $db->prepare("SELECT COUNT(*) FROM tickets t WHERE $baseWhereStr AND t.admin_id IS NOT NULL");
$st->execute($baseParams); $asignados = (int)$st->fetchColumn();
$st = $db->prepare("SELECT COUNT(*) FROM tickets t LEFT JOIN estados e ON e.id = t.estado_id WHERE $baseWhereStr AND e.nombre NOT IN ('Atendido','Cerrado','Rechazado')");
$st->execute($baseParams); $activos = (int)$st->fetchColumn();
$st = $db->prepare("SELECT COUNT(*) FROM tickets t LEFT JOIN estados e ON e.id = t.estado_id WHERE $baseWhereStr AND e.nombre IN ('Atendido','Cerrado')");
$st->execute($baseParams); $cerrados = (int)$st->fetchColumn();

// % SLA (base, ignores filter)
$slaWhere  = array_merge(['t.estado_id IN (7, 8)', 't.closed_at IS NOT NULL'], array_slice($baseWhere, 1));
$slaParams = $baseParams;
$slaStmt = $db->prepare("SELECT COUNT(*) AS total,
    SUM(CASE WHEN TIMESTAMPDIFF(HOUR, t.created_at, t.closed_at) <= sc.horas_resolucion THEN 1 ELSE 0 END) AS dentro
    FROM tickets t
    JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1
    WHERE " . implode(' AND ', $slaWhere));
$slaStmt->execute($slaParams);
$slaRow    = $slaStmt->fetch();
$totalSLA  = (int) $slaRow['total'];
$dentroSLA = (int) $slaRow['dentro'];
$pctSLA    = $totalSLA > 0 ? round($dentroSLA / $totalSLA * 100) : null;
$slaClase  = $pctSLA === null ? '' : ($pctSLA >= 80 ? 'kpi-sla--verde' : ($pctSLA >= 60 ? 'kpi-sla--amber' : 'kpi-sla--rojo'));

$whereStr   = implode(' AND ', $baseWhere);

// ── KPI v2: totales por tabs ──────────────────────────────────────────────
$st = $db->prepare("SELECT COUNT(*) FROM tickets t WHERE $baseWhereStr");
$st->execute($baseParams); $totalKpi = (int)$st->fetchColumn();

$st = $db->prepare("SELECT COUNT(*) FROM tickets t WHERE $baseWhereStr AND t.estado_id IN (1,5)");
$st->execute($baseParams); $tabAbiertosCount = (int)$st->fetchColumn();

$st = $db->prepare("SELECT COUNT(*) FROM tickets t WHERE $baseWhereStr AND t.estado_id IN (2,3,4,6)");
$st->execute($baseParams); $tabProcesoCount = (int)$st->fetchColumn();

$st = $db->prepare("SELECT COUNT(*) FROM tickets t WHERE $baseWhereStr AND t.estado_id IN (7,8,9)");
$st->execute($baseParams); $tabCerradosCount = (int)$st->fetchColumn();

$st = $db->prepare("SELECT COUNT(*) FROM tickets t
    LEFT JOIN sla_configuracion sc ON sc.prioridad_id = t.prioridad_id AND sc.activo = 1
    WHERE $baseWhereStr AND t.estado_id NOT IN (7,8,9)
    AND sc.horas_resolucion IS NOT NULL
    AND TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) > (sc.horas_resolucion * 60 * 0.8)");
$st->execute($baseParams); $kpiSlaRiesgo = (int)$st->fetchColumn();

// ── Helpers ───────────────────────────────────────────────────────────────
function avatarColors(string $name): string {
    $palette = ['#1a3a6b','#1d9e75','#d97706','#7c3aed','#dc2626','#0891b2','#9a3412','#065f46'];
    return $palette[abs(crc32($name)) % count($palette)];
}
function nameInitials(string $name): string {
    $parts = array_filter(explode(' ', $name));
    return strtoupper(implode('', array_map(fn($p) => $p[0], array_slice($parts, 0, 2))));
}
function timeAgo(\DateTimeInterface|string $dt): string {
    $ts  = is_string($dt) ? strtotime($dt) : $dt->getTimestamp();
    $diff = time() - $ts;
    if ($diff < 60)    return 'hace ' . $diff . 's';
    if ($diff < 3600)  return 'hace ' . floor($diff / 60) . 'min';
    if ($diff < 86400) return 'hace ' . floor($diff / 3600) . 'h';
    return 'hace ' . floor($diff / 86400) . 'd';
}

// ── Filter chips data ─────────────────────────────────────────────────────
$activeChips = [];
$removeBase  = $_GET;

if ($afEstadoId > 0) {
    $estNombre = '';
    foreach ($estados ?? [] as $e) { if ((int)$e['id'] === $afEstadoId) { $estNombre = $e['nombre']; break; } }
    $rb = $removeBase; $rb['af_estado'] = 0; unset($rb['page']);
    $activeChips[] = ['label' => 'Estado', 'value' => $estNombre, 'remove' => '?' . http_build_query($rb)];
}
if ($afPrioridadId > 0) {
    $priNombre = '';
    foreach ($prioridades ?? [] as $p) { if ((int)$p['id'] === $afPrioridadId) { $priNombre = $p['nombre']; break; } }
    $rb = $removeBase; $rb['af_prioridad'] = 0; unset($rb['page']);
    $activeChips[] = ['label' => 'Prioridad', 'value' => $priNombre, 'remove' => '?' . http_build_query($rb)];
}
if ($afAdminId > 0) {
    $admNombre = 'Asignado';
    foreach ($admins ?? [] as $a) { if ((int)$a['id'] === $afAdminId) { $admNombre = $a['nombre']; break; } }
    $rb = $removeBase; $rb['af_admin'] = 0; unset($rb['page']);
    $activeChips[] = ['label' => 'Asignado a', 'value' => $admNombre, 'remove' => '?' . http_build_query($rb)];
}
if ($afAdminId === -1) {
    $rb = $removeBase; $rb['af_admin'] = 0; unset($rb['page']);
    $activeChips[] = ['label' => 'Asignado a', 'value' => 'Sin asignar', 'remove' => '?' . http_build_query($rb)];
}
if ($afTipoId > 0) {
    $tipNombre = '';
    foreach ($tipos ?? [] as $t) { if ((int)$t['id'] === $afTipoId) { $tipNombre = $t['nombre']; break; } }
    $rb = $removeBase; $rb['af_tipo'] = 0; unset($rb['page']);
    $activeChips[] = ['label' => 'Tipo', 'value' => $tipNombre, 'remove' => '?' . http_build_query($rb)];
}
if ($afFechaDesde !== '') {
    $rb = $removeBase; $rb['af_fd'] = ''; unset($rb['page']);
    $activeChips[] = ['label' => 'Desde', 'value' => $afFechaDesde, 'remove' => '?' . http_build_query($rb)];
}
if ($afFechaHasta !== '') {
    $rb = $removeBase; $rb['af_fh'] = ''; unset($rb['page']);
    $activeChips[] = ['label' => 'Hasta', 'value' => $afFechaHasta, 'remove' => '?' . http_build_query($rb)];
}
if (trim($_GET['q'] ?? '') !== '') {
    $rb = $removeBase; $rb['q'] = ''; unset($rb['page']);
    $activeChips[] = ['label' => 'Búsqueda', 'value' => trim($_GET['q']), 'remove' => '?' . http_build_query($rb)];
}

// Load filter options
$estados = $db->query("SELECT id, nombre FROM estados ORDER BY id")->fetchAll();
$prioridades = $db->query("SELECT id, nombre FROM prioridades ORDER BY orden")->fetchAll();
$tipos = $db->query("SELECT id, nombre FROM tipos_solicitud ORDER BY nombre")->fetchAll();
$admins = $db->query("SELECT id, nombre FROM usuarios WHERE rol_id = 2 AND activo = 1 ORDER BY nombre")->fetchAll();

// Turno basado en hora actual
$turno = (int)date('H') < 12 ? 'AM' : 'PM';

// Flag de filtros activos
$hasActiveFilter = ($afEstadoId > 0 || $afPrioridadId > 0 || $afAdminId !== 0 || $afTipoId > 0 || $afFechaDesde !== '' || $afFechaHasta !== '');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Incidencias</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    <style>
      [v-cloak]{display:none}
      .dash-content-blur{filter:blur(4px);pointer-events:none;user-select:none;transition:filter .3s}
      .overlay-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:100;backdrop-filter:blur(2px)}
      .overlay-wrapper{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:101;width:90%;max-width:800px;max-height:90vh;overflow-y:auto}
      .overlay-wrapper .crear-card{margin:0!important;width:100%!important;max-width:100%!important;border-radius:16px}
      .overlay-wrapper .crear-back{left:auto;right:16px;top:16px;color:#dc2626}
      @media(max-width:768px){.overlay-wrapper{width:96%;max-height:94vh;padding:0}.overlay-wrapper .crear-card{border-radius:14px}.overlay-wrapper .crear-back{right:12px;top:12px}}
      @media(max-width:480px){.overlay-wrapper .crear-card{padding:12px 10px 16px!important}.overlay-wrapper .crear-back{right:8px;top:8px}}
    </style>
</head>
<body class="dash-body">
<div class="dash-wrapper">

    <!-- ===== HEADER ===== -->
    <?php
    $showSearch        = true;
    $showSidebarToggle = true;
    $userName          = $nombre;
    $basePath          = '';
    require __DIR__ . '/assets/inc/header.php';
    ?>

    <div class="dash-body-inner">

        <!-- ===== SIDEBAR ===== -->
        <?php
        $currentPage = 'tickets';
        require __DIR__ . '/assets/inc/sidebar.php';
        ?>

        <!-- ===== CONTENT ===== -->
        <div class="dash-content">

<div id="dashApp">

    <!-- ========== LIST VIEW (always visible) ========== -->
    <div :class="{ 'dash-content-blur': currentView !== 'list' }">

        <!-- KPI Cards -->
        <?php if (in_array($rol_id, [ROL_ADMIN, ROL_SUPERADMIN])): ?>
        <div class="kpi-grid2">

            <div class="kpi-card2 kpi2-total">
                <div class="kpi2-top">
                    <div class="kpi2-info">
                        <span class="kpi2-num"><?= $totalKpi ?></span>
                        <span class="kpi2-label">Total tickets</span>
                    </div>
                    <div class="kpi2-sparkline">
                        <svg width="80" height="32" viewBox="0 0 80 32" fill="none">
                            <polyline points="0,28 16,20 32,24 48,12 64,16 80,10"
                                      stroke="#1a3a6b" stroke-width="2"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi2-bottom">
                    <span class="kpi2-trend-up"><i class="fa-solid fa-arrow-trend-up"></i> 8.4%</span>
                    <span>vs. semana anterior</span>
                </div>
            </div>

            <div class="kpi-card2 kpi2-abiertos">
                <div class="kpi2-top">
                    <div class="kpi2-info">
                        <span class="kpi2-num"><?= $tabAbiertosCount ?></span>
                        <span class="kpi2-label">Abiertos</span>
                    </div>
                    <div class="kpi2-sparkline">
                        <svg width="80" height="32" viewBox="0 0 80 32" fill="none">
                            <polyline points="0,22 16,18 32,26 48,14 64,20 80,10"
                                      stroke="#2563eb" stroke-width="2"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi2-bottom">
                    <span class="kpi2-trend-up"><i class="fa-solid fa-arrow-trend-up"></i> 3</span>
                    <span>nuevos hoy</span>
                </div>
            </div>

            <div class="kpi-card2 kpi2-proceso">
                <div class="kpi2-top">
                    <div class="kpi2-info">
                        <span class="kpi2-num"><?= $tabProcesoCount ?></span>
                        <span class="kpi2-label">En proceso</span>
                    </div>
                    <div class="kpi2-sparkline">
                        <svg width="80" height="32" viewBox="0 0 80 32" fill="none">
                            <polyline points="0,10 16,18 32,14 48,22 64,18 80,24"
                                      stroke="#d97706" stroke-width="2"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi2-bottom">
                    <span class="kpi2-trend-down"><i class="fa-solid fa-arrow-trend-down"></i> 21%</span>
                    <span>tiempo medio resolución</span>
                </div>
            </div>

            <div class="kpi-card2 kpi2-sla">
                <div class="kpi2-top">
                    <div class="kpi2-info">
                        <span class="kpi2-num"><?= $kpiSlaRiesgo ?></span>
                        <span class="kpi2-label">Críticos / SLA en riesgo</span>
                    </div>
                    <div class="kpi2-sparkline">
                        <svg width="80" height="32" viewBox="0 0 80 32" fill="none">
                            <polyline points="0,24 16,20 32,28 48,16 64,22 80,12"
                                      stroke="#dc2626" stroke-width="2"
                                      stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <div class="kpi2-bottom">
                    <?php if ($kpiSlaRiesgo > 0): ?>
                    <span class="kpi2-trend-warn"><i class="fa-solid fa-triangle-exclamation"></i> <?= $kpiSlaRiesgo ?></span>
                    <span>requieren escalamiento</span>
                    <?php else: ?>
                    <span class="kpi2-trend-up"><i class="fa-solid fa-check"></i> Todo en orden</span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php else: ?>

        <!-- KPI Cards — Usuario normal -->
        <div class="kpi-user-grid">
            <div class="kpi-user-card kpi-user-total">
                <div class="kpi-user-icon"><i class="fa-solid fa-ticket"></i></div>
                <div class="kpi-user-info">
                    <span class="kpi-user-num"><?= $totalKpi ?></span>
                    <span class="kpi-user-label">Total mis tickets</span>
                </div>
            </div>
            <div class="kpi-user-card kpi-user-abiertos">
                <div class="kpi-user-icon"><i class="fa-solid fa-circle-dot"></i></div>
                <div class="kpi-user-info">
                    <span class="kpi-user-num"><?= $tabAbiertosCount ?></span>
                    <span class="kpi-user-label">Abiertos</span>
                </div>
            </div>
            <div class="kpi-user-card kpi-user-proceso">
                <div class="kpi-user-icon"><i class="fa-solid fa-gears"></i></div>
                <div class="kpi-user-info">
                    <span class="kpi-user-num"><?= $tabProcesoCount ?></span>
                    <span class="kpi-user-label">En proceso</span>
                </div>
            </div>
            <div class="kpi-user-card kpi-user-cerrados">
                <div class="kpi-user-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="kpi-user-info">
                    <span class="kpi-user-num"><?= $tabCerradosCount ?></span>
                    <span class="kpi-user-label">Resueltos</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Barra de filtros inline (búsqueda + filtros avanzados) -->
        <div class="inline-filter-bar">
            <form method="GET" action="<?= $basePath ?>index.php" id="inlineFilterForm" class="inline-filter-form">
                <?php if (isset($_GET['tab']) && $_GET['tab'] !== 'all'): ?>
                <input type="hidden" name="tab" value="<?= htmlspecialchars($_GET['tab']) ?>">
                <?php endif; ?>

                <div class="inline-search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="q" id="searchInput"
                           value="<?= htmlspecialchars(trim($_GET['q'] ?? '')) ?>"
                           placeholder="Buscar tickets, solicitantes, sitios...">
                </div>

                <select name="af_estado" class="inline-filter-select">
                    <option value="0">Estado</option>
                    <?php foreach ($estados as $est): ?>
                    <option value="<?= $est['id'] ?>" <?= $afEstadoId === (int)$est['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($est['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select name="af_prioridad" class="inline-filter-select">
                    <option value="0">Prioridad</option>
                    <?php foreach ($prioridades as $pri): ?>
                    <option value="<?= $pri['id'] ?>" <?= $afPrioridadId === (int)$pri['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($pri['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <select name="af_tipo" class="inline-filter-select">
                    <option value="0">Tipo</option>
                    <?php foreach ($tipos as $tip): ?>
                    <option value="<?= $tip['id'] ?>" <?= $afTipoId === (int)$tip['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tip['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>

                <?php if (in_array($rol_id, [ROL_ADMIN, ROL_SUPERADMIN])): ?>
                <select name="af_admin" class="inline-filter-select">
                    <option value="0">Asignado a</option>
                    <option value="-1" <?= $afAdminId === -1 ? 'selected' : '' ?>>Sin asignar</option>
                    <?php foreach ($admins as $adm): ?>
                    <option value="<?= $adm['id'] ?>" <?= $afAdminId === (int)$adm['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($adm['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <input type="date" name="af_fd" class="inline-filter-date"
                       value="<?= htmlspecialchars($afFechaDesde) ?>" title="Desde">
                <input type="date" name="af_fh" class="inline-filter-date"
                       value="<?= htmlspecialchars($afFechaHasta) ?>" title="Hasta">

                <button type="submit" class="inline-filter-btn">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                <?php if ($hasActiveFilter || trim($_GET['q'] ?? '') !== ''): ?>
                <a href="<?= $basePath ?>index.php<?= isset($_GET['tab']) && $_GET['tab'] !== 'all' ? '?tab=' . htmlspecialchars($_GET['tab']) : '' ?>"
                   class="inline-filter-clear" title="Limpiar filtros">
                    <i class="fa-solid fa-xmark"></i>
                </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Filter chips bar + acciones de tabla -->
        <div class="filter-chips-bar">
            <?php foreach ($activeChips as $chip): ?>
            <span class="filter-chip-item">
                <span class="fc-label"><?= htmlspecialchars($chip['label']) ?>:</span>
                <span class="fc-val"><?= htmlspecialchars($chip['value']) ?></span>
                <a href="<?= htmlspecialchars($chip['remove']) ?>" class="fc-remove" title="Eliminar filtro">&#x2715;</a>
            </span>
            <?php endforeach; ?>
            <div class="chips-bar-actions">
                <button class="btn-action-primary" onclick="if(window.appShowCrear)window.appShowCrear();else window.location.href='?view=crear'">
                    <i class="fa-solid fa-plus"></i> Nuevo ticket
                </button>
                <a href="#" class="btn-tab-action"><i class="fa-solid fa-arrow-up-short-wide"></i> Ordenar</a>
                <a href="#" class="btn-tab-action"><i class="fa-solid fa-table-columns"></i> Columnas</a>
            </div>
        </div>



            <!-- Ticket Table -->
            <div class="dash-table-wrap">

                <!-- Column headers -->
                <div class="dash-table-header">
                    <span class="col-id-h">ID</span>
                    <span class="th-sep">|</span>
                    <span class="col-estado-h">ESTADO</span>
                    <span class="th-sep">|</span>
                    <span class="col-soli-h">SOLICITANTE</span>
                    <span class="th-sep">|</span>
                    <span class="col-fecha-h">FECHA CREA. Y RESP.</span>
                    <span class="th-sep">|</span>
                    <span class="col-sitio-h">SITIO</span>
                    <span class="th-sep">|</span>
                    <span class="col-linea-h">LÍNEA DE NEGOCIO</span>
                    <span class="th-sep">|</span>
                    <span class="col-asig-h">ASIGNADO A</span>
                    <span class="th-sep">|</span>
                    <span class="col-tipo-h">TIPO</span>
                    <span class="th-sep">|</span>
                    <span class="col-sla-h">SLA</span>
                    <span class="th-sep">|</span>
                    <span class="col-prio-h">PRIORIDAD</span>
                </div>

                <!-- Empty state -->
                <?php if (empty($tickets)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-exclamation" style="font-size:48px;color:#ccc;margin-bottom:4px;"></i>
                    <p class="empty-title">Aún no hay tickets registrados</p>
                    <p class="empty-sub">Crea tu primer ticket para comenzar a gestionar incidencias y requerimientos.</p>
                    <a href="javascript:void(0)" onclick="appShowCrear()" class="btn-create-ticket">
                        <i class="fa-solid fa-plus"></i> Crear nuevo ticket
                    </a>
                </div>
                <?php endif; ?>

                <!-- Rows -->
                <div id="ticketList">
                    <?php foreach ($tickets as $t): ?>
                    <div class="ticket-row"
                         data-estado="<?= htmlspecialchars(strtolower($t['estado'])) ?>"
                         data-prioridad="<?= htmlspecialchars(strtolower($t['prioridad'])) ?>"
                         data-tipo="<?= htmlspecialchars(strtolower($t['tipo_solicitud'] ?? '')) ?>"
                          data-asignado="<?= htmlspecialchars(strtolower($t['asignado_a'] ?? '')) ?>"
                         data-sitio="<?= htmlspecialchars(strtolower($t['sitio_nombre'] ?? '')) ?>"
                         data-sla="<?= htmlspecialchars($t['sla_estado'] ?? '') ?>"
                         onclick="appShowDetalle(<?= $t['id'] ?>)">

                        <div class="col-id">
                            <?= htmlspecialchars($t['numero_ticket'] ?? '#' . str_pad((string)$t['id'], 5, '0', STR_PAD_LEFT)) ?>
                        </div>

                        <div class="col-estado">
                            <span class="estado-badge <?= estadoBadge($t['estado']) ?>">
                                <?= htmlspecialchars($t['estado']) ?>
                            </span>
                        </div>

                        <div class="col-soli" data-label="Solicitante"><?= htmlspecialchars($t['solicitante'] ?? '—') ?></div>

                        <div class="col-fecha" data-label="Creado">
                            <span><?= date('d M Y H:i', strtotime($t['created_at'])) ?></span>
                            <?php if ($t['updated_at'] && $t['updated_at'] !== $t['created_at']): ?>
                            <span><?= date('d M Y H:i', strtotime($t['updated_at'])) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="col-sitio" data-label="Sitio">
                            <?php if ($t['sitio_nombre']): ?>
                            <span class="city-badge <?= sitioBadge($t['sitio_nombre']) ?>"><?= htmlspecialchars(mb_strtoupper(mb_substr($t['sitio_nombre'], 0, 4))) ?></span>
                            <?php else: ?>—<?php endif; ?>
                        </div>

                        <div class="col-linea" data-label="Línea"><?= htmlspecialchars($t['linea_negocio'] ?? '—') ?></div>

                        <div class="col-asig" data-label="Asignado"><?= htmlspecialchars($t['asignado_a'] ?? '—') ?></div>

                        <div class="col-tipo" data-label="Tipo">
                            <?php if ($t['tipo_solicitud']): ?>
                            <span class="tipo-badge <?= tipoBadge($t['tipo_solicitud']) ?>">
                                <?= htmlspecialchars($t['tipo_solicitud']) ?>
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </div>

                        <div class="col-sla" data-label="SLA">
                            <?php if ($t['sla_estado'] && $t['sla_estado'] !== 'sin_sla'): ?>
                            <span class="sla-badge sla-<?= $t['sla_estado'] ?>"
                                  title="<?= $t['sla_horas_restantes'] !== null ? ($t['sla_horas_restantes'] . 'h restantes') : '' ?>">
                                <?= match($t['sla_estado']) {
                                    'ok'        => 'OK',
                                    'en_riesgo' => 'Riesgo',
                                    'vencido'   => 'Vencido',
                                    default     => '—',
                                } ?>
                            </span>
                            <?php else: ?>
                            <span class="sla-badge sla-na">—</span>
                            <?php endif; ?>
                        </div>

                        <div class="col-prio" data-label="Prioridad">
                            <span class="prio-badge <?= prioBadge($t['prioridad']) ?>">
                                <?= htmlspecialchars($t['prioridad']) ?>
                            </span>
                        </div>

                        <div class="col-arrow"><i class="fa-solid fa-angle-right"></i></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <?php
                $pageQuery = $_GET;
                unset($pageQuery['page']);
                $pageBase  = http_build_query($pageQuery);
                $pageBase  = $pageBase ? $pageBase . '&' : '';
                ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?<?= $pageBase ?>page=<?= $page - 1 ?>" class="page-link"><i class="fa-solid fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php
                    $range = 3;
                    $start = max(1, $page - $range);
                    $end   = min($totalPages, $page + $range);
                    if ($start > 1) echo '<span class="page-dots">…</span>';
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <a href="?<?= $pageBase ?>page=<?= $i ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <?php if ($end < $totalPages) echo '<span class="page-dots">…</span>'; ?>
                    <?php if ($page < $totalPages): ?>
                    <a href="?<?= $pageBase ?>page=<?= $page + 1 ?>" class="page-link"><i class="fa-solid fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div><!-- /.dash-table-wrap -->


    </div><!-- /list view -->

    <!-- ========== BACKDROP ========== -->
    <div v-if="currentView !== 'list'" class="overlay-backdrop" @click="showList"></div>

    <!-- ========== CREAR OVERLAY ========== -->
    <div v-if="currentView === 'crear'" class="overlay-wrapper" v-cloak>
      <div class="crear-card" :style="crearIsAdmin ? 'max-width:760px' : ''">
        <a href="javascript:void(0)" @click="showList" class="crear-back" title="Cerrar"><i class="fa-solid fa-xmark"></i></a>

        <h1 class="crear-title">Crear un ticket</h1>
        <p class="crear-sub">Introduzca los datos a continuación para enviar su solicitud de soporte</p>

        <div v-if="crearSuccess" style="background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.875rem">
          Ticket creado correctamente.
          <a href="javascript:void(0)" @click="showList" style="color:#15803d;font-weight:600">Volver a mis tickets →</a>
        </div>
        <div v-if="crearError" style="background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.875rem">
          {{ crearError }}
        </div>

        <!-- ── FORMULARIO USUARIO ── -->
        <form v-if="!crearIsAdmin" @submit.prevent="crearSubmit" class="crear-form">
          <div class="cc-field">
            <label class="cc-label"><span class="req">*</span> Nombre</label>
            <input :value="crearUsuario?.nombre" class="cc-input" readonly>
          </div>
          <div class="crear-row-2">
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Correo electrónico</label>
              <input :value="crearUsuario?.email" class="cc-input" readonly>
            </div>
            <div class="cc-field">
              <label class="cc-label">Celular</label>
              <input v-model="crearForm.celular" class="cc-input" placeholder="Opcional">
            </div>
          </div>
          <div class="crear-row-2">
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Tipo de solicitud</label>
              <select v-model="crearForm.tipo_solicitud_id" class="cc-input" required>
                <option value="">— Seleccionar —</option>
                <option v-for="t in crearTiposSolicitud" :key="t.id" :value="t.id">{{ t.nombre }}</option>
              </select>
            </div>
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Línea de negocio</label>
              <select v-model="crearForm.linea_negocio_id" class="cc-input" required>
                <option value="">— Seleccionar —</option>
                <option v-for="l in crearLineas" :key="l.id" :value="l.id">{{ l.nombre }}</option>
              </select>
            </div>
          </div>
          <div class="crear-row-2">
            <div class="cc-field" v-if="crearForm.tipo_solicitud_id == 1">
              <label class="cc-label"><span class="req">*</span> Lugar de incidencia</label>
              <select v-model="crearForm.lugar_incidencia_id" class="cc-input" :required="crearForm.tipo_solicitud_id == 1">
                <option value="">— Seleccionar —</option>
                <option v-for="l in crearLugarIncidencia" :key="l.id" :value="l.id">{{ l.nombre }}</option>
              </select>
            </div>
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Sitio</label>
              <select v-model="crearForm.sitio_id" class="cc-input" required>
                <option value="">— Seleccionar —</option>
                <option v-for="s in crearSitios" :key="s.id" :value="s.id">{{ s.nombre }}</option>
              </select>
            </div>
          </div>
          <div class="cc-field">
            <label class="cc-label"><span class="req">*</span> Descripción</label>
            <div id="quill-usuario"></div>
          </div>
          <div>
            <div class="file-hint">Archivos adjuntos (puede seleccionar varios archivos)</div>
            <div class="file-label-row">Seleccione El archivo Aquí</div>
            <div class="file-row">
              <label class="file-btn-label">
                Seleccionar Archivo
                <input type="file" multiple style="display:none" @change="crearOnFileChange" ref="crearFileInput">
              </label>
              <span class="file-names">{{ crearFileNames || 'No se ha seleccionado ningún archivo...' }}</span>
            </div>
          </div>
          <button type="submit" class="btn-crear" :disabled="crearLoading">
            {{ crearLoading ? 'Enviando...' : 'Crear ticket' }}
          </button>
        </form>

        <!-- ── FORMULARIO ADMIN ── -->
        <form v-else @submit.prevent="crearSubmit" class="crear-form">
          <div class="section-title">Detalle de la solicitud</div>
          <div class="cc-field">
            <label class="cc-label"><span class="req">*</span> Nombre del solicitante</label>
            <select v-model="crearForm.usuario_id" @change="crearOnUsuarioChange" class="cc-input" required>
              <option value="">— Seleccionar usuario —</option>
              <option v-for="u in crearUsuarios" :key="u.id" :value="u.id">{{ u.nombre }}</option>
            </select>
          </div>
          <div class="crear-row-2">
            <div class="cc-field">
              <label class="cc-label">Celular</label>
              <input v-model="crearForm.celular" class="cc-input" placeholder="Opcional">
            </div>
            <div class="cc-field">
              <label class="cc-label">Correo</label>
              <input :value="crearUsuarioSeleccionado?.email ?? ''" class="cc-input" readonly>
            </div>
          </div>
          <div class="crear-row-2">
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Línea de negocio</label>
              <select v-model="crearForm.linea_negocio_id" class="cc-input" required>
                <option value="">— Seleccionar —</option>
                <option v-for="l in crearLineas" :key="l.id" :value="l.id">{{ l.nombre }}</option>
              </select>
            </div>
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Sitio</label>
              <select v-model="crearForm.sitio_id" class="cc-input" required>
                <option value="">— Seleccionar —</option>
                <option v-for="s in crearSitios" :key="s.id" :value="s.id">{{ s.nombre }}</option>
              </select>
            </div>
          </div>
          <div class="section-title" style="margin-top:.5rem">✏ Descripción de solución</div>
          <div class="crear-row-2">
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Asignado a</label>
              <select v-model="crearForm.admin_id" class="cc-input" required>
                <option value="">— Sin asignar —</option>
                <option v-for="u in crearUsuarios" :key="u.id" :value="u.id">{{ u.nombre }}</option>
              </select>
            </div>
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Asunto</label>
              <select v-model="crearForm.asunto_id" @change="crearOnAsuntoChange" class="cc-input" :disabled="!crearAdminForm.tipo_asunto_id" required>
                <option value="">— Seleccionar —</option>
                <option v-for="a in crearAsuntos" :key="a.id" :value="a.id">{{ a.nombre }}</option>
              </select>
            </div>
          </div>
          <div class="crear-row-2">
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Tipo de asunto</label>
              <select v-model="crearAdminForm.tipo_asunto_id" @change="crearOnTipoAsuntoChange" class="cc-input" required>
                <option value="">— Seleccionar —</option>
                <option v-for="t in crearTiposAsunto" :key="t.id" :value="t.id">{{ t.nombre }}</option>
              </select>
            </div>
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Prioridad</label>
              <div class="prio-selector">
                <label class="prio-opt" :class="{active: crearForm.prioridad_id == '1'}" style="--prio-bg:var(--col-prio-baja-bg);--prio-text:var(--col-prio-baja-text);opacity:.6;cursor:not-allowed">
                  <input type="radio" name="crearPrioridad" value="1" v-model="crearForm.prioridad_id" hidden disabled>
                  <span>Baja</span>
                </label>
                <label class="prio-opt" :class="{active: crearForm.prioridad_id == '2'}" style="--prio-bg:var(--col-prio-media-bg);--prio-text:var(--col-prio-media-text);opacity:.6;cursor:not-allowed">
                  <input type="radio" name="crearPrioridad" value="2" v-model="crearForm.prioridad_id" hidden disabled>
                  <span>Media</span>
                </label>
                <label class="prio-opt" :class="{active: crearForm.prioridad_id == '3'}" style="--prio-bg:var(--col-prio-alta-bg);--prio-text:var(--col-prio-alta-text);opacity:.6;cursor:not-allowed">
                  <input type="radio" name="crearPrioridad" value="3" v-model="crearForm.prioridad_id" hidden disabled>
                  <span>Alta</span>
                </label>
              </div>
            </div>
          </div>
          <div class="crear-row-2">
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Tipo de solicitud</label>
              <select v-model="crearForm.tipo_solicitud_id" class="cc-input" required>
                <option value="">— Seleccionar —</option>
                <option v-for="t in crearTiposSolicitud" :key="t.id" :value="t.id">{{ t.nombre }}</option>
              </select>
            </div>
            <div class="cc-field">
              <label class="cc-label"><span class="req">*</span> Estado inicial</label>
              <select v-model="crearForm.estado_id" class="cc-input" required>
                <option value="">— Seleccionar —</option>
                <option value="1">En espera</option>
                <option value="2">En proceso</option>
              </select>
            </div>
          </div>
          <div class="cc-field" v-if="crearForm.tipo_solicitud_id == 1">
            <label class="cc-label"><span class="req">*</span> Lugar de incidencia</label>
            <select v-model="crearForm.lugar_incidencia_id" class="cc-input" :required="crearForm.tipo_solicitud_id == 1">
              <option value="">— Seleccionar —</option>
              <option v-for="l in crearLugarIncidencia" :key="l.id" :value="l.id">{{ l.nombre }}</option>
            </select>
          </div>
          <div class="cc-field">
            <label class="cc-label"><span class="req">*</span> Descripción</label>
            <div id="quill-admin"></div>
          </div>
          <div>
            <div class="file-hint">Archivos adjuntos (puede seleccionar varios archivos)</div>
            <div class="file-label-row">Seleccione El archivo Aquí</div>
            <div class="file-row">
              <label class="file-btn-label">
                Seleccionar Archivo
                <input type="file" multiple style="display:none" @change="crearOnFileChange" ref="crearFileInput">
              </label>
              <span class="file-names">{{ crearFileNames || 'No se ha seleccionado ningún archivo...' }}</span>
            </div>
          </div>
          <button type="submit" class="btn-crear" :disabled="crearLoading">
            {{ crearLoading ? 'Guardando...' : 'Guardar y publicar' }}
          </button>
        </form>
      </div>
    </div>

    <!-- ========== DETALLE OVERLAY ========== -->
    <div v-if="currentView === 'detalle'" class="overlay-wrapper" v-cloak>
      <div v-if="detalleLoading" style="text-align:center;padding:3rem;color:var(--text-secondary)">Cargando...</div>
      <div v-else-if="!detalleTicket" style="text-align:center;padding:3rem;color:#b91c1c">Ticket no encontrado.</div>
      <div v-else class="crear-card" style="max-width:700px">

        <a href="javascript:void(0)" @click="showList" class="crear-back" title="Cerrar"><i class="fa-solid fa-xmark"></i></a>
        <h1 class="crear-title">ID {{ detalleTicket.numero_ticket }}</h1>

        <div class="section-title" style="margin-top:1rem">Detalle de la solicitud</div>
        <div class="det-grid">
          <div class="det-item">
            <label>Tipo de solicitud</label>
            <span>{{ detalleTicket.tipo_solicitud_nombre || '—' }}</span>
          </div>
          <div class="det-item">
            <label>Solicitante</label>
            <span>{{ detalleTicket.usuario_nombre || '—' }}</span>
          </div>
          <div class="det-item">
            <label>Línea de negocio</label>
            <span>{{ detalleTicket.linea_negocio_nombre || '—' }}</span>
          </div>
          <div class="det-item">
            <label>Celular</label>
            <span>—</span>
          </div>
          <div class="det-item">
            <label>Sitio</label>
            <span>{{ detalleTicket.sitio_nombre || '—' }}</span>
          </div>
          <div class="det-item">
            <label>&#128206; Datos adjuntos</label>
            <span v-if="detalleAdjuntos.length === 0" style="color:var(--sidebar-bg);font-size:12px">Sin adjuntos</span>
            <div v-else class="det-adjuntos">
              <div v-for="a in detalleAdjuntos" :key="a.id" class="det-adjunto-item">
                <i class="fa-solid fa-paperclip"></i>
                <a :href="'download.php?id=' + a.id" target="_blank">{{ a.nombre_original }}</a>
                <span class="det-adj-size">({{ formatBytes(a.tamanio_bytes) }})</span>
              </div>
            </div>
          </div>
        </div>

        <div style="margin-bottom:1rem">
          <div style="font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--text-secondary);margin-bottom:4px">Mensaje del solicitante</div>
          <div class="det-mensaje" v-html="detalleTicket.descripcion"></div>
        </div>

        <template v-if="detalleIsAdmin">
          <div class="section-title">&#9998; Descripción de solución</div>
          <form @submit.prevent="detalleGuardar" style="display:flex;flex-direction:column;gap:16px">
            <div class="crear-row-2">
              <div class="cc-field">
                <label class="cc-label">Asignado a</label>
                <select v-model="detalleForm.admin_id" class="cc-input">
                  <option value="">— Sin asignar —</option>
                  <option v-for="a in detalleAdmins" :key="a.id" :value="a.id">{{ a.nombre }}</option>
                </select>
              </div>
              <div class="cc-field">
                <label class="cc-label">Asunto</label>
                <select v-model="detalleForm.asunto_id" @change="detalleOnAsuntoChange" class="cc-input" :disabled="!detalleAdminForm.tipo_asunto_id">
                  <option value="">— Seleccionar —</option>
                  <option v-for="a in detalleAsuntos" :key="a.id" :value="a.id">{{ a.nombre }}</option>
                </select>
              </div>
            </div>
            <div class="crear-row-2">
              <div class="cc-field">
                <label class="cc-label">Tipo de asunto</label>
                <select v-model="detalleAdminForm.tipo_asunto_id" @change="detalleOnTipoAsuntoChange" class="cc-input">
                  <option value="">— Seleccionar —</option>
                  <option v-for="t in detalleTiposAsunto" :key="t.id" :value="t.id">{{ t.nombre }}</option>
                </select>
              </div>
              <div class="cc-field">
                <label class="cc-label">Prioridad</label>
                <div class="prio-selector">
                  <label class="prio-opt" :class="{active: detalleForm.prioridad_id == '1'}" style="--prio-bg:var(--col-prio-baja-bg);--prio-text:var(--col-prio-baja-text)">
                    <input type="radio" name="detallePrioridad" value="1" v-model="detalleForm.prioridad_id" hidden>
                    <span>Baja</span>
                  </label>
                  <label class="prio-opt" :class="{active: detalleForm.prioridad_id == '2'}" style="--prio-bg:var(--col-prio-media-bg);--prio-text:var(--col-prio-media-text)">
                    <input type="radio" name="detallePrioridad" value="2" v-model="detalleForm.prioridad_id" hidden>
                    <span>Media</span>
                  </label>
                  <label class="prio-opt" :class="{active: detalleForm.prioridad_id == '3'}" style="--prio-bg:var(--col-prio-alta-bg);--prio-text:var(--col-prio-alta-text)">
                    <input type="radio" name="detallePrioridad" value="3" v-model="detalleForm.prioridad_id" hidden>
                    <span>Alta</span>
                  </label>
                </div>
              </div>
            </div>
            <div class="crear-row-2">
              <div class="cc-field">
                <label class="cc-label">Solicitud de aprobación</label>
                <input value="—" class="cc-input" disabled>
              </div>
              <div class="cc-field">
                <label class="cc-label">Estado</label>
                <select v-model="detalleForm.estado_id" class="cc-input">
                  <option v-for="e in detalleEstados" :key="e.id" :value="e.id">{{ e.nombre }}</option>
                </select>
              </div>
            </div>
            <div class="cc-field">
              <label class="cc-label">&#9998; Descripción</label>
              <div id="quill-solucion"></div>
            </div>
            <div>
              <div class="file-hint">Archivos adjuntos (puede seleccionar varios archivos)</div>
              <div class="file-label-row">Seleccione El archivo Aquí</div>
              <div class="file-row">
                <label class="file-btn-label">
                  Seleccionar Archivo
                  <input type="file" multiple style="display:none" @change="detalleOnFileChange">
                </label>
                <span class="file-names">{{ detalleFileNames || 'No se ha seleccionado ningún archivo...' }}</span>
              </div>
            </div>
            <div v-if="detalleMsgError" style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:.65rem 1rem;font-size:.875rem">{{ detalleMsgError }}</div>
            <div v-if="detalleMsgOk"    style="background:#dcfce7;color:#15803d;border-radius:8px;padding:.65rem 1rem;font-size:.875rem">{{ detalleMsgOk }}</div>
            <button type="submit" class="btn-crear" :disabled="detalleGuardando">
              {{ detalleGuardando ? 'Guardando...' : 'Guardar y publicar' }}
            </button>
          </form>
        </template>

        <template v-else>
          <div style="display:flex;gap:.5rem;margin-top:.5rem">
            <span :class="'badge badge-'+detalleEstadoColor(detalleTicket.estado)">{{ detalleTicket.estado }}</span>
            <span :class="'badge badge-'+detallePrioridadColor(detalleTicket.prioridad)">{{ detalleTicket.prioridad }}</span>
          </div>
          <p style="font-size:.8rem;color:var(--text-secondary);margin-top:1rem">
            Creado {{ formatDate(detalleTicket.created_at) }}
            · Asignado a {{ detalleTicket.admin_nombre || 'Sin asignar' }}
          </p>
        </template>

        <div class="section-title" style="margin-top:1.5rem">Historial</div>
        <div class="timeline">
          <div v-for="item in detalleTimeline" :key="item.tipo + '-' + item.id + '-' + item.created_at" class="tl-item" :class="'tl-' + item.tipo">
            <div class="tl-icon">
              <i v-if="item.tipo === 'estado'" class="fa-solid fa-arrows-spin"></i>
              <i v-else-if="item.tipo === 'respuesta'" class="fa-solid fa-reply"></i>
              <i v-else class="fa-solid fa-paperclip"></i>
            </div>
            <div class="tl-body">
              <div class="tl-header">
                <span class="tl-user">{{ item.usuario_nombre || 'Sistema' }}</span>
                <span class="tl-date">{{ formatDate(item.created_at) }}</span>
              </div>
              <template v-if="item.tipo === 'estado'">
                <div class="tl-estado">
                  <span v-if="item.estado_anterior" :class="'badge badge-' + detalleEstadoColor(item.estado_anterior)">{{ item.estado_anterior }}</span>
                  <span v-if="item.estado_anterior" class="tl-arrow">&rarr;</span>
                  <span :class="'badge badge-' + detalleEstadoColor(item.estado_nuevo)">{{ item.estado_nuevo }}</span>
                </div>
                <div v-if="item.contenido && item.contenido !== '<p><br></p>'" class="tl-nota" v-html="item.contenido"></div>
              </template>
              <div v-else-if="item.tipo === 'respuesta'" class="tl-respuesta" v-html="item.contenido"></div>
              <div v-else-if="item.tipo === 'adjunto'" class="tl-adjunto">
                <i class="fa-solid fa-paperclip"></i>
                <a :href="'download.php?id=' + item.id" target="_blank">{{ item.nombre_original }}</a>
                <span class="det-adj-size">({{ formatBytes(item.tamanio_bytes) }})</span>
              </div>
            </div>
          </div>
          <div v-if="detalleTimeline.length === 0" style="color:var(--text-secondary);font-size:13px;padding:1rem 0;text-align:center">Sin historial</div>
        </div>

        <template v-if="detalleIsAdmin">
          <div class="section-title" style="margin-top:1rem">Responder</div>
          <div id="quill-respuesta"></div>
          <div v-if="detalleRespError" style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:.65rem 1rem;font-size:.875rem;margin-top:.5rem">{{ detalleRespError }}</div>
          <div v-if="detalleRespOk"    style="background:#dcfce7;color:#15803d;border-radius:8px;padding:.65rem 1rem;font-size:.875rem;margin-top:.5rem">{{ detalleRespOk }}</div>
          <button @click="detalleEnviarRespuesta" class="btn-crear" style="margin-top:.5rem" :disabled="detalleRespGuardando">
            {{ detalleRespGuardando ? 'Enviando...' : 'Enviar respuesta' }}
          </button>
        </template>

        <template v-if="!detalleIsAdmin">
          <div class="section-title" style="margin-top:1rem">Adjuntar archivos</div>
          <div>
            <div class="file-hint">Archivos adjuntos (puede seleccionar varios archivos)</div>
            <div class="file-label-row">Seleccione El archivo Aquí</div>
            <div class="file-row">
              <label class="file-btn-label">
                Seleccionar Archivo
                <input type="file" multiple style="display:none" @change="detalleOnFileChangeUser">
              </label>
              <span class="file-names">{{ detalleUserFileNames || 'No se ha seleccionado ningún archivo...' }}</span>
            </div>
            <button @click="detalleSubirArchivosUsuario" class="btn-crear" style="margin-top:.5rem" :disabled="detalleUserUploading">
              {{ detalleUserUploading ? 'Subiendo...' : 'Subir archivos' }}
            </button>
          </div>
        </template>

      </div>
    </div>

</div><!-- /dashApp -->
        </div><!-- /.dash-content -->
    </div><!-- /.dash-body-inner -->
</div><!-- /.dash-wrapper -->

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Debounced server-side search (removed)

    // Sidebar toggle (collapse on desktop, off-canvas on mobile)
    var sidebar = document.querySelector('.dash-sidebar');
    var sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebar && sidebarToggle) {
        function closeSidebarMobile() {
            sidebar.classList.remove('mobile-open');
            var bd = document.querySelector('.sidebar-backdrop');
            if (bd) bd.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
        function toggleSidebar() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('mobile-open');
                document.body.classList.toggle('sidebar-open');
                var backdrop = document.querySelector('.sidebar-backdrop');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'sidebar-backdrop';
                    document.body.appendChild(backdrop);
                    backdrop.addEventListener('click', closeSidebarMobile);
                }
                backdrop.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                var isCollapsed = sidebar.classList.contains('collapsed');
                document.querySelector('.dash-body-inner')?.classList.toggle('sidebar-collapsed', isCollapsed);
                localStorage.setItem('sidebar_collapsed', isCollapsed);
            }
        }

        var saved = localStorage.getItem('sidebar_collapsed');
        if (saved === 'true') {
            sidebar.classList.add('collapsed');
            document.querySelector('.dash-body-inner')?.classList.add('sidebar-collapsed');
        }
        sidebarToggle.addEventListener('click', toggleSidebar);

        var sidebarCloseBtn = document.getElementById('sidebarClose');
        if (sidebarCloseBtn) {
            sidebarCloseBtn.addEventListener('click', closeSidebarMobile);
        }

        window.addEventListener('resize', function () {
            if (window.innerWidth > 768 && sidebar.classList.contains('mobile-open')) {
                closeSidebarMobile();
            }
        });
    }
});
</script>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="assets/js/api.js"></script>
<script src="assets/js/auth.js"></script>
<script>
const TEMPLATE_DESARROLLO = '<p><strong>Que tipo de Desarrollo necesita:</strong></p><p><br></p><p>Programacion Pagina Web / Aplicacion / Sharepoint - Forms-PowerApps-Power Automate:</p><p><br></p><p>Quiere automatizar procesos Si / No:</p><p><br></p><p>Areas que puede ayudar el aplicativo:</p><p><br></p><p>Quien sera los administradores:</p><p><br></p><p>Quien sera los usuarios clave:</p><p><br></p><p><em>Nota: Si tiene alguna plantilla o documento que quieren implementar por favor adjuntarla</em></p><p>Descripcion Completa:</p>';

const ESTADO_COLORES    = {1:'en-espera',2:'en-proceso',3:'esperando-respuesta',4:'contestado',5:'reabierto',6:'reprogramado',7:'atendido',8:'cerrado',9:'rechazado'};
const PRIORIDAD_COLORES = {1:'green',2:'yellow',3:'orange'};
const PRIORIDAD_IDS     = {'Baja':1,'Media':2,'Alta':3};
const ESTADO_IDS = {'En espera':1,'En proceso':2,'Esperando respuesta':3,'Contestado':4,'Reabierto':5,'Reprogramado':6,'Atendido':7,'Cerrado':8,'Rechazado':9};

function sidebarSetActive(label) {
  document.querySelectorAll('.dash-nav-item').forEach(function(el) {
    el.classList.remove('active');
    if (el.textContent.trim().includes(label)) el.classList.add('active');
  });
}

const app = Vue.createApp({
  data() {
    return {
      currentView: 'list',

      // --- Crear ---
      crearUsuario: null,
      crearIsAdmin: false,
      crearForm: {
        descripcion: '', celular: '', tipo_solicitud_id: '', lugar_incidencia_id: '',
        linea_negocio_id: '', sitio_id: '', prioridad_id: '', usuario_id: '',
        admin_id: '', asunto_id: '', estado_id: '',
      },
      crearAdminForm: { tipo_asunto_id: '' },
      crearSitios: [], crearLineas: [], crearTiposSolicitud: [], crearLugarIncidencia: [],
      crearTiposAsunto: [], crearAsuntos: [], crearUsuarios: [],
      crearUsuarioSeleccionado: null,
      crearFileNames: '', crearFiles: [],
      crearLoading: false, crearError: '', crearSuccess: false,
      crearQuill: null,

      // --- Detalle ---
      detalleTicketId: null,
      detalleTicket: null, detalleLoading: false,
      detalleIsAdmin: false,
      detalleAdmins: [], detalleTiposAsunto: [], detalleAsuntos: [],
      detalleAdminForm: { tipo_asunto_id: '' },
      detalleForm: { admin_id: '', estado_id: '', asunto_id: '', prioridad_id: '' },
      detalleEstados: [
        {id:1,nombre:'En espera'},{id:2,nombre:'En proceso'},{id:3,nombre:'Esperando respuesta'},
        {id:4,nombre:'Contestado'},{id:5,nombre:'Reabierto'},{id:6,nombre:'Reprogramado'},
        {id:7,nombre:'Atendido'},{id:8,nombre:'Cerrado'},{id:9,nombre:'Rechazado'},
      ],
      detalleQuill: null, detalleQuillResp: null,
      detalleFileNames: '', detalleGuardando: false,
      detalleMsgError: '', detalleMsgOk: '',
      detalleTimeline: [], detalleAdjuntos: [],
      detalleRespGuardando: false, detalleRespError: '', detalleRespOk: '',
      detalleUserFileNames: '', detalleUserFiles: [], detalleUserUploading: false,
    };
  },
  created() {
    var params = new URLSearchParams(location.search);
    if (params.get('view') === 'crear') {
      this.currentView = 'crear';
    } else if (params.get('view') === 'detalle') {
      var id = params.get('id');
      if (id) {
        this.detalleTicketId = id;
        this.currentView = 'detalle';
      }
    }
  },
  mounted() {
    if (this.currentView === 'crear') sidebarSetActive('Crear Ticket');
    else if (this.currentView === 'detalle') sidebarSetActive('Total Tickets');
  },
  watch: {
    currentView(view) {
      if (view === 'crear') {
        var self = this;
        this.$nextTick(function() { self.crearInit(); });
      }
      if (view === 'detalle' && this.detalleTicketId) {
        this.$nextTick(function() { this.detalleCargar(); }.bind(this));
      }
    },
    detalleTicketId(id) {
      if (id && this.currentView === 'detalle') {
        var self = this;
        this.$nextTick(function() { self.detalleCargar(); });
      }
    },
    'crearForm.tipo_solicitud_id'(id) {
      if (!this.crearQuill) return;
      if (id == 4) {
        this.crearCargarPlantillaDesarrollo();
      } else if (!this.crearIsAdmin || this.crearAdminForm.tipo_asunto_id != 9) {
        this.crearQuill.setText('');
      }
    },
    'crearAdminForm.tipo_asunto_id'(id) {
      if (!this.crearQuill || !this.crearIsAdmin) return;
      if (id == 9) {
        this.crearCargarPlantillaDesarrollo();
      } else if (this.crearForm.tipo_solicitud_id != 4) {
        this.crearQuill.setText('');
      }
    },
  },
  methods: {
    // --- Navigation ---
    showList() {
      if (this.crearSuccess || this.detalleMsgOk) {
        window.location.href = window.location.pathname;
        return;
      }
      this.currentView = 'list';
      this.detalleTicketId = null;
      this.detalleTicket = null;
      this.crearSuccess = false;
      this.crearQuill = null;
      this.detalleQuill = null;
      this.detalleQuillResp = null;
      sidebarSetActive('Total Tickets');
    },
    showCrear() {
      this.crearResetForm();
      this.currentView = 'crear';
    },
    showDetalle(id) {
      this.detalleTicket = null;
      this.detalleTimeline = [];
      this.detalleAdjuntos = [];
      this.detalleQuill = null;
      this.detalleQuillResp = null;
      this.detalleMsgOk = '';
      this.detalleMsgError = '';
      this.detalleRespOk = '';
      this.detalleRespError = '';
      this.detalleFileNames = '';
      this.detalleUserFileNames = '';
      this.detalleUserFiles = [];
      this.detalleTicketId = id;
      this.currentView = 'detalle';
    },

    // ==================== CREAR ====================
    crearResetForm() {
      var u = Auth.getUsuario();
      this.crearUsuario = u;
      this.crearIsAdmin = Auth.isAdmin();
      this.crearForm = {
        descripcion: '', celular: '', tipo_solicitud_id: '', lugar_incidencia_id: '',
        linea_negocio_id: '', sitio_id: '', prioridad_id: '', usuario_id: '',
        admin_id: '', asunto_id: '', estado_id: '',
      };
      this.crearAdminForm = { tipo_asunto_id: '' };
      this.crearUsuarioSeleccionado = null;
      this.crearFileNames = ''; this.crearFiles = [];
      this.crearLoading = false; this.crearError = ''; this.crearSuccess = false;
      this.crearQuill = null;
    },
    async crearInit() {
      this.crearResetForm();
      var u = Auth.getUsuario();
      this.crearUsuario = u;
      this.crearIsAdmin = Auth.isAdmin();

      var [sitiosRes, lineasRes, tiposSolRes, lugIncRes] = await Promise.allSettled([
        api.catalogos.sitios(), api.catalogos.lineas(), api.catalogos.tiposSolicitud(), api.catalogos.lugarIncidencia(),
      ]);
      this.crearSitios         = sitiosRes.value?.data  ?? [];
      this.crearLineas         = lineasRes.value?.data  ?? [];
      this.crearTiposSolicitud = tiposSolRes.value?.data ?? [];
      this.crearLugarIncidencia = lugIncRes.value?.data ?? [];

      if (!this.crearIsAdmin) {
        if (u.sitio_id)         this.crearForm.sitio_id         = u.sitio_id;
        if (u.linea_negocio_id) this.crearForm.linea_negocio_id = u.linea_negocio_id;
      } else {
        var [tiposAsuntoRes, usuariosRes] = await Promise.allSettled([
          api.catalogos.tiposAsunto(), api.catalogos.usuarios({rol: 2}),
        ]);
        this.crearTiposAsunto = tiposAsuntoRes.value?.data ?? [];
        this.crearUsuarios    = usuariosRes.value?.data    ?? [];
      }

      this.$nextTick(function() { this.crearInitQuill(); }.bind(this));
    },
    crearCargarPlantillaDesarrollo() {
      if (!this.crearQuill) return;
      var txt = this.crearQuill.root.textContent.trim();
      if (!txt || txt === 'Describe el problema con detalle...') {
        this.crearQuill.clipboard.dangerouslyPasteHTML(TEMPLATE_DESARROLLO);
      }
    },
    crearInitQuill() {
      var editorId = this.crearIsAdmin ? 'quill-admin' : 'quill-usuario';
      var el = document.getElementById(editorId);
      if (el && !this.crearQuill) {
        this.crearQuill = new Quill(el, {
          theme: 'snow',
          placeholder: 'Describe el problema con detalle...',
          modules: { toolbar: [['bold','italic','underline','strike'], [{list:'ordered'},{list:'bullet'}], [{align:[]}], ['link']] },
        });
        if (this.crearForm.tipo_solicitud_id == 4 || (this.crearIsAdmin && this.crearAdminForm.tipo_asunto_id == 9)) {
          this.crearCargarPlantillaDesarrollo();
        }
      }
    },
    crearOnUsuarioChange() {
      this.crearUsuarioSeleccionado = this.crearUsuarios.find(function(u) { return u.id == this.crearForm.usuario_id; }.bind(this)) ?? null;
      if (this.crearUsuarioSeleccionado) {
        if (this.crearUsuarioSeleccionado.sitio_id)         this.crearForm.sitio_id         = this.crearUsuarioSeleccionado.sitio_id;
        if (this.crearUsuarioSeleccionado.linea_negocio_id) this.crearForm.linea_negocio_id = this.crearUsuarioSeleccionado.linea_negocio_id;
      }
    },
    async crearOnTipoAsuntoChange() {
      this.crearForm.asunto_id = '';
      this.crearAsuntos = [];
      if (!this.crearAdminForm.tipo_asunto_id) return;
      var res = await api.catalogos.asuntos(this.crearAdminForm.tipo_asunto_id);
      this.crearAsuntos = res?.data ?? [];
    },
    crearOnAsuntoChange() {
      var asunto = this.crearAsuntos.find(function(a) { return a.id == this.crearForm.asunto_id; }.bind(this));
      if (asunto?.prioridad_default_id) this.crearForm.prioridad_id = String(asunto.prioridad_default_id);
    },
    crearOnFileChange(e) {
      this.crearFiles = Array.from(e.target.files);
      this.crearFileNames = this.crearFiles.map(function(f) { return f.name; }).join(', ');
    },
    async crearSubmit() {
      this.crearError = ''; this.crearLoading = true;
      try {
        if (this.crearQuill) this.crearForm.descripcion = this.crearQuill.root.innerHTML;

        var required = this.crearIsAdmin
          ? ['usuario_id', 'linea_negocio_id', 'sitio_id', 'admin_id', 'tipo_solicitud_id', 'estado_id']
          : ['tipo_solicitud_id', 'linea_negocio_id', 'sitio_id'];

        for (var i = 0; i < required.length; i++) {
          if (!this.crearForm[required[i]] || this.crearForm[required[i]] === '') {
            this.crearError = 'Completa todos los campos obligatorios';
            this.crearLoading = false; return;
          }
        }

        if (this.crearIsAdmin) {
          if (!this.crearAdminForm.tipo_asunto_id) { this.crearError = 'Selecciona un Tipo de asunto'; this.crearLoading = false; return; }
          if (!this.crearForm.asunto_id) { this.crearError = 'Selecciona un Asunto'; this.crearLoading = false; return; }
        }

        if (this.crearForm.tipo_solicitud_id == 1 && (!this.crearForm.lugar_incidencia_id || this.crearForm.lugar_incidencia_id === '')) {
          this.crearError = 'Selecciona un Lugar de incidencia'; this.crearLoading = false; return;
        }

        if (!this.crearForm.descripcion || this.crearForm.descripcion === '<p><br></p>') {
          this.crearError = 'La descripción es obligatoria'; this.crearLoading = false; return;
        }

        if (this.crearForm.tipo_solicitud_id == 4) {
          var text = this.crearQuill ? this.crearQuill.root.textContent : this.crearForm.descripcion;
          var labels = [
            'Programacion Pagina Web / Aplicacion / Sharepoint - Forms-PowerApps-Power Automate:',
            'Quiere automatizar procesos Si / No:', 'Areas que puede ayudar el aplicativo:',
            'Quien sera los administradores:', 'Quien sera los usuarios clave:', 'Descripcion Completa:',
          ];
          for (var j = 0; j < labels.length; j++) {
            var idx = text.indexOf(labels[j]);
            if (idx === -1) continue;
            var after = text.substring(idx + labels[j].length, idx + labels[j].length + 200).trim();
            if (!after || after.startsWith(labels[j + 1]) || after.startsWith('Nota:')) {
              this.crearError = 'Completa el campo: ' + labels[j].replace(':', '');
              this.crearLoading = false; return;
            }
          }
        }

        var payload = Object.assign({}, this.crearForm);
        Object.keys(payload).forEach(function(k) { if (payload[k] === '' || payload[k] === null) delete payload[k]; });

        var res = await api.tickets.create(payload);
        if (res?.success && this.crearFiles.length > 0) {
          var ticketId = res.data?.id;
          if (ticketId) {
            for (var k = 0; k < this.crearFiles.length; k++) {
              try { await api.upload.subir(ticketId, this.crearFiles[k]); } catch (e) { console.error('Error subiendo archivo', e); }
            }
          }
        }
        this.crearLoading = false;
        if (res?.success) {
          this.crearSuccess = true;
        } else {
          this.crearError = res?.message || 'Error al crear el ticket';
        }
      } catch (e) {
        this.crearLoading = false;
        this.crearError = 'Error de conexión: ' + (e.message || 'intente de nuevo');
        console.error(e);
      }
    },

    // ==================== DETALLE ====================
    async detalleCargar() {
      var usuario = Auth.getUsuario();
      this.detalleIsAdmin = Auth.isAdmin();

      this.detalleLoading = true;
      var id = this.detalleTicketId;
      if (!id) { this.detalleLoading = false; return; }

      var res = await api.tickets.get(id);
      this.detalleTicket = res?.data ?? null;
      this.detalleLoading = false;
      if (!this.detalleTicket) return;

      this.detalleForm.estado_id    = ESTADO_IDS[this.detalleTicket.estado] ?? 1;
      this.detalleForm.admin_id     = this.detalleTicket.admin_id ?? '';
      this.detalleForm.asunto_id    = this.detalleTicket.asunto_id ?? '';
      this.detalleForm.prioridad_id = String(PRIORIDAD_IDS[this.detalleTicket.prioridad] ?? 2);

      this.detalleCargarTimeline();
      this.detalleCargarAdjuntos();

      if (this.detalleIsAdmin) {
        var [admRes, taRes] = await Promise.allSettled([
          api.catalogos.usuarios({rol:2}), api.catalogos.tiposAsunto(),
        ]);
        this.detalleAdmins     = admRes.value?.data ?? [];
        this.detalleTiposAsunto = taRes.value?.data ?? [];

        if (this.detalleForm.asunto_id) {
          var asuRes = await api.catalogos.asuntos();
          var allAsuntos = asuRes?.data ?? [];
          var match = allAsuntos.find(function(a) { return a.id == this.detalleForm.asunto_id; }.bind(this));
          if (match) {
            this.detalleAdminForm.tipo_asunto_id = match.tipo_asunto_id;
            this.detalleAsuntos = allAsuntos.filter(function(a) { return a.tipo_asunto_id == match.tipo_asunto_id; });
          }
        }

        this.$nextTick(function() {
          var el = document.getElementById('quill-solucion');
          if (el && !this.detalleQuill) {
            this.detalleQuill = new Quill(el, {
              theme: 'snow', placeholder: 'Agregar solución o comentario...',
              modules: { toolbar: [['bold','italic','underline','strike'], [{list:'ordered'},{list:'bullet'}], [{align:[]}], ['link']] },
            });
          }
          var elResp = document.getElementById('quill-respuesta');
          if (elResp && !this.detalleQuillResp) {
            this.detalleQuillResp = new Quill(elResp, {
              theme: 'snow', placeholder: 'Escribe tu respuesta...',
              modules: { toolbar: [['bold','italic','underline','strike'], [{list:'ordered'},{list:'bullet'}], ['link']] },
            });
          }
        }.bind(this));
      }
    },
    async detalleCargarTimeline() {
      if (!this.detalleTicket) return;
      var res = await api.tickets.timeline(this.detalleTicket.id);
      this.detalleTimeline = res?.data ?? [];
    },
    async detalleCargarAdjuntos() {
      if (!this.detalleTicket) return;
      var res = await api.tickets.timeline(this.detalleTicket.id);
      if (res?.data) this.detalleAdjuntos = res.data.filter(function(i) { return i.tipo === 'adjunto'; });
    },
    detalleOnTipoAsuntoChange() {
      this.detalleForm.asunto_id = '';
      this.detalleAsuntos = [];
      if (!this.detalleAdminForm.tipo_asunto_id) return;
      api.catalogos.asuntos(this.detalleAdminForm.tipo_asunto_id).then(function(r) { this.detalleAsuntos = r?.data ?? []; }.bind(this));
    },
    detalleOnAsuntoChange() {
      var asunto = this.detalleAsuntos.find(function(a) { return a.id == this.detalleForm.asunto_id; }.bind(this));
      if (asunto?.prioridad_default_id) this.detalleForm.prioridad_id = String(asunto.prioridad_default_id);
    },
    async detalleGuardar() {
      this.detalleMsgError = ''; this.detalleMsgOk = ''; this.detalleGuardando = true;
      var promesas = [];
      var estadoOriginal = ESTADO_IDS[this.detalleTicket.estado];
      var prioOriginal   = PRIORIDAD_IDS[this.detalleTicket.prioridad] ?? '';
      var estadoChanged  = this.detalleForm.estado_id && this.detalleForm.estado_id !== estadoOriginal;
      var prioChanged    = this.detalleForm.prioridad_id && this.detalleForm.prioridad_id != prioOriginal;
      if (estadoChanged || prioChanged) {
        var nota = this.detalleQuill ? this.detalleQuill.root.innerHTML : '';
        var body = { estado_id: this.detalleForm.estado_id, nota: nota };
        if (prioChanged) body.prioridad_id = this.detalleForm.prioridad_id;
        promesas.push(api.tickets.updateEstado(this.detalleTicket.id, body));
      }
      var adminOriginal = this.detalleTicket.admin_id ?? '';
      if (this.detalleForm.admin_id && this.detalleForm.admin_id != adminOriginal) {
        promesas.push(api.tickets.asignar(this.detalleTicket.id, { admin_id: this.detalleForm.admin_id }));
      }
      var asuntoOriginal = this.detalleTicket.asunto_id ?? '';
      if (this.detalleForm.asunto_id && this.detalleForm.asunto_id != asuntoOriginal) {
        promesas.push(api.tickets.updateAsunto(this.detalleTicket.id, { asunto_id: this.detalleForm.asunto_id }));
      }
      var fileInput = document.querySelector('#dashApp input[type="file"][multiple]');
      if (fileInput?.files?.length) {
        for (var i = 0; i < fileInput.files.length; i++) {
          promesas.push(api.upload.subir(this.detalleTicket.id, fileInput.files[i]));
        }
      }
      var resultados = await Promise.all(promesas);
      this.detalleGuardando = false;
      if (resultados.every(function(r) { return r?.success !== false; })) {
        this.detalleMsgOk = 'Guardado correctamente';
        var res2 = await api.tickets.get(this.detalleTicket.id);
        this.detalleTicket = res2?.data ?? this.detalleTicket;
        this.detalleCargarTimeline();
        this.detalleCargarAdjuntos();
      } else {
        this.detalleMsgError = 'Ocurrió un error al guardar';
      }
    },
    async detalleEnviarRespuesta() {
      this.detalleRespError = ''; this.detalleRespOk = ''; this.detalleRespGuardando = true;
      var contenido = this.detalleQuillResp ? this.detalleQuillResp.root.innerHTML : '';
      if (!contenido || contenido === '<p><br></p>') {
        this.detalleRespError = 'Escribe una respuesta'; this.detalleRespGuardando = false; return;
      }
      var res = await api.tickets.responder(this.detalleTicket.id, { contenido: contenido });
      if (res?.success) {
        this.detalleRespOk = 'Respuesta enviada';
        if (this.detalleQuillResp) this.detalleQuillResp.root.innerHTML = '';
        this.detalleCargarTimeline();
      } else {
        this.detalleRespError = res?.message || 'Error al enviar';
      }
      this.detalleRespGuardando = false;
    },
    detalleOnFileChange(e) {
      var files = Array.from(e.target.files);
      this.detalleFileNames = files.map(function(f) { return f.name; }).join(', ');
    },
    detalleOnFileChangeUser(e) {
      this.detalleUserFiles = Array.from(e.target.files);
      this.detalleUserFileNames = this.detalleUserFiles.map(function(f) { return f.name; }).join(', ');
    },
    async detalleSubirArchivosUsuario() {
      if (!this.detalleUserFiles.length) return;
      this.detalleUserUploading = true;
      for (var i = 0; i < this.detalleUserFiles.length; i++) {
        await api.upload.subir(this.detalleTicket.id, this.detalleUserFiles[i]);
      }
      this.detalleUserUploading = false;
      this.detalleUserFiles = [];
      this.detalleUserFileNames = '';
      this.detalleCargarAdjuntos();
      this.detalleCargarTimeline();
    },
    detalleEstadoColor(n) {
      var id = ESTADO_IDS[n];
      return id ? ESTADO_COLORES[id] ?? 'gray' : 'gray';
    },
    detallePrioridadColor(n) { return PRIORIDAD_COLORES[PRIORIDAD_IDS[n]] ?? 'gray'; },
    formatDate(d)     { return new Date(d).toLocaleDateString('es-CO', {day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}); },
    formatBytes(b) {
      if (!b) return '';
      var units = ['B','KB','MB','GB'];
      var i = 0;
      var size = b;
      while (size >= 1024 && i < units.length - 1) { size /= 1024; i++; }
      return size.toFixed(1) + ' ' + units[i];
    },
  },
});

var vm = app.mount('#dashApp');

// Global functions for outside Vue access
window.appShowCrear = function() {
  vm.showCrear();
  sidebarSetActive('Crear Ticket');
};
window.appShowDetalle = function(id) {
  vm.showDetalle(id);
  sidebarSetActive('Total Tickets');
};
window.appShowList = function() {
  vm.showList();
  sidebarSetActive('Total Tickets');
};
</script>
</body>
</html>
