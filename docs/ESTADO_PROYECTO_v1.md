# Contexto del Proyecto — Sistema de Tickets (TI / Área de Sistemas)
**Última actualización:** 2026-05-20  
**Rama activa:** `lina`  
**Rama principal:** `main`

---

## ¿Qué es este sistema?

Aplicación web interna de gestión de tickets de soporte para el área de TI / Sistemas de una empresa de telecomunicaciones e infraestructura con presencia en múltiples ciudades de Colombia. Reemplaza el flujo informal de solicitudes por WhatsApp o correo, centralizando la atención, la asignación de responsables, el seguimiento por SLA y el análisis de desempeño.

**Usuarios del sistema:**
- **Usuarios finales (762 registrados):** empleados de las distintas sedes y líneas de negocio que crean tickets cuando tienen un problema o solicitud de TI.
- **Administradores (44 registrados):** técnicos o coordinadores de TI que atienden, asignan y cierran tickets.

**Cobertura geográfica:** 37 sitios en 3 regiones — Centro-Oriente (26 sedes), Suroccidente (8 sedes), Capital (1) y 2 sin región asignada.

---

## Stack tecnológico

| Capa | Tecnología | Detalle |
|------|-----------|---------|
| Backend | PHP 8+ puro | Arquitectura MVC manual, API REST con sesiones PHP, sin frameworks |
| Frontend | HTML5 + Vue.js 3 (CDN) | Sin npm, sin build tools, sin bundler |
| Editor rich text | Quill.js 1.3.7 (CDN) | Para descripciones de tickets |
| Gráficos | Chart.js 4.4.7 (CDN) | Dashboard web |
| Estilos | CSS3 puro + variables CSS | `public/assets/css/main.css` |
| Base de datos | MySQL 8 — `bd_tickets` | Puerto 33066 en XAMPP local |
| Servidor local | XAMPP | `http://localhost/sistema_tickets/public/` |
| Dashboard BI | Power BI Desktop 2.153.1206.0 | Conexión ODBC a MySQL |
| Iconos | Font Awesome 6.5.1 (CDN) | |
| Fuentes | DM Sans, DM Mono (Google Fonts) | |

---

## Estructura del proyecto

```
sistema_tickets/
├── .env                        ← Credenciales locales (NO en git)
├── .env.example
├── .env.production.example
├── README.md
│
├── backend/                    ← PHP — NO accesible desde web
│   ├── config/
│   │   ├── app.php             ← Carga .env, inicia sesión, configura errores
│   │   ├── database.php        ← Conexión PDO a MySQL
│   │   └── constants.php       ← Constantes de roles, estados, prioridades
│   ├── controllers/
│   │   ├── AuthController.php  ← Login, logout, registro, reset de contraseña
│   │   ├── TicketController.php← CRUD tickets, cambio de estado, asignación
│   │   ├── UsuarioController.php
│   │   └── DashboardController.php
│   ├── models/
│   │   ├── TicketModel.php
│   │   ├── UsuarioModel.php    ← Usa PASSWORD_BCRYPT para contraseñas
│   │   └── DashboardModel.php
│   ├── middleware/
│   │   ├── Auth.php            ← Valida sesión y rol (usuario vs admin)
│   │   └── Cors.php
│   └── helpers/
│       ├── Response.php        ← Respuestas JSON estandarizadas
│       └── Validator.php
│
├── public/                     ← Document Root (único expuesto al web)
│   ├── .htaccess               ← Rewrite rules Apache
│   ├── index.php               ← Lista de tickets + KPIs admin + sidebar filtros
│   ├── informes.php            ← Analíticas admin (charts + tabla demora) [NUEVO]
│   ├── logout.php
│   ├── api/                    ← Endpoints REST
│   │   ├── auth.php            ← POST login/logout | GET me, password-reset
│   │   ├── tickets.php         ← CRUD + estado + asignar + stats
│   │   ├── usuarios.php        ← Gestión usuarios (solo admin)
│   │   ├── catalogos.php       ← Sitios, líneas, tipos, asuntos, etc.
│   │   └── dashboard.php       ← KPIs y estadísticas (usado por DashboardController)
│   ├── pages/
│   │   ├── login.html          ← Login con Vue.js
│   │   ├── register.html       ← Registro (2 columnas)
│   │   ├── password-reset.html ← Reset contraseña
│   │   ├── admin/
│   │   │   └── usuarios.html   ← Tabla paginada + modal crear usuario
│   │   └── tickets/
│   │       ├── crear.html      ← Formulario (diferenciado usuario vs admin, Quill.js)
│   │       └── detalle.html    ← Ver ticket + editar estado/asignación (admin)
│   ├── admin/                  ← Alias de pages/admin/ (legacy, mantener)
│   └── assets/
│       ├── css/main.css        ← Estilos únicos (~2100 líneas, todas las clases)
│       ├── js/api.js           ← Cliente HTTP (fetch wrapper hacia /api/)
│       └── js/auth.js          ← Gestión sesión + Auth.requireAuth()
│
├── database/
│   ├── v1/                     ← Backup más antiguo (22 archivos .sql)
│   ├── v2/                     ← Segunda versión
│   ├── v3/                     ← Solo migraciones (migration_v2_v3.sql, views.sql)
│   ├── v4/                     ← Cuarta versión
│   ├── v5/                     ← VERSIÓN ACTUAL (22 archivos .sql + datos reales)
│   └── migrations/
│       └── migration_lugar_incidencia.sql
│
├── dashboard/                  ← Power BI
│   └── powerbi/
│       └── Sistema_Tickets.pbix
│
└── docs/                       ← Documentación
    ├── ESTADO_PROYECTO_v1.md   ← Este archivo
    ├── power_bi_dashboard.md
    └── GIT_GUIA.md
```

---

## Autenticación y roles

**Tipo:** Sesiones PHP (`session_start()`, lifetime 7200s = 2h)

**Flujo:** POST `api/auth.php?action=login` → `password_verify()` contra hash bcrypt → `$_SESSION['usuario']` → middleware `Auth::check()` en cada endpoint.

**Roles:**
| rol_id | nombre | Permisos |
|--------|--------|----------|
| 1 | usuario | Solo ve sus propios tickets |
| 2 | admin | Ve todos, asigna, cambia estado, crea en nombre de otros |

**Usuarios de prueba (insertar en BD si no existen):**

```sql
-- Usuario normal (rol_id = 1)
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol_id`, `activo`)
VALUES ('Usuario Test', 'usuario@test.com',
        '$2y$10$QD6LfIx5UnZlcFZeZ5SrEezqh5Rl.v2FtLIaAtvs5AFjx5kXzQab2', 1, 1);
-- Contraseña: usuario123

-- Administrador (rol_id = 2)
INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol_id`, `activo`)
VALUES ('Admin Test', 'admin@test.com',
        '$2y$10$uq7/h3126vF8P8RfKJ2q6ObZiHkiDWLKd0ZPg5.MMJ45oEYyXAOh.', 2, 1);
-- Contraseña: admin123
```

---

## Base de datos — `bd_tickets`

### Cómo restaurar desde cero

```sql
-- 1. Importar todos los archivos de database/v5/ en orden (catálogos primero)
--    Orden sugerido: roles → estados → prioridades → regiones → sitios →
--    lineas_negocio → tipos_asunto → tipos_solicitud → asuntos →
--    sla_configuracion → usuarios → tickets → (resto)
-- 2. Las vistas ya están incluidas en bd_tickets_routines.sql
```

### Tablas principales

**Catálogo (datos maestros):**
- `roles` — 2 registros: usuario (1), admin (2)
- `estados` — 9 registros (ver abajo)
- `prioridades` — 4: Baja (1), Media (2), Alta (3), Crítica (4)
- `tipos_solicitud` — 4: Incidencia, Requerimiento, Desarrollo, Trino
- `tipos_asunto` — 9 categorías
- `asuntos` — 40 asuntos organizados bajo tipos
- `regiones` — 4: Centro-Oriente, Suroccidente, Capital, Sin región
- `sitios` — 37 sedes físicas
- `lineas_negocio` — 28 áreas/proyectos de la empresa
- `sla_configuracion` — tiempos por prioridad (ver abajo)
- `lugares_incidencia` — catálogo de lugares

**Transaccional:**
- `tickets` — tabla principal (9,435 registros, desde abril 2022)
- `usuarios` — 762 registros (718 usuarios + 44 admins)
- `respuestas` — diálogos en tickets (pendiente de implementar en frontend)
- `adjuntos` — archivos subidos (pendiente)

**Historial / Auditoría:**
- `historial_estados` — cambios de estado de cada ticket
- `historial_reasignaciones` — cambios de admin asignado
- `auditoria` — log general
- `notificaciones` — alertas internas

**Config:**
- `sla_pausas` — pausas del SLA (ej. fines de semana)
- `ticket_relaciones` — vínculos entre tickets relacionados
- `password_resets` — tokens de recuperación de contraseña

### Estados del ticket (9)

| id | nombre | Terminal | Permite reabrir |
|----|--------|----------|-----------------|
| 1 | En espera | No | No |
| 2 | En proceso | No | No |
| 3 | Esperando respuesta | No | No |
| 4 | Contestado | No | Sí |
| 5 | Reabierto | No | No |
| 6 | Reprogramado | No | No |
| 7 | Atendido | No | Sí |
| 8 | Cerrado | **Sí** | No |
| 9 | Rechazado | **Sí** | No |

### SLA por prioridad

| Prioridad | Respuesta | Resolución |
|-----------|-----------|------------|
| Baja (1) | 4h | 48h |
| Media (2) | 2h | 48h |
| Alta (3) | 1h | 8h |
| Crítica (4) | 0.5h | 4h |

El cálculo SLA es en tiempo real en el backend:  
- `vencido` → si supera `horas_resolucion * 60` minutos  
- `en_riesgo` → si supera el 80% del tiempo de resolución  
- `ok` → dentro del SLA

### Constantes en `backend/config/constants.php`

```php
ROL_USUARIO = 1       ROL_ADMIN = 2
ESTADO_EN_ESPERA = 1  ESTADO_EN_PROCESO = 2  ESTADO_ESPERANDO = 3
ESTADO_CONTESTADO = 4 ESTADO_REABIERTO = 5    ESTADO_REPROGRAMADO = 6
ESTADO_ATENDIDO = 7   ESTADO_CERRADO = 8      ESTADO_RECHAZADO = 9
PRIORIDAD_BAJA = 1    PRIORIDAD_MEDIA = 2
PRIORIDAD_ALTA = 3    PRIORIDAD_CRITICA = 4
```

---

## Flujo de navegación

```
http://localhost/sistema_tickets/public/

[No autenticado]
  → pages/login.html         (POST api/auth.php?action=login)
  → pages/register.html      (POST api/auth.php?action=register)
  → pages/password-reset.html

[Usuario autenticado — rol 1]
  index.php                  ← PÁGINA PRINCIPAL (lista sus tickets, filtros sidebar)
  └── pages/tickets/crear.html   (crea ticket, formulario simplificado)
  └── pages/tickets/detalle.html (ve detalle, solo lectura)

[Admin autenticado — rol 2]
  index.php                  ← PÁGINA PRINCIPAL (todos los tickets, KPIs, filtros)
  ├── pages/tickets/crear.html   (crea ticket en nombre de cualquier usuario)
  ├── pages/tickets/detalle.html (edita estado, asigna, agrega solución)
  ├── pages/admin/usuarios.html  (gestión de usuarios)
  └── informes.php               (analíticas: charts + tabla demora por estado)
```

**Importante:** `index.php` es un archivo PHP server-rendered (NO Vue.js). Carga todos los tickets en el HTML inicial y filtra con JS en el cliente. Los filtros del sidebar son client-side sobre los datos ya renderizados. `informes.php` también es PHP server-rendered con Chart.js.

---

## Estadísticas reales de la BD (2026-05-20)

```
Total tickets:     9,435  (desde 2022-04-25 hasta hoy)
Usuarios:            762  (718 usuarios / 44 admins)
Tickets activos:   4,712
Tickets sin admin: 4,099  ← backlog principal sin asignar
Tickets cerrados:  4,206
Tickets rechazados:  517

Por estado:
  En espera:    4,159 (44%)
  Cerrado:      4,206 (44.6%)
  Rechazado:      517 (5.5%)
  En proceso:     396 (4.2%)
  Atendido:       130
  Esperando:       22
  Reprogramado:     5

Por tipo de solicitud:
  Incidencia:   5,329 (56.5%)
  Requerimiento:3,832 (40.6%)
  Trino:          257 (2.7%)
  Desarrollo:      17

Por prioridad:
  Media:    7,270 (77%)
  Alta:     1,411 (15%)
  Baja:       754 (8%)
  Crítica:      0 (actualmente)

Top líneas de negocio:
  Sede Cali Sur:              3,151 (33% del total)
  Sistemas Nacional:            421
  Controlar Costos y Gastos:    252
  Sedes Santanderes:            221

Top asuntos:
  Problemas de software:        720
  Cuenta bloqueada o expirada:  312
  Problemas de hardware:        274
  Mesa de control Trino:        107
  Problemas con sistema AX:     104

Tendencia anual:
  2022: 2,753 | 2023: 2,455 | 2024: 1,448 | 2025: 2,110 | 2026: 669 (ene-may)
```

---

## Vistas disponibles para dashboard

| Vista | Propósito |
|-------|-----------|
| `v_tickets_pendientes` | Tickets activos con alerta SLA en tiempo real |
| `v_sla_cumplimiento` | Historial SLA de tickets cerrados |
| `v_metricas_admin` | Rendimiento por administrador |
| `v_dash_resumen_global` | KPIs globales (1 fila) |
| `v_dash_creados_vs_cerrados` | Tendencia diaria creados vs cerrados |
| `v_dash_por_estado` | Distribución por estado |
| `v_dash_por_prioridad` | Distribución por prioridad |
| `v_dash_por_sitio` | Carga por sede/región |
| `v_dash_por_linea_negocio` | Carga por área |
| `v_dash_por_tipo_asunto` | Distribución por categoría |
| `v_dash_sla_vencidos` | Tickets vencidos con horas de retraso |
| `v_dash_tickets_por_dia` | Serie diaria de tickets |
| `v_promedio_por_prioridad` | Tiempos promedio por prioridad |
| `v_promedio_por_asunto` | Tiempos promedio por asunto |
| `v_promedio_por_estado` | Tiempos promedio por estado |
| `v_tiempos_estado_ticket` | Tiempo en cada estado por ticket |

### ⚠️ Nota: `informes.php` NO usa estas vistas

`public/informes.php` contiene queries SQL inline escritas directamente sobre las tablas base. Equivalen conceptualmente a las vistas pero **no las consumen**. El mapeo es:

| Elemento en `informes.php` | Vista equivalente en BD | Diferencia |
|---|---|---|
| KPIs: total, activos, sin asignar, cerrados | `v_dash_resumen_global` | La vista devuelve una fila con todos los KPIs; informes.php hace 4 queries separadas |
| KPI: vencidos activos | `v_dash_sla_vencidos` | La vista incluye `horas_retraso`; informes.php solo cuenta |
| KPI: % SLA | `v_sla_cumplimiento` | La vista tiene detalle por ticket; informes.php agrega en una query |
| Chart Tendencia | `v_dash_creados_vs_cerrados` / `v_dash_tickets_por_dia` | La vista es diaria global; informes.php usa UNION con filtro de período |
| Chart Por Estado | `v_dash_por_estado` | Equivalentes; informes.php aplica filtros tipo/prioridad |
| Chart Por Prioridad | `v_dash_por_prioridad` | Equivalentes; informes.php aplica filtros |
| Chart Tipo Solicitud | `v_dash_por_tipo_asunto` | La vista agrupa por tipo_asunto; informes.php agrupa por tipo_solicitud |
| Chart SLA por Prioridad | `v_promedio_por_prioridad` | La vista trae tiempos promedio; informes.php calcula dentro/fuera SLA |
| Chart Top Asuntos | `v_promedio_por_asunto` | La vista trae tiempos; informes.php cuenta volumen |
| Tabla demora | `v_promedio_por_estado` | Equivalentes |

**Razón:** Las vistas no aceptan parámetros (filtros por tipo/prioridad), por eso se optó por queries inline que permiten aplicar `WHERE` dinámico. En una refactorización futura se podría usar las vistas para los casos sin filtro y queries inline solo cuando hay filtros activos.

---

## Variables de entorno (.env)

```
APP_ENV=development
APP_URL=http://localhost/sistema_tickets/public

DB_HOST=localhost
DB_PORT=33066          ← IMPORTANTE: puerto personalizado, NO el 3306 estándar
DB_NAME=bd_tickets
DB_USER=tabasco
DB_PASS=tabasco

SESSION_NAME=tickets_session
SESSION_LIFETIME=7200  ← 2 horas
```

---

## Power BI — Estado

### Configuración ODBC
- **DSN:** `LOCAL` (MySQL ODBC 8.0 Unicode Driver → `bd_tickets`)
- **Usuario:** `tabasco` | **Contraseña:** `tabasco`

### Tablas cargadas en el modelo (20)
`tickets`, `estados`, `prioridades`, `tipos_asunto`, `tipos_solicitud`, `regiones`, `sitios`, `lineas_negocio`, `asuntos`, `sla_configuracion`, `usuarios` + las vistas `v_dash_*` y `v_metricas_admin`

### Medidas DAX creadas (17) — tabla `_Medidas`
`Total Tickets`, `Tickets Activos`, `Tickets Cerrados`, `Tickets Rechazados`, `Sin Asignar`, `Criticos Activos`, `Con Gasto`, `Dentro SLA`, `Fuera SLA`, `Pct Cumplimiento SLA`, `Vencidos SLA`, `En Riesgo SLA`, `Promedio Resolucion h`, `Creados Ultimos 30d`, `Cerrados Ultimos 30d`, `Tasa Resolucion`, `Color SLA`

### Relaciones pendientes de crear (vista Modelo de PBI Desktop)
```
tickets[estado_id]         → estados[id]
tickets[prioridad_id]      → prioridades[id]
tickets[sitio_id]          → sitios[id]
tickets[linea_negocio_id]  → lineas_negocio[id]
tickets[asunto_id]         → asuntos[id]
tickets[tipo_solicitud_id] → tipos_solicitud[id]
tickets[usuario_id]        → usuarios[id]
asuntos[tipo_asunto_id]    → tipos_asunto[id]
sitios[region_id]          → regiones[id]
```

### Cómo recrear el modelo desde cero
```
1. PBI Desktop → Obtener datos → ODBC → DSN=LOCAL
2. Seleccionar las 20 tablas/vistas → Cargar
3. Guardar como Sistema_Tickets.pbix
4. Ejecutar: dashboard/powerbi/9_agregar_medidas_amo.ps1  (con PBI abierto)
5. Crear relaciones manualmente en vista Modelo
6. Seguir docs/power_bi_dashboard.md para visuals
```

### Notas de compatibilidad
- **pbi-tools 1.2.0** es INCOMPATIBLE con PBI Desktop 2.153.x (error `MissingMethodException` en `PowerBIPackager.Save`). No usar `compile`. Usar Tabular Editor 2 + AMO.
- **Tabular Editor 2.28** — ruta: `dashboard/powerbi/_tabular_editor/`

---

## Funcionalidades implementadas

### Backend (completo)
- [x] Login / Logout / Registro con bcrypt (`PASSWORD_BCRYPT`)
- [x] Reset de contraseña con token
- [x] CRUD completo de tickets
- [x] Cambio de estado con registro en `historial_estados`
- [x] Asignación de admin con registro en `historial_reasignaciones`
- [x] Filtros: estado, prioridad, tipo_solicitud, busqueda, usuario_id
- [x] Cálculo SLA en tiempo real (ok / en_riesgo / vencido)
- [x] Paginación en listados
- [x] Middleware de autenticación y roles en todos los endpoints
- [x] KPIs globales via `DashboardController`
- [x] 16 vistas SQL para analíticas

### Frontend (completo)
- [x] `index.php` — lista de tickets, sidebar con filtros dropdown, KPIs admin, búsqueda
- [x] `informes.php` — analíticas admin: 4 KPIs, 2 charts (estado, tiempo promedio), tabla demora
- [x] `pages/login.html` — login Vue.js
- [x] `pages/register.html` — registro 2 columnas
- [x] `pages/password-reset.html` — reset contraseña
- [x] `pages/tickets/crear.html` — formulario diferenciado usuario/admin, Quill.js
- [x] `pages/tickets/detalle.html` — detalle + edición admin (estado, asignación, solución)
- [x] `pages/admin/usuarios.html` — tabla paginada + modal crear usuario
- [x] `assets/js/api.js` — cliente HTTP completo para todos los endpoints
- [x] `assets/js/auth.js` — gestión de sesión con sessionStorage
- [x] `assets/css/main.css` — sistema de estilos completo (~2100 líneas)

### Power BI (parcial)
- [x] 17 medidas DAX creadas en tabla `_Medidas`
- [x] 20 tablas/vistas cargadas
- [ ] Relaciones pendientes de crear en vista Modelo

---

## Pendientes (v2)

- [ ] Crear relaciones en Power BI (vista Modelo) y las 4 páginas de reporte
- [ ] Módulo de respuestas: backend existe (`respuestas` table), falta UI
- [ ] Módulo de adjuntos: backend parcial, falta subida y descarga real de archivos
- [ ] Notificaciones por email (tabla `notificaciones` existe, falta envío)
- [ ] Asignación automática / por cola (4,099 tickets sin admin asignado)
- [ ] Gestión de usuarios admin: editar, activar/desactivar, cambiar rol
- [ ] Organizar `database/`: los ~22 .sql en la raíz son duplicados más viejos que v5
