<?php
/**
 * Keep Session Alive
 * Actualiza el timestamp de última actividad
 * Archivo: api/keep-session.php
 */

session_start();
header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No hay sesión activa'
    ]);
    exit;
}

try {
    // Actualizar el timestamp de última actividad
    $_SESSION['last_activity'] = time();
    
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sesión mantenida activa',
        'last_activity' => $_SESSION['last_activity'],
        'user_id' => $_SESSION['user_id']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al mantener sesión: ' . $e->getMessage()
    ]);
}
?>