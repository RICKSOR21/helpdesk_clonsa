<?php
session_start();
header('Content-Type: application/json');

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
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

// ============================================
// OBTENER INFORMACIÓN DEL USUARIO
// ============================================
$user_id = $_SESSION['user_id'];
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
$user_departamento = $_SESSION['departamento_id'] ?? 1;

// Obtener parámetros de filtro
$departamento_id = $_GET['departamento'] ?? 'all';
$periodo = $_GET['periodo'] ?? 'semana';
$fecha_desde = $_GET['fecha_desde'] ?? null;
$fecha_hasta = $_GET['fecha_hasta'] ?? null;

// ============================================
// APLICAR RESTRICCIONES POR ROL
// ============================================
$puede_ver_todos = ($user_rol === 'Administrador' || $user_rol === 'Admin');
$es_jefe = ($user_rol === 'Jefe');
$es_usuario = ($user_rol === 'Usuario');

if (!$puede_ver_todos) {
    $departamento_id = $user_departamento;
}

// ============================================
// ✅ CALCULAR FECHAS SEGÚN PERÍODO (FIJAS)
// ============================================
if ($periodo === 'personalizado' && $fecha_desde && $fecha_hasta) {
    $desde = DateTime::createFromFormat('d/m/Y', $fecha_desde);
    $hasta = DateTime::createFromFormat('d/m/Y', $fecha_hasta);
    
    if ($desde && $hasta) {
        $fechaDesde = $desde->format('Y-m-d 00:00:00');
        $fechaHasta = $hasta->format('Y-m-d 23:59:59');
    } else {
        $fechaDesde = '2026-01-24 00:00:00';
        $fechaHasta = '2026-01-31 23:59:59';
    }
} else {
    // ✅ FECHAS FIJAS
    switch($periodo) {
        case 'mes':
            // Todo Enero 2026
            $fechaDesde = '2026-01-01 00:00:00';
            $fechaHasta = '2026-01-31 23:59:59';
            break;
        case 'año':
            // Año 2026
            $fechaDesde = '2026-01-01 00:00:00';
            $fechaHasta = '2026-12-31 23:59:59';
            break;
        case 'todo':
            $fechaDesde = '2000-01-01 00:00:00';
            $fechaHasta = '2099-12-31 23:59:59';
            break;
        case 'semana':
        default:
            // ✅ ÚLTIMA SEMANA: 24-31 Enero 2026
            $fechaDesde = '2026-01-24 00:00:00';
            $fechaHasta = '2026-01-31 23:59:59';
            break;
    }
}

// ============================================
// CONSTRUIR QUERY BASE
// ============================================
$whereConditions = ["t.created_at BETWEEN :fecha_desde AND :fecha_hasta"];
$params = [
    ':fecha_desde' => $fechaDesde,
    ':fecha_hasta' => $fechaHasta
];

if ($departamento_id !== 'all') {
    $whereConditions[] = "t.departamento_id = :departamento_id";
    $params[':departamento_id'] = $departamento_id;
}

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
// MÉTRICAS ACTUALES
// ============================================

// 1. Tickets Abiertos
$query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClause AND t.estado_id IN (1,2,3)";
$stmt = $db->prepare($query);
$stmt->execute($params);
$tickets_abiertos = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 2. Tickets en Proceso
$query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClause AND t.estado_id = 2";
$stmt = $db->prepare($query);
$stmt->execute($params);
$tickets_proceso = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 3. Tickets Resueltos
$query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClause AND t.estado_id IN (4,5)";
$stmt = $db->prepare($query);
$stmt->execute($params);
$tickets_resueltos = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 4. Tiempo Promedio
$query = "SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.fecha_resolucion)) as promedio 
          FROM tickets t 
          WHERE $whereClause AND t.fecha_resolucion IS NOT NULL";
$stmt = $db->prepare($query);
$stmt->execute($params);
$tiempo_promedio_horas = $stmt->fetch(PDO::FETCH_ASSOC)['promedio'] ?? 0;

$dias = floor($tiempo_promedio_horas / 24);
$horas = floor($tiempo_promedio_horas % 24);
$minutos = round(($tiempo_promedio_horas - floor($tiempo_promedio_horas)) * 60);
$tiempo_promedio_formato = $dias > 0 ? "{$dias}d:{$horas}h:{$minutos}min" : "{$horas}h:{$minutos}min";

// 5. Canal más frecuente
$query = "SELECT ca.nombre, COUNT(t.id) as total 
          FROM tickets t 
          INNER JOIN canales_atencion ca ON t.canal_atencion_id = ca.id 
          WHERE $whereClause 
          GROUP BY ca.id 
          ORDER BY total DESC 
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute($params);
$canal_frecuente = $stmt->fetch(PDO::FETCH_ASSOC);

// 6. Falla más frecuente
$query = "SELECT tf.nombre, COUNT(t.id) as total 
          FROM tickets t 
          INNER JOIN tipos_falla tf ON t.tipo_falla_id = tf.id 
          WHERE $whereClause 
          GROUP BY tf.id 
          ORDER BY total DESC 
          LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute($params);
$falla_frecuente = $stmt->fetch(PDO::FETCH_ASSOC);

// ============================================
// ✅ COMPARATIVAS (FECHAS FIJAS)
// ============================================
$comparativas = [
    'tickets_abiertos' => 0,
    'tickets_proceso' => 0,
    'tickets_resueltos' => 0,
    'tiempo_promedio' => 0
];

if ($periodo !== 'todo') {
    switch($periodo) {
        case 'mes':
            // Mes anterior: Diciembre 2025
            $fechaDesdeAnterior = '2025-12-01 00:00:00';
            $fechaHastaAnterior = '2025-12-31 23:59:59';
            break;
        case 'año':
            // Año anterior: 2025
            $fechaDesdeAnterior = '2025-01-01 00:00:00';
            $fechaHastaAnterior = '2025-12-31 23:59:59';
            break;
        case 'personalizado':
            $dias_diferencia = (strtotime($fechaHasta) - strtotime($fechaDesde)) / 86400;
            $fechaDesdeAnterior = date('Y-m-d 00:00:00', strtotime($fechaDesde . " -" . ceil($dias_diferencia) . " days"));
            $fechaHastaAnterior = date('Y-m-d 23:59:59', strtotime($fechaDesde . " -1 day"));
            break;
        case 'semana':
        default:
            // ✅ SEMANA ANTERIOR: 17-23 Enero 2026
            $fechaDesdeAnterior = '2026-01-17 00:00:00';
            $fechaHastaAnterior = '2026-01-23 23:59:59';
            break;
    }
    
    $whereConditionsAnterior = ["t.created_at BETWEEN :fecha_desde_anterior AND :fecha_hasta_anterior"];
    $paramsAnterior = [
        ':fecha_desde_anterior' => $fechaDesdeAnterior,
        ':fecha_hasta_anterior' => $fechaHastaAnterior
    ];
    
    if ($departamento_id !== 'all') {
        $whereConditionsAnterior[] = "t.departamento_id = :departamento_id";
        $paramsAnterior[':departamento_id'] = $departamento_id;
    }
    
    if ($es_usuario) {
        $whereConditionsAnterior[] = "t.usuario_id = :user_id";
        $paramsAnterior[':user_id'] = $user_id;
    } elseif ($es_jefe) {
        $whereConditionsAnterior[] = "(t.usuario_id IN (SELECT id FROM usuarios WHERE departamento_id = :dept_jefe) 
                                       OR t.asignado_a IN (SELECT id FROM usuarios WHERE departamento_id = :dept_jefe2))";
        $paramsAnterior[':dept_jefe'] = $user_departamento;
        $paramsAnterior[':dept_jefe2'] = $user_departamento;
    }
    
    $whereClauseAnterior = implode(' AND ', $whereConditionsAnterior);
    
    $query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClauseAnterior AND t.estado_id IN (1,2,3)";
    $stmt = $db->prepare($query);
    $stmt->execute($paramsAnterior);
    $tickets_abiertos_anterior = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClauseAnterior AND t.estado_id = 2";
    $stmt = $db->prepare($query);
    $stmt->execute($paramsAnterior);
    $tickets_proceso_anterior = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClauseAnterior AND t.estado_id IN (4,5)";
    $stmt = $db->prepare($query);
    $stmt->execute($paramsAnterior);
    $tickets_resueltos_anterior = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $query = "SELECT AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.fecha_resolucion)) as promedio 
              FROM tickets t 
              WHERE $whereClauseAnterior AND t.fecha_resolucion IS NOT NULL";
    $stmt = $db->prepare($query);
    $stmt->execute($paramsAnterior);
    $tiempo_promedio_anterior = $stmt->fetch(PDO::FETCH_ASSOC)['promedio'] ?? 0;
    
    function calcularPorcentajeCambio($actual, $anterior) {
        if ($anterior == 0) return 0;
        return round((($actual - $anterior) / $anterior) * 100, 1);
    }
    
    $comparativas['tickets_abiertos'] = calcularPorcentajeCambio($tickets_abiertos, $tickets_abiertos_anterior);
    $comparativas['tickets_proceso'] = calcularPorcentajeCambio($tickets_proceso, $tickets_proceso_anterior);
    $comparativas['tickets_resueltos'] = calcularPorcentajeCambio($tickets_resueltos, $tickets_resueltos_anterior);
    $comparativas['tiempo_promedio'] = calcularPorcentajeCambio($tiempo_promedio_horas, $tiempo_promedio_anterior);
}

// ============================================
// RESPUESTA JSON
// ============================================
$response = [
    'metricas' => [
        'tickets_abiertos' => $tickets_abiertos,
        'tickets_proceso' => $tickets_proceso,
        'tickets_resueltos' => $tickets_resueltos,
        'tiempo_promedio' => $tiempo_promedio_formato,
        'tiempo_promedio_horas' => round($tiempo_promedio_horas, 1),
        'canal_frecuente' => $canal_frecuente['nombre'] ?? 'N/A',
        'canal_frecuente_total' => $canal_frecuente['total'] ?? 0,
        'falla_frecuente' => $falla_frecuente['nombre'] ?? 'N/A',
        'falla_frecuente_total' => $falla_frecuente['total'] ?? 0
    ],
    'comparativas' => $comparativas,
    'periodo' => [
        'tipo' => $periodo,
        'fecha_desde' => date('d/m/Y', strtotime($fechaDesde)),
        'fecha_hasta' => date('d/m/Y', strtotime($fechaHasta))
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>