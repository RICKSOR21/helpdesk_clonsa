<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Conexión a BD
$host = 'localhost';
$dbname = 'helpdesk_clonsa';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión: ' . $e->getMessage()]);
    exit;
}

// Obtener datos de sesión
$user_id = $_SESSION['user_id'];
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
$user_departamento = $_SESSION['departamento_id'] ?? null;

// Obtener parámetros
$departamento = $_GET['departamento'] ?? 'all';
$periodo = $_GET['periodo'] ?? 'semana';
$fecha_desde = $_GET['fecha_desde'] ?? null;
$fecha_hasta = $_GET['fecha_hasta'] ?? null;

// ============================================
// DETERMINAR PERMISOS Y FILTROS
// ============================================

$puede_ver_todos = ($user_rol === 'Administrador' || $user_rol === 'Admin');
$es_jefe = ($user_rol === 'Jefe');
$es_usuario = ($user_rol === 'Usuario');

// Calcular rango de fechas según período
if (!$fecha_desde || !$fecha_hasta) {
    $hoy = new DateTime();
    
    switch($periodo) {
        case 'mes':
            $desde = clone $hoy;
            $desde->modify('-30 days');
            break;
        case 'año':
            $desde = clone $hoy;
            $desde->modify('-1 year');
            break;
        case 'semana':
        default:
            $desde = clone $hoy;
            $desde->modify('-7 days');
            break;
    }
    
    $fecha_desde = $desde->format('Y-m-d 00:00:00');
    $fecha_hasta = $hoy->format('Y-m-d 23:59:59');
} else {
    // Convertir formato dd/mm/yyyy a yyyy-mm-dd
    $desde_parts = explode('/', $fecha_desde);
    $hasta_parts = explode('/', $fecha_hasta);
    $fecha_desde = $desde_parts[2] . '-' . $desde_parts[1] . '-' . $desde_parts[0] . ' 00:00:00';
    $fecha_hasta = $hasta_parts[2] . '-' . $hasta_parts[1] . '-' . $hasta_parts[0] . ' 23:59:59';
}

// ============================================
// CONSTRUIR QUERY BASE
// ============================================

$whereConditions = ["t.created_at BETWEEN :fecha_desde AND :fecha_hasta"];
$params = [
    ':fecha_desde' => $fecha_desde,
    ':fecha_hasta' => $fecha_hasta
];

// Filtro por departamento
if ($departamento !== 'all') {
    $whereConditions[] = "t.departamento_id = :departamento_id";
    $params[':departamento_id'] = $departamento;
}

// Restricciones por rol
if ($es_usuario) {
    $whereConditions[] = "t.usuario_id = :user_id";
    $params[':user_id'] = $user_id;
} elseif ($es_jefe) {
    $whereConditions[] = "(t.usuario_id IN (SELECT id FROM usuarios WHERE departamento_id = :dept_jefe) 
                           OR t.asignado_a IN (SELECT id FROM usuarios WHERE departamento_id = :dept_jefe2))";
    $params[':dept_jefe'] = $user_departamento;
    $params[':dept_jefe2'] = $user_departamento;
}

$whereClause = implode(' AND ', $whereConditions);

// ============================================
// 1. OBTENER TOTAL DE TICKETS
// ============================================

$sqlTotal = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClause";
$stmtTotal = $db->prepare($sqlTotal);
$stmtTotal->execute($params);
$totalTickets = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

// ============================================
// 2. DETERMINAR ACTIVIDADES SEGÚN DEPARTAMENTO
// ============================================

$actividadesPermitidas = null;

if ($puede_ver_todos && $departamento !== 'all') {
    // Admin filtrando por departamento específico
    $sqlActividades = "SELECT actividad_id FROM actividades_departamentos WHERE departamento_id = :dept_id";
    $stmtAct = $db->prepare($sqlActividades);
    $stmtAct->execute([':dept_id' => $departamento]);
    $actividadesPermitidas = $stmtAct->fetchAll(PDO::FETCH_COLUMN);
} elseif ($es_jefe || $es_usuario) {
    // Jefe/Usuario: actividades de su departamento
    $sqlActividades = "SELECT actividad_id FROM actividades_departamentos WHERE departamento_id = :dept_id";
    $stmtAct = $db->prepare($sqlActividades);
    $stmtAct->execute([':dept_id' => $user_departamento]);
    $actividadesPermitidas = $stmtAct->fetchAll(PDO::FETCH_COLUMN);
}

// ============================================
// FUNCIÓN PARA ACORTAR NOMBRES
// ============================================
function acortarNombre($nombre) {
    return str_replace('Mantenimiento', 'Mantto', $nombre);
}

// ============================================
// 3. OBTENER TOP 4 ACTIVIDADES
// ============================================

$sqlActividades = "
    SELECT 
        a.id,
        a.nombre,
        a.color,
        COALESCE(COUNT(t.id), 0) as cantidad,
        CASE 
            WHEN :total > 0 THEN ROUND((COUNT(t.id) * 100.0 / :total), 2)
            ELSE 0
        END as porcentaje
    FROM actividades a
    LEFT JOIN tickets t ON a.id = t.actividad_id AND $whereClause
";

if ($actividadesPermitidas !== null && count($actividadesPermitidas) > 0) {
    $placeholders = implode(',', array_fill(0, count($actividadesPermitidas), '?'));
    $sqlActividades .= " WHERE a.id IN ($placeholders)";
}

$sqlActividades .= "
    GROUP BY a.id, a.nombre, a.color
    ORDER BY cantidad DESC, a.nombre ASC
    LIMIT 4
";

$stmtActividades = $db->prepare($sqlActividades);

// Bind params
$bindParams = array_merge([':total' => $totalTickets ?: 1], $params);
$bindIndex = 1;

foreach ($bindParams as $key => $value) {
    if (is_string($key)) {
        $stmtActividades->bindValue($key, $value);
    }
}

if ($actividadesPermitidas !== null && count($actividadesPermitidas) > 0) {
    foreach ($actividadesPermitidas as $actId) {
        $stmtActividades->bindValue($bindIndex++, $actId, PDO::PARAM_INT);
    }
}

$stmtActividades->execute();
$topActividades = $stmtActividades->fetchAll(PDO::FETCH_ASSOC);

// ✅ ACORTAR NOMBRES (Mantenimiento → Mantto)
foreach ($topActividades as &$actividad) {
    $actividad['nombre'] = acortarNombre($actividad['nombre']);
}
unset($actividad); // Liberar referencia

// ✅ Si hay menos de 4 actividades, completar con ceros
while (count($topActividades) < 4) {
    $topActividades[] = [
        'id' => 0,
        'nombre' => 'Sin actividad',
        'color' => '#CCCCCC',
        'cantidad' => 0,
        'porcentaje' => 0
    ];
}

// ============================================
// RESPUESTA JSON
// ============================================

echo json_encode([
    'total_tickets' => (int)$totalTickets,
    'top_actividades' => $topActividades,
    'periodo' => $periodo,
    'departamento' => $departamento,
    'fecha_desde' => $fecha_desde,
    'fecha_hasta' => $fecha_hasta
], JSON_UNESCAPED_UNICODE);
?>