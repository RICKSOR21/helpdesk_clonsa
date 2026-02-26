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
$user_id = $_SESSION['user_id'];

$SESSION_TIMEOUT_JS = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 120;
$SESSION_POPUP_TIMEOUT_JS = defined('SESSION_POPUP_TIMEOUT') ? SESSION_POPUP_TIMEOUT : 900;

date_default_timezone_set('America/Lima');
$hora = date('H');
$saludo = ($hora >= 5 && $hora < 12) ? 'Buenos dias' : (($hora >= 12 && $hora < 19) ? 'Buenas tardes' : 'Buenas noches');
$primer_nombre = explode(' ', $user_name)[0];

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

// Obtener proximo numero de ticket para preview
$nextTicketPreview = 'TKN-XX-###';
try {
    $stmt = $db->prepare("SELECT d.abreviatura, COALESCE(tc.ultimo_numero, 0) + 1 as next_num
                          FROM departamentos d
                          LEFT JOIN ticket_contadores tc ON d.id = tc.departamento_id
                          WHERE d.id = ?");
    $stmt->execute([$departamento_usuario]);
    $previewData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($previewData) {
        $abrev = $previewData['abreviatura'] ?? 'GN';
        $nextNum = $previewData['next_num'] ?? 1;
        $nextTicketPreview = 'TKN-' . $abrev . '-' . str_pad($nextNum, 2, '0', STR_PAD_LEFT);
    }
} catch(Exception $e) {
    // Mantener valor por defecto
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIRA - Crear Ticket</title>
  <link rel="stylesheet" href="template/vendors/feather/feather.css">
  <link rel="stylesheet" href="template/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="template/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="template/vendors/typicons/typicons.css">
  <link rel="stylesheet" href="template/vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="template/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="template/css/vertical-layout-light/style.css">
  <link rel="shortcut icon" href="template/images/favicon.svg" />
  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
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
    #descripcion { min-height: 90px; resize: vertical; line-height: 1.5; }
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

    .file-upload-zone { border: 2px dashed #d0d0d0; border-radius: 10px; padding: 30px; text-align: center; background: #fafafa; transition: all 0.3s; cursor: pointer; }
    .file-upload-zone:hover, .file-upload-zone.dragover { border-color: #1F3BB3; background: #f0f4ff; }
    .file-upload-zone i { font-size: 48px; color: #1F3BB3; margin-bottom: 15px; }
    .file-upload-zone h5 { font-size: 16px; color: #333; margin-bottom: 5px; }
    .file-upload-zone p { font-size: 13px; color: #666; margin-bottom: 0; }
    .file-list { margin-top: 15px; }
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
    .file-item .file-remove { width: 30px; height: 30px; border-radius: 50%; border: none; background: #fee; color: #dc3545; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
    .file-item .file-remove:hover { background: #dc3545; color: #fff; }
    .file-item .file-preview { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; margin-right: 12px; }

    .btn-submit { background: linear-gradient(135deg, #1F3BB3 0%, #4a6fd1 100%); border: none; padding: 12px 30px; font-size: 15px; font-weight: 500; border-radius: 8px; transition: all 0.3s; }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(31, 59, 179, 0.3); }
    .btn-cancel { background: #f0f0f0; color: #666; border: none; padding: 12px 30px; font-size: 15px; font-weight: 500; border-radius: 8px; }
    .btn-cancel:hover { background: #e0e0e0; color: #333; }
    .upload-progress { display: none; margin-top: 15px; }
    .upload-progress .progress { height: 8px; border-radius: 4px; }
    .upload-progress-text { font-size: 12px; color: #666; margin-top: 5px; }
    .alert-file-error { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 10px 15px; border-radius: 8px; font-size: 13px; margin-top: 10px; display: none; }

    /* Modal de proceso */
    .modal-process { background: rgba(0,0,0,0.7); }
    .modal-process .modal-content { border: none; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
    .modal-process .modal-body { padding: 40px; text-align: center; }

    /* Loader animado */
    .process-loader { width: 80px; height: 80px; margin: 0 auto 25px; position: relative; }
    .process-loader .spinner { width: 100%; height: 100%; border: 4px solid #e9ecef; border-top-color: #1F3BB3; border-radius: 50%; animation: spin 1s linear infinite; }
    @keyframes spin { to { transform: rotate(360deg); } }

    .process-title { font-size: 20px; font-weight: 600; color: #333; margin-bottom: 10px; }
    .process-subtitle { font-size: 14px; color: #666; margin-bottom: 25px; }

    .process-progress { background: #e9ecef; border-radius: 10px; height: 8px; overflow: hidden; margin-bottom: 10px; }
    .process-progress-bar { height: 100%; background: linear-gradient(90deg, #1F3BB3, #4a6fd1); border-radius: 10px; transition: width 0.3s ease; width: 0%; }
    .process-progress-text { font-size: 12px; color: #888; }

    /* Check animado de exito */
    .success-checkmark { width: 100px; height: 100px; margin: 0 auto 25px; position: relative; }
    .success-checkmark .check-icon { width: 100px; height: 100px; position: relative; border-radius: 50%; box-sizing: content-box; border: 4px solid #4CAF50; }
    .success-checkmark .icon-line { height: 5px; background-color: #4CAF50; display: block; border-radius: 2px; position: absolute; z-index: 10; }
    .success-checkmark .icon-line.line-tip { top: 46px; left: 16px; width: 25px; transform: rotate(45deg); animation: icon-line-tip 0.75s; }
    .success-checkmark .icon-line.line-long { top: 38px; left: 28px; width: 47px; transform: rotate(-45deg); animation: icon-line-long 0.75s; }
    .success-checkmark .icon-circle { top: -4px; left: -4px; z-index: 10; width: 100px; height: 100px; border-radius: 50%; position: absolute; box-sizing: content-box; border: 4px solid rgba(76, 175, 80, .5); }
    .success-checkmark .icon-fix { top: 8px; width: 5px; left: 26px; z-index: 1; height: 85px; position: absolute; transform: rotate(-45deg); background-color: #fff; }

    @keyframes icon-line-tip { 0% { width: 0; left: 1px; top: 19px; } 54% { width: 0; left: 1px; top: 19px; } 70% { width: 50px; left: -8px; top: 37px; } 84% { width: 17px; left: 21px; top: 48px; } 100% { width: 25px; left: 16px; top: 46px; } }
    @keyframes icon-line-long { 0% { width: 0; right: 46px; top: 54px; } 65% { width: 0; right: 46px; top: 54px; } 84% { width: 55px; right: 0px; top: 35px; } 100% { width: 47px; right: 8px; top: 38px; } }

    /* Error icon */
    .error-icon { width: 100px; height: 100px; margin: 0 auto 25px; background: #fee; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    .error-icon i { font-size: 50px; color: #dc3545; }

    .ticket-code-box { background: linear-gradient(135deg, #1F3BB3 0%, #4a6fd1 100%); border-radius: 12px; padding: 20px; margin: 20px 0; }
    .ticket-code-label { font-size: 12px; color: rgba(255,255,255,0.8); margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px; }
    .ticket-code-value { font-size: 28px; font-weight: 700; color: #fff; font-family: 'Courier New', monospace; letter-spacing: 2px; }

    .modal-btn-primary { background: linear-gradient(135deg, #1F3BB3 0%, #4a6fd1 100%); border: none; padding: 12px 40px; font-size: 15px; font-weight: 500; border-radius: 8px; color: #fff; transition: all 0.3s; }
    .modal-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(31, 59, 179, 0.3); color: #fff; }
    .modal-btn-secondary { background: #f0f0f0; border: none; padding: 12px 40px; font-size: 15px; font-weight: 500; border-radius: 8px; color: #666; }
    .modal-btn-secondary:hover { background: #e0e0e0; color: #333; }

    /* Preview del codigo */
    .ticket-preview-box { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border: 2px dashed #1F3BB3; border-radius: 12px; padding: 15px; margin-top: 15px; text-align: center; }
    .ticket-preview-label { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; }
    .ticket-preview-code { font-size: 20px; font-weight: 700; color: #1F3BB3; font-family: 'Courier New', monospace; }
    .ticket-preview-note { font-size: 11px; color: #999; margin-top: 8px; font-style: italic; }
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
            <h3 class="welcome-sub-text">Crear Nuevo Ticket - Portal SIRA Clonsa Ingenieria</h3>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">
          <li class="nav-item d-none d-lg-block">
            <span class="nav-link dropdown-bordered" style="cursor: default; background-color: #e9ecef; opacity: 0.9; pointer-events: none;">
              <i class="mdi mdi-office-building me-1"></i> General
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
                <li class="nav-item"><a class="nav-link d-flex align-items-center justify-content-between" href="tickets.php"><span>Todos los Tickets</span><span class="count-todos-sidebar sidebar-badge">0</span></a></li>
                <li class="nav-item"><a class="nav-link active" href="tickets-create.php">Crear Ticket</a></li>
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
          <div class="row">
            <div class="col-12">
              <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                  <h4 class="mb-1"><i class="mdi mdi-ticket-confirmation text-primary me-2"></i>Crear Nuevo Ticket</h4>
                  <p class="text-muted mb-0">Complete el formulario para registrar un nuevo ticket de soporte</p>
                </div>
                <a href="tickets.php" class="btn btn-outline-secondary"><i class="mdi mdi-arrow-left me-1"></i> Volver</a>
              </div>
            </div>
          </div>

          <form id="ticketForm" enctype="multipart/form-data">
            <div class="row">
              <div class="col-lg-8">
                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-information"></i>Informacion del Ticket</div>
                  <div class="row">
                    <div class="col-md-12 mb-3">
                      <div class="form-group">
                        <label class="required-field"><i class="mdi mdi-format-title text-primary"></i>Titulo del Ticket</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" placeholder="Describa brevemente el problema o solicitud" required>
                      </div>
                    </div>
                    <div class="col-md-12 mb-3">
                      <div class="form-group">
                        <label class="required-field"><i class="mdi mdi-text text-info"></i>Descripcion Detallada</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Proporcione todos los detalles relevantes..." required></textarea>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-tag-multiple"></i>Clasificacion</div>
                  <div class="row">
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label class="required-field"><i class="mdi mdi-clipboard-text text-info"></i>Tipo de Actividad</label>
                        <select class="form-control" id="actividad_id" name="actividad_id" required><option value="">Seleccione</option></select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-alert-circle text-danger"></i>Tipo de Falla</label>
                        <select class="form-control" id="tipo_falla_id" name="tipo_falla_id"><option value="">Seleccione si aplica</option></select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label class="required-field"><i class="mdi mdi-phone text-success"></i>Canal de Atencion</label>
                        <select class="form-control" id="canal_atencion_id" name="canal_atencion_id" required><option value="">Seleccione</option></select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label class="required-field"><i class="mdi mdi-flag text-warning"></i>Prioridad</label>
                        <select class="form-control" id="prioridad_id" name="prioridad_id" required><option value="">Seleccione</option></select>
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
                        <select class="form-control select2-search" id="ubicacion_id" name="ubicacion_id"><option value="">Buscar ubicacion...</option></select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-radar text-info"></i>Equipo</label>
                        <select class="form-control select2-search" id="equipo_id" name="equipo_id"><option value="">Buscar equipo...</option></select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-barcode text-secondary"></i>Codigo de Equipo</label>
                        <select class="form-control select2-search" id="codigo_equipo_id" name="codigo_equipo_id"><option value="">Buscar codigo...</option></select>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-attachment"></i>Archivos Adjuntos</div>
                  <div class="file-upload-zone" id="dropZone">
                    <i class="mdi mdi-cloud-upload"></i>
                    <h5>Arrastre archivos aqui o haga clic para seleccionar</h5>
                    <p>Maximo 10MB por archivo. Sin limite de archivos. Todos los formatos permitidos.</p>
                    <input type="file" id="fileInput" name="archivos[]" multiple style="display: none;">
                  </div>
                  <div class="alert-file-error" id="fileError"></div>
                  <div class="file-list" id="fileList"></div>
                  <div class="upload-progress" id="uploadProgress">
                    <div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div></div>
                    <div class="upload-progress-text">Subiendo archivos...</div>
                  </div>
                </div>
              </div>

              <div class="col-lg-4">
                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-account-group"></i>Asignacion</div>
                  <div class="form-group mb-3">
                    <label><i class="mdi mdi-office-building text-primary"></i>Departamento</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($departamento_nombre); ?>" readonly style="background:#f8f9fa; cursor:default;">
                    <input type="hidden" id="departamento_id" name="departamento_id" value="<?php echo intval($departamento_usuario); ?>">
                  </div>
                  <div class="form-group mb-3">
                    <label><i class="mdi mdi-account text-success"></i>Asignar a</label>
                    <select class="form-control" id="asignado_a" name="asignado_a"><option value="">Sin asignar</option></select>
                  </div>
                </div>

                <div class="form-section">
                  <div class="form-section-title"><i class="mdi mdi-card-account-phone"></i>Informacion de Contacto</div>
                  <div class="form-group mb-3">
                    <label><i class="mdi mdi-account text-info"></i>Nombre del Solicitante</label>
                    <input type="text" class="form-control" id="solicitante_nombre" name="solicitante_nombre" placeholder="Nombre completo">
                  </div>
                  <div class="form-group mb-3">
                    <label><i class="mdi mdi-email text-danger"></i>Email de Contacto</label>
                    <input type="email" class="form-control" id="solicitante_email" name="solicitante_email" placeholder="correo@ejemplo.com">
                  </div>
                  <div class="form-group mb-3">
                    <label><i class="mdi mdi-phone text-success"></i>Telefono</label>
                    <input type="text" class="form-control" id="solicitante_telefono" name="solicitante_telefono" placeholder="+51 999 999 999">
                  </div>
                </div>

                <div class="form-section" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                  <div class="form-section-title" style="color: white; border-bottom-color: rgba(255,255,255,0.2);"><i class="mdi mdi-information"></i>Resumen</div>
                  <div class="d-flex justify-content-between mb-2"><span style="opacity: 0.8;">Archivos adjuntos:</span><span class="fw-bold" id="filesCount">0</span></div>
                  <div class="d-flex justify-content-between mb-2"><span style="opacity: 0.8;">Tamano total:</span><span class="fw-bold" id="totalSize">0 KB</span></div>
                  <div class="d-flex justify-content-between"><span style="opacity: 0.8;">Creado por:</span><span class="fw-bold"><?php echo htmlspecialchars($primer_nombre); ?></span></div>

                  <!-- Preview del codigo de ticket -->
                  <div class="ticket-preview-box" style="background: rgba(255,255,255,0.15); border-color: rgba(255,255,255,0.3);">
                    <div class="ticket-preview-label" style="color: rgba(255,255,255,0.8);">Proximo codigo de ticket</div>
                    <div class="ticket-preview-code" style="color: #fff;" id="ticketPreviewCode"><?php echo $nextTicketPreview; ?></div>
                    <div class="ticket-preview-note" style="color: rgba(255,255,255,0.6);">El codigo se asignara automaticamente</div>
                  </div>
                </div>

                <div class="d-grid gap-2">
                  <button type="submit" class="btn btn-primary btn-submit"><i class="mdi mdi-check me-2"></i>Crear Ticket</button>
                  <a href="tickets.php" class="btn btn-cancel"><i class="mdi mdi-close me-2"></i>Cancelar</a>
                </div>
              </div>
            </div>
          </form>
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

  <!-- Modal de Proceso (Cargando) -->
  <div class="modal fade modal-process" id="modalProceso" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="process-loader"><div class="spinner"></div></div>
          <h4 class="process-title">Creando ticket...</h4>
          <p class="process-subtitle">Por favor espere mientras procesamos su solicitud</p>
          <div class="process-progress"><div class="process-progress-bar" id="processProgressBar"></div></div>
          <div class="process-progress-text" id="processProgressText">Iniciando...</div>
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
          <h4 class="process-title" style="color: #4CAF50;">Ticket Creado Exitosamente!</h4>
          <p class="process-subtitle">Su ticket ha sido registrado en el sistema</p>
          <div class="ticket-code-box">
            <div class="ticket-code-label">Codigo de Ticket</div>
            <div class="ticket-code-value" id="ticketCodigoExito">TKN-XX-000</div>
          </div>
          <p class="text-muted mb-4" style="font-size: 13px;">Guarde este codigo para dar seguimiento a su solicitud</p>
          <div class="d-flex justify-content-center gap-3">
            <a href="tickets.php" class="modal-btn-primary"><i class="mdi mdi-view-list me-2"></i>Ver Tickets</a>
            <button type="button" class="modal-btn-secondary" onclick="location.reload()"><i class="mdi mdi-plus me-2"></i>Crear Otro</button>
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
          <h4 class="process-title" style="color: #dc3545;">Error al Crear Ticket</h4>
          <p class="process-subtitle" id="errorMessage">Ha ocurrido un error inesperado</p>
          <div class="d-flex justify-content-center gap-3 mt-4">
            <button type="button" class="modal-btn-primary" data-bs-dismiss="modal"><i class="mdi mdi-refresh me-2"></i>Intentar de Nuevo</button>
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
  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
  <script>
    const SESSION_TIMEOUT = <?php echo $SESSION_TIMEOUT_JS; ?>;
    const SESSION_POPUP_TIMEOUT = <?php echo $SESSION_POPUP_TIMEOUT_JS; ?>;
    window.CURRENT_USER_ID = <?php echo intval($user_id); ?>;
    window.USER_ROL = '<?php echo addslashes($user_rol); ?>';
  </script>
  <script src="assets/js/session-manager.js"></script>
  <script src="assets/js/notificaciones.js?v=<?php echo time(); ?>"></script>

  <script>
    const MAX_FILE_SIZE = 10 * 1024 * 1024;
    let uploadedFiles = [];
    let isSubmitting = false; // Control para evitar doble envío

    // Modales
    const modalProceso = new bootstrap.Modal(document.getElementById('modalProceso'));
    const modalExito = new bootstrap.Modal(document.getElementById('modalExito'));
    const modalError = new bootstrap.Modal(document.getElementById('modalError'));

    $(document).ready(function() {
      const deptId = <?php echo intval($departamento_usuario); ?>;

      loadCatalogos();
      setupFileUpload();

      // Inicializar Select2 para los campos con busqueda
      $('.select2-search').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: function() { return $(this).data('placeholder') || 'Buscar...'; },
        allowClear: true,
        language: {
          noResults: function() { return "No se encontraron resultados"; },
          searching: function() { return "Buscando..."; }
        }
      });

      // Cargar automáticamente con el departamento del usuario
      if(deptId) {
        loadUsuariosDepartamento(deptId);
        loadCatalogosporDepartamento(deptId);
      }

      // Evitar doble envío
      $('#ticketForm').on('submit', function(e) {
        e.preventDefault();
        if(isSubmitting) return false;
        isSubmitting = true;
        submitTicket();
      });
    });

    function loadCatalogos() {
      // Cargar catálogos que no dependen del departamento
      $.get('api/catalogos.php?tipo=canales', function(r) { if(r.success) r.data.forEach(i => $('#canal_atencion_id').append(`<option value="${i.id}">${i.nombre}</option>`)); });
      $.get('api/catalogos.php?tipo=prioridades', function(r) { if(r.success) r.data.forEach(i => $('#prioridad_id').append(`<option value="${i.id}">${i.nombre}</option>`)); });
    }

    function loadCatalogosporDepartamento(deptId) {
      // Limpiar selects dependientes del departamento
      $('#actividad_id').html('<option value="">Seleccione</option>');
      $('#tipo_falla_id').html('<option value="">Seleccione si aplica</option>');
      $('#ubicacion_id').html('<option value="">Seleccione ubicación...</option>');
      $('#equipo_id').html('<option value="">Seleccione equipo...</option>');
      $('#codigo_equipo_id').html('<option value="">Buscar codigo...</option>');

      if(!deptId) {
        $('#ubicacion_id, #equipo_id, #codigo_equipo_id').trigger('change.select2');
        return;
      }

      // Cargar catálogos filtrados por departamento
      $.get('api/catalogos.php?tipo=actividades&departamento_id=' + deptId, function(r) {
        if(r.success) r.data.forEach(i => $('#actividad_id').append(`<option value="${i.id}">${i.nombre}</option>`));
      });
      $.get('api/catalogos.php?tipo=tipos_falla&departamento_id=' + deptId, function(r) {
        if(r.success) r.data.forEach(i => $('#tipo_falla_id').append(`<option value="${i.id}">${i.nombre}</option>`));
      });
      $.get('api/catalogos.php?tipo=ubicaciones&departamento_id=' + deptId, function(r) {
        if(r.success) {
          r.data.forEach(i => $('#ubicacion_id').append(`<option value="${i.id}">${i.nombre}</option>`));
          $('#ubicacion_id').trigger('change.select2');
        }
      });
      $.get('api/catalogos.php?tipo=equipos&departamento_id=' + deptId, function(r) {
        if(r.success) {
          r.data.forEach(i => $('#equipo_id').append(`<option value="${i.id}">${i.nombre}</option>`));
          $('#equipo_id').trigger('change.select2');
        }
      });
      $.get('api/catalogos.php?tipo=codigos_equipo&departamento_id=' + deptId, function(r) {
        if(r.success) {
          r.data.forEach(i => $('#codigo_equipo_id').append(`<option value="${i.id}">${i.codigo}${i.descripcion ? ' - ' + i.descripcion : ''}</option>`));
          $('#codigo_equipo_id').trigger('change.select2');
        }
      });
    }

    function loadUsuariosDepartamento(id) {
      $('#asignado_a').html('<option value="">Sin asignar</option>');
      if(!id) {
        $('#asignado_a').html('<option value="">Primero seleccione departamento</option>');
        return;
      }
      $('#asignado_a').html('<option value="">Cargando...</option>');
      $.get('api/usuarios.php?action=por_departamento&departamento_id=' + id, function(r) {
        if(window.USER_ROL === 'Usuario') {
          // Usuario normal: auto-seleccionar su nombre y deshabilitar
          if(r.success && r.data.length > 0) {
            var u = r.data[0];
            $('#asignado_a').html(`<option value="${u.id}">${u.nombre_completo}</option>`);
            $('#asignado_a').val(u.id).prop('disabled', true);
          }
        } else {
          $('#asignado_a').html('<option value="">Sin asignar</option>');
          if(r.success && r.data.length > 0) {
            r.data.forEach(i => $('#asignado_a').append(`<option value="${i.id}">${i.nombre_completo}</option>`));
          } else {
            $('#asignado_a').html('<option value="">No hay usuarios en este departamento</option>');
          }
        }
      }).fail(function() {
        $('#asignado_a').html('<option value="">Error al cargar usuarios</option>');
      });
    }

    function updateTicketPreview(deptId) {
      if(!deptId) {
        $('#ticketPreviewCode').text('<?php echo $nextTicketPreview; ?>');
        return;
      }
      const selected = $('#departamento_id option:selected');
      const abrev = selected.data('abrev') || 'GN';
      $.get('api/catalogos.php?tipo=next_ticket_number&departamento_id=' + deptId, function(r) {
        if(r.success && r.next_number) {
          const num = r.next_number < 100 ? String(r.next_number).padStart(2, '0') : r.next_number;
          $('#ticketPreviewCode').text('TKN-' + abrev + '-' + num);
        }
      });
    }

    function setupFileUpload() {
      const dropZone = document.getElementById('dropZone');
      const fileInput = document.getElementById('fileInput');
      dropZone.addEventListener('click', () => fileInput.click());
      dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
      dropZone.addEventListener('dragleave', () => { dropZone.classList.remove('dragover'); });
      dropZone.addEventListener('drop', (e) => { e.preventDefault(); dropZone.classList.remove('dragover'); handleFiles(e.dataTransfer.files); });
      fileInput.addEventListener('change', (e) => { handleFiles(e.target.files); });
    }

    function handleFiles(files) {
      const fileError = document.getElementById('fileError');
      fileError.style.display = 'none';
      Array.from(files).forEach(file => {
        if(file.size > MAX_FILE_SIZE) { fileError.innerHTML = `<i class="mdi mdi-alert me-2"></i>El archivo "${file.name}" excede 10MB`; fileError.style.display = 'block'; return; }
        uploadedFiles.push(file);
        addFileToList(file, uploadedFiles.length - 1);
      });
      updateSummary();
    }

    function addFileToList(file, index) {
      const fileList = document.getElementById('fileList');
      const ext = file.name.split('.').pop().toLowerCase();
      let iconClass = 'other', icon = 'mdi-file';
      if(['jpg','jpeg','png','gif','bmp','webp'].includes(ext)) { iconClass = 'image'; icon = 'mdi-file-image'; }
      else if(ext === 'pdf') { iconClass = 'pdf'; icon = 'mdi-file-pdf-box'; }
      else if(['doc','docx'].includes(ext)) { iconClass = 'doc'; icon = 'mdi-file-word'; }
      else if(['xls','xlsx'].includes(ext)) { iconClass = 'excel'; icon = 'mdi-file-excel'; }

      const fileItem = document.createElement('div');
      fileItem.className = 'file-item'; fileItem.id = `file-${index}`;

      if(['jpg','jpeg','png','gif','bmp','webp'].includes(ext)) {
        const reader = new FileReader();
        reader.onload = function(e) {
          fileItem.innerHTML = `<img src="${e.target.result}" class="file-preview" alt="${file.name}"><div class="file-info"><div class="file-name">${file.name}</div><div class="file-size">${formatFileSize(file.size)}</div></div><button type="button" class="file-remove" onclick="removeFile(${index})"><i class="mdi mdi-close"></i></button>`;
        };
        reader.readAsDataURL(file);
      } else {
        fileItem.innerHTML = `<div class="file-icon ${iconClass}"><i class="mdi ${icon}"></i></div><div class="file-info"><div class="file-name">${file.name}</div><div class="file-size">${formatFileSize(file.size)}</div></div><button type="button" class="file-remove" onclick="removeFile(${index})"><i class="mdi mdi-close"></i></button>`;
      }
      fileList.appendChild(fileItem);
    }

    function removeFile(index) { uploadedFiles.splice(index, 1); refreshFileList(); updateSummary(); }
    function refreshFileList() { const fileList = document.getElementById('fileList'); fileList.innerHTML = ''; uploadedFiles.forEach((file, index) => { addFileToList(file, index); }); }
    function formatFileSize(bytes) { if(bytes === 0) return '0 Bytes'; const k = 1024; const sizes = ['Bytes', 'KB', 'MB', 'GB']; const i = Math.floor(Math.log(bytes) / Math.log(k)); return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]; }
    function updateSummary() { document.getElementById('filesCount').textContent = uploadedFiles.length; const totalSize = uploadedFiles.reduce((acc, file) => acc + file.size, 0); document.getElementById('totalSize').textContent = formatFileSize(totalSize); }

    function submitTicket() {
      // Habilitar campos disabled temporalmente para incluirlos en FormData
      $('#asignado_a, #departamento_id').prop('disabled', false);
      const formData = new FormData(document.getElementById('ticketForm'));
      // Volver a deshabilitar si el rol es Usuario
      if(window.USER_ROL === 'Usuario') {
        $('#asignado_a, #departamento_id').prop('disabled', true);
      }

      // Eliminar archivos del FormData (vienen del input) para evitar duplicados
      formData.delete('archivos[]');

      // Agregar solo los archivos del array uploadedFiles
      uploadedFiles.forEach((file) => { formData.append('archivos[]', file); });

      // Mostrar modal de proceso
      modalProceso.show();
      updateProgress(10, 'Preparando datos...');

      $('.btn-submit').prop('disabled', true);

      $.ajax({
        url: 'api/tickets.php?action=crear',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
          const xhr = new window.XMLHttpRequest();
          xhr.upload.addEventListener('progress', function(e) {
            if(e.lengthComputable) {
              const percent = Math.round((e.loaded / e.total) * 70) + 20;
              updateProgress(percent, 'Subiendo archivos... ' + Math.round((e.loaded / e.total) * 100) + '%');
            }
          });
          return xhr;
        },
        success: function(response) {
          updateProgress(95, 'Finalizando...');

          setTimeout(function() {
            modalProceso.hide();

            if(response.success) {
              $('#ticketCodigoExito').text(response.codigo);
              setTimeout(() => modalExito.show(), 300);
            } else {
              $('#errorMessage').text(response.message || 'Ha ocurrido un error inesperado');
              setTimeout(() => modalError.show(), 300);
              $('.btn-submit').prop('disabled', false);
              isSubmitting = false;
            }
          }, 500);
        },
        error: function(xhr, status, error) {
          modalProceso.hide();
          $('#errorMessage').text('Error de conexion. Por favor intente nuevamente.');
          setTimeout(() => modalError.show(), 300);
          $('.btn-submit').prop('disabled', false);
          isSubmitting = false;
        }
      });
    }

    function updateProgress(percent, text) {
      $('#processProgressBar').css('width', percent + '%');
      $('#processProgressText').text(text);
    }
  </script>
<script src="js/sidebar-badges.js"></script>
</body>
</html>




