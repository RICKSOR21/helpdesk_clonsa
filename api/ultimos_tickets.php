<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once '../config/session.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

require_once '../config/config.php';
$db = getDBConnection();

$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
$user_departamento = $_SESSION['departamento_id'] ?? null;
$user_id = $_SESSION['user_id'];

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
$departamento_filtro = $_GET['departamento'] ?? 'all';

$puede_ver_todos = ($user_rol === 'Administrador' || $user_rol === 'Admin');

// Query para obtener los últimos tickets creados
$sql = "SELECT
    t.id,
    t.codigo,
    t.titulo,
    t.created_at,
    u.nombre_completo as usuario,
    a.nombre as actividad,
    d.nombre as departamento
FROM tickets t
JOIN usuarios u ON u.id = t.usuario_id
LEFT JOIN actividades a ON a.id = t.actividad_id
LEFT JOIN departamentos d ON d.id = t.departamento_id
WHERE 1=1";

$params = [];

// Filtro por departamento seleccionado (solo para admin)
if ($puede_ver_todos && $departamento_filtro !== 'all') {
    $sql .= " AND t.departamento_id = :departamento_filtro";
    $params[':departamento_filtro'] = $departamento_filtro;
} elseif ($user_rol === 'Jefe' && $user_departamento) {
    // Jefe solo ve su departamento
    $sql .= " AND t.departamento_id = :departamento_id";
    $params[':departamento_id'] = $user_departamento;
} elseif ($user_rol === 'Usuario') {
    // Usuario normal: solo tickets donde es responsable actual
    $sql .= " AND (t.asignado_a = :user_asignado OR (t.usuario_id = :user_creador AND (t.asignado_a IS NULL OR t.asignado_a = 0 OR t.asignado_a = :user_mismo)))";
    $params[':user_asignado'] = $user_id;
    $params[':user_creador'] = $user_id;
    $params[':user_mismo'] = $user_id;
}
// Si es admin y departamento = 'all', no agrega filtro (ve todo)

$sql .= " ORDER BY t.created_at DESC LIMIT :limit";

$stmt = $db->prepare($sql);

// Bind de parámetros
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Meses en español
$meses_es = [
    'Jan' => 'Ene', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Abr',
    'May' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago',
    'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Dic'
];

// Formatear los datos
$tickets_formatted = [];
foreach ($tickets as $ticket) {
    $fecha = new DateTime($ticket['created_at']);
    $fecha_formato = $fecha->format('d M, Y');

    // Reemplazar mes en inglés por español
    foreach ($meses_es as $en => $es) {
        $fecha_formato = str_replace($en, $es, $fecha_formato);
    }

    $tickets_formatted[] = [
        'id' => $ticket['id'],
        'codigo' => $ticket['codigo'],
        'titulo' => $ticket['titulo'],
        'usuario' => $ticket['usuario'],
        'actividad' => $ticket['actividad'] ?? 'Sin actividad',
        'departamento' => $ticket['departamento'] ?? 'Sin departamento',
        'fecha' => $fecha_formato,
        'hora' => $fecha->format('H:i') . ' hrs'
    ];
}

ob_end_clean();

echo json_encode([
    'tickets' => $tickets_formatted,
    'total' => count($tickets_formatted),
    'departamento_filtro' => $departamento_filtro
], JSON_UNESCAPED_UNICODE);
