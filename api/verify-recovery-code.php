<?php
// api/verify-recovery-code.php
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido';
    echo json_encode($response);
    exit;
}

try {
    $host = 'localhost';
    $dbname = 'helpdesk_clonsa';
    $username = 'root';
    $password = '';
    
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    
    if (empty($email) || empty($code)) {
        $response['message'] = 'Datos incompletos';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($code) !== 6 || !ctype_digit($code)) {
        $response['message'] = 'Código inválido';
        echo json_encode($response);
        exit;
    }
    
    // Verificar código
    $query = "SELECT id, username, nombre_completo FROM usuarios 
              WHERE email = :email 
              AND reset_token = :code 
              AND reset_token_expires > NOW() 
              AND activo = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':code', $code);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $response['message'] = 'Código inválido o expirado';
        
        // Log intento fallido
        $log_file = __DIR__ . '/../logs/recovery_attempts.log';
        $log_dir = dirname($log_file);
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        file_put_contents($log_file, date('Y-m-d H:i:s') . " - Failed verification for: $email (Code: $code)\n", FILE_APPEND);
        
        echo json_encode($response);
        exit;
    }
    
    $response['success'] = true;
    $response['message'] = 'Código verificado correctamente';
    $response['user'] = [
        'id' => $user['id'],
        'nombre' => $user['nombre_completo']
    ];
    
    // Log exitoso
    $log_file = __DIR__ . '/../logs/recovery_codes.log';
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Code verified for: $email (User: {$user['username']})\n", FILE_APPEND);
    
} catch (Exception $e) {
    $response['message'] = 'Error del servidor. Por favor intenta más tarde.';
    error_log("Verify recovery code error: " . $e->getMessage());
}

echo json_encode($response);
?>