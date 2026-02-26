<?php
require_once 'config/session.php';
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
if ($user_rol !== 'Administrador' && $user_rol !== 'Admin') { header('Location: dashboard.php'); exit; }

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$departamento_usuario = $_SESSION['departamento_id'] ?? 1;
$puede_ver_todos = true;
$SESSION_TIMEOUT_JS = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 120;
$SESSION_POPUP_TIMEOUT_JS = defined('SESSION_POPUP_TIMEOUT') ? SESSION_POPUP_TIMEOUT : 900;

$catalogKey = isset($CATALOGO_KEY) ? $CATALOGO_KEY : 'departamentos';
$catalogMap = [
  'departamentos' => ['title' => 'Departamentos', 'icon' => 'mdi-office-building'],
  'canales' => ['title' => 'Canales de Atencion', 'icon' => 'mdi-forum-outline'],
  'actividades' => ['title' => 'Tipos de Actividad', 'icon' => 'mdi-clipboard-list-outline'],
  'tipos_falla' => ['title' => 'Tipos de Falla', 'icon' => 'mdi-alert-circle-outline'],
  'ubicaciones' => ['title' => 'Ubicaciones', 'icon' => 'mdi-map-marker-outline'],
  'equipos' => ['title' => 'Equipos', 'icon' => 'mdi-laptop'],
];
if (!isset($catalogMap[$catalogKey])) { $catalogKey = 'departamentos'; }
$meta = $catalogMap[$catalogKey];

$hora = date('H');
$saludo = ($hora >= 5 && $hora < 12) ? 'Buenos dias' : (($hora >= 12 && $hora < 19) ? 'Buenas tardes' : 'Buenas noches');
$primer_nombre = explode(' ', $user_name)[0];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>SIRA - Catalogos</title>
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
.notif-content { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 1px; }
.notif-title { font-size: 12px; font-weight: 500; color: #333; margin: 0; line-height: 1.3; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.notif-subtitle { font-size: 11px; color: #666; margin: 0; line-height: 1.3; }
.notif-time { font-size: 10px; color: #1F3BB3; display: flex; align-items: center; gap: 2px; margin-top: 1px; }
.main-panel{
  width: 100%;
  min-height: 100vh;
  background: #f4f5f7;
}
.page-body-wrapper{
  background: #f4f5f7 !important;
}
.content-wrapper{
  background: #f4f5f7 !important;
  padding-top: 0 !important;
}
.catalog-grid{display:grid;grid-template-columns:320px 1fr;gap:16px}
.hint-card{background:linear-gradient(145deg,#1f3bb3,#3d64d8);color:#fff;border-radius:14px;padding:20px;box-shadow:0 12px 24px rgba(31,59,179,.2)}
.hint-card h4{font-size:22px;font-weight:700;margin-bottom:8px}
.hint-card ul{padding-left:18px;margin:0}
.hint-card li{margin-bottom:8px;font-size:13px}
.catalog-card{background:#fff;border-radius:14px;box-shadow:0 12px 24px rgba(31,59,179,.08)}
.catalog-head{padding:16px 18px;border-bottom:1px solid #edf1f7;display:flex;justify-content:space-between;align-items:center}
.catalog-title{font-size:18px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px}
.catalog-body{padding:16px 18px}
.section-title{font-size:12px;text-transform:uppercase;color:#6b7280;letter-spacing:.4px;font-weight:700;margin-bottom:10px}
.required-star{color:#dc2626;font-weight:700}
.form-control,.form-select{border-radius:8px;min-height:40px}
.form-control:focus,.form-select:focus{border-color:#1f3bb3;box-shadow:0 0 0 .15rem rgba(31,59,179,.14)}
textarea.form-control{
  min-height:46px;
  padding-top:10px;
  padding-bottom:10px;
}
.input-group-text{border-radius:8px 0 0 8px;background:#f8faff}
.input-group .form-control,.input-group .form-select{border-left:0}
.catalog-card .btn{display:inline-flex;align-items:center;justify-content:center;text-align:center;gap:6px}
.filters-row{display:flex;gap:10px;align-items:end;flex-wrap:wrap;margin:12px 0}
.filters-row .filter-item{min-width:180px}
.filters-row label{font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase;margin-bottom:4px;display:block}
.catalog-table thead th{font-size:12px;text-transform:uppercase;color:#6b7280;font-weight:700}
.badge-active{background:rgba(34,197,94,.14);color:#15803d;font-weight:700;padding:6px 10px;border-radius:20px;font-size:11px}
.badge-inactive{background:rgba(220,53,69,.14);color:#b4232f;font-weight:700;padding:6px 10px;border-radius:20px;font-size:11px}
.color-dot{width:16px;height:16px;border-radius:50%;display:inline-block;border:1px solid rgba(0,0,0,.14);margin-right:6px}
.actions-group{display:flex;gap:6px;flex-wrap:wrap;justify-content:center}
.catalog-table th:last-child,.catalog-table td:last-child{text-align:center}
.catalog-pagination{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:12px;flex-wrap:wrap}
.catalog-pagination .summary{font-size:13px;color:#6b7280}
.catalog-pagination .pages{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.catalog-pagination .pages .btn{min-width:36px;height:36px;padding:0 10px}
.toast-fixed{position:fixed;right:18px;bottom:18px;z-index:9999;background:#16a34a;color:#fff;padding:10px 14px;border-radius:10px;display:none;align-items:center;gap:8px;box-shadow:0 10px 22px rgba(0,0,0,.2)}
.toast-fixed.error{background:#dc2626}
@media (max-width:1100px){.catalog-grid{grid-template-columns:1fr}}
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
          <h3 class="welcome-sub-text">Gestion de Catalogos - Portal SIRA Clonsa Ingenieria</h3>
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
          <a class="nav-link" data-bs-toggle="collapse" href="#tickets-menu" aria-expanded="true" aria-controls="tickets-menu"><i class="menu-icon mdi mdi-ticket-confirmation"></i><span class="menu-title">Tickets</span><i class="menu-arrow"></i></a>
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
          <a class="nav-link" data-bs-toggle="collapse" href="#usuarios-menu" aria-expanded="false" aria-controls="usuarios-menu"><i class="menu-icon mdi mdi-account-multiple"></i><span class="menu-title">Usuarios</span><i class="menu-arrow"></i></a>
          <div class="collapse" id="usuarios-menu">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"><a class="nav-link" href="usuarios-create.php">Crear Usuario</a></li>
              <li class="nav-item"><a class="nav-link" href="usuarios.php">Lista de Usuarios</a></li>
            </ul>
          </div>
        </li>
        <li class="nav-item nav-category">CONFIGURACION</li>
        <li class="nav-item"><a class="nav-link" href="perfil.php"><i class="menu-icon mdi mdi-account-circle-outline"></i><span class="menu-title">Perfil</span></a></li>
        <li class="nav-item">
          <a class="nav-link" data-bs-toggle="collapse" href="#catalogos-menu" aria-expanded="true" aria-controls="catalogos-menu"><i class="menu-icon mdi mdi-table-settings"></i><span class="menu-title">Catalogos</span><i class="menu-arrow"></i></a>
          <div class="collapse show" id="catalogos-menu">
            <ul class="nav flex-column sub-menu">
              <li class="nav-item"><a class="nav-link <?php echo $catalogKey==='departamentos'?'active':''; ?>" href="catalogos-departamentos.php">Departamentos</a></li>
              <li class="nav-item"><a class="nav-link <?php echo $catalogKey==='canales'?'active':''; ?>" href="catalogos-canales.php">Canales de Atencion</a></li>
              <li class="nav-item"><a class="nav-link <?php echo $catalogKey==='actividades'?'active':''; ?>" href="catalogos-actividades.php">Tipos de Actividad</a></li>
              <li class="nav-item"><a class="nav-link <?php echo $catalogKey==='tipos_falla'?'active':''; ?>" href="catalogos-fallas.php">Tipos de Falla</a></li>
              <li class="nav-item"><a class="nav-link <?php echo $catalogKey==='ubicaciones'?'active':''; ?>" href="catalogos-ubicaciones.php">Ubicaciones</a></li>
              <li class="nav-item"><a class="nav-link <?php echo $catalogKey==='equipos'?'active':''; ?>" href="catalogos-equipos.php">Equipos</a></li>
            </ul>
          </div>
        </li>
        <li class="nav-item nav-category">REPORTES</li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="collapse" href="#reportes-menu" aria-expanded="false" aria-controls="reportes-menu"><i class="menu-icon mdi mdi-chart-line"></i><span class="menu-title">Reportes</span><i class="menu-arrow"></i></a><div class="collapse" id="reportes-menu"><ul class="nav flex-column sub-menu"><?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?><li class="nav-item"><a class="nav-link" href="reportes-general.php">Reporte General</a></li><li class="nav-item"><a class="nav-link" href="reportes-departamento.php">Por Departamento</a></li><?php endif; ?><li class="nav-item"><a class="nav-link" href="reportes-usuario.php">Por Usuario</a></li></ul></div></li>
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

    <div class="main-panel"><div class="content-wrapper">
      <div class="catalog-grid">
        <div class="hint-card"><h4><?php echo htmlspecialchars($meta['title']); ?></h4><p>Gestion profesional del catalogo con restricciones de administrador.</p><ul><li>Crear y editar registros</li><li>Activar o desactivar sin eliminar historial</li><li>Validacion de duplicados</li></ul></div>
        <div class="catalog-card">
          <div class="catalog-head"><h4 class="catalog-title"><i class="mdi <?php echo $meta['icon']; ?>"></i><?php echo htmlspecialchars($meta['title']); ?></h4><button type="button" class="btn btn-outline-primary btn-sm" id="btnReloadCatalog"><i class="mdi mdi-refresh me-1"></i>Actualizar</button></div>
          <div class="catalog-body">
            <form id="catalogForm" class="mb-3">
              <input type="hidden" id="cat_id">
              <div class="section-title">Datos del registro</div>
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Nombre <span class="required-star">*</span></label><div class="input-group"><span class="input-group-text"><i class="mdi mdi-format-title"></i></span><input type="text" class="form-control" id="cat_nombre" required></div></div>
                <div class="col-md-6 d-none" id="wrapAbreviatura"><label class="form-label">Abreviatura <span class="required-star">*</span></label><input type="text" class="form-control" id="cat_abreviatura" maxlength="5"></div>
                <div class="col-md-6 d-none" id="wrapColor"><label class="form-label">Color <span class="required-star">*</span></label><input type="color" class="form-control form-control-color w-100" id="cat_color" value="#6b7280"></div>
                <div class="col-md-6 d-none" id="wrapIcono"><label class="form-label">Icono <span class="required-star">*</span></label><input type="text" class="form-control" id="cat_icono" placeholder="mdi mdi-alert-circle-outline"></div>
                <div class="col-md-6 d-none" id="wrapDepartamento"><label class="form-label">Departamento <span class="required-star">*</span></label><select class="form-select" id="cat_departamento_id"><option value="">Seleccione departamento</option></select></div>
                <div class="col-12"><label class="form-label">Descripcion <span class="required-star">*</span></label><div class="input-group"><span class="input-group-text"><i class="mdi mdi-format-title"></i></span><input type="text" class="form-control" id="cat_descripcion"></div></div>
                <div class="col-12 d-flex justify-content-end gap-2"><button type="button" class="btn btn-light" id="btnClearForm">Limpiar</button><button type="submit" class="btn btn-primary"><i class="mdi mdi-content-save me-1"></i><span id="saveLabel">Guardar</span></button></div>
              </div>
            </form>

            <div class="filters-row">
              <div class="filter-item" style="min-width:260px;"><label>Buscar</label><input type="text" class="form-control" id="fSearch" placeholder="Nombre o descripcion"></div>
              <div class="filter-item"><label>Estado</label><select class="form-control" id="fEstado"><option value="">Todos</option><option value="1">Activos</option><option value="0">Inactivos</option></select></div>
              <div class="filter-item d-none" id="wrapFDepartamento"><label>Departamento</label><select class="form-control" id="fDepartamento"><option value="">Todos</option></select></div>
              <div class="filter-item"><button type="button" class="btn btn-outline-secondary w-100" style="height:40px;" id="btnClearFilters">Limpiar filtros</button></div>
            </div>

            <div class="table-responsive"><table class="table catalog-table"><thead><tr id="catHeadRow"></tr></thead><tbody id="catBody"></tbody></table></div>
            <div class="catalog-pagination" id="catalogPagination">
              <div class="summary" id="catalogSummary">Mostrando 0-0 de 0 registros</div>
              <div class="pages" id="catalogPages"></div>
            </div>
          </div>
        </div>
      </div>
    </div></div>
  </div>
</div>

<div class="toast-fixed" id="toastCatalog"><i class="mdi mdi-check-circle"></i><span id="toastCatalogMsg">OK</span></div>

<script src="template/vendors/js/vendor.bundle.base.js"></script>
<script src="template/js/off-canvas.js"></script>
<script src="template/js/hoverable-collapse.js"></script>
<script src="template/js/template.js"></script>
<script>const SESSION_TIMEOUT=<?php echo $SESSION_TIMEOUT_JS; ?>; const SESSION_POPUP_TIMEOUT=<?php echo $SESSION_POPUP_TIMEOUT_JS; ?>;</script>
<script src="assets/js/session-manager.js"></script>
<script src="assets/js/notificaciones.js?v=<?php echo time(); ?>"></script>
<script>
(function(){
  const key='<?php echo $catalogKey; ?>';
  const conf={
    departamentos:{columns:['nombre','abreviatura','descripcion','activo'],extras:['abreviatura']},
    canales:{columns:['nombre','descripcion','activo'],extras:[]},
    actividades:{columns:['nombre','departamento_nombre','color','descripcion','activo'],extras:['color','departamento']},
    tipos_falla:{columns:['nombre','departamento_nombre','icono','descripcion','activo'],extras:['icono','departamento']},
    ubicaciones:{columns:['nombre','departamento_nombre','descripcion','activo'],extras:['departamento']},
    equipos:{columns:['nombre','departamento_nombre','descripcion','activo'],extras:['departamento']}
  };
  let allRows=[];
  let currentPage=1;
  const pageSize=5;
  const esc=v=>String(v||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  function totalCols(){return conf[key].columns.length+1}
  function toast(msg,err){const e=document.getElementById('toastCatalog');document.getElementById('toastCatalogMsg').textContent=msg;e.classList.toggle('error',!!err);e.style.display='inline-flex';setTimeout(()=>e.style.display='none',2200)}
  function active(v){return parseInt(v||0,10)===1}
  function status(v){return active(v)?'<span class="badge-active">Activo</span>':'<span class="badge-inactive">Inactivo</span>'}
  function setup(){
    const ex=conf[key].extras;
    document.getElementById('wrapAbreviatura').classList.toggle('d-none',!ex.includes('abreviatura'));
    document.getElementById('wrapColor').classList.toggle('d-none',!ex.includes('color'));
    document.getElementById('wrapIcono').classList.toggle('d-none',!ex.includes('icono'));
    document.getElementById('wrapDepartamento').classList.toggle('d-none',!ex.includes('departamento'));
    document.getElementById('wrapFDepartamento').classList.toggle('d-none',!ex.includes('departamento'));
    const form=document.getElementById('catalogForm');
    form.querySelectorAll('input, select, textarea').forEach(el=>{ if(el.id!=='cat_id') el.required=false; });
    form.querySelectorAll('.col-md-6:not(.d-none) input, .col-md-6:not(.d-none) select, .col-12 input, .col-12 textarea, .col-12 select').forEach(el=>{
      if(el.id!=='cat_id' && !el.disabled) el.required=true;
    });
  }
  function loadDepartamentos(){
    if(!conf[key].extras.includes('departamento')) return Promise.resolve();
    return fetch('api/catalogos.php?tipo=departamentos').then(r=>r.json()).then(r=>{
      if(!r.success) return;
      const s=document.getElementById('cat_departamento_id');
      const fs=document.getElementById('fDepartamento');
      s.innerHTML='<option value="">Seleccione departamento</option>';
      fs.innerHTML='<option value="">Todos</option>';
      (r.data||[]).forEach(d=>{
        const opt=`<option value="${d.id}">${esc(d.nombre)}</option>`;
        s.innerHTML+=opt;
        fs.innerHTML+=opt;
      });
    });
  }
  function renderHead(){
    const labels={nombre:'Nombre',abreviatura:'Abrev.',descripcion:'Descripcion',color:'Color',icono:'Icono',departamento_nombre:'Departamento',activo:'Estado'};
    const h=document.getElementById('catHeadRow');h.innerHTML=conf[key].columns.map(c=>`<th>${labels[c]||c}</th>`).join('')+'<th>Acciones</th>';
  }
  function filtered(){
    const q=(document.getElementById('fSearch').value||'').toLowerCase().trim();
    const e=document.getElementById('fEstado').value;
    const dep=(document.getElementById('fDepartamento')?.value)||'';
    return allRows.filter(r=>{
      if(e!==''&&String(r.activo)!==e) return false;
      if(dep!==''&&String(r.departamento_id||'')!==dep) return false;
      if(!q) return true;
      const hay=`${r.nombre||''} ${r.descripcion||''} ${r.abreviatura||''} ${r.departamento_nombre||''}`.toLowerCase();
      return hay.includes(q);
    });
  }
  function renderPagination(totalRows,totalPages){
    const pages=document.getElementById('catalogPages');
    const summary=document.getElementById('catalogSummary');
    const start=totalRows===0?0:((currentPage-1)*pageSize)+1;
    const end=Math.min(currentPage*pageSize,totalRows);
    summary.textContent=`Mostrando ${start}-${end} de ${totalRows} registros`;
    if(totalPages<=1){pages.innerHTML='';return;}
    let html=`<button type="button" class="btn btn-outline-secondary btn-sm page-btn" data-page="${Math.max(1,currentPage-1)}" ${currentPage===1?'disabled':''}><i class="mdi mdi-chevron-left"></i></button>`;
    for(let p=1;p<=totalPages;p++){
      html+=`<button type="button" class="btn btn-sm ${p===currentPage?'btn-primary':'btn-outline-secondary'} page-btn" data-page="${p}">${p}</button>`;
    }
    html+=`<button type="button" class="btn btn-outline-secondary btn-sm page-btn" data-page="${Math.min(totalPages,currentPage+1)}" ${currentPage===totalPages?'disabled':''}><i class="mdi mdi-chevron-right"></i></button>`;
    pages.innerHTML=html;
    pages.querySelectorAll('.page-btn').forEach(btn=>btn.addEventListener('click',()=>{const next=parseInt(btn.dataset.page,10)||1;if(next===currentPage)return;currentPage=next;render();}));
  }
  function render(){
    const rows=filtered(),b=document.getElementById('catBody');
    const totalRows=rows.length;
    const totalPages=Math.max(1,Math.ceil(totalRows/pageSize));
    if(currentPage>totalPages) currentPage=totalPages;
    const start=(currentPage-1)*pageSize;
    const pageRows=rows.slice(start,start+pageSize);
    if(!totalRows){b.innerHTML=`<tr><td colspan="${totalCols()}" class="text-center text-muted py-4">No hay registros</td></tr>`;renderPagination(0,1);return;}
    b.innerHTML=pageRows.map(r=>{let t='';conf[key].columns.forEach(c=>{if(c==='activo'){t+=`<td>${status(r.activo)}</td>`;}else if(c==='color'){t+=`<td><span class="color-dot" style="background:${esc(r.color||'#6b7280')}"></span>${esc(r.color||'#6b7280')}</td>`;}else if(c==='icono'){t+=`<td><i class="${esc(r.icono||'mdi mdi-alert-circle-outline')}"></i> ${esc(r.icono||'-')}</td>`;}else{t+=`<td>${esc(r[c]||'-')}</td>`;}});const nx=active(r.activo)?0:1;const lbl=active(r.activo)?'Desactivar':'Activar';const cls=active(r.activo)?'btn-outline-warning':'btn-outline-success';return `<tr>${t}<td><div class="actions-group"><button class="btn btn-sm btn-outline-primary btn-edit" data-id="${r.id}"><i class="mdi mdi-pencil"></i></button><button class="btn btn-sm ${cls} btn-toggle" data-id="${r.id}" data-activo="${nx}">${lbl}</button></div></td></tr>`;}).join('');
    renderPagination(totalRows,totalPages);
    b.querySelectorAll('.btn-edit').forEach(x=>x.addEventListener('click',()=>edit(parseInt(x.dataset.id,10))));
    b.querySelectorAll('.btn-toggle').forEach(x=>x.addEventListener('click',()=>toggle(parseInt(x.dataset.id,10),parseInt(x.dataset.activo,10))));
  }
  function load(){fetch(`api/catalogos.php?action=list_admin&tipo=${encodeURIComponent(key)}`).then(r=>r.json()).then(r=>{if(!r.success)throw new Error(r.message||'Error');allRows=r.data||[];currentPage=1;render();}).catch(e=>{document.getElementById('catBody').innerHTML=`<tr><td colspan="${totalCols()}" class="text-center text-danger py-4">Error al cargar</td></tr>`;toast(e.message,true);});}
  function clearForm(){document.getElementById('catalogForm').reset();document.getElementById('cat_id').value='';document.getElementById('cat_color').value='#6b7280';document.getElementById('saveLabel').textContent='Guardar';}
  function edit(id){const r=allRows.find(x=>parseInt(x.id,10)===id);if(!r)return;document.getElementById('cat_id').value=r.id||'';document.getElementById('cat_nombre').value=r.nombre||'';document.getElementById('cat_descripcion').value=r.descripcion||'';document.getElementById('cat_abreviatura').value=r.abreviatura||'';document.getElementById('cat_color').value=r.color||'#6b7280';document.getElementById('cat_icono').value=r.icono||'';document.getElementById('cat_departamento_id').value=r.departamento_id||'';document.getElementById('saveLabel').textContent='Actualizar';window.scrollTo({top:0,behavior:'smooth'});}
  function toggle(id,ac){const d=new URLSearchParams();d.append('action','toggle_estado');d.append('tipo',key);d.append('id',id);d.append('activo',ac);fetch('api/catalogos.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:d.toString()}).then(r=>r.json()).then(r=>{if(!r.success)throw new Error(r.message||'No se pudo actualizar');toast(r.message||'Estado actualizado');load();}).catch(e=>toast(e.message,true));}
  function save(ev){ev.preventDefault();const id=document.getElementById('cat_id').value.trim();const d=new URLSearchParams();d.append('action',id?'update':'create');d.append('tipo',key);d.append('id',id);d.append('nombre',document.getElementById('cat_nombre').value.trim());d.append('descripcion',document.getElementById('cat_descripcion').value.trim());d.append('abreviatura',document.getElementById('cat_abreviatura').value.trim());d.append('color',document.getElementById('cat_color').value);d.append('icono',document.getElementById('cat_icono').value.trim());d.append('departamento_id',document.getElementById('cat_departamento_id').value||'');fetch('api/catalogos.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:d.toString()}).then(r=>r.json()).then(r=>{if(!r.success)throw new Error(r.message||'No se pudo guardar');toast(r.message||'Guardado');clearForm();load();}).catch(e=>toast(e.message,true));}
  document.getElementById('catalogForm').addEventListener('submit',save);
  document.getElementById('btnClearForm').addEventListener('click',clearForm);
  document.getElementById('btnReloadCatalog').addEventListener('click',load);
  document.getElementById('fSearch').addEventListener('input',()=>{currentPage=1;render();});
  document.getElementById('fEstado').addEventListener('change',()=>{currentPage=1;render();});
  document.getElementById('fDepartamento').addEventListener('change',()=>{currentPage=1;render();});
  document.getElementById('btnClearFilters').addEventListener('click',()=>{document.getElementById('fSearch').value='';document.getElementById('fEstado').value='';document.getElementById('fDepartamento').value='';currentPage=1;render();});
  setup();renderHead();loadDepartamentos().finally(load);
})();
</script>
<script src="js/sidebar-badges.js"></script>
</body>
</html>



