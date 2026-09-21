# Contexto del Proyecto — Sistema de Tickets (Tabasco OC LLC)

## Descripción General
Sistema de gestión de incidencias y requerimientos para el Área de TI de **Tabasco OC LLC**, que cubre dos sub-áreas: **Sistemas** y **Desarrollo**. Los usuarios de cualquier área de la empresa pueden abrir tickets para solicitar soporte técnico (equipos, impresoras, redes, etc.) o proyectos de desarrollo (aplicaciones web, automatizaciones, SharePoint, Power Platform, etc.).

---

## Stack Tecnológico
| Capa | Tecnología |
|------|-----------|
| Servidor | PHP 8.x sobre XAMPP (Windows) |
| Base de datos | MySQL (MariaDB) |
| Frontend | HTML + CSS (DM Sans) + Vue.js 3 (CDN, sin build) + Quill.js |
| Editor de texto | Quill.js 1.3.7 |
| Iconos | Font Awesome 6.5 |
| Gráficos | Chart.js 4 |
| Autenticación | Sesiones PHP + JWT-like en localStorage |
| URL local | http://localhost/sistema_tickets/public/ |

---

## Estructura de Directorios
```
sistema_tickets/
├── backend/
│   ├── config/
│   │   ├── app.php          # Configuración general
│   │   ├── database.php     # Conexión PDO (getDB())
│   │   └── constants.php    # ROL_USUARIO=1, ROL_ADMIN=2, ROL_SUPERADMIN=3, ESTADO_EN_ESPERA=1 …
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── TicketController.php
│   │   ├── UsuarioController.php
│   │   ├── CatalogoController.php
│   │   └── DashboardController.php
│   ├── middleware/
│   │   └── Auth.php         # Auth::usuario(), Auth::requireAuth()
│   └── models/
│       ├── TicketModel.php
│       ├── UsuarioModel.php
│       └── DashboardModel.php
├── public/
│   ├── index.php            # Vista principal usuario/admin (lista de tickets)
│   ├── logout.php
│   ├── pages/
│   │   ├── login.html
│   │   ├── register.html
│   │   ├── password-reset.html
│   │   ├── dashboard.html   # Dashboard alternativo (Vue, obsoleto)
│   │   ├── tickets/
│   │   │   ├── crear.html
│   │   │   └── detalle.html
│   │   └── admin/
│   │       └── usuarios.html
│   ├── assets/
│   │   ├── css/main.css     # Hoja de estilos principal (tokens CSS + componentes)
│   │   ├── js/
│   │   │   ├── api.js       # Cliente HTTP hacia /public/api/
│   │   │   └── auth.js      # Auth.getUsuario(), Auth.isAdmin(), Auth.logout()
│   │   ├── inc/
│   │   │   ├── header.php   # Barra superior (toggle sidebar, usuario)
│   │   │   └── sidebar.php  # Sidebar con navegación y filtros
│   │   └── img/logo.jpg
│   └── api/
│       ├── tickets.php      # REST: GET/POST/PATCH /api/tickets
│       └── usuarios.php     # REST: GET/POST /api/usuarios
└── database/
    ├── bd_tickets_*.sql     # Scripts de creación por tabla
    └── bd_tickets_routines.sql
```

---

## Roles de Usuario
| ID | Nombre | Acceso |
|----|--------|--------|
| 1 | Usuario | Solo ve y crea sus propios tickets |
| 2 | Coordinador (Admin) | Gestiona todos los tickets, asigna, cambia estado |
| 3 | Superadmin | Admin + gestión de usuarios, configuración global |

Constantes PHP:
```php
define('ROL_USUARIO',    1);
define('ROL_ADMIN',      2);
define('ROL_SUPERADMIN', 3);
define('ESTADO_EN_ESPERA', 1);
```

---

## Flujo Principal (index.php)
`index.php` es el núcleo de la aplicación. Hace todo en un solo archivo PHP+Vue:

1. **PHP (servidor):** autentica sesión → aplica filtros GET → consulta BD con joins → pasa datos a HTML
2. **HTML+Vue (cliente):** muestra lista de tickets + overlay "Crear ticket" + overlay "Detalle ticket"
3. **Filtros disponibles:** tab (all/abiertos/en_proceso/cerrados), estado, prioridad, tipo, admin asignado, fechas desde/hasta, búsqueda libre (q)
4. **Paginación:** 7 tickets por página, server-side

### Queries clave:
- Tickets: JOIN con estados, prioridades, usuarios, sitios, tipos_solicitud, lineas_negocio, sla_configuracion
- KPIs: Totales por tab, SLA en riesgo
- Actividad reciente: historial_estados últimas 5 entradas
- Distribución: COUNT por linea_negocio

---

## Tablas Principales BD
| Tabla | Descripción |
|-------|-------------|
| `tickets` | Ticket principal |
| `estados` | En espera, En proceso, Esperando respuesta, Contestado, Reabierto, Reprogramado, Atendido, Cerrado, Rechazado |
| `prioridades` | Baja(1), Media(2), Alta(3) |
| `tipos_solicitud` | Incidencia(1), Requerimiento(2), etc. |
| `tipos_asunto` | Categorías de asunto (admin) |
| `asuntos` | Asuntos específicos con prioridad_default_id |
| `lineas_negocio` | Área de negocio del solicitante |
| `sitios` | Ubicación física (ciudades) |
| `usuarios` | Solicitantes y administradores |
| `historial_estados` | Log de cambios de estado |
| `respuestas` | Respuestas/comentarios en tickets |
| `adjuntos` | Archivos adjuntos |
| `sla_configuracion` | Horas de resolución por prioridad |
| `notificaciones` | Sistema de notificaciones |
| `password_resets` | Reset de contraseñas |

---

## Diseño Visual
- **Paleta principal:** Sidebar azul marino (`#1a3a6b`), fondo gris claro (`#f0f2f7`), superficie blanca
- **Fuentes:** DM Sans (UI), DM Mono (números/IDs)
- **Sidebar:** 220px de ancho, colapsable en desktop, off-canvas en mobile
- **Layout:** Header fijo → Sidebar izquierdo → Contenido principal con tabla de tickets
- **Tokens CSS:** Variables en `:root` (ver `main.css`)

---

## Estado Actual del Proyecto (2026-05-29)
- Rama activa: `lina`
- Autenticación PHP completa (sesiones)
- Vista usuario: `index.php` lista tickets propios, crear ticket overlay, detalle overlay
- Vista admin: `index.php` mismo archivo, más filtros, KPIs v2, gestión de tickets
- Filtros funcionando: tabs, estado, prioridad, tipo, asignado, fechas, búsqueda
- SLA configurado: badges ok/en_riesgo/vencido
- Formulario crear ticket: campo adjuntos, Quill.js, plantilla para Desarrollo

---

## Cambios Recientes Aplicados (rama lina)
- Eliminado breadcrumb "Service Desk / Tickets" del header
- Buscador movido de header a barra inline en área de contenido (debajo de KPIs)
- KPIs ahora visibles para todos los roles (usuario ve sus propias estadísticas)
- Filtros avanzados movidos de sidebar a barra inline en contenido
- Turno "AM/PM" basado en hora del servidor reemplaza "Vespertino"
- Sidebar: eliminado sección Análisis, Tipo de solicitudes, Filtros avanzados, bloque usuario
- Sidebar: añadida sección Historial (enlace a tickets cerrados del usuario)
- Eliminadas secciones "Actividad reciente" y "Distribución por línea de negocio"
- Title bar de contenido suprimida (redundante con brand del sidebar)

---

## Convenciones de Código
- PHP clásico (no frameworks), PDO con prepared statements
- Vue 3 CDN (Options API), sin build step
- CSS con variables tokens en `:root`, BEM-like naming (`dash-sidebar`, `kpi-card2`, etc.)
- Todas las queries con placeholders PDO para evitar SQL injection
- Autenticación: `Auth::usuario()` retorna array con id, nombre, rol_id, etc.

---

## Comandos Útiles
```bash
# Servidor XAMPP en Windows
# Apache: http://localhost/sistema_tickets/public/
# phpMyAdmin: http://localhost/phpmyadmin/

# Git
git status
git log --oneline -10
git checkout lina
```

---

## Próximos Pasos Sugeridos
1. Vista dedicada para historial de tickets del usuario
2. Sistema de notificaciones en tiempo real
3. Dashboard de analítica para admins (informes.php)
4. Power BI embed para reportes de gestión
5. Gestión de catálogos desde UI (tipos, asuntos, prioridades)
