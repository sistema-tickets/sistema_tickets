<?php
require_once __DIR__ . '/../../../backend/config/app.php';
require_once __DIR__ . '/../../../backend/config/database.php';
require_once __DIR__ . '/../../../backend/config/constants.php';
require_once __DIR__ . '/../../../backend/middleware/Auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$usuario = Auth::check();
$rol_id  = (int) $usuario['rol_id'];
$nombre  = $usuario['nombre'];

// Only admins
if ($rol_id !== ROL_SUPERADMIN) {
    header('Location: /sistema_tickets/public/index.php');
    exit;
}

$basePath    = '../../';
$currentPage = 'catalogos';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catálogos — Sistema de Tickets</title>
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
        <h2 style="margin:0 0 .5rem 0">Catálogos</h2>
        <p class="catalog-help">Administra las tablas base del sistema: regiones, sitios, líneas de negocio, tipos de solicitud, tipos de asunto y lugar de incidencia.</p>

        <div class="card">
          <div id="app" v-cloak>
            <div class="tabs">
              <button v-for="t in tabs" :key="t.key" class="tab" :class="{active: tab===t.key}" @click="tab=t.key">{{ t.label }}</button>
            </div>

            <!-- Tab: Regiones -->
            <div class="tab-content" :class="{active: tab==='regiones'}">
              <div class="inline-form">
                <div class="form-group">
                  <label class="form-label">Código</label>
                  <input v-model="frmRegiones.codigo" class="form-control" placeholder="Ej: REG-01" style="width:100px" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Nombre</label>
                  <input v-model="frmRegiones.nombre" class="form-control" placeholder="Ej: Capital" required>
                </div>
                <button @click="crear('regiones')" class="btn btn-primary" :disabled="loading">Agregar</button>
              </div>
              <div v-if="loading" style="color:var(--color-muted)">Cargando...</div>
              <div v-else class="table-wrap">
                <table>
                  <thead><tr><th>ID</th><th>Código</th><th>Nombre</th><th style="width:80px"></th></tr></thead>
                  <tbody>
                    <tr v-for="r in data.regiones" :key="r.id" :class="{'editing-row': editando?.id===r.id && editando?.tipo==='regiones'}">
                      <td>{{ r.id }}</td>
                      <td v-if="editando?.id===r.id && editando?.tipo==='regiones'"><input v-model="editando.codigo" class="form-control" style="width:90px"></td>
                      <td v-else>{{ r.codigo }}</td>
                      <td v-if="editando?.id===r.id && editando?.tipo==='regiones'"><input v-model="editando.nombre" class="form-control"></td>
                      <td v-else>{{ r.nombre }}</td>
                      <td>
                        <div v-if="editando?.id===r.id && editando?.tipo==='regiones'" class="actions-cell">
                          <button @click="guardar('regiones')" class="btn-icon btn-icon-edit" title="Guardar"><i class="fa-solid fa-check"></i></button>
                          <button @click="cancelarEdit" class="btn-icon" style="background:#f3f4f6;color:#666" title="Cancelar"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div v-else class="actions-cell">
                          <button @click="editar('regiones', r)" class="btn-icon btn-icon-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                          <button @click="eliminar('regiones', r.id)" class="btn-icon btn-icon-del" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="!data.regiones?.length"><td colspan="4" style="text-align:center;color:var(--text-secondary);padding:2rem">Sin registros</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Tab: Sitios -->
            <div class="tab-content" :class="{active: tab==='sitios'}">
              <div class="inline-form">
                <div class="form-group">
                  <label class="form-label">Región</label>
                  <select v-model="frmSitios.region_id" class="form-control" style="min-width:140px" required>
                    <option value="">Seleccionar...</option>
                    <option v-for="r in data.regiones" :key="r.id" :value="r.id">{{ r.nombre }}</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Nombre</label>
                  <input v-model="frmSitios.nombre" class="form-control" placeholder="Ej: Bogotá" required>
                </div>
                <button @click="crear('sitios')" class="btn btn-primary" :disabled="loading">Agregar</button>
              </div>
              <div v-if="loading" style="color:var(--color-muted)">Cargando...</div>
              <div v-else class="table-wrap">
                <table>
                  <thead><tr><th>ID</th><th>Región</th><th>Nombre</th><th style="width:80px"></th></tr></thead>
                  <tbody>
                    <tr v-for="r in data.sitios" :key="r.id" :class="{'editing-row': editando?.id===r.id && editando?.tipo==='sitios'}">
                      <td>{{ r.id }}</td>
                      <td v-if="editando?.id===r.id && editando?.tipo==='sitios'">
                        <select v-model="editando.region_id" class="form-control" style="min-width:120px">
                          <option v-for="reg in data.regiones" :key="reg.id" :value="reg.id">{{ reg.nombre }}</option>
                        </select>
                      </td>
                      <td v-else>{{ regionNombre(r.region_id) }}</td>
                      <td v-if="editando?.id===r.id && editando?.tipo==='sitios'"><input v-model="editando.nombre" class="form-control"></td>
                      <td v-else>{{ r.nombre }}</td>
                      <td>
                        <div v-if="editando?.id===r.id && editando?.tipo==='sitios'" class="actions-cell">
                          <button @click="guardar('sitios')" class="btn-icon btn-icon-edit" title="Guardar"><i class="fa-solid fa-check"></i></button>
                          <button @click="cancelarEdit" class="btn-icon" style="background:#f3f4f6;color:#666" title="Cancelar"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div v-else class="actions-cell">
                          <button @click="editar('sitios', r)" class="btn-icon btn-icon-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                          <button @click="eliminar('sitios', r.id)" class="btn-icon btn-icon-del" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="!data.sitios?.length"><td colspan="4" style="text-align:center;color:var(--text-secondary);padding:2rem">Sin registros</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Tab: Líneas -->
            <div class="tab-content" :class="{active: tab==='lineas'}">
              <div class="inline-form">
                <div class="form-group">
                  <label class="form-label">Nombre</label>
                  <input v-model="frmLineas.nombre" class="form-control" placeholder="Ej: Comercial" required>
                </div>
                <button @click="crear('lineas')" class="btn btn-primary" :disabled="loading">Agregar</button>
              </div>
              <div v-if="loading" style="color:var(--color-muted)">Cargando...</div>
              <div v-else class="table-wrap">
                <table>
                  <thead><tr><th>ID</th><th>Nombre</th><th>Activo</th><th style="width:80px"></th></tr></thead>
                  <tbody>
                    <tr v-for="r in data.lineas" :key="r.id" :class="{'editing-row': editando?.id===r.id && editando?.tipo==='lineas'}">
                      <td>{{ r.id }}</td>
                      <td v-if="editando?.id===r.id && editando?.tipo==='lineas'"><input v-model="editando.nombre" class="form-control"></td>
                      <td v-else>{{ r.nombre }}</td>
                      <td>
                        <label v-if="editando?.id===r.id && editando?.tipo==='lineas'" class="switch">
                          <input type="checkbox" v-model="editando.activo" :true-value="1" :false-value="0">
                          <span class="slider"></span>
                        </label>
                        <span v-else :class="r.activo?'badge badge-green':'badge badge-red'">{{ r.activo?'Activo':'Inactivo' }}</span>
                      </td>
                      <td>
                        <div v-if="editando?.id===r.id && editando?.tipo==='lineas'" class="actions-cell">
                          <button @click="guardar('lineas')" class="btn-icon btn-icon-edit" title="Guardar"><i class="fa-solid fa-check"></i></button>
                          <button @click="cancelarEdit" class="btn-icon" style="background:#f3f4f6;color:#666" title="Cancelar"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div v-else class="actions-cell">
                          <button @click="editar('lineas', r)" class="btn-icon btn-icon-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                          <button @click="eliminar('lineas', r.id)" class="btn-icon btn-icon-del" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="!data.lineas?.length"><td colspan="4" style="text-align:center;color:var(--text-secondary);padding:2rem">Sin registros</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Tab: Tipos Solicitud -->
            <div class="tab-content" :class="{active: tab==='tipos_solicitud'}">
              <div class="inline-form">
                <div class="form-group">
                  <label class="form-label">Nombre</label>
                  <input v-model="frmTiposSol.nombre" class="form-control" placeholder="Ej: Incidencia" required>
                </div>
                <button @click="crear('tipos_solicitud')" class="btn btn-primary" :disabled="loading">Agregar</button>
              </div>
              <div v-if="loading" style="color:var(--color-muted)">Cargando...</div>
              <div v-else class="table-wrap">
                <table>
                  <thead><tr><th>ID</th><th>Nombre</th><th style="width:80px"></th></tr></thead>
                  <tbody>
                    <tr v-for="r in data.tipos_solicitud" :key="r.id" :class="{'editing-row': editando?.id===r.id && editando?.tipo==='tipos_solicitud'}">
                      <td>{{ r.id }}</td>
                      <td v-if="editando?.id===r.id && editando?.tipo==='tipos_solicitud'"><input v-model="editando.nombre" class="form-control"></td>
                      <td v-else>{{ r.nombre }}</td>
                      <td>
                        <div v-if="editando?.id===r.id && editando?.tipo==='tipos_solicitud'" class="actions-cell">
                          <button @click="guardar('tipos_solicitud')" class="btn-icon btn-icon-edit" title="Guardar"><i class="fa-solid fa-check"></i></button>
                          <button @click="cancelarEdit" class="btn-icon" style="background:#f3f4f6;color:#666" title="Cancelar"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div v-else class="actions-cell">
                          <button @click="editar('tipos_solicitud', r)" class="btn-icon btn-icon-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                          <button @click="eliminar('tipos_solicitud', r.id)" class="btn-icon btn-icon-del" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="!data.tipos_solicitud?.length"><td colspan="3" style="text-align:center;color:var(--text-secondary);padding:2rem">Sin registros</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Tab: Tipos Asunto -->
            <div class="tab-content" :class="{active: tab==='tipos_asunto'}">
              <div class="inline-form">
                <div class="form-group">
                  <label class="form-label">Nombre</label>
                  <input v-model="frmTiposAsu.nombre" class="form-control" placeholder="Ej: Soporte Técnico" required>
                </div>
                <button @click="crear('tipos_asunto')" class="btn btn-primary" :disabled="loading">Agregar</button>
              </div>
              <div v-if="loading" style="color:var(--color-muted)">Cargando...</div>
              <div v-else class="table-wrap">
                <table>
                  <thead><tr><th>ID</th><th>Nombre</th><th style="width:80px"></th></tr></thead>
                  <tbody>
                    <tr v-for="r in data.tipos_asunto" :key="r.id" :class="{'editing-row': editando?.id===r.id && editando?.tipo==='tipos_asunto'}">
                      <td>{{ r.id }}</td>
                      <td v-if="editando?.id===r.id && editando?.tipo==='tipos_asunto'"><input v-model="editando.nombre" class="form-control"></td>
                      <td v-else>{{ r.nombre }}</td>
                      <td>
                        <div v-if="editando?.id===r.id && editando?.tipo==='tipos_asunto'" class="actions-cell">
                          <button @click="guardar('tipos_asunto')" class="btn-icon btn-icon-edit" title="Guardar"><i class="fa-solid fa-check"></i></button>
                          <button @click="cancelarEdit" class="btn-icon" style="background:#f3f4f6;color:#666" title="Cancelar"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div v-else class="actions-cell">
                          <button @click="editar('tipos_asunto', r)" class="btn-icon btn-icon-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                          <button @click="eliminar('tipos_asunto', r.id)" class="btn-icon btn-icon-del" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="!data.tipos_asunto?.length"><td colspan="3" style="text-align:center;color:var(--text-secondary);padding:2rem">Sin registros</td></tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Tab: Lugar Incidencia -->
            <div class="tab-content" :class="{active: tab==='lugar_incidencia'}">
              <div class="inline-form">
                <div class="form-group">
                  <label class="form-label">Nombre</label>
                  <input v-model="frmLugar.nombre" class="form-control" placeholder="Ej: Interno" required>
                </div>
                <button @click="crear('lugar_incidencia')" class="btn btn-primary" :disabled="loading">Agregar</button>
              </div>
              <div v-if="loading" style="color:var(--color-muted)">Cargando...</div>
              <div v-else class="table-wrap">
                <table>
                  <thead><tr><th>ID</th><th>Nombre</th><th style="width:80px"></th></tr></thead>
                  <tbody>
                    <tr v-for="r in data.lugar_incidencia" :key="r.id" :class="{'editing-row': editando?.id===r.id && editando?.tipo==='lugar_incidencia'}">
                      <td>{{ r.id }}</td>
                      <td v-if="editando?.id===r.id && editando?.tipo==='lugar_incidencia'"><input v-model="editando.nombre" class="form-control"></td>
                      <td v-else>{{ r.nombre }}</td>
                      <td>
                        <div v-if="editando?.id===r.id && editando?.tipo==='lugar_incidencia'" class="actions-cell">
                          <button @click="guardar('lugar_incidencia')" class="btn-icon btn-icon-edit" title="Guardar"><i class="fa-solid fa-check"></i></button>
                          <button @click="cancelarEdit" class="btn-icon" style="background:#f3f4f6;color:#666" title="Cancelar"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                        <div v-else class="actions-cell">
                          <button @click="editar('lugar_incidencia', r)" class="btn-icon btn-icon-edit" title="Editar"><i class="fa-solid fa-pen"></i></button>
                          <button @click="eliminar('lugar_incidencia', r.id)" class="btn-icon btn-icon-del" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                        </div>
                      </td>
                    </tr>
                    <tr v-if="!data.lugar_incidencia?.length"><td colspan="3" style="text-align:center;color:var(--text-secondary);padding:2rem">Sin registros</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="toast" :class="toastCls" ref="toast">{{ toastMsg }}</div>
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
const app = Vue.createApp({
  data() {
    return {
      tab: 'regiones',
      tabs: [
        { key: 'regiones', label: 'Regiones' },
        { key: 'sitios', label: 'Sitios' },
        { key: 'lineas', label: 'Líneas Negocio' },
        { key: 'tipos_solicitud', label: 'Tipos Solicitud' },
        { key: 'tipos_asunto', label: 'Tipos Asunto' },
        { key: 'lugar_incidencia', label: 'Lugar Incidencia' },
      ],
      loading: false,
      data: {
        regiones: [], sitios: [], lineas: [], tipos_solicitud: [], tipos_asunto: [], lugar_incidencia: [],
      },
      editando: null,
      frmRegiones: { codigo: '', nombre: '' },
      frmSitios: { region_id: '', nombre: '' },
      frmLineas: { nombre: '' },
      frmTiposSol: { nombre: '' },
      frmTiposAsu: { nombre: '' },
      frmLugar: { nombre: '' },
      toastMsg: '',
      toastType: 'success',
      toastTimer: null,
    };
  },
  computed: {
    toastCls() { return 'toast toast-' + this.toastType; },
  },
  async mounted() {
    await this.cargarTodo();
  },
  methods: {
    async cargarTodo() {
      this.loading = true;
      try {
        const keys = ['regiones', 'sitios', 'lineas', 'tipos_solicitud', 'tipos_asunto', 'lugar_incidencia'];
        const res = await Promise.all(keys.map(k => api.catalogos.list(k)));
        keys.forEach((k, i) => { this.data[k] = res[i]?.data || []; });
      } catch (e) { this.toast('Error al cargar datos', 'error'); }
      this.loading = false;
    },
    async crear(tipo) {
      const frmMap = {
        regiones: 'frmRegiones', sitios: 'frmSitios', lineas: 'frmLineas',
        tipos_solicitud: 'frmTiposSol', tipos_asunto: 'frmTiposAsu', lugar_incidencia: 'frmLugar',
      };
      const frm = this[frmMap[tipo]];
      const body = { ...frm };
      if (tipo === 'lineas') body.activo = 1;
      const r = await api.catalogos.create(tipo, body);
      if (r?.success) {
        Object.assign(frm, { codigo: '', region_id: '', nombre: '' });
        await this.cargarTodo();
        this.toast('Creado correctamente', 'success');
      } else {
        this.toast(r?.message || 'Error al crear', 'error');
      }
    },
    editar(tipo, item) {
      this.editando = { tipo, id: item.id, ...JSON.parse(JSON.stringify(item)) };
    },
    cancelarEdit() { this.editando = null; },
    async guardar(tipo) {
      if (!this.editando) return;
      const fieldMap = {
        regiones: ['codigo', 'nombre'], sitios: ['region_id', 'nombre'],
        lineas: ['nombre', 'activo'], tipos_solicitud: ['nombre'],
        tipos_asunto: ['nombre'], lugar_incidencia: ['nombre'],
      };
      const body = {};
      for (const f of (fieldMap[tipo] || [])) body[f] = this.editando[f];
      const r = await api.catalogos.update(tipo, this.editando.id, body);
      if (r?.success) { this.editando = null; await this.cargarTodo(); this.toast('Actualizado correctamente', 'success'); }
      else { this.toast(r?.message || 'Error al actualizar', 'error'); }
    },
    async eliminar(tipo, id) {
      if (!confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')) return;
      const r = await api.catalogos.delete(tipo, id);
      if (r?.success) { await this.cargarTodo(); this.toast('Eliminado correctamente', 'success'); }
      else { this.toast(r?.message || 'Error al eliminar', 'error'); }
    },
    regionNombre(id) { const r = this.data.regiones.find(x => x.id === id); return r ? r.nombre : '—'; },
    toast(msg, type = 'success') {
      this.toastMsg = msg; this.toastType = type;
      clearTimeout(this.toastTimer);
      this.$nextTick(() => { const el = this.$refs.toast; if (el) el.classList.add('show'); });
      this.toastTimer = setTimeout(() => { const el = this.$refs.toast; if (el) el.classList.remove('show'); }, 3000);
    },
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
