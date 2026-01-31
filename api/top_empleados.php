<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$host = 'localhost';
$dbname = 'helpdesk_clonsa';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
$user_departamento = $_SESSION['departamento_id'] ?? null;

$periodo = $_GET['periodo'] ?? 'semana';
$departamento = $_GET['departamento'] ?? 'all';
$fecha_desde = $_GET['fecha_desde'] ?? null;
$fecha_hasta = $_GET['fecha_hasta'] ?? null;

// ============================================
// CALCULAR FECHAS
// ============================================

$hoy = date('Y-m-d');
switch ($periodo) {
    case 'mes':
        $fecha_inicio = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'año':
        $fecha_inicio = date('Y-m-d', strtotime('-365 days'));
        break;
    case 'personalizado':
        $fecha_inicio = $fecha_desde ?? date('Y-m-d', strtotime('-7 days'));
        $fecha_fin = $fecha_hasta ?? $hoy;
        break;
    default:
        $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
}

if ($periodo !== 'personalizado') {
    $fecha_fin = $hoy;
}

// ============================================
// ✅ QUERY: TODOS LOS USUARIOS CON SUS TICKETS (O 0 SI NO TIENEN)
// ============================================

$sql = "SELECT 
    u.id,
    u.nombre_completo as nombre,
    COALESCE(COUNT(t.id), 0) as tickets
FROM usuarios u
LEFT JOIN tickets t ON u.id = t.asignado_a 
    AND t.estado_id = 5
    AND DATE(t.fecha_resolucion) BETWEEN :fecha_inicio AND :fecha_fin
WHERE 1=1";

// ✅ Filtros por rol
if ($user_rol === 'Jefe' && $user_departamento) {
    $sql .= " AND u.departamento_id = :departamento_id";
} elseif ($user_rol === 'Usuario') {
    $sql .= " AND u.id = :user_id";
} elseif ($departamento !== 'all' && ($user_rol === 'Administrador' || $user_rol === 'Admin')) {
    $sql .= " AND u.departamento_id = :departamento_filtro";
}

$sql .= " GROUP BY u.id, u.nombre_completo
ORDER BY tickets DESC
LIMIT 10";

$stmt = $db->prepare($sql);
$stmt->bindParam(':fecha_inicio', $fecha_inicio);
$stmt->bindParam(':fecha_fin', $fecha_fin);

if ($user_rol === 'Jefe' && $user_departamento) {
    $stmt->bindParam(':departamento_id', $user_departamento, PDO::PARAM_INT);
}
if ($user_rol === 'Usuario') {
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
}
if (($user_rol === 'Administrador' || $user_rol === 'Admin') && $departamento !== 'all') {
    $dept_id = intval($departamento);
    $stmt->bindParam(':departamento_filtro', $dept_id, PDO::PARAM_INT);
}

$stmt->execute();
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

// ✅ Si hay al menos un empleado con tickets > 0, ese es el ganador
// ✅ Si todos tienen 0, el "ganador" es el primero con 0 tickets
$ganador = null;
if (count($empleados_final) > 0) {
    // Buscar el primero con tickets > 0
    foreach ($empleados_final as $emp) {
        if ($emp['tickets'] > 0) {
            $ganador = $emp;
            break;
        }
    }
    // Si no hay ninguno con tickets, el ganador es el primero (con 0 tickets)
    if (!$ganador) {
        $ganador = $empleados_final[0];
    }
}

// ============================================
// RESPUESTA JSON
// ============================================

echo json_encode([
    'empleados' => $empleados_final,
    'ganador' => $ganador,
    'total_tickets' => $total_tickets,
    'periodo' => $periodo,
    'fecha_desde' => $fecha_inicio,
    'fecha_hasta' => $fecha_fin
], JSON_UNESCAPED_UNICODE);