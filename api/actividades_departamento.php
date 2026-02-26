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

$departamento = $_GET['departamento'] ?? 'all';

// Obtener departamentos con sus actividades
$resultado = [];

if ($departamento === 'all') {
    // Obtener todos los departamentos con sus actividades
    $sql = "SELECT
                d.id as departamento_id,
                d.nombre as departamento_nombre,
                a.id as actividad_id,
                a.nombre as actividad_nombre
            FROM departamentos d
            LEFT JOIN actividades_departamentos ad ON d.id = ad.departamento_id
            LEFT JOIN actividades a ON ad.actividad_id = a.id AND a.activo = 1
            WHERE d.activo = 1 AND d.id != 1
            ORDER BY d.id, a.nombre";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar por departamento
    $departamentos = [];
    foreach ($rows as $row) {
        $deptId = $row['departamento_id'];
        if (!isset($departamentos[$deptId])) {
            $departamentos[$deptId] = [
                'id' => $deptId,
                'nombre' => $row['departamento_nombre'],
                'actividades' => []
            ];
        }
        if ($row['actividad_id']) {
            $departamentos[$deptId]['actividades'][] = [
                'id' => $row['actividad_id'],
                'nombre' => str_replace('Mantenimiento', 'Mantto', $row['actividad_nombre'])
            ];
        }
    }

    $resultado = [
        'mostrar_todos' => true,
        'departamentos' => array_values($departamentos)
    ];
} else {
    // Obtener actividades del departamento específico
    $sql = "SELECT
                a.id as actividad_id,
                a.nombre as actividad_nombre
            FROM actividades_departamentos ad
            INNER JOIN actividades a ON ad.actividad_id = a.id AND a.activo = 1
            WHERE ad.departamento_id = :departamento_id
            ORDER BY a.nombre";

    $stmt = $db->prepare($sql);
    $stmt->execute([':departamento_id' => $departamento]);
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener nombre del departamento
    $sqlDept = "SELECT nombre FROM departamentos WHERE id = :id";
    $stmtDept = $db->prepare($sqlDept);
    $stmtDept->execute([':id' => $departamento]);
    $deptNombre = $stmtDept->fetchColumn();

    $resultado = [
        'mostrar_todos' => false,
        'departamento_actual' => [
            'id' => $departamento,
            'nombre' => $deptNombre
        ],
        'actividades' => array_map(function($a) {
            return [
                'id' => $a['actividad_id'],
                'nombre' => str_replace('Mantenimiento', 'Mantto', $a['actividad_nombre'])
            ];
        }, $actividades)
    ];
}

ob_end_clean();
echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
