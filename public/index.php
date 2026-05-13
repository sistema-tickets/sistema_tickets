<?php
require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/config/database.php';
require_once __DIR__ . '/../backend/config/constants.php';
require_once __DIR__ . '/../backend/middleware/Auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$usuario = Auth::usuario();
if (!$usuario) {
    header('Location: pages/login.html');
    exit;
}

$rol_id = (int) $usuario['rol_id'];
$nombre = $usuario['nombre'];
$email  = $usuario['email'];
$sitio_id = $usuario['sitio_id'];

$rol_nombre = $rol_id === ROL_ADMIN ? 'admin' : 'usuario';
$db = getDB();

// Build ticket query
$where  = ['1=1'];
$params = [];

if ($rol_id === ROL_USUARIO) {
    $where[] = 't.usuario_id = ?';
    $params[] = $usuario['id'];
}

$sql = "SELECT t.id, t.numero_ticket, t.descripcion, t.created_at, t.updated_at,
               e.nombre AS estado, p.nombre AS prioridad,
               u.nombre AS solicitante,
               a.nombre AS asignado_a,
               s.nombre AS sitio_nombre,
               ts.nombre AS tipo_solicitud
        FROM tickets t
        LEFT JOIN estados          e  ON e.id  = t.estado_id
        LEFT JOIN prioridades      p  ON p.id  = t.prioridad_id
        LEFT JOIN usuarios         u  ON u.id  = t.usuario_id
        LEFT JOIN usuarios         a  ON a.id  = t.admin_id
        LEFT JOIN sitios           s  ON s.id  = t.sitio_id
        LEFT JOIN tipos_solicitud  ts ON ts.id = t.tipo_solicitud_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY t.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

function badgeEstado(string $estado): string {
    return match ($estado) {
        'En espera'           => 'badge-espera',
        'En proceso'          => 'badge-proceso',
        'Esperando respuesta' => 'badge-espera',
        'Contestado'          => 'badge-resuelto',
        'Reabierto'           => 'badge-proceso',
        'Reprogramado'        => 'badge-proceso',
        'Atendido'            => 'badge-resuelto',
        'Cerrado'             => 'badge-cancelado',
        'Rechazado'           => 'badge-cancelado',
        default               => 'badge-espera',
    };
}

function prioridadClase(string $p): string {
    return match ($p) {
        'Baja'    => 'priority-baja',
        'Media'   => 'priority-media',
        'Alta'    => 'priority-alta',
        'Crítica' => 'priority-critica',
        default   => '',
    };
}

$totalTickets    = count($tickets);
$pendingTickets  = count(array_filter($tickets, fn($t) => $t['estado'] === 'En espera'));
$processTickets  = count(array_filter($tickets, fn($t) => $t['estado'] === 'En proceso'));
$resolvedTickets = count(array_filter($tickets, fn($t) => in_array($t['estado'], ['Atendido', 'Cerrado'])));
$highPriority    = count(array_filter($tickets, fn($t) => in_array($t['prioridad'], ['Alta', 'Crítica'])));
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
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons/css/regular-rounded.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
    <div class="layout">

        <!-- ===== SIDEBAR ===== -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="assets/img/logo.jpg" alt="Logo" class="sidebar-logo-img">
            </div>

            <nav class="sidebar-nav">
                <a href="pages/tickets/crear.html" class="nav-btn">
                    <span class="nav-icon"><i class="fi fi-rs-plus"></i></span>
                    Crear Ticket
                </a>
                <a href="pages/cuenta.html" class="nav-btn">
                    <span class="nav-icon"><i class="fi fi-rs-user"></i></span>
                    Mi cuenta
                </a>
            </nav>

            <div class="sidebar-divider"></div>

            <div class="filter-section">
                <p class="filter-heading">Filtros</p>
                <div class="filter-group">
                    <label>Estado</label>
                    <input type="text" id="filterEstado" placeholder="Buscar estado..." autocomplete="off">
                </div>
                <div class="filter-group">
                    <label>Asignado a</label>
                    <input type="text" id="filterAsignado" placeholder="Buscar responsable..." autocomplete="off">
                </div>
                <div class="filter-group">
                    <label>Prioridad</label>
                    <input type="text" id="filterPrioridad" placeholder="Baja, Media, Alta..." autocomplete="off">
                </div>
                <div class="filter-group">
                    <label>Tipo de solicitud</label>
                    <input type="text" id="filterTipo" placeholder="Incidencia, Desarrollo..." autocomplete="off">
                </div>
            </div>

            <button class="sidebar-refresh" onclick="location.reload()">
                <i class="fi fi-rs-reload"></i> Actualizar
            </button>
        </aside>

        <!-- ===== MAIN ===== -->
        <main class="main">

            <!-- Header -->
            <header class="page-header">
                <div class="header-info">
                    <p class="eyebrow">Panel de control — <?= $rol_nombre ?></p>
                    <h1><?= $rol_id === ROL_ADMIN ? 'Todos los tickets' : 'Mis tickets' ?></h1>
                    <p class="header-desc">
                        <?= $rol_id === ROL_ADMIN ? 'Revisa y gestiona todas las solicitudes del sistema.' : 'Revisa tus solicitudes y mantén el seguimiento.' ?>
                    </p>
                </div>
                <div class="header-actions">
                    <a href="logout.php" class="btn btn-outline">Cerrar sesión</a>
                </div>
            </header>

            <!-- Search & Quick Filters -->
            <section class="toolbar">
                <div class="search-box">
                    <span class="search-ico"><i class="fi fi-rs-search"></i></span>
                    <input id="searchInput" type="search" placeholder="Buscar ticket, solicitante, tipo..." autocomplete="off">
                </div>
                <div class="quick-filters">
                    <button class="chip active" data-quick="all">Todos</button>
                    <button class="chip" data-quick="pending">Pendiente</button>
                    <button class="chip" data-quick="process">En proceso</button>
                    <button class="chip" data-quick="high">Alta prioridad</button>
                </div>
            </section>

            <!-- Stats Row -->
            <section class="stats-row">
                <div class="stat-box">
                    <span class="stat-label">Tickets</span>
                    <strong class="stat-num"><?= $totalTickets ?></strong>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Pendientes</span>
                    <strong class="stat-num"><?= $pendingTickets ?></strong>
                </div>
                <div class="stat-box">
                    <span class="stat-label">En proceso</span>
                    <strong class="stat-num"><?= $processTickets ?></strong>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Resueltos</span>
                    <strong class="stat-num"><?= $resolvedTickets ?></strong>
                </div>
                <div class="stat-box">
                    <span class="stat-label">Alta prioridad</span>
                    <strong class="stat-num"><?= $highPriority ?></strong>
                </div>
            </section>

            <!-- Ticket List -->
            <section class="ticket-list" id="ticketList">

                <?php if (empty($tickets)): ?>
                    <div class="no-tickets">
                        <i class="fi fi-rs-inbox"></i>
                        <p>No hay tickets para mostrar</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($tickets as $t): ?>
                <article class="ticket-card"
                    data-estado="<?= strtolower($t['estado']) ?>"
                    data-prioridad="<?= strtolower($t['prioridad']) ?>"
                    data-tipo="<?= strtolower($t['tipo_solicitud'] ?? '') ?>"
                    data-asignado="<?= strtolower($t['asignado_a'] ?? '') ?>">
                    <div class="tc-left">
                        <div class="tc-top">
                            <span class="tc-id"><?= htmlspecialchars($t['numero_ticket'] ?? '#' . str_pad((string)$t['id'], 5, '0', STR_PAD_LEFT)) ?></span>
                            <span class="tc-badge <?= badgeEstado($t['estado']) ?>"><?= htmlspecialchars($t['estado']) ?></span>
                        </div>
                        <div class="tc-user"><?= htmlspecialchars($t['solicitante'] ?? '—') ?></div>
                        <div class="tc-meta"><?= htmlspecialchars($t['sitio_nombre'] ?? '') ?></div>
                    </div>
                    <div class="tc-mid">
                        <div class="tc-date-row">
                            <span class="tc-date-label">Creado</span>
                            <span><?= date('d M Y H:i', strtotime($t['created_at'])) ?></span>
                        </div>
                        <?php if ($t['updated_at'] && $t['updated_at'] !== $t['created_at']): ?>
                        <div class="tc-date-row">
                            <span class="tc-date-label">Actualizado</span>
                            <span><?= date('d M Y H:i', strtotime($t['updated_at'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="tc-right">
                        <?php if ($t['asignado_a']): ?>
                        <div class="tc-meta-row">
                            <span class="tc-attr-label">Asignado</span>
                            <span class="tc-avatar"><?= htmlspecialchars(mb_substr($t['asignado_a'], 0, 2)) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="tc-meta-row">
                            <span class="tc-attr-label">Prior.</span>
                            <span class="tc-attr-value <?= prioridadClase($t['prioridad']) ?>"><?= htmlspecialchars($t['prioridad']) ?></span>
                        </div>
                        <div class="tc-meta-row">
                            <span class="tc-attr-label">Tipo</span>
                            <span class="tc-attr-value"><?= htmlspecialchars($t['tipo_solicitud'] ?? '—') ?></span>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>

            </section>
        </main>
    </div>

    <script>
    // Filter by search input (client-side)
    document.addEventListener('DOMContentLoaded', function () {
        const cards  = document.querySelectorAll('.ticket-card');
        const search = document.getElementById('searchInput');
        const chips  = document.querySelectorAll('.chip');
        const estadoFilter = document.getElementById('filterEstado');
        const asignadoFilter = document.getElementById('filterAsignado');
        const prioridadFilter = document.getElementById('filterPrioridad');
        const tipoFilter = document.getElementById('filterTipo');

        function filterCards() {
            const q = (search.value || '').toLowerCase();
            const e = (estadoFilter.value || '').toLowerCase();
            const a = (asignadoFilter.value || '').toLowerCase();
            const p = (prioridadFilter.value || '').toLowerCase();
            const tp = (tipoFilter.value || '').toLowerCase();
            const activeChip = document.querySelector('.chip.active');
            const quick = activeChip ? activeChip.dataset.quick : 'all';

            cards.forEach(c => {
                const estado    = (c.dataset.estado || '').toLowerCase();
                const prioridad = (c.dataset.prioridad || '').toLowerCase();
                const tipo      = (c.dataset.tipo || '').toLowerCase();
                const asignado  = (c.dataset.asignado || '').toLowerCase();
                const text      = c.textContent.toLowerCase();

                let match = true;
                if (q && !text.includes(q)) match = false;
                if (e && !estado.includes(e)) match = false;
                if (a && !asignado.includes(a)) match = false;
                if (p && !prioridad.includes(p)) match = false;
                if (tp && !tipo.includes(tp)) match = false;

                if (quick === 'pending' && estado !== 'en espera') match = false;
                if (quick === 'process' && estado !== 'en proceso') match = false;
                if (quick === 'high' && prioridad !== 'alta' && prioridad !== 'crítica') match = false;

                c.classList.toggle('hidden', !match);
            });
        }

        search.addEventListener('input', filterCards);
        estadoFilter.addEventListener('input', filterCards);
        asignadoFilter.addEventListener('input', filterCards);
        prioridadFilter.addEventListener('input', filterCards);
        tipoFilter.addEventListener('input', filterCards);

        chips.forEach(chip => {
            chip.addEventListener('click', function () {
                chips.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
                filterCards();
            });
        });
    });
    </script>
</body>
</html>
