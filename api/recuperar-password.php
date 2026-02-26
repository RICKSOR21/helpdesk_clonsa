<?php
// api/recuperar-password.php
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
    
    // Buscar usuario por email
    $query = "SELECT id, username, nombre_completo, email FROM usuarios WHERE email = :email AND activo = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Por seguridad, no revelamos si el email existe o no
        $response['success'] = true;
        $response['message'] = 'Si el correo existe en nuestro sistema, recibirás instrucciones para restablecer tu contraseña.';
        echo json_encode($response);
        exit;
    }
    
    // Generar token de recuperación
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Guardar token en la base de datos
    $query = "UPDATE usuarios SET 
              reset_token = :token, 
              reset_token_expires = :expires 
              WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':expires', $expires);
    $stmt->bindParam(':user_id', $user['id']);
    $stmt->execute();
    
    // Construir URL de recuperación
    $reset_url = APP_URL . "/reset-password.php?token=$token";
    
    // Preparar correo
    $to = $user['email'];
    $subject = 'Recuperación de Contraseña - Helpdesk Clonsa';
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 12px 30px; background: #4B49AC; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 30px; color: #6c757d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🔑 Recuperación de Contraseña</h1>
                <p>Helpdesk Clonsa Ingeniería</p>
            </div>
            <div class='content'>
                <p>Hola <strong>{$user['nombre_completo']}</strong>,</p>
                <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta.</p>
                <p>Haz clic en el siguiente botón para crear una nueva contraseña:</p>
                <p style='text-align: center;'>
                    <a href='$reset_url' class='button'>Restablecer Contraseña</a>
                </p>
                <p>O copia y pega este enlace en tu navegador:</p>
                <p style='background: #fff; padding: 10px; border-left: 3px solid #4B49AC; word-break: break-all;'>
                    $reset_url
                </p>
                <p><strong>⏰ Este enlace expirará en 1 hora.</strong></p>
                <p style='margin-top: 30px; color: #dc3545;'>
                    <strong>⚠️ Si no solicitaste este cambio, ignora este correo.</strong> Tu contraseña permanecerá sin cambios.
                </p>
            </div>
            <div class='footer'>
                <p>© 2026 Clonsa Ingeniería. Todos los derechos reservados.</p>
                <p>Este es un correo automático, por favor no respondas.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Headers del correo
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: Helpdesk Clonsa <noreply@clonsa.pe>\r\n";
    $headers .= "Reply-To: soporte@clonsa.pe\r\n";
    
    // Enviar correo
    if (mail($to, $subject, $message, $headers)) {
        $response['success'] = true;
        $response['message'] = 'Se han enviado las instrucciones a tu correo electrónico.';
        
        // Log de recuperación
        $log_file = __DIR__ . '/../logs/password_recovery.log';
        $log_dir = dirname($log_file);
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Recovery email sent to: $email (User: {$user['username']})\n", FILE_APPEND);
    } else {
        // Si falla el envío de correo, igual respondemos exitosamente por seguridad
        $response['success'] = true;
        $response['message'] = 'Si el correo existe en nuestro sistema, recibirás instrucciones para restablecer tu contraseña.';
        
        // Log del error
        $log_file = __DIR__ . '/../logs/password_recovery_errors.log';
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed to send email to: $email\n", FILE_APPEND);
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Error del servidor. Por favor intenta más tarde.';
    error_log("Recovery password error: " . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error inesperado. Por favor intenta más tarde.';
    error_log("Recovery password error: " . $e->getMessage());
}

echo json_encode($response);
?>