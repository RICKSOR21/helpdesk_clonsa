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

$user_id = $_SESSION['user_id'];
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 3;

// Meses en español
$meses_es = [
    'Jan' => 'Ene', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Abr',
    'May' => 'May', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Ago',
    'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Dic'
];

// Obtener comunicados activos con estado de lectura
$sql = "SELECT
    c.id,
    c.titulo,
    c.contenido,
    c.tipo,
    c.icono,
    c.color,
    c.created_at,
    CASE WHEN nl.id IS NOT NULL THEN 1 ELSE 0 END as leido
FROM comunicados c
LEFT JOIN notificaciones_leidas nl ON nl.tipo = 'comunicado' AND nl.referencia_id = c.id AND nl.usuario_id = :user_id
WHERE c.activo = 1
AND (c.fecha_expiracion IS NULL OR c.fecha_expiracion > NOW())
ORDER BY c.created_at DESC
LIMIT :limit";

$stmt = $db->prepare($sql);
$stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$comunicados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Formatear datos
$comunicados_formatted = [];
foreach ($comunicados as $com) {
    $fecha = new DateTime($com['created_at']);
    $fecha_formato = $fecha->format('d M, Y');

    // Reemplazar mes en inglés por español
    foreach ($meses_es as $en => $es) {
        $fecha_formato = str_replace($en, $es, $fecha_formato);
    }

    // Tiempo relativo
    $ahora = new DateTime();
    $diff = $ahora->diff($fecha);

    if ($diff->d == 0) {
        $tiempo_relativo = 'Hoy';
    } elseif ($diff->d == 1) {
        $tiempo_relativo = 'Ayer';
    } elseif ($diff->d < 7) {
        $tiempo_relativo = 'Hace ' . $diff->d . ' días';
    } else {
        $tiempo_relativo = $fecha_formato;
    }

    $comunicados_formatted[] = [
        'id' => $com['id'],
        'titulo' => $com['titulo'],
        'contenido' => substr($com['contenido'], 0, 100) . '...',
        'tipo' => $com['tipo'],
        'icono' => $com['icono'],
        'color' => $com['color'],
        'fecha' => $fecha_formato,
        'tiempo_relativo' => $tiempo_relativo,
        'leido' => (bool)$com['leido']
    ];
}

// Contar total de comunicados NO leídos (para el badge)
$sql_count = "SELECT COUNT(*) as total
              FROM comunicados c
              LEFT JOIN notificaciones_leidas nl ON nl.tipo = 'comunicado' AND nl.referencia_id = c.id AND nl.usuario_id = :user_id
              WHERE c.activo = 1
              AND (c.fecha_expiracion IS NULL OR c.fecha_expiracion > NOW())
              AND nl.id IS NULL";
$stmt_count = $db->prepare($sql_count);
$stmt_count->execute([':user_id' => $user_id]);
$total_no_leidos = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];

ob_end_clean();

echo json_encode([
    'comunicados' => $comunicados_formatted,
    'total' => $total_no_leidos
], JSON_UNESCAPED_UNICODE);
