<?php
/**
 * Session Check - Verificación de Sesión
 * Incluir en TODAS las páginas protegidas (dashboard, tickets, etc.)
 * Archivo: includes/session-check.php
 * 
 * Uso: require_once 'includes/session-check.php';
 */

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración
require_once __DIR__ . '/../config/config.php';

// ═══════════════════════════════════════════════════════════════════
// VERIFICAR AUTENTICACIÓN
// ═══════════════════════════════════════════════════════════════════

if (!isset($_SESSION['user_id'])) {
    // No hay sesión activa, redirigir a login
    header('Location: login.php');
    exit;
}

// ═══════════════════════════════════════════════════════════════════
// VERIFICAR TIMEOUT DE SESIÓN
// ═══════════════════════════════════════════════════════════════════

// Si no existe last_activity, establecerla ahora
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

// Calcular tiempo de inactividad
$inactiveTime = time() - $_SESSION['last_activity'];

// Si excede el timeout, cerrar sesión
if ($inactiveTime > SESSION_TIMEOUT) {
    // Guardar mensaje
    $_SESSION['timeout_message'] = 'Tu sesión expiró por inactividad';
    
    // Destruir sesión
    session_unset();
    session_destroy();
    
    // Redirigir a login
    header('Location: login.php?timeout=1');
    exit;
}

// Actualizar timestamp de última actividad
$_SESSION['last_activity'] = time();

// ═══════════════════════════════════════════════════════════════════
// REGENERAR ID DE SESIÓN PERIÓDICAMENTE (SEGURIDAD)
// ═══════════════════════════════════════════════════════════════════

if (!isset($_SESSION['session_created'])) {
    $_SESSION['session_created'] = time();
} elseif (time() - $_SESSION['session_created'] > 1800) {
    // Regenerar cada 30 minutos
    session_regenerate_id(true);
    $_SESSION['session_created'] = time();
}

// ═══════════════════════════════════════════════════════════════════
// PREVENIR VOLVER ATRÁS DESPUÉS DE LOGOUT
// ═══════════════════════════════════════════════════════════════════

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// ═══════════════════════════════════════════════════════════════════
// VARIABLES JAVASCRIPT PARA SESSION MANAGER
// ═══════════════════════════════════════════════════════════════════

// Estas variables estarán disponibles en JavaScript
$SESSION_TIMEOUT = SESSION_TIMEOUT;
$SESSION_POPUP_TIMEOUT = SESSION_POPUP_TIMEOUT;

?>
<script>
// Configuración de timeout para JavaScript
const SESSION_TIMEOUT = <?php echo SESSION_TIMEOUT; ?>;
const SESSION_POPUP_TIMEOUT = <?php echo SESSION_POPUP_TIMEOUT; ?>;

// Prevenir volver atrás después de logout
window.history.pushState(null, '', window.location.href);
window.onpopstate = function() {
    window.history.pushState(null, '', window.location.href);
};
</script>