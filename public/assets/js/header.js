(function () {
    var u = null;
    try { u = Auth.getUsuario(); } catch (e) {}
    if (!u) return;

    var hasSidebar = !!document.querySelector('.sidebar');

    var h = document.createElement('header');
    h.className = 'dash-header';

    var html = '';
    if (hasSidebar) {
        html += '<button class="sidebar-toggle" id="sidebarToggle" title="Abrir menú">' +
                    '<i class="fa-solid fa-bars"></i>' +
                '</button>';
    }
    html += '<div class="dash-header-logo">' +
                '<img src="' + BASE_URL + '/assets/img/logo.jpg" alt="Logo">' +
            '</div>' +
            '<span class="dash-header-title">Sistema de Incidencia y Requerimiento</span>';

    // Only show user in header on pages without sidebar
    if (!hasSidebar) {
        html += '<div class="dash-header-right">' +
                    '<div class="user-wrap">' +
                        '<button class="user-btn" id="userBtn">' +
                            '<i class="fa-solid fa-user"></i>' +
                            '<span>' + (u.nombre || 'Usuario') + '</span>' +
                            '<i class="fa-solid fa-angle-down"></i>' +
                        '</button>' +
                        '<div class="user-dropdown" id="userDropdown">' +
                            '<div class="user-dropdown-header">' +
                                '<i class="fa-solid fa-user"></i>' +
                                '<span>' + (u.nombre || 'Usuario') + '</span>' +
                            '</div>' +
                            '<a href="' + BASE_URL + '/logout.php" class="user-dropdown-item">' +
                                '<i class="fa-solid fa-right-from-bracket"></i>' +
                                '<span>Cerrar sesión</span>' +
                            '</a>' +
                        '</div>' +
                    '</div>' +
                '</div>';
    }
    h.innerHTML = html;

    var app = document.getElementById('app');
    if (app) {
        app.parentNode.insertBefore(h, app);
    } else {
        document.body.insertBefore(h, document.body.firstChild);
    }

    // ── User dropdown (only on pages without sidebar) ──
    if (!hasSidebar) {
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
    }

    // ── Sidebar toggle (mobile off-canvas) ──
    var toggleBtn = document.getElementById('sidebarToggle');
    var sidebar   = document.querySelector('.sidebar');
    if (toggleBtn && sidebar) {
        function closeSidebar() {
            sidebar.classList.remove('mobile-open');
            var bd = document.querySelector('.sidebar-backdrop');
            if (bd) bd.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }

        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            if (window.innerWidth > 768) return;
            sidebar.classList.toggle('mobile-open');
            document.body.classList.toggle('sidebar-open');

            var backdrop = document.querySelector('.sidebar-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'sidebar-backdrop';
                document.body.appendChild(backdrop);
                backdrop.addEventListener('click', closeSidebar);
            }
            backdrop.classList.toggle('show');
        });
    }
})();
