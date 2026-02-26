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
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
$user_departamento = $_SESSION['departamento_id'] ?? null;

$periodo = $_GET['periodo'] ?? 'semana';
$departamento = $_GET['departamento'] ?? 'all';
$actividad = $_GET['actividad'] ?? 'all';
$fecha_desde = $_GET['fecha_desde'] ?? null;
$fecha_hasta = $_GET['fecha_hasta'] ?? null;

// ============================================
// CALCULAR FECHAS (DINÁMICO)
// ============================================
$hoy = new DateTime();

if ($periodo === 'personalizado' && $fecha_desde && $fecha_hasta) {
    $desde_parts = explode('/', $fecha_desde);
    $hasta_parts = explode('/', $fecha_hasta);
    $fecha_inicio = $desde_parts[2] . '-' . $desde_parts[1] . '-' . $desde_parts[0];
    $fecha_fin = $hasta_parts[2] . '-' . $hasta_parts[1] . '-' . $hasta_parts[0];
} else {
    switch ($periodo) {
        case 'mes':
            // Mes actual: desde el 1 hasta el último día del mes
            $fecha_inicio = $hoy->format('Y-m-01');
            $fecha_fin = $hoy->format('Y-m-t');
            break;
        case 'año':
            // Año actual completo
            $fecha_inicio = $hoy->format('Y-01-01');
            $fecha_fin = $hoy->format('Y-12-31');
            break;
        case 'semana':
        default:
            // Últimos 7 días
            $fecha_inicio = (new DateTime())->modify('-7 days')->format('Y-m-d');
            $fecha_fin = (new DateTime())->format('Y-m-d');
            break;
    }
}

// ============================================
// QUERY: USUARIOS CON TICKETS RESUELTOS
// ============================================

// Si se selecciona una actividad específica, obtener los departamentos vinculados
$departamentosActividad = [];
if ($actividad !== 'all') {
    $sqlDeptAct = "SELECT departamento_id FROM actividades_departamentos WHERE actividad_id = :act_id";
    $stmtDeptAct = $db->prepare($sqlDeptAct);
    $stmtDeptAct->execute([':act_id' => intval($actividad)]);
    $departamentosActividad = $stmtDeptAct->fetchAll(PDO::FETCH_COLUMN);
}

// Preparar parámetros
$params = [];
$fecha_inicio_full = $fecha_inicio . ' 00:00:00';
$fecha_fin_full = $fecha_fin . ' 23:59:59';

// Construir condiciones de filtro para tickets
// Solo estado_id = 4 (Resuelto), NO incluir 5 (Rechazado) porque no son "completados"
$ticketConditions = "t.estado_id = 4 AND t.created_at BETWEEN :fecha_inicio AND :fecha_fin";
$params[':fecha_inicio'] = $fecha_inicio_full;
$params[':fecha_fin'] = $fecha_fin_full;

// Filtro por actividad en tickets
if ($actividad !== 'all') {
    $ticketConditions .= " AND t.actividad_id = :actividad_id";
    $params[':actividad_id'] = intval($actividad);
}

$sql = "SELECT
    u.id,
    u.nombre_completo as nombre,
    COALESCE(COUNT(t.id), 0) as tickets
FROM usuarios u
LEFT JOIN tickets t ON u.id = t.asignado_a
    AND $ticketConditions
WHERE u.activo = 1";

// ============================================
// FILTROS POR ROL (siempre se aplican primero)
// ============================================
if ($user_rol === 'Usuario') {
    // Usuario normal: SIEMPRE solo ve sus propios datos
    $sql .= " AND u.id = :user_id";
    $params[':user_id'] = $user_id;
} elseif ($actividad !== 'all' && count($departamentosActividad) > 0) {
    // Admin/Jefe con actividad seleccionada: usar departamentos vinculados a esa actividad
    $deptPlaceholders = [];
    foreach ($departamentosActividad as $idx => $deptId) {
        $paramName = ':dept_act_' . $idx;
        $deptPlaceholders[] = $paramName;
        $params[$paramName] = $deptId;
    }
    $sql .= " AND u.departamento_id IN (" . implode(',', $deptPlaceholders) . ")";
} else {
    // Sin actividad específica: filtros normales por rol
    if ($user_rol === 'Jefe' && $user_departamento) {
        $sql .= " AND u.departamento_id = :departamento_id";
        $params[':departamento_id'] = $user_departamento;
    } elseif ($departamento !== 'all' && ($user_rol === 'Administrador' || $user_rol === 'Admin')) {
        $sql .= " AND u.departamento_id = :departamento_filtro";
        $params[':departamento_filtro'] = intval($departamento);
    }
}

$sql .= " GROUP BY u.id, u.nombre_completo
HAVING tickets > 0
ORDER BY tickets DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================
// CALCULAR PORCENTAJES
// ============================================

$total_tickets = array_sum(array_column($empleados, 'tickets'));

$empleados_final = [];
foreach ($empleados as $emp) {
    $porcentaje = $total_tickets > 0 ? round(($emp['tickets'] / $total_tickets) * 100, 1) : 0;
    $empleados_final[] = [
        'id' => $emp['id'],
        'nombre' => $emp['nombre'],
        'tickets' => (int)$emp['tickets'],
        'porcentaje' => $porcentaje
    ];
}

// El ganador es el PRIMER elemento (mayor tickets, ordenamos DESC)
$ganador = null;
if (count($empleados_final) > 0) {
    $ganador = $empleados_final[0];
}

// INVERTIR el array para que Chart.js muestre el ganador ARRIBA
// Chart.js horizontalBar dibuja el primer elemento abajo y el último arriba
$empleados_final = array_reverse($empleados_final);

// ============================================
// RESPUESTA JSON
// ============================================
ob_end_clean();

echo json_encode([
    'empleados' => $empleados_final,
    'ganador' => $ganador,
    'total_tickets' => $total_tickets,
    'periodo' => $periodo,
    'fecha_desde' => $fecha_inicio,
    'fecha_hasta' => $fecha_fin
], JSON_UNESCAPED_UNICODE);
