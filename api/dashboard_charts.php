<?php
require_once '../config/session.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once '../config/config.php';
$db = getDBConnection();

$user_id = $_SESSION['user_id'];
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
$user_departamento = $_SESSION['departamento_id'] ?? 1;

$departamento_id = $_GET['departamento'] ?? 'all';
$periodo = $_GET['periodo'] ?? 'semana';
$fecha_desde = $_GET['fecha_desde'] ?? null;
$fecha_hasta = $_GET['fecha_hasta'] ?? null;

$puede_ver_todos = ($user_rol === 'Administrador' || $user_rol === 'Admin');
$es_jefe = ($user_rol === 'Jefe');
$es_usuario = ($user_rol === 'Usuario');

if (!$puede_ver_todos) {
    $departamento_id = $user_departamento;
}

// CALCULAR FECHAS (DINÁMICO)
$hoy = new DateTime();

if ($periodo === 'personalizado' && $fecha_desde && $fecha_hasta) {
    $desde = DateTime::createFromFormat('d/m/Y', $fecha_desde);
    $hasta = DateTime::createFromFormat('d/m/Y', $fecha_hasta);

    if ($desde && $hasta) {
        $fechaDesde = $desde->format('Y-m-d 00:00:00');
        $fechaHasta = $hasta->format('Y-m-d 23:59:59');
    } else {
        $fechaDesde = (new DateTime())->modify('-7 days')->format('Y-m-d 00:00:00');
        $fechaHasta = (new DateTime())->format('Y-m-d 23:59:59');
    }
} else {
    switch($periodo) {
        case 'mes':
            // Mes actual completo
            $fechaDesde = $hoy->format('Y-m-01 00:00:00');
            $fechaHasta = $hoy->format('Y-m-t 23:59:59');
            break;
        case 'año':
            // Año actual completo
            $fechaDesde = $hoy->format('Y-01-01 00:00:00');
            $fechaHasta = $hoy->format('Y-12-31 23:59:59');
            break;
        case 'semana':
        default:
            // Últimos 7 días
            $fechaDesde = (new DateTime())->modify('-7 days')->format('Y-m-d 00:00:00');
            $fechaHasta = (new DateTime())->format('Y-m-d 23:59:59');
            break;
    }
}

// GENERAR LABELS
function generarLabelsYGrupos($periodo, $fechaDesde, $fechaHasta) {
  $labels = [];
  $grupos = [];
  
  $desde = new DateTime($fechaDesde);
  $hasta = new DateTime($fechaHasta);
  $diff_days = $desde->diff($hasta)->days;
  
  $dias_semana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
  $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
  
  switch($periodo) {
      case 'semana':
          for ($i = 0; $i < 7; $i++) {
              $fecha = clone $desde;
              $fecha->modify("+$i days");
              
              $dia_semana = $dias_semana[$fecha->format('w')];
              $mes = $meses[$fecha->format('n') - 1];
              $labels[] = $dia_semana . ' ' . $fecha->format('d') . '/' . $mes;
              
              $grupos[] = [
                  'desde' => $fecha->format('Y-m-d 00:00:00'),
                  'hasta' => $fecha->format('Y-m-d 23:59:59')
              ];
          }
          break;
          
      case 'mes':
          for ($i = 0; $i < 30; $i += 5) {
              $inicio = clone $desde;
              $inicio->modify("+$i days");
              
              $fin = clone $inicio;
              $fin->modify("+4 days");
              
              if ($fin > $hasta) $fin = clone $hasta;
              
              $mes = $meses[$fin->format('n') - 1];
              $labels[] = $inicio->format('d') . '-' . $fin->format('d') . '/' . $mes;
              
              $grupos[] = [
                  'desde' => $inicio->format('Y-m-d 00:00:00'),
                  'hasta' => $fin->format('Y-m-d 23:59:59')
              ];
          }
          break;
          
      case 'año':
          for ($i = 0; $i < 12; $i++) {
              $mes_fecha = clone $desde;
              $mes_fecha->modify("+$i months");
              
              $labels[] = $meses[$mes_fecha->format('n') - 1];
              
              $primer_dia = $mes_fecha->format('Y-m-01 00:00:00');
              $ultimo_dia = $mes_fecha->format('Y-m-t 23:59:59');
              
              $grupos[] = [
                  'desde' => $primer_dia,
                  'hasta' => $ultimo_dia
              ];
          }
          break;
          
      case 'personalizado':
          $puntos = min(12, max(6, ceil($diff_days / 5)));
          $dias_por_punto = ceil($diff_days / $puntos);
          
          for ($i = 0; $i < $puntos; $i++) {
              $inicio = clone $desde;
              $inicio->modify("+" . ($i * $dias_por_punto) . " days");
              
              $fin = clone $inicio;
              $fin->modify("+" . ($dias_por_punto - 1) . " days");
              
              if ($fin > $hasta) $fin = clone $hasta;
              
              if ($diff_days <= 7) {
                  $dia_semana = $dias_semana[$inicio->format('w')];
                  $mes = $meses[$inicio->format('n') - 1];
                  $labels[] = $dia_semana . ' ' . $inicio->format('d') . '/' . $mes;
              } elseif ($diff_days <= 60) {
                  $mes = $meses[$fin->format('n') - 1];
                  $labels[] = $inicio->format('d') . '-' . $fin->format('d') . '/' . $mes;
              } else {
                  $mes_inicio = $meses[$inicio->format('n') - 1];
                  $mes_fin = $meses[$fin->format('n') - 1];
                  $labels[] = $inicio->format('d') . ' ' . $mes_inicio . ' - ' . $fin->format('d') . ' ' . $mes_fin;
              }
              
              $grupos[] = [
                  'desde' => $inicio->format('Y-m-d 00:00:00'),
                  'hasta' => $fin->format('Y-m-d 23:59:59')
              ];
          }
          break;
  }
  
  return ['labels' => $labels, 'grupos' => $grupos];
}

$labelsYGrupos = generarLabelsYGrupos($periodo, $fechaDesde, $fechaHasta);
$labels = $labelsYGrupos['labels'];
$grupos = $labelsYGrupos['grupos'];

// ✅ DETERMINAR ACTIVIDADES SEGÚN DEPARTAMENTO - SIEMPRE LEYENDA FIJA
$actividades_ids = [];
$actividades_info = [];
$tipo_grafico = 'fijo'; // Siempre usar leyenda fija (sin dropdown)

if ($departamento_id === 'all' || $departamento_id == 2) {
    // ✅ GENERAL Y SOPORTE TÉCNICO: 3 actividades de mantenimiento
    $actividades_ids = [1, 2, 3];
    $actividades_info = [
        ['id' => 1, 'nombre' => 'Mantto Preventivo', 'color' => '#1F3BB3'],
        ['id' => 2, 'nombre' => 'Mantto Correctivo', 'color' => '#4CAF50'],
        ['id' => 3, 'nombre' => 'Mantto Predictivo', 'color' => '#FF9800']
    ];

} else {
    // ✅ OTROS DEPARTAMENTOS: Obtener actividades dinámicamente
    $sqlAct = "SELECT actividad_id FROM actividades_departamentos WHERE departamento_id = :dept_id";
    $stmtAct = $db->prepare($sqlAct);
    $stmtAct->execute([':dept_id' => $departamento_id]);
    $actividades_ids = $stmtAct->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($actividades_ids)) {
        $placeholders = implode(',', array_fill(0, count($actividades_ids), '?'));
        $sqlInfo = "SELECT id, nombre, color FROM actividades WHERE id IN ($placeholders) ORDER BY id";
        $stmtInfo = $db->prepare($sqlInfo);
        $stmtInfo->execute($actividades_ids);
        $actividades_info = $stmtInfo->fetchAll(PDO::FETCH_ASSOC);
    }
}

// QUERY BASE
$whereConditions = [];
$params = [];

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

// OBTENER DATOS
$datasets = [];

foreach ($actividades_info as $actividad) {
    $datos = [];
    
    foreach ($grupos as $grupo) {
        $whereTemp = array_merge($whereConditions, [
            "t.created_at BETWEEN :grupo_desde AND :grupo_hasta",
            "t.actividad_id = :actividad_id"
        ]);
        
        $paramsTemp = array_merge($params, [
            ':grupo_desde' => $grupo['desde'],
            ':grupo_hasta' => $grupo['hasta'],
            ':actividad_id' => $actividad['id']
        ]);
        
        $whereClause = implode(' AND ', $whereTemp);
        
        $query = "SELECT COUNT(*) as total FROM tickets t WHERE $whereClause";
        $stmt = $db->prepare($query);
        $stmt->execute($paramsTemp);
        $datos[] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
    $datasets[] = [
        'id' => $actividad['id'],
        'nombre' => $actividad['nombre'],
        'color' => $actividad['color'],
        'data' => $datos
    ];
}

echo json_encode([
    'labels' => $labels,
    'datasets' => $datasets,
    'actividades' => $actividades_info,
    'periodo' => $periodo,
    'departamento' => $departamento_id,
    'total_puntos' => count($labels),
    'tipo_grafico' => $tipo_grafico
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>