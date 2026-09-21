<?php
require_once __DIR__ . '/../../backend/config/app.php';
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/config/constants.php';
require_once __DIR__ . '/../../backend/middleware/Auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$usuario = Auth::usuario();
if ($usuario) {
    header('Location: ../index.php');
    exit;
}

$db = getDB();
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre']    ?? '');
    $email     = trim($_POST['email']     ?? '');
    $sitio_id  = $_POST['sitio_id']       ?? '';
    $password  = $_POST['password']       ?? '';
    $confirmar = $_POST['confirmar']      ?? '';

    if (empty($nombre) || empty($email) || empty($password) || empty($confirmar) || empty($sitio_id)) {
        $error = 'Por favor completa todos los campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo electrónico no es válido.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($password !== $confirmar) {
        $error = 'Las contraseñas no coinciden.';
    } else {
        $stmt = $db->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'El correo ya está registrado.';
        } else {
            $stmt = $db->prepare(
                'INSERT INTO usuarios (nombre, email, password, rol_id, sitio_id)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $nombre,
                $email,
                password_hash($password, PASSWORD_BCRYPT),
                ROL_USUARIO,
                $sitio_id ? (int)$sitio_id : null,
            ]);
            $success = 'Registro exitoso. Ahora puedes iniciar sesión.';
        }
    }
}

$sitios = $db->query('SELECT id, nombre FROM sitios ORDER BY nombre')->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro — Sistema de Incidencias</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons/css/regular-rounded.css">
    <link rel="stylesheet" href="../assets/css/main.css">
</head>
<body class="login-body">

    <div class="login-card" style="max-width:600px">

        <div class="login-logo">
            <img src="../assets/img/logo.jpg" alt="Logo">
        </div>

        <h1 class="login-title">Crear cuenta</h1>
        <p class="login-sub">Completa los datos para registrarte en el sistema.</p>

        <?php if ($error): ?>
            <div class="login-error">
                <span>⚠</span> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="login-success">
                <span>✓</span> <?= htmlspecialchars($success) ?>
            </div>
            <a href="login.html" class="btn btn-primary btn-login" style="text-align:center;text-decoration:none;margin-top:8px;">
                Ir al inicio de sesión →
            </a>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" class="login-form" novalidate>
            <div class="form-group">
                <label for="nombre">Nombre completo</label>
                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    placeholder="Tu nombre"
                    value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                    required
                    autocomplete="name"
                >
            </div>

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="tucorreo@empresa.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="sitio_id">Sede</label>
                <select id="sitio_id" name="sitio_id" required class="form-select">
                    <option value="" disabled <?= empty($_POST['sitio_id']) ? 'selected' : '' ?>>Selecciona tu sede</option>
                    <?php foreach ($sitios as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= ($_POST['sitio_id'] ?? '') == $s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-password-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Mínimo 6 caracteres"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-pass" onclick="togglePass('password')" title="Mostrar/ocultar contraseña">
                            <span><i class="fi fi-rs-eye"></i></span>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmar">Confirmar contraseña</label>
                    <div class="input-password-wrap">
                        <input
                            type="password"
                            id="confirmar"
                            name="confirmar"
                            placeholder="Repite la contraseña"
                            required
                            autocomplete="new-password"
                        >
                        <button type="button" class="toggle-pass" onclick="togglePass('confirmar')" title="Mostrar/ocultar contraseña">
                            <span><i class="fi fi-rs-eye"></i></span>
                        </button>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                Registrarse →
            </button>

            <p class="login-sub-center">¿Ya tienes cuenta? <a href="login.html">Inicia sesión</a></p>
        </form>
        <?php endif; ?>

    </div>

    <script>
    function togglePass(id) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }
    </script>
</body>
</html>
