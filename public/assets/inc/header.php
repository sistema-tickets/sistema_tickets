<?php
$userName = $userName ?? 'Usuario';
$usuario  = $usuario  ?? [];
$rolId    = (int)($usuario['rol_id'] ?? 0);
$rolLabel = match($rolId) { 3 => 'Superadmin', 2 => 'Coordinador', default => 'Usuario' };

$words    = array_filter(explode(' ', $userName));
$initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice($words, 0, 2))));
$basePath = $basePath ?? '';
?>
<header class="dash-header">

    <?php if ($showSidebarToggle ?? false): ?>
    <button class="sidebar-toggle" id="sidebarToggle" title="Mostrar/Ocultar sidebar">
        <i class="fa-solid fa-bars"></i>
    </button>
    <?php endif; ?>

    <img src="<?= $basePath ?>assets/img/logo.jpg" alt="Tabasco OC LLC" class="header-logo">

    <div style="flex:1"></div>

    <!-- Acciones + usuario -->
    <div class="dash-header-right" style="display:flex;align-items:center;gap:6px;">

        <a href="<?= $basePath ?>logout.php" class="header-user-chip" title="Cerrar sesión">
            <span class="header-user-avatar"><?= htmlspecialchars($initials) ?></span>
            <div class="header-user-text">
                <span class="header-user-name2"><?= htmlspecialchars($userName) ?></span>
                <span class="header-user-role2"><?= $rolLabel ?></span>
            </div>
        </a>
    </div>

</header>
