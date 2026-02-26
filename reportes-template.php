<?php
require_once 'config/session.php';
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
$departamento_usuario = (int)($_SESSION['departamento_id'] ?? 1);
$puede_ver_todos = ($user_rol === 'Administrador' || $user_rol === 'Admin');
$SESSION_TIMEOUT_JS = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 120;
$SESSION_POPUP_TIMEOUT_JS = defined('SESSION_POPUP_TIMEOUT') ? SESSION_POPUP_TIMEOUT : 900;

$REPORT_SCOPE = $REPORT_SCOPE ?? 'general';
$REPORT_TITLE = $REPORT_TITLE ?? 'Reporte General';
$REPORT_SUBTITLE = $REPORT_SUBTITLE ?? 'Vista ejecutiva del soporte y rendimiento';

$hora = date('H');
$saludo = ($hora >= 5 && $hora < 12) ? 'Buenos dias' : (($hora >= 12 && $hora < 19) ? 'Buenas tardes' : 'Buenas noches');
$primer_nombre = explode(' ', $user_name)[0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>SIRA - Reportes</title>
<link rel="stylesheet" href="template/vendors/feather/feather.css">
<link rel="stylesheet" href="template/vendors/mdi/css/materialdesignicons.min.css">
<link rel="stylesheet" href="template/vendors/ti-icons/css/themify-icons.css">
<link rel="stylesheet" href="template/vendors/typicons/typicons.css">
<link rel="stylesheet" href="template/vendors/simple-line-icons/css/simple-line-icons.css">
<link rel="stylesheet" href="template/vendors/css/vendor.bundle.base.css">
<link rel="stylesheet" href="template/css/vertical-layout-light/style.css">
<link rel="shortcut icon" href="template/images/favicon.svg" />
<style>
.main-panel{width:100%;min-height:100vh;background:#f4f5f7}
.page-body-wrapper{background:#f4f5f7!important}
.content-wrapper{background:#f4f5f7!important;padding-top:8px!important}
.report-shell{display:grid;grid-template-columns:1fr;gap:14px}
.report-card{background:#fff;border-radius:14px;box-shadow:0 10px 24px rgba(31,59,179,.08);border:1px solid #eef2ff}
.report-head{padding:14px 16px;border-bottom:1px solid #edf1f7;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.report-title{margin:0;font-size:20px;font-weight:800;color:#0f172a}
.report-sub{margin:2px 0 0;font-size:13px;color:#64748b}
.filters{padding:14px 16px;display:grid;grid-template-columns:220px 180px 180px 240px 240px auto;gap:10px;align-items:end}
.filters .form-label{font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:4px}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px}
.kpi-grid{padding:0 16px 16px;display:grid;grid-template-columns:repeat(6,minmax(160px,1fr));gap:10px}
.kpi{background:linear-gradient(135deg,#f8faff,#ffffff);border:1px solid #e8ecff;border-radius:12px;padding:12px 14px}
.kpi .kpi-label{font-size:11px;color:#64748b;text-transform:uppercase;font-weight:700}
.kpi .kpi-value{font-size:28px;line-height:1.1;font-weight:800;color:#0f172a;margin-top:4px}
.kpi .kpi-foot{font-size:12px;color:#64748b;margin-top:4px}
.chart-grid{padding:0 16px 16px;display:grid;grid-template-columns:2fr 1fr;gap:12px}
.chart-grid-2{padding:0 16px 16px;display:grid;grid-template-columns:1fr 1fr;gap:12px}
.chart-grid-2.single-only{grid-template-columns:1fr}
.chart-box{background:#fff;border:1px solid #eef2ff;border-radius:12px;padding:10px 12px;position:relative}
.chart-title{font-size:14px;font-weight:700;color:#1e293b;margin-bottom:8px}
.chart-box.trend{height:430px}
.chart-box.pie{height:430px}
.chart-box.bar{height:360px}
.chart-box canvas{width:100%!important;height:calc(100% - 34px)!important;display:block}
.table-wrap{padding:14px 16px 16px}
.table-headline{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin:4px 0 12px}
.table-headline h5{margin:0;font-size:15px;font-weight:800}
.report-table{border-collapse:separate;border-spacing:0 10px}
.report-table thead th{font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:#64748b;font-weight:800;text-align:center;border:none!important;padding:10px 10px}
.report-table tbody td{text-align:center;vertical-align:middle;background:#ffffff;border-top:1px solid #e9edf7;border-bottom:1px solid #e9edf7;padding:12px 10px}
.report-table tbody td:first-child{border-left:1px solid #e9edf7;border-radius:10px 0 0 10px}
.report-table tbody td:last-child{border-right:1px solid #e9edf7;border-radius:0 10px 10px 0}
.report-table thead th:first-child,
.report-table tbody td:first-child{text-align:left;padding-left:24px}
.report-table tbody td:first-child{font-weight:600;color:#0f172a}
.report-table tbody tr:hover td{background:#f6f9ff}
.report-table tbody tr{transition:all .18s ease}
.metric-pill{display:inline-flex;align-items:center;justify-content:center;min-width:38px;padding:5px 10px;border-radius:999px;font-weight:800;font-size:12px}
.metric-blue{background:rgba(31,59,179,.12);color:#1f3bb3}
.metric-cyan{background:rgba(6,182,212,.12);color:#0e7490}
.metric-green{background:rgba(34,197,94,.14);color:#15803d}
.metric-red{background:rgba(239,68,68,.14);color:#b91c1c}
.user-cell{display:flex;align-items:center;gap:10px}
.user-avatar{width:34px;height:34px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;font-size:12px;font-weight:800;flex:0 0 34px;box-shadow:0 4px 12px rgba(79,70,229,.22)}
.user-meta{display:flex;flex-direction:column;line-height:1.25}
.user-name{font-weight:700;color:#0f172a}
.user-sub{font-size:11px;color:#64748b}
.hours-pill{display:inline-flex;align-items:center;justify-content:center;padding:5px 10px;border-radius:999px;font-weight:800;font-size:12px}
.hours-ok{background:rgba(34,197,94,.14);color:#15803d}
.hours-mid{background:rgba(251,191,36,.18);color:#92400e}
.hours-bad{background:rgba(239,68,68,.16);color:#b91c1c}
.ops-ref{display:flex;align-items:center;justify-content:center;height:100%;padding:8px}
.ops-ref-card{width:min(420px,100%);border:1px solid #e8ecff;border-radius:14px;padding:14px 16px;background:linear-gradient(145deg,#f8faff,#ffffff)}
.ops-ref-top{display:flex;align-items:center;justify-content:space-between;gap:10px}
.ops-ref-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px}
.ops-ref-level{font-size:13px;font-weight:800}
.ops-ref-value{font-size:36px;line-height:1.05;font-weight:900;color:#0f172a;margin-top:8px}
.ops-ref-sub{font-size:12px;color:#64748b;margin-top:4px}
.ops-ok{background:rgba(34,197,94,.14);color:#15803d}
.ops-mid{background:rgba(251,191,36,.2);color:#92400e}
.ops-bad{background:rgba(239,68,68,.16);color:#b91c1c}
.badge-mini{display:inline-flex;padding:4px 10px;border-radius:999px;font-weight:700;font-size:11px}
.ok{background:rgba(34,197,94,.15);color:#15803d}
.warn{background:rgba(251,191,36,.2);color:#92400e}
.bad{background:rgba(220,38,38,.16);color:#b91c1c}
.pagination-mini{display:flex;justify-content:flex-end;gap:6px;padding:6px 0}
.pagination-mini .btn{min-width:34px;height:34px;padding:0 10px}
.created-link{font-weight:800;text-decoration:none}
.created-link:hover{text-decoration:none}
.count-indicator { position: relative !important; }
.count-indicator .badge-notif {
  position: absolute !important; top: 5px !important; right: 2px !important;
  background-color: #dc3545 !important; color: #fff !important; border-radius: 10px !important;
  min-width: 18px !important; height: 18px !important; padding: 0 5px !important; font-size: 11px !important;
  font-weight: 600 !important; display: none !important; align-items: center !important; justify-content: center !important;
  line-height: 18px !important; border: 2px solid #f4f5f7 !important;
}
.count-indicator .badge-notif.show { display: flex !important; }
.sidebar-badge{background:#dc3545;color:#fff;border-radius:10px;min-width:18px;height:18px;padding:0 6px;font-size:11px;font-weight:700;display:none;align-items:center;justify-content:center;line-height:18px;margin-left:8px}
.sidebar-badge.show{display:inline-flex}

/* Dropdown comunicados/campana con el mismo estilo de dashboard */
.notif-item{
  padding:10px 14px!important;
  border-bottom:1px solid #eee;
  transition:all .2s ease;
  text-decoration:none!important;
}
.notif-item:hover{background-color:#f5f7fa!important}
.notif-item.unread{background-color:#eef4ff!important}
.notif-item:last-child{border-bottom:none}
.notif-row{display:flex;align-items:center;gap:10px}
.status-indicator{
  width:18px;height:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0
}
.status-indicator .unread-dot{
  width:9px;height:9px;background:#dc3545;border-radius:50%;
  box-shadow:0 0 0 3px rgba(220,53,69,.15);animation:pulse-dot 2s infinite
}
@keyframes pulse-dot{
  0%,100%{box-shadow:0 0 0 3px rgba(220,53,69,.15)}
  50%{box-shadow:0 0 0 5px rgba(220,53,69,.1)}
}
.notif-icon{
  width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0
}
.notif-icon i{font-size:18px}
.notif-content{
  flex:1;min-width:0;display:flex;flex-direction:column;gap:1px
}
.notif-title{
  font-size:12px;font-weight:500;color:#333;margin:0;line-height:1.3;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis
}
.notif-item.unread .notif-title{font-weight:600;color:#1a1a1a}
.notif-subtitle{font-size:11px;color:#666;margin:0;line-height:1.3}
.notif-time{
  font-size:10px;color:#1F3BB3;display:flex;align-items:center;gap:2px;margin-top:1px
}
.notif-time i{font-size:10px}
@media (max-width:1400px){.kpi-grid{grid-template-columns:repeat(3,minmax(180px,1fr))}.filters{grid-template-columns:repeat(3,minmax(220px,1fr))}.chart-grid,.chart-grid-2{grid-template-columns:1fr}}
@media (max-width:860px){.kpi-grid{grid-template-columns:repeat(2,minmax(140px,1fr))}.filters{grid-template-columns:1fr}}
</style>
</head>
<body class="authenticated">
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
          <h3 class="welcome-sub-text"><?php echo htmlspecialchars($REPORT_SUBTITLE); ?></h3>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <script>
          window.USER_ROL = '<?php echo $user_rol; ?>';
          window.USER_DEPARTAMENTO = <?php echo $departamento_usuario; ?>;
          window.PUEDE_VER_TODOS = <?php echo $puede_ver_todos ? 'true' : 'false'; ?>;
          window.CURRENT_USER_ID = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
        </script>
        <li class="nav-item dropdown d-none d-lg-block">
          <span class="nav-link dropdown-bordered" style="cursor: default; background-color: #e9ecef; opacity: .9; pointer-events:none;">
            <i class="mdi mdi-office-building me-1"></i> General
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
          <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown"><img class="img-xs rounded-circle" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=667eea&color=fff&size=128" alt="Profile"></a>
          <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
            <div class="dropdown-header text-center">
              <img class="img-md rounded-circle" src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=667eea&color=fff&size=128" alt="Profile">
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
          <a class="nav-link" data-bs-toggle="collapse" href="#tickets-menu"><i class="menu-icon mdi mdi-ticket-confirmation"></i><span class="menu-title">Tickets</span><i class="menu-arrow"></i></a>
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
        <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="collapse" href="#usuarios-menu"><i class="menu-icon mdi mdi-account-multiple"></i><span class="menu-title">Usuarios</span><i class="menu-arrow"></i></a><div class="collapse" id="usuarios-menu"><ul class="nav flex-column sub-menu"><li class="nav-item"><a class="nav-link" href="usuarios-create.php">Crear Usuario</a></li><li class="nav-item"><a class="nav-link" href="usuarios.php">Lista de Usuarios</a></li></ul></div></li>
        <?php endif; ?>
        <li class="nav-item nav-category">CONFIGURACION</li>
        <li class="nav-item"><a class="nav-link" href="perfil.php"><i class="menu-icon mdi mdi-account-circle-outline"></i><span class="menu-title">Perfil</span></a></li>
        <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin'): ?>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="collapse" href="#catalogos-menu"><i class="menu-icon mdi mdi-table-settings"></i><span class="menu-title">Catalogos</span><i class="menu-arrow"></i></a><div class="collapse" id="catalogos-menu"><ul class="nav flex-column sub-menu"><li class="nav-item"><a class="nav-link" href="catalogos-departamentos.php">Departamentos</a></li><li class="nav-item"><a class="nav-link" href="catalogos-canales.php">Canales de Atencion</a></li><li class="nav-item"><a class="nav-link" href="catalogos-actividades.php">Tipos de Actividad</a></li><li class="nav-item"><a class="nav-link" href="catalogos-fallas.php">Tipos de Falla</a></li><li class="nav-item"><a class="nav-link" href="catalogos-ubicaciones.php">Ubicaciones</a></li><li class="nav-item"><a class="nav-link" href="catalogos-equipos.php">Equipos</a></li></ul></div></li>
        <?php endif; ?>
        <li class="nav-item nav-category">REPORTES</li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#reportes-menu" aria-expanded="true"><i class="menu-icon mdi mdi-chart-line"></i><span class="menu-title">Reportes</span><i class="menu-arrow"></i></a>
          <div class="collapse show" id="reportes-menu">
            <ul class="nav flex-column sub-menu">
              <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?>
              <li class="nav-item"><a class="nav-link <?php echo $REPORT_SCOPE==='general'?'active':''; ?>" href="reportes-general.php">Reporte General</a></li>
              <li class="nav-item"><a class="nav-link <?php echo $REPORT_SCOPE==='departamento'?'active':''; ?>" href="reportes-departamento.php">Por Departamento</a></li>
              <?php endif; ?>
              <li class="nav-item"><a class="nav-link <?php echo $REPORT_SCOPE==='usuario'?'active':''; ?>" href="reportes-usuario.php">Por Usuario</a></li>
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
        <div class="report-shell">
          <div class="report-card">
            <div class="report-head">
              <div>
                <h4 class="report-title"><?php echo htmlspecialchars($REPORT_TITLE); ?></h4>
                <p class="report-sub">Analitica ejecutiva de control de soporte y rendimiento</p>
              </div>
              <button type="button" class="btn btn-primary" id="btnRefresh"><i class="mdi mdi-refresh"></i>Actualizar</button>
            </div>
            <div class="filters">
              <div><label class="form-label">Periodo</label><select class="form-control" id="fPeriodo"><option value="semana">Ultima semana</option><option value="mes">Mes actual</option><option value="ano">Ultimo Año</option><option value="personalizado">Personalizado</option></select></div>
              <div><label class="form-label">Desde</label><input type="date" class="form-control" id="fDesde"></div>
              <div><label class="form-label">Hasta</label><input type="date" class="form-control" id="fHasta"></div>
              <div><label class="form-label">Departamento</label><select class="form-control" id="fDepartamento"><option value="all">Todos</option></select></div>
              <div><label class="form-label">Usuario</label><select class="form-control" id="fUsuario"><option value="all">Todos</option></select></div>
              <div class="d-flex gap-2"><button class="btn btn-outline-secondary w-100" id="btnClear">Limpiar</button><button class="btn btn-outline-primary w-100" id="btnExport"><i class="mdi mdi-download"></i>Exportar CSV</button></div>
            </div>
            <div class="kpi-grid">
              <div class="kpi"><div class="kpi-label">Tickets Totales</div><div class="kpi-value" id="kTotal">0</div><div class="kpi-foot">Volumen total</div></div>
              <div class="kpi"><div class="kpi-label">Abiertos</div><div class="kpi-value" id="kAbierto">0</div><div class="kpi-foot">Pendientes de atencion</div></div>
              <div class="kpi"><div class="kpi-label">En Atencion</div><div class="kpi-value" id="kAtencion">0</div><div class="kpi-foot">Trabajo activo</div></div>
              <div class="kpi"><div class="kpi-label">Verificando</div><div class="kpi-value" id="kVerificando">0</div><div class="kpi-foot">Validacion jefatura</div></div>
              <div class="kpi"><div class="kpi-label">Resueltos</div><div class="kpi-value" id="kResuelto">0</div><div class="kpi-foot">Tickets cerrados</div></div>
              <div class="kpi"><div class="kpi-label">SLA 48h</div><div class="kpi-value" id="kSla">0%</div><div class="kpi-foot"><span id="kProm">0</span> horas promedio</div></div>
            </div>
          </div>

          <div class="chart-grid">
            <div class="chart-box trend"><div class="chart-title" id="titleTrend">Tendencia operativa (creados vs cerrados)</div><canvas id="chTrend"></canvas></div>
            <div class="chart-box pie"><div class="chart-title" id="titleStatus">Distribucion por estado</div><canvas id="chStatus"></canvas></div>
          </div>

          <div class="chart-grid-2">
            <div class="chart-box bar"><div class="chart-title" id="titleCanal">Carga por canal</div><canvas id="chCanal"></canvas></div>
            <div class="chart-box bar"><div class="chart-title" id="titleActividad">Carga por actividad</div><canvas id="chActividad"></canvas></div>
          </div>

          <div class="chart-grid-2 d-none" id="singleUserHoursRow">
            <div class="chart-box bar" id="singleUserHoursChartBox">
              <div class="chart-title" id="titleSingleUserHours">Promedio de horas del usuario seleccionado</div>
              <canvas id="chSingleUserHours"></canvas>
            </div>
            <div class="chart-box bar">
              <div class="chart-title">Referencia operativa</div>
              <div class="ops-ref">
                <div class="ops-ref-card" id="singleUserOpsCard">
                  <div class="ops-ref-top">
                    <span class="ops-ref-icon ops-mid" id="singleUserOpsIcon"><i class="mdi mdi-thermometer"></i></span>
                    <span class="ops-ref-level" id="singleUserOpsLevel">Nivel medio</span>
                  </div>
                  <div class="ops-ref-value" id="singleUserHoursSummary">0.00 h</div>
                  <div class="ops-ref-sub">Tiempo promedio de atencion del usuario</div>
                </div>
              </div>
            </div>
          </div>

          <div class="report-card table-wrap">
            <div class="table-headline"><h5 id="mainTableTitle">Rendimiento del personal</h5><span class="badge-mini ok" id="usersCount">0 usuarios</span></div>
            <div class="table-responsive">
              <table class="table report-table" id="tblMain">
                <thead id="tblMainHead"></thead>
                <tbody id="tblMainBody"></tbody>
              </table>
            </div>
            <div class="pagination-mini" id="usersPager"></div>
          </div>

          <div class="report-card table-wrap">
            <div class="table-headline"><h5 id="secondaryTableTitle">Control por departamento</h5><span class="badge-mini warn" id="deptCount">0 departamentos</span></div>
            <div class="table-responsive">
              <table class="table report-table" id="tblSecondary">
                <thead id="tblSecondaryHead"></thead>
                <tbody id="tblSecondaryBody"></tbody>
              </table>
            </div>
            <div class="pagination-mini" id="secondaryPager"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="createdDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="mdi mdi-format-list-numbered me-2 text-primary"></i>Detalle de tickets creados</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div id="createdDetailInfo" class="text-muted"></div>
          <span class="badge-mini ok" id="createdDetailCount">0 tickets</span>
        </div>
        <div id="createdDetailSummary" class="text-muted small mb-2"></div>
        <div class="text-muted small mb-3">Nota: en el ranking, "Resueltos (asignados)" cuenta tickets cerrados por el usuario asignado.</div>
        <div class="table-responsive">
          <table class="table report-table mb-0">
            <thead><tr><th>Codigo</th><th>Titulo</th><th>Departamento</th><th>Estado</th><th>Creado</th></tr></thead>
            <tbody id="createdDetailBody"></tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script src="template/vendors/js/vendor.bundle.base.js"></script>
<script src="template/vendors/chart.js/Chart.min.js"></script>
<script src="template/js/off-canvas.js"></script>
<script src="template/js/hoverable-collapse.js"></script>
<script src="template/js/template.js"></script>
<script>const SESSION_TIMEOUT=<?php echo $SESSION_TIMEOUT_JS; ?>; const SESSION_POPUP_TIMEOUT=<?php echo $SESSION_POPUP_TIMEOUT_JS; ?>;</script>
<script src="assets/js/session-manager.js"></script>
<script src="assets/js/notificaciones.js?v=<?php echo time(); ?>"></script>
<script>
(function(){
  const scope = '<?php echo $REPORT_SCOPE; ?>';
  const charts = {};
  let mainRows = [];
  let secondaryRows = [];
  let usersPage = 1;
  let secondaryPage = 1;
  const pageSize = 5;
  let reportReqSeq = 0;

  const colors = ['#1f3bb3','#22c55e','#f59e0b','#ef4444','#06b6d4','#8b5cf6','#ec4899','#64748b'];
  let createdModal = null;

  function qs(id){ return document.getElementById(id); }
  function num(v){ return Number(v || 0); }
  function esc(v){ return String(v ?? ''); }
  function shortLabel(v, max = 18){
    const s = String(v ?? '');
    return s.length > max ? (s.slice(0, max - 1) + '...') : s;
  }
  function initials(name){
    const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return 'US';
    if (parts.length === 1) return parts[0].slice(0,2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
  }
  function hoursBadge(v){
    const n = num(v);
    if (n <= 24) return `<span class="hours-pill hours-ok">${n.toFixed(2)}</span>`;
    if (n <= 72) return `<span class="hours-pill hours-mid">${n.toFixed(2)}</span>`;
    return `<span class="hours-pill hours-bad">${n.toFixed(2)}</span>`;
  }

  function destroyChart(key){ if(charts[key]){ charts[key].destroy(); charts[key] = null; } }
  function makeChart(key,ctx,cfg){ destroyChart(key); charts[key] = new Chart(ctx,cfg); }

  function fillSelect(select, rows, valueKey, labelKey, keepAll){
    const current = select.value;
    select.innerHTML = keepAll ? '<option value="all">Todos</option>' : '';
    rows.forEach(r => {
      const op = document.createElement('option');
      op.value = r[valueKey];
      op.textContent = r[labelKey];
      select.appendChild(op);
    });
    if ([...select.options].some(o => o.value === current)) {
      select.value = current;
    }
  }

  function badgeByBacklog(v){
    if (v <= 2) return '<span class="badge-mini ok">'+v+'</span>';
    if (v <= 6) return '<span class="badge-mini warn">'+v+'</span>';
    return '<span class="badge-mini bad">'+v+'</span>';
  }

  function buildScopeRows(data){
    const users = data.tablas.usuarios || [];
    const depts = data.tablas.departamentos || [];
    const tickets = data.tablas.tickets_recientes || [];
    const selectedUserId = qs('fUsuario').value !== 'all' ? Number(qs('fUsuario').value) : null;

    if (scope === 'departamento') {
      mainRows = depts.map(r => {
        const total = num(r.total);
        const resRate = total ? ((num(r.resueltos) / total) * 100) : 0;
        const rejRate = total ? ((num(r.rechazados) / total) * 100) : 0;
        return { ...r, res_rate: resRate, rej_rate: rejRate };
      });
      secondaryRows = users.map(r => {
        const assigned = Math.max(1, num(r.asignados));
        const eff = (num(r.resueltos) / assigned) * 100;
        return { ...r, eff };
      });
    } else if (scope === 'usuario') {
      const userSource = selectedUserId ? users.filter(r => Number(r.id) === selectedUserId) : users;
      mainRows = userSource.map(r => {
        const asignados = Math.max(1, num(r.asignados));
        const eficiencia = Math.max(0, Math.min(100, (num(r.resueltos) / asignados) * 100));
        return { ...r, eficiencia };
      });
      mainRows.sort((a,b) => num(b.eficiencia) - num(a.eficiencia));
      const ticketSource = selectedUserId ? tickets.filter(t => Number(t.usuario_id) === selectedUserId || Number(t.asignado_a) === selectedUserId) : tickets;
      secondaryRows = ticketSource.map(t => ({
        codigo: t.codigo,
        titulo: t.titulo,
        departamento: t.departamento_nombre,
        estado: t.estado_nombre,
        actividad: t.actividad_nombre
      }));
    } else {
      mainRows = users;
      secondaryRows = depts;
    }
  }

  function renderMainTable(){
    const tbody = qs('tblMainBody');
    const thead = qs('tblMainHead');
    const pager = qs('usersPager');
    const total = mainRows.length;
    const pages = Math.max(1, Math.ceil(total / pageSize));
    if (usersPage > pages) usersPage = pages;
    const start = (usersPage - 1) * pageSize;
    const view = mainRows.slice(start, start + pageSize);

    if (scope === 'departamento') {
      qs('mainTableTitle').textContent = 'Comparativo de departamentos';
      thead.innerHTML = '<tr><th>Departamento</th><th>Total</th><th>Resueltos</th><th>Rechazados</th><th>Pendiente</th><th>% Resolucion</th><th>% Rechazo</th></tr>';
      tbody.innerHTML = view.map(r => `<tr><td>${esc(r.departamento)}</td><td>${num(r.total)}</td><td>${num(r.resueltos)}</td><td>${num(r.rechazados)}</td><td>${badgeByBacklog(num(r.backlog))}</td><td>${num(r.res_rate).toFixed(1)}%</td><td>${num(r.rej_rate).toFixed(1)}%</td></tr>`).join('');
      qs('usersCount').textContent = total + ' departamentos';
    } else if (scope === 'usuario') {
      qs('mainTableTitle').textContent = 'Ranking de productividad por usuario';
      thead.innerHTML = '<tr><th>Usuario</th><th>Creados</th><th>Asignados</th><th>Resueltos (asignados)</th><th>Rechazados</th><th>Pendiente</th><th>Eficiencia</th></tr>';
      tbody.innerHTML = view.map(r => `<tr><td>${esc(r.nombre)}</td><td><a href="#" class="created-link btn-created-detail" data-user-id="${num(r.id)}" data-user-name="${esc(r.nombre)}">${num(r.creados)}</a></td><td>${num(r.asignados)}</td><td>${num(r.resueltos)}</td><td>${num(r.rechazados)}</td><td>${badgeByBacklog(num(r.backlog))}</td><td><span class="hours-pill hours-ok">${num(r.eficiencia).toFixed(1)}%</span></td></tr>`).join('');
      qs('usersCount').textContent = total + ' usuarios';
    } else {
      qs('mainTableTitle').textContent = 'Rendimiento del personal';
      thead.innerHTML = '<tr><th>Usuario</th><th>Creados</th><th>Asignados</th><th>Resueltos</th><th>Rechazados</th><th>Pendiente</th><th>Promedio h</th></tr>';
      tbody.innerHTML = view.map(r => {
        const assigned = Math.max(1, num(r.asignados));
        const ef = ((num(r.resueltos) / assigned) * 100).toFixed(1);
        return `<tr>
          <td>
            <div class="user-cell">
              <span class="user-avatar">${initials(r.nombre)}</span>
              <span class="user-meta">
                <span class="user-name">${esc(r.nombre)}</span>
                <span class="user-sub">Eficiencia ${ef}%</span>
              </span>
            </div>
          </td>
          <td><a href="#" class="created-link metric-pill metric-blue btn-created-detail" data-user-id="${num(r.id)}" data-user-name="${esc(r.nombre)}">${num(r.creados)}</a></td>
          <td><span class="metric-pill metric-cyan">${num(r.asignados)}</span></td>
          <td><span class="metric-pill metric-green">${num(r.resueltos)}</span></td>
          <td><span class="metric-pill metric-red">${num(r.rechazados)}</span></td>
          <td>${badgeByBacklog(num(r.backlog))}</td>
          <td>${hoursBadge(r.promedio_horas)}</td>
        </tr>`;
      }).join('');
      qs('usersCount').textContent = total + ' usuarios';
    }
    if (!view.length) tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Sin datos</td></tr>';
    tbody.querySelectorAll('.btn-created-detail').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        openCreatedDetail(btn.dataset.userId, btn.dataset.userName);
      });
    });

    if (pages <= 1) { pager.innerHTML = ''; return; }
    let html = `<button class="btn btn-outline-secondary btn-sm pbtn" data-p="${Math.max(1, usersPage - 1)}"><i class="mdi mdi-chevron-left"></i></button>`;
    for (let p = 1; p <= pages; p++) {
      html += `<button class="btn btn-sm ${p===usersPage?'btn-primary':'btn-outline-secondary'} pbtn" data-p="${p}">${p}</button>`;
    }
    html += `<button class="btn btn-outline-secondary btn-sm pbtn" data-p="${Math.min(pages, usersPage + 1)}"><i class="mdi mdi-chevron-right"></i></button>`;
    pager.innerHTML = html;
    pager.querySelectorAll('.pbtn').forEach(b => b.addEventListener('click', () => {
      usersPage = Number(b.dataset.p || 1);
      renderMainTable();
    }));
  }

  function renderSecondaryTable(){
    const tbody = qs('tblSecondaryBody');
    const thead = qs('tblSecondaryHead');
    const pager = qs('secondaryPager');
    if (scope === 'departamento') {
      pager.innerHTML = '';
      qs('secondaryTableTitle').textContent = 'Top usuarios por eficiencia';
      qs('deptCount').textContent = secondaryRows.length + ' usuarios';
      thead.innerHTML = '<tr><th>Usuario</th><th>Asignados</th><th>Resueltos</th><th>Rechazados</th><th>Pendiente</th><th>% Eficiencia</th></tr>';
      tbody.innerHTML = secondaryRows.slice(0, 15).map(r => `<tr><td>${esc(r.nombre)}</td><td>${num(r.asignados)}</td><td>${num(r.resueltos)}</td><td>${num(r.rechazados)}</td><td>${num(r.backlog)}</td><td>${num(r.eff).toFixed(1)}%</td></tr>`).join('');
    } else if (scope === 'usuario') {
      qs('secondaryTableTitle').textContent = 'Detalle de tickets recientes';
      qs('deptCount').textContent = secondaryRows.length + ' tickets';
      thead.innerHTML = '<tr><th>Codigo</th><th>Titulo</th><th>Departamento</th><th>Estado</th><th>Actividad</th></tr>';
      const total = secondaryRows.length;
      const pages = Math.max(1, Math.ceil(total / pageSize));
      if (secondaryPage > pages) secondaryPage = pages;
      const start = (secondaryPage - 1) * pageSize;
      const view = secondaryRows.slice(start, start + pageSize);
      tbody.innerHTML = view.map(r => `<tr><td>${esc(r.codigo)}</td><td>${esc(r.titulo)}</td><td>${esc(r.departamento)}</td><td>${esc(r.estado)}</td><td>${esc(r.actividad)}</td></tr>`).join('');
      if (pages <= 1) {
        pager.innerHTML = '';
      } else {
        let html = `<button class="btn btn-outline-secondary btn-sm spbtn" data-p="${Math.max(1, secondaryPage - 1)}"><i class="mdi mdi-chevron-left"></i></button>`;
        for (let p = 1; p <= pages; p++) {
          html += `<button class="btn btn-sm ${p===secondaryPage?'btn-primary':'btn-outline-secondary'} spbtn" data-p="${p}">${p}</button>`;
        }
        html += `<button class="btn btn-outline-secondary btn-sm spbtn" data-p="${Math.min(pages, secondaryPage + 1)}"><i class="mdi mdi-chevron-right"></i></button>`;
        pager.innerHTML = html;
        pager.querySelectorAll('.spbtn').forEach(b => b.addEventListener('click', () => {
          secondaryPage = Number(b.dataset.p || 1);
          renderSecondaryTable();
        }));
      }
    } else {
      pager.innerHTML = '';
      qs('secondaryTableTitle').textContent = 'Control por departamento';
      qs('deptCount').textContent = secondaryRows.length + ' departamentos';
      thead.innerHTML = '<tr><th>Departamento</th><th>Total</th><th>Resueltos</th><th>Rechazados</th><th>Pendiente</th></tr>';
      tbody.innerHTML = secondaryRows.map(r => {
        const total = Math.max(1, num(r.total));
        const ef = ((num(r.resueltos) / total) * 100).toFixed(1);
        return `<tr>
          <td><div class="user-cell"><span class="user-avatar">${initials(r.departamento)}</span><span class="user-meta"><span class="user-name">${esc(r.departamento)}</span><span class="user-sub">Eficiencia ${ef}%</span></span></div></td>
          <td><span class="metric-pill metric-blue">${num(r.total)}</span></td>
          <td><span class="metric-pill metric-green">${num(r.resueltos)}</span></td>
          <td><span class="metric-pill metric-red">${num(r.rechazados)}</span></td>
          <td>${badgeByBacklog(num(r.backlog))}</td>
        </tr>`;
      }).join('');
    }
    if (!tbody.innerHTML) tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Sin datos</td></tr>';
  }

  function renderCharts(data){
    const trend = data.charts.trend;
    const depts = data.tablas.departamentos || [];
    const users = data.tablas.usuarios || [];
    const selectedUserId = qs('fUsuario').value !== 'all' ? Number(qs('fUsuario').value) : null;
    const singleRow = qs('singleUserHoursRow');
    const singleChartBox = qs('singleUserHoursChartBox');
    const singleSummary = qs('singleUserHoursSummary');
    const opsIcon = qs('singleUserOpsIcon');
    const opsLevel = qs('singleUserOpsLevel');

    qs('titleTrend').textContent = (scope === 'departamento') ? 'Volumen por departamento' : ((scope === 'usuario') ? 'Top usuarios por tickets resueltos' : 'Tendencia operativa (creados vs cerrados)');
    qs('titleStatus').textContent = (scope === 'departamento') ? 'Backlog por departamento' : ((scope === 'usuario') ? 'Rechazos por usuario' : 'Distribucion por estado');
    qs('titleCanal').textContent = (scope === 'departamento') ? 'Comparativo resueltos/rechazados por departamento' : ((scope === 'usuario') ? 'Asignados vs creados por usuario' : 'Carga por canal');
    qs('titleActividad').textContent = (scope === 'departamento') ? 'Carga por actividad' : ((scope === 'usuario') ? 'Carga por actividad del usuario' : 'Carga por actividad');

    if (scope === 'departamento') {
      singleRow.classList.add('d-none');
      singleRow.classList.remove('single-only');
      singleChartBox.classList.remove('d-none');
      destroyChart('singleHours');
      const labels = depts.map(d => d.departamento);
      makeChart('trend', qs('chTrend'), {type:'bar',data:{labels,datasets:[{label:'Total',data:depts.map(d=>num(d.total)),backgroundColor:'#1f3bb3'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
      makeChart('status', qs('chStatus'), {type:'doughnut',data:{labels,datasets:[{data:depts.map(d=>num(d.backlog)),backgroundColor:colors}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
      makeChart('canal', qs('chCanal'), {type:'bar',data:{labels:labels.map(x=>shortLabel(x)),datasets:[{label:'Resueltos',data:depts.map(d=>num(d.resueltos)),backgroundColor:'#22c55e'},{label:'Rechazados',data:depts.map(d=>num(d.rechazados)),backgroundColor:'#ef4444'}]},options:{responsive:true,maintainAspectRatio:false,layout:{padding:{left:6,right:8,top:4,bottom:2}},scales:{x:{ticks:{maxRotation:0,minRotation:0,autoSkip:true,maxTicksLimit:8}},y:{beginAtZero:true,min:0,ticks:{precision:0}}}}});
      makeChart('actividad', qs('chActividad'), {type:'bar',data:{labels:data.charts.actividades.map(x=>shortLabel(x.label)),datasets:[{label:'Tickets',data:data.charts.actividades.map(x=>x.value),backgroundColor:'#8b5cf6'}]},options:{responsive:true,maintainAspectRatio:false,layout:{padding:{left:6,right:8,top:4,bottom:2}},plugins:{legend:{display:false}},scales:{x:{ticks:{maxRotation:0,minRotation:0,autoSkip:true,maxTicksLimit:8}},y:{beginAtZero:true,min:0,ticks:{precision:0}}}}});
      return;
    }

    if (scope === 'usuario') {
      const source = selectedUserId ? users.filter(u => Number(u.id) === selectedUserId) : users;
      const top = [...source].sort((a,b)=>num(b.resueltos)-num(a.resueltos)).slice(0,10);
      const rej = [...source].sort((a,b)=>num(b.rechazados)-num(a.rechazados)).slice(0,8);
      const tickets = data.tablas.tickets_recientes || [];
      const ticketSource = selectedUserId
        ? tickets.filter(t => Number(t.usuario_id) === selectedUserId || Number(t.asignado_a) === selectedUserId)
        : tickets;
      const actMap = {};
      ticketSource.forEach(t => {
        const key = t.actividad_nombre || 'Sin actividad';
        actMap[key] = (actMap[key] || 0) + 1;
      });
      const actRows = Object.keys(actMap).map(k => ({ label: k, value: actMap[k] })).sort((a,b)=>num(b.value)-num(a.value)).slice(0,10);
      makeChart('trend', qs('chTrend'), {type:'bar',data:{labels:top.map(x=>shortLabel(x.nombre)),datasets:[{label:'Resueltos',data:top.map(x=>num(x.resueltos)),backgroundColor:'#1f3bb3'}]},options:{responsive:true,maintainAspectRatio:false,layout:{padding:{left:6,right:8,top:4,bottom:2}},plugins:{legend:{display:false}},scales:{x:{ticks:{maxRotation:0,minRotation:0,autoSkip:true,maxTicksLimit:8}},y:{beginAtZero:true,min:0,ticks:{precision:0}}}}});
      makeChart('status', qs('chStatus'), {type:'doughnut',data:{labels:rej.map(x=>x.nombre),datasets:[{data:rej.map(x=>num(x.rechazados)),backgroundColor:colors}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});
      makeChart('canal', qs('chCanal'), {type:'bar',data:{labels:top.map(x=>shortLabel(x.nombre)),datasets:[{label:'Asignados',data:top.map(x=>num(x.asignados)),backgroundColor:'#06b6d4'},{label:'Creados',data:top.map(x=>num(x.creados)),backgroundColor:'#22c55e'}]},options:{responsive:true,maintainAspectRatio:false,layout:{padding:{left:6,right:8,top:4,bottom:2}},scales:{x:{ticks:{maxRotation:0,minRotation:0,autoSkip:true,maxTicksLimit:8}},y:{beginAtZero:true,min:0,ticks:{precision:0}}}}});
      makeChart('actividad', qs('chActividad'), {type:'bar',data:{labels:actRows.map(x=>shortLabel(x.label)),datasets:[{label:'Tickets',data:actRows.map(x=>num(x.value)),backgroundColor:'#8b5cf6'}]},options:{responsive:true,maintainAspectRatio:false,layout:{padding:{left:6,right:8,top:4,bottom:2}},plugins:{legend:{display:false}},scales:{x:{ticks:{maxRotation:0,minRotation:0,autoSkip:true,maxTicksLimit:8}},y:{beginAtZero:true,min:0,ticks:{precision:0}}}}});

      if (selectedUserId && source.length === 1) {
        const u = source[0];
        const h = num(u.promedio_horas);
        singleRow.classList.remove('d-none');
        singleRow.classList.add('single-only');
        singleChartBox.classList.add('d-none');
        singleSummary.textContent = h.toFixed(2) + ' h';
        qs('titleSingleUserHours').textContent = 'Promedio de horas - ' + (u.nombre || 'Usuario');
        opsIcon.classList.remove('ops-ok','ops-mid','ops-bad');
        if (h <= 24) {
          opsIcon.classList.add('ops-ok');
          opsIcon.innerHTML = '<i class="mdi mdi-thermometer-low"></i>';
          opsLevel.textContent = 'Nivel bajo';
        } else if (h <= 72) {
          opsIcon.classList.add('ops-mid');
          opsIcon.innerHTML = '<i class="mdi mdi-thermometer"></i>';
          opsLevel.textContent = 'Nivel medio';
        } else {
          opsIcon.classList.add('ops-bad');
          opsIcon.innerHTML = '<i class="mdi mdi-thermometer-high"></i>';
          opsLevel.textContent = 'Nivel alto';
        }
        destroyChart('singleHours');
      } else {
        singleRow.classList.add('d-none');
        singleRow.classList.remove('single-only');
        singleChartBox.classList.remove('d-none');
        destroyChart('singleHours');
      }
      return;
    }

    singleRow.classList.add('d-none');
    singleRow.classList.remove('single-only');
    singleChartBox.classList.remove('d-none');
    destroyChart('singleHours');
    makeChart('trend', qs('chTrend'), {
      type: 'line',
      data: {
        labels: trend.labels,
        datasets: [
          { label:'Creados', data: trend.creados, borderColor:'#1f3bb3', backgroundColor:'rgba(31,59,179,.12)', fill:true, tension:.35, borderWidth:2 },
          { label:'Cerrados', data: trend.cerrados, borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,.12)', fill:true, tension:.35, borderWidth:2 }
        ]
      },
      options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'top'}}, scales:{y:{beginAtZero:true,ticks:{precision:0}}} }
    });

    const statusLabels = data.charts.estados.map(x => x.label);
    const statusVals = data.charts.estados.map(x => x.value);
    makeChart('status', qs('chStatus'), {
      type:'doughnut',
      data:{ labels:statusLabels, datasets:[{ data:statusVals, backgroundColor: colors }]},
      options:{ responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} }
    });

    const canalLabels = data.charts.canales.map(x => x.label);
    const canalVals = data.charts.canales.map(x => x.value);
    makeChart('canal', qs('chCanal'), {
      type:'bar',
      data:{ labels:canalLabels.map(x=>shortLabel(x)), datasets:[{ label:'Tickets', data:canalVals, backgroundColor:'#06b6d4' }]},
      options:{ responsive:true, maintainAspectRatio:false, layout:{padding:{left:6,right:8,top:4,bottom:2}}, plugins:{legend:{display:false}}, scales:{x:{ticks:{maxRotation:0,minRotation:0,autoSkip:true,maxTicksLimit:8}},y:{beginAtZero:true,min:0,ticks:{precision:0}}} }
    });

    const actLabels = data.charts.actividades.map(x => x.label);
    const actVals = data.charts.actividades.map(x => x.value);
    makeChart('actividad', qs('chActividad'), {
      type:'bar',
      data:{ labels:actLabels.map(x=>shortLabel(x)), datasets:[{ label:'Tickets', data:actVals, backgroundColor:'#8b5cf6' }]},
      options:{ responsive:true, maintainAspectRatio:false, layout:{padding:{left:6,right:8,top:4,bottom:2}}, plugins:{legend:{display:false}}, scales:{x:{ticks:{maxRotation:0,minRotation:0,autoSkip:true,maxTicksLimit:8}},y:{beginAtZero:true,min:0,ticks:{precision:0}}} }
    });
  }

  function renderKpi(k){
    qs('kTotal').textContent = num(k.total);
    qs('kAbierto').textContent = num(k.abierto);
    qs('kAtencion').textContent = num(k.en_atencion);
    qs('kVerificando').textContent = num(k.verificando);
    qs('kResuelto').textContent = num(k.resuelto);
    qs('kSla').textContent = num(k.sla_48).toFixed(1) + '%';
    qs('kProm').textContent = num(k.promedio_horas).toFixed(2);
  }

  function classifyState(name){
    const s = String(name || '').toLowerCase();
    if (s.includes('rechaz')) return 'rechazado';
    if (s.includes('verif') || s.includes('pendiente')) return 'verificando';
    if (s.includes('atenc') || s.includes('proceso')) return 'en_atencion';
    if (s.includes('resuel') || s.includes('cerrad') || s.includes('aprob')) return 'resuelto';
    if (s.includes('abiert')) return 'abierto';
    return 'otro';
  }

  function renderKpiFromTickets(tickets){
    const out = { total:0, abierto:0, en_atencion:0, verificando:0, resuelto:0, rechazado:0, promedio_horas:0, sla_48:0 };
    const hours = [];
    let ok48 = 0;
    tickets.forEach(t => {
      out.total++;
      const st = classifyState(t.estado_nombre);
      if (Object.prototype.hasOwnProperty.call(out, st)) out[st]++;
      const from = Date.parse(t.created_at || '');
      const to = Date.parse((t.fecha_resolucion || t.updated_at || ''));
      if (!isNaN(from) && !isNaN(to) && to >= from && (st === 'resuelto' || st === 'rechazado')) {
        const h = (to - from) / 3600000;
        hours.push(h);
        if (h <= 48) ok48++;
      }
    });
    if (hours.length) {
      out.promedio_horas = hours.reduce((a,b)=>a+b,0) / hours.length;
      out.sla_48 = (ok48 / hours.length) * 100;
    }
    renderKpi(out);
  }

  function applyFilterLocks(){
    const isPersonalizado = qs('fPeriodo').value === 'personalizado';
    qs('fDesde').disabled = !isPersonalizado;
    qs('fHasta').disabled = !isPersonalizado;
    if (!isPersonalizado) {
      qs('fDesde').value = '';
      qs('fHasta').value = '';
    }

    if (scope === 'general') {
      qs('fDepartamento').value = 'all';
      qs('fUsuario').value = 'all';
      qs('fDepartamento').disabled = true;
      qs('fUsuario').disabled = true;
      return;
    }

    const isUsuarioRol = (window.USER_ROL === 'Usuario');

    if (isUsuarioRol) {
      // Usuario normal: fijar su departamento y su usuario, deshabilitar ambos
      qs('fDepartamento').value = String(window.USER_DEPARTAMENTO);
      qs('fDepartamento').disabled = true;
      if (scope === 'usuario') {
        qs('fUsuario').value = String(window.CURRENT_USER_ID);
        qs('fUsuario').disabled = true;
      } else {
        qs('fUsuario').value = 'all';
        qs('fUsuario').disabled = true;
      }
      return;
    }

    if (!window.PUEDE_VER_TODOS) {
      qs('fDepartamento').value = String(window.USER_DEPARTAMENTO);
      qs('fDepartamento').disabled = true;
    } else {
      qs('fDepartamento').disabled = false;
    }

    if (scope === 'departamento') {
      qs('fUsuario').value = 'all';
      qs('fUsuario').disabled = true;
    } else {
      qs('fUsuario').disabled = false;
    }
  }

  async function openCreatedDetail(userId, userName){
    const p = new URLSearchParams({
      action: 'created_detail',
      scope,
      periodo: qs('fPeriodo').value,
      fecha_desde: qs('fDesde').value,
      fecha_hasta: qs('fHasta').value,
      departamento: qs('fDepartamento').value,
      usuario: 'all',
      created_user_id: String(userId),
      _ts: String(Date.now())
    });
    const body = qs('createdDetailBody');
    const summary = qs('createdDetailSummary');
    qs('createdDetailInfo').textContent = `Usuario: ${userName}`;
    qs('createdDetailCount').textContent = 'Cargando...';
    summary.textContent = '';
    body.innerHTML = '<tr><td colspan="5" class="text-muted">Cargando detalle...</td></tr>';
    if (!createdModal) createdModal = new bootstrap.Modal(qs('createdDetailModal'));
    createdModal.show();
    const res = await fetch('api/reportes.php?' + p.toString());
    const data = await res.json();
    if (!data.success) {
      body.innerHTML = `<tr><td colspan="5" class="text-danger">${esc(data.message || 'No se pudo cargar')}</td></tr>`;
      qs('createdDetailCount').textContent = '0 tickets';
      summary.textContent = '';
      return;
    }
    const rows = data.data || [];
    qs('createdDetailCount').textContent = `${rows.length} tickets`;
    if (data.summary) {
      const s = data.summary;
      summary.textContent = `Resumen creados: ${num(s.total)} | Resueltos (creados): ${num(s.resueltos)} | Abiertos: ${num(s.abierto)} | En atencion: ${num(s.en_atencion)} | Verificando: ${num(s.verificando)} | Rechazados: ${num(s.rechazado)}`;
    } else {
      summary.textContent = `Resumen creados: ${rows.length}`;
    }
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="5" class="text-muted">Sin tickets para este filtro</td></tr>';
      return;
    }
    body.innerHTML = rows.map(r => `<tr><td>${esc(r.codigo)}</td><td>${esc(r.titulo)}</td><td>${esc(r.departamento_nombre)}</td><td>${esc(r.estado_nombre)}</td><td>${esc(r.created_at)}</td></tr>`).join('');
  }

  async function loadReport(){
    const seq = ++reportReqSeq;
    applyFilterLocks();
    const selectedUsuario = qs('fUsuario').value;
    const p = new URLSearchParams({
      scope,
      periodo: qs('fPeriodo').value,
      fecha_desde: qs('fDesde').value,
      fecha_hasta: qs('fHasta').value,
      departamento: qs('fDepartamento').value,
      usuario: (scope === 'usuario' ? 'all' : selectedUsuario),
      _ts: String(Date.now())
    });
    const res = await fetch('api/reportes.php?' + p.toString());
    if (seq !== reportReqSeq) return;
    const data = await res.json();
    if (seq !== reportReqSeq) return;
    if (!data.success) throw new Error(data.message || 'No se pudo cargar reportes');

    const isUsuarioRol = (window.USER_ROL === 'Usuario');

    fillSelect(qs('fDepartamento'), data.catalogos.departamentos, 'id', 'nombre', !isUsuarioRol);
    fillSelect(qs('fUsuario'), data.catalogos.usuarios, 'id', 'nombre_completo', !isUsuarioRol);

    if (isUsuarioRol) {
      // Forzar seleccion del usuario y departamento propios
      qs('fDepartamento').value = String(window.USER_DEPARTAMENTO);
      qs('fUsuario').value = String(window.CURRENT_USER_ID);
    } else if (scope === 'usuario' && selectedUsuario && selectedUsuario !== 'all') {
      qs('fUsuario').value = selectedUsuario;
    }
    applyFilterLocks();

    if (scope === 'usuario' && qs('fUsuario').value !== 'all') {
      const sid = Number(qs('fUsuario').value);
      const userTickets = (data.tablas.tickets_recientes || []).filter(t => Number(t.usuario_id) === sid || Number(t.asignado_a) === sid);
      renderKpiFromTickets(userTickets);
    } else {
      renderKpi(data.kpi);
    }
    renderCharts(data);
    buildScopeRows(data);
    usersPage = 1;
    secondaryPage = 1;
    renderMainTable();
    renderSecondaryTable();
  }

  async function exportCsv(){
    const selectedUsuario = qs('fUsuario').value;
    const p = new URLSearchParams({
      action: 'tickets_export',
      scope,
      periodo: qs('fPeriodo').value,
      fecha_desde: qs('fDesde').value,
      fecha_hasta: qs('fHasta').value,
      departamento: qs('fDepartamento').value,
      usuario: (scope === 'usuario' ? selectedUsuario : qs('fUsuario').value),
      _ts: String(Date.now())
    });
    const res = await fetch('api/reportes.php?' + p.toString());
    const data = await res.json();
    if (!data.success) throw new Error(data.message || 'No se pudo exportar');
    const rows = data.data || [];
    const head = ['Codigo','Titulo','Estado','Departamento','Canal','Actividad','Falla','Creador','Asignado A','Creado','Actualizado','Resuelto'];
    const csv = [head, ...rows.map(r => [
      r.codigo, r.titulo, r.estado, r.departamento, r.canal, r.actividad, r.falla, r.creador, r.asignado_a, r.creado, r.actualizado, r.resuelto
    ])].map(r => r.map(v => `"${String(v ?? '').replace(/"/g,'""')}"`).join(',')).join('\n');
    const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    const suffix = scope === 'usuario' && selectedUsuario !== 'all' ? '_usuario' : (scope === 'departamento' ? '_departamento' : '_general');
    a.download = 'tickets' + suffix + '.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  }

  qs('btnRefresh').addEventListener('click', loadReport);
  qs('btnExport').addEventListener('click', () => exportCsv().catch((err) => {
    alert(err?.message || 'No se pudo exportar CSV');
  }));
  qs('btnClear').addEventListener('click', () => {
    qs('fPeriodo').value = 'semana';
    qs('fDesde').value = '';
    qs('fHasta').value = '';
    qs('fDepartamento').value = 'all';
    qs('fUsuario').value = 'all';
    loadReport();
  });
  ['fPeriodo','fDesde','fHasta','fDepartamento','fUsuario'].forEach(id => qs(id).addEventListener('change', loadReport));

  loadReport().catch(() => {});
})();
</script>
<script src="js/sidebar-badges.js"></script>
</body>
</html>



