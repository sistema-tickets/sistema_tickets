# Contexto del Proyecto — Sistema de Tickets (Tabasco OC LLC)

## Descripción General
Sistema de gestión de incidencias y requerimientos para el Área de TI de **Tabasco OC LLC**, que cubre dos sub-áreas: **Sistemas** y **Desarrollo**. Los usuarios de cualquier área de la empresa pueden abrir tickets para solicitar soporte técnico (equipos, impresoras, redes, etc.) o proyectos de desarrollo (aplicaciones web, automatizaciones, SharePoint, Power Platform, etc.).

---

## Stack Tecnológico
| Capa | Tecnología |
|------|-----------|
| Servidor | PHP 8.x sobre XAMPP (Windows) |
| Base de datos | MySQL/MariaDB 10.4, puerto **33066**, BD: `bd_tickets` |
| Frontend | HTML + CSS (DM Sans) + Vue.js 3 (CDN, sin build) + Quill.js |
| Editor de texto | Quill.js 1.3.7 |
| Iconos | Font Awesome 6.5 |
| Gráficos | Chart.js 4 |
| Autenticación | Sesiones PHP + JWT-like en localStorage |
| URL local | http://localhost/sistema_tickets/public/ |

---

## Credenciales de BD (desarrollo)
```
DB_HOST=localhost
DB_PORT=33066
DB_NAME=bd_tickets
DB_USER=tabasco
DB_PASS=tabasco
```

---

## Estructura de Directorios
```
sistema_tickets/
├── backup/                      ← Backups por versión
│   ├── CHANGELOG.txt            ← Bitácora de versiones
│   └── v1/                      ← Versión 1 (2026-05-29)
│       ├── (copia completa del proyecto)
│       └── bd_tickets_v1_dump.sql   ← Dump completo de BD con datos
├── backend/
│   ├── config/
│   │   ├── app.php              # loadEnv(), función env()
│   │   ├── database.php         # Conexión PDO (getDB())
│   │   └── constants.php        # ROL_USUARIO=1, ROL_ADMIN=2, ROL_SUPERADMIN=3, ESTADO_EN_ESPERA=1
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── TicketController.php
│   │   ├── UsuarioController.php
│   │   ├── CatalogoController.php
│   │   └── DashboardController.php
│   ├── middleware/
│   │   └── Auth.php             # Auth::usuario(), Auth::requireAuth()
│   └── models/
│       ├── TicketModel.php
│       ├── UsuarioModel.php
│       └── DashboardModel.php
├── public/
│   ├── index.php                # Vista principal (lista de tickets, crear, detalle)
│   ├── logout.php
│   ├── pages/
│   │   ├── login.html
│   │   ├── register.html
│   │   ├── password-reset.html
│   │   └── admin/
│   │       └── usuarios.html
│   ├── assets/
│   │   ├── css/main.css         # Hoja de estilos principal
│   │   ├── js/
│   │   │   ├── api.js           # Cliente HTTP hacia /public/api/
│   │   │   └── auth.js          # Auth.getUsuario(), Auth.isAdmin(), Auth.logout()
│   │   └── inc/
│   │       ├── header.php       # Barra superior (toggle sidebar + chip usuario)
│   │       └── sidebar.php      # Sidebar con navegación
│   └── api/
│       ├── tickets.php          # REST: GET/POST/PATCH /api/tickets
│       └── usuarios.php         # REST: GET/POST /api/usuarios
├── database/
│   └── bd_tickets_*.sql         # Scripts de creación por tabla
└── contexto.md                  ← Este archivo
```

---

## Roles de Usuario
| ID | Nombre | Acceso |
|----|--------|--------|
| 1 | Usuario | Solo ve y crea sus propios tickets |
| 2 | Coordinador (Admin) | Gestiona todos los tickets, asigna, cambia estado |
| 3 | Superadmin | Admin + gestión de usuarios, configuración global |

```php
define('ROL_USUARIO',    1);
define('ROL_ADMIN',      2);
define('ROL_SUPERADMIN', 3);
define('ESTADO_EN_ESPERA', 1);
```

---

## Layout actual — index.php

### Header (`header.php`)
- **Izquierda:** botón hamburguesa → logo empresarial `assets/img/logo.jpg` (32px alto)
- **Derecha:** botones notificaciones + reloj + chip de usuario (nombre, rol, logout)
- Sin breadcrumb, sin buscador

### Sidebar (`sidebar.php`)
- **Ancho:** 220px en desktop, colapsable a 50px (icono-only). Off-canvas en mobile (270px).
- **Transición:** `cubic-bezier(0.4,0,0.2,1)` suave; texto se desvanece con `opacity`+`max-width` antes de que el ancho anime. El `padding-left` del contenido transiciona sincrónicamente.
- **Bordes:** `border-radius: 0 16px 16px 0` en desktop (solo lado derecho). Sin redondeo en mobile.
- **Botón cerrar (X):** visible solo en mobile, esquina superior derecha del overlay.
- **Clase JS:** `.sidebar-collapsed` en `dash-body-inner` sincroniza el `padding-left` del contenido.

```
[Brand: SI | Sistema de Tickets | Operations Center]
[N tickets · Turno AM/PM]   ← 11px, gris discreto, fondo sutil

  HISTORIAL
    Historial de tickets → ?tab=cerrados
  ─────────────
  MESA DE SERVICIO
    Total Tickets  [badge N]
    Asignados a mí [badge N]  ← solo admin
  ─────────────
  ADMINISTRACIÓN              ← solo superadmin
    Usuarios y roles
    Configuración
```

### Área de contenido (orden de secciones)
```
1. KPI Cards
   · Admin/Superadmin: 4 tarjetas con sparkline (.kpi-grid2)
   · Usuario normal:   4 tarjetas simples (.kpi-user-grid)
     Total | Abiertos | En proceso | Resueltos

2. Barra de filtros inline (.inline-filter-bar)   ← border-radius: 14px
   Búsqueda + Estado + Prioridad + Tipo + [Asignado a, solo admin]
   + Fecha desde/hasta + [Filtrar] + [Limpiar si hay filtros activos]

3. Chips de filtros activos (.filter-chips-bar)   ← border-radius: 14px
   Chips removibles por cada filtro activo
   → Derecha (.chips-bar-actions): [Nuevo ticket] [Ordenar] [Columnas]

4. Tabla de tickets (.dash-table-wrap)             ← border-radius: 14px, margin-top: 12px
   Paginación: 7 tickets por página (server-side)
```

### Espaciado del contenido
- `padding: 16px 28px 32px` en desktop (28px laterales para separar de sidebar y borde)
- `padding: 12px 14px 24px` en mobile

### Turno AM/PM
Calculado en PHP: `$turno = (int)date('H') < 12 ? 'AM' : 'PM'`
Definido en `sidebar.php` y también en `index.php` (ambos lo computan independientemente).

---

## Flujo Principal (index.php)
1. **PHP:** autentica sesión → aplica filtros GET → queries BD con joins → pasa datos a HTML
2. **Vue 3 (CDN):** maneja overlays de Crear ticket y Detalle ticket
3. **Filtros GET disponibles:** `af_estado`, `af_prioridad`, `af_tipo`, `af_admin`, `af_fd`, `af_fh`, `q` (búsqueda), `tab` (all/abiertos/en_proceso/cerrados)
4. **SLA:** badges ok/en_riesgo/vencido calculados en tiempo real contra `sla_configuracion`

---

## Tablas Principales BD
| Tabla | Descripción |
|-------|-------------|
| `tickets` | Ticket principal |
| `estados` | En espera, En proceso, Esperando respuesta, Contestado, Reabierto, Reprogramado, Atendido, Cerrado, Rechazado |
| `prioridades` | Baja(1), Media(2), Alta(3) |
| `tipos_solicitud` | Incidencia(1), Requerimiento(2), etc. |
| `tipos_asunto` | Categorías de asunto (solo admin) |
| `asuntos` | Asuntos con `prioridad_default_id` |
| `lineas_negocio` | Área de negocio del solicitante |
| `sitios` | Ubicaciones físicas (ciudades) |
| `usuarios` | Solicitantes y administradores |
| `historial_estados` | Log de cambios de estado |
| `respuestas` | Respuestas/comentarios en tickets |
| `adjuntos` | Archivos adjuntos |
| `sla_configuracion` | Horas de resolución por prioridad |
| `notificaciones` | Sistema de notificaciones |
| `password_resets` | Reset de contraseñas |

---

## Diseño Visual
- **Paleta:** Sidebar fondo `#ebebeb` (gris claro), brand azul `#1a3a6b`, fondo contenido `#f0f2f7`
- **Fuentes:** DM Sans (UI), DM Mono (números/IDs)
- **Tokens CSS:** Variables en `:root` de `main.css`
- **Sidebar:** 220px desktop / 50px colapsado / 270px mobile overlay
- **Border-radius:** 14px en tarjetas KPI, filter bar, chips bar y tabla. `0 16px 16px 0` en sidebar desktop.
- **Transiciones:** `cubic-bezier(0.4,0,0.2,1)` para sidebar y contenido. Texto usa `opacity`+`max-width` para evitar saltos bruscos.
- **Logo:** `assets/img/logo.jpg` en el header, 32px alto, junto al botón hamburguesa.

---

## Sistema de Backups
```
backup/
├── CHANGELOG.txt    ← bitácora de todas las versiones
└── v1/              ← snapshot 2026-05-29
    ├── (259 archivos, ~52 MB)
    └── bd_tickets_v1_dump.sql  (~5.5 MB con datos)
```
Para restaurar la BD de v1:
```bash
mysql -h localhost -P 33066 -u tabasco -ptabasco bd_tickets < backup/v1/bd_tickets_v1_dump.sql
```

---

## Comandos Útiles
```bash
# Servidor XAMPP en Windows
# Apache: http://localhost/sistema_tickets/public/
# phpMyAdmin: http://localhost/phpmyadmin/

# Exportar BD (desde CMD/PowerShell en Windows)
C:\xampp\mysql\bin\mysqldump.exe -h localhost -P 33066 -u tabasco -ptabasco --single-transaction --routines --triggers bd_tickets > backup\vN\bd_tickets_vN_dump.sql

# Git
git status
git log --oneline -10
git checkout lina
```

---

## Próximos Pasos Sugeridos
1. Vista dedicada para historial de tickets del usuario
2. Sistema de notificaciones en tiempo real
3. Dashboard analítico para administradores (informes.php)
4. Power BI embed para reportes de gestión
5. Gestión de catálogos desde la UI (tipos, asuntos, prioridades)
6. Merge de rama `lina` a `main` cuando esté estable
