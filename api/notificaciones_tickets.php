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
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
if ($limit <= 0) $limit = 5;

$notificaciones = [];
$total_no_leidos = 0;

function tiempo_relativo($dateString) {
    $fecha = new DateTime($dateString);
    $ahora = new DateTime();
    $diff = $ahora->diff($fecha);

    if ($diff->days == 0 && $diff->h == 0 && $diff->i < 60) {
        return $diff->i <= 1 ? 'Ahora' : 'Hace ' . $diff->i . ' min';
    }
    if ($diff->days == 0 && $diff->h < 24) {
        return 'Hace ' . $diff->h . ' hrs';
    }
    if ($diff->days == 1) {
        return 'Ayer';
    }
    return 'Hace ' . $diff->days . ' dias';
}

// 1) Tickets creados recientemente
$sql_nuevos = "SELECT
    t.id,
    t.codigo,
    t.created_at as fecha_evento,
    uc.nombre_completo as creador,
    CASE WHEN nl.id IS NOT NULL THEN 1 ELSE 0 END as leido
FROM tickets t
JOIN usuarios uc ON uc.id = t.usuario_id
LEFT JOIN notificaciones_leidas nl
    ON nl.tipo = 'ticket'
   AND nl.referencia_id = t.id
   AND nl.usuario_id = :user_id
WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

$paramsNuevos = [':user_id' => $user_id];

if ($user_rol === 'Jefe' && $user_departamento) {
    $sql_nuevos .= " AND t.departamento_id = :departamento_id";
    $paramsNuevos[':departamento_id'] = $user_departamento;
} elseif ($user_rol === 'Usuario') {
    $sql_nuevos .= " AND (t.usuario_id = :creator_id OR t.asignado_a = :asignado_id)";
    $paramsNuevos[':creator_id'] = $user_id;
    $paramsNuevos[':asignado_id'] = $user_id;
}

$sql_nuevos .= " ORDER BY t.created_at DESC LIMIT 30";

$stmt = $db->prepare($sql_nuevos);
$stmt->execute($paramsNuevos);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ticket) {
    $leido = (bool)$ticket['leido'];
    if (!$leido) $total_no_leidos++;

    $notificaciones[] = [
        'id' => $ticket['id'],
        'tipo' => 'nuevo',
        'tipo_notificacion' => 'ticket',
        'codigo' => $ticket['codigo'],
        'titulo' => 'Nuevo Ticket ' . $ticket['codigo'],
        'mensaje' => 'Creado por ' . $ticket['creador'],
        'tiempo' => tiempo_relativo($ticket['fecha_evento']),
        'fecha_evento' => $ticket['fecha_evento'],
        'leido' => $leido
    ];
}

// 2) Tickets asignados al usuario actual
$sql_asignados = "SELECT
    t.id,
    t.codigo,
    t.updated_at as fecha_evento,
    ua.nombre_completo as asignado_nombre,
    CASE WHEN nl.id IS NOT NULL THEN 1 ELSE 0 END as leido
FROM tickets t
LEFT JOIN usuarios ua ON ua.id = t.asignado_a
LEFT JOIN notificaciones_leidas nl
    ON nl.tipo = 'ticket_asignado'
   AND nl.referencia_id = t.id
   AND nl.usuario_id = :user_id_join
WHERE t.asignado_a = :user_id_asignado
  AND t.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
  AND (t.updated_at > t.created_at OR t.usuario_id <> :user_id_2)
ORDER BY t.updated_at DESC
LIMIT 30";

$stmt = $db->prepare($sql_asignados);
$stmt->execute([
    ':user_id_join' => $user_id,
    ':user_id_asignado' => $user_id,
    ':user_id_2' => $user_id
]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ticket) {
    $leido = (bool)$ticket['leido'];
    if (!$leido) $total_no_leidos++;

    $notificaciones[] = [
        'id' => $ticket['id'],
        'tipo' => 'asignado',
        'tipo_notificacion' => 'ticket_asignado',
        'codigo' => $ticket['codigo'],
        'titulo' => 'Ticket Asignado ' . $ticket['codigo'],
        'mensaje' => 'Asignado a ' . ($ticket['asignado_nombre'] ?: 'ti'),
        'tiempo' => tiempo_relativo($ticket['fecha_evento']),
        'fecha_evento' => $ticket['fecha_evento'],
        'leido' => $leido
    ];
}

// 3) Tickets aprobados (verificados por Jefe/Admin)
$sql_aprobados = "SELECT
    t.id,
    t.codigo,
    t.fecha_aprobacion as fecha_evento,
    ua.nombre_completo as aprobado_por_nombre,
    CASE WHEN nl.id IS NOT NULL THEN 1 ELSE 0 END as leido
FROM tickets t
LEFT JOIN usuarios ua ON ua.id = t.aprobado_por
LEFT JOIN notificaciones_leidas nl
    ON nl.tipo = 'ticket_aprobado'
   AND nl.referencia_id = t.id
   AND nl.usuario_id = :user_id
WHERE t.fecha_aprobacion IS NOT NULL
  AND t.fecha_aprobacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

$paramsAprob = [':user_id' => $user_id];
if ($user_rol === 'Jefe' && $user_departamento) {
    $sql_aprobados .= " AND t.departamento_id = :departamento_id";
    $paramsAprob[':departamento_id'] = $user_departamento;
} elseif ($user_rol === 'Usuario') {
    $sql_aprobados .= " AND (t.usuario_id = :creator_id OR t.asignado_a = :asignado_id)";
    $paramsAprob[':creator_id'] = $user_id;
    $paramsAprob[':asignado_id'] = $user_id;
}
$sql_aprobados .= " ORDER BY t.fecha_aprobacion DESC LIMIT 30";

$stmt = $db->prepare($sql_aprobados);
$stmt->execute($paramsAprob);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ticket) {
    $leido = (bool)$ticket['leido'];
    if (!$leido) $total_no_leidos++;

    $notificaciones[] = [
        'id' => $ticket['id'],
        'tipo' => 'aprobado',
        'tipo_notificacion' => 'ticket_aprobado',
        'codigo' => $ticket['codigo'],
        'titulo' => 'Ticket Aprobado ' . $ticket['codigo'],
        'mensaje' => 'Verificado por ' . ($ticket['aprobado_por_nombre'] ?: 'Jefe/Admin'),
        'tiempo' => tiempo_relativo($ticket['fecha_evento']),
        'fecha_evento' => $ticket['fecha_evento'],
        'leido' => $leido
    ];
}

// 4) Tickets rechazados (nota de rechazo)
$sql_rechazados = "SELECT
    c.id as evento_id,
    t.id as ticket_id,
    t.codigo,
    c.created_at as fecha_evento,
    ur.nombre_completo as rechazado_por_nombre,
    CASE WHEN nl.id IS NOT NULL THEN 1 ELSE 0 END as leido
FROM ticket_comentarios c
INNER JOIN tickets t ON t.id = c.ticket_id
LEFT JOIN usuarios ur ON ur.id = c.usuario_id
LEFT JOIN notificaciones_leidas nl
    ON nl.tipo = 'ticket_rechazado'
   AND nl.referencia_id = c.id
   AND nl.usuario_id = :user_id
WHERE c.tipo = 'nota_interna'
  AND c.mensaje LIKE '%TICKET RECHAZADO%'
  AND c.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

$paramsRech = [':user_id' => $user_id];
if ($user_rol === 'Jefe' && $user_departamento) {
    $sql_rechazados .= " AND t.departamento_id = :departamento_id";
    $paramsRech[':departamento_id'] = $user_departamento;
} elseif ($user_rol === 'Usuario') {
    $sql_rechazados .= " AND (t.usuario_id = :creator_id OR t.asignado_a = :asignado_id)";
    $paramsRech[':creator_id'] = $user_id;
    $paramsRech[':asignado_id'] = $user_id;
}
$sql_rechazados .= " ORDER BY c.created_at DESC LIMIT 30";

$stmt = $db->prepare($sql_rechazados);
$stmt->execute($paramsRech);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ticket) {
    $leido = (bool)$ticket['leido'];
    if (!$leido) $total_no_leidos++;

    $notificaciones[] = [
        'id' => $ticket['ticket_id'],
        'referencia_evento' => $ticket['evento_id'],
        'tipo' => 'rechazado',
        'tipo_notificacion' => 'ticket_rechazado',
        'codigo' => $ticket['codigo'],
        'titulo' => 'Ticket Rechazado ' . $ticket['codigo'],
        'mensaje' => 'Rechazado por ' . ($ticket['rechazado_por_nombre'] ?: 'Jefe/Admin'),
        'tiempo' => tiempo_relativo($ticket['fecha_evento']),
        'fecha_evento' => $ticket['fecha_evento'],
        'leido' => $leido
    ];
}

usort($notificaciones, function($a, $b) {
    return strtotime($b['fecha_evento']) <=> strtotime($a['fecha_evento']);
});

$notificaciones = array_slice($notificaciones, 0, $limit);

ob_end_clean();

echo json_encode([
    'notificaciones' => $notificaciones,
    'total' => $total_no_leidos
], JSON_UNESCAPED_UNICODE);
