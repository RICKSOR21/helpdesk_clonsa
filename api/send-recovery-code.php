<?php
/**
 * API: envio de codigo de recuperacion
 * api/send-recovery-code.php
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'message' => ''];

function enviarCodigoRecuperacionEmail($toEmail, $nombreCompleto, $code)
{
    if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) {
        // En local permitimos flujo sin SMTP real.
        return true;
    }

    $subject = 'Codigo de recuperacion - ' . (defined('APP_NAME') ? APP_NAME : 'Helpdesk');
    $appName = defined('APP_NAME') ? APP_NAME : 'Helpdesk';
    $minutes = defined('TOKEN_EXPIRATION_MINUTES') ? (int) TOKEN_EXPIRATION_MINUTES : 15;

    $html = '
    <div style="font-family:Arial,sans-serif;max-width:620px;margin:0 auto;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
      <div style="background:#1f3bb3;color:#fff;padding:14px 18px;">
        <h2 style="margin:0;font-size:18px;">Recuperacion de clave</h2>
      </div>
      <div style="padding:18px;color:#111827;font-size:14px;line-height:1.5;">
        <p>Hola <strong>' . htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
        <p>Usa este codigo para recuperar tu clave en <strong>' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . '</strong>:</p>
        <div style="font-size:32px;letter-spacing:8px;font-weight:700;color:#1f3bb3;background:#f3f4f6;padding:12px 16px;border-radius:8px;display:inline-block;">
          ' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '
        </div>
        <p style="margin-top:14px;">Este codigo vence en ' . $minutes . ' minutos.</p>
        <p>Si no solicitaste este cambio, ignora este correo.</p>
      </div>
    </div>';

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $fromEmail = defined('SMTP_FROM') ? SMTP_FROM : 'no-reply@localhost';
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Helpdesk';
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";

    return @mail($toEmail, $subject, $html, $headers);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Metodo no permitido';
    echo json_encode($response);
    exit;
}

try {
    $db = getDBConnection();

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $response['message'] = 'El correo electronico es requerido';
        echo json_encode($response);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Correo electronico no valido';
        echo json_encode($response);
        exit;
    }

    $query = "SELECT id, username, nombre_completo, email FROM usuarios WHERE email = :email AND activo = 1";
    $stmt = $db->prepare($query);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $response['success'] = false;
        $response['message'] = 'No existe ninguna cuenta registrada con este correo electronico.';
        writeLog('recovery_attempts.log', "Recovery attempt for non-existent email: {$email}");
        echo json_encode($response);
        exit;
    }

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires = date('Y-m-d H:i:s', strtotime('+' . TOKEN_EXPIRATION_MINUTES . ' minutes'));

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

    $emailSent = enviarCodigoRecuperacionEmail($email, $user['nombre_completo'], $code);

    if (!$emailSent) {
        $response['success'] = false;
        $response['message'] = 'No se pudo enviar el codigo al correo. Verifica la configuracion de correo del servidor.';
        writeLog('recovery_errors.log', "Email send failed for: {$email} (User: {$user['username']})");
        echo json_encode($response);
        exit;
    }

    $response['success'] = true;
    $response['message'] = 'Codigo enviado exitosamente. Revisa tu correo electronico.';

    if (defined('IS_DEVELOPMENT') && IS_DEVELOPMENT) {
        writeLog('recovery_codes.log', "Code sent to: {$email} (User: {$user['username']}) - Code: {$code}");
    } else {
        writeLog('recovery_codes.log', "Code sent to: {$email} (User: {$user['username']})");
    }
} catch (PDOException $e) {
    $response['message'] = 'Error del servidor. Intenta mas tarde.';
    error_log('Recovery code error (PDO): ' . $e->getMessage());
} catch (Exception $e) {
    $response['message'] = 'Error al procesar la solicitud.';
    error_log('Recovery code error: ' . $e->getMessage());
}

echo json_encode($response);
?>
