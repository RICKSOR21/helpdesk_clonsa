<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../config/session.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

require_once '../config/config.php';
$db = getDBConnection();

$periodo = $_GET['periodo'] ?? 'week';
$user_id = $_SESSION['user_id'];
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
$user_departamento = $_SESSION['departamento_id'] ?? null;

// Calcular fechas según período
$hoy = new DateTime();

switch ($periodo) {
    case 'month':
        $fecha_inicio = $hoy->format('Y-m-01'); // Primer día del mes actual
        $fecha_fin = $hoy->format('Y-m-t');      // Último día del mes actual
        $periodo_texto = 'Mes actual (' . $hoy->format('F Y') . ')';
        break;
    case 'year':
        $fecha_inicio = $hoy->format('Y-01-01'); // 1 de enero
        $fecha_fin = $hoy->format('Y-12-31');    // 31 de diciembre
        $periodo_texto = 'Año ' . $hoy->format('Y');
        break;
    case 'custom':
        $fecha_inicio = $_GET['fecha_inicio'] ?? $hoy->modify('-7 days')->format('Y-m-d');
        $fecha_fin = $_GET['fecha_fin'] ?? (new DateTime())->format('Y-m-d');
        $periodo_texto = "Personalizado ($fecha_inicio a $fecha_fin)";
        break;
    default: // week
        $fecha_inicio = (clone $hoy)->modify('-7 days')->format('Y-m-d');
        $fecha_fin = $hoy->format('Y-m-d');
        $periodo_texto = 'Última semana';
}

// Construir condición de permisos
$permisos_sql = "";
$params = [':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin];

if ($user_rol === 'Jefe' && $user_departamento) {
    $permisos_sql = " AND t.departamento_id = :dept_id";
    $params[':dept_id'] = $user_departamento;
} elseif ($user_rol === 'Usuario') {
    $permisos_sql = " AND (t.usuario_id = :user_id OR t.asignado_a = :user_id2)";
    $params[':user_id'] = $user_id;
    $params[':user_id2'] = $user_id;
}

// =====================
// MÉTRICAS PRINCIPALES
// =====================
$metricas = [];

// Tickets abiertos (todo lo NO resuelto: Abierto + En Atención + Rechazado)
$sql = "SELECT COUNT(*) as total FROM tickets t WHERE t.estado_id IN (1,2,3,5) AND DATE(t.created_at) BETWEEN :fecha_inicio AND :fecha_fin $permisos_sql";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$metricas['abiertos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tickets en proceso (En Atención + Rechazado con avance pendiente)
$sql = "SELECT COUNT(*) as total FROM tickets t WHERE t.estado_id IN (2,5) AND DATE(t.created_at) BETWEEN :fecha_inicio AND :fecha_fin $permisos_sql";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$metricas['proceso'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tickets resueltos (solo estado_id=4, NO incluir Rechazado=5)
$sql = "SELECT COUNT(*) as total FROM tickets t WHERE t.estado_id = 4 AND DATE(t.created_at) BETWEEN :fecha_inicio AND :fecha_fin $permisos_sql";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$metricas['resueltos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Tiempo promedio de resolución
$sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.updated_at)) as promedio
        FROM tickets t
        WHERE t.estado_id = 4
        AND DATE(t.created_at) BETWEEN :fecha_inicio AND :fecha_fin $permisos_sql";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$promedio_horas = $stmt->fetch(PDO::FETCH_ASSOC)['promedio'];

if ($promedio_horas) {
    $dias = floor($promedio_horas / 24);
    $horas = floor($promedio_horas % 24);
    $metricas['tiempo_promedio'] = ($dias > 0 ? $dias . 'd ' : '') . $horas . 'h';
} else {
    $metricas['tiempo_promedio'] = 'N/A';
}

// Calcular comparaciones (período anterior)
$diff = (new DateTime($fecha_fin))->diff(new DateTime($fecha_inicio));
$dias_periodo = $diff->days + 1;

$fecha_inicio_ant = (new DateTime($fecha_inicio))->modify("-$dias_periodo days")->format('Y-m-d');
$fecha_fin_ant = (new DateTime($fecha_inicio))->modify('-1 day')->format('Y-m-d');

$params_ant = [':fecha_inicio' => $fecha_inicio_ant, ':fecha_fin' => $fecha_fin_ant];
if (isset($params[':dept_id'])) $params_ant[':dept_id'] = $params[':dept_id'];
if (isset($params[':user_id'])) {
    $params_ant[':user_id'] = $params[':user_id'];
    $params_ant[':user_id2'] = $params[':user_id'];
}

// Abiertos anterior
$sql = "SELECT COUNT(*) as total FROM tickets t WHERE t.estado_id IN (1,2,3,5) AND DATE(t.created_at) BETWEEN :fecha_inicio AND :fecha_fin $permisos_sql";
$stmt = $db->prepare($sql);
$stmt->execute($params_ant);
$abiertos_ant = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$metricas['abiertos_comp'] = calcularComparacion($metricas['abiertos'], $abiertos_ant);

// Resueltos anterior (solo estado_id=4)
$sql = "SELECT COUNT(*) as total FROM tickets t WHERE t.estado_id = 4 AND DATE(t.created_at) BETWEEN :fecha_inicio AND :fecha_fin $permisos_sql";
$stmt = $db->prepare($sql);
$stmt->execute($params_ant);
$resueltos_ant = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$metricas['resueltos_comp'] = calcularComparacion($metricas['resueltos'], $resueltos_ant);

function calcularComparacion($actual, $anterior) {
    if ($anterior == 0) {
        return $actual > 0 ? '+100%' : '0%';
    }
    $porcentaje = (($actual - $anterior) / $anterior) * 100;
    $signo = $porcentaje >= 0 ? '+' : '';
    return $signo . round($porcentaje, 1) . '%';
}

// =====================
// LISTA DE TICKETS
// =====================
$sql = "SELECT
    t.codigo,
    t.titulo,
    e.nombre as estado,
    p.nombre as prioridad,
    d.nombre as departamento,
    u.nombre_completo as creador,
    COALESCE(ua.nombre_completo, 'Sin asignar') as asignado,
    DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i') as fecha_creacion,
    DATE_FORMAT(t.updated_at, '%Y-%m-%d %H:%i') as fecha_actualizacion
FROM tickets t
LEFT JOIN estados e ON e.id = t.estado_id
LEFT JOIN prioridades p ON p.id = t.prioridad_id
LEFT JOIN departamentos d ON d.id = t.departamento_id
LEFT JOIN usuarios u ON u.id = t.usuario_id
LEFT JOIN usuarios ua ON ua.id = t.asignado_a
WHERE DATE(t.created_at) BETWEEN :fecha_inicio AND :fecha_fin $permisos_sql
ORDER BY t.created_at DESC
LIMIT 100";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================
// TOP EMPLEADOS
// =====================
$sql_top = "SELECT
    u.nombre_completo as nombre,
    COALESCE(d.nombre, 'Sin departamento') as departamento,
    COUNT(*) as resueltos
FROM tickets t
JOIN usuarios u ON u.id = t.asignado_a
LEFT JOIN departamentos d ON d.id = u.departamento_id
WHERE t.estado_id = 4
AND DATE(t.created_at) BETWEEN :fecha_inicio AND :fecha_fin";

if ($user_rol === 'Jefe' && $user_departamento) {
    $sql_top .= " AND t.departamento_id = :dept_id";
}

$sql_top .= " GROUP BY u.id ORDER BY resueltos DESC LIMIT 10";

$stmt = $db->prepare($sql_top);
$params_top = [':fecha_inicio' => $fecha_inicio, ':fecha_fin' => $fecha_fin];
if ($user_rol === 'Jefe' && $user_departamento) {
    $params_top[':dept_id'] = $user_departamento;
}
$stmt->execute($params_top);
$top_empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================
// ACTIVIDADES/STATUS
// =====================
$sql_act = "SELECT
    COALESCE(a.nombre, 'Sin actividad') as nombre,
    COUNT(*) as total
FROM tickets t
LEFT JOIN actividades a ON a.id = t.actividad_id
WHERE DATE(t.created_at) BETWEEN :fecha_inicio AND :fecha_fin $permisos_sql
GROUP BY t.actividad_id
ORDER BY total DESC";

$stmt = $db->prepare($sql_act);
$stmt->execute($params);
$actividades_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_tickets = array_sum(array_column($actividades_raw, 'total'));
$actividades = [];
foreach ($actividades_raw as $act) {
    $porcentaje = $total_tickets > 0 ? round(($act['total'] / $total_tickets) * 100, 1) : 0;
    $actividades[] = [
        'nombre' => $act['nombre'],
        'total' => $act['total'],
        'porcentaje' => $porcentaje
    ];
}

// =====================
// ESTADÍSTICAS DIARIAS
// =====================
$sql_daily = "SELECT
    DATE(t.created_at) as fecha,
    COUNT(*) as creados,
    SUM(CASE WHEN t.estado_id = 4 THEN 1 ELSE 0 END) as resueltos
FROM tickets t
WHERE DATE(t.created_at) BETWEEN :fecha_inicio AND :fecha_fin $permisos_sql
GROUP BY DATE(t.created_at)
ORDER BY fecha ASC";

$stmt = $db->prepare($sql_daily);
$stmt->execute($params);
$estadisticas_diarias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================
// RESPUESTA
// =====================
echo json_encode([
    'periodo' => $periodo,
    'periodo_texto' => $periodo_texto,
    'fecha_inicio' => $fecha_inicio,
    'fecha_fin' => $fecha_fin,
    'metricas' => $metricas,
    'tickets' => $tickets,
    'top_empleados' => $top_empleados,
    'actividades' => $actividades,
    'estadisticas_diarias' => $estadisticas_diarias
], JSON_UNESCAPED_UNICODE);
