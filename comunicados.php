<?php
require_once 'config/session.php';
session_start();
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/EmailHelper.php';

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
$departamento_usuario = $_SESSION['departamento_id'] ?? 1;

$departamento_nombre = 'General';
$stmt = $db->prepare("SELECT nombre FROM departamentos WHERE id = ?");
$stmt->execute([$departamento_usuario]);
if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $departamento_nombre = $row['nombre'];
}

$puede_ver_todos = ($user_rol === 'Administrador' || $user_rol === 'Admin');
$es_jefe = ($user_rol === 'Jefe');
$es_usuario = ($user_rol === 'Usuario');
$SESSION_TIMEOUT_JS = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 120;
$SESSION_POPUP_TIMEOUT_JS = defined('SESSION_POPUP_TIMEOUT') ? SESSION_POPUP_TIMEOUT : 900;

date_default_timezone_set('America/Lima');
$hora = date('H');
$saludo = ($hora >= 5 && $hora < 12) ? 'Buenos dias' : (($hora >= 12 && $hora < 19) ? 'Buenas tardes' : 'Buenas noches');
$primer_nombre = explode(' ', $user_name)[0];
$esAdmin = ($user_rol === 'Administrador' || $user_rol === 'Admin');
$flashOk = '';
$flashErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_comunicado'])) {
    if (!$esAdmin) {
        $flashErr = 'Solo Administrador puede publicar comunicados.';
    } else {
        $titulo = trim((string)($_POST['titulo'] ?? ''));
        $contenido = trim((string)($_POST['contenido'] ?? ''));
        $tipo = trim((string)($_POST['tipo'] ?? 'informativo'));
        $fechaExpRaw = trim((string)($_POST['fecha_expiracion'] ?? ''));
        $tipos = [
            'actualizacion' => ['icono' => 'mdi-update', 'color' => '#4CAF50'],
            'mantenimiento' => ['icono' => 'mdi-wrench', 'color' => '#F59E0B'],
            'alerta' => ['icono' => 'mdi-shield-alert', 'color' => '#E91E63'],
            'informativo' => ['icono' => 'mdi-information', 'color' => '#2196F3'],
        ];
        if (!isset($tipos[$tipo])) $tipo = 'informativo';

        if ($titulo === '' || $contenido === '') {
            $flashErr = 'Titulo y contenido son obligatorios.';
        } else {
            $fechaExp = null;
            if ($fechaExpRaw !== '') {
                $ts = strtotime($fechaExpRaw);
                if ($ts !== false) $fechaExp = date('Y-m-d H:i:s', $ts);
            }
            try {
                $colsStmt = $db->query("SHOW COLUMNS FROM comunicados");
                $cols = $colsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
                $set = [];
                $params = [];
                $candidates = [
                    'titulo' => $titulo,
                    'contenido' => $contenido,
                    'tipo' => $tipo,
                    'icono' => $tipos[$tipo]['icono'],
                    'color' => $tipos[$tipo]['color'],
                    'creado_por' => (int)($_SESSION['user_id'] ?? 0),
                    'activo' => 1,
                    'fecha_expiracion' => $fechaExp,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                foreach ($candidates as $col => $value) {
                    if (!in_array($col, $cols, true)) continue;
                    if ($col === 'fecha_expiracion' && $value === null) {
                        $set[] = "$col = NULL";
                    } else {
                        $set[] = "$col = :$col";
                        $params[":$col"] = $value;
                    }
                }
                $ins = $db->prepare('INSERT INTO comunicados SET ' . implode(', ', $set));
                $ins->execute($params);

                // Notificar comunicado por correo a todos los usuarios
                EmailHelper::notifyComunicado([
                    'titulo'    => $titulo,
                    'contenido' => $contenido,
                    'tipo'      => $tipo,
                    'creado_por' => (int)($_SESSION['user_id'] ?? 0),
                ], $db);

                $flashOk = 'Comunicado publicado para todos los usuarios.';
            } catch (Throwable $e) {
                $flashErr = 'No se pudo publicar comunicado.';
            }
        }
    }
}

$stmtCom = $db->prepare("SELECT c.id, c.titulo, c.contenido, c.tipo, c.icono, c.color, c.created_at, COALESCE(u.nombre_completo,'Sistema') AS autor, CASE WHEN nl.id IS NOT NULL THEN 1 ELSE 0 END AS leido
FROM comunicados c
LEFT JOIN usuarios u ON u.id = c.creado_por
LEFT JOIN notificaciones_leidas nl ON nl.tipo = 'comunicado' AND nl.referencia_id = c.id AND nl.usuario_id = :uid
WHERE c.activo = 1 AND (c.fecha_expiracion IS NULL OR c.fecha_expiracion > NOW())
ORDER BY c.created_at DESC");
$stmtCom->execute([':uid' => (int)($_SESSION['user_id'] ?? 0)]);
$comunicadosData = $stmtCom->fetchAll(PDO::FETCH_ASSOC);
foreach ($comunicadosData as &$cm) {
    $ts = strtotime((string)$cm['created_at']);
    $cm['tiempo'] = $ts ? date('d/m/Y H:i', $ts) : '';
}
unset($cm);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIRA - Comunicados</title>
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
    .navbar .navbar-nav.ms-auto {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .navbar .navbar-nav.ms-auto > .nav-item {
      margin-left: 0 !important;
      margin-right: 0 !important;
    }
    .navbar .navbar-nav .nav-item .nav-link.count-indicator {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 28px;
      height: 28px;
      padding: 0 8px !important;
    }
    .navbar .navbar-nav .nav-item .nav-link.count-indicator i {
      font-size: 21px !important;
      line-height: 1 !important;
      color: #0f172a;
    }
    .count-indicator .badge-notif {
      position: absolute !important; top: -4px !important; right: -8px !important;
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
      padding: 0 6px; font-size: 11px; font-weight: 700; display: none; align-items: center;
      justify-content: center; line-height: 18px; margin-left: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.25);
    }
    .sidebar-badge.show { display: inline-flex; }
    .notif-item {
      padding: 10px 14px !important;
      border-bottom: 1px solid #eee;
      transition: all 0.2s ease;
      text-decoration: none !important;
    }
    .notif-item:hover { background-color: #f5f7fa !important; }
    .notif-item.unread { background-color: #eef4ff !important; }
    .notif-row { display: flex; align-items: center; gap: 10px; }
    .status-indicator { width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .status-indicator .unread-dot {
      width: 9px; height: 9px; background: #dc3545; border-radius: 50%;
      box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
    }
    .notif-icon {
      width: 38px; height: 38px; border-radius: 10px;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .notif-icon i { font-size: 18px; }
    .notif-content { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
    .notif-title { font-size: 12px; font-weight: 500; color: #333; margin: 0; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .notif-item.unread .notif-title { font-weight: 600; color: #1a1a1a; }
    .notif-subtitle { font-size: 11px; color: #666; margin: 0; line-height: 1.3; }
    .notif-time { font-size: 10px; color: #1F3BB3; display: flex; align-items: center; gap: 2px; margin-top: 1px; }

    .notif-card { background:#fff; border-radius:12px; box-shadow:0 0 12px rgba(0,0,0,.05); }
    .notif-table thead th {
      font-size: 12px;
      text-transform: uppercase;
      color: #6b7280;
      border-bottom: 1px solid #e5e7eb;
      padding: 12px 10px;
      font-weight: 700;
      white-space: nowrap;
    }
    .notif-table thead th:first-child,
    .notif-table tbody td:first-child {
      width: 78px;
      min-width: 78px;
      text-align: center;
      padding-left: 0;
      padding-right: 0;
    }
    .notif-table tbody td {
      padding: 12px 10px;
      vertical-align: middle;
      border-top: 1px solid #f1f3f5;
    }
    .notif-table tbody tr.notif-row-unread {
      background: linear-gradient(90deg, rgba(31, 59, 179, 0.12) 0%, rgba(31, 59, 179, 0.05) 45%, rgba(255, 255, 255, 1) 100%);
    }
    .notif-table tbody tr.notif-row-read {
      background: linear-gradient(90deg, rgba(40, 167, 69, 0.07) 0%, rgba(40, 167, 69, 0.02) 45%, rgba(255, 255, 255, 1) 100%);
    }
    .notif-status-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      display: block;
      background: #dc3545;
      box-shadow: 0 0 0 4px rgba(220, 53, 69, 0.14);
      margin-left: auto;
      margin-right: auto;
    }
    .notif-status-dot.blink {
      animation: notifBlink 1.05s infinite;
    }
    .notif-status-dot.read {
      background: #28a745;
      box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.12);
    }
    .notif-status-eye {
      color: #28a745;
      font-size: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 18px;
      height: 18px;
      margin-left: auto;
      margin-right: auto;
    }
    @keyframes notifBlink {
      0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.65); }
      50% { opacity: .35; box-shadow: 0 0 0 7px rgba(220, 53, 69, 0); }
    }
    .notif-icon-table {
      width: 34px;
      height: 34px;
      border-radius: 8px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-right: 10px;
      flex-shrink: 0;
    }
    .notif-ticket-cell {
      display: flex;
      align-items: center;
      min-width: 250px;
    }
    .notif-ticket-title {
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 2px;
      line-height: 1.2;
    }
    .notif-ticket-code {
      color: #6b7280;
      font-size: 12px;
    }
    .notif-msg-cell {
      color: #4b5563;
      font-size: 13px;
      min-width: 320px;
    }
    .notif-time-cell {
      color: #1f3bb3;
      font-size: 12px;
      white-space: nowrap;
    }
    .notif-empty {
      text-align: center;
      color: #6b7280;
      padding: 28px 12px;
    }
    .notif-filter-row {
      display: flex;
      gap: 10px;
      align-items: end;
      margin-bottom: 12px;
      flex-wrap: wrap;
    }
    .notif-filter-item { min-width: 180px; }
    .notif-filter-item label {
      font-size: 11px;
      font-weight: 600;
      color: #6b7280;
      margin-bottom: 4px;
      display: block;
      text-transform: uppercase;
    }
    .notif-filter-item .form-control {
      height: 36px;
      font-size: 13px;
      border-radius: 8px;
    }
    .notif-pagination-wrap {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 14px;
      gap: 12px;
      flex-wrap: wrap;
    }
    .notif-pagination-info {
      font-size: 13px;
      color: #6b7280;
    }
    .notif-pagination .btn {
      min-width: 36px;
      height: 34px;
      padding: 0 10px;
      border-radius: 8px;
      margin-left: 4px;
    }
    .notif-pagination .btn.active {
      background: #1f3bb3;
      color: #fff;
      border-color: #1f3bb3;
    }
    #notifClearFilters {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      text-align: center;
    }
    .notif-row-item { display:flex; gap:12px; align-items:flex-start; padding:14px 16px; border-bottom:1px solid #edf0f4; }
    .notif-row-item:last-child { border-bottom:none; }
    .notif-dot { width:10px; height:10px; border-radius:50%; margin-top:8px; flex-shrink:0; background:#dc3545; box-shadow:0 0 0 4px rgba(220,53,69,.14); }
    .notif-row-item.leido .notif-dot { background:#28a745; box-shadow:0 0 0 4px rgba(40,167,69,.12); }
    .notif-icon-box { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    .notif-content-main { flex:1; min-width:0; }
    .notif-title { font-weight:700; color:#1f2937; margin-bottom:2px; }
    .notif-msg { color:#6b7280; font-size:13px; margin-bottom:2px; }
    .notif-time { color:#1f3bb3; font-size:12px; }
    .notif-link { color:#1f3bb3; font-size:12px; text-decoration:none; font-weight:600; }
    .pill-com { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:600; margin-left:8px; }
    .pill-actualizacion { background:rgba(76,175,80,.14); color:#2f7d32; }
    .pill-mantenimiento { background:rgba(245,158,11,.16); color:#b45309; }
    .pill-alerta { background:rgba(233,30,99,.14); color:#be185d; }
    .pill-informativo { background:rgba(33,150,243,.14); color:#1e64b5; }
    .publish-card {
      border: 1px solid #dde7ff;
      border-radius: 14px;
      background: linear-gradient(180deg, #f8fbff 0%, #ffffff 42%);
      box-shadow: 0 8px 24px rgba(31, 59, 179, 0.08);
      overflow: hidden;
    }
    .publish-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 14px 16px;
      border-bottom: 1px solid #edf2ff;
      background: linear-gradient(90deg, rgba(31,59,179,.08), rgba(31,59,179,.02));
    }
    .publish-title {
      margin: 0;
      font-size: 22px;
      font-weight: 800;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .publish-sub {
      margin: 2px 0 0;
      font-size: 13px;
      color: #64748b;
    }
    .publish-badge {
      font-size: 11px;
      font-weight: 700;
      padding: 5px 10px;
      border-radius: 999px;
      color: #1f3bb3;
      background: rgba(31,59,179,.12);
      border: 1px solid rgba(31,59,179,.15);
    }
    .publish-card .card-body { padding: 16px; }
    .publish-card .form-label {
      font-size: 12px;
      font-weight: 700;
      color: #334155;
      margin-bottom: 6px;
    }
    .publish-card .req { color: #dc2626; }
    .publish-card .form-control,
    .publish-card .form-select {
      border-radius: 10px;
      border: 1px solid #dbe3f3;
      min-height: 46px;
      font-size: 15px;
      box-shadow: none;
    }
    .publish-card textarea.form-control {
      min-height: 120px;
      resize: vertical;
      line-height: 1.45;
    }
    .publish-card .form-control:focus,
    .publish-card .form-select:focus {
      border-color: #97b3ff;
      box-shadow: 0 0 0 3px rgba(31,59,179,.10);
    }
    .publish-help {
      margin-top: 6px;
      font-size: 12px;
      color: #64748b;
    }
    .btn-publish {
      min-width: 250px;
      min-height: 50px;
      border-radius: 12px;
      font-size: 18px;
      font-weight: 700;
      box-shadow: 0 8px 18px rgba(31,59,179,.26);
    }
  </style>
</head>
<body>
<div class="container-scroller">
  <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
      <div class="me-3"><button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize"><span class="icon-menu"></span></button></div>
      <div>
        <a class="navbar-brand brand-logo" href="dashboard.php"><img src="template/images/logo.svg" alt="logo" /></a>
        <a class="navbar-brand brand-logo-mini" href="dashboard.php"><img src="template/images/logo-mini.svg" alt="logo" /></a>
      </div>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-top">
      <ul class="navbar-nav">
        <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
          <h1 class="welcome-text"><?php echo $saludo; ?>, <span class="text-black fw-bold"><?php echo htmlspecialchars($primer_nombre); ?></span></h1>
          <h3 class="welcome-sub-text">Historial completo de comunicados</h3>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item d-none d-lg-block">
            <span class="nav-link dropdown-bordered" style="cursor: default; background-color: #e9ecef; opacity: 0.9; pointer-events: none;">
              <i class="mdi mdi-office-building me-1"></i> General
            </span>
        </li>

        <script>
            window.USER_ROL = '<?php echo $user_rol; ?>';
            window.USER_DEPARTAMENTO = <?php echo $departamento_usuario; ?>;
            window.PUEDE_VER_TODOS = <?php echo $puede_ver_todos ? 'true' : 'false'; ?>;
            window.CURRENT_USER_ID = <?php echo intval($_SESSION['user_id'] ?? 0); ?>;
        </script>

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
              <a href="comunicados.php" class="btn btn-sm btn-primary" style="font-size: 10px; padding: 3px 10px;">
                Ver todos <i class="mdi mdi-arrow-right"></i>
              </a>
            </div>
            <div id="comunicadosContainer" style="max-height: 280px; overflow-y: auto;">
              <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
            </div>
          </div>
        </li>

        <li class="nav-item dropdown">
          <a class="nav-link count-indicator" id="ticketsDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="icon-bell icon-lg"></i>
            <span class="count count-tickets badge-notif"></span>
          </a>
          <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="ticketsDropdown" style="width: 340px;">
            <div class="dropdown-item py-2 border-bottom d-flex justify-content-between align-items-center" style="cursor: default; background: #f8f9fa;">
              <div class="d-flex align-items-center">
                <i class="mdi mdi-bell-outline text-primary me-2" style="font-size: 18px;"></i>
                <span class="font-weight-bold" style="font-size: 13px;">Notificaciones</span>
              </div>
              <a href="tickets.php" class="btn btn-sm btn-primary" style="font-size: 10px; padding: 3px 10px;">
                Ver tickets <i class="mdi mdi-arrow-right"></i>
              </a>
            </div>
            <div id="ticketsNotificacionesContainer" style="max-height: 280px; overflow-y: auto;">
              <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
            </div>
          </div>
        </li>

        <li class="nav-item dropdown d-none d-lg-block user-dropdown">
          <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
            <img class="img-xs rounded-circle" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=667eea&color=fff&size=128" alt="Profile image"> </a>
          <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
            <div class="dropdown-header text-center">
              <img class="img-md rounded-circle" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=667eea&color=fff&size=128" alt="Profile image">
              <p class="mb-1 mt-3 font-weight-semibold"><?php echo htmlspecialchars($user_name); ?></p>
              <p class="fw-light text-muted mb-0"><?php echo htmlspecialchars($_SESSION["user_email"] ?? ""); ?></p>
            </div>
            <a class="dropdown-item" href="perfil.php"><i class="dropdown-item-icon mdi mdi-account-outline text-primary me-2"></i> Mi Perfil <span class="badge badge-pill badge-danger">1</span></a>
            <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-message-text-outline text-primary me-2"></i> Mensajes</a>
            <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-calendar-check-outline text-primary me-2"></i> Actividad</a>
            <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-help-circle-outline text-primary me-2"></i> FAQ</a>
            <a class="dropdown-item" href="api/logout.php"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i>Cerrar Sesion</a>
          </div>
        </li>
      </ul>
      <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas"><span class="mdi mdi-menu"></span></button>
    </div>
  </nav>

  <div class="container-fluid page-body-wrapper">
    <nav class="sidebar sidebar-offcanvas" id="sidebar">
      <ul class="nav">
        <li class="nav-item">
          <a class="nav-link" href="dashboard.php">
            <i class="mdi mdi-view-dashboard menu-icon"></i>
            <span class="menu-title">Dashboard</span>
          </a>
        </li>

        <li class="nav-item nav-category">GESTION DE TICKETS</li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#tickets-menu" aria-expanded="true" aria-controls="tickets-menu">
            <i class="menu-icon mdi mdi-ticket-confirmation"></i>
            <span class="menu-title">Tickets</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse show" id="tickets-menu">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-between" href="tickets.php">
                  <span>Todos los Tickets</span>
                  <span class="count-todos-sidebar sidebar-badge">0</span>
                </a>
              </li>
              <li class="nav-item"><a class="nav-link" href="tickets-create.php">Crear Ticket</a></li>
              <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?>
              <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="tickets-mis.php"><span>Mis Tickets</span><span class="count-mis-sidebar sidebar-badge">0</span></a></li>
              <?php endif; ?>
              <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="tickets-asignados.php"><span>Asignados a Mi</span><span class="count-asignados-sidebar sidebar-badge">0</span></a></li>
              <li class="nav-item">
                <a class="nav-link d-flex align-items-center justify-content-between" href="notificaciones.php">
                  <span>Notificaciones</span>
                  <span class="count-notificaciones-sidebar sidebar-badge">0</span>
                </a>
              </li>
            </ul>
          </div>
        </li>

        <?php if ($user_rol === 'Administrador' || $user_rol === 'Jefe'): ?>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#usuarios-menu" aria-expanded="false" aria-controls="usuarios-menu">
            <i class="menu-icon mdi mdi-account-multiple"></i>
            <span class="menu-title">Usuarios</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse" id="usuarios-menu">
            <ul class="nav flex-column sub-menu">
              <?php if ($user_rol === 'Administrador'): ?>
              <li class="nav-item"> <a class="nav-link" href="usuarios-create.php">Crear Usuario</a></li>
              <?php endif; ?>
              <li class="nav-item"> <a class="nav-link" href="usuarios.php">Lista de Usuarios</a></li>
            </ul>
          </div>
        </li>
        <?php endif; ?>

        <?php if ($user_rol === 'Administrador'): ?>
        <li class="nav-item nav-category">CONFIGURACION</li>
        <li class="nav-item"><a class="nav-link" href="perfil.php"><i class="menu-icon mdi mdi-account-circle-outline"></i><span class="menu-title">Perfil</span></a></li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#catalogos-menu" aria-expanded="false" aria-controls="catalogos-menu">
            <i class="menu-icon mdi mdi-table-settings"></i>
            <span class="menu-title">Catalogos</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse" id="catalogos-menu">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"> <a class="nav-link" href="catalogos-departamentos.php">Departamentos</a></li>
              <li class="nav-item"> <a class="nav-link" href="catalogos-canales.php">Canales de Atencion</a></li>
              <li class="nav-item"> <a class="nav-link" href="catalogos-actividades.php">Tipos de Actividad</a></li>
              <li class="nav-item"> <a class="nav-link" href="catalogos-fallas.php">Tipos de Falla</a></li>
              <li class="nav-item"> <a class="nav-link" href="catalogos-ubicaciones.php">Ubicaciones</a></li>
              <li class="nav-item"> <a class="nav-link" href="catalogos-equipos.php">Equipos</a></li>
            </ul>
          </div>
        </li>
        <?php endif; ?>

        <li class="nav-item nav-category">Reportes</li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#reportes-menu" aria-expanded="false" aria-controls="reportes-menu">
            <i class="menu-icon mdi mdi-chart-line"></i>
            <span class="menu-title">Reportes</span>
            <i class="menu-arrow"></i>
          </a>
          <div class="collapse" id="reportes-menu">
            <ul class="nav flex-column sub-menu">
              <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?>
              <li class="nav-item"> <a class="nav-link" href="reportes-general.php">Reporte General</a></li>
              <li class="nav-item"> <a class="nav-link" href="reportes-departamento.php">Por Departamento</a></li>
              <?php endif; ?>
              <li class="nav-item"> <a class="nav-link" href="reportes-usuario.php">Por Usuario</a></li>
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
              <li class="nav-item"><a class="nav-link active d-flex align-items-center justify-content-between" href="comunicados.php"><span>Avisos</span><span class="count-comunicados-sidebar sidebar-badge">0</span></a></li>
            </ul>
          </div>
        </li>
<li class="nav-item nav-category">AYUDA</li>
        <li class="nav-item">
          <a class="nav-link" href="documentacion.php">
            <i class="menu-icon mdi mdi-file-document"></i>
            <span class="menu-title">Documentacion</span>
          </a>
        </li>
      </ul>
    </nav>

    <div class="main-panel">
      <div class="content-wrapper">
        <?php if ($flashOk !== ''): ?>
          <div class="alert alert-success"><?php echo htmlspecialchars($flashOk); ?></div>
        <?php endif; ?>
        <?php if ($flashErr !== ''): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($flashErr); ?></div>
        <?php endif; ?>

        <?php if ($esAdmin): ?>
        <div class="row mb-3">
          <div class="col-md-12 stretch-card">
            <div class="card publish-card">
              <div class="publish-head">
                <div>
                  <h4 class="publish-title"><i class="mdi mdi-bullhorn"></i>Publicar comunicado global</h4>
                  <p class="publish-sub">Este aviso se enviara a todos los usuarios del sistema.</p>
                </div>
                <span class="publish-badge">Administrador</span>
              </div>
              <div class="card-body">
                <form method="post" action="comunicados.php">
                  <input type="hidden" name="crear_comunicado" value="1">
                  <div class="row g-3">
                    <div class="col-md-5">
                      <label class="form-label">Titulo <span class="req">*</span></label>
                      <input type="text" name="titulo" class="form-control" maxlength="180" placeholder="Ej: Nueva actualizacion de seguridad v2.6" required>
                    </div>
                    <div class="col-md-3">
                      <label class="form-label">Tipo <span class="req">*</span></label>
                      <select name="tipo" class="form-select" required>
                        <option value="actualizacion">Actualizacion</option>
                        <option value="mantenimiento">Mantenimiento</option>
                        <option value="alerta">Alerta</option>
                        <option value="informativo" selected>Informativo</option>
                      </select>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Expira (opcional)</label>
                      <input type="datetime-local" name="fecha_expiracion" class="form-control">
                    </div>
                    <div class="col-md-12">
                      <label class="form-label">Contenido <span class="req">*</span></label>
                      <textarea name="contenido" class="form-control" rows="4" placeholder="Describe el comunicado que veran todos los usuarios..." required></textarea>
                      <div class="publish-help">Recomendado: detallar accion esperada, impacto y fecha de vigencia.</div>
                    </div>
                    <div class="col-md-12 d-flex justify-content-end">
                      <button type="submit" class="btn btn-primary btn-publish"><i class="mdi mdi-send me-1"></i>Publicar para todos</button>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <div class="row">
          <div class="col-md-12 stretch-card">
            <div class="card notif-card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="card-title mb-0"><i class="mdi mdi-email-outline me-1"></i>Historial de comunicados</h4>
                  <button class="btn btn-outline-primary btn-sm" id="btnRefreshCom"><i class="mdi mdi-refresh me-1"></i>Actualizar</button>
                </div>
                <div class="notif-filter-row">
                  <div class="notif-filter-item" style="min-width:260px;">
                    <label>Buscar</label>
                    <input type="text" id="comSearch" class="form-control" placeholder="Titulo o detalle...">
                  </div>
                  <div class="notif-filter-item">
                    <label>Tipo</label>
                    <select id="comTypeFilter" class="form-control">
                      <option value="">Todos</option>
                      <option value="actualizacion">Actualizacion</option>
                      <option value="mantenimiento">Mantenimiento</option>
                      <option value="alerta">Alerta</option>
                      <option value="informativo">Informativo</option>
                    </select>
                  </div>
                  <div class="notif-filter-item">
                    <label>Estado</label>
                    <select id="comReadFilter" class="form-control">
                      <option value="">Todos</option>
                      <option value="unread">No leidos</option>
                      <option value="read">Leidos</option>
                    </select>
                  </div>
                  <div class="notif-filter-item" style="min-width:120px;">
                    <button type="button" class="btn btn-outline-secondary w-100" id="notifClearFilters" style="height:36px;">Limpiar</button>
                  </div>
                </div>
                <div class="table-responsive">
                  <table class="table notif-table mb-0">
                    <thead>
                      <tr>
                        <th>Estado</th>
                        <th>Comunicado</th>
                        <th>Tipo</th>
                        <th>Detalle</th>
                        <th>Fecha</th>
                        <th>Accion</th>
                      </tr>
                    </thead>
                    <tbody id="comHistoryContainer"></tbody>
                  </table>
                </div>
                <div class="notif-pagination-wrap">
                  <div class="notif-pagination-info" id="comPaginationInfo">Mostrando 0 de 0 comunicados</div>
                  <div class="notif-pagination" id="comPagination"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <footer class="footer"><div class="d-sm-flex justify-content-center justify-content-sm-between"><span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Portal SIRA <?php echo date('Y'); ?></span></div></footer>
    </div>
  </div>
</div>

<script src="template/vendors/js/vendor.bundle.base.js"></script>
<script src="template/js/off-canvas.js"></script>
<script src="template/js/hoverable-collapse.js"></script>
<script src="template/js/template.js"></script>
<script>
  const SESSION_TIMEOUT = <?php echo $SESSION_TIMEOUT_JS; ?>;
  const SESSION_POPUP_TIMEOUT = <?php echo $SESSION_POPUP_TIMEOUT_JS; ?>;
</script>
<script src="assets/js/session-manager.js"></script>
<script src="assets/js/notificaciones.js?v=<?php echo time(); ?>"></script>
<script>
(function(){
  const PAGE_SIZE = 10;
  let allItems = <?php echo json_encode($comunicadosData, JSON_UNESCAPED_UNICODE); ?>;
  let filteredItems = [];
  let currentPage = 1;

  function esc(v){ return String(v ?? ''); }
  function getTypeMeta(tipo) {
    switch(String(tipo || '').toLowerCase()){
      case 'actualizacion': return { icon:'mdi-update', bg:'rgba(76,175,80,.12)', color:'#2f7d32', pill:'pill-actualizacion', label:'Actualizacion' };
      case 'mantenimiento': return { icon:'mdi-wrench', bg:'rgba(245,158,11,.16)', color:'#b45309', pill:'pill-mantenimiento', label:'Mantenimiento' };
      case 'alerta': return { icon:'mdi-shield-alert', bg:'rgba(233,30,99,.14)', color:'#be185d', pill:'pill-alerta', label:'Alerta' };
      default: return { icon:'mdi-information', bg:'rgba(33,150,243,.14)', color:'#1e64b5', pill:'pill-informativo', label:'Informativo' };
    }
  }

  function getFilteredItems() {
    const q = (document.getElementById('comSearch')?.value || '').toLowerCase().trim();
    const typeFilter = document.getElementById('comTypeFilter')?.value || '';
    const readFilter = document.getElementById('comReadFilter')?.value || '';

    return allItems.filter(n => {
      if (typeFilter && String(n.tipo || '').toLowerCase() !== typeFilter) return false;
      if (readFilter === 'read' && !Number(n.leido)) return false;
      if (readFilter === 'unread' && Number(n.leido)) return false;

      if (q) {
        const haystack = `${n.titulo || ''} ${n.contenido || ''}`.toLowerCase();
        if (!haystack.includes(q)) return false;
      }
      return true;
    });
  }

  function renderPagination(totalItems){
    const pages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));
    if (currentPage > pages) currentPage = pages;
    const wrap = document.getElementById('comPagination');
    if (!wrap) return;

    let html = '';
    html += `<button class="btn btn-outline-secondary" ${currentPage === 1 ? 'disabled' : ''} data-page="${currentPage - 1}">�</button>`;
    const start = Math.max(1, currentPage - 2);
    const end = Math.min(pages, start + 4);
    for (let p = start; p <= end; p++) {
      html += `<button class="btn btn-outline-secondary ${p === currentPage ? 'active' : ''}" data-page="${p}">${p}</button>`;
    }
    html += `<button class="btn btn-outline-secondary" ${currentPage === pages ? 'disabled' : ''} data-page="${currentPage + 1}">�</button>`;
    wrap.innerHTML = html;

    wrap.querySelectorAll('button[data-page]').forEach(btn => {
      btn.addEventListener('click', function() {
        const p = parseInt(this.getAttribute('data-page'), 10);
        if (!isNaN(p) && p >= 1) {
          currentPage = p;
          renderHistoryPage();
        }
      });
    });
  }

  function renderHistoryPage(){
    const el = document.getElementById('comHistoryContainer');
    filteredItems = getFilteredItems();
    const total = filteredItems.length;

    if(!filteredItems.length){
      el.innerHTML = '<tr><td colspan="6" class="notif-empty"><i class="mdi mdi-email-off-outline" style="font-size:28px;display:block;margin-bottom:8px;"></i>No hay comunicados</td></tr>';
      document.getElementById('comPaginationInfo').textContent = 'Mostrando 0 de 0 comunicados';
      document.getElementById('comPagination').innerHTML = '';
      return;
    }

    const startIndex = (currentPage - 1) * PAGE_SIZE;
    const pageItems = filteredItems.slice(startIndex, startIndex + PAGE_SIZE);
    let html='';
    pageItems.forEach(n => {
      const meta = getTypeMeta(n.tipo);
      const rowClass = Number(n.leido) ? 'notif-row-read' : 'notif-row-unread';
      const estadoHtml = Number(n.leido)
        ? '<i class="mdi mdi-eye-check-outline notif-status-eye"></i>'
        : '<span class="notif-status-dot blink"></span>';
      const actionHtml = Number(n.leido)
        ? '<span class="text-success" style="font-size:12px;font-weight:600;">Visto</span>'
        : `<a class="notif-link js-mark-read" href="#" data-id="${n.id}">Marcar visto</a>`;
      html += `
        <tr class="${rowClass}">
          <td>${estadoHtml}</td>
          <td>
            <div class="notif-ticket-cell">
              <span class="notif-icon-table" style="background:${meta.bg};"><i class="mdi ${esc(n.icono || meta.icon)}" style="color:${esc(n.color || meta.color)};font-size:18px;"></i></span>
              <div>
                <div class="notif-ticket-title">${esc(n.titulo || 'Comunicado')}</div>
                <div class="notif-ticket-code">#COM-${n.id}</div>
              </div>
            </div>
          </td>
          <td><span class="pill-com ${meta.pill}">${meta.label}</span></td>
          <td class="notif-msg-cell">${esc(n.contenido || '')}</td>
          <td class="notif-time-cell"><i class="mdi mdi-clock-outline"></i> ${esc(n.tiempo || '')}</td>
          <td>${actionHtml}</td>
        </tr>`;
    });
    el.innerHTML = html;
    const shownFrom = startIndex + 1;
    const shownTo = Math.min(startIndex + PAGE_SIZE, total);
    document.getElementById('comPaginationInfo').textContent = `Mostrando ${shownFrom}-${shownTo} de ${total} comunicados`;
    renderPagination(total);

    el.querySelectorAll('.js-mark-read').forEach(a => {
      a.addEventListener('click', function(e){
        e.preventDefault();
        const id = Number(this.dataset.id || 0);
        if (!id) return;
        fetch('api/marcar_leido.php', {
          method:'POST',
          headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
          body: `tipo=comunicado&referencia_id=${encodeURIComponent(id)}`
        }).finally(() => {
          const idx = allItems.findIndex(x => Number(x.id) === id);
          if (idx >= 0) allItems[idx].leido = 1;
          renderHistoryPage();
        });
      });
    });
  }

  function loadHistory(){ currentPage = 1; renderHistoryPage(); }

  function bindFilters(){
    const onChange = () => {
      currentPage = 1;
      renderHistoryPage();
    };
    document.getElementById('comSearch')?.addEventListener('input', onChange);
    document.getElementById('comTypeFilter')?.addEventListener('change', onChange);
    document.getElementById('comReadFilter')?.addEventListener('change', onChange);
    document.getElementById('notifClearFilters')?.addEventListener('click', () => {
      document.getElementById('comSearch').value = '';
      document.getElementById('comTypeFilter').value = '';
      document.getElementById('comReadFilter').value = '';
      onChange();
    });
  }

  document.getElementById('btnRefreshCom')?.addEventListener('click', loadHistory);
  bindFilters();
  loadHistory();
})();
</script>
<script src="js/sidebar-badges.js"></script>
</body>
</html>




