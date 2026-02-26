<?php
require_once 'config/session.php';
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIRA - Historial de Notificaciones</title>
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
    .notif-pill { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:600; margin-left:8px; }
    .pill-nuevo { background:rgba(76,175,80,.14); color:#2f7d32; }
    .pill-asignado { background:rgba(33,150,243,.14); color:#1e64b5; }
    .pill-aprobado { background:rgba(40,167,69,.14); color:#1f7a35; }
    .pill-rechazado { background:rgba(220,53,69,.14); color:#b4232f; }
    .pill-transferido { background:rgba(249,115,22,.14); color:#b45309; }
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
          <h3 class="welcome-sub-text">Historial completo de notificaciones</h3>
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
                <a class="nav-link active d-flex align-items-center justify-content-between" href="notificaciones.php">
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
              <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="comunicados.php"><span>Avisos</span><span class="count-comunicados-sidebar sidebar-badge">0</span></a></li>
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
        <div class="row">
          <div class="col-md-12 stretch-card">
            <div class="card notif-card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                  <h4 class="card-title mb-0"><i class="mdi mdi-history me-1"></i>Historial de Notificaciones</h4>
                  <button class="btn btn-outline-primary btn-sm" id="btnRefreshNotifs"><i class="mdi mdi-refresh me-1"></i>Actualizar</button>
                </div>
                <div class="notif-filter-row">
                  <div class="notif-filter-item" style="min-width:260px;">
                    <label>Buscar</label>
                    <input type="text" id="notifSearch" class="form-control" placeholder="Codigo, titulo o detalle...">
                  </div>
                  <div class="notif-filter-item">
                    <label>Tipo</label>
                    <select id="notifTypeFilter" class="form-control">
                      <option value="">Todos</option>
                      <option value="nuevo">Creado</option>
                      <option value="asignado">Asignado</option>
                      <option value="aprobado">Aprobado</option>
                      <option value="rechazado">Rechazado</option>
                      <option value="transferido">Transferido</option>
                    </select>
                  </div>
                  <div class="notif-filter-item">
                    <label>Estado</label>
                    <select id="notifReadFilter" class="form-control">
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
                        <th>Ticket</th>
                        <th>Tipo</th>
                        <th>Detalle</th>
                        <th>Fecha</th>
                        <th>Accion</th>
                      </tr>
                    </thead>
                    <tbody id="notifHistoryContainer">
                      <tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></td></tr>
                    </tbody>
                  </table>
                </div>
                <div class="notif-pagination-wrap">
                  <div class="notif-pagination-info" id="notifPaginationInfo">Mostrando 0 de 0 notificaciones</div>
                  <div class="notif-pagination" id="notifPagination"></div>
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
  let allItems = [];
  let filteredItems = [];
  let currentPage = 1;

  function getTypeMeta(tipo) {
    switch(tipo){
      case 'nuevo': return { icon:'mdi-ticket-confirmation', bg:'rgba(76,175,80,.12)', color:'#2f7d32', pill:'pill-nuevo', label:'Creado' };
      case 'asignado': return { icon:'mdi-account-arrow-right', bg:'rgba(33,150,243,.12)', color:'#1e64b5', pill:'pill-asignado', label:'Asignado' };
      case 'aprobado': return { icon:'mdi-check-decagram', bg:'rgba(40,167,69,.14)', color:'#1f7a35', pill:'pill-aprobado', label:'Aprobado' };
      case 'rechazado': return { icon:'mdi-close-octagon', bg:'rgba(220,53,69,.14)', color:'#b4232f', pill:'pill-rechazado', label:'Rechazado' };
      case 'transferido': return { icon:'mdi-swap-horizontal', bg:'rgba(249,115,22,.14)', color:'#b45309', pill:'pill-transferido', label:'Transferido' };
      default: return { icon:'mdi-bell-outline', bg:'rgba(107,114,128,.12)', color:'#6b7280', pill:'pill-asignado', label:'Evento' };
    }
  }

  function getFilteredItems() {
    const q = (document.getElementById('notifSearch')?.value || '').toLowerCase().trim();
    const typeFilter = document.getElementById('notifTypeFilter')?.value || '';
    const readFilter = document.getElementById('notifReadFilter')?.value || '';

    return allItems.filter(n => {
      if (typeFilter && n.tipo !== typeFilter) return false;
      if (readFilter === 'read' && !n.leido) return false;
      if (readFilter === 'unread' && n.leido) return false;

      if (q) {
        const haystack = `${n.codigo || ''} ${n.titulo || ''} ${n.mensaje || ''}`.toLowerCase();
        if (!haystack.includes(q)) return false;
      }
      return true;
    });
  }

  function renderPagination(totalItems){
    const pages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));
    if (currentPage > pages) currentPage = pages;
    const wrap = document.getElementById('notifPagination');
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
    const el = document.getElementById('notifHistoryContainer');
    filteredItems = getFilteredItems();
    const total = filteredItems.length;

    if(!filteredItems.length){
      el.innerHTML = '<tr><td colspan="6" class="notif-empty"><i class="mdi mdi-bell-off-outline" style="font-size:28px;display:block;margin-bottom:8px;"></i>No hay notificaciones</td></tr>';
      document.getElementById('notifPaginationInfo').textContent = 'Mostrando 0 de 0 notificaciones';
      document.getElementById('notifPagination').innerHTML = '';
      return;
    }

    const startIndex = (currentPage - 1) * PAGE_SIZE;
    const pageItems = filteredItems.slice(startIndex, startIndex + PAGE_SIZE);
    let html='';
    pageItems.forEach(n => {
      const meta = getTypeMeta(n.tipo);
      const leidoClass = n.leido ? 'read' : '';
      const rowClass = n.leido ? 'notif-row-read' : 'notif-row-unread';
      const estadoHtml = n.leido
        ? '<i class="mdi mdi-eye-check-outline notif-status-eye"></i>'
        : '<span class="notif-status-dot blink"></span>';
      const tipoNotif = n.tipo_notificacion || 'ticket';
      const refId = n.referencia_evento || n.id;
      html += `
        <tr class="${rowClass}">
          <td>${estadoHtml}</td>
          <td>
            <div class="notif-ticket-cell">
              <span class="notif-icon-table" style="background:${meta.bg};"><i class="mdi ${meta.icon}" style="color:${meta.color};font-size:18px;"></i></span>
              <div>
                <div class="notif-ticket-title">${n.titulo || 'Notificacion'}</div>
                <div class="notif-ticket-code">#${n.codigo || '-'}</div>
              </div>
            </div>
          </td>
          <td><span class="notif-pill ${meta.pill}">${meta.label}</span></td>
          <td class="notif-msg-cell">${n.mensaje || ''}</td>
          <td class="notif-time-cell"><i class="mdi mdi-clock-outline"></i> ${n.tiempo || ''}</td>
          <td><a class="notif-link" href="ticket-view.php?id=${n.id}" data-tipo="${tipoNotif}" data-ref="${refId}">Ver ticket</a></td>
        </tr>`;
    });
    el.innerHTML = html;
    const shownFrom = startIndex + 1;
    const shownTo = Math.min(startIndex + PAGE_SIZE, total);
    document.getElementById('notifPaginationInfo').textContent = `Mostrando ${shownFrom}-${shownTo} de ${total} notificaciones`;
    renderPagination(total);

    el.querySelectorAll('a.notif-link').forEach(a => {
      a.addEventListener('click', function(){
        fetch('api/marcar_leido.php', {
          method:'POST',
          headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
          body: `tipo=${encodeURIComponent(this.dataset.tipo)}&referencia_id=${encodeURIComponent(this.dataset.ref)}`
        });
      });
    });
  }

  function loadHistory(){
    fetch('api/notificaciones_tickets.php?limit=300', { credentials:'same-origin' })
      .then(r => r.json())
      .then(r => {
        allItems = r.notificaciones || [];
        currentPage = 1;
        renderHistoryPage();
      })
      .catch(() => {
        document.getElementById('notifHistoryContainer').innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Error al cargar historial</td></tr>';
        document.getElementById('notifPaginationInfo').textContent = 'Mostrando 0 de 0 notificaciones';
        document.getElementById('notifPagination').innerHTML = '';
      });
  }

  function bindFilters(){
    const onChange = () => {
      currentPage = 1;
      renderHistoryPage();
    };
    document.getElementById('notifSearch')?.addEventListener('input', onChange);
    document.getElementById('notifTypeFilter')?.addEventListener('change', onChange);
    document.getElementById('notifReadFilter')?.addEventListener('change', onChange);
    document.getElementById('notifClearFilters')?.addEventListener('click', () => {
      document.getElementById('notifSearch').value = '';
      document.getElementById('notifTypeFilter').value = '';
      document.getElementById('notifReadFilter').value = '';
      onChange();
    });
  }

  document.getElementById('btnRefreshNotifs').addEventListener('click', loadHistory);
  bindFilters();
  loadHistory();
})();
</script>
<script src="js/sidebar-badges.js"></script>
</body>
</html>




