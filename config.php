<?php
// Configuración de seguridad adicional para las sesiones
ini_set('session.cookie_httponly', 1); // Evita acceso a cookies por JavaScript
ini_set('session.cookie_secure', 1); // Requiere HTTPS para las cookies si está disponible
ini_set('session.use_strict_mode', 1); // Previene ataques de fijación de sesión

// Inicia la sesión
session_start();
?>
