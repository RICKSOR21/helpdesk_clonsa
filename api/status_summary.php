<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

require_once '../config/session.php';
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../config/config.php';
$db = getDBConnection();

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

// Calcular rango de fechas según período (DINÁMICO)
$hoy = new DateTime();

if ($periodo === 'personalizado' && $fecha_desde && $fecha_hasta) {
    // Convertir formato dd/mm/yyyy a yyyy-mm-dd
    $desde_parts = explode('/', $fecha_desde);
    $hasta_parts = explode('/', $fecha_hasta);
    $fecha_desde = $desde_parts[2] . '-' . $desde_parts[1] . '-' . $desde_parts[0] . ' 00:00:00';
    $fecha_hasta = $hasta_parts[2] . '-' . $hasta_parts[1] . '-' . $hasta_parts[0] . ' 23:59:59';
} else {
    switch($periodo) {
        case 'mes':
            // Mes actual completo
            $fecha_desde = $hoy->format('Y-m-01 00:00:00');
            $fecha_hasta = $hoy->format('Y-m-t 23:59:59');
            break;
        case 'año':
            // Año actual completo
            $fecha_desde = $hoy->format('Y-01-01 00:00:00');
            $fecha_hasta = $hoy->format('Y-12-31 23:59:59');
            break;
        case 'semana':
        default:
            // Últimos 7 días
            $fecha_desde = (new DateTime())->modify('-7 days')->format('Y-m-d 00:00:00');
            $fecha_hasta = (new DateTime())->format('Y-m-d 23:59:59');
            break;
    }
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
// 1. OBTENER TOTAL DE TICKETS
// ============================================

$sqlTotal = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClause";
$stmtTotal = $db->prepare($sqlTotal);
$stmtTotal->execute($params);
$totalTickets = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];

// ============================================
// 2. DETERMINAR ACTIVIDADES SEGÚN DEPARTAMENTO
// ============================================

$actividadesPermitidas = [];
$filtrarActividades = false;

if ($puede_ver_todos && $departamento !== 'all') {
    // Admin filtrando por departamento específico: mostrar actividades del mapping + las que ya tienen tickets
    $sqlAct = "SELECT DISTINCT actividad_id FROM (
        SELECT actividad_id FROM actividades_departamentos WHERE departamento_id = ?
        UNION
        SELECT DISTINCT t.actividad_id FROM tickets t WHERE t.departamento_id = ? AND t.actividad_id IS NOT NULL
    ) combined";
    $stmtAct = $db->prepare($sqlAct);
    $stmtAct->execute([$departamento, $departamento]);
    $actividadesPermitidas = $stmtAct->fetchAll(PDO::FETCH_COLUMN);
    $filtrarActividades = true;
} elseif ($es_jefe || $es_usuario) {
    // Jefe/Usuario: actividades del mapping de su depto + las que ya tienen tickets en su depto
    if ($user_departamento) {
        $sqlAct = "SELECT DISTINCT actividad_id FROM (
            SELECT actividad_id FROM actividades_departamentos WHERE departamento_id = ?
            UNION
            SELECT DISTINCT t.actividad_id FROM tickets t WHERE t.departamento_id = ? AND t.actividad_id IS NOT NULL
        ) combined";
        $stmtAct = $db->prepare($sqlAct);
        $stmtAct->execute([$user_departamento, $user_departamento]);
        $actividadesPermitidas = $stmtAct->fetchAll(PDO::FETCH_COLUMN);
        $filtrarActividades = true;
    }
}

// ============================================
// FUNCIÓN PARA ACORTAR NOMBRES
// ============================================
function acortarNombre($nombre) {
    return str_replace('Mantenimiento', 'Mantto', $nombre);
}

// ============================================
// 3. OBTENER TODAS LAS ACTIVIDADES CON TICKETS
// ============================================

// Usar una query más simple y directa
if ($filtrarActividades && count($actividadesPermitidas) > 0) {
    // Crear placeholders para las actividades permitidas
    $actPlaceholders = implode(',', array_fill(0, count($actividadesPermitidas), '?'));

    $sqlActividades = "
        SELECT
            a.id,
            a.nombre,
            a.color,
            COUNT(t.id) as cantidad
        FROM actividades a
        INNER JOIN tickets t ON a.id = t.actividad_id
        WHERE a.id IN ($actPlaceholders)
        AND t.created_at BETWEEN ? AND ?
    ";

    // Agregar filtro de departamento si aplica
    if ($departamento !== 'all') {
        $sqlActividades .= " AND t.departamento_id = ?";
    }

    // Restricciones por rol
    if ($es_usuario) {
        $sqlActividades .= " AND (t.asignado_a = ? OR (t.usuario_id = ? AND (t.asignado_a IS NULL OR t.asignado_a = 0 OR t.asignado_a = ?)))";
    } elseif ($es_jefe) {
        $sqlActividades .= " AND (t.usuario_id IN (SELECT id FROM usuarios WHERE departamento_id = ?)
                                  OR t.asignado_a IN (SELECT id FROM usuarios WHERE departamento_id = ?))";
    }

    $sqlActividades .= " GROUP BY a.id, a.nombre, a.color HAVING cantidad > 0 ORDER BY cantidad DESC";

    $stmtActividades = $db->prepare($sqlActividades);

    // Construir array de parámetros en orden
    $actParams = $actividadesPermitidas;
    $actParams[] = $fecha_desde;
    $actParams[] = $fecha_hasta;

    if ($departamento !== 'all') {
        $actParams[] = $departamento;
    }

    if ($es_usuario) {
        $actParams[] = $user_id;
        $actParams[] = $user_id;
        $actParams[] = $user_id;
    } elseif ($es_jefe) {
        $actParams[] = $user_departamento;
        $actParams[] = $user_departamento;
    }

    $stmtActividades->execute($actParams);
} else {
    // Sin filtro de actividades (mostrar todas)
    $sqlActividades = "
        SELECT
            a.id,
            a.nombre,
            a.color,
            COUNT(t.id) as cantidad
        FROM actividades a
        INNER JOIN tickets t ON a.id = t.actividad_id
        WHERE t.created_at BETWEEN ? AND ?
    ";

    $actParams = [$fecha_desde, $fecha_hasta];

    if ($departamento !== 'all') {
        $sqlActividades .= " AND t.departamento_id = ?";
        $actParams[] = $departamento;
    }

    if ($es_usuario) {
        $sqlActividades .= " AND (t.asignado_a = ? OR (t.usuario_id = ? AND (t.asignado_a IS NULL OR t.asignado_a = 0 OR t.asignado_a = ?)))";
        $actParams[] = $user_id;
        $actParams[] = $user_id;
        $actParams[] = $user_id;
    } elseif ($es_jefe) {
        $sqlActividades .= " AND (t.usuario_id IN (SELECT id FROM usuarios WHERE departamento_id = ?)
                                  OR t.asignado_a IN (SELECT id FROM usuarios WHERE departamento_id = ?))";
        $actParams[] = $user_departamento;
        $actParams[] = $user_departamento;
    }

    $sqlActividades .= " GROUP BY a.id, a.nombre, a.color HAVING cantidad > 0 ORDER BY cantidad DESC";

    $stmtActividades = $db->prepare($sqlActividades);
    $stmtActividades->execute($actParams);
}

$todasActividades = $stmtActividades->fetchAll(PDO::FETCH_ASSOC);

// ✅ ACORTAR NOMBRES (Mantenimiento → Mantto) y calcular porcentaje
foreach ($todasActividades as &$actividad) {
    $actividad['nombre'] = acortarNombre($actividad['nombre']);
    $actividad['porcentaje'] = $totalTickets > 0 ? round(($actividad['cantidad'] * 100) / $totalTickets) : 0;
}
unset($actividad);

// ============================================
// 4. CREAR TOP 3 + OTROS
// ============================================
$topActividades = [];

// Tomar las primeras 3 actividades
for ($i = 0; $i < min(3, count($todasActividades)); $i++) {
    $topActividades[] = $todasActividades[$i];
}

// Calcular "Otros" (suma de las actividades restantes)
$otrosCantidad = 0;
$otrosPorcentaje = 0;

for ($i = 3; $i < count($todasActividades); $i++) {
    $otrosCantidad += (int)$todasActividades[$i]['cantidad'];
    $otrosPorcentaje += (float)$todasActividades[$i]['porcentaje'];
}

// Agregar "Otros" si hay actividades adicionales
if ($otrosCantidad > 0) {
    $topActividades[] = [
        'id' => 0,
        'nombre' => 'Otros',
        'color' => '#6c757d',
        'cantidad' => $otrosCantidad,
        'porcentaje' => round($otrosPorcentaje)
    ];
}

// ============================================
// RESPUESTA JSON
// ============================================
ob_end_clean();

echo json_encode([
    'total_tickets' => (int)$totalTickets,
    'top_actividades' => $topActividades,
    'periodo' => $periodo,
    'departamento' => $departamento,
    'fecha_desde' => $fecha_desde,
    'fecha_hasta' => $fecha_hasta,
    'debug_actividades_permitidas' => $actividadesPermitidas,
    'debug_total_actividades_encontradas' => count($todasActividades)
], JSON_UNESCAPED_UNICODE);
?>
