<?php
/**
 * API: Envío de código de recuperación - VERSIÓN CLARA
 * api/send-recovery-code.php
 * 
 * Muestra mensajes claros si el email no existe (más amigable para usuarios)
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

try {
    $db = getDBConnection();
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $response['message'] = 'El correo electrónico es requerido';
        echo json_encode($response);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Correo electrónico no válido';
        echo json_encode($response);
        exit;
    }
    
    // Buscar usuario
    $query = "SELECT id, username, nombre_completo, email FROM usuarios WHERE email = :email AND activo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar si el usuario existe
    if (!$user) {
        $response['success'] = false;
        $response['message'] = 'No existe ninguna cuenta registrada con este correo electrónico.';
        
        // Log del intento
        writeLog('recovery_attempts.log', "Recovery attempt for non-existent email: {$email}");
        
        echo json_encode($response);
        exit;
    }
    
    // Usuario existe - generar código
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+' . TOKEN_EXPIRATION_MINUTES . ' minutes'));
    
    // Guardar código en la base de datos
    $query = "UPDATE usuarios SET reset_token = :code, reset_token_expires = :expires WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $updated = $stmt->execute([
        ':code' => $code,
        ':expires' => $expires,
        ':user_id' => $user['id']
    ]);
    
    if (!$updated) {
        $response['message'] = 'Error al procesar la solicitud. Intenta nuevamente.';
        echo json_encode($response);
        exit;
    }
    
    // Enviar email (aquí iría el código de envío real)
    // $emailSent = sendEmailWithCode($email, $user['nombre_completo'], $code);
    
    $response['success'] = true;
    $response['message'] = 'Código enviado exitosamente. Revisa tu correo electrónico.';
    
    // Log de éxito
    writeLog('recovery_codes.log', "Code sent to: {$email} (User: {$user['username']}) - Code: {$code}");
    
} catch (PDOException $e) {
    $response['message'] = 'Error del servidor. Intenta más tarde.';
    error_log("Recovery code error: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error al procesar la solicitud.';
    error_log("Recovery code error: " . $e->getMessage());
}

echo json_encode($response);
?>