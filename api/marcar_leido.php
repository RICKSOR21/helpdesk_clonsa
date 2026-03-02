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

$user_id = (int)$_SESSION['user_id'];
$tipo = $_POST['tipo'] ?? $_GET['tipo'] ?? null;
$referencia_id = $_POST['referencia_id'] ?? $_GET['referencia_id'] ?? null;

if (!$tipo || !$referencia_id) {
    echo json_encode(['success' => false, 'error' => 'Parametros requeridos']);
    exit;
}

$tiposPermitidos = ['ticket', 'ticket_asignado', 'ticket_aprobado', 'ticket_rechazado', 'comunicado'];
if (!in_array($tipo, $tiposPermitidos, true)) {
    echo json_encode(['success' => false, 'error' => 'Tipo invalido']);
    exit;
}

try {
    $tipo_guardar = $tipo;

    // Backward compatibility: old schema only allows enum('ticket','comunicado')
    $colStmt = $db->query("SHOW COLUMNS FROM notificaciones_leidas LIKE 'tipo'");
    $colInfo = $colStmt ? $colStmt->fetch(PDO::FETCH_ASSOC) : null;
    if ($colInfo && !empty($colInfo['Type']) && preg_match("/^enum\\((.*)\\)$/i", (string)$colInfo['Type'], $m)) {
        preg_match_all("/'([^']+)'/", (string)$m[1], $vals);
        $enumValues = $vals[1] ?? [];
        if (!in_array($tipo_guardar, $enumValues, true) && str_starts_with((string)$tipo_guardar, 'ticket') && in_array('ticket', $enumValues, true)) {
            $tipo_guardar = 'ticket';
        }
    }

    $sql = "INSERT IGNORE INTO notificaciones_leidas (usuario_id, tipo, referencia_id) VALUES (:user_id, :tipo, :ref_id)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':user_id' => $user_id,
        ':tipo' => $tipo_guardar,
        ':ref_id' => (int)$referencia_id
    ]);

    echo json_encode(['success' => true, 'tipo_guardado' => $tipo_guardar]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
