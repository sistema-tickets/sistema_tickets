# Tablero Power BI — Sistema de Tickets
**Guía completa de configuración y diseño**

---

## Requisitos previos

| Requisito | Detalle |
|---|---|
| Power BI Desktop | Versión más reciente |
| Driver ODBC | MySQL ODBC 8.0 Unicode Driver |
| DSN configurado | Nombre: `LOCAL` → apunta a `bd_tickets` en localhost |
| Migración aplicada | `database/v3/migration_v2_v3.sql` ejecutado |
| Vistas aplicadas | `database/v3/views.sql` ejecutado |

---

## Paso 1 — Conectar Power BI a MySQL vía ODBC

1. Abre **Power BI Desktop**.
2. Ve a **Inicio → Obtener datos → Más…**
3. Busca **ODBC** → clic en **Conectar**.
4. En el menú desplegable de DSN selecciona **LOCAL** → **Aceptar**.
5. Si pide credenciales, selecciona **Base de datos** e ingresa el usuario y contraseña de MySQL (por defecto en XAMPP: usuario `root`, contraseña vacía).
6. Clic en **Conectar**.

> **Modo de conexión:** usa siempre **Importar** (no DirectQuery). El modo ODBC con DirectQuery tiene restricciones en DAX y es más lento para reportes.

---

## Paso 2 — Seleccionar tablas y vistas a importar

En el **Navegador** que aparece, expande `bd_tickets` y marca exactamente las siguientes:

### Tablas dimensión

| Tabla | Propósito |
|---|---|
| `estados` | Etiquetas y filtros de estado |
| `prioridades` | Nivel de urgencia |
| `tipos_asunto` | Categoría del problema |
| `tipos_solicitud` | Tipo de solicitud |
| `regiones` | Agrupación geográfica |
| `sitios` | Sede o ubicación física |
| `lineas_negocio` | Área de la empresa |
| `asuntos` | SLA específico por asunto |
| `sla_configuracion` | SLA por prioridad (fallback) |

### Tabla de hechos

| Tabla | Propósito |
|---|---|
| `tickets` | Hecho principal — toda la actividad del sistema |

### Vistas pre-calculadas

| Vista | Propósito |
|---|---|
| `v_sla_cumplimiento` | Historial SLA de tickets cerrados |
| `v_metricas_admin` | Rendimiento y SLA por administrador |
| `v_tickets_pendientes` | Tickets activos con alerta SLA en tiempo real |
| `v_dash_creados_vs_cerrados` | Tendencia diaria: creados vs cerrados |
| `v_dash_por_sitio` | Carga de tickets por sede y región |
| `v_dash_por_linea_negocio` | Carga por área de negocio |
| `v_dash_por_tipo_asunto` | Distribución por categoría |
| `v_promedio_por_prioridad` | SLA y tiempos promedio por prioridad |
| `v_promedio_por_asunto` | SLA y tiempos promedio por asunto |

Clic en **Cargar**.

---

## Paso 3 — Crear las dos copias de Usuarios

La tabla `usuarios` tiene dos roles distintos en los tickets: **solicitante** (`usuario_id`) y **administrador** (`admin_id`). Para que Power BI maneje ambas relaciones sin conflicto, crea dos consultas separadas.

1. Ve a **Inicio → Transformar datos** (abre Power Query).
2. En el panel izquierdo, busca la consulta `usuarios`.
3. Clic derecho → **Duplicar**.
4. Renombra la copia como `Administradores`.
5. En la consulta `Administradores`, ve a **Agregar columna → Columna personalizada** o simplemente usa el filtro:
   - Haz clic en la flecha de la columna `rol_id` → filtra por valor `2`.
6. Renombra la consulta original `usuarios` como `Solicitantes`.
7. Clic en **Cerrar y aplicar**.

---

## Paso 4 — Crear el modelo de datos (Relaciones)

Ve a la vista **Modelo** (ícono de diagrama en la barra izquierda).

Crea las siguientes relaciones arrastrando campos entre tablas. Todas son de tipo **N a 1**, con dirección de filtro cruzado de `tickets` → dimensión.

### Relaciones de tickets con dimensiones

| Desde (tickets) | Hacia (dimensión) | Campo |
|---|---|---|
| `tickets[estado_id]` | `estados[id]` | estado |
| `tickets[prioridad_id]` | `prioridades[id]` | prioridad |
| `tickets[sitio_id]` | `sitios[id]` | sitio |
| `tickets[linea_negocio_id]` | `lineas_negocio[id]` | área |
| `tickets[asunto_id]` | `asuntos[id]` | asunto |
| `tickets[tipo_solicitud_id]` | `tipos_solicitud[id]` | tipo |
| `tickets[usuario_id]` | `Solicitantes[id]` | quién creó |
| `tickets[admin_id]` | `Administradores[id]` | quién atiende |
| `tickets[prioridad_id]` | `sla_configuracion[prioridad_id]` | SLA fallback |

### Relaciones entre dimensiones

| Desde | Hacia | Campo |
|---|---|---|
| `asuntos[tipo_asunto_id]` | `tipos_asunto[id]` | categoría |
| `sitios[region_id]` | `regiones[id]` | región |

> **Nota:** Las vistas (`v_sla_cumplimiento`, `v_metricas_admin`, etc.) son tablas independientes — no necesitan relaciones porque ya traen todos los joins resueltos desde MySQL.

---

## Paso 5 — Crear tabla de medidas

1. Ve a la vista **Datos** (ícono de tabla).
2. En la cinta superior: **Modelado → Nueva tabla**.
3. Escribe:
   ```
   _Medidas = DATATABLE("x", STRING, {{""}})
   ```
4. Renombra la tabla como `_Medidas`.
5. Todas las medidas DAX que crees a continuación irán aquí.

---

## Paso 6 — Medidas DAX

Selecciona la tabla `_Medidas` y crea cada medida con **Modelado → Nueva medida**.

### 6.1 KPIs principales

```dax
Total Tickets =
COUNTROWS(tickets)
```

```dax
Tickets Activos =
CALCULATE(
    COUNTROWS(tickets),
    tickets[estado_id] NOT IN {8, 9}
)
```

```dax
Tickets Cerrados =
CALCULATE(COUNTROWS(tickets), tickets[estado_id] = 8)
```

```dax
Tickets Rechazados =
CALCULATE(COUNTROWS(tickets), tickets[estado_id] = 9)
```

```dax
Sin Asignar =
CALCULATE(
    COUNTROWS(tickets),
    ISBLANK(tickets[admin_id]),
    tickets[estado_id] NOT IN {8, 9}
)
```

```dax
Críticos Activos =
CALCULATE(
    COUNTROWS(tickets),
    tickets[prioridad_id] = 4,
    tickets[estado_id] NOT IN {8, 9}
)
```

```dax
Con Gasto =
CALCULATE(COUNTROWS(tickets), tickets[genera_gasto] = 1)
```

### 6.2 Métricas SLA

```dax
Dentro SLA =
CALCULATE(
    COUNTROWS(v_sla_cumplimiento),
    v_sla_cumplimiento[resultado_sla] = "dentro_sla"
)
```

```dax
Fuera SLA =
CALCULATE(
    COUNTROWS(v_sla_cumplimiento),
    v_sla_cumplimiento[resultado_sla] = "fuera_sla"
)
```

```dax
% Cumplimiento SLA =
DIVIDE([Dentro SLA], [Dentro SLA] + [Fuera SLA], BLANK())
```

```dax
Vencidos SLA =
CALCULATE(
    COUNTROWS(v_tickets_pendientes),
    v_tickets_pendientes[sla_estado] = "vencido"
)
```

```dax
En Riesgo SLA =
CALCULATE(
    COUNTROWS(v_tickets_pendientes),
    v_tickets_pendientes[sla_estado] = "en_riesgo"
)
```

```dax
Promedio Resolución (h) =
AVERAGEX(
    FILTER(
        tickets,
        tickets[estado_id] = 8 &&
        NOT ISBLANK(tickets[closed_at])
    ),
    DATEDIFF(tickets[created_at], tickets[closed_at], HOUR)
)
```

### 6.3 Tendencias

```dax
Creados Últimos 30d =
CALCULATE(
    [Total Tickets],
    DATESINPERIOD(tickets[created_at], TODAY(), -30, DAY)
)
```

```dax
Cerrados Últimos 30d =
CALCULATE(
    [Tickets Cerrados],
    DATESINPERIOD(tickets[closed_at], TODAY(), -30, DAY)
)
```

```dax
Tasa Resolución =
DIVIDE([Tickets Cerrados], [Total Tickets], 0)
```

### 6.4 Formato condicional (color por estado SLA)

```dax
Color SLA =
SWITCH(
    SELECTEDVALUE(v_tickets_pendientes[sla_estado]),
    "vencido",   "#DC2626",
    "en_riesgo", "#D97706",
    "ok",        "#16A34A",
    "sin_sla",   "#94A3B8",
    "#94A3B8"
)
```

---

## Paso 7 — Diseño del tablero

El tablero se organiza en **4 páginas**. Renombra las páginas haciendo doble clic en la pestaña.

---

### Página 1 — Resumen Ejecutivo

**Segmentadores (parte superior):**
- `tickets[created_at]` → tipo: Rango de fechas relativo

**Fila de tarjetas KPI (6 tarjetas, de izquierda a derecha):**

| Tarjeta | Medida | Formato |
|---|---|---|
| Total Tickets | `[Total Tickets]` | Número entero |
| Activos | `[Tickets Activos]` | Número entero |
| Cerrados | `[Tickets Cerrados]` | Número entero |
| Vencidos SLA | `[Vencidos SLA]` | Número entero — color rojo |
| Sin Asignar | `[Sin Asignar]` | Número entero — color naranja |
| Críticos | `[Críticos Activos]` | Número entero — color rojo oscuro |

**Visual 1 — Gráfico de dona: distribución por estado**
- Leyenda: `v_dash_por_estado[estado]`
- Valores: `v_dash_por_estado[total]`
- Título: *Tickets por estado*

**Visual 2 — Gráfico de línea: tendencia creados vs cerrados**
- Eje X: `v_dash_creados_vs_cerrados[fecha]`
- Línea 1: `v_dash_creados_vs_cerrados[tickets_creados]`
- Línea 2: `v_dash_creados_vs_cerrados[tickets_cerrados]`
- Título: *Tendencia últimos 30 días*

**Visual 3 — Barras agrupadas: activos vs cerrados por prioridad**
- Eje Y: `prioridades[nombre]`
- Valor 1: `[Tickets Activos]` (azul)
- Valor 2: `[Tickets Cerrados]` (verde)
- Ordenar por `prioridades[orden]` descendente

**Visual 4 — Medidor: cumplimiento SLA**
- Valor: `[% Cumplimiento SLA]`
- Mínimo: `0` — Máximo: `1`
- Objetivo: `0.9` (90%)
- Formato: porcentaje con 1 decimal

---

### Página 2 — Operaciones

**Segmentadores:**
- `v_tickets_pendientes[sla_estado]` → lista
- `v_tickets_pendientes[prioridad]` → lista
- `v_tickets_pendientes[admin_asignado]` → lista desplegable

**Visual principal — Tabla: tickets activos con alerta SLA**

Campos a mostrar (en este orden):

| Campo | Tabla |
|---|---|
| `numero_ticket` | `v_tickets_pendientes` |
| `descripcion` | `v_tickets_pendientes` |
| `estado_actual` | `v_tickets_pendientes` |
| `prioridad` | `v_tickets_pendientes` |
| `usuario` | `v_tickets_pendientes` |
| `admin_asignado` | `v_tickets_pendientes` |
| `sitio` | `v_tickets_pendientes` |
| `horas_desde_creacion` | `v_tickets_pendientes` |
| `sla_horas_restantes` | `v_tickets_pendientes` |
| `sla_estado` | `v_tickets_pendientes` |

**Formato condicional en columna `sla_estado`:**
1. Clic en la columna → Formato condicional → Color de fondo.
2. Tipo: **Campo** → selecciona la medida `[Color SLA]`.

**Visual 2 — Barras horizontales: carga por administrador**
- Eje Y: `Administradores[nombre]`
- Valor: `[Tickets Activos]`
- Ordenar: descendente

**Visual 3 — Barras apiladas: SLA por prioridad**
- Eje Y: `prioridades[nombre]`
- Pila 1: `[Vencidos SLA]` (rojo)
- Pila 2: `[En Riesgo SLA]` (naranja)
- Ordenar por `prioridades[orden]`

---

### Página 3 — Rendimiento y SLA

**Visual principal — Tabla: métricas por administrador**

Campos:

| Campo | Tabla |
|---|---|
| `admin` | `v_metricas_admin` |
| `total_asignados` | `v_metricas_admin` |
| `activos` | `v_metricas_admin` |
| `cerrados` | `v_metricas_admin` |
| `promedio_horas_resolucion` | `v_metricas_admin` |
| `promedio_horas_primera_respuesta` | `v_metricas_admin` |
| `pct_cumplimiento_sla` | `v_metricas_admin` |

**Formato condicional en `pct_cumplimiento_sla`:**
- Tipo: escala de colores → rojo (0%) a verde (100%).

**Visual 2 — Barras: % SLA por prioridad**
- Eje Y: `v_promedio_por_prioridad[prioridad]`
- Valor: `v_promedio_por_prioridad[pct_cumplimiento_sla]`
- Línea de referencia constante: 90 (objetivo)

**Visual 3 — Barras horizontales: % SLA por asunto (Top 10)**
- Eje Y: `v_promedio_por_asunto[asunto]`
- Valor: `v_promedio_por_asunto[pct_cumplimiento_sla]`
- Filtro: `total_tickets >= 5` (excluye asuntos con muy pocos datos)
- Ordenar: ascendente (los peores primero)

**Visual 4 — Dispersión: resolución vs % SLA por admin**
- Eje X: `v_metricas_admin[promedio_horas_resolucion]`
- Eje Y: `v_metricas_admin[pct_cumplimiento_sla]`
- Leyenda: `v_metricas_admin[admin]`
- Tamaño de burbuja: `v_metricas_admin[total_asignados]`

---

### Página 4 — Distribución

**Segmentador:**
- `regiones[nombre]` → afecta el visual de sitios

**Visual 1 — Barras horizontales: carga por sitio**
- Eje Y: `v_dash_por_sitio[sitio]`
- Valor activos: `v_dash_por_sitio[activos]` (azul)
- Valor cerrados: `v_dash_por_sitio[cerrados]` (verde)
- Leyenda de región: `v_dash_por_sitio[region]`
- Ordenar: por activos descendente

**Visual 2 — Barras: carga por línea de negocio**
- Eje Y: `v_dash_por_linea_negocio[linea_negocio]`
- Valor: `v_dash_por_linea_negocio[activos]`
- Color: escala según `alta_prioridad_activos`

**Visual 3 — Barras: distribución por tipo de asunto**
- Eje Y: `v_dash_por_tipo_asunto[tipo_asunto]`
- Valor: `v_dash_por_tipo_asunto[total_tickets]`
- Ordenar: descendente

---

## Paso 8 — Estilo y tema

### Paleta de colores del sistema

| Uso | Código hex |
|---|---|
| Azul principal (sidebar) | `#1A3A6B` |
| Azul oscuro | `#112D57` |
| Acento dorado | `#F5A623` |
| Fondo general | `#F0F2F7` |
| Rojo (vencido) | `#DC2626` |
| Naranja (riesgo) | `#D97706` |
| Verde (ok) | `#16A34A` |
| Gris (sin SLA) | `#94A3B8` |

### Aplicar tema personalizado

1. Ve a **Vista → Temas → Personalizar tema actual**.
2. En **Colores del tema**, establece los primeros colores con la paleta anterior.
3. Fuente: **DM Sans** (si está disponible) o **Segoe UI**.
4. Guarda el tema como `tema_tickets.json` para reutilizarlo.

### Configuraciones generales recomendadas

- **Tarjetas KPI:** quitar etiqueta de categoría, aumentar tamaño del valor a 32px, agregar ícono de tendencia.
- **Tablas:** activar **Filas alternas** con color `#F8F9FB`.
- **Todos los gráficos:** desactivar líneas de cuadrícula verticales, mantener horizontales en gris claro `#DDE3EF`.
- **Títulos:** negrita, 14px, color `#13223A`.

---

## Paso 9 — Actualización de datos

### Actualización manual (desarrollo)
- **Inicio → Actualizar** — reconsulta MySQL y actualiza todos los datos.

### Actualización programada (producción con Power BI Service)
1. Publica el informe en Power BI Service: **Inicio → Publicar**.
2. En el servicio, ve al dataset → **Programar actualización**.
3. Configura la frecuencia (recomendado: cada hora en horario laboral).
4. Requiere instalar el **Power BI Gateway (Personal Mode)** en el equipo donde corre XAMPP.

---

## Resumen de vistas por página

| Página | Tablas / Vistas usadas |
|---|---|
| Resumen Ejecutivo | `tickets`, `prioridades`, `v_dash_por_estado`, `v_dash_creados_vs_cerrados` |
| Operaciones | `v_tickets_pendientes`, `Administradores`, `prioridades` |
| Rendimiento SLA | `v_metricas_admin`, `v_promedio_por_prioridad`, `v_promedio_por_asunto` |
| Distribución | `v_dash_por_sitio`, `v_dash_por_linea_negocio`, `v_dash_por_tipo_asunto`, `regiones` |

---

## Solución de problemas frecuentes

| Problema | Causa | Solución |
|---|---|---|
| Error de conexión ODBC | MySQL no está corriendo | Iniciar Apache y MySQL en XAMPP |
| Vista no aparece en el navegador | Vista no fue creada aún | Ejecutar `database/v3/views.sql` en MySQL |
| Relación genera error circular | Dos relaciones a `usuarios` | Verificar que uses `Solicitantes` y `Administradores` por separado |
| DAX devuelve BLANK en tarjeta | Sin datos en el período filtrado | Normal — el filtro de fecha excluye los registros |
| `% Cumplimiento SLA` es BLANK | No hay tickets cerrados aún | Agregar datos de prueba o quitar filtro de fecha |
| Tabla muy lenta al cargar | `tickets` tiene miles de registros | En Power Query: quitar columnas innecesarias antes de cargar |
