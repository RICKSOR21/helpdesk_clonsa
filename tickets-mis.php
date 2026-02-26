<?php
require_once 'config/session.php';
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Inicializar last_activity si no existe
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

$database = new Database();
$db = $database->getConnection();

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';

// Configuracion de timeout
$SESSION_TIMEOUT_JS = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 120;
$SESSION_POPUP_TIMEOUT_JS = defined('SESSION_POPUP_TIMEOUT') ? SESSION_POPUP_TIMEOUT : 900;

// Saludo dinamico segun la hora
date_default_timezone_set('America/Lima');
$hora = date('H');
$saludo = ($hora >= 5 && $hora < 12) ? 'Buenos dias' : (($hora >= 12 && $hora < 19) ? 'Buenas tardes' : 'Buenas noches');
$primer_nombre = explode(' ', $user_name)[0];
$fecha_actual = date('d/m/Y');

// Logica de filtros segun rol
$departamento_usuario = $_SESSION['departamento_id'] ?? 1;
$departamento_nombre = 'General';

if ($departamento_usuario) {
    $stmt = $db->prepare("SELECT nombre FROM departamentos WHERE id = ?");
    $stmt->execute([$departamento_usuario]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dept) {
        $departamento_nombre = $dept['nombre'];
    }
}

$puede_ver_todos = ($user_rol === 'Administrador' || $user_rol === 'Admin');
$es_jefe = ($user_rol === 'Jefe');
$es_usuario = ($user_rol === 'Usuario');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIRA - Mis Tickets</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="template/vendors/feather/feather.css">
  <link rel="stylesheet" href="template/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="template/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="template/vendors/typicons/typicons.css">
  <link rel="stylesheet" href="template/vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="template/vendors/css/vendor.bundle.base.css">
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="template/vendors/datatables.net-bs4/dataTables.bootstrap4.css">
  <!-- inject:css -->
  <link rel="stylesheet" href="template/css/vertical-layout-light/style.css">
  <link rel="shortcut icon" href="template/images/favicon.svg" />
  <style>
    .progress-cell { min-width: 150px; }
    .filter-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }

    /* Estilos para la tabla de tickets */
    .select-table thead th {
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      color: #6c757d;
      border-bottom: 1px solid #e9ecef;
      padding: 15px 10px;
    }
    .select-table tbody tr {
      border-bottom: 1px solid #f0f0f0;
    }
    .select-table tbody tr:hover {
      background-color: #f8f9fa;
    }
    .select-table tbody td {
      padding: 15px 10px;
      vertical-align: middle;
    }
    .select-table tbody td h6 {
      font-size: 14px;
      font-weight: 600;
      margin-bottom: 2px;
    }
    .select-table tbody td p {
      font-size: 12px;
      margin-bottom: 0;
    }
    .max-width-progress-wrap {
      min-width: 100px;
    }
    .progress-md {
      height: 6px;
    }

    /* Badges de estado */
    .badge-opacity-success {
      background-color: rgba(40, 167, 69, 0.15);
      color: #28a745;
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: 500;
    }
    .badge-opacity-warning {
      background-color: rgba(255, 193, 7, 0.15);
      color: #d39e00;
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: 500;
    }
    .badge-opacity-danger {
      background-color: rgba(220, 53, 69, 0.15);
      color: #dc3545;
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: 500;
    }
    .badge-opacity-info {
      background-color: rgba(23, 162, 184, 0.15);
      color: #17a2b8;
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: 500;
    }
    /* Badge naranja para Abierto */
    .badge-opacity-orange {
      background-color: rgba(253, 126, 20, 0.15);
      color: #fd7e14;
      padding: 8px 15px;
      border-radius: 20px;
      font-weight: 500;
    }
    /* Indicador pulsante para pendiente de aprobacion */
    .estado-container {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .pulse-dot-sm {
      width: 10px;
      height: 10px;
      background: #dc3545;
      border-radius: 50%;
      display: inline-block;
      animation: pulse-sm 1.5s infinite;
    }
    .transfer-t-badge {
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: #dc3545;
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 700;
      line-height: 1;
      animation: transfer-blink 1.05s infinite;
      box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
    }
    @keyframes transfer-blink {
      0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
      50% { opacity: .35; box-shadow: 0 0 0 6px rgba(220, 53, 69, 0); }
    }
    @keyframes pulse-sm {
      0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
      70% { box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
      100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    /* Modal de verificacion */
    .modal-verificacion .modal-header { border-bottom: none; padding-bottom: 0; }
    .modal-verificacion .modal-title { font-weight: 600; }
    .modal-verificacion .btn-aprobar { background: #28a745; color: #fff; border: none; }
    .modal-verificacion .btn-aprobar:hover { background: #218838; }
    .modal-verificacion .btn-rechazar { background: #dc3545; color: #fff; border: none; }
    .modal-verificacion .btn-rechazar:hover { background: #c82333; }
    .modal-verificacion textarea { resize: none; }
    .modal-transfer .modal-content {
      border: 0;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);
    }
    .modal-transfer .modal-header {
      border-bottom: 0;
      padding: 18px 22px;
      background: linear-gradient(135deg, #f5f9ff 0%, #edf4ff 100%);
    }
    .modal-transfer .modal-title {
      font-weight: 700;
      color: #1f3bb3;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .modal-transfer .modal-title i {
      width: 30px;
      height: 30px;
      border-radius: 8px;
      background: rgba(31, 59, 179, 0.12);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: #1f3bb3;
    }
    .transfer-summary-card {
      border: 1px solid #e9eefb;
      background: #fafcff;
      border-radius: 12px;
      padding: 12px;
      margin-bottom: 14px;
    }
    .transfer-summary-row {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      font-size: 13px;
      margin-bottom: 6px;
    }
    .transfer-summary-row:last-child { margin-bottom: 0; }
    .transfer-summary-label { color: #6b7280; font-weight: 600; }
    .transfer-summary-value { color: #1f2937; font-weight: 600; text-align: right; }
    .modal-transfer .form-label {
      font-size: 12px;
      font-weight: 700;
      color: #334155;
      text-transform: uppercase;
      letter-spacing: 0.3px;
      margin-bottom: 6px;
    }
    .modal-transfer .form-control, .modal-transfer .form-select {
      border-radius: 10px;
      border: 1px solid #dbe3f2;
      min-height: 42px;
      background: #fff;
    }
    .modal-transfer .form-control:focus, .modal-transfer .form-select:focus {
      border-color: #1f3bb3;
      box-shadow: 0 0 0 0.18rem rgba(31, 59, 179, 0.15);
    }
    .modal-transfer .modal-footer {
      border-top: 1px solid #edf1f7;
      padding: 14px 18px;
      background: #fcfdff;
    }
    .modal-transfer .modal-footer .btn {
      min-width: 126px;
      border-radius: 10px;
      font-weight: 600;
    }
    .transfer-pending-box {
      border: 1px solid #ffe69c;
      background: #fff8e1;
      border-radius: 8px;
      padding: 10px 12px;
      font-size: 12px;
      color: #664d03;
      margin-bottom: 10px;
    }

    /* Estilos para badges de notificaciones */
    .count-indicator { position: relative !important; }
    .count-indicator .badge-notif {
      position: absolute !important;
      top: 5px !important;
      right: 2px !important;
      background-color: #dc3545 !important;
      color: #ffffff !important;
      border-radius: 10px !important;
      min-width: 18px !important;
      height: 18px !important;
      padding: 0 5px !important;
      font-size: 11px !important;
      font-weight: 600 !important;
      display: none !important;
      align-items: center !important;
      justify-content: center !important;
      line-height: 18px !important;
      border: 2px solid #f4f5f7 !important;
      box-shadow: 0 1px 4px rgba(0,0,0,0.3) !important;
      z-index: 999 !important;
    }
    .count-indicator .badge-notif.show { display: flex !important; }
    .sidebar-badge {
      background: #dc3545;
      color: #ffffff;
      border-radius: 10px;
      min-width: 18px;
      height: 18px;
      padding: 0 6px;
      font-size: 11px;
      font-weight: 700;
      display: none;
      align-items: center;
      justify-content: center;
      line-height: 18px;
      margin-left: 8px;
      box-shadow: 0 1px 4px rgba(0,0,0,0.25);
    }
    .sidebar-badge.show { display: inline-flex; }

    /* Estilos para items de notificacion */
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

    /* Estilos de paginacion */
    .pagination .page-link {
      border: 1px solid #e9ecef;
      color: #6c757d;
      padding: 6px 12px;
      font-size: 13px;
    }
    .pagination .page-item.active .page-link {
      background-color: #1F3BB3;
      border-color: #1F3BB3;
      color: #fff;
    }
    .pagination .page-link:hover {
      background-color: #f0f4ff;
      border-color: #1F3BB3;
      color: #1F3BB3;
    }
    .pagination .page-item.disabled .page-link {
      background-color: #f8f9fa;
      color: #adb5bd;
    }

    /* Filtros estilos */
    .filter-section {
      padding: 10px 15px 15px;
    }
    .filter-section label {
      font-size: 11px;
      font-weight: 500;
      color: #495057;
      margin-bottom: 4px;
      display: block;
    }
    .filter-section .form-control {
      font-size: 12px;
      border-radius: 6px;
      padding: 6px 10px;
      height: 36px;
    }
    .filter-section .form-control:focus {
      border-color: #1F3BB3;
      box-shadow: 0 0 0 0.15rem rgba(31, 59, 179, 0.15);
    }
    .filter-section .form-check-input {
      width: 2em;
      height: 1em;
    }
    .filter-section .form-check-input:checked {
      background-color: #1F3BB3;
      border-color: #1F3BB3;
    }
    #advancedFilters {
      background: rgba(31, 59, 179, 0.02);
      border-radius: 6px;
      padding: 10px 0 0;
    }
    .action-popup {
      position: fixed;
      right: 20px;
      bottom: 20px;
      z-index: 9999;
      display: none;
      align-items: center;
      gap: 10px;
      padding: 12px 16px;
      border-radius: 10px;
      background: #28a745;
      color: #fff;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
      font-weight: 500;
      animation: popupIn 0.2s ease;
    }
    .action-popup.error { background: #dc3545; }
    .action-popup i { font-size: 18px; line-height: 1; }
    @keyframes popupIn {
      from { transform: translateY(8px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
  </style>
</head>
<body class="authenticated">
  <div class="container-scroller">
    <!-- Navbar -->
    <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
      <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
        <div class="me-3">
          <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
            <span class="icon-menu"></span>
          </button>
        </div>
        <div>
          <a class="navbar-brand brand-logo" href="dashboard.php">
            <img src="template/images/logo.svg" alt="logo" />
          </a>
          <a class="navbar-brand brand-logo-mini" href="dashboard.php">
            <img src="template/images/logo-mini.svg" alt="logo" />
          </a>
        </div>
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-top">
        <ul class="navbar-nav">
          <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
            <h1 class="welcome-text"><?php echo $saludo; ?>, <span class="text-black fw-bold"><?php echo htmlspecialchars($primer_nombre); ?></span></h1>
            <h3 class="welcome-sub-text">Gestion de Tickets - Portal SIRA Clonsa Ingenieria</h3>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <!-- Selector de Departamento (Deshabilitado para Admin - Vista General) -->
          <li class="nav-item d-none d-lg-block">
            <span class="nav-link dropdown-bordered" style="cursor: default; background-color: #e9ecef; opacity: 0.9; pointer-events: none;">
              <i class="mdi mdi-office-building me-1"></i> General
            </span>
          </li>

          <!-- Variables JS -->
          <script>
            window.USER_ROL = '<?php echo $user_rol; ?>';
            window.USER_DEPARTAMENTO = <?php echo $departamento_usuario; ?>;
            window.PUEDE_VER_TODOS = <?php echo $puede_ver_todos ? 'true' : 'false'; ?>;
            window.CURRENT_USER_ID = <?php echo intval($_SESSION['user_id'] ?? 0); ?>;
          </script>

          <!-- COMUNICADOS -->
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

          <!-- NOTIFICACIONES DE TICKETS -->
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
                <a href="notificaciones.php" class="btn btn-sm btn-primary" style="font-size: 10px; padding: 3px 10px;">
                  Ver todas <i class="mdi mdi-arrow-right"></i>
                </a>
              </div>
              <div id="ticketsNotificacionesContainer" style="max-height: 280px; overflow-y: auto;">
                <div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div>
              </div>
            </div>
          </li>

          <!-- Perfil de Usuario -->
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
              <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-message-text-outline text-primary me-2"></i> Mensajes</a>
              <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-calendar-check-outline text-primary me-2"></i> Actividad</a>
              <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-help-circle-outline text-primary me-2"></i> FAQ</a>
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
      <!-- Sidebar -->
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <!-- Dashboard -->
          <li class="nav-item">
            <a class="nav-link" href="dashboard.php">
              <i class="mdi mdi-view-dashboard menu-icon"></i>
              <span class="menu-title">Dashboard</span>
            </a>
          </li>

          <li class="nav-item nav-category">Gestion de Tickets</li>

          <!-- Tickets - ACTIVE -->
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
                <li class="nav-item"> <a class="nav-link" href="tickets-create.php">Crear Ticket</a></li>
                <li class="nav-item"> <a class="nav-link active d-flex align-items-center justify-content-between" href="tickets-mis.php"><span>Mis Tickets</span><span class="count-mis-sidebar sidebar-badge">0</span></a></li>
                <li class="nav-item">
                  <a class="nav-link d-flex align-items-center justify-content-between" href="tickets-asignados.php">
                    <span>Asignados a Mi</span>
                    <span class="count-asignados-sidebar sidebar-badge">0</span>
                  </a>
                </li>
                <li class="nav-item">
                  <a class="nav-link d-flex align-items-center justify-content-between" href="notificaciones.php">
                    <span>Notificaciones</span>
                    <span class="count-notificaciones-sidebar sidebar-badge">0</span>
                  </a>
                </li>
              </ul>
            </div>
          </li>

          <!-- Usuarios (solo Admin y Jefes) -->
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

          <!-- Catalogos (solo Admin) -->
          <?php if ($user_rol === 'Administrador'): ?>
          <li class="nav-item nav-category">Configuracion</li>
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

          <!-- Reportes -->
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

          <!-- Ayuda -->
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

      <!-- Main Panel -->
      <div class="main-panel">
        <div class="content-wrapper">
          <!-- Filtros -->
          <div class="row">
            <div class="col-md-12">
              <div class="filter-section">
                <!-- Header con toggle de filtros avanzados -->
                <div class="d-flex justify-content-end" style="margin-bottom: 8px;">
                  <label class="d-flex align-items-center mb-0" style="cursor: pointer; font-size: 11px; color: #6c757d; gap: 6px;">
                    <input class="form-check-input m-0" type="checkbox" id="toggleAdvancedFilters" style="cursor: pointer;">
                    Filtros avanzados
                  </label>
                </div>
                <!-- Filtros Principales -->
                <div class="d-flex align-items-end gap-2">
                  <div class="flex-fill">
                    <label><i class="mdi mdi-magnify text-primary me-1"></i>Buscar</label>
                    <input type="text" class="form-control" id="searchInput" placeholder="Codigo, titulo...">
                  </div>
                  <div class="flex-fill">
                    <label><i class="mdi mdi-flag text-warning me-1"></i>Estado</label>
                    <select class="form-control" id="filterEstado">
                      <option value="">Todos</option>
                    </select>
                  </div>
                  <div class="flex-fill">
                    <label><i class="mdi mdi-clipboard-text text-info me-1"></i>Actividad</label>
                    <select class="form-control" id="filterActividad">
                      <option value="">Todas</option>
                    </select>
                  </div>
                  <div class="flex-fill">
                    <label><i class="mdi mdi-map-marker text-danger me-1"></i>Ubicacion</label>
                    <select class="form-control" id="filterUbicacion">
                      <option value="">Todas</option>
                    </select>
                  </div>
                  <div class="flex-fill">
                    <label><i class="mdi mdi-percent text-primary me-1"></i>Progreso</label>
                    <select class="form-control" id="filterProgreso">
                      <option value="">Todos</option>
                      <option value="0-25">0-25%</option>
                      <option value="26-50">26-50%</option>
                      <option value="51-75">51-75%</option>
                      <option value="76-99">76-99%</option>
                      <option value="100">100%</option>
                    </select>
                  </div>
                  <div>
                    <button class="btn btn-outline-secondary" onclick="clearFilters()" title="Limpiar filtros" style="height: 36px; width: 36px; padding: 0;">
                      <i class="mdi mdi-refresh"></i>
                    </button>
                  </div>
                </div>

                <!-- Filtros Avanzados (Ocultos por defecto) -->
                <div id="advancedFilters" style="display: none;">
                  <div class="d-flex align-items-end gap-2 mt-2 pt-2 border-top">
                    <div class="flex-fill">
                      <label><i class="mdi mdi-phone text-success me-1"></i>Canal</label>
                      <select class="form-control" id="filterCanal">
                        <option value="">Todos</option>
                      </select>
                    </div>
                    <div class="flex-fill">
                      <label><i class="mdi mdi-radar text-info me-1"></i>Equipo</label>
                      <select class="form-control" id="filterEquipo">
                        <option value="">Todos</option>
                      </select>
                    </div>
                    <div class="flex-fill">
                      <label><i class="mdi mdi-account text-purple me-1"></i>Creador</label>
                      <select class="form-control" id="filterCreador">
                        <option value="">Todos</option>
                      </select>
                    </div>
                    <div class="flex-fill">
                      <label><i class="mdi mdi-calendar text-secondary me-1"></i>Desde</label>
                      <input type="date" class="form-control" id="filterFechaDesde">
                    </div>
                    <div class="flex-fill">
                      <label><i class="mdi mdi-calendar-range text-secondary me-1"></i>Hasta</label>
                      <input type="date" class="form-control" id="filterFechaHasta">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tabla de Tickets -->
          <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
              <div class="card card-rounded">
                <div class="card-body">
                  <div class="d-sm-flex justify-content-between align-items-start mb-4">
                    <div>
                      <h4 class="card-title card-title-dash">Mis Tickets</h4>
                      <p class="card-subtitle card-subtitle-dash" id="ticketsSubtitle">Cargando tickets...</p>
                    </div>
                    <div>
                      <a href="tickets-create.php" class="btn btn-primary btn-lg text-white mb-0 me-0">
                        <i class="mdi mdi-plus"></i> Nuevo Ticket
                      </a>
                    </div>
                  </div>
                  <div class="table-responsive mt-1">
                    <table class="table select-table">
                      <thead>
                        <tr>
                          <th>Creador</th>
                          <th>Ticket</th>
                          <th>Canal</th>
                          <th>Ubicacion / Equipo</th>
                          <th>Actividad</th>
                          <th>Progreso</th>
                          <th>Estado</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody id="ticketsBody">
                        <tr>
                          <td colspan="8" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 mb-0">Cargando tickets...</p>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <!-- Paginacion -->
                  <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div class="d-flex align-items-center">
                      <label class="me-2 mb-0 text-muted" style="font-size: 13px;">Mostrar:</label>
                      <select class="form-control form-control-sm" id="itemsPerPage" style="width: 80px;">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="all">Todos</option>
                      </select>
                      <span class="ms-2 text-muted" style="font-size: 13px;">tickets por pagina</span>
                    </div>
                    <div id="paginationInfo" class="text-muted" style="font-size: 13px;">
                      Mostrando 0 de 0 tickets
                    </div>
                    <nav aria-label="Paginacion de tickets">
                      <ul class="pagination pagination-sm mb-0" id="paginationControls">
                        <!-- Controles de paginacion generados dinamicamente -->
                      </ul>
                    </nav>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
          <div class="d-sm-flex justify-content-center justify-content-sm-between">
            <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">
              Portal SIRA <a href="https://www.clonsa.pe/" target="_blank">Clonsa Ingenieria</a> <?php echo date('Y'); ?>
            </span>
            <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">
              Sistema Integral de Registro y Atencion
            </span>
          </div>
        </footer>
      </div>
    </div>
  </div>

  <div id="actionPopup" class="action-popup" role="status" aria-live="polite">
    <i id="actionPopupIcon" class="mdi mdi-check-circle"></i>
    <span id="actionPopupText">Operacion completada</span>
  </div>

  <!-- plugins:js -->
  <script src="template/vendors/js/vendor.bundle.base.js"></script>
  <!-- Plugin js for this page -->
  <script src="template/vendors/datatables.net/jquery.dataTables.js"></script>
  <script src="template/vendors/datatables.net-bs4/dataTables.bootstrap4.js"></script>
  <!-- inject:js -->
  <script src="template/js/off-canvas.js"></script>
  <script src="template/js/hoverable-collapse.js"></script>
  <script src="template/js/template.js"></script>
  <script src="template/js/settings.js"></script>

  <!-- Session Manager -->
  <script>
    const SESSION_TIMEOUT = <?php echo $SESSION_TIMEOUT_JS; ?>;
    const SESSION_POPUP_TIMEOUT = <?php echo $SESSION_POPUP_TIMEOUT_JS; ?>;
  </script>
  <script src="assets/js/session-manager.js"></script>

  <!-- Notificaciones -->
  <script src="assets/js/notificaciones.js?v=<?php echo time(); ?>"></script>

  <script>
    const USER_ROL = '<?php echo $user_rol; ?>';
    const CURRENT_USER_ID = <?php echo intval($_SESSION['user_id'] ?? 0); ?>;
    const CURRENT_USER_NAME = <?php echo json_encode($user_name ?? 'Usuario', JSON_UNESCAPED_UNICODE); ?>;
    let allTickets = [];
    let filteredTickets = [];
    let currentPage = 1;
    let itemsPerPage = 10;
    let actionPopupTimer = null;
    let ticketTransferenciaActual = null;
    let transferenciaPendienteActual = null;

    $(document).ready(function() {
      loadCatalogos();
      loadTickets();

      // Eventos de filtros
      $('#searchInput').on('keyup', function() {
        currentPage = 1;
        filterTickets();
      });

      $('#filterEstado, #filterActividad, #filterUbicacion, #filterProgreso, #filterCanal, #filterEquipo, #filterCreador, #filterFechaDesde, #filterFechaHasta').on('change', function() {
        currentPage = 1;
        filterTickets();
      });

      // Evento de cambio de items por pagina
      $('#itemsPerPage').on('change', function() {
        const val = $(this).val();
        itemsPerPage = val === 'all' ? 'all' : parseInt(val);
        currentPage = 1;
        renderPaginatedTickets();
      });

      // Toggle filtros avanzados
      $('#toggleAdvancedFilters').on('change', function() {
        if($(this).is(':checked')) {
          $('#advancedFilters').slideDown(200);
        } else {
          $('#advancedFilters').slideUp(200);
          // Limpiar filtros avanzados al ocultar
          $('#filterCanal, #filterEquipo, #filterCreador').val('');
          $('#filterFechaDesde, #filterFechaHasta').val('');
          filterTickets();
        }
      });
    });

    function loadCatalogos() {
      // Estados
      $.get('api/catalogos.php?tipo=estados', function(response) {
        if(response.success) {
          response.data.forEach(item => {
            const estadoNombre = (item.nombre || '').toLowerCase() === 'pendiente' ? 'Verificando' : item.nombre;
            $('#filterEstado').append(`<option value="${item.id}">${estadoNombre}</option>`);
          });
          $('#filterEstado').append('<option value="verificando">Verificando</option>');
          $('#filterEstado').append('<option value="transferido">Transferido</option>');
        }
      });

      // Actividades
      $.get('api/catalogos.php?tipo=actividades', function(response) {
        if(response.success) {
          const grupos = new Map();
          response.data.forEach(item => {
            const canon = normalizarActividadNombre(item.nombre || '');
            if(!grupos.has(canon)) {
              grupos.set(canon, {
                value: `grupo:${canon.toLowerCase()}`,
                label: canon
              });
            }
          });

          Array.from(grupos.values())
            .sort((a, b) => a.label.localeCompare(b.label, 'es'))
            .forEach(item => {
              $('#filterActividad').append(`<option value="${item.value}">${item.label}</option>`);
            });
        }
      });

      // Ubicaciones
      $.get('api/catalogos.php?tipo=ubicaciones', function(response) {
        if(response.success) {
          response.data.forEach(item => {
            $('#filterUbicacion').append(`<option value="${item.id}">${item.nombre}</option>`);
          });
        }
      });

      // Canales
      $.get('api/catalogos.php?tipo=canales', function(response) {
        if(response.success) {
          response.data.forEach(item => {
            $('#filterCanal').append(`<option value="${item.id}">${item.nombre}</option>`);
          });
        }
      });

      // Equipos
      $.get('api/catalogos.php?tipo=equipos', function(response) {
        if(response.success) {
          response.data.forEach(item => {
            $('#filterEquipo').append(`<option value="${item.id}">${item.nombre}</option>`);
          });
        }
      });

      // En Mis Tickets el creador es siempre el usuario logueado.
      $('#filterCreador')
        .html(`<option value="${CURRENT_USER_ID}">${CURRENT_USER_NAME}</option>`)
        .val(String(CURRENT_USER_ID))
        .prop('disabled', true);
    }

    function loadTickets() {
      $.ajax({
        url: 'api/tickets.php?action=listar',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
          console.log('Respuesta API:', response);
          if(response.success) {
            const base = response.data || [];
            const myId = parseInt(CURRENT_USER_ID || 0);
            allTickets = base.filter(ticket => {
              const esCreador = parseInt(ticket.usuario_id || 0) === myId;
              const asignadoA = parseInt(ticket.asignado_a || 0);
              const asignadoAMi = asignadoA === myId;
              // Mis tickets = asignados a mí O creados por mí (sin asignar a otro)
              return asignadoAMi || (esCreador && (asignadoA === 0 || asignadoA === myId));
            });
            filteredTickets = [...allTickets];
            renderPaginatedTickets();
          } else {
            $('#ticketsSubtitle').text('Error al cargar');
            $('#ticketsBody').html(`<tr><td colspan="8" class="text-center text-danger py-4">Error: ${response.message || 'No se pudieron cargar los tickets'}</td></tr>`);
          }
        },
        error: function(xhr, status, error) {
          console.error('Error AJAX:', status, error);
          console.error('Respuesta:', xhr.responseText);
          $('#ticketsSubtitle').text('Error de conexion');
          $('#ticketsBody').html(`<tr><td colspan="8" class="text-center text-danger py-4">Error de conexion al servidor</td></tr>`);
        }
      });
    }

    function renderPaginatedTickets() {
      const totalTickets = filteredTickets.length;
      let ticketsToShow;
      let totalPages;
      let startIndex;
      let endIndex;

      if(itemsPerPage === 'all') {
        ticketsToShow = filteredTickets;
        totalPages = 1;
        startIndex = 0;
        endIndex = totalTickets;
      } else {
        totalPages = Math.ceil(totalTickets / itemsPerPage);
        if(currentPage > totalPages) currentPage = totalPages || 1;
        startIndex = (currentPage - 1) * itemsPerPage;
        endIndex = Math.min(startIndex + itemsPerPage, totalTickets);
        ticketsToShow = filteredTickets.slice(startIndex, endIndex);
      }

      // Actualizar info de paginacion
      if(totalTickets > 0) {
        $('#paginationInfo').text(`Mostrando ${startIndex + 1} - ${endIndex} de ${totalTickets} tickets`);
      } else {
        $('#paginationInfo').text('No hay tickets para mostrar');
      }

      // Renderizar tickets
      renderTickets(ticketsToShow, totalTickets);

      // Generar controles de paginacion
      renderPaginationControls(totalPages);
    }

    function renderPaginationControls(totalPages) {
      let html = '';

      if(totalPages <= 1 || itemsPerPage === 'all') {
        $('#paginationControls').html('');
        return;
      }

      // Boton anterior
      html += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="goToPage(${currentPage - 1}); return false;"><i class="mdi mdi-chevron-left"></i></a>
      </li>`;

      // Paginas
      const maxVisiblePages = 5;
      let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
      let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);

      if(endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
      }

      if(startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(1); return false;">1</a></li>`;
        if(startPage > 2) {
          html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
      }

      for(let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
          <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
        </li>`;
      }

      if(endPage < totalPages) {
        if(endPage < totalPages - 1) {
          html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${totalPages}); return false;">${totalPages}</a></li>`;
      }

      // Boton siguiente
      html += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="goToPage(${currentPage + 1}); return false;"><i class="mdi mdi-chevron-right"></i></a>
      </li>`;

      $('#paginationControls').html(html);
    }

    function goToPage(page) {
      const totalPages = itemsPerPage === 'all' ? 1 : Math.ceil(filteredTickets.length / itemsPerPage);
      if(page < 1 || page > totalPages) return;
      currentPage = page;
      renderPaginatedTickets();
      // Scroll suave hacia la tabla
      $('html, body').animate({ scrollTop: $('.card-rounded').offset().top - 100 }, 300);
    }

    function renderTickets(tickets, totalFiltered = null) {
      // Actualizar subtitulo
      const totalCount = totalFiltered !== null ? totalFiltered : tickets.length;
      $('#ticketsSubtitle').text(`Tienes ${totalCount} tickets registrados`);

      if(tickets.length === 0) {
        $('#ticketsBody').html(`<tr><td colspan="8" class="text-center py-4">No se encontraron tickets</td></tr>`);
        return;
      }

      let html = '';
      tickets.forEach(ticket => {
        // Determinar color del progreso
        let progresoColor = 'danger';
        let progresoTextColor = 'danger';
        if(ticket.progreso >= 75) {
          progresoColor = 'success';
          progresoTextColor = 'success';
        } else if(ticket.progreso >= 50) {
          progresoColor = 'success';
          progresoTextColor = 'success';
        } else if(ticket.progreso >= 25) {
          progresoColor = 'warning';
          progresoTextColor = 'warning';
        }

        // Determinar badge del estado segun progreso y estado_id
        // Logica: El estado visual se determina por el progreso Y el estado_id
        let estadoBadgeClass = 'badge-opacity-warning';
        let showPulseDot = false;
        let showCheckIcon = false;
        const showTransferBadge = parseInt(ticket.transferencia_aprobada || 0) >= 1;
        let estadoDisplay = ticket.estado;
        let progreso = parseInt(ticket.progreso) || 0;

        // Primero verificar estados especiales (Rechazado, Resuelto con pendiente)
        if(ticket.estado_id == 5) {
          // Rechazado - Rojo
          estadoBadgeClass = 'badge-opacity-danger';
          estadoDisplay = 'Rechazado';
        } else if(ticket.estado_id == 4 && ticket.pendiente_aprobacion == 1) {
          // Pendiente de verificacion - LED parpadeante
          estadoBadgeClass = 'badge-opacity-success';
          showPulseDot = true;
          estadoDisplay = 'Verificando';
        } else if(ticket.estado_id == 4 && ticket.pendiente_aprobacion == 0) {
          // Resuelto/Aprobado - Check verde
          estadoBadgeClass = 'badge-opacity-success';
          showCheckIcon = true;
          estadoDisplay = 'Resuelto';
        } else if(progreso == 0) {
          // Abierto - Naranja (0%)
          estadoBadgeClass = 'badge-opacity-orange';
          estadoDisplay = 'Abierto';
        } else if(progreso > 0 && progreso < 100) {
          // En Atencion - Azul (1-99%)
          estadoBadgeClass = 'badge-opacity-info';
          estadoDisplay = 'En Atencion';
        } else {
          // Cualquier otro caso
          estadoBadgeClass = 'badge-opacity-warning';
        }

        // Avatar del creador
        const avatarUrl = `https://ui-avatars.com/api/?name=${encodeURIComponent(ticket.creador || 'Usuario')}&background=667eea&color=fff&size=45`;

        // Icono del canal
        let canalIcon = 'mdi-help-circle';
        let canalColor = '#6c757d';
        if(ticket.canal) {
          const canalLower = ticket.canal.toLowerCase();
          if(canalLower.includes('telefono') || canalLower.includes('telefono') || canalLower.includes('llamada')) {
            canalIcon = 'mdi-phone';
            canalColor = '#28a745';
          } else if(canalLower.includes('email') || canalLower.includes('correo')) {
            canalIcon = 'mdi-email';
            canalColor = '#17a2b8';
          } else if(canalLower.includes('whatsapp')) {
            canalIcon = 'mdi-whatsapp';
            canalColor = '#25D366';
          } else if(canalLower.includes('presencial') || canalLower.includes('persona')) {
            canalIcon = 'mdi-account';
            canalColor = '#6f42c1';
          } else if(canalLower.includes('web') || canalLower.includes('portal')) {
            canalIcon = 'mdi-web';
            canalColor = '#fd7e14';
          }
        }

        html += `
          <tr>
            <td>
              <div class="d-flex">
                <img src="${avatarUrl}" alt="" class="me-2" style="width: 45px; height: 45px; border-radius: 50%;">
                <div>
                  <h6 class="mb-0 d-flex align-items-center gap-2">
                    <span>${ticket.creador || 'Sin asignar'}</span>
                    ${showTransferBadge ? '<span class="transfer-t-badge" title="Transferido">T</span>' : ''}
                  </h6>
                  <p class="text-muted mb-0" style="font-size: 12px;">${formatDate(ticket.created_at)}</p>
                </div>
              </div>
            </td>
            <td>
              ${(() => { const dIds = (ticket.departamentos_actividad_ids || '').split(',').filter(Boolean); const label = dIds.length > 1 ? ticket.departamentos_actividad : ticket.departamento_nombre; return label ? `<span style="font-size:10px;font-weight:600;color:#1F3BB3;letter-spacing:0.3px;text-transform:uppercase;display:block;margin-bottom:2px;">${label}</span>` : ''; })()}
              <h6 class="mb-1"><a href="ticket-view.php?id=${ticket.id}" class="text-dark">${ticket.codigo}</a></h6>
              <p class="text-muted mb-0" style="font-size: 12px; max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${ticket.titulo}</p>
            </td>
            <td class="text-center">
              <i class="mdi ${canalIcon}" style="font-size: 24px; color: ${canalColor};" title="${ticket.canal || 'Sin canal'}"></i>
              <p class="text-muted mb-0" style="font-size: 10px;">${ticket.canal || '-'}</p>
            </td>
            <td>
              <h6 class="mb-1" style="font-size: 12px;"><i class="mdi mdi-map-marker text-danger"></i> ${ticket.ubicacion || 'Sin ubicacion'}</h6>
              <p class="text-muted mb-0" style="font-size: 11px;"><i class="mdi mdi-radar text-info"></i> ${ticket.equipo || 'Sin equipo'}</p>
            </td>
            <td>
              <span class="badge" style="background-color: ${ticket.actividad_color || '#6c757d'}; color: white; font-size: 11px;">${normalizarActividadNombre(ticket.actividad || 'N/A')}</span>
            </td>
            <td>
              <div>
                <div class="d-flex justify-content-between align-items-center mb-1 max-width-progress-wrap">
                  <p class="text-${progresoTextColor} mb-0">${ticket.progreso}%</p>
                </div>
                <div class="progress progress-md">
                  <div class="progress-bar bg-${progresoColor}" role="progressbar" style="width: ${ticket.progreso}%" aria-valuenow="${ticket.progreso}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </td>
            <td>
              <div class="estado-container">
                <div class="badge ${estadoBadgeClass}">${estadoDisplay}</div>
                ${showPulseDot ? '<span class="pulse-dot-sm" title="Pendiente de verificacion"></span>' : ''}
                ${showCheckIcon ? '<i class="mdi mdi-check-circle text-success" style="font-size: 16px;" title="Verificado"></i>' : ''}
              </div>
            </td>
            <td>
              <a href="ticket-view.php?id=${ticket.id}" class="btn btn-sm btn-outline-primary" title="Ver ticket">
                <i class="mdi mdi-eye"></i>
              </a>
              ${(ticket.estado_id == 1 || ticket.estado_id == 2 || ticket.estado_id == 5) ? `
                <button class="btn btn-sm ${(parseInt(ticket.transferencia_pendiente || 0) === 1) ? 'btn-outline-warning' : 'btn-outline-info'}"
                        onclick="abrirTransferencia(${ticket.id})"
                        title="${(parseInt(ticket.transferencia_pendiente || 0) === 1) ? 'Gestionar transferencia pendiente' : 'Transferir ticket'}">
                  <i class="mdi mdi-swap-horizontal"></i>
                </button>
              ` : ``}
              ${(ticket.estado_id == 4 && ticket.pendiente_aprobacion == 1) ? (
                (USER_ROL === 'Administrador' || USER_ROL === 'Admin' || USER_ROL === 'Jefe') ? `
                  <button class="btn btn-sm btn-outline-warning" onclick="verificarTicket('${ticket.codigo}')" title="Verificar cierre">
                    <i class="mdi mdi-pencil"></i>
                  </button>
                ` : ``
              ) : (ticket.estado_id == 4 && ticket.pendiente_aprobacion == 0) ? `
                <button class="btn btn-sm btn-outline-secondary" disabled title="Ticket resuelto">
                  <i class="mdi mdi-lock"></i>
                </button>
              ` : `
                <button class="btn btn-sm btn-outline-danger" onclick="eliminarTicket('${ticket.codigo}', ${ticket.id})" title="Eliminar ticket">
                  <i class="mdi mdi-close"></i>
                </button>
              `}
            </td>
          </tr>
        `;
      });
      $('#ticketsBody').html(html);
    }

    function filterTickets() {
      const search = $('#searchInput').val().toLowerCase();
      const estado = $('#filterEstado').val();
      const actividad = $('#filterActividad').val();
      const ubicacion = $('#filterUbicacion').val();
      const progreso = $('#filterProgreso').val();
      const canal = $('#filterCanal').val();
      const equipo = $('#filterEquipo').val();
      const creador = $('#filterCreador').val();
      const fechaDesde = $('#filterFechaDesde').val();
      const fechaHasta = $('#filterFechaHasta').val();

      filteredTickets = allTickets.filter(ticket => {
        // Busqueda por texto (codigo, titulo o creador)
        const matchSearch = !search ||
          ticket.codigo.toLowerCase().includes(search) ||
          ticket.titulo.toLowerCase().includes(search) ||
          (ticket.creador && ticket.creador.toLowerCase().includes(search));

        // Filtros de seleccion
        let matchEstado = true;
        if(estado) {
          if(estado === 'verificando') {
            matchEstado = parseInt(ticket.estado_id || 0) === 4 && parseInt(ticket.pendiente_aprobacion || 0) === 1;
          } else if(estado === 'transferido') {
            matchEstado = parseInt(ticket.transferencia_aprobada || 0) >= 1;
          } else if(estado == '2') {
            // "En Atención" incluye también Rechazados (estado_id=5) porque siguen en proceso
            const eid = parseInt(ticket.estado_id || 0);
            matchEstado = eid === 2 || eid === 5;
          } else {
            matchEstado = parseInt(ticket.estado_id || 0) === parseInt(estado);
          }
        }
        let matchActividad = true;
        if(actividad) {
          if(String(actividad).startsWith('grupo:')) {
            const grupo = String(actividad).replace('grupo:', '');
            matchActividad = normalizarActividadNombre(ticket.actividad || '').toLowerCase() === grupo;
          } else {
            matchActividad = ticket.actividad_id == actividad;
          }
        }
        const matchUbicacion = !ubicacion || ticket.ubicacion_id == ubicacion;
        const matchCanal = !canal || ticket.canal_atencion_id == canal;
        const matchEquipo = !equipo || ticket.equipo_id == equipo;
        const matchCreador = !creador || ticket.usuario_id == creador;

        // Filtro de progreso
        let matchProgreso = true;
        if(progreso) {
          if(progreso === '100') {
            matchProgreso = ticket.progreso == 100;
          } else {
            const [min, max] = progreso.split('-').map(Number);
            matchProgreso = ticket.progreso >= min && ticket.progreso <= max;
          }
        }

        // Filtro de fechas
        let matchFecha = true;
        if(fechaDesde || fechaHasta) {
          const ticketDate = new Date(ticket.created_at);
          ticketDate.setHours(0, 0, 0, 0);

          if(fechaDesde) {
            const desde = new Date(fechaDesde);
            desde.setHours(0, 0, 0, 0);
            if(ticketDate < desde) matchFecha = false;
          }
          if(fechaHasta) {
            const hasta = new Date(fechaHasta);
            hasta.setHours(23, 59, 59, 999);
            if(ticketDate > hasta) matchFecha = false;
          }
        }

        return matchSearch && matchEstado && matchActividad && matchUbicacion &&
               matchProgreso && matchCanal && matchEquipo && matchCreador && matchFecha;
      });

      renderPaginatedTickets();
    }

    function clearFilters() {
      $('#searchInput').val('');
      $('#filterEstado, #filterActividad, #filterUbicacion, #filterProgreso, #filterCanal, #filterEquipo, #filterCreador').val('');
      $('#filterFechaDesde, #filterFechaHasta').val('');
      currentPage = 1;
      filteredTickets = [...allTickets];
      renderPaginatedTickets();
    }

    function formatDate(dateString) {
      if(!dateString) return 'Sin fecha';
      const date = new Date(dateString);
      if(isNaN(date.getTime())) return 'Fecha invalida';
      return date.toLocaleDateString('es-PE') + ' ' + date.toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit'});
    }

    function normalizarActividadNombre(nombre) {
      const raw = (nombre || '').toString().trim();
      if(!raw) return 'N/A';

      const normalized = raw
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ');

      if(/^(mantto|mantenimiento)\s+correctivo$/.test(normalized)) return 'Mantenimiento Correctivo';
      if(/^(mantto|mantenimiento)\s+predictivo$/.test(normalized)) return 'Mantenimiento Predictivo';
      if(/^(mantto|mantenimiento)\s+preventivo$/.test(normalized)) return 'Mantenimiento Preventivo';

      return raw;
    }

    function verTicket(codigo) {
      window.location.href = 'ticket-detalle.php?codigo=' + codigo;
    }

    function editarProgreso(codigo, progresoActual) {
      const nuevoProgreso = prompt('Ingresa el nuevo progreso (0-100):', progresoActual);
      if(nuevoProgreso !== null && nuevoProgreso >= 0 && nuevoProgreso <= 100) {
        $.post('api/tickets.php?action=actualizar_progreso', {
          codigo: codigo,
          progreso: nuevoProgreso
        }, function(response) {
          if(response.success) {
            alert('Progreso actualizado correctamente');
            loadTickets();
          } else {
            alert('Error: ' + response.message);
          }
        });
      }
    }

    // Variables para verificacion
    let ticketVerificando = null;

    function verificarTicket(codigo) {
      ticketVerificando = codigo;
      $('#modalVerificacion').modal('show');
      $('#comentarioVerificacion').val('');
    }

    function aprobarTicket() {
      if(!ticketVerificando) return;

      const comentario = $('#comentarioVerificacion').val();
      $('#modalVerificacion').modal('hide');

      $.post('api/tickets.php?action=aprobar_cierre', {
        codigo: ticketVerificando,
        comentario: comentario
      }, function(response) {
        if(response.success) {
          showActionPopup('Ticket aprobado correctamente', 'success');
          loadTickets();
        } else {
          showActionPopup('Error: ' + (response.message || 'No se pudo aprobar el ticket'), 'error');
        }
        ticketVerificando = null;
      });
    }

    function rechazarTicket() {
      if(!ticketVerificando) return;

      const comentario = $('#comentarioVerificacion').val();
      if(!comentario.trim()) {
        alert('Debe indicar el motivo del rechazo');
        return;
      }

      $('#modalVerificacion').modal('hide');

      $.post('api/tickets.php?action=rechazar_cierre', {
        codigo: ticketVerificando,
        comentario: comentario
      }, function(response) {
        if(response.success) {
          showActionPopup('Ticket rechazado. Se notifico al usuario.', 'success');
          loadTickets();
        } else {
          showActionPopup('Error: ' + (response.message || 'No se pudo rechazar el ticket'), 'error');
        }
        ticketVerificando = null;
      });
    }

    // Variables para eliminar
    let ticketEliminar = null;
    let ticketIdEliminar = null;

    function eliminarTicket(codigo, id) {
      ticketEliminar = codigo;
      ticketIdEliminar = id;
      $('#codigoEliminar').text(codigo);
      $('#modalEliminar').modal('show');
    }

    function esAdminOJefe() {
      return USER_ROL === 'Administrador' || USER_ROL === 'Admin' || USER_ROL === 'Jefe';
    }

    function abrirTransferencia(ticketId) {
      const ticket = allTickets.find(t => parseInt(t.id) === parseInt(ticketId));
      if(!ticket) {
        showActionPopup('No se encontro informacion del ticket', 'error');
        return;
      }

      ticketTransferenciaActual = ticket;
      transferenciaPendienteActual = null;
      $('#transferTicketCodigo').text(ticket.codigo || '-');
      $('#transferCreadoPor').text(ticket.creador || '-');
      $('#transferAsignadoActual').text(ticket.asignado || 'Sin asignar');
      $('#transferComentario').val('');
      $('#transferUsuarioDestino').html('<option value="">Cargando usuarios...</option>');
      $('#transferPendienteInfo').hide().html('');
      $('#btnSolicitarTransfer').hide();
      $('#btnTransferirDirecto').hide();
      $('#btnAprobarTransfer').hide();
      $('#btnRechazarTransfer').hide();
      $('#transferUsuarioDestino').prop('disabled', false);
      $('#transferComentario').prop('disabled', false);

      $.get('api/usuarios.php?action=por_departamento&departamento_id=' + (ticket.departamento_id || ''), function(respUsers) {
        if(respUsers.success) {
          const excluidos = new Set([
            parseInt(ticket.asignado_a || 0),      // asignado actual
            parseInt(CURRENT_USER_ID || 0)         // usuario logueado
          ]);

          let htmlUsers = '<option value="">Seleccione usuario destino</option>';
          (respUsers.data || []).forEach(u => {
            if(excluidos.has(parseInt(u.id))) return;
            htmlUsers += `<option value="${u.id}">${u.nombre_completo}</option>`;
          });

          if(htmlUsers === '<option value="">Seleccione usuario destino</option>') {
            htmlUsers += '<option value="" disabled>No hay usuarios disponibles para transferencia</option>';
          }

          $('#transferUsuarioDestino').html(htmlUsers);
        } else {
          $('#transferUsuarioDestino').html('<option value="">No se pudo cargar usuarios</option>');
        }
      });

      $.get('api/tickets.php?action=obtener_transferencia_pendiente&ticket_id=' + ticket.id, function(respTransfer) {
        const pendiente = (respTransfer.success && respTransfer.data) ? respTransfer.data : null;
        if(pendiente) {
          transferenciaPendienteActual = pendiente;
          const fecha = pendiente.created_at ? formatDate(pendiente.created_at) : '-';
          const motivo = pendiente.motivo ? pendiente.motivo : 'Sin motivo';
          $('#transferPendienteInfo').html(`
            <div><strong>Solicitud pendiente</strong></div>
            <div>Solicitado por: ${pendiente.solicitado_por_nombre || 'Usuario'}</div>
            <div>Destino: ${pendiente.usuario_destino_nombre || '-'}</div>
            <div>Fecha: ${fecha}</div>
            <div>Motivo: ${motivo}</div>
          `).show();

          $('#transferUsuarioDestino').val(pendiente.usuario_destino);

          if(esAdminOJefe()) {
            $('#btnAprobarTransfer').show();
            $('#btnRechazarTransfer').show();
          } else {
            $('#transferUsuarioDestino').prop('disabled', true);
            $('#transferComentario').prop('disabled', true);
          }
        } else {
          if(esAdminOJefe()) {
            $('#btnTransferirDirecto').show();
          } else {
            $('#btnSolicitarTransfer').show();
          }
        }

        $('#modalTransferencia').modal('show');
      });
    }

    function solicitarTransferencia() {
      if(!ticketTransferenciaActual) return;
      const usuarioDestino = $('#transferUsuarioDestino').val();
      const motivo = $('#transferComentario').val().trim();

      if(!usuarioDestino) {
        showActionPopup('Seleccione usuario destino', 'error');
        return;
      }

      $.post('api/tickets.php?action=solicitar_transferencia', {
        ticket_id: ticketTransferenciaActual.id,
        usuario_destino: usuarioDestino,
        motivo: motivo
      }, function(response) {
        if(response.success) {
          showActionPopup('Solicitud enviada. Debe ser aprobada por Jefe o Administrador.', 'success');
          $('#modalTransferencia').modal('hide');
          loadTickets();
        } else {
          showActionPopup('Error: ' + (response.message || 'No se pudo solicitar la transferencia'), 'error');
        }
      });
    }

    function transferirTicketDirecto() {
      if(!ticketTransferenciaActual) return;
      const usuarioDestino = $('#transferUsuarioDestino').val();
      const motivo = $('#transferComentario').val().trim();

      if(!usuarioDestino) {
        showActionPopup('Seleccione usuario destino', 'error');
        return;
      }

      $.post('api/tickets.php?action=transferir_ticket_directo', {
        ticket_id: ticketTransferenciaActual.id,
        usuario_destino: usuarioDestino,
        motivo: motivo
      }, function(response) {
        if(response.success) {
          showActionPopup('Ticket transferido correctamente', 'success');
          $('#modalTransferencia').modal('hide');
          loadTickets();
        } else {
          showActionPopup('Error: ' + (response.message || 'No se pudo transferir el ticket'), 'error');
        }
      });
    }

    function responderTransferencia(decision) {
      if(!transferenciaPendienteActual) {
        showActionPopup('No hay solicitud pendiente para responder', 'error');
        return;
      }

      const comentario = $('#transferComentario').val().trim();
      $.post('api/tickets.php?action=responder_transferencia', {
        transferencia_id: transferenciaPendienteActual.id,
        decision: decision,
        comentario: comentario
      }, function(response) {
        if(response.success) {
          showActionPopup(response.message || 'Solicitud procesada', 'success');
          $('#modalTransferencia').modal('hide');
          loadTickets();
        } else {
          showActionPopup('Error: ' + (response.message || 'No se pudo procesar la solicitud'), 'error');
        }
      });
    }

    function showActionPopup(message, type = 'success') {
      const popup = $('#actionPopup');
      const icon = $('#actionPopupIcon');
      const text = $('#actionPopupText');

      popup.removeClass('error');
      if(type === 'error') {
        popup.addClass('error');
        icon.attr('class', 'mdi mdi-alert-circle');
      } else {
        icon.attr('class', 'mdi mdi-check-circle');
      }

      text.text(message || 'Operacion completada');
      popup.stop(true, true).fadeIn(120);

      if(actionPopupTimer) clearTimeout(actionPopupTimer);
      actionPopupTimer = setTimeout(() => popup.fadeOut(180), 2200);
    }

    function confirmarEliminar() {
      if(!ticketEliminar) return;

      $('#modalEliminar').modal('hide');

      $.post('api/tickets.php?action=eliminar', {
        ticket_id: ticketIdEliminar
      }, function(response) {
        if(response.success) {
          showActionPopup('Ticket eliminado correctamente', 'success');
          loadTickets();
        } else {
          showActionPopup('Error: ' + response.message, 'error');
        }
        ticketEliminar = null;
        ticketIdEliminar = null;
      });
    }
  </script>

  <!-- Modal de Verificacion -->
  <div class="modal fade modal-verificacion" id="modalVerificacion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-clipboard-check text-warning me-2"></i>Verificar Cierre de Ticket</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted mb-3">El usuario ha solicitado cerrar este ticket. Desea aprobar o rechazar el cierre?</p>
          <div class="mb-3">
            <label class="form-label">Comentario (obligatorio para rechazar)</label>
            <textarea class="form-control" id="comentarioVerificacion" rows="3" placeholder="Ingrese un comentario..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-rechazar" onclick="rechazarTicket()">
            <i class="mdi mdi-close-circle me-1"></i>Rechazar
          </button>
          <button type="button" class="btn btn-aprobar" onclick="aprobarTicket()">
            <i class="mdi mdi-check-circle me-1"></i>Aprobar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Transferencia -->
  <div class="modal fade modal-transfer" id="modalTransferencia" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="mdi mdi-swap-horizontal"></i>Transferir Ticket</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="transfer-summary-card">
            <div class="transfer-summary-row">
              <span class="transfer-summary-label">Ticket</span>
              <span class="transfer-summary-value" id="transferTicketCodigo">-</span>
            </div>
            <div class="transfer-summary-row">
              <span class="transfer-summary-label">Creado por</span>
              <span class="transfer-summary-value" id="transferCreadoPor">-</span>
            </div>
            <div class="transfer-summary-row">
              <span class="transfer-summary-label">Asignado actual</span>
              <span class="transfer-summary-value" id="transferAsignadoActual">-</span>
            </div>
          </div>
          <div id="transferPendienteInfo" class="transfer-pending-box" style="display:none;"></div>
          <div class="mb-3">
            <label class="form-label">Usuario destino</label>
            <select class="form-select" id="transferUsuarioDestino">
              <option value="">Seleccione usuario destino</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Motivo / comentario</label>
            <textarea class="form-control" id="transferComentario" rows="3" placeholder="Ingrese un comentario"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-outline-primary" id="btnSolicitarTransfer" onclick="solicitarTransferencia()">
            <i class="mdi mdi-send me-1"></i>Solicitar
          </button>
          <button type="button" class="btn btn-info text-white" id="btnTransferirDirecto" onclick="transferirTicketDirecto()">
            <i class="mdi mdi-swap-horizontal me-1"></i>Transferir
          </button>
          <button type="button" class="btn btn-success" id="btnAprobarTransfer" onclick="responderTransferencia('aprobar')">
            <i class="mdi mdi-check-circle me-1"></i>Aprobar
          </button>
          <button type="button" class="btn btn-danger" id="btnRechazarTransfer" onclick="responderTransferencia('rechazar')">
            <i class="mdi mdi-close-circle me-1"></i>Rechazar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal de Eliminar -->
  <div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0">
          <h5 class="modal-title"><i class="mdi mdi-alert-circle text-danger me-2"></i>Eliminar Ticket</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body text-center">
          <div style="width: 80px; height: 80px; background: #f8d7da; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
            <i class="mdi mdi-delete-outline" style="font-size: 40px; color: #dc3545;"></i>
          </div>
          <p class="mb-1">Esta seguro que desea eliminar el ticket?</p>
          <p class="text-primary fw-bold" id="codigoEliminar"></p>
          <p class="text-muted" style="font-size: 13px;">Esta accion no se puede deshacer.</p>
        </div>
        <div class="modal-footer border-0 justify-content-center">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" onclick="confirmarEliminar()">
            <i class="mdi mdi-delete me-1"></i>Si, Eliminar
          </button>
        </div>
      </div>
    </div>
  </div>
<script src="js/sidebar-badges.js"></script>
</body>
</html>





