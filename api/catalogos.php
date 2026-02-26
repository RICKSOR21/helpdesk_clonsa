<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/session.php';
session_start();

require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? '';
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$departamentoId = (int)($_GET['departamento_id'] ?? $_POST['departamento_id'] ?? 0);
$userRol = $_SESSION['user_rol'] ?? 'Usuario';

$response = ['success' => false, 'message' => '', 'data' => []];

function isAdminCatalogos($rol) {
    return $rol === 'Administrador' || $rol === 'Admin';
}

function getCatalogConfig($tipo) {
    $configs = [
        'departamentos' => [
            'table' => 'departamentos',
            'fields' => ['nombre', 'abreviatura', 'descripcion'],
            'uses_departamento' => false
        ],
        'canales' => [
            'table' => 'canales_atencion',
            'fields' => ['nombre', 'descripcion'],
            'uses_departamento' => false
        ],
        'actividades' => [
            'table' => 'actividades',
            'fields' => ['nombre', 'descripcion', 'color'],
            'uses_departamento' => true
        ],
        'tipos_falla' => [
            'table' => 'tipos_falla',
            'fields' => ['nombre', 'descripcion', 'icono', 'departamento_id'],
            'uses_departamento' => true
        ],
        'ubicaciones' => [
            'table' => 'ubicaciones',
            'fields' => ['nombre', 'descripcion', 'departamento_id'],
            'uses_departamento' => true
        ],
        'equipos' => [
            'table' => 'equipos',
            'fields' => ['nombre', 'descripcion', 'departamento_id'],
            'uses_departamento' => true
        ],
    ];

    return $configs[$tipo] ?? null;
}

function normalizeText($value) {
    return trim((string)$value);
}

try {
    // Nuevo bloque de acciones de gestion (admin).
    if ($action !== '') {
        $cfg = getCatalogConfig($tipo);
        if (!$cfg) {
            echo json_encode(['success' => false, 'message' => 'Tipo de catalogo no valido']);
            exit;
        }

        if (!isAdminCatalogos($userRol)) {
            echo json_encode(['success' => false, 'message' => 'Sin permisos para gestionar catalogos']);
            exit;
        }

        if ($action === 'list_admin') {
            if ($tipo === 'actividades') {
                $sql = "
                    SELECT a.*, ad_ref.departamento_id, d.nombre AS departamento_nombre
                    FROM actividades a
                    LEFT JOIN (
                        SELECT actividad_id, MIN(departamento_id) AS departamento_id
                        FROM actividades_departamentos
                        GROUP BY actividad_id
                    ) ad_ref ON ad_ref.actividad_id = a.id
                    LEFT JOIN departamentos d ON d.id = ad_ref.departamento_id
                    ORDER BY a.nombre
                ";
            } elseif ($cfg['uses_departamento']) {
                $sql = "
                    SELECT c.*, d.nombre AS departamento_nombre
                    FROM {$cfg['table']} c
                    LEFT JOIN departamentos d ON d.id = c.departamento_id
                    ORDER BY c.nombre
                ";
            } else {
                $sql = "SELECT * FROM {$cfg['table']} ORDER BY nombre";
            }
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            $response['data'] = $rows;
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'create' || $action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $nombre = normalizeText($_POST['nombre'] ?? '');
            $descripcion = normalizeText($_POST['descripcion'] ?? '');
            $abreviatura = strtoupper(normalizeText($_POST['abreviatura'] ?? ''));
            $color = normalizeText($_POST['color'] ?? '');
            $icono = normalizeText($_POST['icono'] ?? '');
            $depId = (int)($_POST['departamento_id'] ?? 0);

            if ($nombre === '') {
                $response['message'] = 'El nombre es obligatorio';
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($cfg['uses_departamento'] && $depId <= 0) {
                $response['message'] = 'Debe seleccionar un departamento';
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($tipo === 'departamentos' && $abreviatura !== '' && strlen($abreviatura) > 5) {
                $response['message'] = 'La abreviatura no debe exceder 5 caracteres';
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Actividades siempre asociadas a un departamento via tabla puente.
            if ($tipo === 'actividades') {
                if ($action === 'update' && $id <= 0) {
                    $response['message'] = 'ID invalido';
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $dupSql = "
                    SELECT COUNT(*) AS total
                    FROM actividades a
                    INNER JOIN actividades_departamentos ad ON ad.actividad_id = a.id
                    WHERE LOWER(a.nombre) = LOWER(:nombre)
                      AND ad.departamento_id = :departamento_id
                ";
                $dupParams = [
                    ':nombre' => $nombre,
                    ':departamento_id' => $depId
                ];
                if ($action === 'update') {
                    $dupSql .= " AND a.id <> :id";
                    $dupParams[':id'] = $id;
                }
                $dupStmt = $db->prepare($dupSql);
                $dupStmt->execute($dupParams);
                $dup = (int)($dupStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
                if ($dup > 0) {
                    $response['message'] = 'Ya existe una actividad con ese nombre en el departamento';
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if ($action === 'create') {
                    $stmtIns = $db->prepare("
                        INSERT INTO actividades (nombre, descripcion, color, activo)
                        VALUES (:nombre, :descripcion, :color, 1)
                    ");
                    $stmtIns->execute([
                        ':nombre' => $nombre,
                        ':descripcion' => ($descripcion !== '' ? $descripcion : null),
                        ':color' => ($color !== '' ? $color : '#6b7280')
                    ]);
                    $actividadId = (int)$db->lastInsertId();

                    $stmtLink = $db->prepare("
                        INSERT INTO actividades_departamentos (actividad_id, departamento_id)
                        VALUES (:actividad_id, :departamento_id)
                    ");
                    $stmtLink->execute([
                        ':actividad_id' => $actividadId,
                        ':departamento_id' => $depId
                    ]);

                    $response['success'] = true;
                    $response['message'] = 'Registro creado correctamente';
                    $response['data'] = ['id' => $actividadId];
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $stmtUp = $db->prepare("
                    UPDATE actividades
                    SET nombre = :nombre,
                        descripcion = :descripcion,
                        color = :color
                    WHERE id = :id
                ");
                $stmtUp->execute([
                    ':nombre' => $nombre,
                    ':descripcion' => ($descripcion !== '' ? $descripcion : null),
                    ':color' => ($color !== '' ? $color : '#6b7280'),
                    ':id' => $id
                ]);

                // Mantener una sola asociacion principal por actividad.
                $stmtDelLinks = $db->prepare("DELETE FROM actividades_departamentos WHERE actividad_id = :actividad_id");
                $stmtDelLinks->execute([':actividad_id' => $id]);
                $stmtLink = $db->prepare("
                    INSERT INTO actividades_departamentos (actividad_id, departamento_id)
                    VALUES (:actividad_id, :departamento_id)
                ");
                $stmtLink->execute([
                    ':actividad_id' => $id,
                    ':departamento_id' => $depId
                ]);

                $response['success'] = true;
                $response['message'] = 'Registro actualizado correctamente';
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }

            $dupSql = "SELECT COUNT(*) AS total FROM {$cfg['table']} WHERE LOWER(nombre) = LOWER(:nombre)";
            $dupParams = [':nombre' => $nombre];
            if ($cfg['uses_departamento']) {
                $dupSql .= " AND departamento_id = :departamento_id";
                $dupParams[':departamento_id'] = $depId;
            }
            if ($action === 'update') {
                if ($id <= 0) {
                    $response['message'] = 'ID invalido';
                    echo json_encode($response, JSON_UNESCAPED_UNICODE);
                    exit;
                }
                $dupSql .= " AND id <> :id";
                $dupParams[':id'] = $id;
            }

            $dupStmt = $db->prepare($dupSql);
            $dupStmt->execute($dupParams);
            $dup = (int)($dupStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            if ($dup > 0) {
                $response['message'] = 'Ya existe un registro con ese nombre';
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($action === 'create') {
                $cols = ['nombre'];
                $vals = [':nombre'];
                $params = [':nombre' => $nombre];

                if (in_array('descripcion', $cfg['fields'], true)) {
                    $cols[] = 'descripcion';
                    $vals[] = ':descripcion';
                    $params[':descripcion'] = ($descripcion !== '' ? $descripcion : null);
                }
                if (in_array('abreviatura', $cfg['fields'], true)) {
                    $cols[] = 'abreviatura';
                    $vals[] = ':abreviatura';
                    $params[':abreviatura'] = ($abreviatura !== '' ? $abreviatura : null);
                }
                if (in_array('color', $cfg['fields'], true)) {
                    $cols[] = 'color';
                    $vals[] = ':color';
                    $params[':color'] = ($color !== '' ? $color : '#6b7280');
                }
                if (in_array('icono', $cfg['fields'], true)) {
                    $cols[] = 'icono';
                    $vals[] = ':icono';
                    $params[':icono'] = ($icono !== '' ? $icono : 'mdi mdi-alert-circle-outline');
                }
                if (in_array('departamento_id', $cfg['fields'], true)) {
                    $cols[] = 'departamento_id';
                    $vals[] = ':departamento_id';
                    $params[':departamento_id'] = $depId;
                }
                if (in_array('activo', array_column($db->query("SHOW COLUMNS FROM {$cfg['table']}")->fetchAll(PDO::FETCH_ASSOC), 'Field'), true)) {
                    $cols[] = 'activo';
                    $vals[] = '1';
                }

                $sql = "INSERT INTO {$cfg['table']} (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ")";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                $response['success'] = true;
                $response['message'] = 'Registro creado correctamente';
                $response['data'] = ['id' => (int)$db->lastInsertId()];
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }

            // update
            $sets = ['nombre = :nombre'];
            $params = [':nombre' => $nombre, ':id' => $id];

            if (in_array('descripcion', $cfg['fields'], true)) {
                $sets[] = 'descripcion = :descripcion';
                $params[':descripcion'] = ($descripcion !== '' ? $descripcion : null);
            }
            if (in_array('abreviatura', $cfg['fields'], true)) {
                $sets[] = 'abreviatura = :abreviatura';
                $params[':abreviatura'] = ($abreviatura !== '' ? $abreviatura : null);
            }
            if (in_array('color', $cfg['fields'], true)) {
                $sets[] = 'color = :color';
                $params[':color'] = ($color !== '' ? $color : '#6b7280');
            }
            if (in_array('icono', $cfg['fields'], true)) {
                $sets[] = 'icono = :icono';
                $params[':icono'] = ($icono !== '' ? $icono : 'mdi mdi-alert-circle-outline');
            }
            if (in_array('departamento_id', $cfg['fields'], true)) {
                $sets[] = 'departamento_id = :departamento_id';
                $params[':departamento_id'] = $depId;
            }

            $sql = "UPDATE {$cfg['table']} SET " . implode(', ', $sets) . " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $response['success'] = true;
            $response['message'] = 'Registro actualizado correctamente';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }

        if ($action === 'toggle_estado') {
            $id = (int)($_POST['id'] ?? 0);
            $activo = (int)($_POST['activo'] ?? 0) === 1 ? 1 : 0;
            if ($id <= 0) {
                $response['message'] = 'ID invalido';
                echo json_encode($response, JSON_UNESCAPED_UNICODE);
                exit;
            }

            $sql = "UPDATE {$cfg['table']} SET activo = :activo WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([
                ':activo' => $activo,
                ':id' => $id
            ]);
            $response['success'] = true;
            $response['message'] = $activo ? 'Registro activado' : 'Registro desactivado';
            echo json_encode($response, JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Accion no valida']);
        exit;
    }

    // Modo legado (lectura de catalogos para formularios de tickets).
    switch ($tipo) {
        case 'canales':
            $query = "SELECT id, nombre FROM canales_atencion WHERE activo = 1 ORDER BY nombre";
            break;
        case 'actividades':
            if ($departamentoId > 0) {
                $query = "SELECT DISTINCT a.id, a.nombre, a.color FROM actividades a
                          INNER JOIN actividades_departamentos ad ON a.id = ad.actividad_id
                          WHERE a.activo = 1 AND ad.departamento_id = :departamento_id
                          ORDER BY a.nombre";
            } else {
                $query = "SELECT id, nombre, color FROM actividades WHERE activo = 1 ORDER BY nombre";
            }
            break;
        case 'tipos_falla':
            if ($departamentoId > 0) {
                $query = "SELECT id, nombre, icono FROM tipos_falla WHERE activo = 1 AND departamento_id = :departamento_id ORDER BY nombre";
            } else {
                $query = "SELECT id, nombre, icono FROM tipos_falla WHERE activo = 1 ORDER BY nombre";
            }
            break;
        case 'ubicaciones':
            if ($departamentoId > 0) {
                $query = "SELECT id, nombre FROM ubicaciones WHERE activo = 1 AND departamento_id = :departamento_id ORDER BY nombre";
            } else {
                $query = "SELECT id, nombre FROM ubicaciones WHERE activo = 1 ORDER BY nombre";
            }
            break;
        case 'equipos':
            if ($departamentoId > 0) {
                $query = "SELECT id, nombre FROM equipos WHERE activo = 1 AND departamento_id = :departamento_id ORDER BY nombre";
            } else {
                $query = "SELECT id, nombre FROM equipos WHERE activo = 1 ORDER BY nombre";
            }
            break;
        case 'codigos_equipo':
            if ($departamentoId > 0) {
                $query = "SELECT id, codigo, descripcion FROM codigos_equipo WHERE activo = 1 AND departamento_id = :departamento_id ORDER BY codigo";
            } else {
                $query = "SELECT id, codigo, descripcion FROM codigos_equipo WHERE activo = 1 ORDER BY codigo";
            }
            break;
        case 'prioridades':
            $query = "SELECT id, nombre, color FROM prioridades ORDER BY nivel";
            break;
        case 'estados':
            $query = "SELECT id, nombre, color FROM estados ORDER BY id";
            break;
        case 'areas':
            $query = "SELECT id, nombre FROM areas WHERE activo = 1 ORDER BY nombre";
            break;
        case 'usuarios':
            if ($departamentoId > 0) {
                $query = "SELECT id, nombre_completo AS nombre, username FROM usuarios WHERE activo = 1 AND departamento_id = :departamento_id ORDER BY nombre_completo";
            } else {
                $query = "SELECT id, nombre_completo AS nombre, username FROM usuarios WHERE activo = 1 ORDER BY nombre_completo";
            }
            break;
        case 'departamentos':
            $query = "SELECT id, nombre, abreviatura FROM departamentos WHERE activo = 1 ORDER BY nombre";
            break;
        case 'next_ticket_number':
            $deptId = (int)($_GET['departamento_id'] ?? 0);
            if ($deptId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Departamento requerido']);
                exit;
            }
            $stmt = $db->prepare("SELECT COALESCE(ultimo_numero, 0) + 1 AS next_num FROM ticket_contadores WHERE departamento_id = :departamento_id");
            $stmt->execute([':departamento_id' => $deptId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'next_number' => (int)($result['next_num'] ?? 1)]);
            exit;
        default:
            echo json_encode(['success' => false, 'message' => 'Tipo de catalogo no valido']);
            exit;
    }

    $stmt = $db->prepare($query);
    if ($departamentoId > 0 && strpos($query, ':departamento_id') !== false) {
        $stmt->bindValue(':departamento_id', $departamentoId, PDO::PARAM_INT);
    }
    $stmt->execute();
    $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $response['success'] = true;
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
