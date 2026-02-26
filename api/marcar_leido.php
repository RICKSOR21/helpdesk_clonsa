<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/session.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

require_once '../config/config.php';
$db = getDBConnection();

$user_id = $_SESSION['user_id'];
$tipo = $_POST['tipo'] ?? $_GET['tipo'] ?? null;
$referencia_id = $_POST['referencia_id'] ?? $_GET['referencia_id'] ?? null;

if (!$tipo || !$referencia_id) {
    echo json_encode(['success' => false, 'error' => 'Parámetros requeridos']);
    exit;
}

// Validar tipo
if (!in_array($tipo, ['ticket', 'ticket_asignado', 'ticket_aprobado', 'ticket_rechazado', 'comunicado'])) {
    echo json_encode(['success' => false, 'error' => 'Tipo inválido']);
    exit;
}

try {
    // Insertar o ignorar si ya existe
    $sql = "INSERT IGNORE INTO notificaciones_leidas (usuario_id, tipo, referencia_id) VALUES (:user_id, :tipo, :ref_id)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':tipo' => $tipo,
        ':ref_id' => $referencia_id
    ]);

    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
