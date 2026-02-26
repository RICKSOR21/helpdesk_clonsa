<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once '../config/session.php';
session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../config/config.php';
$db = getDBConnection();

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
// ✅ CALCULAR FECHAS SEGÚN PERÍODO (DINÁMICO)
// ============================================
$hoy = new DateTime();

if ($periodo === 'personalizado' && $fecha_desde && $fecha_hasta) {
    $desde = DateTime::createFromFormat('d/m/Y', $fecha_desde);
    $hasta = DateTime::createFromFormat('d/m/Y', $fecha_hasta);

    if ($desde && $hasta) {
        $fechaDesde = $desde->format('Y-m-d 00:00:00');
        $fechaHasta = $hasta->format('Y-m-d 23:59:59');
    } else {
        $fechaDesde = $hoy->modify('-7 days')->format('Y-m-d 00:00:00');
        $fechaHasta = (new DateTime())->format('Y-m-d 23:59:59');
    }
} else {
    switch($periodo) {
        case 'mes':
            // Mes actual: desde el 1 hasta el último día del mes actual
            $fechaDesde = $hoy->format('Y-m-01 00:00:00');
            $fechaHasta = $hoy->format('Y-m-t 23:59:59'); // 't' = último día del mes
            break;
        case 'año':
            // Año actual: desde el 1 de enero hasta el 31 de diciembre
            $fechaDesde = $hoy->format('Y-01-01 00:00:00');
            $fechaHasta = $hoy->format('Y-12-31 23:59:59');
            break;
        case 'todo':
            $fechaDesde = '2000-01-01 00:00:00';
            $fechaHasta = '2099-12-31 23:59:59';
            break;
        case 'semana':
        default:
            // Última semana: últimos 7 días
            $desde = (new DateTime())->modify('-7 days');
            $fechaDesde = $desde->format('Y-m-d 00:00:00');
            $fechaHasta = (new DateTime())->format('Y-m-d 23:59:59');
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
    $whereConditions[] = "(t.asignado_a = :user_asignado OR (t.usuario_id = :user_creador AND (t.asignado_a IS NULL OR t.asignado_a = 0 OR t.asignado_a = :user_mismo)))";
    $params[':user_asignado'] = $user_id;
    $params[':user_creador'] = $user_id;
    $params[':user_mismo'] = $user_id;
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

// 1. Tickets Abiertos (todo lo NO resuelto: Abierto + En Atención + Rechazado)
$query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClause AND t.estado_id IN (1,2,3,5)";
$stmt = $db->prepare($query);
$stmt->execute($params);
$tickets_abiertos = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 2. Tickets en Proceso (En Atención + Rechazado con avance pendiente)
$query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClause AND t.estado_id IN (2,5)";
$stmt = $db->prepare($query);
$stmt->execute($params);
$tickets_proceso = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

// 3. Tickets Resueltos (solo estado_id=4, NO incluir Rechazado=5)
$query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClause AND t.estado_id = 4";
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
    $hoyComp = new DateTime();

    switch($periodo) {
        case 'mes':
            // Mes anterior al mes actual
            $mesAnterior = (new DateTime())->modify('first day of last month');
            $fechaDesdeAnterior = $mesAnterior->format('Y-m-01 00:00:00');
            $fechaHastaAnterior = $mesAnterior->format('Y-m-t 23:59:59');
            break;
        case 'año':
            // Año anterior
            $anioAnterior = $hoyComp->format('Y') - 1;
            $fechaDesdeAnterior = $anioAnterior . '-01-01 00:00:00';
            $fechaHastaAnterior = $anioAnterior . '-12-31 23:59:59';
            break;
        case 'personalizado':
            $dias_diferencia = (strtotime($fechaHasta) - strtotime($fechaDesde)) / 86400;
            $fechaDesdeAnterior = date('Y-m-d 00:00:00', strtotime($fechaDesde . " -" . ceil($dias_diferencia) . " days"));
            $fechaHastaAnterior = date('Y-m-d 23:59:59', strtotime($fechaDesde . " -1 day"));
            break;
        case 'semana':
        default:
            // Semana anterior: 7 días antes del período actual
            $fechaDesdeAnterior = (new DateTime())->modify('-14 days')->format('Y-m-d 00:00:00');
            $fechaHastaAnterior = (new DateTime())->modify('-8 days')->format('Y-m-d 23:59:59');
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
        $whereConditionsAnterior[] = "(t.asignado_a = :user_asignado OR (t.usuario_id = :user_creador AND (t.asignado_a IS NULL OR t.asignado_a = 0 OR t.asignado_a = :user_mismo)))";
        $paramsAnterior[':user_asignado'] = $user_id;
        $paramsAnterior[':user_creador'] = $user_id;
        $paramsAnterior[':user_mismo'] = $user_id;
    } elseif ($es_jefe) {
        $whereConditionsAnterior[] = "(t.usuario_id IN (SELECT id FROM usuarios WHERE departamento_id = :dept_jefe) 
                                       OR t.asignado_a IN (SELECT id FROM usuarios WHERE departamento_id = :dept_jefe2))";
        $paramsAnterior[':dept_jefe'] = $user_departamento;
        $paramsAnterior[':dept_jefe2'] = $user_departamento;
    }
    
    $whereClauseAnterior = implode(' AND ', $whereConditionsAnterior);
    
    $query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClauseAnterior AND t.estado_id IN (1,2,3,5)";
    $stmt = $db->prepare($query);
    $stmt->execute($paramsAnterior);
    $tickets_abiertos_anterior = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClauseAnterior AND t.estado_id IN (2,5)";
    $stmt = $db->prepare($query);
    $stmt->execute($paramsAnterior);
    $tickets_proceso_anterior = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClauseAnterior AND t.estado_id = 4";
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
        // Si ambos son 0, no hay cambio
        if ($actual == 0 && $anterior == 0) return 0;
        // Si el anterior es 0 pero hay datos actuales, es un incremento del 100%
        if ($anterior == 0) return ($actual > 0) ? 100 : 0;
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
ob_end_clean();

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