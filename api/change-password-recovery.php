<?php
// api/change-password-recovery.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
    
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($new_password)) {
        $response['message'] = 'Datos incompletos';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($new_password) < 8) {
        $response['message'] = 'La contraseña debe tener al menos 8 caracteres';
        echo json_encode($response);
        exit;
    }
    
    // Verificar que el usuario existe y tiene un código verificado recientemente
    // (el código debe haberse verificado en los últimos 15 minutos)
    $query = "SELECT id, username FROM usuarios 
              WHERE email = :email 
              AND reset_token_expires > NOW() 
              AND activo = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $response['message'] = 'Sesión expirada. Por favor solicita un nuevo código.';
        echo json_encode($response);
        exit;
    }
    
    // Actualizar contraseña
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $query = "UPDATE usuarios SET 
              password = :password,
              reset_token = NULL,
              reset_token_expires = NULL
              WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':user_id', $user['id']);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = '¡Contraseña actualizada exitosamente!';
        
        // Log de cambio
        $log_file = __DIR__ . '/../logs/password_changes.log';
        $log_dir = dirname($log_file);
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Password changed for user: {$user['username']} (Email: $email)\n", FILE_APPEND);
    } else {
        $response['message'] = 'Error al actualizar la contraseña';
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error del servidor. Por favor intenta más tarde.';
    error_log("Change password recovery error: " . $e->getMessage());
}

echo json_encode($response);
?>