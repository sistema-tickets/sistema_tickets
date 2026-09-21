<?php
require_once __DIR__ . '/../backend/config/app.php';
require_once __DIR__ . '/../backend/middleware/Auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();
Auth::logout();
?>
<script>sessionStorage.clear();location.href='pages/login.html';</script>
