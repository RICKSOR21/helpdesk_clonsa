<?php
// api/login.php - Versión corregida con departamento_id
require_once '../config/session.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en producción

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

require_once '../config/config.php';

try {
    $db = getDBConnection();
    
    // Obtener datos del formulario
    $user_input = trim($_POST['username'] ?? '');
    $pass_input = $_POST['password'] ?? '';
    
    if (empty($user_input) || empty($pass_input)) {
        $response['message'] = 'Usuario y contraseña son requeridos';
        echo json_encode($response);
        exit;
    }
    
    // Buscar usuario - INCLUIR departamento_id
    $query = "SELECT u.*, r.nombre as rol_nombre 
              FROM usuarios u 
              INNER JOIN roles r ON u.rol_id = r.id
              WHERE (u.username = :user_input OR u.email = :email_input) 
              AND u.activo = 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_input', $user_input, PDO::PARAM_STR);
    $stmt->bindParam(':email_input', $user_input, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if (!$user) {
        $response['message'] = 'Usuario no encontrado o inactivo';
        echo json_encode($response);
        exit;
    }
    
    // Verificar contraseña
    if (!password_verify($pass_input, $user['password'])) {
        $response['message'] = 'Contraseña incorrecta';
        echo json_encode($response);
        exit;
    }
    
    // ✅ LOGIN EXITOSO - Crear sesión
    
    // Regenerar ID de sesión por seguridad
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['nombre_completo'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_rol'] = $user['rol_nombre']; // ✅ IMPORTANTE: Guardar como "Administrador", "Jefe", "Usuario"
    $_SESSION['rol_id'] = $user['rol_id'];
    $_SESSION['departamento_id'] = $user['departamento_id']; // ✅ CRÍTICO: Agregar departamento_id
    
    // ⭐ CRÍTICO: Resetear timestamps al iniciar sesión
    $_SESSION['last_activity'] = time();
    $_SESSION['session_created'] = time();
    
    // Log para debug
    error_log("Nueva sesión iniciada - User: {$user['username']} - ID: {$user['id']} - Depto: {$user['departamento_id']} - Rol: {$user['rol_nombre']} - Timestamp: " . time());
    
    // Actualizar último acceso
    $query = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user['id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $response['success'] = true;
    $response['message'] = 'Inicio de sesión exitoso';
    $response['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'nombre' => $user['nombre_completo'],
        'rol' => $user['rol_nombre'],
        'departamento_id' => $user['departamento_id'] // ✅ Incluir en respuesta
    ];
    
} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log("Login error (PDO): " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
    error_log("Login error: " . $e->getMessage());
}

echo json_encode($response);
?>