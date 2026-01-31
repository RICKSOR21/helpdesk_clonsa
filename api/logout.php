<?php
/**
 * Logout API
 * Cierra la sesión del usuario de forma segura
 * Compatible con Auth.php existente
 * Archivo: api/logout.php
 */

session_start();

// Verificar si es llamada AJAX (desde session-manager.js)
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// También verificar si espera JSON
$expects_json = (isset($_SERVER['CONTENT_TYPE']) && 
                 strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                (isset($_SERVER['HTTP_ACCEPT']) && 
                 strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

// Guardar información antes de destruir
$user_id = $_SESSION['user_id'] ?? null;

// Si tienes Auth.php, usarlo
if (file_exists('../config/database.php') && file_exists('../includes/Auth.php')) {
    require_once '../config/database.php';
    require_once '../includes/Auth.php';
    
    try {
        $database = new Database();
        $db = $database->getConnection();
        $auth = new Auth($db);
        $auth->logout();
    } catch (Exception $e) {
        // Si falla, hacer logout manual
        $_SESSION = array();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
    }
} else {
    // Logout manual si no existe Auth.php
    $_SESSION = array();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    session_destroy();
}

// Prevenir caché
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Log del evento
if ($user_id) {
    error_log("User $user_id logged out at " . date('Y-m-d H:i:s'));
}

// Responder según el tipo de petición
if ($is_ajax || $expects_json) {
    // Si es AJAX (desde session-manager.js), responder JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Sesión cerrada correctamente'
    ]);
} else {
    // Si es petición normal, redirigir a login
    header('Location: ../login.php');
}

exit;
?>