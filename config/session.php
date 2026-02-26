<?php
/**
 * Configuración de Sesiones - Helpdesk Clonsa
 * config/session.php
 * 
 * IMPORTANTE: Este archivo debe cargarse ANTES de session_start()
 * para evitar el error "Session ini settings cannot be changed when a session is active"
 */

// Configurar directorio de sesiones personalizado (solución para permisos en Laragon)
$session_path = __DIR__ . '/../sessions';

// Crear directorio si no existe
if (!file_exists($session_path)) {
    mkdir($session_path, 0777, true);
}

// Configurar PHP para usar el directorio personalizado
// IMPORTANTE: Esto debe ejecutarse ANTES de session_start()
ini_set('session.save_path', $session_path);
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 3600); // 1 hora - debe coincidir con SESSION_TIMEOUT

// Configuraciones adicionales de seguridad
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');

// HTTPS: cookie segura en produccion
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
