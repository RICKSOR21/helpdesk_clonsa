<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/session.php';
session_start();

require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'cambiar_clave':
            $claveActual = (string)($_POST['clave_actual'] ?? '');
            $claveNueva = (string)($_POST['clave_nueva'] ?? '');
            $claveConfirmar = (string)($_POST['clave_confirmar'] ?? '');

            if ($claveActual === '' || $claveNueva === '' || $claveConfirmar === '') {
                $response['message'] = 'Complete todos los campos obligatorios';
                break;
            }

            if (strlen($claveNueva) < 6) {
                $response['message'] = 'La nueva clave debe tener al menos 6 caracteres';
                break;
            }

            if ($claveNueva !== $claveConfirmar) {
                $response['message'] = 'La confirmacion de clave no coincide';
                break;
            }

            $stmtUser = $db->prepare('SELECT password FROM usuarios WHERE id = :id LIMIT 1');
            $stmtUser->execute([':id' => $currentUserId]);
            $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $response['message'] = 'Usuario no encontrado';
                break;
            }

            $hashActual = (string)($user['password'] ?? '');
            if (!password_verify($claveActual, $hashActual)) {
                $response['message'] = 'La clave actual es incorrecta';
                break;
            }

            if (password_verify($claveNueva, $hashActual)) {
                $response['message'] = 'La nueva clave debe ser diferente a la actual';
                break;
            }

            $stmtUpdate = $db->prepare('UPDATE usuarios SET password = :password WHERE id = :id');
            $stmtUpdate->execute([
                ':password' => password_hash($claveNueva, PASSWORD_DEFAULT),
                ':id' => $currentUserId
            ]);

            $response['success'] = true;
            $response['message'] = 'Clave actualizada correctamente';
            break;

        default:
            $response['message'] = 'Accion no valida';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error al procesar la solicitud';
}

echo json_encode($response);
?>
