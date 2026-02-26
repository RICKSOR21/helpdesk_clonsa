<?php
require_once 'config/session.php';
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

// Funcion para formatear tamano de archivos
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

$database = new Database();
$db = $database->getConnection();

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
$user_id = $_SESSION['user_id'];

$SESSION_TIMEOUT_JS = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 120;
$SESSION_POPUP_TIMEOUT_JS = defined('SESSION_POPUP_TIMEOUT') ? SESSION_POPUP_TIMEOUT : 900;

date_default_timezone_set('America/Lima');
$hora = date('H');
$saludo = ($hora >= 5 && $hora < 12) ? 'Buenos dias' : (($hora >= 12 && $hora < 19) ? 'Buenas tardes' : 'Buenas noches');
$primer_nombre = explode(' ', $user_name)[0];

// Obtener ID del ticket
$ticket_id = $_GET['id'] ?? null;
if (!$ticket_id) {
    header('Location: tickets.php');
    exit;
}

// Obtener datos del ticket
$stmt = $db->prepare("
    SELECT t.*,
           d.nombre as departamento_nombre, d.abreviatura as departamento_abrev,
           e.nombre as estado_nombre, e.color as estado_color,
           p.nombre as prioridad_nombre, p.color as prioridad_color,
           a.nombre as actividad_nombre,
           tf.nombre as tipo_falla_nombre,
           ca.nombre as canal_nombre,
           ub.nombre as ubicacion_nombre,
           eq.nombre as equipo_nombre,
           ce.codigo as codigo_equipo_codigo,
           creador.nombre_completo as creador_nombre,
           asignado.nombre_completo as asignado_nombre,
           aprobador.nombre_completo as aprobador_nombre
    FROM tickets t
    LEFT JOIN departamentos d ON t.departamento_id = d.id
    LEFT JOIN estados e ON t.estado_id = e.id
    LEFT JOIN prioridades p ON t.prioridad_id = p.id
    LEFT JOIN actividades a ON t.actividad_id = a.id
    LEFT JOIN tipos_falla tf ON t.tipo_falla_id = tf.id
    LEFT JOIN canales_atencion ca ON t.canal_atencion_id = ca.id
    LEFT JOIN ubicaciones ub ON t.ubicacion_id = ub.id
    LEFT JOIN equipos eq ON t.equipo_id = eq.id
    LEFT JOIN codigos_equipo ce ON t.codigo_equipo_id = ce.id
    LEFT JOIN usuarios creador ON t.usuario_id = creador.id
    LEFT JOIN usuarios asignado ON t.asignado_a = asignado.id
    LEFT JOIN usuarios aprobador ON t.aprobado_por = aprobador.id
    WHERE t.id = ?
");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    header('Location: tickets.php');
    exit;
}

// Ultima actualizacion realizada por el usuario creador en el seguimiento
// (comentarios + cambios de progreso registrados en historial).
$stmtUserUpdate = $db->prepare("
    SELECT MAX(fecha_evento) as ultima_actualizacion_usuario
    FROM (
        SELECT c.created_at as fecha_evento
        FROM ticket_comentarios c
        WHERE c.ticket_id = :ticket_id_1 AND c.usuario_id = :usuario_id_1
        UNION ALL
        SELECT h.created_at as fecha_evento
        FROM historial h
        WHERE h.ticket_id = :ticket_id_2 AND h.usuario_id = :usuario_id_2 AND h.campo_modificado = 'progreso'
    ) as eventos_usuario
");
$stmtUserUpdate->execute([
    ':ticket_id_1' => $ticket_id,
    ':usuario_id_1' => $ticket['usuario_id'],
    ':ticket_id_2' => $ticket_id,
    ':usuario_id_2' => $ticket['usuario_id']
]);
$ultimaUserRow = $stmtUserUpdate->fetch(PDO::FETCH_ASSOC);
$ultima_actualizacion_usuario = $ultimaUserRow['ultima_actualizacion_usuario'] ?? null;

if (!$ultima_actualizacion_usuario) {
    $ultima_actualizacion_usuario = $ticket['created_at'];
}

// Obtener archivos adjuntos del ticket
$stmt = $db->prepare("SELECT * FROM ticket_archivos WHERE ticket_id = ? ORDER BY created_at DESC");
$stmt->execute([$ticket_id]);
$adjuntos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pendiente_aprobacion = isset($ticket['pendiente_aprobacion']) ? $ticket['pendiente_aprobacion'] : 0;
// Estados: 1=Abierto, 2=En Atencion, 4=Resuelto, 5=Rechazado
$ticket_resuelto = ($ticket['estado_id'] == 4 && $pendiente_aprobacion == 0); // Aprobado/Verificado
$ticket_resuelto_pendiente = ($ticket['estado_id'] == 4 && $pendiente_aprobacion == 1); // Pendiente verificacion
$ticket_rechazado = ($ticket['estado_id'] == 5); // Rechazado por Jefe/Admin
$ticket_cerrado = $ticket_resuelto; // Solo cerrado si fue aprobado
$puede_aprobar = ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe');
$puede_editar = !$ticket_resuelto && !$ticket_resuelto_pendiente && ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe' || $ticket['usuario_id'] == $user_id || $ticket['asignado_a'] == $user_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIRA - <?php echo htmlspecialchars($ticket['codigo']); ?></title>
  <link rel="stylesheet" href="template/vendors/feather/feather.css">
  <link rel="stylesheet" href="template/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="template/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="template/vendors/typicons/typicons.css">
  <link rel="stylesheet" href="template/vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="template/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="template/css/vertical-layout-light/style.css">
  <link rel="shortcut icon" href="template/images/favicon.svg" />
  <style>
    .count-indicator { position: relative !important; }
    .count-indicator .badge-notif {
      position: absolute !important; top: 5px !important; right: 2px !important;
      background-color: #dc3545 !important; color: #ffffff !important;
      border-radius: 10px !important; min-width: 18px !important; height: 18px !important;
      padding: 0 5px !important; font-size: 11px !important; font-weight: 600 !important;
      display: none !important; align-items: center !important; justify-content: center !important;
      line-height: 18px !important; border: 2px solid #f4f5f7 !important;
      box-shadow: 0 1px 4px rgba(0,0,0,0.3) !important; z-index: 999 !important;
    }
    .count-indicator .badge-notif.show { display: flex !important; }
    .sidebar-badge {
      background: #dc3545; color: #fff; border-radius: 10px; min-width: 18px; height: 18px;
      padding: 0 5px; font-size: 11px; font-weight: 600; display: none; align-items: center;
      justify-content: center; line-height: 18px; margin-left: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.25);
    }
    .sidebar-badge.show { display: inline-flex; }
    .notif-item { padding: 10px 14px !important; border-bottom: 1px solid #eee; transition: all 0.2s ease; text-decoration: none !important; }
    .notif-item:hover { background-color: #f5f7fa !important; }
    .notif-item.unread { background-color: #eef4ff !important; }
    .notif-row { display: flex; align-items: center; gap: 10px; }
    .status-indicator { width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .status-indicator .unread-dot { width: 9px; height: 9px; background: #dc3545; border-radius: 50%; box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15); }
    .notif-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .notif-icon i { font-size: 18px; }
    .notif-content { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
    .notif-title { font-size: 12px; font-weight: 500; color: #333; margin: 0; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .notif-item.unread .notif-title { font-weight: 600; color: #1a1a1a; }
    .notif-subtitle { font-size: 11px; color: #666; margin: 0; line-height: 1.3; }
    .notif-time { font-size: 10px; color: #1F3BB3; display: flex; align-items: center; gap: 2px; margin-top: 1px; }

    .form-section { background: #fff; border-radius: 10px; padding: 25px; margin-bottom: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
    .form-section-title { font-size: 16px; font-weight: 600; color: #1F3BB3; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; display: flex; align-items: center; gap: 10px; }
    .form-section-title i { font-size: 20px; }
    .form-group label { font-size: 13px; font-weight: 500; color: #495057; margin-bottom: 6px; }
    .form-group label i { margin-right: 5px; }
    .form-control, .form-select { border-radius: 8px; border: 1px solid #e0e0e0; padding: 10px 15px; font-size: 14px; transition: all 0.2s; background-color: #fff !important; }
    .form-control:focus, .form-select:focus { border-color: #1F3BB3; box-shadow: 0 0 0 0.2rem rgba(31, 59, 179, 0.15); }
    .form-control::placeholder { color: #999 !important; }
    #descripcion {
      min-height: 90px;
      resize: vertical;
      line-height: 1.5;
    }
    select.form-control, select.form-select { color: #212529 !important; -webkit-text-fill-color: #212529 !important; opacity: 1 !important; }
    .form-select option { color: #212529 !important; }
    .form-select option[value=""] { color: #999 !important; }
    .required-field::after { content: " *"; color: #dc3545; }

    /* Select2 custom styles */
    .select2-container--bootstrap-5 .select2-selection { border-radius: 8px !important; border: 1px solid #e0e0e0 !important; min-height: 38px !important; height: 38px !important; padding: 4px 10px !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered { color: #212529 !important; font-size: 14px !important; line-height: 28px !important; -webkit-text-fill-color: #212529 !important; padding-left: 5px !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow { height: 36px !important; }
    .select2-container--bootstrap-5 .select2-selection--single .select2-selection__placeholder { color: #999 !important; font-size: 14px !important; }
    .select2-container--bootstrap-5.select2-container--focus .select2-selection { border-color: #1F3BB3 !important; box-shadow: 0 0 0 0.2rem rgba(31, 59, 179, 0.15) !important; }
    .select2-container--bootstrap-5 .select2-dropdown { border-radius: 8px !important; border: 1px solid #e0e0e0 !important; box-shadow: 0 5px 20px rgba(0,0,0,0.1) !important; }
    .select2-container--bootstrap-5 .select2-results__option { font-size: 14px !important; padding: 8px 12px !important; color: #212529 !important; }
    .select2-container--bootstrap-5 .select2-results__option--highlighted { background-color: #1F3BB3 !important; color: #fff !important; }
    .select2-container--bootstrap-5 .select2-search--dropdown .select2-search__field { border-radius: 6px !important; border: 1px solid #e0e0e0 !important; padding: 8px 12px !important; font-size: 14px !important; color: #212529 !important; }
    .select2-container--bootstrap-5 .select2-search--dropdown { padding: 8px !important; }

    .btn-submit { background: linear-gradient(135deg, #1F3BB3 0%, #4a6fd1 100%); border: none; padding: 12px 30px; font-size: 15px; font-weight: 500; border-radius: 8px; transition: all 0.3s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(31, 59, 179, 0.3); }
    .btn-cancel { background: #f0f0f0; color: #666; border: none; padding: 12px 30px; font-size: 15px; font-weight: 500; border-radius: 8px; }
    .btn-cancel:hover { background: #e0e0e0; color: #333; }

    .ticket-header { background: linear-gradient(135deg, #1F3BB3 0%, #4a6fd1 100%); color: white; border-radius: 10px; padding: 20px 25px; margin-bottom: 20px; }
    .ticket-code { font-size: 24px; font-weight: 700; font-family: 'Courier New', monospace; letter-spacing: 2px; }
    .ticket-meta {
      font-size: 13px;
      opacity: 0.9;
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: nowrap;
      white-space: nowrap;
      overflow-x: auto;
      overflow-y: hidden;
      scrollbar-width: none;
    }
    .ticket-meta::-webkit-scrollbar { display: none; }
    .ticket-meta .meta-item {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      flex: 0 0 auto;
      white-space: nowrap;
    }
    .ticket-meta .meta-sep {
      opacity: 0.75;
      flex: 0 0 auto;
    }

    /* Info Badges */
    .info-badge { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 25px; font-size: 12px; font-weight: 600; color: white; background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3); }
    .info-badge i { font-size: 14px; }

    .file-item { display: flex; align-items: center; padding: 10px 15px; background: #f8f9fa; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e9ecef; }
    .file-item .file-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 12px; font-size: 20px; }
    .file-item .file-icon.image { background: #e3f2fd; color: #1976d2; }
    .file-item .file-icon.pdf { background: #ffebee; color: #c62828; }
    .file-item .file-icon.doc { background: #e8f5e9; color: #2e7d32; }
    .file-item .file-icon.excel { background: #e8f5e9; color: #1b5e20; }
    .file-item .file-icon.other { background: #f5f5f5; color: #616161; }
    .file-item .file-info { flex: 1; min-width: 0; }
    .file-item .file-name { font-size: 13px; font-weight: 500; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .file-item .file-size { font-size: 11px; color: #666; }
    .file-item .file-download { width: 30px; height: 30px; border-radius: 50%; border: none; background: #e3f2fd; color: #1976d2; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none; }
    .file-item .file-download:hover { background: #1976d2; color: #fff; }
    .file-item .file-remove { width: 30px; height: 30px; border-radius: 50%; border: none; background: #ffebee; color: #dc3545; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; margin-left: 8px; }
    .file-item .file-remove:hover { background: #dc3545; color: #fff; }

    /* Modal styles */
    .modal-process { background: rgba(0,0,0,0.7); }
    .modal-process .modal-content { border: none; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    .modal-process .modal-body { padding: 40px; text-align: center; }
    .process-loader { width: 80px; height: 80px; margin: 0 auto 25px; position: relative; }
    .process-loader .spinner { width: 100%; height: 100%; border: 4px solid #e9ecef; border-top-color: #1F3BB3; border-radius: 50%; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .process-title { font-size: 20px; font-weight: 600; color: #333; margin-bottom: 10px; }
    .process-subtitle { font-size: 14px; color: #666; margin-bottom: 25px; }

    .success-checkmark { width: 100px; height: 100px; margin: 0 auto 25px; position: relative; }
    .success-checkmark .check-icon { width: 100px; height: 100px; position: relative; border-radius: 50%; box-sizing: content-box; border: 4px solid #4CAF50; }
    .success-checkmark .icon-line { height: 5px; background-color: #4CAF50; display: block; border-radius: 2px; position: absolute; z-index: 10; }
    .success-checkmark .icon-line.line-tip { top: 46px; left: 16px; width: 25px; transform: rotate(45deg); animation: icon-line-tip 0.75s; }
    .success-checkmark .icon-line.line-long { top: 38px; left: 28px; width: 47px; transform: rotate(-45deg); animation: icon-line-long 0.75s; }
    .success-checkmark .icon-circle { top: -4px; left: -4px; z-index: 10; width: 100px; height: 100px; border-radius: 50%; position: absolute; box-sizing: content-box; border: 4px solid rgba(76, 175, 80, .5); }
    .success-checkmark .icon-fix { top: 8px; width: 5px; left: 26px; z-index: 1; height: 85px; position: absolute; transform: rotate(-45deg); background-color: #fff; }
    @keyframes icon-line-tip { 0% { width: 0; left: 1px; top: 19px; } 54% { width: 0; left: 1px; top: 19px; } 70% { width: 50px; left: -8px; top: 37px; } 84% { width: 17px; left: 21px; top: 48px; } 100% { width: 25px; left: 16px; top: 46px; } }
    @keyframes icon-line-long { 0% { width: 0; right: 46px; top: 54px; } 65% { width: 0; right: 46px; top: 54px; } 84% { width: 55px; right: 0px; top: 35px; } 100% { width: 47px; right: 8px; top: 38px; } }

    .error-icon { width: 100px; height: 100px; margin: 0 auto 25px; background: #fee; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .error-icon i { font-size: 50px; color: #dc3545; }
    .warning-icon { width: 100px; height: 100px; margin: 0 auto 25px; background: #fff3cd; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .warning-icon i { font-size: 50px; color: #fd7e14; }
    .modal-btn-danger { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border: none; padding: 12px 40px; font-size: 15px; font-weight: 500; border-radius: 8px; color: #fff; transition: all 0.3s; text-decoration: none; display: inline-block; }
    .modal-btn-danger:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(220, 53, 69, 0.3); color: #fff; }

    .modal-btn-primary { background: linear-gradient(135deg, #1F3BB3 0%, #4a6fd1 100%); border: none; padding: 12px 40px; font-size: 15px; font-weight: 500; border-radius: 8px; color: #fff; transition: all 0.3s; text-decoration: none; display: inline-block; }
    .modal-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(31, 59, 179, 0.3); color: #fff; }
    .modal-btn-secondary { background: #f0f0f0; border: none; padding: 12px 40px; font-size: 15px; font-weight: 500; border-radius: 8px; color: #666; }
    .modal-btn-secondary:hover { background: #e0e0e0; color: #333; }

    /* Chat/Seguimiento Styles */
    .chat-section { background: #fff; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05); overflow: hidden; }
    .chat-header { background: linear-gradient(135deg, #1F3BB3 0%, #4a6fd1 100%); color: white; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
    .chat-header-title { font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .chat-header-title i { font-size: 20px; }
    .chat-messages { height: 450px; overflow-y: auto; padding: 20px; background: #f8f9fa; }
    .chat-messages::-webkit-scrollbar { width: 6px; }
    .chat-messages::-webkit-scrollbar-track { background: #f1f1f1; }
    .chat-messages::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 3px; }
    .chat-messages::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

    .chat-message { display: flex; margin-bottom: 20px; gap: 12px; }
    .chat-message.own { flex-direction: row-reverse; }
    .chat-message .avatar { width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; color: white; }
    .chat-message .avatar img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
    .chat-message .message-content { max-width: 70%; }
    .chat-message .message-bubble { padding: 12px 16px; border-radius: 18px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
    .chat-message.own .message-bubble { background: linear-gradient(135deg, #1F3BB3 0%, #4a6fd1 100%); color: white; }
    .chat-message .message-header { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
    .chat-message .message-author { font-weight: 600; font-size: 13px; color: #333; }
    .chat-message.own .message-author { color: rgba(255,255,255,0.9); }
    .chat-message .message-time { font-size: 11px; color: #999; }
    .chat-message.own .message-time { color: rgba(255,255,255,0.7); }
    .chat-message .message-text { font-size: 14px; line-height: 1.5; word-wrap: break-word; }
    .chat-message .message-type { font-size: 10px; padding: 2px 8px; border-radius: 10px; display: inline-block; margin-bottom: 5px; }
    .chat-message .message-type.solucion { background: #d4edda; color: #155724; }
    .chat-message .message-type.nota_interna { background: #fff3cd; color: #856404; }
    .chat-message .message-type.transferido { background: #ffe8cc; color: #b45309; }
    .chat-message .message-type.comentario { background: #e2e3e5; color: #383d41; }
    .chat-message .message-role-badge {
      font-size: 10px;
      padding: 2px 8px;
      border-radius: 10px;
      display: inline-block;
      margin-bottom: 5px;
      margin-left: 6px;
      background: #d4edda;
      color: #155724;
      font-weight: 600;
    }
    .chat-message.verification .message-bubble {
      border: 1px solid #c3e6cb;
      background: #f7fff9;
      color: #1f2d3d;
    }
    .chat-message.verification .message-author { color: #155724; }
    .chat-message.verification .message-time { color: #5b6b7a; }
    .chat-message.transfer .message-bubble {
      border: 1px solid #f8c68a;
      background: #fff8ef;
      color: #3f3f46;
    }
    .chat-message.transfer .message-author { color: #b45309; }
    .chat-message.transfer .message-time { color: #78716c; }

    .chat-message .message-attachments { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 8px; }
    .chat-message .attachment-item { display: flex; align-items: center; gap: 6px; padding: 6px 10px; background: rgba(0,0,0,0.05); border-radius: 8px; font-size: 12px; text-decoration: none; color: inherit; transition: all 0.2s; }
    .chat-message.own .attachment-item { background: rgba(255,255,255,0.2); color: white; }
    .chat-message .attachment-item:hover { background: rgba(0,0,0,0.1); }
    .chat-message.own .attachment-item:hover { background: rgba(255,255,255,0.3); }
    .chat-message .attachment-item i { font-size: 16px; }
    .chat-message .attachment-image { max-width: 200px; max-height: 150px; border-radius: 8px; margin-top: 8px; cursor: pointer; transition: transform 0.2s; }
    .chat-message .attachment-image:hover { transform: scale(1.02); }
    .chat-message .message-delete { position: absolute; top: -8px; right: -8px; width: 24px; height: 24px; border-radius: 50%; background: #dc3545; color: white; border: 2px solid #fff; font-size: 12px; cursor: pointer; display: none; align-items: center; justify-content: center; transition: all 0.2s; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    .chat-message .message-delete:hover { transform: scale(1.1); background: #c82333; }
    .chat-message:hover .message-delete { display: flex; }
    .chat-message .message-content { position: relative; }

    .chat-input-area { padding: 15px 20px; background: #fff; border-top: 1px solid #eee; }
    .chat-input-wrapper { display: flex; gap: 10px; align-items: flex-end; }
    .chat-input-container { flex: 1; display: flex; flex-direction: column; gap: 8px; }
    .chat-textarea { border: 1px solid #e0e0e0; border-radius: 20px; padding: 12px 18px; font-size: 14px; resize: none; min-height: 44px; max-height: 120px; transition: all 0.2s; }
    .chat-textarea:focus { border-color: #1F3BB3; box-shadow: 0 0 0 3px rgba(31, 59, 179, 0.1); outline: none; }
    .chat-textarea::placeholder { color: #999; }

    .chat-actions { display: flex; gap: 8px; align-items: center; }
    .chat-btn-attach { width: 44px; height: 44px; border-radius: 50%; border: 1px solid #e0e0e0; background: #fff; color: #666; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .chat-btn-attach:hover { background: #f0f0f0; color: #1F3BB3; border-color: #1F3BB3; }
    .chat-btn-send { width: 44px; height: 44px; border-radius: 50%; border: none; background: linear-gradient(135deg, #1F3BB3 0%, #4a6fd1 100%); color: white; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .chat-btn-send:hover { transform: scale(1.05); box-shadow: 0 3px 10px rgba(31, 59, 179, 0.3); }
    .chat-btn-send:disabled { background: #ccc; cursor: not-allowed; transform: none; box-shadow: none; }

    .chat-file-preview { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px 0; }
    .chat-file-item { display: flex; align-items: center; gap: 6px; padding: 6px 10px; background: #e9ecef; border-radius: 8px; font-size: 12px; }
    .chat-file-item .remove-file { cursor: pointer; color: #dc3545; margin-left: 4px; }
    .chat-file-item .remove-file:hover { color: #a71d2a; }

    .chat-type-selector { display: flex; gap: 8px; margin-bottom: 8px; }
    .chat-type-btn { padding: 5px 12px; border-radius: 15px; border: 1px solid #e0e0e0; background: #fff; font-size: 12px; cursor: pointer; transition: all 0.2s; }
    .chat-type-btn:hover { border-color: #1F3BB3; }
    .chat-type-btn.active { background: #1F3BB3; color: white; border-color: #1F3BB3; }
    .chat-type-btn.active.solucion { background: #28a745; border-color: #28a745; }
    .chat-type-btn.active.nota_interna { background: #ffc107; border-color: #ffc107; color: #333; }

    .chat-empty { text-align: center; padding: 60px 20px; color: #999; }
    .chat-empty i { font-size: 60px; margin-bottom: 15px; opacity: 0.5; }
    .chat-empty h5 { font-size: 16px; margin-bottom: 5px; color: #666; }
    .chat-empty p { font-size: 13px; }

    .chat-date-separator { text-align: center; margin: 20px 0; position: relative; }
    .chat-date-separator span { background: #f8f9fa; padding: 0 15px; font-size: 12px; color: #999; position: relative; z-index: 1; }
    .chat-date-separator::before { content: ''; position: absolute; left: 0; right: 0; top: 50%; height: 1px; background: #e0e0e0; }

    /* Lightbox for images */
    .lightbox-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 9999; display: none; align-items: center; justify-content: center; }
    .lightbox-overlay.active { display: flex; }
    .lightbox-image { max-width: 90%; max-height: 90%; border-radius: 8px; }
    .lightbox-close { position: absolute; top: 20px; right: 20px; color: white; font-size: 30px; cursor: pointer; }

    /* Progress Card - Temperature Style */
    .progress-card { background: #fff; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05); overflow: hidden; }
    .progress-card-header { background: linear-gradient(135deg, #1F3BB3 0%, #4a6fd1 100%); color: white; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; }
    .progress-card-title { font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
    .progress-card-title i { font-size: 20px; }
    .progress-percentage { font-size: 28px; font-weight: 700; font-family: 'Courier New', monospace; }
    .progress-card-body { padding: 25px; }

    .temperature-bar-container { margin-bottom: 20px; }
    .temperature-scale { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 11px; color: #666; font-weight: 500; }
    .temperature-bar { position: relative; height: 30px; background: linear-gradient(90deg, #e9ecef 0%, #e9ecef 100%); border-radius: 15px; overflow: hidden; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
    .temperature-fill { height: 100%; border-radius: 15px; transition: width 0.5s ease, background 0.5s ease; position: relative; z-index: 2; }
    .temperature-fill::after { content: ''; position: absolute; right: 0; top: 50%; transform: translateY(-50%); width: 8px; height: 8px; background: white; border-radius: 50%; box-shadow: 0 0 5px rgba(0,0,0,0.3); }
    .temperature-markers { position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 1; }
    .temperature-markers .marker { position: absolute; top: 0; bottom: 0; width: 2px; background: rgba(255,255,255,0.5); }

    /* Dynamic temperature colors */
    .temp-cold { background: linear-gradient(90deg, #dc3545 0%, #dc3545 100%); }
    .temp-cool { background: linear-gradient(90deg, #dc3545 0%, #fd7e14 100%); }
    .temp-warm { background: linear-gradient(90deg, #dc3545 0%, #fd7e14 40%, #ffc107 100%); }
    .temp-hot { background: linear-gradient(90deg, #dc3545 0%, #fd7e14 30%, #ffc107 60%, #28a745 100%); }
    .temp-complete { background: linear-gradient(90deg, #28a745 0%, #20c997 100%); }


    .timeline-item { display: flex; align-items: flex-start; gap: 12px; padding: 8px 0; border-bottom: 1px dashed #eee; }
    .timeline-item:last-child { border-bottom: none; }
    .timeline-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 4px; }
    .timeline-content { flex: 1; min-width: 0; }
    .timeline-text { font-size: 13px; color: #333; display: flex; align-items: center; gap: 8px; }
    .timeline-text .badge-change { font-size: 11px; padding: 2px 8px; border-radius: 10px; font-weight: 600; }
    .timeline-text .badge-up { background: #d4edda; color: #155724; }
    .timeline-text .badge-down { background: #f8d7da; color: #721c24; }
    .timeline-date { font-size: 11px; color: #999; margin-top: 2px; display: flex; align-items: center; gap: 5px; }
    .timeline-empty { text-align: center; padding: 20px; color: #999; font-size: 13px; }
    .timeline-empty i { font-size: 30px; display: block; margin-bottom: 8px; opacity: 0.5; }

    /* Progress Timeline Panel (sidebar) */
    .progress-timeline-panel { max-height: 250px; overflow-y: auto; }
    .progress-timeline-panel::-webkit-scrollbar { width: 4px; }
    .progress-timeline-panel::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 2px; }
    .progress-timeline-panel::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 2px; }
    .progress-timeline-panel::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }

    /* Chat Toolbar with Progress Controls */
    .chat-toolbar { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 10px; }
    .chat-progress-controls { display: flex; align-items: center; gap: 8px; }
    .progress-input-group { display: flex; align-items: center; gap: 5px; position: relative; }
    .progress-label { font-size: 12px; font-weight: 600; color: #666; white-space: nowrap; }
    .progress-input { width: 60px; padding: 5px 20px 5px 8px; border-radius: 6px; border: 1px solid #e0e0e0; font-size: 14px; font-weight: 600; text-align: center; transition: all 0.2s; }
    .progress-input:focus { border-color: #1F3BB3; box-shadow: 0 0 0 2px rgba(31, 59, 179, 0.15); outline: none; }
    .progress-input::-webkit-inner-spin-button, .progress-input::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .progress-input-suffix { position: absolute; right: 6px; font-size: 12px; font-weight: 600; color: #666; pointer-events: none; }
    .btn-update-progress { padding: 5px 10px; font-size: 14px; border-radius: 6px; transition: all 0.2s; }
    .btn-update-progress:not(:disabled) { animation: fadeIn 0.3s ease; }
    .btn-close-ticket { padding: 5px 12px; font-size: 12px; border-radius: 6px; white-space: nowrap; transition: all 0.2s; }
    .btn-close-ticket:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(220, 53, 69, 0.3); }
    @keyframes fadeIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes slideIn { from { opacity: 0; transform: translateX(50px); } to { opacity: 1; transform: translateX(0); } }

    /* Ticket Cerrado/Pendiente Aprobacion/Rechazado Styles */
    .ticket-closed-banner { background: linear-gradient(135deg, #198754 0%, #20c997 100%); color: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3); }
    .ticket-pending-banner { background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%); color: #333; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 15px rgba(253, 126, 20, 0.3); }
    .ticket-pending-banner .banner-content { display: flex; align-items: center; gap: 12px; }
    .ticket-pending-banner .banner-icon {
      width: 50px;
      height: 50px;
      background: rgba(255,255,255,0.25);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 50px;
    }
    .ticket-pending-banner .banner-text h4 { color: #333; }
    .ticket-pending-banner .banner-text p { color: #555; }
    .ticket-rejected-banner { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3); }
    .ticket-rejected-banner .banner-content { display: flex; align-items: center; gap: 12px; }
    .ticket-rejected-banner .banner-icon { width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .ticket-rejected-banner .banner-icon i { font-size: 28px; }
    .ticket-rejected-banner .banner-text h4 { margin: 0; font-size: 18px; font-weight: 600; }
    .ticket-rejected-banner .banner-text p { margin: 0; font-size: 13px; opacity: 0.9; }

    /* Indicador parpadeante */
    .pulse-dot {
      width: 12px;
      height: 12px;
      background: #dc3545;
      border-radius: 50%;
      display: inline-block;
      margin-right: 8px;
      position: relative;
      z-index: 1;
    }
    .pulse-dot::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      transform: translate(-50%, -50%);
      background: rgba(220, 53, 69, 0.45);
      animation: pulseRing 1.5s ease-out infinite;
      z-index: -1;
    }
    @keyframes pulseRing {
      0% { transform: translate(-50%, -50%) scale(1); opacity: 0.9; }
      70% { transform: translate(-50%, -50%) scale(2.2); opacity: 0; }
      100% { transform: translate(-50%, -50%) scale(2.2); opacity: 0; }
    }
    .check-approved { color: #198754; font-size: 18px; margin-right: 5px; }

    /* Botones de aprobacion */
    .btn-aprobar { background: linear-gradient(135deg, #198754 0%, #20c997 100%); border: none; color: white; padding: 8px 20px; border-radius: 8px; font-weight: 500; transition: all 0.3s; }
    .btn-aprobar:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(25, 135, 84, 0.3); color: white; }
    .btn-rechazar { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border: none; color: white; padding: 8px 20px; border-radius: 8px; font-weight: 500; transition: all 0.3s; }
    .btn-rechazar:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3); color: white; }
    #comentarioVerificacion {
      min-height: 130px;
      resize: vertical;
    }
    .verificacion-file-wrap {
      background: #f8f9fa;
      border: 1px dashed #ced4da;
      border-radius: 12px;
      padding: 10px 12px;
      margin-top: 10px;
    }
    .verificacion-file-actions {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
    }
    .verificacion-file-name {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 12px;
      background: #e9ecef;
      color: #495057;
      border-radius: 999px;
      padding: 4px 10px;
      margin-top: 8px;
    }
    .verificacion-file-remove {
      border: none;
      background: transparent;
      color: #dc3545;
      padding: 0;
      line-height: 1;
      cursor: pointer;
      font-size: 14px;
    }
    .ticket-closed-banner .banner-content { display: flex; align-items: center; gap: 12px; }
    .ticket-closed-banner .banner-icon { width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .ticket-closed-banner .banner-icon i { font-size: 28px; }
    .ticket-closed-banner .banner-text h4 { margin: 0; font-size: 18px; font-weight: 600; }
    .ticket-closed-banner .banner-text p { margin: 0; font-size: 13px; opacity: 0.9; }
    .ticket-closed-badge { background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; }
    .chat-closed-overlay { background: rgba(248, 249, 250, 0.95); padding: 30px; text-align: center; border-radius: 0 0 10px 10px; }
    .chat-closed-overlay i { font-size: 50px; color: #198754; margin-bottom: 10px; }
    .chat-closed-overlay h5 { color: #333; margin-bottom: 5px; }
    .chat-closed-overlay p { color: #666; margin: 0; font-size: 13px; }
  </style>
</head>
<body class="authenticated">
  <div class="container-scroller">
    <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
      <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
        <div class="me-3">
          <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
            <span class="icon-menu"></span>
          </button>
        </div>
        <div>
          <a class="navbar-brand brand-logo" href="dashboard.php"><img src="template/images/logo.svg" alt="logo" /></a>
          <a class="navbar-brand brand-logo-mini" href="dashboard.php"><img src="template/images/logo-mini.svg" alt="logo" /></a>
        </div>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-top">
        <ul class="navbar-nav">
          <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
            <h1 class="welcome-text"><?php echo $saludo; ?>, <span class="text-black fw-bold"><?php echo htmlspecialchars($primer_nombre); ?></span></h1>
            <h3 class="welcome-sub-text">Detalle del Ticket <?php echo htmlspecialchars($ticket['codigo']); ?></h3>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item d-none d-lg-block">
            <span class="nav-link dropdown-bordered" style="cursor: default; background-color: #e9ecef; opacity: 0.9; pointer-events: none;">
              <i class="mdi mdi-office-building me-1"></i> <?php echo htmlspecialchars($ticket['departamento_nombre'] ?? 'General'); ?>
            </span>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link count-indicator" id="comunicadosDropdown" href="#" data-bs-toggle="dropdown">
              <i class="icon-mail icon-lg"></i>
              <span class="count count-comunicados badge-notif"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="comunicadosDropdown" style="width: 340px;">
              <div class="dropdown-item py-2 border-bottom d-flex justify-content-between align-items-center" style="cursor: default; background: #f8f9fa;">
                <div class="d-flex align-items-center">
                  <i class="mdi mdi-email-outline text-primary me-2" style="font-size: 18px;"></i>
                  <span class="font-weight-bold" style="font-size: 13px;">Comunicados</span>
                </div>
                <a href="comunicados.php" class="btn btn-sm btn-primary" style="font-size: 10px; padding: 3px 10px;">Ver todos <i class="mdi mdi-arrow-right"></i></a>
              </div>
              <div id="comunicadosContainer" style="max-height: 280px; overflow-y: auto;">
                <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
              </div>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link count-indicator" id="ticketsDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="icon-bell"></i>
              <span class="count count-tickets badge-notif"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="ticketsDropdown" style="width: 340px;">
              <div class="dropdown-item py-2 border-bottom d-flex justify-content-between align-items-center" style="cursor: default; background: #f8f9fa;">
                <div class="d-flex align-items-center">
                  <i class="mdi mdi-bell-outline text-primary me-2" style="font-size: 18px;"></i>
                  <span class="font-weight-bold" style="font-size: 13px;">Notificaciones</span>
                </div>
                <a href="notificaciones.php" class="btn btn-sm btn-primary" style="font-size: 10px; padding: 3px 10px;">Ver todas <i class="mdi mdi-arrow-right"></i></a>
              </div>
              <div id="ticketsNotificacionesContainer" style="max-height: 280px; overflow-y: auto;">
                <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
              </div>
            </div>
          </li>
          <li class="nav-item dropdown d-none d-lg-block user-dropdown">
            <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
              <img class="img-xs rounded-circle" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=667eea&color=fff&size=128" alt="Profile image">
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
              <div class="dropdown-header text-center">
                <img class="img-md rounded-circle" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=667eea&color=fff&size=128" alt="Profile image">
                <p class="mb-1 mt-3 font-weight-semibold"><?php echo htmlspecialchars($user_name); ?></p>
                <p class="fw-light text-muted mb-0"><?php echo htmlspecialchars($_SESSION["user_email"] ?? ""); ?></p>
              </div>
              <a class="dropdown-item" href="perfil.php"><i class="dropdown-item-icon mdi mdi-account-outline text-primary me-2"></i> Mi Perfil</a>
              <a class="dropdown-item" href="api/logout.php"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Cerrar Sesion</a>
            </div>
          </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
          <span class="mdi mdi-menu"></span>
        </button>
      </div>
    </nav>

    <div class="container-fluid page-body-wrapper">
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="mdi mdi-view-dashboard menu-icon"></i><span class="menu-title">Dashboard</span></a></li>
          <li class="nav-item nav-category">Gestion de Tickets</li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#tickets-menu" aria-expanded="true" aria-controls="tickets-menu">
              <i class="menu-icon mdi mdi-ticket-confirmation"></i><span class="menu-title">Tickets</span><i class="menu-arrow"></i>
            </a>
            <div class="collapse show" id="tickets-menu">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"><a class="nav-link active d-flex align-items-center justify-content-between" href="tickets.php"><span>Todos los Tickets</span><span class="count-todos-sidebar sidebar-badge">0</span></a></li>
                <li class="nav-item"><a class="nav-link" href="tickets-create.php">Crear Ticket</a></li>
                <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?>
                <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="tickets-mis.php"><span>Mis Tickets</span><span class="count-mis-sidebar sidebar-badge">0</span></a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="tickets-asignados.php"><span>Asignados a Mi</span><span class="count-asignados-sidebar sidebar-badge">0</span></a></li>
                <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="notificaciones.php"><span>Notificaciones</span><span class="count-notificaciones-sidebar sidebar-badge">0</span></a></li>
              </ul>
            </div>
          </li>
          <?php if ($user_rol === 'Administrador' || $user_rol === 'Jefe'): ?>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#usuarios-menu" aria-expanded="false" aria-controls="usuarios-menu">
              <i class="menu-icon mdi mdi-account-multiple"></i><span class="menu-title">Usuarios</span><i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="usuarios-menu">
              <ul class="nav flex-column sub-menu">
                <?php if ($user_rol === 'Administrador'): ?>
                <li class="nav-item"><a class="nav-link" href="usuarios-create.php">Crear Usuario</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="usuarios.php">Lista de Usuarios</a></li>
              </ul>
            </div>
          </li>
          <?php endif; ?>
          <?php if ($user_rol === 'Administrador'): ?>
          <li class="nav-item nav-category">Configuracion</li>
        <li class="nav-item"><a class="nav-link" href="perfil.php"><i class="menu-icon mdi mdi-account-circle-outline"></i><span class="menu-title">Perfil</span></a></li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#catalogos-menu" aria-expanded="false" aria-controls="catalogos-menu">
              <i class="menu-icon mdi mdi-table-settings"></i><span class="menu-title">Catalogos</span><i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="catalogos-menu">
              <ul class="nav flex-column sub-menu">
                <li class="nav-item"><a class="nav-link" href="catalogos-departamentos.php">Departamentos</a></li>
                <li class="nav-item"><a class="nav-link" href="catalogos-canales.php">Canales de Atencion</a></li>
                <li class="nav-item"><a class="nav-link" href="catalogos-actividades.php">Tipos de Actividad</a></li>
                <li class="nav-item"><a class="nav-link" href="catalogos-fallas.php">Tipos de Falla</a></li>
                <li class="nav-item"><a class="nav-link" href="catalogos-ubicaciones.php">Ubicaciones</a></li>
                <li class="nav-item"><a class="nav-link" href="catalogos-equipos.php">Equipos</a></li>
              </ul>
            </div>
          </li>
          <?php endif; ?>
          <li class="nav-item nav-category">Reportes</li>
          <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#reportes-menu" aria-expanded="false" aria-controls="reportes-menu">
              <i class="menu-icon mdi mdi-chart-line"></i><span class="menu-title">Reportes</span><i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="reportes-menu">
              <ul class="nav flex-column sub-menu">
                <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?>
                <li class="nav-item"><a class="nav-link" href="reportes-general.php">Reporte General</a></li>
                <li class="nav-item"><a class="nav-link" href="reportes-departamento.php">Por Departamento</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="reportes-usuario.php">Por Usuario</a></li>
              </ul>
            </div>
          </li>
                          <li class="nav-item nav-category">COMUNICADOS</li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#comunicados-menu" aria-expanded="false" aria-controls="comunicados-menu">
            <i class="menu-icon mdi mdi-email-outline"></i>
            <span class="menu-title">Comunicados</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse" id="comunicados-menu">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="comunicados.php"><span>Avisos</span><span class="count-comunicados-sidebar sidebar-badge">0</span></a></li>
            </ul>
          </div>
        </li>
<li class="nav-item nav-category">AYUDA</li>
          <li class="nav-item"><a class="nav-link" href="documentacion.php"><i class="menu-icon mdi mdi-file-document"></i><span class="menu-title">Documentacion</span></a></li>
        </ul>
      </nav>

      <div class="main-panel">
        <div class="content-wrapper">
          <!-- Header del Ticket -->
          <div class="ticket-header">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="ticket-code mb-2"><?php echo htmlspecialchars($ticket['codigo']); ?></div>
                <div class="ticket-meta">
                  <span class="meta-item">
                    <i class="mdi mdi-calendar"></i>
                    <span>Creado: <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></span>
                  </span>
                  <span class="meta-sep">|</span>
                  <span class="meta-item">
                    <i class="mdi mdi-account"></i>
                    <span>Por: <?php echo htmlspecialchars($ticket['creador_nombre'] ?? 'N/A'); ?></span>
                  </span>
                  <span class="meta-sep">|</span>
                  <span class="meta-item">
                    <i class="mdi mdi-update"></i>
                    <span>Ult. Actualizacion Usuario: <?php echo $ultima_actualizacion_usuario ? date('d/m/Y H:i', strtotime($ultima_actualizacion_usuario)) : 'Sin actualizar'; ?></span>
                  </span>
                </div>
              </div>
              <div class="col-md-6 mt-3 mt-md-0">
                <div class="d-flex align-items-center justify-content-md-end gap-3">
                  <!-- Badge de Progreso -->
                  <div class="info-badge" style="background: rgba(255,255,255,0.2);">
                    <i class="mdi mdi-percent"></i>
                    <span>Progreso: <?php echo $ticket['progreso'] ?? 0; ?>%</span>
                  </div>
                  <!-- Badge de Estado -->
                  <div class="info-badge" style="background: <?php echo $ticket['estado_color'] ?? '#6c757d'; ?>;">
                    <?php if ($ticket_resuelto_pendiente): ?>
                    <span class="pulse-dot"></span>
                    <span>Pendiente Verificacion</span>
                    <?php elseif ($ticket_resuelto): ?>
                    <i class="mdi mdi-check-circle check-approved"></i>
                    <span>Resuelto</span>
                    <?php elseif ($ticket_rechazado): ?>
                    <i class="mdi mdi-close-circle"></i>
                    <span>Rechazado</span>
                    <?php else: ?>
                    <i class="mdi mdi-checkbox-marked-circle"></i>
                    <span><?php echo htmlspecialchars($ticket['estado_nombre'] ?? 'Sin estado'); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <?php if ($ticket_resuelto_pendiente): ?>
          <!-- Banner de Pendiente Verificacion -->
          <div class="ticket-pending-banner">
            <div class="banner-content">
              <div class="banner-icon">
                <span class="pulse-dot" style="margin: 0;"></span>
              </div>
              <div class="banner-text">
                <h4>Pendiente de Verificacion</h4>
                <p>Este ticket ha sido marcado como resuelto y esta esperando la verificacion de un Jefe o Administrador.</p>
              </div>
            </div>
            <?php if ($puede_aprobar): ?>
            <div class="d-flex gap-2">
              <button type="button" class="btn-aprobar" onclick="abrirModalVerificacion('aprobar')">
                <i class="mdi mdi-check-circle me-1"></i>Aprobar
              </button>
              <button type="button" class="btn-rechazar" onclick="abrirModalVerificacion('rechazar')">
                <i class="mdi mdi-close-circle me-1"></i>Rechazar
              </button>
            </div>
            <?php else: ?>
            <div class="ticket-closed-badge" style="background: rgba(0,0,0,0.1);">
              <i class="mdi mdi-clock-outline me-1"></i>
              Esperando verificacion
            </div>
            <?php endif; ?>
          </div>
          <?php elseif ($ticket_rechazado): ?>
          <!-- Banner de Ticket Rechazado -->
          <div class="ticket-rejected-banner">
            <div class="banner-content">
              <div class="banner-icon">
                <i class="mdi mdi-close-circle"></i>
              </div>
              <div class="banner-text">
                <h4>Ticket Rechazado</h4>
                <p>Este ticket fue rechazado por el supervisor. Revisa los comentarios para conocer el motivo y corregir lo necesario.</p>
              </div>
            </div>
            <div class="ticket-closed-badge" style="background: rgba(0,0,0,0.1);">
              <i class="mdi mdi-percent me-1"></i>
              Progreso: 90%
            </div>
          </div>
          <?php elseif ($ticket_resuelto): ?>
          <!-- Banner de Ticket Resuelto/Aprobado -->
          <div class="ticket-closed-banner">
            <div class="banner-content">
              <div class="banner-icon">
                <i class="mdi mdi-check-circle"></i>
              </div>
              <div class="banner-text">
                <h4>Ticket Resuelto</h4>
                <p>Este ticket ha sido completado y verificado. No se permiten mas modificaciones.</p>
              </div>
            </div>
            <div class="ticket-closed-badge">
              <i class="mdi mdi-calendar-check me-1"></i>
              Verificado por: <?php echo htmlspecialchars($ticket['aprobador_nombre'] ?? 'Jefe/Administrador'); ?> - <?php echo $ticket['fecha_aprobacion'] ? date('d/m/Y H:i', strtotime($ticket['fecha_aprobacion'])) : date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?>
            </div>
          </div>
          <?php endif; ?>

          <div class="row">
            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                  <h4 class="mb-1"><i class="mdi mdi-ticket-confirmation text-primary me-2"></i>Detalle del Ticket</h4>
                  <p class="text-muted mb-0">Informacion del ticket<?php echo $ticket_cerrado ? ' (Solo lectura)' : ''; ?></p>
                </div>
                <div class="d-flex gap-2">
                  <a href="tickets.php" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-left me-1"></i> Volver</a>
                  <?php if ($puede_editar): ?>
                  <button type="submit" form="formEditarTicket" class="btn btn-primary btn-submit"><i class="mdi mdi-content-save me-1"></i> Guardar Cambios</button>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <form id="formEditarTicket">
            <input type="hidden" name="codigo" value="<?php echo htmlspecialchars($ticket['codigo']); ?>">
          <div class="row">
              <div class="col-lg-8">
                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-information"></i>Informacion del Ticket</div>
                  <div class="row">
                    <div class="col-md-12 mb-3">
                      <div class="form-group">
                        <label class="required-field"><i class="mdi mdi-format-title text-primary"></i>Titulo del Ticket</label>
                        <input type="text" class="form-control" name="titulo" id="titulo" value="<?php echo htmlspecialchars($ticket['titulo']); ?>" required <?php echo !$puede_editar ? 'readonly' : ''; ?>>
                      </div>
                    </div>
                    <div class="col-md-12 mb-3">
                      <div class="form-group">
                        <label class="required-field"><i class="mdi mdi-text text-info"></i>Descripcion Detallada</label>
                        <textarea class="form-control" name="descripcion" id="descripcion" rows="5" required <?php echo !$puede_editar ? 'readonly' : ''; ?>><?php echo htmlspecialchars($ticket['descripcion']); ?></textarea>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-tag-multiple"></i>Clasificacion</div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-clipboard-text text-info"></i>Tipo de Actividad</label>
                        <select class="form-select" name="actividad_id" id="actividad_id" <?php echo !$puede_editar ? 'disabled' : ''; ?>>
                          <option value="">Seleccione...</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-alert-circle text-danger"></i>Tipo de Falla</label>
                        <select class="form-select" name="tipo_falla_id" id="tipo_falla_id" <?php echo !$puede_editar ? 'disabled' : ''; ?>>
                          <option value="">Seleccione...</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-phone text-success"></i>Canal de Atencion</label>
                        <select class="form-select" name="canal_atencion_id" id="canal_atencion_id" <?php echo !$puede_editar ? 'disabled' : ''; ?>>
                          <option value="">Seleccione...</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-flag text-warning"></i>Prioridad</label>
                        <select class="form-select" name="prioridad_id" id="prioridad_id" <?php echo !$puede_editar ? 'disabled' : ''; ?>>
                          <option value="">Seleccione...</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-map-marker"></i>Ubicacion y Equipo</div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-map-marker text-danger"></i>Ubicacion / Mina</label>
                        <select class="form-select" name="ubicacion_id" id="ubicacion_id" <?php echo !$puede_editar ? 'disabled' : ''; ?>>
                          <option value="">Seleccione...</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-radar text-info"></i>Equipo</label>
                        <select class="form-select" name="equipo_id" id="equipo_id" <?php echo !$puede_editar ? 'disabled' : ''; ?>>
                          <option value="">Seleccione...</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-barcode text-secondary"></i>Codigo de Equipo</label>
                        <select class="form-select" name="codigo_equipo_id" id="codigo_equipo_id" <?php echo !$puede_editar ? 'disabled' : ''; ?>>
                          <option value="">Seleccione...</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <?php if (count($adjuntos) > 0): ?>
                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-attachment"></i>Archivos Adjuntos (<?php echo count($adjuntos); ?>)</div>
                  <div class="file-list">
                    <?php foreach ($adjuntos as $adj):
                      $ext = strtolower(pathinfo($adj['nombre_original'], PATHINFO_EXTENSION));
                      $iconClass = 'other'; $icon = 'mdi-file';
                      if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) { $iconClass = 'image'; $icon = 'mdi-file-image'; }
                      elseif ($ext === 'pdf') { $iconClass = 'pdf'; $icon = 'mdi-file-pdf-box'; }
                      elseif (in_array($ext, ['doc','docx'])) { $iconClass = 'doc'; $icon = 'mdi-file-word'; }
                      elseif (in_array($ext, ['xls','xlsx'])) { $iconClass = 'excel'; $icon = 'mdi-file-excel'; }
                      $fileSize = isset($adj['tamano']) ? formatBytes($adj['tamano']) : '';
                    ?>
                    <div class="file-item" id="file-<?php echo $adj['id']; ?>">
                      <div class="file-icon <?php echo $iconClass; ?>"><i class="mdi <?php echo $icon; ?>"></i></div>
                      <div class="file-info">
                        <div class="file-name"><?php echo htmlspecialchars($adj['nombre_original']); ?></div>
                        <div class="file-size"><?php echo $fileSize; ?> - <?php echo date('d/m/Y H:i', strtotime($adj['created_at'])); ?></div>
                      </div>
                      <a href="<?php echo $adj['ruta']; ?>" target="_blank" class="file-download" title="Descargar">
                        <i class="mdi mdi-download"></i>
                      </a>
                      <?php if ($puede_editar): ?>
                      <button type="button" class="file-remove" onclick="eliminarArchivo(<?php echo $adj['id']; ?>, '<?php echo htmlspecialchars($adj['nombre_original']); ?>')" title="Eliminar">
                        <i class="mdi mdi-close"></i>
                      </button>
                      <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
                <?php endif; ?>

              </div>

              <div class="col-lg-4">
                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-account-group"></i>Asignacion</div>
                  <div class="form-group mb-3">
                    <label><i class="mdi mdi-office-building text-primary"></i>Departamento</label>
                    <select class="form-select" name="departamento_id" id="departamento_id" <?php echo !$puede_editar ? 'disabled' : ''; ?>>
                      <option value="">Seleccione...</option>
                    </select>
                  </div>
                  <div class="form-group mb-3">
                    <label><i class="mdi mdi-account text-success"></i>Asignado a</label>
                    <select class="form-select" name="asignado_a" id="asignado_a" <?php echo !$puede_editar ? 'disabled' : ''; ?>>
                      <option value="">Sin asignar</option>
                    </select>
                  </div>
                </div>

                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-card-account-phone"></i>Informacion de Contacto</div>
                  <div class="form-group mb-3">
                    <label><i class="mdi mdi-account text-info"></i>Nombre del Solicitante</label>
                    <input type="text" class="form-control" name="solicitante_nombre" id="solicitante_nombre" value="<?php echo htmlspecialchars($ticket['solicitante_nombre'] ?? ''); ?>" <?php echo !$puede_editar ? 'readonly' : ''; ?>>
                  </div>
                  <div class="form-group mb-3">
                    <label><i class="mdi mdi-email text-danger"></i>Email de Contacto</label>
                    <input type="email" class="form-control" name="solicitante_email" id="solicitante_email" value="<?php echo htmlspecialchars($ticket['solicitante_email'] ?? ''); ?>" <?php echo !$puede_editar ? 'readonly' : ''; ?>>
                  </div>
                  <div class="form-group mb-3">
                    <label><i class="mdi mdi-phone text-success"></i>Telefono</label>
                    <input type="text" class="form-control" name="solicitante_telefono" id="solicitante_telefono" value="<?php echo htmlspecialchars($ticket['solicitante_telefono'] ?? ''); ?>" <?php echo !$puede_editar ? 'readonly' : ''; ?>>
                  </div>
                </div>

                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-information-outline"></i>Informacion</div>
                  <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted"><i class="mdi mdi-calendar me-1"></i>Creado:</span>
                    <span class="fw-bold"><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></span>
                  </div>
                  <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted"><i class="mdi mdi-update me-1"></i>Actualizado:</span>
                    <span class="fw-bold"><?php echo $ticket['updated_at'] ? date('d/m/Y H:i', strtotime($ticket['updated_at'])) : 'Sin actualizar'; ?></span>
                  </div>
                  <div class="d-flex justify-content-between">
                    <span class="text-muted"><i class="mdi mdi-account me-1"></i>Creador:</span>
                    <span class="fw-bold"><?php echo htmlspecialchars($ticket['creador_nombre'] ?? 'N/A'); ?></span>
                  </div>
                </div>

                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-history"></i>Historial de Progreso</div>
                  <div class="progress-timeline-panel" id="progressTimeline">
                    <div class="text-center py-3">
                      <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </form>

          <!-- Barra de Progreso -->
          <div class="row mt-4">
            <div class="col-12">
              <div class="progress-card">
                <div class="progress-card-header">
                  <div class="progress-card-title">
                    <i class="mdi mdi-chart-timeline-variant"></i>
                    <span>Progreso del Ticket</span>
                  </div>
                  <div class="progress-percentage" id="progressPercentage"><?php echo $ticket['progreso'] ?? 0; ?>%</div>
                </div>
                <div class="progress-card-body">
                  <div class="temperature-bar-container">
                    <div class="temperature-scale">
                      <span>0%</span>
                      <span>25%</span>
                      <span>50%</span>
                      <span>75%</span>
                      <span>100%</span>
                    </div>
                    <div class="temperature-bar">
                      <div class="temperature-fill" id="temperatureFill" style="width: <?php echo $ticket['progreso'] ?? 0; ?>%;"></div>
                      <div class="temperature-markers">
                        <div class="marker" style="left: 25%;"></div>
                        <div class="marker" style="left: 50%;"></div>
                        <div class="marker" style="left: 75%;"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Seccion de Chat/Seguimiento -->
          <div class="row mt-4">
            <div class="col-12">
              <div class="chat-section">
                <div class="chat-header">
                  <div class="chat-header-title">
                    <i class="mdi mdi-message-text"></i>
                    <span>Seguimiento del Ticket</span>
                  </div>
                  <span class="badge bg-light text-dark" id="chatCount">0 mensajes</span>
                </div>

                <div class="chat-messages" id="chatMessages">
                  <div class="chat-empty">
                    <i class="mdi mdi-message-text-outline"></i>
                    <h5>Sin comentarios aun</h5>
                    <p>Se el primero en agregar un comentario o solucion a este ticket</p>
                  </div>
                </div>

                <?php if ($ticket_cerrado): ?>
                <div class="chat-closed-overlay">
                  <i class="mdi mdi-lock-check"></i>
                  <h5>Ticket Cerrado</h5>
                  <p>Este ticket esta cerrado y no se pueden agregar mas comentarios.</p>
                </div>
                <?php elseif ($ticket_resuelto_pendiente): ?>
                <div class="chat-closed-overlay" style="background: rgba(253, 126, 20, 0.1);">
                  <i class="mdi mdi-clock-alert" style="color: #fd7e14;"></i>
                  <h5>Pendiente de Aprobacion</h5>
                  <p>Este ticket esta esperando aprobacion. No se pueden agregar comentarios hasta que sea aprobado o rechazado.</p>
                </div>
                <?php else: ?>
                <div class="chat-input-area">
                  <div class="chat-toolbar">
                    <div class="chat-type-selector">
                      <button type="button" class="chat-type-btn active" data-type="comentario">
                        <i class="mdi mdi-comment-outline me-1"></i>Comentario
                      </button>
                      <button type="button" class="chat-type-btn solucion" data-type="solucion">
                        <i class="mdi mdi-check-circle-outline me-1"></i>Solucion
                      </button>
                      <button type="button" class="chat-type-btn nota_interna" data-type="nota_interna">
                        <i class="mdi mdi-lock-outline me-1"></i>Nota Interna
                      </button>
                    </div>
                    <?php if ($puede_editar): ?>
                    <div class="chat-progress-controls">
                      <div class="progress-input-group">
                        <span class="progress-label">Progreso:</span>
                        <input type="number" class="form-control progress-input" id="progressInput" min="0" max="100" value="<?php echo $ticket['progreso'] ?? 0; ?>">
                        <span class="progress-input-suffix">%</span>
                      </div>
                      <button type="button" class="btn btn-sm btn-success btn-update-progress" id="btnUpdateProgress" disabled>
                        <i class="mdi mdi-check"></i>
                      </button>
                      <button type="button" class="btn btn-sm btn-danger btn-close-ticket" id="btnCloseTicket">
                        <i class="mdi mdi-lock me-1"></i>Cerrar
                      </button>
                    </div>
                    <?php endif; ?>
                  </div>
                  <div class="chat-file-preview" id="chatFilePreview"></div>
                  <div class="chat-input-wrapper">
                    <div class="chat-input-container">
                      <textarea class="chat-textarea" id="chatInput" placeholder="Escribe un comentario..." rows="1"></textarea>
                    </div>
                    <div class="chat-actions">
                      <input type="file" id="chatFileInput" multiple style="display: none;" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                      <button type="button" class="chat-btn-attach" onclick="document.getElementById('chatFileInput').click()" title="Adjuntar archivo">
                        <i class="mdi mdi-attachment"></i>
                      </button>
                      <button type="button" class="chat-btn-send" id="btnSendComment" onclick="enviarComentario()" title="Enviar">
                        <i class="mdi mdi-send"></i>
                      </button>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Lightbox para imagenes -->
        <div class="lightbox-overlay" id="lightbox" onclick="closeLightbox()">
          <span class="lightbox-close">&times;</span>
          <img src="" alt="Preview" class="lightbox-image" id="lightboxImage">
        </div>

        <footer class="footer">
          <div class="d-sm-flex justify-content-center justify-content-sm-between">
            <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Portal SIRA <a href="https://www.clonsa.pe/" target="_blank">Clonsa Ingenieria</a> <?php echo date('Y'); ?></span>
            <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Sistema Integral de Registro y Atencion</span>
          </div>
        </footer>
      </div>
    </div>
  </div>

  <!-- Modal de Proceso -->
  <div class="modal fade modal-process" id="modalProceso" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="process-loader"><div class="spinner"></div></div>
          <h4 class="process-title">Guardando cambios...</h4>
          <p class="process-subtitle">Por favor espere</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Exito -->
  <div class="modal fade modal-process" id="modalExito" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="success-checkmark">
            <div class="check-icon">
              <span class="icon-line line-tip"></span>
              <span class="icon-line line-long"></span>
              <div class="icon-circle"></div>
              <div class="icon-fix"></div>
            </div>
          </div>
          <h4 class="process-title" style="color: #4CAF50;">Cambios Guardados!</h4>
          <p class="process-subtitle">El ticket ha sido actualizado correctamente</p>
          <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="tickets.php" class="modal-btn-primary"><i class="mdi mdi-view-list me-2"></i>Ver Tickets</a>
            <a href="javascript:location.reload();" class="modal-btn-secondary"><i class="mdi mdi-pencil me-2"></i>Seguir Editando</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Error -->
  <div class="modal fade modal-process" id="modalError" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="error-icon"><i class="mdi mdi-close"></i></div>
          <h4 class="process-title" style="color: #dc3545;">Error al Guardar</h4>
          <p class="process-subtitle" id="errorMessage">Ha ocurrido un error inesperado</p>
          <div class="d-flex justify-content-center gap-3 mt-4">
            <button type="button" class="modal-btn-primary" data-bs-dismiss="modal"><i class="mdi mdi-refresh me-2"></i>Intentar de Nuevo</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Confirmar Cerrar Ticket -->
  <div class="modal fade modal-process" id="modalConfirmarCerrar" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="warning-icon">
            <i class="mdi mdi-alert-circle"></i>
          </div>
          <h4 class="process-title" style="color: #fd7e14;">Cerrar Ticket</h4>
          <p class="process-subtitle">Esta seguro que desea cerrar este ticket?</p>
          <p class="text-muted" style="font-size: 13px;">Esta accion establecera el progreso a 100% y el estado a "Cerrado". No podra realizar mas modificaciones.</p>
          <div class="d-flex justify-content-center gap-3 mt-4">
            <button type="button" class="modal-btn-secondary" data-bs-dismiss="modal"><i class="mdi mdi-close me-2"></i>Cancelar</button>
            <button type="button" class="modal-btn-danger" id="btnConfirmarCerrar"><i class="mdi mdi-lock me-2"></i>Si, Cerrar Ticket</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Cerrando Ticket -->
  <div class="modal fade modal-process" id="modalCerrando" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="process-loader"><div class="spinner"></div></div>
          <h4 class="process-title">Cerrando ticket...</h4>
          <p class="process-subtitle">Por favor espere</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Ticket Cerrado Exitosamente -->
  <div class="modal fade modal-process" id="modalTicketCerrado" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="success-checkmark">
            <div class="check-icon">
              <span class="icon-line line-tip"></span>
              <span class="icon-line line-long"></span>
              <div class="icon-circle"></div>
              <div class="icon-fix"></div>
            </div>
          </div>
          <h4 class="process-title" style="color: #198754;">Ticket Enviado!</h4>
          <p class="process-subtitle">El ticket ha sido marcado como resuelto y esta pendiente de verificacion</p>
          <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="tickets.php" class="modal-btn-primary"><i class="mdi mdi-view-list me-2"></i>Ver Tickets</a>
            <a href="javascript:location.reload();" class="modal-btn-secondary"><i class="mdi mdi-eye me-2"></i>Ver Ticket</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Verificacion de Ticket -->
  <div class="modal fade modal-process" id="modalVerificacion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="warning-icon" id="verificacionIcon" style="background: #d4edda;">
            <i class="mdi mdi-clipboard-check" style="color: #198754;"></i>
          </div>
          <h4 class="process-title" id="verificacionTitulo" style="color: #198754;">Verificar Ticket</h4>
          <p class="process-subtitle" id="verificacionSubtitulo">Ingrese un comentario sobre la verificacion</p>
          <div class="text-start mt-3 mb-3">
            <label class="form-label text-muted" style="font-size: 12px;">Comentario <span id="comentarioRequerido" style="display:none;" class="text-danger">*</span></label>
            <textarea class="form-control" id="comentarioVerificacion" rows="6" placeholder="Ingrese su comentario..."></textarea>
            <small class="text-muted" id="comentarioAyuda">El comentario aparecera en el seguimiento del ticket.</small>
            <div class="verificacion-file-wrap text-start" id="rechazoFileWrap" style="display:none;">
              <div class="verificacion-file-actions">
                <input type="file" id="rechazoFileInput" style="display: none;" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnAdjuntarRechazo">
                  <i class="mdi mdi-paperclip me-1"></i>Adjuntar evidencia (opcional)
                </button>
                <small class="text-muted">Tambien puede pegar una imagen con Ctrl + V</small>
              </div>
              <div id="rechazoFilePreview"></div>
            </div>
          </div>
          <div class="d-flex justify-content-center gap-3 mt-4">
            <button type="button" class="modal-btn-secondary" data-bs-dismiss="modal"><i class="mdi mdi-close me-2"></i>Cancelar</button>
            <button type="button" class="btn-rechazar" id="btnRechazarVerificacion" style="display:none;"><i class="mdi mdi-close-circle me-2"></i>Rechazar</button>
            <button type="button" class="btn-aprobar" id="btnAprobarVerificacion" style="display:none;"><i class="mdi mdi-check-circle me-2"></i>Aprobar</button>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- Modal Aprobado Exitosamente -->
  <div class="modal fade modal-process" id="modalAprobado" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="success-checkmark">
            <div class="check-icon">
              <span class="icon-line line-tip"></span>
              <span class="icon-line line-long"></span>
              <div class="icon-circle"></div>
              <div class="icon-fix"></div>
            </div>
          </div>
          <h4 class="process-title" style="color: #198754;">Ticket Resuelto!</h4>
          <p class="process-subtitle">El ticket ha sido verificado y marcado como resuelto</p>
          <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="tickets.php" class="modal-btn-primary"><i class="mdi mdi-view-list me-2"></i>Ver Tickets</a>
            <a href="javascript:location.reload();" class="modal-btn-secondary"><i class="mdi mdi-eye me-2"></i>Ver Ticket</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Rechazado -->
  <div class="modal fade modal-process" id="modalRechazado" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="warning-icon" style="background: #f8d7da;">
            <i class="mdi mdi-close-circle" style="color: #dc3545;"></i>
          </div>
          <h4 class="process-title" style="color: #dc3545;">Ticket Rechazado</h4>
          <p class="process-subtitle">El ticket ha sido marcado como "Rechazado" con 90% de progreso</p>
          <p class="text-muted" style="font-size: 13px;">Se ha agregado el comentario al seguimiento del ticket.</p>
          <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="tickets.php" class="modal-btn-primary"><i class="mdi mdi-view-list me-2"></i>Ver Tickets</a>
            <a href="javascript:location.reload();" class="modal-btn-secondary"><i class="mdi mdi-eye me-2"></i>Ver Ticket</a>
          </div>
        </div>
      </div>
    </div>
  </div>


  <script src="template/vendors/js/vendor.bundle.base.js"></script>
  <script src="template/js/off-canvas.js"></script>
  <script src="template/js/hoverable-collapse.js"></script>
  <script src="template/js/template.js"></script>
  <script src="template/js/settings.js"></script>
  <script>
    const SESSION_TIMEOUT = <?php echo $SESSION_TIMEOUT_JS; ?>;
    const SESSION_POPUP_TIMEOUT = <?php echo $SESSION_POPUP_TIMEOUT_JS; ?>;
    window.CURRENT_USER_ID = <?php echo intval($user_id); ?>;
    window.USER_ROL = '<?php echo addslashes($user_rol); ?>';
  </script>
  <script src="assets/js/session-manager.js"></script>

  <script>
    // Datos del ticket actual
    const ticketData = {
      departamento_id: <?php echo $ticket['departamento_id'] ?? 'null'; ?>,
      estado_id: <?php echo $ticket['estado_id'] ?? 'null'; ?>,
      pendiente_aprobacion: <?php echo $ticket['pendiente_aprobacion'] ?? '0'; ?>,
      prioridad_id: <?php echo $ticket['prioridad_id'] ?? 'null'; ?>,
      actividad_id: <?php echo $ticket['actividad_id'] ?? 'null'; ?>,
      tipo_falla_id: <?php echo $ticket['tipo_falla_id'] ?? 'null'; ?>,
      canal_atencion_id: <?php echo $ticket['canal_atencion_id'] ?? 'null'; ?>,
      ubicacion_id: <?php echo $ticket['ubicacion_id'] ?? 'null'; ?>,
      equipo_id: <?php echo $ticket['equipo_id'] ?? 'null'; ?>,
      codigo_equipo_id: <?php echo $ticket['codigo_equipo_id'] ?? 'null'; ?>,
      asignado_a: <?php echo $ticket['asignado_a'] ?? 'null'; ?>
    };

    // Verificar si el ticket esta resuelto (no se pueden eliminar comentarios)
    const comentariosBloqueados = (
      ticketData.pendiente_aprobacion == 1 ||
      ticketData.estado_id == 4 ||
      ticketData.estado_id == 5
    );

    const modalProceso = new bootstrap.Modal(document.getElementById('modalProceso'));
    const modalExito = new bootstrap.Modal(document.getElementById('modalExito'));
    const modalError = new bootstrap.Modal(document.getElementById('modalError'));
    const modalConfirmarCerrar = new bootstrap.Modal(document.getElementById('modalConfirmarCerrar'));
    const modalCerrando = new bootstrap.Modal(document.getElementById('modalCerrando'));
    const modalTicketCerrado = new bootstrap.Modal(document.getElementById('modalTicketCerrado'));

    // Modales de verificacion (solo si existen)
    const modalVerificacion = document.getElementById('modalVerificacion') ? new bootstrap.Modal(document.getElementById('modalVerificacion')) : null;
    const modalAprobado = document.getElementById('modalAprobado') ? new bootstrap.Modal(document.getElementById('modalAprobado')) : null;
    const modalRechazado = document.getElementById('modalRechazado') ? new bootstrap.Modal(document.getElementById('modalRechazado')) : null;

    let tipoVerificacion = null; // 'aprobar' o 'rechazar'
    let rechazoArchivo = null;

    $(document).ready(function() {
      // Cargar todos los catalogos
      cargarCatalogos();

      // Actualizar label de progreso
      $('#progreso').on('input', function() {
        $('#progressValue').text($(this).val() + '%');
      });

      // Manejar envio del formulario
      $('#formEditarTicket').on('submit', function(e) {
        e.preventDefault();
        guardarTicket();
      });
    });

    // ========== CARGAR CATALOGOS ==========
    function cargarCatalogos() {
      // Cargar departamentos
      $.get('api/catalogos.php?tipo=departamentos', function(r) {
        if(r.success) {
          let html = '<option value="">Seleccione...</option>';
          r.data.forEach(item => {
            const selected = item.id == ticketData.departamento_id ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.nombre}</option>`;
          });
          $('#departamento_id').html(html);
          // Cargar usuarios del departamento seleccionado
          if(ticketData.departamento_id) {
            cargarUsuariosDepartamento(ticketData.departamento_id);
          }
        }
      });

      // Cargar estados
      $.get('api/catalogos.php?tipo=estados', function(r) {
        if(r.success) {
          let html = '<option value="">Seleccione...</option>';
          r.data.forEach(item => {
            const selected = item.id == ticketData.estado_id ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.nombre}</option>`;
          });
          $('#estado_id').html(html);
        }
      });

      // Cargar prioridades
      $.get('api/catalogos.php?tipo=prioridades', function(r) {
        if(r.success) {
          let html = '<option value="">Seleccione...</option>';
          r.data.forEach(item => {
            const selected = item.id == ticketData.prioridad_id ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.nombre}</option>`;
          });
          $('#prioridad_id').html(html);
        }
      });

      // Cargar actividades
      $.get('api/catalogos.php?tipo=actividades', function(r) {
        if(r.success) {
          let html = '<option value="">Seleccione...</option>';
          r.data.forEach(item => {
            const selected = item.id == ticketData.actividad_id ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.nombre}</option>`;
          });
          $('#actividad_id').html(html);
        }
      });

      // Cargar tipos de falla
      $.get('api/catalogos.php?tipo=tipos_falla', function(r) {
        if(r.success) {
          let html = '<option value="">Seleccione...</option>';
          r.data.forEach(item => {
            const selected = item.id == ticketData.tipo_falla_id ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.nombre}</option>`;
          });
          $('#tipo_falla_id').html(html);
        }
      });

      // Cargar canales de atencion
      $.get('api/catalogos.php?tipo=canales', function(r) {
        if(r.success) {
          let html = '<option value="">Seleccione...</option>';
          r.data.forEach(item => {
            const selected = item.id == ticketData.canal_atencion_id ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.nombre}</option>`;
          });
          $('#canal_atencion_id').html(html);
        }
      });

      // Cargar ubicaciones
      $.get('api/catalogos.php?tipo=ubicaciones', function(r) {
        if(r.success) {
          let html = '<option value="">Seleccione...</option>';
          r.data.forEach(item => {
            const selected = item.id == ticketData.ubicacion_id ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.nombre}</option>`;
          });
          $('#ubicacion_id').html(html);
        }
      });

      // Cargar equipos
      $.get('api/catalogos.php?tipo=equipos', function(r) {
        if(r.success) {
          let html = '<option value="">Seleccione...</option>';
          r.data.forEach(item => {
            const selected = item.id == ticketData.equipo_id ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.nombre}</option>`;
          });
          $('#equipo_id').html(html);
        }
      });

      // Cargar codigos de equipo
      $.get('api/catalogos.php?tipo=codigos_equipo', function(r) {
        if(r.success) {
          let html = '<option value="">Seleccione...</option>';
          r.data.forEach(item => {
            const selected = item.id == ticketData.codigo_equipo_id ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.codigo}</option>`;
          });
          $('#codigo_equipo_id').html(html);
        }
      });

      // Cuando cambie el departamento, cargar usuarios
      $('#departamento_id').on('change', function() {
        cargarUsuariosDepartamento($(this).val());
      });
    }

    function cargarUsuariosDepartamento(deptId) {
      if(!deptId) {
        $('#asignado_a').html('<option value="">Sin asignar</option>');
        return;
      }
      $.get('api/usuarios.php?action=por_departamento&departamento_id=' + deptId, function(r) {
        if(r.success) {
          let html = '<option value="">Sin asignar</option>';
          r.data.forEach(item => {
            const selected = item.id == ticketData.asignado_a ? 'selected' : '';
            html += `<option value="${item.id}" ${selected}>${item.nombre_completo}</option>`;
          });
          $('#asignado_a').html(html);
        }
      });
    }

    // ========== GUARDAR TICKET ==========
    function guardarTicket() {
      modalProceso.show();

      const formData = new FormData(document.getElementById('formEditarTicket'));

      $.ajax({
        url: 'api/tickets.php?action=actualizar',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          modalProceso.hide();
          if(response.success) {
            setTimeout(() => modalExito.show(), 300);
          } else {
            $('#errorMessage').text(response.message || 'No se pudo actualizar el ticket');
            setTimeout(() => modalError.show(), 300);
          }
        },
        error: function() {
          modalProceso.hide();
          $('#errorMessage').text('Error de conexion');
          setTimeout(() => modalError.show(), 300);
        }
      });
    }

    // ========== CHAT/SEGUIMIENTO ==========
    const ticketId = <?php echo $ticket_id; ?>;
    const currentUserId = <?php echo $user_id; ?>;
    const currentUserName = '<?php echo addslashes($user_name); ?>';
    let chatFiles = [];
    let selectedType = 'comentario';

    // Cargar comentarios al iniciar
    $(document).ready(function() {
      cargarComentarios();

      // Selector de tipo de comentario
      $('.chat-type-btn').on('click', function() {
        $('.chat-type-btn').removeClass('active');
        $(this).addClass('active');
        selectedType = $(this).data('type');
      });

      // Auto-resize textarea
      $('#chatInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
      });

      // Enviar con Enter (Shift+Enter para nueva linea)
      $('#chatInput').on('keydown', function(e) {
        if(e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          enviarComentario();
        }
      });
      $('#chatInput').on('paste', function(e) {
        if(capturarArchivosPegadosChat(e)) {
          e.preventDefault();
        }
      });

      // Manejar archivos adjuntos
      $('#chatFileInput').on('change', function(e) {
        handleChatFiles(e.target.files);
      });

      // Adjuntos opcionales en rechazo de verificacion
      $('#btnAdjuntarRechazo').on('click', function() {
        $('#rechazoFileInput').trigger('click');
      });

      $('#rechazoFileInput').on('change', function(e) {
        const file = e.target.files && e.target.files[0] ? e.target.files[0] : null;
        setRechazoArchivo(file);
      });

      $('#comentarioVerificacion').on('paste', function(e) {
        if(tipoVerificacion !== 'rechazar') return;
        if(capturarImagenPegada(e)) {
          e.preventDefault();
        }
      });

      $(document).on('click', '#btnQuitarRechazoFile', function() {
        limpiarRechazoArchivo();
      });
    });

    function cargarComentarios() {
      $.get('api/comentarios.php?action=listar&ticket_id=' + ticketId, function(response) {
        if(response.success) {
          renderComentarios(response.data);
        }
      });
    }

    function renderComentarios(comentarios) {
      const container = $('#chatMessages');

      if(comentarios.length === 0) {
        container.html(`
          <div class="chat-empty">
            <i class="mdi mdi-message-text-outline"></i>
            <h5>Sin comentarios aun</h5>
            <p>Se el primero en agregar un comentario o solucion a este ticket</p>
          </div>
        `);
        $('#chatCount').text('0 mensajes');
        return;
      }

      let html = '';
      let lastDate = '';

      comentarios.forEach(function(c) {
        const textoMensaje = (c.mensaje || '').toUpperCase();
        const esNotaVerificacion = (
          c.tipo === 'nota_interna' &&
          (textoMensaje.includes('TICKET APROBADO') || textoMensaje.includes('TICKET RECHAZADO'))
        );
        const esNotaTransferencia = (
          c.tipo === 'nota_interna' &&
          (textoMensaje.includes('TICKET TRANSFERIDO') || textoMensaje.includes('SOLICITUD DE TRANSFERENCIA'))
        );
        const isOwn = !esNotaVerificacion && !esNotaTransferencia && c.usuario_id == currentUserId;
        const fecha = new Date(c.created_at);
        const fechaStr = fecha.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
        const horaStr = fecha.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });

        // Separador de fecha
        if(fechaStr !== lastDate) {
          html += `<div class="chat-date-separator"><span>${fechaStr}</span></div>`;
          lastDate = fechaStr;
        }

        // Tipo badge
        let tipoBadge = '';
        if(c.tipo === 'solucion') {
          tipoBadge = '<span class="message-type solucion"><i class="mdi mdi-check-circle me-1"></i>Solucion</span>';
        } else if(esNotaTransferencia) {
          const esSolicitud = textoMensaje.includes('SOLICITUD DE TRANSFERENCIA RECHAZADA');
          const esPendiente = textoMensaje.includes('SOLICITUD DE TRANSFERENCIA') && !textoMensaje.includes('RECHAZADA') && !textoMensaje.includes('TICKET TRANSFERIDO');
          if (esSolicitud) {
            tipoBadge = '<span class="message-type transferido" style="background:#f8d7da;color:#721c24;"><i class="mdi mdi-transfer-right me-1"></i>Solicitud rechazada</span>';
          } else if (esPendiente) {
            tipoBadge = '<span class="message-type transferido" style="background:#fff3cd;color:#856404;"><i class="mdi mdi-clock-outline me-1"></i>Solicitud de transferencia</span>';
          } else {
            tipoBadge = '<span class="message-type transferido"><i class="mdi mdi-swap-horizontal me-1"></i>Transferido</span>';
          }
        } else if(c.tipo === 'nota_interna') {
          tipoBadge = '<span class="message-type nota_interna"><i class="mdi mdi-lock me-1"></i>Nota Interna</span>';
        }
        const rolBadge = (esNotaVerificacion || esNotaTransferencia)
          ? '<span class="message-role-badge"><i class="mdi mdi-account-tie me-1"></i>Jefe/Admin</span>'
          : '';

        // Archivos adjuntos
        let archivosHtml = '';
        if(c.archivos && c.archivos.length > 0) {
          archivosHtml = '<div class="message-attachments">';
          c.archivos.forEach(function(archivo) {
            const ext = archivo.nombre_original.split('.').pop().toLowerCase();
            const isImage = ['jpg','jpeg','png','gif','webp','bmp'].includes(ext);

            if(isImage) {
              archivosHtml += `<img src="${archivo.ruta}" class="attachment-image" onclick="openLightbox('${archivo.ruta}')" alt="${archivo.nombre_original}">`;
            } else {
              let icon = 'mdi-file';
              if(ext === 'pdf') icon = 'mdi-file-pdf-box';
              else if(['doc','docx'].includes(ext)) icon = 'mdi-file-word';
              else if(['xls','xlsx'].includes(ext)) icon = 'mdi-file-excel';

              archivosHtml += `<a href="${archivo.ruta}" target="_blank" class="attachment-item"><i class="mdi ${icon}"></i>${archivo.nombre_original}</a>`;
            }
          });
          archivosHtml += '</div>';
        }

        // Iniciales para avatar
        const initials = c.usuario_nombre.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        const avatarColor = getAvatarColor(c.usuario_id);
        const avatarText = initials;
        const avatarStyle = esNotaVerificacion
          ? 'background: linear-gradient(135deg, #198754 0%, #20c997 100%);'
          : esNotaTransferencia
          ? 'background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);'
          : `background: ${avatarColor};`;

        // Mostrar boton eliminar solo si no esta bloqueado por verificacion
        // y el usuario tiene permisos.
        const canDelete = !comentariosBloqueados &&
          !esNotaVerificacion &&
          !esNotaTransferencia &&
          (
            isOwn ||
            '<?php echo $user_rol; ?>' === 'Administrador' ||
            '<?php echo $user_rol; ?>' === 'Admin'
          );
        const deleteBtn = canDelete ? `<button class="message-delete" onclick="eliminarComentario(${c.id})" title="Eliminar"><i class="mdi mdi-close"></i></button>` : '';

        html += `
          <div class="chat-message ${isOwn ? 'own' : ''} ${esNotaVerificacion ? 'verification' : ''} ${esNotaTransferencia ? 'transfer' : ''}">
            <div class="avatar" style="${avatarStyle}">${avatarText}</div>
            <div class="message-content">
              ${deleteBtn}
              ${tipoBadge}${rolBadge}
              <div class="message-bubble">
                <div class="message-header">
                  <span class="message-author">${(esNotaVerificacion || esNotaTransferencia) ? (c.usuario_nombre || 'Jefe/Admin') : (isOwn ? 'Tu' : c.usuario_nombre)}</span>
                  <span class="message-time">${horaStr}</span>
                </div>
                <div class="message-text">${escapeHtml(c.mensaje).replace(/\n/g, '<br>')}</div>
                ${archivosHtml}
              </div>
            </div>
          </div>
        `;
      });

      container.html(html);
      $('#chatCount').text(comentarios.length + ' mensaje' + (comentarios.length !== 1 ? 's' : ''));

      // Scroll al final (con pequeno delay para asegurar que las imagenes carguen)
      setTimeout(function() {
        container.scrollTop(container[0].scrollHeight);
      }, 100);
    }

    function handleChatFiles(files) {
      Array.from(files).forEach(file => {
        if(!file) return;
        if(file.size > 10 * 1024 * 1024) {
          alert('El archivo "' + file.name + '" excede 10MB');
          return;
        }
        chatFiles.push(file);
      });
      renderChatFilePreview();
    }

    function renderChatFilePreview() {
      const container = $('#chatFilePreview');
      if(chatFiles.length === 0) {
        container.html('');
        return;
      }

      let html = '';
      chatFiles.forEach((file, index) => {
        const ext = file.name.split('.').pop().toLowerCase();
        let icon = 'mdi-file';
        if(['jpg','jpeg','png','gif','webp','bmp'].includes(ext)) icon = 'mdi-file-image';
        else if(ext === 'pdf') icon = 'mdi-file-pdf-box';
        else if(['doc','docx'].includes(ext)) icon = 'mdi-file-word';
        else if(['xls','xlsx'].includes(ext)) icon = 'mdi-file-excel';

        html += `
          <div class="chat-file-item">
            <i class="mdi ${icon}"></i>
            <span>${file.name}</span>
            <i class="mdi mdi-close remove-file" onclick="removeChatFile(${index})"></i>
          </div>
        `;
      });
      container.html(html);
    }

    function removeChatFile(index) {
      chatFiles.splice(index, 1);
      renderChatFilePreview();
    }

    function capturarArchivosPegadosChat(event) {
      const original = event.originalEvent || event;
      const clipboardData = original.clipboardData || window.clipboardData;
      if(!clipboardData) return false;

      const archivos = [];

      if(clipboardData.items && clipboardData.items.length) {
        for(let i = 0; i < clipboardData.items.length; i++) {
          const item = clipboardData.items[i];
          if(item && item.kind === 'file') {
            const file = item.getAsFile();
            if(file) {
              archivos.push(normalizarArchivoPegadoChat(file, archivos.length));
            }
          }
        }
      }

      if(archivos.length === 0 && clipboardData.files && clipboardData.files.length) {
        for(let i = 0; i < clipboardData.files.length; i++) {
          const file = clipboardData.files[i];
          if(file) {
            archivos.push(normalizarArchivoPegadoChat(file, archivos.length));
          }
        }
      }

      if(archivos.length === 0) return false;

      handleChatFiles(archivos);
      mostrarNotificacion(
        archivos.length === 1
          ? 'Archivo pegado en el comentario'
          : archivos.length + ' archivos pegados en el comentario',
        'success'
      );
      return true;
    }

    function normalizarArchivoPegadoChat(file, index) {
      const fileType = file.type || 'application/octet-stream';
      const hasName = !!(file.name && file.name.trim());
      if(hasName || typeof File === 'undefined') {
        return file;
      }

      let ext = 'bin';
      if(fileType.indexOf('/') > -1) {
        ext = fileType.split('/')[1] || 'bin';
        if(ext.indexOf('+') > -1) ext = ext.split('+')[0];
      }
      if(!ext) ext = 'bin';

      const nombre = `adjunto_${Date.now()}_${index}.${ext}`;
      return new File([file], nombre, { type: fileType });
    }

    function limpiarRechazoArchivo() {
      rechazoArchivo = null;
      $('#rechazoFileInput').val('');
      renderRechazoFilePreview();
    }

    function capturarImagenPegada(event) {
      const original = event.originalEvent || event;
      const clipboardData = original.clipboardData || window.clipboardData;
      if(!clipboardData) return false;

      // Caso 1: clipboardData.items (comun en Chrome/Edge)
      if(clipboardData.items && clipboardData.items.length) {
        for(let i = 0; i < clipboardData.items.length; i++) {
          const item = clipboardData.items[i];
          if(item && item.kind === 'file' && item.type && item.type.indexOf('image/') === 0) {
            const file = item.getAsFile();
            if(file) {
              setRechazoArchivo(crearArchivoPegado(file));
              mostrarNotificacion('Imagen pegada como evidencia de rechazo', 'success');
              return true;
            }
          }
        }
      }

      // Caso 2: clipboardData.files (algunos navegadores/portapapeles)
      if(clipboardData.files && clipboardData.files.length) {
        for(let i = 0; i < clipboardData.files.length; i++) {
          const file = clipboardData.files[i];
          if(file && file.type && file.type.indexOf('image/') === 0) {
            setRechazoArchivo(crearArchivoPegado(file));
            mostrarNotificacion('Imagen pegada como evidencia de rechazo', 'success');
            return true;
          }
        }
      }

      return false;
    }

    function crearArchivoPegado(file) {
      const ext = (file.type && file.type.split('/')[1]) ? file.type.split('/')[1] : 'png';
      if(typeof File === 'undefined') return file;
      return new File([file], `evidencia_rechazo_${Date.now()}.${ext}`, { type: file.type || 'image/png' });
    }

    function setRechazoArchivo(file) {
      if(!file) {
        limpiarRechazoArchivo();
        return;
      }

      if(file.size > 10 * 1024 * 1024) {
        alert('El archivo excede 10MB');
        return;
      }

      rechazoArchivo = file;
      renderRechazoFilePreview();
    }

    function renderRechazoFilePreview() {
      const container = $('#rechazoFilePreview');
      if(!rechazoArchivo) {
        container.html('');
        return;
      }

      const safeName = escapeHtml(rechazoArchivo.name || 'archivo_adjunto');
      container.html(`
        <div class="verificacion-file-name">
          <i class="mdi mdi-paperclip"></i>
          <span>${safeName}</span>
          <button type="button" id="btnQuitarRechazoFile" class="verificacion-file-remove" title="Quitar archivo">
            <i class="mdi mdi-close-circle"></i>
          </button>
        </div>
      `);
    }

    function enviarComentario() {
      const mensaje = $('#chatInput').val().trim();

      if(!mensaje && chatFiles.length === 0) {
        return;
      }

      const formData = new FormData();
      formData.append('ticket_id', ticketId);
      formData.append('mensaje', mensaje || '(Archivo adjunto)');
      formData.append('tipo', selectedType);

      chatFiles.forEach(file => {
        formData.append('archivos[]', file);
      });

      $('#btnSendComment').prop('disabled', true);

      $.ajax({
        url: 'api/comentarios.php?action=crear',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          if(response.success) {
            $('#chatInput').val('').css('height', 'auto');
            chatFiles = [];
            renderChatFilePreview();
            $('#chatFileInput').val('');
            cargarComentarios();
          } else {
            alert('Error: ' + (response.message || 'No se pudo enviar el comentario'));
          }
          $('#btnSendComment').prop('disabled', false);
        },
        error: function() {
          alert('Error de conexion');
          $('#btnSendComment').prop('disabled', false);
        }
      });
    }

    function openLightbox(src) {
      event.stopPropagation();
      $('#lightboxImage').attr('src', src);
      $('#lightbox').addClass('active');
    }

    function closeLightbox() {
      $('#lightbox').removeClass('active');
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function getAvatarColor(userId) {
      const colors = ['#1F3BB3', '#4a6fd1', '#667eea', '#764ba2', '#28a745', '#17a2b8', '#fd7e14', '#dc3545', '#6f42c1', '#20c997'];
      return colors[userId % colors.length];
    }

    // Cerrar lightbox con Escape
    $(document).on('keydown', function(e) {
      if(e.key === 'Escape') {
        closeLightbox();
      }
    });

    // ========== BARRA DE PROGRESO TEMPERATURA ==========
    function updateProgressBar(progress) {
      const fill = document.getElementById('temperatureFill');
      const percentage = document.getElementById('progressPercentage');

      if(!fill || !percentage) return;

      fill.style.width = progress + '%';
      percentage.textContent = progress + '%';

      // Remover clases anteriores
      fill.classList.remove('temp-cold', 'temp-cool', 'temp-warm', 'temp-hot', 'temp-complete');

      // Asignar clase segun el porcentaje
      if(progress === 100) {
        fill.classList.add('temp-complete');
      } else if(progress >= 75) {
        fill.classList.add('temp-hot');
      } else if(progress >= 50) {
        fill.classList.add('temp-warm');
      } else if(progress >= 25) {
        fill.classList.add('temp-cool');
      } else {
        fill.classList.add('temp-cold');
      }
    }

    function cargarHistorialProgreso() {
      $.get('api/tickets.php?action=historial_progreso&ticket_id=' + ticketId, function(response) {
        if(response.success) {
          renderHistorialProgreso(response.data);
        }
      });
    }

    function renderHistorialProgreso(historial) {
      const container = $('#progressTimeline');

      if(!historial || historial.length === 0) {
        container.html(`
          <div class="timeline-empty">
            <i class="mdi mdi-information-outline"></i>
            Sin actualizaciones de progreso registradas
          </div>
        `);
        return;
      }

      let html = '';
      historial.forEach(function(item) {
        const fecha = new Date(item.created_at);
        const fechaStr = fecha.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });
        const horaStr = fecha.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });

        // Evento especial: aprobacion del jefe/admin
        if (item.campo_modificado === 'aprobacion') {
          const aprobadoInfo = `
            <div class="mt-1" style="font-size:12px;color:#155724;background:#d4edda;border:1px solid #c3e6cb;border-radius:8px;padding:6px 10px;">
              <strong>Aprobado por:</strong> ${item.usuario_nombre ? escapeHtml(item.usuario_nombre) : 'Jefe/Administrador'}
            </div>
          `;

          html += `
            <div class="timeline-item">
              <div class="timeline-dot" style="background: #28a745;"></div>
              <div class="timeline-content">
                <div class="timeline-text">
                  <span><i class="mdi mdi-check-circle text-success me-1"></i>Cierre aprobado</span>
                  <span class="badge-change badge-up">
                    <i class="mdi mdi-account-check"></i>
                    Verificado
                  </span>
                </div>
                <div class="timeline-date">
                  <i class="mdi mdi-calendar-clock"></i>
                  ${fechaStr} ${horaStr}
                  ${item.usuario_nombre ? '- ' + item.usuario_nombre : ''}
                </div>
                ${aprobadoInfo}
              </div>
            </div>
          `;
          return;
        }

        // Evento especial: rechazo del jefe/admin
        if (item.campo_modificado === 'rechazo') {
          const motivo = item.motivo ? escapeHtml(item.motivo) : '';
          const motivoHtml = motivo
            ? `<div class="mt-1" style="font-size:12px;color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;border-radius:8px;padding:6px 10px;"><strong>Motivo del rechazo:</strong> ${motivo}</div>`
            : '';

          html += `
            <div class="timeline-item">
              <div class="timeline-dot" style="background: #dc3545;"></div>
              <div class="timeline-content">
                <div class="timeline-text">
                  <span><i class="mdi mdi-close-circle text-danger me-1"></i>Cierre rechazado</span>
                  <span class="badge-change badge-down">
                    <i class="mdi mdi-account-cancel"></i>
                    Rechazado
                  </span>
                </div>
                <div class="timeline-date">
                  <i class="mdi mdi-calendar-clock"></i>
                  ${fechaStr} ${horaStr}
                  ${item.usuario_nombre ? '- ' + item.usuario_nombre : ''}
                </div>
                ${motivoHtml}
              </div>
            </div>
          `;
          return;
        }

        // Evento especial: transferencia (todos los estados)
        const camposTransferencia = ['transferencia', 'transferencia_aprobada', 'transferencia_rechazada', 'transferencia_pendiente'];
        if (camposTransferencia.includes(item.campo_modificado)) {
          const desde = item.valor_anterior ? escapeHtml(item.valor_anterior) : 'Sin asignar';
          const hacia = item.valor_nuevo ? escapeHtml(item.valor_nuevo) : 'Sin destino';
          const detallePartes = [];
          if (item.motivo) detallePartes.push('<strong>Motivo:</strong> ' + escapeHtml(item.motivo));
          if (item.comentario) detallePartes.push('<strong>Comentario:</strong> ' + escapeHtml(item.comentario));

          let dotColor, badgeBg, badgeColor, badgeIcon, badgeLabel, detalleStyle;
          const campo = item.campo_modificado;
          const estado = item.estado_transferencia || (campo === 'transferencia' ? 'aprobada' : campo.replace('transferencia_',''));

          if (estado === 'aprobada' || campo === 'transferencia') {
            dotColor = '#f97316';
            badgeBg = '#ffe8cc'; badgeColor = '#b45309';
            badgeIcon = 'mdi-transfer-right'; badgeLabel = 'Transferido';
            detalleStyle = 'color:#9a3412;background:#fff3e0;border:1px solid #fed7aa;';
          } else if (estado === 'rechazada') {
            dotColor = '#dc3545';
            badgeBg = '#f8d7da'; badgeColor = '#721c24';
            badgeIcon = 'mdi-transfer-right mdi-rotate-180'; badgeLabel = 'Transferencia rechazada';
            detalleStyle = 'color:#721c24;background:#f8d7da;border:1px solid #f5c6cb;';
          } else { // pendiente
            dotColor = '#fd7e14';
            badgeBg = '#fff3cd'; badgeColor = '#856404';
            badgeIcon = 'mdi-clock-outline'; badgeLabel = 'Transferencia solicitada';
            detalleStyle = 'color:#856404;background:#fff3cd;border:1px solid #ffeeba;';
          }

          const detalleHtml = detallePartes.length
            ? `<div class="mt-1" style="font-size:12px;${detalleStyle}border-radius:8px;padding:6px 10px;">${detallePartes.join('<br>')}</div>`
            : '';

          html += `
            <div class="timeline-item">
              <div class="timeline-dot" style="background: ${dotColor};"></div>
              <div class="timeline-content">
                <div class="timeline-text">
                  <span><i class="mdi mdi-swap-horizontal me-1" style="color:${dotColor};"></i>${desde} <i class="mdi mdi-arrow-right"></i> ${hacia}</span>
                  <span class="badge-change" style="background:${badgeBg};color:${badgeColor};">
                    <i class="mdi ${badgeIcon}"></i>
                    ${badgeLabel}
                  </span>
                </div>
                <div class="timeline-date">
                  <i class="mdi mdi-calendar-clock"></i>
                  ${fechaStr} ${horaStr}
                  ${item.usuario_nombre ? '- ' + escapeHtml(item.usuario_nombre) : ''}
                </div>
                ${detalleHtml}
              </div>
            </div>
          `;
          return;
        }

        // Determinar si subio o bajo
        const anterior = parseInt(item.valor_anterior) || 0;
        const nuevo = parseInt(item.valor_nuevo) || 0;
        const diff = nuevo - anterior;
        const isUp = diff > 0;

        // Color del punto segun el nuevo valor
        let dotColor = '#dc3545';
        if(nuevo >= 75) dotColor = '#28a745';
        else if(nuevo >= 50) dotColor = '#ffc107';
        else if(nuevo >= 25) dotColor = '#fd7e14';

        html += `
          <div class="timeline-item">
            <div class="timeline-dot" style="background: ${dotColor};"></div>
            <div class="timeline-content">
              <div class="timeline-text">
                <span>${item.valor_anterior || '0%'} <i class="mdi mdi-arrow-right"></i> ${item.valor_nuevo || '0%'}</span>
                <span class="badge-change ${isUp ? 'badge-up' : 'badge-down'}">
                  <i class="mdi ${isUp ? 'mdi-arrow-up' : 'mdi-arrow-down'}"></i>
                  ${isUp ? '+' : ''}${diff}%
                </span>
              </div>
              <div class="timeline-date">
                <i class="mdi mdi-calendar-clock"></i>
                ${fechaStr} ${horaStr}
                ${item.usuario_nombre ? '- ' + item.usuario_nombre : ''}
              </div>
            </div>
          </div>
        `;
      });

      container.html(html);
    }

    // Inicializar barra de progreso al cargar
    let originalProgress = <?php echo $ticket['progreso'] ?? 0; ?>;

    $(document).ready(function() {
      updateProgressBar(originalProgress);
      cargarHistorialProgreso();

      // Input de progreso
      $('#progressInput').on('input', function() {
        let newValue = parseInt($(this).val()) || 0;
        // Limitar entre 0 y 100
        if(newValue < 0) newValue = 0;
        if(newValue > 100) newValue = 100;
        $(this).val(newValue);
        updateProgressBar(newValue);
        toggleUpdateButton(newValue);
      });

      // Boton actualizar progreso
      $('#btnUpdateProgress').on('click', function() {
        const newProgress = parseInt($('#progressInput').val());
        actualizarProgreso(newProgress);
      });

      // Boton cerrar ticket
      $('#btnCloseTicket').on('click', function() {
        cerrarTicket();
      });
    });

    function toggleUpdateButton(newValue) {
      if(newValue !== originalProgress) {
        $('#btnUpdateProgress').prop('disabled', false);
      } else {
        $('#btnUpdateProgress').prop('disabled', true);
      }
    }

    function actualizarProgreso(newProgress) {
      const btn = $('#btnUpdateProgress');
      btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-1"></i>Guardando...');

      $.ajax({
        url: 'api/tickets.php?action=actualizar_progreso',
        method: 'POST',
        data: {
          codigo: '<?php echo $ticket['codigo']; ?>',
          progreso: newProgress
        },
        success: function(response) {
          if(response.success) {
            if (response.pendiente_aprobacion == 1 || response.nuevo_estado == 4) {
              mostrarNotificacion('Ticket enviado a pendiente de verificacion', 'success');
              setTimeout(() => location.reload(), 700);
              return;
            }

            originalProgress = newProgress;
            btn.html('<i class="mdi mdi-check me-1"></i>Actualizar').prop('disabled', true);

            // Recargar historial
            cargarHistorialProgreso();

            // Actualizar badge en header
            $('.info-badge span:contains("Progreso")').text('Progreso: ' + newProgress + '%');

            // Si llego a 100%, deshabilitar boton cerrar
            if(newProgress === 100) {
              $('#btnCloseTicket').prop('disabled', true);
            }

            // Mostrar notificacion
            mostrarNotificacion('Progreso actualizado a ' + newProgress + '%', 'success');
          } else {
            btn.html('<i class="mdi mdi-check me-1"></i>Actualizar').prop('disabled', false);
            mostrarNotificacion(response.message || 'Error al actualizar', 'error');
          }
        },
        error: function() {
          btn.html('<i class="mdi mdi-check me-1"></i>Actualizar').prop('disabled', false);
          mostrarNotificacion('Error de conexion', 'error');
        }
      });
    }

    function cerrarTicket() {
      // Mostrar modal de confirmacion
      modalConfirmarCerrar.show();
    }

    // Evento para confirmar cierre de ticket
    $('#btnConfirmarCerrar').on('click', function() {
      modalConfirmarCerrar.hide();
      modalCerrando.show();

      $.ajax({
        url: 'api/tickets.php?action=cerrar_ticket',
        method: 'POST',
        dataType: 'json',
        data: {
          codigo: '<?php echo $ticket['codigo']; ?>'
        },
        success: function(response) {
          modalCerrando.hide();
          if(response.success) {
            modalTicketCerrado.show();
          } else {
            $('#errorMessage').text(response.message || 'Error al cerrar el ticket');
            modalError.show();
          }
        },
        error: function(xhr, status, error) {
          console.error('Error:', status, error, xhr.responseText);
          modalCerrando.hide();
          $('#errorMessage').text('Error de conexion con el servidor');
          modalError.show();
        }
      });
    });

    function mostrarNotificacion(mensaje, tipo) {
      const bgColor = tipo === 'success' ? '#28a745' : '#dc3545';
      const icon = tipo === 'success' ? 'mdi-check-circle' : 'mdi-alert-circle';

      const notif = $(`
        <div class="notif-toast" style="position: fixed; bottom: 20px; right: 20px; background: ${bgColor}; color: white; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); z-index: 9999; display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease;">
          <i class="mdi ${icon}" style="font-size: 20px;"></i>
          <span>${mensaje}</span>
        </div>
      `);

      $('body').append(notif);
      setTimeout(() => notif.fadeOut(300, () => notif.remove()), 3000);
    }

    // ========== ELIMINAR COMENTARIO ==========
    function eliminarComentario(comentarioId) {
      if (comentariosBloqueados) {
        alert('No se pueden eliminar mensajes porque este ticket ya paso por verificacion/aprobacion.');
        return;
      }

      if(!confirm('Esta seguro de eliminar este comentario?\n\nEsta accion no se puede deshacer.')) {
        return;
      }

      $.ajax({
        url: 'api/comentarios.php?action=eliminar',
        method: 'POST',
        data: { comentario_id: comentarioId },
        success: function(response) {
          if(response.success) {
            cargarComentarios();
          } else {
            alert('Error: ' + (response.message || 'No se pudo eliminar el comentario'));
          }
        },
        error: function() {
          alert('Error de conexion');
        }
      });
    }

    // ========== VERIFICACION DE TICKET (APROBAR / RECHAZAR) ==========
    function abrirModalVerificacion(tipo) {
      tipoVerificacion = tipo;
      $('#comentarioVerificacion').val('');
      limpiarRechazoArchivo();

      if(tipo === 'aprobar') {
        $('#verificacionIcon').css('background', '#d4edda').find('i').css('color', '#198754').removeClass('mdi-close-circle').addClass('mdi-check-circle');
        $('#verificacionTitulo').css('color', '#198754').text('Aprobar Ticket');
        $('#verificacionSubtitulo').text('Confirma que el ticket ha sido resuelto correctamente?');
        $('#comentarioRequerido').hide();
        $('#comentarioAyuda').text('Puede agregar un comentario opcional.');
        $('#rechazoFileWrap').hide();
        $('#btnAprobarVerificacion').show();
        $('#btnRechazarVerificacion').hide();
      } else {
        $('#verificacionIcon').css('background', '#f8d7da').find('i').css('color', '#dc3545').removeClass('mdi-check-circle').addClass('mdi-close-circle');
        $('#verificacionTitulo').css('color', '#dc3545').text('Rechazar Ticket');
        $('#verificacionSubtitulo').text('El ticket no fue resuelto correctamente?');
        $('#comentarioRequerido').show();
        $('#comentarioAyuda').text('Debe indicar el motivo del rechazo. Este comentario aparecera en el seguimiento.');
        $('#rechazoFileWrap').show();
        $('#btnAprobarVerificacion').hide();
        $('#btnRechazarVerificacion').show();
      }

      if(modalVerificacion) modalVerificacion.show();
    }

    $('#modalVerificacion').on('hidden.bs.modal', function() {
      $('#comentarioVerificacion').val('');
      $('#rechazoFileWrap').hide();
      limpiarRechazoArchivo();
      tipoVerificacion = null;
      $(document).off('paste.rechazoEvidencia');
    });

    $('#modalVerificacion').on('shown.bs.modal', function() {
      if(tipoVerificacion !== 'rechazar') return;
      $(document).off('paste.rechazoEvidencia').on('paste.rechazoEvidencia', function(e) {
        if(tipoVerificacion !== 'rechazar') return;
        const objetivo = document.activeElement;
        const objetivoEnModal = objetivo && $(objetivo).closest('#modalVerificacion').length > 0;
        if(!objetivoEnModal) return;
        if(capturarImagenPegada(e)) {
          e.preventDefault();
        }
      });
    });

    // Evento para aprobar
    $('#btnAprobarVerificacion').on('click', function() {
      const comentario = $('#comentarioVerificacion').val().trim();
      if(modalVerificacion) modalVerificacion.hide();
      modalProceso.show();

      $.ajax({
        url: 'api/tickets.php?action=aprobar_cierre',
        method: 'POST',
        data: {
          codigo: '<?php echo $ticket['codigo']; ?>',
          comentario: comentario
        },
        success: function(response) {
          modalProceso.hide();
          if(response.success) {
            if(modalAprobado) modalAprobado.show();
          } else {
            $('#errorMessage').text(response.message || 'Error al aprobar');
            modalError.show();
          }
        },
        error: function() {
          modalProceso.hide();
          $('#errorMessage').text('Error de conexion con el servidor');
          modalError.show();
        }
      });
    });

    // Evento para rechazar
    $('#btnRechazarVerificacion').on('click', function() {
      const comentario = $('#comentarioVerificacion').val().trim();

      if(!comentario) {
        alert('Debe indicar el motivo del rechazo');
        return;
      }

      if(modalVerificacion) modalVerificacion.hide();
      modalProceso.show();

      const formData = new FormData();
      formData.append('codigo', '<?php echo $ticket['codigo']; ?>');
      formData.append('comentario', comentario);
      if(rechazoArchivo) {
        formData.append('archivo_rechazo', rechazoArchivo);
      }

      $.ajax({
        url: 'api/tickets.php?action=rechazar_cierre',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
          modalProceso.hide();
          if(response.success) {
            if(modalRechazado) modalRechazado.show();
          } else {
            $('#errorMessage').text(response.message || 'Error al rechazar');
            modalError.show();
          }
        },
        error: function() {
          modalProceso.hide();
          $('#errorMessage').text('Error de conexion con el servidor');
          modalError.show();
        }
      });
    });
  </script>
  <script src="assets/js/notificaciones.js?v=<?php echo time(); ?>"></script>
<script src="js/sidebar-badges.js"></script>
</body>
</html>






