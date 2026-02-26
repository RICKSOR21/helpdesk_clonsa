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
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$departamento_usuario = (int)($_SESSION['departamento_id'] ?? 0);
$puede_ver_todos = ($user_rol === 'Administrador' || $user_rol === 'Admin');

$database = new Database();
$db = $database->getConnection();

$departamento_nombre = 'General';
if ($departamento_usuario > 0) {
    $stmtDept = $db->prepare('SELECT nombre FROM departamentos WHERE id = :id LIMIT 1');
    $stmtDept->execute([':id' => $departamento_usuario]);
    $rowDept = $stmtDept->fetch(PDO::FETCH_ASSOC);
    if ($rowDept) {
        $departamento_nombre = (string)$rowDept['nombre'];
    }
}

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
  <title>SIRA - Documentacion</title>
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
      line-height: 18px !important; border: 2px solid #f4f5f7 !important;
    }
    .count-indicator .badge-notif.show { display: flex !important; }
    .sidebar-badge {
      background: #dc3545; color: #fff; border-radius: 10px; min-width: 18px; height: 18px;
      padding: 0 6px; font-size: 11px; font-weight: 700; display: none; align-items: center;
      justify-content: center; line-height: 18px; margin-left: 8px;
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

    /* ===== DOCUMENTACION STYLES ===== */
    .doc-hero {
      background: linear-gradient(135deg, #1f3bb3 0%, #3d64d8 50%, #6366f1 100%);
      border-radius: 16px;
      padding: 40px 36px;
      color: #fff;
      position: relative;
      overflow: hidden;
      margin-bottom: 20px;
      box-shadow: 0 16px 40px rgba(31,59,179,0.25);
    }
    .doc-hero::before {
      content: '';
      position: absolute;
      top: -60px; right: -60px;
      width: 260px; height: 260px;
      background: rgba(255,255,255,0.06);
      border-radius: 50%;
    }
    .doc-hero::after {
      content: '';
      position: absolute;
      bottom: -40px; left: 30%;
      width: 180px; height: 180px;
      background: rgba(255,255,255,0.04);
      border-radius: 50%;
    }
    .doc-hero-content { position: relative; z-index: 1; }
    .doc-hero-logo { max-height: 54px; margin-bottom: 16px; filter: brightness(0) invert(1); }
    .doc-hero h1 { font-size: 30px; font-weight: 900; margin: 0 0 6px; letter-spacing: -0.3px; }
    .doc-hero h2 { font-size: 16px; font-weight: 400; opacity: 0.85; margin: 0 0 12px; }
    .doc-hero-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(255,255,255,0.15); border-radius: 999px;
      padding: 6px 16px; font-size: 12px; font-weight: 600;
      backdrop-filter: blur(8px);
    }

    .doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 16px; margin-bottom: 20px; }

    .doc-card {
      background: #fff;
      border-radius: 14px;
      border: 1px solid #eef2ff;
      box-shadow: 0 8px 20px rgba(31,59,179,0.06);
      transition: all 0.25s ease;
      overflow: hidden;
    }
    .doc-card:hover {
      box-shadow: 0 12px 28px rgba(31,59,179,0.12);
      transform: translateY(-2px);
    }
    .doc-card-head {
      padding: 18px 20px 14px;
      display: flex; align-items: center; gap: 14px;
      border-bottom: 1px solid #f1f4f9;
    }
    .doc-card-icon {
      width: 46px; height: 46px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; flex-shrink: 0;
    }
    .doc-card-head h3 { font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; }
    .doc-card-head p { font-size: 12px; color: #64748b; margin: 2px 0 0; }
    .doc-card-body { padding: 16px 20px 20px; }
    .doc-card-body ul { list-style: none; padding: 0; margin: 0; }
    .doc-card-body li {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 8px 0;
      border-bottom: 1px solid #f7f8fc;
      font-size: 13px; color: #334155; line-height: 1.45;
    }
    .doc-card-body li:last-child { border-bottom: none; }
    .doc-card-body li i { color: #1f3bb3; font-size: 16px; margin-top: 1px; flex-shrink: 0; }

    .icon-blue { background: rgba(31,59,179,0.1); color: #1f3bb3; }
    .icon-green { background: rgba(34,197,94,0.12); color: #15803d; }
    .icon-orange { background: rgba(245,158,11,0.12); color: #92400e; }
    .icon-purple { background: rgba(139,92,246,0.12); color: #7c3aed; }
    .icon-cyan { background: rgba(6,182,212,0.12); color: #0e7490; }
    .icon-red { background: rgba(239,68,68,0.12); color: #dc2626; }

    .doc-states {
      background: #fff;
      border-radius: 14px;
      border: 1px solid #eef2ff;
      box-shadow: 0 8px 20px rgba(31,59,179,0.06);
      padding: 24px;
      margin-bottom: 20px;
    }
    .doc-states h3 { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 18px; display: flex; align-items: center; gap: 10px; }
    .state-flow { display: flex; align-items: center; gap: 0; flex-wrap: wrap; justify-content: center; }
    .state-item {
      display: flex; flex-direction: column; align-items: center; gap: 6px;
      padding: 14px 18px; border-radius: 12px; min-width: 120px;
      text-align: center; transition: all 0.2s ease;
    }
    .state-item:hover { background: #f8faff; }
    .state-dot {
      width: 42px; height: 42px;
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
      font-size: 20px; color: #fff;
    }
    .state-label { font-size: 12px; font-weight: 700; color: #334155; }
    .state-desc { font-size: 10px; color: #94a3b8; max-width: 100px; }
    .state-arrow { font-size: 22px; color: #cbd5e1; margin: 0 -4px; }

    .dot-blue { background: linear-gradient(135deg, #1f3bb3, #3d64d8); }
    .dot-yellow { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
    .dot-orange { background: linear-gradient(135deg, #f97316, #fb923c); }
    .dot-green { background: linear-gradient(135deg, #22c55e, #4ade80); }
    .dot-red { background: linear-gradient(135deg, #ef4444, #f87171); }

    .doc-footer {
      text-align: center; padding: 20px 0 8px;
      font-size: 12px; color: #94a3b8;
    }
    .doc-footer strong { color: #1f3bb3; }

    .doc-roles {
      background: #fff;
      border-radius: 14px;
      border: 1px solid #eef2ff;
      box-shadow: 0 8px 20px rgba(31,59,179,0.06);
      padding: 24px;
      margin-bottom: 20px;
    }
    .doc-roles h3 { font-size: 18px; font-weight: 800; color: #0f172a; margin: 0 0 18px; display: flex; align-items: center; gap: 10px; }
    .roles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 14px; }
    .role-item {
      border: 1px solid #eef2ff; border-radius: 12px; padding: 16px;
      text-align: center; transition: all 0.2s ease;
    }
    .role-item:hover { background: #f8faff; border-color: #d4deff; }
    .role-icon { font-size: 28px; margin-bottom: 6px; }
    .role-name { font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
    .role-desc { font-size: 11px; color: #64748b; line-height: 1.4; }
  </style>
</head>
<body>
<div class="container-scroller">
  <nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
      <div class="me-3"><button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize"><span class="icon-menu"></span></button></div>
      <div>
        <a class="navbar-brand brand-logo" href="dashboard.php"><img src="template/images/logo.svg" alt="logo"></a>
        <a class="navbar-brand brand-logo-mini" href="dashboard.php"><img src="template/images/logo-mini.svg" alt="logo"></a>
      </div>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-top">
      <ul class="navbar-nav">
        <li class="nav-item font-weight-semibold d-none d-lg-block ms-0">
          <h1 class="welcome-text"><?php echo $saludo; ?>, <span class="text-black fw-bold"><?php echo htmlspecialchars($primer_nombre); ?></span></h1>
          <h3 class="welcome-sub-text">Centro de documentacion y ayuda del sistema</h3>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item dropdown d-none d-lg-block">
          <span class="nav-link dropdown-bordered" style="cursor: default; background-color: #e9ecef; opacity: .9; pointer-events:none;">
            <i class="mdi mdi-office-building me-1"></i> <?php echo htmlspecialchars($departamento_nombre); ?>
          </span>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link count-indicator" id="comunicadosDropdown" href="#" data-bs-toggle="dropdown"><i class="icon-mail icon-lg"></i><span class="count count-comunicados badge-notif"></span></a>
          <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="comunicadosDropdown" style="width:340px">
            <div class="dropdown-item py-2 border-bottom d-flex justify-content-between align-items-center" style="background:#f8f9fa">
              <div class="d-flex align-items-center"><i class="mdi mdi-email-outline text-primary me-2"></i><span class="font-weight-bold" style="font-size:13px">Comunicados</span></div>
              <a href="comunicados.php" class="btn btn-sm btn-primary" style="font-size:10px;padding:3px 10px">Ver todos <i class="mdi mdi-arrow-right"></i></a>
            </div>
            <div id="comunicadosContainer" style="max-height:280px;overflow-y:auto"><div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div></div>
          </div>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link count-indicator" id="ticketsDropdown" href="#" data-bs-toggle="dropdown"><i class="icon-bell"></i><span class="count count-tickets badge-notif"></span></a>
          <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="ticketsDropdown" style="width:340px">
            <div class="dropdown-item py-2 border-bottom d-flex justify-content-between align-items-center" style="background:#f8f9fa">
              <div class="d-flex align-items-center"><i class="mdi mdi-bell-outline text-primary me-2"></i><span class="font-weight-bold" style="font-size:13px">Notificaciones</span></div>
              <a href="notificaciones.php" class="btn btn-sm btn-primary" style="font-size:10px;padding:3px 10px">Ver todas <i class="mdi mdi-arrow-right"></i></a>
            </div>
            <div id="ticketsNotificacionesContainer" style="max-height:280px;overflow-y:auto"><div class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div></div>
          </div>
        </li>
        <li class="nav-item dropdown d-none d-lg-block user-dropdown">
          <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown">
            <img class="img-xs rounded-circle" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=667eea&color=fff&size=128" alt="Profile image">
          </a>
          <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
            <div class="dropdown-header text-center">
              <img class="img-md rounded-circle" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=667eea&color=fff&size=128" alt="Profile image">
              <p class="mb-1 mt-3 font-weight-semibold"><?php echo htmlspecialchars($user_name); ?></p>
              <p class="fw-light text-muted mb-0"><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></p>
            </div>
            <a class="dropdown-item" href="perfil.php"><i class="dropdown-item-icon mdi mdi-account-outline text-primary me-2"></i>Mi Perfil</a>
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
        <li class="nav-item"><a class="nav-link" data-bs-toggle="collapse" href="#tickets-menu" aria-expanded="false" aria-controls="tickets-menu"><i class="menu-icon mdi mdi-ticket-confirmation"></i><span class="menu-title">Tickets</span><i class="menu-arrow"></i></a>
          <div class="collapse" id="tickets-menu"><ul class="nav flex-column sub-menu">
            <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="tickets.php"><span>Todos los Tickets</span><span class="count-todos-sidebar sidebar-badge">0</span></a></li>
            <li class="nav-item"><a class="nav-link" href="tickets-create.php">Crear Ticket</a></li>
            <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?>
            <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="tickets-mis.php"><span>Mis Tickets</span><span class="count-mis-sidebar sidebar-badge">0</span></a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="tickets-asignados.php"><span>Asignados a Mi</span><span class="count-asignados-sidebar sidebar-badge">0</span></a></li>
            <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="notificaciones.php"><span>Notificaciones</span><span class="count-notificaciones-sidebar sidebar-badge">0</span></a></li>
          </ul></div>
        </li>
        <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="collapse" href="#usuarios-menu" aria-expanded="false" aria-controls="usuarios-menu"><i class="menu-icon mdi mdi-account-multiple"></i><span class="menu-title">Usuarios</span><i class="menu-arrow"></i></a>
          <div class="collapse" id="usuarios-menu"><ul class="nav flex-column sub-menu">
            <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin'): ?>
            <li class="nav-item"><a class="nav-link" href="usuarios-create.php">Crear Usuario</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="usuarios.php">Lista de Usuarios</a></li>
          </ul></div>
        </li>
        <?php endif; ?>
        <li class="nav-item nav-category">CONFIGURACION</li>
        <li class="nav-item"><a class="nav-link" href="perfil.php"><i class="menu-icon mdi mdi-account-circle-outline"></i><span class="menu-title">Perfil</span></a></li>
        <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin'): ?>
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
        <li class="nav-item nav-category">Reportes</li>
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
        <li class="nav-item"><a class="nav-link active" href="documentacion.php"><i class="menu-icon mdi mdi-file-document"></i><span class="menu-title">Documentacion</span></a></li>
      </ul>
    </nav>

    <div class="main-panel">
      <div class="content-wrapper">

        <!-- HERO -->
        <div class="doc-hero">
          <div class="doc-hero-content">
            <img src="template/images/logo.svg" alt="SIRA" class="doc-hero-logo">
            <!-- <h1>SIRA Portal</h1> -->
            <h2>Sistema de Tickets de Operacion &mdash; Clonsa Ingenieria</h2>
            <div class="doc-hero-badge">
              <i class="mdi mdi-shield-check"></i> Plataforma interna de gestion operativa v1.3
            </div>
          </div>
        </div>

        <!-- FLUJO DE ESTADOS -->
        <div class="doc-states">
          <h3><i class="mdi mdi-swap-horizontal-bold text-primary"></i> Ciclo de vida de un ticket</h3>
          <div class="state-flow">
            <div class="state-item">
              <div class="state-dot dot-blue"><i class="mdi mdi-file-plus-outline"></i></div>
              <div class="state-label">Abierto</div>
              <div class="state-desc">Ticket recien creado</div>
            </div>
            <div class="state-arrow"><i class="mdi mdi-chevron-right"></i></div>
            <div class="state-item">
              <div class="state-dot dot-yellow"><i class="mdi mdi-wrench-outline"></i></div>
              <div class="state-label">En Atencion</div>
              <div class="state-desc">Tecnico trabajando</div>
            </div>
            <div class="state-arrow"><i class="mdi mdi-chevron-right"></i></div>
            <div class="state-item">
              <div class="state-dot dot-orange"><i class="mdi mdi-eye-check-outline"></i></div>
              <div class="state-label">Verificando</div>
              <div class="state-desc">Revision de jefatura</div>
            </div>
            <div class="state-arrow"><i class="mdi mdi-chevron-right"></i></div>
            <div class="state-item">
              <div class="state-dot dot-green"><i class="mdi mdi-check-bold"></i></div>
              <div class="state-label">Resuelto</div>
              <div class="state-desc">Aprobado y cerrado</div>
            </div>
            <div class="state-arrow" style="opacity:0.4"><i class="mdi mdi-chevron-right"></i></div>
            <div class="state-item">
              <div class="state-dot dot-red"><i class="mdi mdi-close-thick"></i></div>
              <div class="state-label">Rechazado</div>
              <div class="state-desc">Devuelto para corregir</div>
            </div>
          </div>
        </div>

        <!-- MODULOS -->
        <div class="doc-grid">

          <!-- Dashboard -->
          <div class="doc-card">
            <div class="doc-card-head">
              <div class="doc-card-icon icon-blue"><i class="mdi mdi-view-dashboard"></i></div>
              <div>
                <h3>Dashboard</h3>
                <p>Panel de control principal</p>
              </div>
            </div>
            <div class="doc-card-body">
              <ul>
                <li><i class="mdi mdi-chart-box-outline"></i> Resumen de KPIs: tickets abiertos, en atencion, resueltos y rechazados en tiempo real.</li>
                <li><i class="mdi mdi-trophy-outline"></i> Top empleados: ranking de productividad por tickets resueltos en el periodo.</li>
                <li><i class="mdi mdi-history"></i> Ultimos tickets creados y ultimos historicos SIRA por departamento.</li>
                <li><i class="mdi mdi-filter-outline"></i> Filtros por periodo (semana, mes, ano) y departamento.</li>
              </ul>
            </div>
          </div>

          <!-- Tickets -->
          <div class="doc-card">
            <div class="doc-card-head">
              <div class="doc-card-icon icon-green"><i class="mdi mdi-ticket-confirmation"></i></div>
              <div>
                <h3>Gestion de Tickets</h3>
                <p>Crear, asignar y dar seguimiento</p>
              </div>
            </div>
            <div class="doc-card-body">
              <ul>
                <li><i class="mdi mdi-plus-circle-outline"></i> <strong>Crear Ticket:</strong> registre solicitudes con actividad, departamento, canal, falla, equipo y ubicacion.</li>
                <li><i class="mdi mdi-format-list-bulleted"></i> <strong>Todos los Tickets:</strong> vista completa con filtros por estado, actividad, departamento y creador.</li>
                <li><i class="mdi mdi-account-arrow-right"></i> <strong>Asignados a Mi:</strong> tickets donde usted es el tecnico responsable asignado.</li>
                <li><i class="mdi mdi-comment-text-outline"></i> <strong>Comentarios:</strong> agregue notas de seguimiento, evidencias y progreso en cada ticket.</li>
              </ul>
            </div>
          </div>

          <!-- Reportes -->
          <div class="doc-card">
            <div class="doc-card-head">
              <div class="doc-card-icon icon-purple"><i class="mdi mdi-chart-line"></i></div>
              <div>
                <h3>Reportes</h3>
                <p>Analitica y metricas de rendimiento</p>
              </div>
            </div>
            <div class="doc-card-body">
              <ul>
                <li><i class="mdi mdi-earth"></i> <strong>General:</strong> vision ejecutiva de toda la operacion con graficos de tendencia y estado.</li>
                <li><i class="mdi mdi-domain"></i> <strong>Por Departamento:</strong> comparativo entre departamentos, backlog y eficiencia.</li>
                <li><i class="mdi mdi-account-outline"></i> <strong>Por Usuario:</strong> productividad individual, tickets creados vs resueltos y promedio de horas.</li>
                <li><i class="mdi mdi-download"></i> <strong>Exportar CSV:</strong> descargue los datos filtrados para analisis externo.</li>
              </ul>
            </div>
          </div>

          <!-- Comunicados -->
          <div class="doc-card">
            <div class="doc-card-head">
              <div class="doc-card-icon icon-orange"><i class="mdi mdi-email-outline"></i></div>
              <div>
                <h3>Comunicados</h3>
                <p>Avisos y notificaciones internas</p>
              </div>
            </div>
            <div class="doc-card-body">
              <ul>
                <li><i class="mdi mdi-bullhorn-outline"></i> Avisos oficiales publicados por la administracion para todo el equipo.</li>
                <li><i class="mdi mdi-bell-ring-outline"></i> Notificaciones en tiempo real cuando se crean, asignan o actualizan tickets.</li>
                <li><i class="mdi mdi-eye-outline"></i> Indicador de leidos/no leidos con contador en la campana del navbar.</li>
              </ul>
            </div>
          </div>

          <!-- Perfil -->
          <div class="doc-card">
            <div class="doc-card-head">
              <div class="doc-card-icon icon-cyan"><i class="mdi mdi-account-circle-outline"></i></div>
              <div>
                <h3>Perfil</h3>
                <p>Configuracion de cuenta personal</p>
              </div>
            </div>
            <div class="doc-card-body">
              <ul>
                <li><i class="mdi mdi-lock-outline"></i> Cambie su contrasena de acceso de forma segura.</li>
                <li><i class="mdi mdi-shield-check-outline"></i> Medidor de seguridad visual para validar la fortaleza de su nueva clave.</li>
                <li><i class="mdi mdi-information-outline"></i> Se recomienda usar al menos 6 caracteres combinando mayusculas, numeros y simbolos.</li>
              </ul>
            </div>
          </div>

          <!-- Catalogos -->
          <div class="doc-card">
            <div class="doc-card-head">
              <div class="doc-card-icon icon-red"><i class="mdi mdi-table-settings"></i></div>
              <div>
                <h3>Catalogos</h3>
                <p>Configuracion maestra del sistema</p>
              </div>
            </div>
            <div class="doc-card-body">
              <ul>
                <li><i class="mdi mdi-office-building-outline"></i> <strong>Departamentos:</strong> areas operativas de la organizacion.</li>
                <li><i class="mdi mdi-phone-in-talk-outline"></i> <strong>Canales:</strong> vias de atencion (presencial, telefono, correo, etc).</li>
                <li><i class="mdi mdi-cog-outline"></i> <strong>Actividades, Fallas, Ubicaciones, Equipos:</strong> clasificadores para el registro preciso de tickets.</li>
                <li><i class="mdi mdi-lock-alert-outline"></i> Solo disponible para Administradores.</li>
              </ul>
            </div>
          </div>

        </div>

        <!-- ROLES -->
        <div class="doc-roles">
          <h3><i class="mdi mdi-account-group text-primary"></i> Roles del sistema</h3>
          <div class="roles-grid">
            <div class="role-item">
              <div class="role-icon"><i class="mdi mdi-shield-crown text-danger"></i></div>
              <div class="role-name">Administrador</div>
              <div class="role-desc">Acceso total: catalogos, usuarios, reportes globales y gestion completa de tickets.</div>
            </div>
            <div class="role-item">
              <div class="role-icon"><i class="mdi mdi-account-tie text-warning"></i></div>
              <div class="role-name">Jefe</div>
              <div class="role-desc">Supervisa su departamento: aprueba, rechaza tickets y accede a reportes departamentales.</div>
            </div>
            <div class="role-item">
              <div class="role-icon"><i class="mdi mdi-account text-primary"></i></div>
              <div class="role-name">Usuario</div>
              <div class="role-desc">Crea tickets, gestiona los asignados a el y consulta su reporte individual.</div>
            </div>
          </div>
        </div>

        <!-- FOOTER -->
        <div class="doc-footer">
          <strong>SIRA Portal</strong> &mdash; Sistema de Tickets de Operacion &copy; <?php echo date('Y'); ?> <strong>Clonsa Ingenieria</strong>. Todos los derechos reservados.
        </div>

      </div>
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
<script src="js/sidebar-badges.js"></script>
</body>
</html>
