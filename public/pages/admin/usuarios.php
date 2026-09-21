<?php
require_once __DIR__ . '/../../../backend/config/app.php';
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/config/constants.php';
require_once __DIR__ . '/../../../backend/middleware/Auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$usuario = Auth::check();
$rol_id  = (int) $usuario['rol_id'];
$nombre  = $usuario['nombre'];

if ($rol_id !== ROL_SUPERADMIN) {
    header('Location: /sistema_tickets/public/index.php');
    exit;
}

$basePath    = '../../';
$currentPage = 'usuarios';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Usuarios — Sistema de Tickets</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/main.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="dash-body">
<div class="dash-wrapper">

  <?php
  $showSearch        = false;
  $showSidebarToggle = true;
  require __DIR__ . '/../../assets/inc/header.php';
  ?>

  <div class="dash-body-inner">

    <?php require __DIR__ . '/../../assets/inc/sidebar.php'; ?>

    <div class="dash-content">
      <div style="padding:1.5rem 2rem;flex:1">
        <div id="app" v-cloak>
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
            <h2 style="margin:0">Gestión de Usuarios</h2>
            <button @click="showModal=true" class="btn btn-primary">+ Nuevo Usuario</button>
          </div>
          <div class="card">
            <div v-if="loading" style="color:var(--color-muted)">Cargando...</div>
            <div v-else class="table-wrap">
              <table>
                <thead>
                  <tr><th>#</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Sitio</th><th>Último login</th><th>Estado</th><th>Acciones</th></tr>
                </thead>
                <tbody>
                  <tr v-for="u in usuarios" :key="u.id">
                    <td>{{ u.id }}</td>
                    <td>{{ u.nombre }}</td>
                    <td>{{ u.email }}</td>
                    <td><span :class="u.rol_nombre==='admin'?'badge badge-blue':'badge badge-gray'">{{ u.rol_nombre }}</span></td>
                    <td>{{ u.sitio_nombre || '—' }}</td>
                    <td>{{ u.ultimo_login ? formatDate(u.ultimo_login) : 'Nunca' }}</td>
                    <td><span :class="u.activo?'badge badge-green':'badge badge-red'">{{ u.activo?'Activo':'Inactivo' }}</span></td>
                    <td>
                      <button @click="editarUsuario(u)" class="btn-icon btn-icon-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                      <button @click="confirmarEliminar(u)" class="btn-icon btn-icon-del" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="pagination">
              <button class="page-btn" :disabled="page===1" @click="page--;load()">‹</button>
              <span style="font-size:.875rem">Página {{ page }} / {{ totalPages }}</span>
              <button class="page-btn" :disabled="page>=totalPages" @click="page++;load()">›</button>
            </div>
          </div>

          <!-- Modal nuevo usuario -->
          <div v-if="showModal" class="modal-overlay" @click.self="showModal=false">
            <div class="modal">
              <div class="modal-header">
                <span class="modal-title">Nuevo Usuario</span>
                <button class="modal-close" @click="showModal=false">✕</button>
              </div>
              <div v-if="formError" class="alert alert-error">{{ formError }}</div>
              <form @submit.prevent="crearUsuario">
                <div class="form-group">
                  <label class="form-label">Nombre</label>
                  <input v-model="form.nombre" class="form-control" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Email</label>
                  <input v-model="form.email" type="email" class="form-control" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Contraseña</label>
                  <input v-model="form.password" type="password" class="form-control" minlength="8" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Rol</label>
                  <select v-model="form.rol_id" class="form-control">
                    <option value="1">Usuario</option>
                    <option value="2">Administrador</option>
                  </select>
                </div>
                <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem">
                  <button type="button" @click="showModal=false" class="btn btn-outline">Cancelar</button>
                  <button type="submit" class="btn btn-primary" :disabled="formLoading">
                    {{ formLoading ? 'Guardando...' : 'Crear' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Modal editar usuario -->
          <div v-if="showEditModal" class="modal-overlay" @click.self="showEditModal=false">
            <div class="modal">
              <div class="modal-header">
                <span class="modal-title">Editar Usuario</span>
                <button class="modal-close" @click="showEditModal=false">✕</button>
              </div>
              <div v-if="editError" class="alert alert-error">{{ editError }}</div>
              <form @submit.prevent="actualizarUsuario">
                <div class="form-group">
                  <label class="form-label">Nombre</label>
                  <input v-model="editForm.nombre" class="form-control" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Email</label>
                  <input v-model="editForm.email" type="email" class="form-control" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Rol</label>
                  <select v-model="editForm.rol_id" class="form-control">
                    <option value="1">Usuario</option>
                    <option value="2">Administrador</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Estado</label>
                  <select v-model="editForm.activo" class="form-control">
                    <option :value="1">Activo</option>
                    <option :value="0">Inactivo</option>
                  </select>
                </div>
                <div style="display:flex;gap:.75rem;justify-content:flex-end;margin-top:.5rem">
                  <button type="button" @click="showEditModal=false" class="btn btn-outline">Cancelar</button>
                  <button type="submit" class="btn btn-primary" :disabled="editLoading">
                    {{ editLoading ? 'Guardando...' : 'Guardar cambios' }}
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Modal confirmar eliminación -->
          <div v-if="showDeleteModal" class="modal-overlay" @click.self="showDeleteModal=false">
            <div class="modal" style="max-width:400px">
              <div class="modal-header">
                <span class="modal-title">Desactivar usuario</span>
                <button class="modal-close" @click="showDeleteModal=false">✕</button>
              </div>
              <p style="margin:1rem 0;font-size:.9rem">
                ¿Estás seguro de desactivar a <strong>{{ eliminarUsuario?.nombre }}</strong>?
              </p>
              <div style="display:flex;gap:.75rem;justify-content:flex-end">
                <button @click="showDeleteModal=false" class="btn btn-outline">Cancelar</button>
                <button @click="eliminarUsuarioAction" class="btn btn-danger" :disabled="deleteLoading">
                  {{ deleteLoading ? 'Desactivando...' : 'Desactivar' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// ── Sidebar toggle + collapse ──────────────────────────────────────────
(function(){
    var btn = document.getElementById('sidebarToggle');
    var sb  = document.querySelector('.dash-sidebar');
    function closeMobile() {
        sb.classList.remove('mobile-open');
        var bd = document.querySelector('.sidebar-backdrop');
        if (bd) { bd.classList.remove('show'); bd.remove(); }
        document.body.classList.remove('sidebar-open');
    }
    function toggleSidebar() {
        if (window.innerWidth <= 768) {
            sb.classList.toggle('mobile-open');
            document.body.classList.toggle('sidebar-open');
            var bd = document.querySelector('.sidebar-backdrop');
            if (!bd) { bd = document.createElement('div'); bd.className = 'sidebar-backdrop'; document.body.appendChild(bd); bd.addEventListener('click', closeMobile); }
            bd.classList.toggle('show');
        } else {
            sb.classList.toggle('collapsed');
            localStorage.setItem('sidebar_collapsed', sb.classList.contains('collapsed'));
        }
    }
    if (btn && sb) {
        var saved = localStorage.getItem('sidebar_collapsed');
        if (saved === 'true') sb.classList.add('collapsed');
        btn.addEventListener('click', toggleSidebar);
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768 && sb.classList.contains('mobile-open')) closeMobile();
        });
    }
})();
</script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="../../assets/js/api.js"></script>
<script src="../../assets/js/auth.js"></script>
<script>
Vue.createApp({
  data() {
    return {
      usuarios: [], loading: true, page: 1, totalPages: 1,
      showModal: false, formLoading: false, formError: '',
      form: { nombre: '', email: '', password: '', rol_id: '1' },
      showEditModal: false, editLoading: false, editError: '',
      editForm: { id: null, nombre: '', email: '', rol_id: '1', activo: 1 },
      showDeleteModal: false, deleteLoading: false,
      eliminarUsuario: null,
    };
  },
  async mounted() {
    await this.load();
  },
  methods: {
    async load() {
      this.loading = true;
      const res = await api.usuarios.list({ page: this.page, limit: 20 });
      this.usuarios   = res?.data?.usuarios ?? [];
      this.totalPages = res?.data?.total_pages ?? 1;
      this.loading = false;
    },
    async crearUsuario() {
      this.formError = ''; this.formLoading = true;
      const res = await api.usuarios.create(this.form);
      this.formLoading = false;
      if (res?.success) {
        this.showModal = false;
        this.form = { nombre:'', email:'', password:'', rol_id:'1' };
        await this.load();
      } else {
        this.formError = res?.message || 'Error al crear usuario';
      }
    },
    editarUsuario(u) {
      this.editForm = {
        id: u.id,
        nombre: u.nombre,
        email: u.email,
        rol_id: String(u.rol_id),
        activo: u.activo,
      };
      this.editError = '';
      this.showEditModal = true;
    },
    async actualizarUsuario() {
      this.editError = ''; this.editLoading = true;
      const { id, ...data } = this.editForm;
      const res = await api.usuarios.update(id, data);
      this.editLoading = false;
      if (res?.success) {
        this.showEditModal = false;
        await this.load();
      } else {
        this.editError = res?.message || 'Error al actualizar usuario';
      }
    },
    confirmarEliminar(u) {
      this.eliminarUsuario = u;
      this.showDeleteModal = true;
    },
    async eliminarUsuarioAction() {
      if (!this.eliminarUsuario) return;
      this.deleteLoading = true;
      const res = await api.usuarios.delete(this.eliminarUsuario.id);
      this.deleteLoading = false;
      if (res?.success) {
        this.showDeleteModal = false;
        this.eliminarUsuario = null;
        await this.load();
      } else {
        alert(res?.message || 'Error al desactivar usuario');
      }
    },
    formatDate(d) { return new Date(d).toLocaleDateString('es-CO', {day:'2-digit',month:'short',year:'numeric'}); },
  }
}).mount('#app');

// ── Sidebar dropdowns ──
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.dd-wrap').forEach(function (wrap) {
        var btn = wrap.querySelector('.dash-nav-item');
        var dd  = wrap.querySelector('.dd-dropdown');
        var ch  = wrap.querySelector('.dd-chevron');
        if (!btn || !dd) return;
        btn.addEventListener('click', function (e) { e.stopPropagation(); dd.classList.toggle('open'); if (ch) ch.classList.toggle('open'); });
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.dd-wrap')) { document.querySelectorAll('.dd-dropdown').forEach(function (d) { d.classList.remove('open'); }); document.querySelectorAll('.dd-chevron').forEach(function (c) { c.classList.remove('open'); }); }
    });
});
</script>
</body>
</html>
