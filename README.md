# Sistema de Tickets

Sistema web de gestión de tickets de soporte. Backend en PHP puro (sin framework), frontend en HTML + Vue 3 + CSS propio.

---

## Estructura del proyecto

```
sistema_tickets/
├── .env                        ← Variables de entorno (DB, sesión, etc.)
├── .env.example                ← Plantilla de variables de entorno
├── .gitignore
├── README.md
│
├── backend/                    ← Código PHP del servidor (NO accesible vía web)
│   ├── config/
│   │   ├── app.php             ← Carga .env, configura sesión y errores
│   │   ├── constants.php       ← Constantes globales (roles, estados, etc.)
│   │   └── database.php        ← Conexión PDO a MySQL
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── CatalogoController.php
│   │   ├── DashboardController.php
│   │   ├── TicketController.php
│   │   └── UsuarioController.php
│   ├── helpers/
│   │   ├── Response.php        ← Respuestas JSON estandarizadas
│   │   └── Validator.php       ← Validación de datos de entrada
│   ├── middleware/
│   │   ├── Auth.php            ← Verificación de sesión / roles
│   │   └── Cors.php            ← Cabeceras CORS
│   └── models/
│       ├── DashboardModel.php
│       ├── TicketModel.php
│       └── UsuarioModel.php
│
├── public/                     ← Raíz web (Apache/XAMPP apunta aquí)
│   ├── .htaccess
│   ├── index.php               ← Entry point principal (lista de tickets)
│   ├── informes.php            ← Vista de informes (admin)
│   ├── download.php            ← Descarga de adjuntos
│   ├── login.html              ← Página de login (acceso directo)
│   ├── logout.php              ← Cierre de sesión
│   ├── dashboard.html          ← Dashboard legacy (Vue 3)
│   │
│   ├── api/                    ← Endpoints REST (PHP, accesibles vía HTTP)
│   │   ├── auth.php            ← POST login/logout, GET me
│   │   ├── catalogos.php       ← Lectura de catálogos (estados, prioridades, etc.)
│   │   ├── dashboard.php       ← Estadísticas para el dashboard
│   │   ├── tickets.php         ← CRUD tickets
│   │   ├── upload.php          ← Subida de archivos adjuntos
│   │   └── usuarios.php        ← CRUD usuarios
│   │
│   ├── pages/                  ← Páginas del frontend (PHP + HTML)
│   │   ├── login.html
│   │   ├── register.html
│   │   ├── register.php
│   │   ├── password-reset.html
│   │   ├── dashboard.html
│   │   ├── admin/
│   │   │   ├── informes.php    ← Informes y métricas (admin + superadmin)
│   │   │   ├── usuarios.html
│   │   │   ├── usuarios.php
│   │   │   ├── catalogos.html
│   │   │   └── catalogos.php
│   │   └── tickets/
│   │       ├── lista.html
│   │       ├── crear.html
│   │       └── detalle.html
│   │
│   ├── admin/
│   │   └── usuarios.html
│   │
│   └── assets/                 ← Archivos estáticos del frontend
│       ├── css/
│       │   └── main.css
│       ├── img/
│       │   └── logo.jpg
│       ├── inc/                ← Includes PHP reutilizables (sidebar, header)
│       │   ├── header.php
│       │   └── sidebar.php
│       └── js/
│           ├── api.js          ← Cliente HTTP para la API REST
│           ├── auth.js         ← Manejo de sesión en el navegador
│           └── header.js
│
├── database/                   ← Scripts SQL (uno por tabla)
│   ├── bd_tickets_tickets.sql
│   ├── bd_tickets_usuarios.sql
│   └── ... (un archivo por tabla)
│
├── docs/                       ← Documentación del proyecto
│
└── uploads/                    ← Archivos subidos por usuarios (no versionados)
    └── tickets/
```

---

## Dónde va cada cosa

| Tipo de archivo | Carpeta |
|---|---|
| Lógica PHP (modelos, controllers, helpers) | `backend/` |
| Endpoints REST accesibles por HTTP | `public/api/` |
| Includes PHP reutilizables (sidebar, header) | `public/assets/inc/` |
| Páginas principales con lógica PHP | `public/pages/` |
| CSS, JS de frontend, imágenes de la UI | `public/assets/` |
| Scripts SQL de base de datos | `database/` |
| Archivos subidos por usuarios | `uploads/` |

---

## Roles

| Constante | ID | Acceso |
|---|---|---|
| `ROL_USUARIO` | 1 | Solo sus propios tickets |
| `ROL_ADMIN` | 2 | Todos los tickets + Informes |
| `ROL_SUPERADMIN` | 3 | Todo lo anterior + Usuarios y Configuración |

---

## Instalación local (XAMPP)

1. Clonar el repo en `C:\xampp\htdocs\sistema_tickets\`
2. Copiar `.env.example` a `.env` y completar las variables
3. Importar los scripts de `database/` en MySQL (en orden de dependencias)
4. Configurar el Virtual Host o acceder desde `http://localhost/sistema_tickets/public/`

---

## Arquitectura

- **Frontend**: HTML + PHP + Vue 3 (CDN) + CSS propio. Sin bundler ni dependencias npm.
- **Backend**: PHP 8.1+, arquitectura MVC manual. Sin framework.
- **API**: REST JSON. Los endpoints en `public/api/` reciben `$_GET['action']` o `$_GET['id']` para el routing.
- **Autenticación**: Sesiones PHP (`session_start`). El middleware `Auth.php` protege cada endpoint.
- **Base de datos**: MySQL vía PDO. Cada modelo se conecta a través de `backend/config/database.php`.
