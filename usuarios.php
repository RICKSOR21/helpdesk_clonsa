<?php
require_once 'config/session.php';
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
if ($user_rol !== 'Administrador' && $user_rol !== 'Admin' && $user_rol !== 'Jefe') {
    header('Location: dashboard.php');
    exit;
}

if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

$database = new Database();
$db = $database->getConnection();

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$departamento_usuario = $_SESSION['departamento_id'] ?? 1;
$puede_ver_todos = ($user_rol === 'Administrador' || $user_rol === 'Admin');
$SESSION_TIMEOUT_JS = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 120;
$SESSION_POPUP_TIMEOUT_JS = defined('SESSION_POPUP_TIMEOUT') ? SESSION_POPUP_TIMEOUT : 900;

$departamento_nombre = 'General';
if ($departamento_usuario) {
    $stmt = $db->prepare("SELECT nombre FROM departamentos WHERE id = ?");
    $stmt->execute([$departamento_usuario]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dept) {
        $departamento_nombre = $dept['nombre'];
    }
}

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
  <title>SIRA - Lista de Usuarios</title>
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
      background-color: #dc3545 !important; color: #ffffff !important; border-radius: 10px !important;
      min-width: 18px !important; height: 18px !important; padding: 0 5px !important; font-size: 11px !important;
      font-weight: 600 !important; display: none !important; align-items: center !important; justify-content: center !important;
      line-height: 18px !important; border: 2px solid #f4f5f7 !important; box-shadow: 0 1px 4px rgba(0,0,0,0.3) !important; z-index: 999 !important;
    }
    .count-indicator .badge-notif.show { display: flex !important; }
    .sidebar-badge {
      background: #dc3545; color: #fff; border-radius: 10px; min-width: 18px; height: 18px;
      padding: 0 6px; font-size: 11px; font-weight: 700; display: none; align-items: center;
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

    .users-card { background: #fff; border-radius: 14px; box-shadow: 0 8px 20px rgba(31,59,179,0.08); }
    .users-table thead th { font-size: 12px; text-transform: uppercase; color: #6b7280; font-weight: 700; border-bottom: 1px solid #e9ecef; }
    .users-table tbody td { vertical-align: middle; padding: 12px 10px; border-top: 1px solid #f2f4f7; }
    .avatar-user {
      width: 44px; height: 44px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
      background: #5b6ee1; color: #fff; font-weight: 700; font-size: 16px; margin-right: 10px;
    }
    .user-cell { display: flex; align-items: center; min-width: 230px; }
    .user-name { font-weight: 700; color: #111827; margin-bottom: 1px; }
    .user-meta { font-size: 12px; color: #6b7280; }
    .badge-role { padding: 6px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .badge-admin { background: rgba(31,59,179,0.14); color: #1f3bb3; }
    .badge-jefe { background: rgba(249,115,22,0.14); color: #b45309; }
    .badge-user { background: rgba(34,197,94,0.14); color: #15803d; }
    .badge-active { background: rgba(34,197,94,0.14); color: #15803d; font-weight: 700; padding: 6px 10px; border-radius: 20px; font-size: 11px; }
    .badge-inactive { background: rgba(220,53,69,0.14); color: #b4232f; font-weight: 700; padding: 6px 10px; border-radius: 20px; font-size: 11px; }
    .filters-row { display: flex; gap: 10px; align-items: end; flex-wrap: wrap; margin-bottom: 14px; }
    .filters-row .filter-item { min-width: 190px; }
    .filters-row label { font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; display: block; }
    .filters-row .form-control { height: 36px; border-radius: 8px; font-size: 13px; }
    #btnClearFilters,
    .toolbar-top .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      line-height: 1;
    }
    .pagination-wrap { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; flex-wrap: wrap; gap: 10px; }
    .pagination-wrap .info { font-size: 13px; color: #6b7280; }
    .pagination-users .btn { min-width: 36px; height: 34px; border-radius: 8px; margin-left: 4px; }
    .pagination-users .btn.active { background: #1f3bb3; color: #fff; border-color: #1f3bb3; }
    .toolbar-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; gap: 10px; flex-wrap: wrap; }
    .actions-group { display: flex; gap: 6px; flex-wrap: wrap; }
    .actions-group .btn { min-width: 36px; }
    .edit-user-modal .modal-dialog { max-width: 980px; }
    .edit-user-modal .modal-content { border: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 22px 44px rgba(2, 8, 23, 0.24); }
    .edit-user-modal .modal-header {
      border-bottom: 0;
      padding: 18px 24px;
      background: linear-gradient(145deg, #1f3bb3 0%, #3d64d8 100%);
      color: #fff;
    }
    .edit-user-modal .modal-title { font-weight: 700; font-size: 20px; display: flex; align-items: center; gap: 8px; }
    .edit-user-modal .btn-close { filter: brightness(0) invert(1); opacity: 0.95; }
    .edit-user-modal .modal-body { background: #f8faff; padding: 20px 24px; }
    .edit-form-card {
      background: #fff;
      border: 1px solid #e6ebf5;
      border-radius: 12px;
      padding: 16px;
      margin-bottom: 14px;
    }
    .edit-form-card:last-child { margin-bottom: 0; }
    .edit-section-title {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .35px;
      color: #6b7280;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 10px;
    }
    .edit-user-modal .required::after { content: ' *'; color: #dc3545; }
    .edit-user-modal .form-label { font-size: 13px; font-weight: 600; color: #4b5563; margin-bottom: 6px; }
    .edit-user-modal .form-control, .edit-user-modal .form-select {
      border-radius: 9px;
      min-height: 40px;
      border-color: #d9deea;
      font-size: 14px;
    }
    .edit-user-modal .form-control:focus, .edit-user-modal .form-select:focus {
      border-color: #1f3bb3;
      box-shadow: 0 0 0 0.15rem rgba(31,59,179,0.14);
    }
    .edit-user-modal .input-group-text {
      border-radius: 9px 0 0 9px;
      border-color: #d9deea;
      background: #f3f6fd;
      color: #64748b;
    }
    .edit-user-modal .input-group .form-control,
    .edit-user-modal .input-group .form-select { border-left: 0; }
    .edit-user-modal .modal-footer {
      background: #fff;
      border-top: 1px solid #e6ebf5;
      padding: 14px 24px 18px;
      gap: 8px;
    }
    .edit-user-modal .btn-light {
      border-color: #dce3f1;
      color: #475569;
      background: #fff;
    }
    .edit-user-modal .btn-primary {
      background: #1f3bb3;
      border-color: #1f3bb3;
      border-radius: 10px;
      font-weight: 600;
      padding: 9px 16px;
    }
    .edit-user-modal .hint-mini {
      font-size: 11px;
      color: #6b7280;
      margin-top: 6px;
    }
    .toast-fixed {
      position: fixed; right: 18px; bottom: 18px; z-index: 9999;
      background: #16a34a; color: #fff; padding: 10px 14px; border-radius: 10px;
      display: none; align-items: center; gap: 8px; box-shadow: 0 10px 22px rgba(0,0,0,0.2);
    }
    .toast-fixed.error { background: #dc2626; }
  </style>
</head>
<body class="authenticated">
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
          <h3 class="welcome-sub-text">Gestion de Usuarios - Portal SIRA Clonsa Ingenieria</h3>
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
          window.USER_DEPARTAMENTO = <?php echo (int)$departamento_usuario; ?>;
          window.PUEDE_VER_TODOS = <?php echo $puede_ver_todos ? 'true' : 'false'; ?>;
          window.CURRENT_USER_ID = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
        </script>
        <li class="nav-item dropdown">
          <a class="nav-link count-indicator" id="comunicadosDropdown" href="#" data-bs-toggle="dropdown"><i class="icon-mail icon-lg"></i><span class="count count-comunicados badge-notif"></span></a>
          <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="comunicadosDropdown" style="width: 340px;">
            <div class="dropdown-item py-2 border-bottom d-flex justify-content-between align-items-center" style="cursor: default; background: #f8f9fa;">
              <div class="d-flex align-items-center"><i class="mdi mdi-email-outline text-primary me-2" style="font-size: 18px;"></i><span class="font-weight-bold" style="font-size: 13px;">Comunicados</span></div>
              <a href="comunicados.php" class="btn btn-sm btn-primary" style="font-size: 10px; padding: 3px 10px;">Ver todos <i class="mdi mdi-arrow-right"></i></a>
            </div>
            <div id="comunicadosContainer" style="max-height: 280px; overflow-y: auto;"><div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div></div>
          </div>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link count-indicator" id="ticketsDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false"><i class="icon-bell"></i><span class="count count-tickets badge-notif"></span></a>
          <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="ticketsDropdown" style="width: 340px;">
            <div class="dropdown-item py-2 border-bottom d-flex justify-content-between align-items-center" style="cursor: default; background: #f8f9fa;">
              <div class="d-flex align-items-center"><i class="mdi mdi-bell-outline text-primary me-2" style="font-size: 18px;"></i><span class="font-weight-bold" style="font-size: 13px;">Notificaciones</span></div>
              <a href="notificaciones.php" class="btn btn-sm btn-primary" style="font-size: 10px; padding: 3px 10px;">Ver todas <i class="mdi mdi-arrow-right"></i></a>
            </div>
            <div id="ticketsNotificacionesContainer" style="max-height: 280px; overflow-y: auto;"><div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></div></div>
          </div>
        </li>
        <li class="nav-item dropdown d-none d-lg-block user-dropdown">
          <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false"><img class="img-xs rounded-circle" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=667eea&color=fff&size=128" alt="Profile image"></a>
          <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
            <div class="dropdown-header text-center">
              <img class="img-md rounded-circle" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=667eea&color=fff&size=128" alt="Profile image">
              <p class="mb-1 mt-3 font-weight-semibold"><?php echo htmlspecialchars($user_name); ?></p>
              <p class="fw-light text-muted mb-0"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
            </div>
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
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="mdi mdi-view-dashboard menu-icon"></i><span class="menu-title">Dashboard</span></a></li>
        <li class="nav-item nav-category">GESTION DE TICKETS</li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#tickets-menu" aria-expanded="true" aria-controls="tickets-menu">
            <i class="menu-icon mdi mdi-ticket-confirmation"></i><span class="menu-title">Tickets</span><i class="menu-arrow"></i>
          </a>
          <div class="collapse show" id="tickets-menu">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="tickets.php"><span>Todos los Tickets</span><span class="count-todos-sidebar sidebar-badge">0</span></a></li>
              <li class="nav-item"><a class="nav-link" href="tickets-create.php">Crear Ticket</a></li>
              <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?>
              <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="tickets-mis.php"><span>Mis Tickets</span><span class="count-mis-sidebar sidebar-badge">0</span></a></li>
              <?php endif; ?>
              <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="tickets-asignados.php"><span>Asignados a Mi</span><span class="count-asignados-sidebar sidebar-badge">0</span></a></li>
              <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="notificaciones.php"><span>Notificaciones</span><span class="count-notificaciones-sidebar sidebar-badge">0</span></a></li>
            </ul>
          </div>
        </li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#usuarios-menu" aria-expanded="true" aria-controls="usuarios-menu">
            <i class="menu-icon mdi mdi-account-multiple"></i><span class="menu-title">Usuarios</span><i class="menu-arrow"></i>
          </a>
          <div class="collapse show" id="usuarios-menu">
            <ul class="nav flex-column sub-menu">
              <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin'): ?>
              <li class="nav-item"><a class="nav-link" href="usuarios-create.php">Crear Usuario</a></li>
              <?php endif; ?>
              <li class="nav-item"><a class="nav-link active" href="usuarios.php">Lista de Usuarios</a></li>
            </ul>
          </div>
        </li>
        <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin'): ?>
        <li class="nav-item nav-category">CONFIGURACION</li>
        <li class="nav-item"><a class="nav-link" href="perfil.php"><i class="menu-icon mdi mdi-account-circle-outline"></i><span class="menu-title">Perfil</span></a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="collapse" href="#catalogos-menu" aria-expanded="false" aria-controls="catalogos-menu"><i class="menu-icon mdi mdi-table-settings"></i><span class="menu-title">Catalogos</span><i class="menu-arrow"></i></a>
          <div class="collapse" id="catalogos-menu"><ul class="nav flex-column sub-menu">
            <li class="nav-item"><a class="nav-link" href="catalogos-departamentos.php">Departamentos</a></li>
            <li class="nav-item"><a class="nav-link" href="catalogos-canales.php">Canales de Atencion</a></li>
            <li class="nav-item"><a class="nav-link" href="catalogos-actividades.php">Tipos de Actividad</a></li>
            <li class="nav-item"><a class="nav-link" href="catalogos-fallas.php">Tipos de Falla</a></li>
            <li class="nav-item"><a class="nav-link" href="catalogos-ubicaciones.php">Ubicaciones</a></li>
            <li class="nav-item"><a class="nav-link" href="catalogos-equipos.php">Equipos</a></li>
          </ul></div>
        </li>
        <?php endif; ?>
        <li class="nav-item nav-category">REPORTES</li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="collapse" href="#reportes-menu" aria-expanded="false" aria-controls="reportes-menu"><i class="menu-icon mdi mdi-chart-line"></i><span class="menu-title">Reportes</span><i class="menu-arrow"></i></a>
          <div class="collapse" id="reportes-menu"><ul class="nav flex-column sub-menu">
            <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?>
            <li class="nav-item"><a class="nav-link" href="reportes-general.php">Reporte General</a></li>
            <li class="nav-item"><a class="nav-link" href="reportes-departamento.php">Por Departamento</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="reportes-usuario.php">Por Usuario</a></li>
          </ul></div>
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
        <div class="row">
          <div class="col-md-12">
            <div class="card users-card">
              <div class="card-body">
                <div class="toolbar-top">
                  <h4 class="card-title mb-0"><i class="mdi mdi-account-group me-1"></i>Lista de Usuarios</h4>
                  <div class="d-flex gap-2">
                    <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin'): ?>
                    <a href="usuarios-create.php" class="btn btn-primary btn-sm"><i class="mdi mdi-account-plus me-1"></i>Crear Usuario</a>
                    <?php endif; ?>
                    <button class="btn btn-outline-primary btn-sm" id="btnReloadUsers"><i class="mdi mdi-refresh me-1"></i>Actualizar</button>
                  </div>
                </div>

                <div class="filters-row">
                  <div class="filter-item" style="min-width:260px;">
                    <label>Buscar</label>
                    <input type="text" id="fSearch" class="form-control" placeholder="Nombre, username o email">
                  </div>
                  <div class="filter-item">
                    <label>Rol</label>
                    <select id="fRol" class="form-control"><option value="">Todos</option></select>
                  </div>
                  <div class="filter-item">
                    <label>Departamento</label>
                    <select id="fDepto" class="form-control"><option value="">Todos</option></select>
                  </div>
                  <div class="filter-item">
                    <label>Estado</label>
                    <select id="fEstado" class="form-control">
                      <option value="">Todos</option>
                      <option value="1">Activos</option>
                      <option value="0">Inactivos</option>
                    </select>
                  </div>
                  <div class="filter-item" style="min-width:120px;">
                    <button type="button" id="btnClearFilters" class="btn btn-outline-secondary w-100" style="height:36px;">Limpiar</button>
                  </div>
                </div>

                <div class="table-responsive">
                  <table class="table users-table mb-0">
                    <thead>
                      <tr>
                        <th>Usuario</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Departamento</th>
                        <th>Estado</th>
                        <th>Ultimo Acceso</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody id="usersBody">
                      <tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div></td></tr>
                    </tbody>
                  </table>
                </div>

                <div class="pagination-wrap">
                  <div class="info" id="usersInfo">Mostrando 0 de 0 usuarios</div>
                  <div class="pagination-users" id="usersPagination"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="toast-fixed" id="toastUsers"><i class="mdi mdi-check-circle"></i><span id="toastUsersMsg">OK</span></div>

<div class="modal fade edit-user-modal" id="modalEditUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="mdi mdi-account-edit-outline"></i>Editar Usuario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="formEditUser">
        <div class="modal-body">
          <input type="hidden" id="edit_usuario_id" name="usuario_id">
          <div class="edit-form-card">
            <div class="edit-section-title"><i class="mdi mdi-account-card-details"></i>Datos Personales</div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label required">Nombre completo</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="mdi mdi-account-outline"></i></span>
                  <input type="text" class="form-control" id="edit_nombre_completo" name="nombre_completo" required>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label required">Username</label>
                <div class="input-group">
                  <span class="input-group-text">@</span>
                  <input type="text" class="form-control" id="edit_username" name="username" required>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label required">Email</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="mdi mdi-email-outline"></i></span>
                  <input type="email" class="form-control" id="edit_email" name="email" required>
                </div>
              </div>
              <div class="col-md-6">
                <label class="form-label">Telefono</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="mdi mdi-phone-outline"></i></span>
                  <input type="text" class="form-control" id="edit_telefono" name="telefono">
                </div>
              </div>
            </div>
          </div>

          <div class="edit-form-card">
            <div class="edit-section-title"><i class="mdi mdi-shield-account-outline"></i>Permisos y Estado</div>
            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label required">Rol</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="mdi mdi-account-badge-outline"></i></span>
                  <select class="form-select" id="edit_rol_id" name="rol_id" required></select>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label required">Departamento</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="mdi mdi-office-building-outline"></i></span>
                  <select class="form-select" id="edit_departamento_id" name="departamento_id" required></select>
                </div>
              </div>
              <div class="col-md-4">
                <label class="form-label required">Estado</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="mdi mdi-toggle-switch-outline"></i></span>
                  <select class="form-select" id="edit_activo" name="activo" required>
                    <option value="1">Activo</option>
                    <option value="0">Inactivo</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="edit-form-card" id="edit_seccion_notificaciones">
            <div class="edit-section-title"><i class="mdi mdi-email-outline"></i>Notificaciones por Correo</div>
            <div class="row g-3">
              <div class="col-12">
                <div class="form-check form-switch" style="padding-left: 3em;">
                  <input type="checkbox" class="form-check-input" id="edit_recibir_notificaciones_email" name="recibir_notificaciones_email" value="1" role="switch" style="width: 2.5em; height: 1.25em; margin-left: -3em; cursor: pointer;">
                  <label class="form-check-label" for="edit_recibir_notificaciones_email" style="cursor: pointer;">
                    <i class="mdi mdi-email-check-outline me-1"></i>Notificar al administrador sobre este usuario
                  </label>
                  <div class="hint-mini">Si est&aacute; activo, el administrador recibir&aacute; correos cuando este usuario cree tickets, comente, transfiera, etc.</div>
                </div>
              </div>
            </div>
          </div>

          <div class="edit-form-card">
            <div class="edit-section-title"><i class="mdi mdi-key-outline"></i>Seguridad</div>
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Nueva clave (opcional)</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="mdi mdi-lock-outline"></i></span>
                  <input type="password" class="form-control" id="edit_password" name="password" minlength="6" placeholder="Dejar vacio para mantener la clave actual">
                </div>
                <div class="hint-mini">Si no desea cambiar la clave, deje este campo en blanco.</div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i>Guardar cambios</button>
        </div>
      </form>
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
  let allUsers = [];
  let filteredUsers = [];
  let currentPage = 1;
  let catalogos = { roles: [], departamentos: [] };
  let editModal = null;

  function initials(name) {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].substring(0,2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function roleBadge(roleName) {
    if (roleName === 'Administrador' || roleName === 'Admin') return '<span class="badge-role badge-admin">Administrador</span>';
    if (roleName === 'Jefe') return '<span class="badge-role badge-jefe">Jefe</span>';
    return '<span class="badge-role badge-user">Usuario</span>';
  }

  function formatDateTime(value) {
    if (!value) return '-';
    const d = new Date(value.replace(' ', 'T'));
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleString('es-PE', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' });
  }

  function showToast(msg, isError) {
    const el = document.getElementById('toastUsers');
    document.getElementById('toastUsersMsg').textContent = msg;
    el.classList.toggle('error', !!isError);
    el.style.display = 'inline-flex';
    setTimeout(() => { el.style.display = 'none'; }, 2300);
  }

  function getFilteredUsers() {
    const q = (document.getElementById('fSearch').value || '').toLowerCase().trim();
    const r = document.getElementById('fRol').value;
    const d = document.getElementById('fDepto').value;
    const e = document.getElementById('fEstado').value;

    return allUsers.filter(u => {
      if (r && String(u.rol_id) !== r) return false;
      if (d && String(u.departamento_id || '') !== d) return false;
      if (e !== '' && String(u.activo) !== e) return false;
      if (q) {
        const txt = `${u.nombre_completo || ''} ${u.username || ''} ${u.email || ''}`.toLowerCase();
        if (!txt.includes(q)) return false;
      }
      return true;
    });
  }

  function renderPagination(total) {
    const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    if (currentPage > pages) currentPage = pages;

    const wrap = document.getElementById('usersPagination');
    let html = `<button class="btn btn-outline-secondary" ${currentPage===1?'disabled':''} data-p="${currentPage-1}">‹</button>`;
    const start = Math.max(1, currentPage - 2);
    const end = Math.min(pages, start + 4);
    for (let p = start; p <= end; p++) {
      html += `<button class="btn btn-outline-secondary ${p===currentPage?'active':''}" data-p="${p}">${p}</button>`;
    }
    html += `<button class="btn btn-outline-secondary" ${currentPage===pages?'disabled':''} data-p="${currentPage+1}">›</button>`;
    wrap.innerHTML = html;

    wrap.querySelectorAll('button[data-p]').forEach(b => {
      b.addEventListener('click', () => {
        const p = parseInt(b.getAttribute('data-p'), 10);
        if (!Number.isNaN(p) && p >= 1) {
          currentPage = p;
          renderUsers();
        }
      });
    });
  }

  function renderUsers() {
    filteredUsers = getFilteredUsers();
    const total = filteredUsers.length;
    const body = document.getElementById('usersBody');

    if (!total) {
      body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No hay usuarios para mostrar</td></tr>';
      document.getElementById('usersInfo').textContent = 'Mostrando 0 de 0 usuarios';
      document.getElementById('usersPagination').innerHTML = '';
      return;
    }

    const from = (currentPage - 1) * PAGE_SIZE;
    const pageItems = filteredUsers.slice(from, from + PAGE_SIZE);
    const currentUserId = parseInt(window.CURRENT_USER_ID || 0, 10);
    const isAdmin = (window.USER_ROL === 'Administrador' || window.USER_ROL === 'Admin');
    const isJefe = window.USER_ROL === 'Jefe';

    let html = '';
    pageItems.forEach(u => {
      const active = parseInt(u.activo || 0, 10) === 1;
      const isTargetAdmin = (u.rol_nombre === 'Administrador' || u.rol_nombre === 'Admin');
      const statusBadge = active ? '<span class="badge-active">Activo</span>' : '<span class="badge-inactive">Inactivo</span>';
      let btnAction = '<span class="text-muted">-</span>';
      const rowId = parseInt(u.id, 10);

      if (isAdmin) {
        const toggleBtn = isTargetAdmin
          ? `<button class="btn btn-sm btn-outline-secondary" disabled title="Administrador no desactivable"><i class="mdi mdi-shield-account"></i></button>`
          : (active
              ? `<button class="btn btn-sm btn-outline-warning btn-toggle" data-id="${rowId}" data-activo="0" title="Desactivar"><i class="mdi mdi-account-off"></i></button>`
              : `<button class="btn btn-sm btn-outline-success btn-toggle" data-id="${rowId}" data-activo="1" title="Activar"><i class="mdi mdi-account-check"></i></button>`);
        const editBtn = `<button class="btn btn-sm btn-outline-primary btn-edit" data-id="${rowId}" title="Editar"><i class="mdi mdi-pencil"></i></button>`;
        const deleteBtn = rowId === currentUserId
          ? `<button class="btn btn-sm btn-outline-secondary" disabled title="No permitido"><i class="mdi mdi-delete"></i></button>`
          : `<button class="btn btn-sm btn-outline-danger btn-delete" data-id="${rowId}" title="Eliminar"><i class="mdi mdi-delete"></i></button>`;
        btnAction = `<div class="actions-group">${editBtn}${toggleBtn}${deleteBtn}</div>`;
      } else if (isJefe) {
        const esSiMismo    = rowId === currentUserId;
        const esJefeOAdmin = (u.rol_nombre === 'Administrador' || u.rol_nombre === 'Admin' || u.rol_nombre === 'Jefe');
        if (!esSiMismo && !esJefeOAdmin) {
          btnAction = active
            ? `<button class="btn btn-sm btn-outline-warning btn-toggle" data-id="${rowId}" data-activo="0" title="Desactivar"><i class="mdi mdi-account-off"></i></button>`
            : `<button class="btn btn-sm btn-outline-success btn-toggle" data-id="${rowId}" data-activo="1" title="Activar"><i class="mdi mdi-account-check"></i></button>`;
        }
      }

      html += `
      <tr>
        <td>
          <div class="user-cell">
            <span class="avatar-user">${initials(u.nombre_completo)}</span>
            <div>
              <div class="user-name">${escapeHtml(u.nombre_completo || '-')}</div>
              <div class="user-meta">@${escapeHtml(u.username || '-')}</div>
            </div>
          </div>
        </td>
        <td>${escapeHtml(u.email || '-')}</td>
        <td>${roleBadge(u.rol_nombre || '')}</td>
        <td>${escapeHtml(u.departamento_nombre || 'Sin departamento')}</td>
        <td>${statusBadge}</td>
        <td>${formatDateTime(u.ultimo_acceso)}</td>
        <td>${btnAction}</td>
      </tr>`;
    });

    body.innerHTML = html;
    const to = Math.min(from + PAGE_SIZE, total);
    document.getElementById('usersInfo').textContent = `Mostrando ${from + 1}-${to} de ${total} usuarios`;
    renderPagination(total);

    body.querySelectorAll('.btn-toggle').forEach(btn => {
      btn.addEventListener('click', () => {
        const formData = new URLSearchParams();
        formData.append('action', 'cambiar_estado');
        formData.append('usuario_id', btn.getAttribute('data-id'));
        formData.append('activo', btn.getAttribute('data-activo'));
        fetch('api/usuarios.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: formData.toString()
        })
        .then(r => r.json())
        .then(r => {
          if (!r.success) {
            showToast(r.message || 'No se pudo actualizar', true);
            return;
          }
          showToast(r.message || 'Actualizado');
          loadUsers();
        })
        .catch(() => showToast('Error de red', true));
      });
    });

    body.querySelectorAll('.btn-edit').forEach(btn => {
      btn.addEventListener('click', () => openEditModal(btn.getAttribute('data-id')));
    });

    body.querySelectorAll('.btn-delete').forEach(btn => {
      btn.addEventListener('click', () => deleteUser(btn.getAttribute('data-id')));
    });
  }

  function fillFilterCatalogs(users) {
    const rol = document.getElementById('fRol');
    const dept = document.getElementById('fDepto');
    const mapRol = new Map();
    const mapDept = new Map();
    users.forEach(u => {
      mapRol.set(String(u.rol_id), u.rol_nombre || 'Rol');
      mapDept.set(String(u.departamento_id || ''), u.departamento_nombre || 'Sin departamento');
    });

    rol.innerHTML = '<option value="">Todos</option>';
    dept.innerHTML = '<option value="">Todos</option>';
    Array.from(mapRol.entries()).sort((a,b)=>a[1].localeCompare(b[1])).forEach(([id, n]) => {
      rol.innerHTML += `<option value="${id}">${n}</option>`;
    });
    Array.from(mapDept.entries()).filter(([id]) => id !== '').sort((a,b)=>a[1].localeCompare(b[1])).forEach(([id, n]) => {
      dept.innerHTML += `<option value="${id}">${n}</option>`;
    });
  }

  function loadUsers() {
    fetch('api/usuarios.php?action=listar', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(r => {
        if (!r.success) throw new Error(r.message || 'Error');
        allUsers = r.data || [];
        fillFilterCatalogs(allUsers);
        currentPage = 1;
        renderUsers();
      })
      .catch(() => {
        document.getElementById('usersBody').innerHTML = '<tr><td colspan="7" class="text-center text-danger py-4">Error al cargar usuarios</td></tr>';
      });
  }

  function loadCatalogos() {
    return fetch('api/usuarios.php?action=catalogos', { credentials: 'same-origin' })
      .then(r => r.json())
      .then(r => {
        if (!r.success) {
          throw new Error(r.message || 'Error catalogos');
        }
        catalogos = r.data || { roles: [], departamentos: [] };
        const rolSel = document.getElementById('edit_rol_id');
        const depSel = document.getElementById('edit_departamento_id');
        rolSel.innerHTML = '<option value="">Seleccione rol</option>';
        depSel.innerHTML = '<option value="">Seleccione departamento</option>';
        (catalogos.roles || []).forEach(item => {
          rolSel.innerHTML += `<option value="${item.id}">${escapeHtml(item.nombre)}</option>`;
        });
        (catalogos.departamentos || []).forEach(item => {
          depSel.innerHTML += `<option value="${item.id}">${escapeHtml(item.nombre)}</option>`;
        });
      });
  }

  function openEditModal(userId) {
    fetch(`api/usuarios.php?action=obtener&usuario_id=${encodeURIComponent(userId)}`, { credentials: 'same-origin' })
      .then(r => r.json())
      .then(r => {
        if (!r.success || !r.data) {
          throw new Error(r.message || 'No se pudo obtener usuario');
        }
        const u = r.data;
        document.getElementById('edit_usuario_id').value = u.id || '';
        document.getElementById('edit_nombre_completo').value = u.nombre_completo || '';
        document.getElementById('edit_username').value = u.username || '';
        document.getElementById('edit_email').value = u.email || '';
        document.getElementById('edit_telefono').value = u.telefono || '';
        document.getElementById('edit_rol_id').value = String(u.rol_id || '');
        document.getElementById('edit_departamento_id').value = String(u.departamento_id || '');
        document.getElementById('edit_activo').value = String(parseInt(u.activo, 10) === 1 ? 1 : 0);
        document.getElementById('edit_password').value = '';
        document.getElementById('edit_recibir_notificaciones_email').checked = parseInt(u.recibir_notificaciones_email || 1, 10) === 1;
        const estadoSelect = document.getElementById('edit_activo');
        const isTargetAdmin = (u.rol_nombre === 'Administrador' || u.rol_nombre === 'Admin');
        const optInactivo = estadoSelect.querySelector('option[value="0"]');
        if (optInactivo) {
          optInactivo.disabled = isTargetAdmin;
        }
        if (isTargetAdmin) {
          estadoSelect.value = '1';
        }
        editModal.show();
      })
      .catch(err => showToast(err.message || 'No se pudo cargar usuario', true));
  }

  function deleteUser(userId) {
    if (!confirm('Confirma eliminar este usuario? Esta accion no se puede deshacer.')) return;
    const formData = new URLSearchParams();
    formData.append('action', 'eliminar');
    formData.append('usuario_id', userId);
    fetch('api/usuarios.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: formData.toString()
    })
    .then(r => r.json())
    .then(r => {
      if (!r.success) {
        throw new Error(r.message || 'No se pudo eliminar');
      }
      showToast(r.message || 'Usuario eliminado');
      loadUsers();
    })
    .catch(err => showToast(err.message || 'Error de red', true));
  }

  function bindFilters() {
    ['fSearch','fRol','fDepto','fEstado'].forEach(id => {
      const el = document.getElementById(id);
      const evt = id === 'fSearch' ? 'input' : 'change';
      el.addEventListener(evt, () => {
        currentPage = 1;
        renderUsers();
      });
    });
    document.getElementById('btnClearFilters').addEventListener('click', () => {
      document.getElementById('fSearch').value = '';
      document.getElementById('fRol').value = '';
      document.getElementById('fDepto').value = '';
      document.getElementById('fEstado').value = '';
      currentPage = 1;
      renderUsers();
    });
  }

  function bindEditForm() {
    const form = document.getElementById('formEditUser');
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const formData = new URLSearchParams();
      formData.append('action', 'actualizar');
      formData.append('usuario_id', document.getElementById('edit_usuario_id').value.trim());
      formData.append('nombre_completo', document.getElementById('edit_nombre_completo').value.trim());
      formData.append('username', document.getElementById('edit_username').value.trim());
      formData.append('email', document.getElementById('edit_email').value.trim());
      formData.append('telefono', document.getElementById('edit_telefono').value.trim());
      formData.append('rol_id', document.getElementById('edit_rol_id').value);
      formData.append('departamento_id', document.getElementById('edit_departamento_id').value);
      formData.append('activo', document.getElementById('edit_activo').value);
      formData.append('recibir_notificaciones_email', document.getElementById('edit_recibir_notificaciones_email').checked ? 1 : 0);
      formData.append('password', document.getElementById('edit_password').value);

      fetch('api/usuarios.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(r => r.json())
      .then(r => {
        if (!r.success) {
          throw new Error(r.message || 'No se pudo guardar');
        }
        editModal.hide();
        showToast(r.message || 'Usuario actualizado');
        loadUsers();
      })
      .catch(err => showToast(err.message || 'Error de red', true));
    });
  }

  document.getElementById('btnReloadUsers').addEventListener('click', loadUsers);
  editModal = new bootstrap.Modal(document.getElementById('modalEditUser'));
  bindFilters();
  bindEditForm();
  loadCatalogos().finally(loadUsers);
})();
</script>
<script src="js/sidebar-badges.js"></script>
</body>
</html>



