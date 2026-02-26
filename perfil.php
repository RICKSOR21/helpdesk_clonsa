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

$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$departamento_usuario = (int)($_SESSION['departamento_id'] ?? 0);
$puede_ver_todos = ($user_rol === 'Administrador' || $user_rol === 'Admin');

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
  <title>SIRA - Perfil</title>
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

    .hint-card {
      height: 100%;
      background: linear-gradient(145deg, #1f3bb3 0%, #3d64d8 100%);
      color: #fff;
      border-radius: 14px;
      box-shadow: 0 12px 24px rgba(31,59,179,0.2);
      padding: 22px;
    }
    .hint-title { font-size: 20px; font-weight: 700; margin-bottom: 8px; }
    .hint-sub { font-size: 13px; opacity: 0.9; margin-bottom: 18px; }
    .hint-list { list-style: none; padding: 0; margin: 0; }
    .hint-list li { display: flex; gap: 8px; align-items: flex-start; margin-bottom: 10px; font-size: 13px; }
    .hint-list li i { margin-top: 2px; }

    .layout-card {
      background: #fff;
      border-radius: 14px;
      box-shadow: 0 12px 24px rgba(31,59,179,0.08);
      overflow: hidden;
    }
    .layout-card .card-body { padding: 22px; }
    .form-title { font-weight: 700; font-size: 22px; color: #111827; }
    .form-subtitle { color: #6b7280; margin-bottom: 18px; }
    .section-divider {
      border-top: 1px solid #edf1f7;
      margin: 18px 0 16px;
      padding-top: 16px;
    }
    .section-title {
      font-size: 12px;
      text-transform: uppercase;
      color: #6b7280;
      letter-spacing: 0.4px;
      font-weight: 700;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .required::after { content: ' *'; color: #dc3545; }
    .input-group-text {
      border-radius: 8px 0 0 8px;
      border-color: #d9deea;
      background: #f8faff;
      color: #5b6475;
    }
    .input-group .form-control { border-left: 0; }
    .form-control {
      border-radius: 8px;
      min-height: 40px;
      border-color: #d9deea;
    }
    .form-control:focus {
      border-color: #1f3bb3;
      box-shadow: 0 0 0 0.15rem rgba(31,59,179,0.15);
    }
    .password-meter {
      height: 6px;
      border-radius: 10px;
      background: #e9edf5;
      overflow: hidden;
      margin-top: 6px;
    }
    .password-meter > span {
      display: block;
      height: 100%;
      width: 0%;
      transition: width .2s ease;
      background: #dc2626;
    }
    .password-text { font-size: 11px; color: #6b7280; margin-top: 4px; }
    .toast-fixed {
      position: fixed; right: 18px; bottom: 18px; z-index: 9999;
      background: #16a34a; color: #fff; padding: 10px 14px; border-radius: 10px;
      display: none; align-items: center; gap: 8px; box-shadow: 0 10px 22px rgba(0,0,0,0.2);
    }
    .toast-fixed.error { background: #dc2626; }
    .btn-actions {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
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
          <h3 class="welcome-sub-text">Gestion de Perfil - Portal SIRA Clonsa Ingenieria</h3>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item d-none d-lg-block"><span class="nav-link dropdown-bordered" style="cursor: default; background-color: #e9ecef; opacity: 0.9; pointer-events: none;"><i class="mdi mdi-office-building me-1"></i> <?php echo htmlspecialchars($departamento_nombre); ?></span></li>
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
          <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
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
        <li class="nav-item"><a class="nav-link" data-bs-toggle="collapse" href="#tickets-menu" aria-expanded="true" aria-controls="tickets-menu"><i class="menu-icon mdi mdi-ticket-confirmation"></i><span class="menu-title">Tickets</span><i class="menu-arrow"></i></a>
          <div class="collapse show" id="tickets-menu"><ul class="nav flex-column sub-menu">
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
        <li class="nav-item"><a class="nav-link active" href="perfil.php"><i class="menu-icon mdi mdi-account-circle-outline"></i><span class="menu-title">Perfil</span></a></li>
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
        <li class="nav-item"><a class="nav-link" href="documentacion.php"><i class="menu-icon mdi mdi-file-document"></i><span class="menu-title">Documentacion</span></a></li>
      </ul>
    </nav>

    <div class="main-panel">
      <div class="content-wrapper">
        <div class="row">
          <div class="col-lg-4 mb-3">
            <div class="hint-card">
              <div class="hint-title"><i class="mdi mdi-shield-lock-outline me-1"></i>Seguridad de cuenta</div>
              <div class="hint-sub">Actualice su clave personal desde este modulo.</div>
              <ul class="hint-list">
                <li><i class="mdi mdi-check-circle-outline"></i><span>Use una clave de al menos 6 caracteres.</span></li>
                <li><i class="mdi mdi-check-circle-outline"></i><span>Combine mayusculas, numeros y simbolos.</span></li>
                <li><i class="mdi mdi-check-circle-outline"></i><span>No comparta su clave con otros usuarios.</span></li>
                <li><i class="mdi mdi-check-circle-outline"></i><span>Renueve su clave de forma periodica.</span></li>
              </ul>
            </div>
          </div>
          <div class="col-lg-8 mb-3">
            <div class="card layout-card">
              <div class="card-body">
                <div class="form-title">Perfil</div>
                <div class="form-subtitle">Cambio de clave de acceso.</div>

                <form id="frmPerfilClave" autocomplete="off">
                  <div class="section-title"><i class="mdi mdi-lock-outline"></i>Actualizacion de clave</div>
                  <div class="row">
                    <div class="col-md-12 mb-3">
                      <label class="required">Clave actual</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="mdi mdi-key-outline"></i></span>
                        <input type="password" name="clave_actual" id="claveActual" class="form-control" required minlength="6" placeholder="Ingrese su clave actual">
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="required">Nueva clave</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="mdi mdi-lock-reset"></i></span>
                        <input type="password" name="clave_nueva" id="claveNueva" class="form-control" required minlength="6" placeholder="Minimo 6 caracteres">
                      </div>
                      <div class="password-meter"><span id="passwordMeterBar"></span></div>
                      <div class="password-text" id="passwordMeterText">Seguridad: baja</div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <label class="required">Confirmar nueva clave</label>
                      <div class="input-group">
                        <span class="input-group-text"><i class="mdi mdi-shield-check-outline"></i></span>
                        <input type="password" name="clave_confirmar" id="claveConfirmar" class="form-control" required minlength="6" placeholder="Repita la nueva clave">
                      </div>
                    </div>
                  </div>

                  <div class="section-divider"></div>
                  <div class="d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-actions" id="btnGuardarClave"><i class="mdi mdi-content-save"></i><span>Guardar clave</span></button>
                    <button type="button" class="btn btn-outline-secondary btn-actions" id="btnLimpiarForm"><i class="mdi mdi-broom"></i><span>Limpiar</span></button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      <footer class="footer">
        <div class="d-sm-flex justify-content-center justify-content-sm-between">
          <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Portal SIRA <?php echo date('Y'); ?></span>
        </div>
      </footer>
    </div>
  </div>
</div>

<div class="toast-fixed" id="toastPerfil"><i class="mdi mdi-check-circle"></i><span id="toastPerfilMsg">OK</span></div>

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
  function showToast(msg, isError) {
    const el = document.getElementById('toastPerfil');
    document.getElementById('toastPerfilMsg').textContent = msg;
    el.classList.toggle('error', !!isError);
    el.style.display = 'inline-flex';
    setTimeout(() => { el.style.display = 'none'; }, 2800);
  }

  function updatePasswordMeter() {
    const val = document.getElementById('claveNueva').value || '';
    const bar = document.getElementById('passwordMeterBar');
    const txt = document.getElementById('passwordMeterText');
    let score = 0;
    if (val.length >= 6) score += 30;
    if (/[A-Z]/.test(val)) score += 20;
    if (/[a-z]/.test(val)) score += 20;
    if (/[0-9]/.test(val)) score += 15;
    if (/[^A-Za-z0-9]/.test(val)) score += 15;
    score = Math.min(score, 100);
    bar.style.width = score + '%';

    if (score < 40) {
      bar.style.background = '#dc2626';
      txt.textContent = 'Seguridad: baja';
    } else if (score < 70) {
      bar.style.background = '#d97706';
      txt.textContent = 'Seguridad: media';
    } else {
      bar.style.background = '#16a34a';
      txt.textContent = 'Seguridad: alta';
    }
  }

  function limpiarFormulario() {
    document.getElementById('frmPerfilClave').reset();
    document.getElementById('passwordMeterBar').style.width = '0%';
    document.getElementById('passwordMeterText').textContent = 'Seguridad: baja';
  }

  document.getElementById('claveNueva').addEventListener('input', updatePasswordMeter);
  document.getElementById('btnLimpiarForm').addEventListener('click', limpiarFormulario);

  document.getElementById('frmPerfilClave').addEventListener('submit', function(e) {
    e.preventDefault();

    const claveNueva = document.getElementById('claveNueva').value;
    const claveConfirmar = document.getElementById('claveConfirmar').value;

    if (claveNueva !== claveConfirmar) {
      showToast('La confirmacion de clave no coincide', true);
      return;
    }

    const btn = document.getElementById('btnGuardarClave');
    const oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span><span>Guardando...</span>';

    const params = new URLSearchParams();
    params.append('action', 'cambiar_clave');
    params.append('clave_actual', document.getElementById('claveActual').value);
    params.append('clave_nueva', document.getElementById('claveNueva').value);
    params.append('clave_confirmar', document.getElementById('claveConfirmar').value);

    fetch('api/perfil.php', {
      method: 'POST',
      body: params,
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then((r) => r.json())
    .then((r) => {
      if (!r.success) {
        showToast(r.message || 'No se pudo actualizar la clave', true);
        return;
      }
      showToast(r.message || 'Clave actualizada correctamente', false);
      limpiarFormulario();
    })
    .catch(() => showToast('Error de red', true))
    .finally(() => {
      btn.disabled = false;
      btn.innerHTML = oldHtml;
    });
  });
})();
</script>
<script src="js/sidebar-badges.js"></script>
</body>
</html>
