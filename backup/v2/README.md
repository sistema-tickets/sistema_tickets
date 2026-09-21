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
│   │   ├── TicketController.php
│   │   └── UsuarioController.php
│   ├── helpers/
│   │   ├── Response.php        ← Respuestas JSON estandarizadas
│   │   └── Validator.php       ← Validación de datos de entrada
│   ├── middleware/
│   │   ├── Auth.php            ← Verificación de sesión / roles
│   │   └── Cors.php            ← Cabeceras CORS
│   └── models/
│       ├── TicketModel.php
│       └── UsuarioModel.php
│
├── public/                     ← Raíz web (Apache/XAMPP apunta aquí)
│   ├── .htaccess
│   ├── index.php               ← Redirige a pages/login.html
│   │
│   ├── api/                    ← Endpoints REST (PHP, accesibles vía HTTP)
│   │   ├── auth.php            ← POST login/logout, GET me
│   │   ├── tickets.php         ← CRUD tickets
│   │   └── usuarios.php        ← CRUD usuarios
│   │
│   ├── pages/                  ← Páginas HTML del frontend
│   │   ├── login.html
│   │   ├── dashboard.html
│   │   ├── admin/
│   │   │   └── usuarios.html
│   │   └── tickets/
│   │       ├── crear.html
│   │       └── detalle.html
│   │
│   └── assets/                 ← Archivos estáticos del frontend
│       ├── css/
│       │   └── main.css
│       ├── img/
│       └── js/
│           ├── api.js          ← Cliente HTTP para la API REST
│           ├── auth.js         ← Manejo de sesión en el navegador
│           └── components/
│
├── database/                   ← Scripts SQL (uno por tabla)
│   ├── bd_tickets_tickets.sql
│   ├── bd_tickets_usuarios.sql
│   └── ... (un archivo por tabla)
│
├── docs/                       ← Documentación y diseño
│   ├── mockups/                ← Mockups de la interfaz (PNG)
│   └── referencias/            ← Capturas de referencia (Power Apps, etc.)
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
| Páginas HTML | `public/pages/` |
| CSS, JS de frontend, imágenes de la UI | `public/assets/` |
| Scripts SQL de base de datos | `database/` |
| Mockups y docs de diseño | `docs/` |
| Archivos subidos por usuarios | `uploads/` |

---

## Instalación local (XAMPP)

1. Clonar el repo en `C:\xampp\htdocs\sistema_tickets\`
2. Copiar `.env.example` a `.env` y completar las variables
3. Importar los scripts de `database/` en MySQL (en orden de dependencias)
4. Configurar el Virtual Host o acceder desde `http://localhost/sistema_tickets/public/`

---

## Arquitectura

- **Frontend**: HTML estático + Vue 3 (CDN) + CSS propio. Sin bundler ni dependencias npm.
- **Backend**: PHP 8.1+, arquitectura MVC manual. Sin framework.
- **API**: REST JSON. Los endpoints en `public/api/` reciben `$_GET['action']` o `$_GET['id']` para el routing.
- **Autenticación**: Sesiones PHP (`session_start`). El middleware `Auth.php` protege cada endpoint.
- **Base de datos**: MySQL vía PDO. Cada modelo se conecta a través de `backend/config/database.php`.
