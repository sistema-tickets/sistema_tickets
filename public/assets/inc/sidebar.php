<?php
if (!isset($db)) $db = getDB();
$rol_id      = (int)($usuario['rol_id'] ?? 0);
$nombre      = htmlspecialchars($usuario['nombre'] ?? 'Usuario');
$currentPage = $currentPage ?? 'tickets';
$basePath    = $basePath ?? '';

// Conteo total de tickets para el badge
$sidebarTotal = 0;
try {
    $sqTotal = "SELECT COUNT(*) FROM tickets t";
    if ($rol_id === ROL_USUARIO) $sqTotal .= " WHERE t.usuario_id = " . (int)($usuario['id'] ?? 0);
    $sidebarTotal = (int)$db->query($sqTotal)->fetchColumn();
} catch (Exception $e) { /* ignorar */ }

// Conteo asignados a mí (solo admin/superadmin)
$asignadosAMi = 0;
if (in_array($rol_id, [ROL_ADMIN, ROL_SUPERADMIN])) {
    try {
        $sqMi = "SELECT COUNT(*) FROM tickets t WHERE t.admin_id = " . (int)($usuario['id'] ?? 0) . " AND t.estado_id NOT IN (7,8,9)";
        $asignadosAMi = (int)$db->query($sqMi)->fetchColumn();
    } catch (Exception $e) { /* ignorar */ }
}

// Turno AM/PM
$turno = (int)date('H') < 12 ? 'AM' : 'PM';

// Carga catálogos si no están disponibles (para los filtros del sidebar)
if (!isset($estados))     $estados     = $db->query("SELECT id, nombre FROM estados ORDER BY id")->fetchAll();
if (!isset($prioridades)) $prioridades = $db->query("SELECT id, nombre FROM prioridades ORDER BY orden")->fetchAll();
if (!isset($tipos))       $tipos       = $db->query("SELECT id, nombre FROM tipos_solicitud ORDER BY nombre")->fetchAll();
if (!isset($admins))      $admins      = $db->query("SELECT id, nombre FROM usuarios WHERE rol_id = 2 AND activo = 1 ORDER BY nombre")->fetchAll();

$afEstadoId    = (int)($_GET['af_estado'] ?? 0);
$afPrioridadId = (int)($_GET['af_prioridad'] ?? 0);
$afAdminId     = (int)($_GET['af_admin'] ?? 0);
$afTipoId      = (int)($_GET['af_tipo'] ?? 0);
$afFechaDesde  = trim($_GET['af_fd'] ?? '');
$afFechaHasta  = trim($_GET['af_fh'] ?? '');
$hasActiveFilter = ($afEstadoId > 0 || $afPrioridadId > 0 || $afAdminId !== 0 || $afTipoId > 0 || $afFechaDesde !== '' || $afFechaHasta !== '');
$q = htmlspecialchars(trim($_GET['q'] ?? ''));
$tab = $_GET['tab'] ?? 'all';

function sidebar_active(string $page, string $current): string {
    return $page === $current ? ' active' : '';
}
?>
<aside class="dash-sidebar">

    <!-- Botón cerrar (solo visible en mobile) -->
    <button class="sidebar-close-btn" id="sidebarClose" title="Cerrar sidebar">
        <i class="fa-solid fa-xmark"></i>
    </button>

    <!-- Logo / Brand -->
    <div class="sidebar-brand">
        <div class="sidebar-brand-info">
            <div class="sidebar-brand-name">Sistema de Tickets</div>
            <div class="sidebar-brand-sub">Centro de Soluciones TI</div>
        </div>
    </div>

    <!-- Resumen rápido -->
    <div class="sidebar-stats-mini">
        <span class="sidebar-stats-count"><?= $sidebarTotal ?> tickets</span>
        <span class="sidebar-stats-turno">Turno <?= $turno ?></span>
    </div>

    <nav class="dash-nav">

        <!-- ── HISTORIAL ── -->
        <span class="sidebar-section-label">Historial</span>

        <a href="<?= $basePath ?>index.php?tab=cerrados"
           class="dash-nav-item<?= ($tab === 'cerrados' && $currentPage === 'tickets') ? ' active' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Historial de tickets</span>
        </a>

        <div class="sidebar-divider"></div>

        <!-- ── MESA DE SERVICIO ── -->
        <span class="sidebar-section-label">Mesa de Servicio</span>

        <a href="<?= $basePath ?>index.php"
           class="dash-nav-item<?= $currentPage === 'tickets' && !$hasActiveFilter && $tab === 'all' ? ' active' : '' ?>">
            <i class="fa-solid fa-inbox"></i>
            <span>Total Tickets</span>
            <?php if ($sidebarTotal > 0): ?>
            <span class="nav-badge"><?= $sidebarTotal ?></span>
            <?php endif; ?>
        </a>

        <?php if (in_array($rol_id, [ROL_ADMIN, ROL_SUPERADMIN])): ?>
        <a href="<?= $basePath ?>index.php?af_admin=<?= (int)($usuario['id'] ?? 0) ?>"
           class="dash-nav-item<?= ($afAdminId === (int)($usuario['id'] ?? 0)) ? ' active' : '' ?>">
            <i class="fa-solid fa-user-check"></i>
            <span>Asignados a mí</span>
            <?php if ($asignadosAMi > 0): ?>
            <span class="nav-badge"><?= $asignadosAMi ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>


        <?php if (in_array($rol_id, [ROL_ADMIN, ROL_SUPERADMIN])): ?>
        <div class="sidebar-divider"></div>

        <!-- ── REPORTES ── -->
        <span class="sidebar-section-label">Reportes</span>

        <a href="<?= $basePath ?>pages/admin/informes.php"
           class="dash-nav-item<?= sidebar_active('informes', $currentPage) ?>">
            <i class="fa-solid fa-chart-bar"></i>
            <span>Informes</span>
        </a>
        <?php endif; ?>

        <?php if ($rol_id === ROL_SUPERADMIN): ?>
        <div class="sidebar-divider"></div>

        <!-- ── ADMINISTRACIÓN ── -->
        <span class="sidebar-section-label">Administración</span>

        <a href="<?= $basePath ?>pages/admin/usuarios.php"
           class="dash-nav-item<?= sidebar_active('usuarios', $currentPage) ?>">
            <i class="fa-solid fa-users"></i>
            <span>Usuarios y roles</span>
        </a>

        <a href="<?= $basePath ?>pages/admin/catalogos.php"
           class="dash-nav-item<?= sidebar_active('catalogos', $currentPage) ?>">
            <i class="fa-solid fa-gear"></i>
            <span>Configuración</span>
        </a>
        <?php endif; ?>

    </nav>

</aside>
