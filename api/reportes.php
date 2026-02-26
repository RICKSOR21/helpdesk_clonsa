<?php
require_once '../config/session.php';
session_start();
require_once '../config/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

function to_ascii_lower($value) {
    $value = mb_strtolower((string)$value, 'UTF-8');
    $search = ['á','é','í','ó','ú','ñ'];
    $replace = ['a','e','i','o','u','n'];
    return str_replace($search, $replace, $value);
}

function classify_state($estadoNombre) {
    $s = to_ascii_lower($estadoNombre);
    if (strpos($s, 'rechaz') !== false) return 'rechazado';
    if (strpos($s, 'verif') !== false || strpos($s, 'pendiente') !== false) return 'verificando';
    if (strpos($s, 'atenc') !== false || strpos($s, 'proceso') !== false) return 'en_atencion';
    if (strpos($s, 'resuel') !== false || strpos($s, 'cerrad') !== false || strpos($s, 'aprob') !== false) return 'resuelto';
    if (strpos($s, 'abiert') !== false) return 'abierto';
    return 'otro';
}

function period_range($periodo, $fechaDesde, $fechaHasta) {
    $today = new DateTimeImmutable('now');
    $periodo = strtolower((string)$periodo);

    if ($periodo === 'personalizado' && $fechaDesde && $fechaHasta) {
        $d1 = DateTimeImmutable::createFromFormat('Y-m-d', $fechaDesde);
        $d2 = DateTimeImmutable::createFromFormat('Y-m-d', $fechaHasta);
        if ($d1 && $d2 && $d1 <= $d2) {
            return [$d1->setTime(0, 0, 0), $d2->setTime(23, 59, 59)];
        }
    }

    if ($periodo === 'mes') {
        return [
            $today->modify('first day of this month')->setTime(0, 0, 0),
            $today->modify('last day of this month')->setTime(23, 59, 59)
        ];
    }

    if ($periodo === 'ano') {
        return [
            $today->setDate((int)$today->format('Y'), 1, 1)->setTime(0, 0, 0),
            $today->setDate((int)$today->format('Y'), 12, 31)->setTime(23, 59, 59)
        ];
    }

    return [$today->modify('-6 days')->setTime(0, 0, 0), $today->setTime(23, 59, 59)];
}

function date_labels(DateTimeImmutable $from, DateTimeImmutable $to) {
    $labels = [];
    $cursor = $from;
    while ($cursor <= $to) {
        $labels[$cursor->format('Y-m-d')] = ['creados' => 0, 'cerrados' => 0];
        $cursor = $cursor->modify('+1 day');
    }
    return $labels;
}

try {
    $db = getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $userId = (int)($_SESSION['user_id'] ?? 0);
    $userRol = (string)($_SESSION['user_rol'] ?? 'Usuario');
    $userDept = (int)($_SESSION['departamento_id'] ?? 0);

    $isAdmin = ($userRol === 'Administrador' || $userRol === 'Admin');
    $isJefe = ($userRol === 'Jefe');

    $action = $_GET['action'] ?? 'summary';
    $scope = $_GET['scope'] ?? 'general';
    $periodo = $_GET['periodo'] ?? 'semana';
    $selectedDept = $_GET['departamento'] ?? 'all';
    $selectedUser = $_GET['usuario'] ?? 'all';
    $rawDesde = $_GET['fecha_desde'] ?? null;
    $rawHasta = $_GET['fecha_hasta'] ?? null;

    [$from, $to] = period_range($periodo, $rawDesde, $rawHasta);

    $where = ['t.created_at BETWEEN :desde AND :hasta'];
    $params = [
        ':desde' => $from->format('Y-m-d H:i:s'),
        ':hasta' => $to->format('Y-m-d H:i:s'),
    ];

    if ($isAdmin) {
        if ($selectedDept !== 'all') {
            $where[] = 't.departamento_id = :departamento_id';
            $params[':departamento_id'] = (int)$selectedDept;
        }
    } else {
        $where[] = 't.departamento_id = :departamento_id';
        $params[':departamento_id'] = $userDept;
    }

    if ($action !== 'created_detail') {
        if ($selectedUser !== 'all') {
            $where[] = '(t.asignado_a = :usuario_asignado_id OR (t.usuario_id = :usuario_creador_id AND (t.asignado_a IS NULL OR t.asignado_a = 0 OR t.asignado_a = :usuario_mismo_id)))';
            $params[':usuario_asignado_id'] = (int)$selectedUser;
            $params[':usuario_creador_id'] = (int)$selectedUser;
            $params[':usuario_mismo_id'] = (int)$selectedUser;
        } elseif (!$isAdmin && !$isJefe) {
            $where[] = '(t.asignado_a = :usuario_asignado_id OR (t.usuario_id = :usuario_creador_id AND (t.asignado_a IS NULL OR t.asignado_a = 0 OR t.asignado_a = :usuario_mismo_id)))';
            $params[':usuario_asignado_id'] = $userId;
            $params[':usuario_creador_id'] = $userId;
            $params[':usuario_mismo_id'] = $userId;
        }
    } elseif (!$isAdmin && !$isJefe) {
        $where[] = '(t.asignado_a = :usuario_asignado_id OR (t.usuario_id = :usuario_creador_id AND (t.asignado_a IS NULL OR t.asignado_a = 0 OR t.asignado_a = :usuario_mismo_id)))';
        $params[':usuario_asignado_id'] = $userId;
        $params[':usuario_creador_id'] = $userId;
        $params[':usuario_mismo_id'] = $userId;
    }

    if ($action === 'created_detail') {
        $createdUserId = (int)($_GET['created_user_id'] ?? 0);
        if ($createdUserId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Usuario invalido'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if (!$isAdmin && !$isJefe && $createdUserId !== $userId) {
            echo json_encode(['success' => false, 'message' => 'No autorizado para este detalle'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $where[] = 't.usuario_id = :created_user_id';
        $params[':created_user_id'] = $createdUserId;
        $sqlDetail = "SELECT
                        t.codigo,
                        t.titulo,
                        DATE_FORMAT(t.created_at, '%d/%m/%Y %H:%i') AS created_at,
                        COALESCE(d.nombre, 'Sin departamento') AS departamento_nombre,
                        COALESCE(e.nombre, 'Sin estado') AS estado_nombre
                      FROM tickets t
                      LEFT JOIN departamentos d ON d.id = t.departamento_id
                      LEFT JOIN estados e ON e.id = t.estado_id
                      WHERE " . implode(' AND ', $where) . "
                      ORDER BY t.created_at DESC";
        $stmtDetail = $db->prepare($sqlDetail);
        $stmtDetail->execute($params);
        $rows = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);
        $summary = [
            'total' => count($rows),
            'abierto' => 0,
            'en_atencion' => 0,
            'verificando' => 0,
            'resuelto' => 0,
            'rechazado' => 0,
        ];
        foreach ($rows as $r) {
            $key = classify_state($r['estado_nombre'] ?? '');
            if (isset($summary[$key])) {
                $summary[$key]++;
            }
        }
        echo json_encode(['success' => true, 'data' => $rows, 'summary' => $summary], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $sql = "SELECT
                t.id,
                t.codigo,
                t.titulo,
                t.created_at,
                t.updated_at,
                t.fecha_resolucion,
                t.usuario_id,
                t.asignado_a,
                t.departamento_id,
                COALESCE(d.nombre, 'Sin departamento') AS departamento_nombre,
                COALESCE(ca.nombre, 'Sin canal') AS canal_nombre,
                COALESCE(a.nombre, 'Sin actividad') AS actividad_nombre,
                COALESCE(tf.nombre, 'Sin falla') AS falla_nombre,
                COALESCE(e.nombre, 'Sin estado') AS estado_nombre,
                COALESCE(uc.nombre_completo, 'Sin usuario') AS creador_nombre,
                COALESCE(ua.nombre_completo, 'Sin asignado') AS asignado_nombre
            FROM tickets t
            LEFT JOIN departamentos d ON d.id = t.departamento_id
            LEFT JOIN canales_atencion ca ON ca.id = t.canal_atencion_id
            LEFT JOIN actividades a ON a.id = t.actividad_id
            LEFT JOIN tipos_falla tf ON tf.id = t.tipo_falla_id
            LEFT JOIN estados e ON e.id = t.estado_id
            LEFT JOIN usuarios uc ON uc.id = t.usuario_id
            LEFT JOIN usuarios ua ON ua.id = t.asignado_a
            WHERE " . implode(' AND ', $where) . "
            ORDER BY t.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($action === 'tickets_export') {
        $rows = array_map(function ($t) {
            return [
                'codigo' => $t['codigo'],
                'titulo' => $t['titulo'],
                'estado' => $t['estado_nombre'],
                'departamento' => $t['departamento_nombre'],
                'canal' => $t['canal_nombre'],
                'actividad' => $t['actividad_nombre'],
                'falla' => $t['falla_nombre'],
                'creador' => $t['creador_nombre'],
                'asignado_a' => $t['asignado_nombre'],
                'creado' => $t['created_at'],
                'actualizado' => $t['updated_at'],
                'resuelto' => $t['fecha_resolucion'],
            ];
        }, $tickets);
        echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($isAdmin) {
        $usersStmt = $db->prepare("SELECT id, nombre_completo FROM usuarios ORDER BY nombre_completo");
        $usersStmt->execute();
    } elseif ($isJefe) {
        $usersStmt = $db->prepare("SELECT id, nombre_completo FROM usuarios WHERE departamento_id = :dept ORDER BY nombre_completo");
        $usersStmt->execute([':dept' => $userDept]);
    } else {
        // Usuario normal: solo ve su propio usuario
        $usersStmt = $db->prepare("SELECT id, nombre_completo FROM usuarios WHERE id = :uid ORDER BY nombre_completo");
        $usersStmt->execute([':uid' => $userId]);
    }
    $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    $userMap = [];
    foreach ($users as $u) {
        $userMap[(int)$u['id']] = $u['nombre_completo'];
    }

    $deptWhere = $isAdmin ? '' : 'WHERE id = :dept';
    $deptStmt = $db->prepare("SELECT id, nombre FROM departamentos $deptWhere ORDER BY nombre");
    if (!$isAdmin) {
        $deptStmt->execute([':dept' => $userDept]);
    } else {
        $deptStmt->execute();
    }
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

    $kpi = [
        'total' => 0,
        'abierto' => 0,
        'en_atencion' => 0,
        'verificando' => 0,
        'resuelto' => 0,
        'rechazado' => 0,
        'otro' => 0,
        'promedio_horas' => 0.0,
        'sla_48' => 0.0,
    ];

    $daily = date_labels($from, $to);
    $byStatus = [];
    $byChannel = [];
    $byActivity = [];
    $byDepartment = [];
    $userPerf = [];
    $deptPerf = [];
    $resHours = [];
    $resolvedInSla = 0;
    $resolvedTotal = 0;

    foreach ($tickets as $t) {
        $kpi['total']++;
        $state = classify_state($t['estado_nombre']);
        if (!isset($kpi[$state])) {
            $state = 'otro';
        }
        $kpi[$state]++;

        $statusLabel = $t['estado_nombre'] ?: 'Sin estado';
        $byStatus[$statusLabel] = ($byStatus[$statusLabel] ?? 0) + 1;
        $byChannel[$t['canal_nombre']] = ($byChannel[$t['canal_nombre']] ?? 0) + 1;
        $byActivity[$t['actividad_nombre']] = ($byActivity[$t['actividad_nombre']] ?? 0) + 1;
        $byDepartment[$t['departamento_nombre']] = ($byDepartment[$t['departamento_nombre']] ?? 0) + 1;

        $createDate = substr((string)$t['created_at'], 0, 10);
        if (isset($daily[$createDate])) {
            $daily[$createDate]['creados']++;
        }

        $endDateRaw = $t['fecha_resolucion'] ?: $t['updated_at'];
        $hours = null;
        if ($endDateRaw) {
            $start = strtotime((string)$t['created_at']);
            $end = strtotime((string)$endDateRaw);
            if ($start && $end && $end >= $start) {
                $hours = ($end - $start) / 3600;
            }
        }

        if ($state === 'resuelto' || $state === 'rechazado') {
            $closeDate = substr((string)$endDateRaw, 0, 10);
            if ($closeDate && isset($daily[$closeDate])) {
                $daily[$closeDate]['cerrados']++;
            }
            if ($hours !== null) {
                $resHours[] = $hours;
                $resolvedTotal++;
                if ($hours <= 48) {
                    $resolvedInSla++;
                }
            }
        }

        $creator = (int)$t['usuario_id'];
        $assignee = (int)$t['asignado_a'];

        if (!isset($userPerf[$creator])) {
            $userPerf[$creator] = ['id' => $creator, 'nombre' => $userMap[$creator] ?? ('Usuario #' . $creator), 'creados' => 0, 'asignados' => 0, 'resueltos' => 0, 'rechazados' => 0, 'backlog' => 0, 'horas' => []];
        }
        $userPerf[$creator]['creados']++;

        if ($assignee > 0) {
            if (!isset($userPerf[$assignee])) {
                $userPerf[$assignee] = ['id' => $assignee, 'nombre' => $userMap[$assignee] ?? ('Usuario #' . $assignee), 'creados' => 0, 'asignados' => 0, 'resueltos' => 0, 'rechazados' => 0, 'backlog' => 0, 'horas' => []];
            }
            $userPerf[$assignee]['asignados']++;
            if ($state === 'resuelto') {
                $userPerf[$assignee]['resueltos']++;
                if ($hours !== null) {
                    $userPerf[$assignee]['horas'][] = $hours;
                }
            } elseif ($state === 'rechazado') {
                $userPerf[$assignee]['rechazados']++;
            } elseif ($state !== 'otro') {
                $userPerf[$assignee]['backlog']++;
            }
        }

        $deptName = $t['departamento_nombre'];
        if (!isset($deptPerf[$deptName])) {
            $deptPerf[$deptName] = ['departamento' => $deptName, 'total' => 0, 'resueltos' => 0, 'rechazados' => 0, 'backlog' => 0];
        }
        $deptPerf[$deptName]['total']++;
        if ($state === 'resuelto') {
            $deptPerf[$deptName]['resueltos']++;
        } elseif ($state === 'rechazado') {
            $deptPerf[$deptName]['rechazados']++;
        } elseif ($state !== 'otro') {
            $deptPerf[$deptName]['backlog']++;
        }
    }

    if (!empty($resHours)) {
        $kpi['promedio_horas'] = round(array_sum($resHours) / count($resHours), 2);
    }
    if ($resolvedTotal > 0) {
        $kpi['sla_48'] = round(($resolvedInSla / $resolvedTotal) * 100, 1);
    }

    $trendLabels = array_keys($daily);
    $trendCreated = [];
    $trendClosed = [];
    foreach ($daily as $point) {
        $trendCreated[] = $point['creados'];
        $trendClosed[] = $point['cerrados'];
    }

    $userRows = [];
    foreach ($userPerf as $row) {
        $avg = 0.0;
        if (!empty($row['horas'])) {
            $avg = round(array_sum($row['horas']) / count($row['horas']), 2);
        }
        $userRows[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'creados' => $row['creados'],
            'asignados' => $row['asignados'],
            'resueltos' => $row['resueltos'],
            'rechazados' => $row['rechazados'],
            'backlog' => $row['backlog'],
            'promedio_horas' => $avg,
        ];
    }
    usort($userRows, function ($a, $b) {
        if ($b['resueltos'] === $a['resueltos']) {
            return $a['promedio_horas'] <=> $b['promedio_horas'];
        }
        return $b['resueltos'] <=> $a['resueltos'];
    });

    $deptRows = array_values($deptPerf);
    usort($deptRows, function ($a, $b) {
        return $b['total'] <=> $a['total'];
    });

    $statusRows = [];
    foreach ($byStatus as $k => $v) $statusRows[] = ['label' => $k, 'value' => $v];
    usort($statusRows, fn($a, $b) => $b['value'] <=> $a['value']);

    $channelRows = [];
    foreach ($byChannel as $k => $v) $channelRows[] = ['label' => $k, 'value' => $v];
    usort($channelRows, fn($a, $b) => $b['value'] <=> $a['value']);

    $activityRows = [];
    foreach ($byActivity as $k => $v) $activityRows[] = ['label' => $k, 'value' => $v];
    usort($activityRows, fn($a, $b) => $b['value'] <=> $a['value']);

    $departmentRows = [];
    foreach ($byDepartment as $k => $v) $departmentRows[] = ['label' => $k, 'value' => $v];
    usort($departmentRows, fn($a, $b) => $b['value'] <=> $a['value']);

    $response = [
        'success' => true,
        'scope' => $scope,
        'filtros' => [
            'periodo' => $periodo,
            'fecha_desde' => $from->format('Y-m-d'),
            'fecha_hasta' => $to->format('Y-m-d'),
            'departamento' => $isAdmin ? $selectedDept : (string)$userDept,
            'usuario' => $selectedUser,
        ],
        'catalogos' => [
            'departamentos' => $departments,
            'usuarios' => $users,
        ],
        'kpi' => $kpi,
        'charts' => [
            'trend' => ['labels' => $trendLabels, 'creados' => $trendCreated, 'cerrados' => $trendClosed],
            'estados' => $statusRows,
            'canales' => $channelRows,
            'actividades' => $activityRows,
            'departamentos' => $departmentRows,
        ],
        'tablas' => [
            'usuarios' => array_slice($userRows, 0, 25),
            'departamentos' => array_slice($deptRows, 0, 25),
            'tickets_recientes' => array_slice($tickets, 0, 50),
        ],
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al generar reportes',
        'detail' => DEBUG_MODE ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}
