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
    .form-control, .form-select { border-radius: 8px; border: 1px solid #e0e0e0; padding: 10px 15px; font-size: 14px; transition: all 0.2s; }
    .form-control:focus, .form-select:focus { border-color: #1F3BB3; box-shadow: 0 0 0 0.2rem rgba(31, 59, 179, 0.15); }
    .required-field::after { content: " *"; color: #dc3545; }

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
                <a href="tickets.php" class="btn btn-sm btn-primary" style="font-size: 10px; padding: 3px 10px;">Ver tickets <i class="mdi mdi-arrow-right"></i></a>
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
              <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-account-outline text-primary me-2"></i> Mi Perfil</a>
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
                <li class="nav-item"><a class="nav-link" href="tickets.php">Todos los Tickets</a></li>
                <li class="nav-item"><a class="nav-link active" href="tickets-create.php">Crear Ticket</a></li>
                <li class="nav-item"><a class="nav-link" href="tickets-mis.php">Mis Tickets</a></li>
                <li class="nav-item"><a class="nav-link" href="tickets-asignados.php">Asignados a Mi</a></li>
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
                <li class="nav-item"><a class="nav-link" href="usuarios.php">Lista de Usuarios</a></li>
                <?php if ($user_rol === 'Administrador'): ?>
                <li class="nav-item"><a class="nav-link" href="usuarios-create.php">Crear Usuario</a></li>
                <?php endif; ?>
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
                <li class="nav-item"><a class="nav-link" href="reportes-general.php">Reporte General</a></li>
                <li class="nav-item"><a class="nav-link" href="reportes-departamento.php">Por Departamento</a></li>
                <li class="nav-item"><a class="nav-link" href="reportes-usuario.php">Por Usuario</a></li>
              </ul>
            </div>
          </li>
          <li class="nav-item nav-category">Ayuda</li>
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
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="5" placeholder="Proporcione todos los detalles relevantes..." required></textarea>
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
                        <select class="form-control" id="ubicacion_id" name="ubicacion_id"><option value="">Seleccione</option></select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-radar text-info"></i>Equipo</label>
                        <select class="form-control" id="equipo_id" name="equipo_id"><option value="">Seleccione</option></select>
                      </div>
                    </div>
                    <div class="col-md-6 mb-3">
                      <div class="form-group">
                        <label><i class="mdi mdi-barcode text-secondary"></i>Codigo de Equipo</label>
                        <select class="form-control" id="codigo_equipo_id" name="codigo_equipo_id"><option value="">Seleccione</option></select>
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
                    <select class="form-control" id="departamento_id" name="departamento_id"><option value="">Seleccione</option></select>
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

  <script src="template/vendors/js/vendor.bundle.base.js"></script>
  <script src="template/js/off-canvas.js"></script>
  <script src="template/js/hoverable-collapse.js"></script>
  <script src="template/js/template.js"></script>
  <script src="template/js/settings.js"></script>
  <script>const SESSION_TIMEOUT = <?php echo $SESSION_TIMEOUT_JS; ?>; const SESSION_POPUP_TIMEOUT = <?php echo $SESSION_POPUP_TIMEOUT_JS; ?>;</script>
  <script src="assets/js/session-manager.js"></script>
  <script src="assets/js/notificaciones.js?v=<?php echo time(); ?>"></script>

  <script>
    const MAX_FILE_SIZE = 10 * 1024 * 1024;
    let uploadedFiles = [];

    $(document).ready(function() {
      loadCatalogos();
      setupFileUpload();
      $('#departamento_id').on('change', function() { loadUsuariosDepartamento($(this).val()); });
      $('#ticketForm').on('submit', function(e) { e.preventDefault(); submitTicket(); });
    });

    function loadCatalogos() {
      $.get('api/catalogos.php?tipo=actividades', function(r) { if(r.success) r.data.forEach(i => $('#actividad_id').append(`<option value="${i.id}">${i.nombre}</option>`)); });
      $.get('api/catalogos.php?tipo=tipos_falla', function(r) { if(r.success) r.data.forEach(i => $('#tipo_falla_id').append(`<option value="${i.id}">${i.nombre}</option>`)); });
      $.get('api/catalogos.php?tipo=canales', function(r) { if(r.success) r.data.forEach(i => $('#canal_atencion_id').append(`<option value="${i.id}">${i.nombre}</option>`)); });
      $.get('api/catalogos.php?tipo=prioridades', function(r) { if(r.success) r.data.forEach(i => $('#prioridad_id').append(`<option value="${i.id}">${i.nombre}</option>`)); });
      $.get('api/catalogos.php?tipo=ubicaciones', function(r) { if(r.success) r.data.forEach(i => $('#ubicacion_id').append(`<option value="${i.id}">${i.nombre}</option>`)); });
      $.get('api/catalogos.php?tipo=equipos', function(r) { if(r.success) r.data.forEach(i => $('#equipo_id').append(`<option value="${i.id}">${i.nombre}</option>`)); });
      $.get('api/catalogos.php?tipo=codigos_equipo', function(r) { if(r.success) r.data.forEach(i => $('#codigo_equipo_id').append(`<option value="${i.id}">${i.codigo}</option>`)); });
      $.get('api/catalogos.php?tipo=departamentos', function(r) { if(r.success) r.data.forEach(i => $('#departamento_id').append(`<option value="${i.id}">${i.nombre}</option>`)); });
    }

    function loadUsuariosDepartamento(id) {
      $('#asignado_a').html('<option value="">Sin asignar</option>');
      if(!id) return;
      $.get('api/usuarios.php?action=por_departamento&departamento_id=' + id, function(r) { if(r.success) r.data.forEach(i => $('#asignado_a').append(`<option value="${i.id}">${i.nombre_completo}</option>`)); });
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
      const formData = new FormData(document.getElementById('ticketForm'));
      uploadedFiles.forEach((file) => { formData.append('archivos[]', file); });
      $('#uploadProgress').show();
      $('.btn-submit').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin me-2"></i>Creando...');

      $.ajax({
        url: 'api/tickets.php?action=crear', method: 'POST', data: formData, processData: false, contentType: false,
        xhr: function() {
          const xhr = new window.XMLHttpRequest();
          xhr.upload.addEventListener('progress', function(e) { if(e.lengthComputable) { const percent = Math.round((e.loaded / e.total) * 100); $('.progress-bar').css('width', percent + '%'); $('.upload-progress-text').text(`Subiendo... ${percent}%`); } });
          return xhr;
        },
        success: function(response) {
          if(response.success) { alert('Ticket creado exitosamente con codigo: ' + response.codigo); window.location.href = 'tickets.php'; }
          else { alert('Error: ' + response.message); $('.btn-submit').prop('disabled', false).html('<i class="mdi mdi-check me-2"></i>Crear Ticket'); $('#uploadProgress').hide(); }
        },
        error: function() { alert('Error al crear el ticket.'); $('.btn-submit').prop('disabled', false).html('<i class="mdi mdi-check me-2"></i>Crear Ticket'); $('#uploadProgress').hide(); }
      });
    }
  </script>
</body>
</html>

