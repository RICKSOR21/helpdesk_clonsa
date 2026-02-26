<?php
require_once 'config/session.php';
session_start();

// Evitar caché del navegador
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ⭐ CRÍTICO: Inicializar last_activity si no existe
// Esto asegura que el timer comience desde 0 cuando el usuario entra
if (!isset($_SESSION['last_activity'])) {
    $_SESSION['last_activity'] = time();
}

require_once 'config/config.php';
$db = getDBConnection();

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';

// Estadísticas
$stmt = $db->query("SELECT COUNT(*) as total FROM tickets");
$total_tickets = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM tickets WHERE estado_id IN (1,2,3)");
$tickets_abiertos = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM tickets WHERE estado_id = 5");
$tickets_cerrados = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM tickets WHERE estado_id = 2");
$tickets_en_progreso = $stmt->fetch()['total'];

$stmt = $db->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, fecha_resolucion)) as promedio FROM tickets WHERE fecha_resolucion IS NOT NULL");
$tiempo_promedio = round($stmt->fetch()['promedio'] ?? 0, 1);

$stmt = $db->query("SELECT ca.nombre, COUNT(t.id) as total FROM canales_atencion ca LEFT JOIN tickets t ON ca.id = t.canal_atencion_id GROUP BY ca.id ORDER BY total DESC LIMIT 1");
$canal_mas_usado = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT u.nombre_completo, COUNT(t.id) as resueltos FROM usuarios u INNER JOIN tickets t ON u.id = t.asignado_a WHERE t.estado_id = 5 GROUP BY u.id ORDER BY resueltos DESC LIMIT 5");
$top_resueltos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT u.nombre_completo, AVG(TIMESTAMPDIFF(HOUR, t.created_at, t.fecha_resolucion)) as promedio_horas FROM usuarios u INNER JOIN tickets t ON u.id = t.asignado_a WHERE t.fecha_resolucion IS NOT NULL GROUP BY u.id HAVING COUNT(t.id) >= 1 ORDER BY promedio_horas ASC LIMIT 5");
$top_rapidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->query("SELECT t.codigo, t.titulo, t.progreso, u.nombre_completo as creador, e.nombre as estado, e.color as estado_color, t.created_at FROM tickets t INNER JOIN usuarios u ON t.usuario_id = u.id INNER JOIN estados e ON t.estado_id = e.id ORDER BY t.created_at DESC LIMIT 10");
$tickets_recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Configuración de timeout desde config.php
$SESSION_TIMEOUT_JS = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : 120;
$SESSION_POPUP_TIMEOUT_JS = defined('SESSION_POPUP_TIMEOUT') ? SESSION_POPUP_TIMEOUT : 900;

date_default_timezone_set('America/Lima');
$hora = date('H');
$saludo = ($hora >= 5 && $hora < 12) ? 'Buenos dias' : (($hora >= 12 && $hora < 19) ? 'Buenas tardes' : 'Buenas noches');
$primer_nombre = explode(' ', $user_name)[0];
$fecha_actual = date('d/m/Y');
?>

<!DOCTYPE html>

<html lang="es">

<head>
  <!-- Required meta tags -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>SIRA - Dashboard</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="template/vendors/feather/feather.css">
  <link rel="stylesheet" href="template/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="template/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="template/vendors/typicons/typicons.css">
  <link rel="stylesheet" href="template/vendors/simple-line-icons/css/simple-line-icons.css">
  <link rel="stylesheet" href="template/vendors/css/vendor.bundle.base.css">
  <!-- endinject -->
  <!-- Plugin css for this page -->
  <link rel="stylesheet" href="template/vendors/datatables.net-bs4/dataTables.bootstrap4.css">
  <link rel="stylesheet" href="template/js/select.dataTables.min.css">
  <!-- End plugin css for this page -->
  <!-- inject:css -->
  <link rel="stylesheet" href="template/css/vertical-layout-light/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="template/images/favicon.svg" />
</head>
<body class="authenticated">
  <div class="container-scroller"> 
    <!-- partial:partials/_navbar.html -->
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
            <h3 class="welcome-sub-text">Tu resumen de rendimiento - Portal SIRA Clonsa Ingenieria</h3>
          </li>
        </ul>
        <ul class="navbar-nav ms-auto">

          <?php
          // ============================================
          // LÓGICA DE FILTROS SEGÚN ROL
          // ============================================

          $departamento_usuario = $_SESSION['departamento_id'] ?? 1;
          $departamento_nombre = 'General';

          // Obtener nombre del departamento del usuario
          if ($departamento_usuario) {
              $stmt = $db->prepare("SELECT nombre FROM departamentos WHERE id = ?");
              $stmt->execute([$departamento_usuario]);
              $dept = $stmt->fetch(PDO::FETCH_ASSOC);
              if ($dept) {
                  $departamento_nombre = $dept['nombre'];
              }
          }

          // Determinar qué departamentos puede ver
          $puede_ver_todos = ($user_rol === 'Administrador' || $user_rol === 'Admin');
          $es_jefe = ($user_rol === 'Jefe');
          $es_usuario = ($user_rol === 'Usuario');
          ?>

          <li class="nav-item dropdown d-none d-lg-block">
              <?php if ($puede_ver_todos): ?>
                  <!-- ✅ ADMIN: Puede ver y cambiar todos los departamentos -->
                  <a class="nav-link dropdown-bordered dropdown-toggle dropdown-toggle-split" 
                    id="messageDropdown" 
                    href="#" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false"
                    style="cursor: pointer;">
                      General
                  </a>
                  <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="messageDropdown">
                      <a class="dropdown-item py-3" href="#" data-departamento="all">
                          <p class="mb-0 font-weight-medium float-left">General</p>
                      </a>
                      <a class="dropdown-item py-3" href="#" data-departamento="2">
                          <p class="mb-0 font-weight-medium float-left">Soporte Tecnico</p>
                      </a>
                      <a class="dropdown-item py-3" href="#" data-departamento="3">
                          <p class="mb-0 font-weight-medium float-left">Administracion</p>
                      </a>
                      <a class="dropdown-item py-3" href="#" data-departamento="4">
                          <p class="mb-0 font-weight-medium float-left">IT & Desarrollo</p>
                      </a>
                  </div>
                  
              <?php elseif ($es_jefe): ?>
                  <!-- 🔒 JEFE: Solo puede ver su departamento (bloqueado) -->
                  <a class="nav-link dropdown-bordered" 
                    id="messageDropdown" 
                    style="cursor: not-allowed; background-color: #f5f5f5; opacity: 0.8;">
                      <?php echo htmlspecialchars($departamento_nombre); ?>
                  </a>
                  
              <?php else: ?>
                  <!-- 🔒 USUARIO: Solo puede ver su departamento (bloqueado) -->
                  <a class="nav-link dropdown-bordered" 
                    id="messageDropdown" 
                    style="cursor: not-allowed; background-color: #f5f5f5; opacity: 0.8;">
                      <?php echo htmlspecialchars($departamento_nombre); ?>
                  </a>
              <?php endif; ?>
          </li>

          <!-- ⚠️ PASAR VARIABLES PHP A JAVASCRIPT -->
          <script>
              // Variables globales para JavaScript
              window.USER_ROL = '<?php echo $user_rol; ?>';
              window.USER_DEPARTAMENTO = <?php echo $departamento_usuario; ?>;
              window.PUEDE_VER_TODOS = <?php echo $puede_ver_todos ? 'true' : 'false'; ?>;
              window.CURRENT_USER_ID = <?php echo intval($_SESSION['user_id'] ?? 0); ?>;
          </script>
       
          <li class="nav-item d-none d-lg-block">
            <div class="input-group date navbar-date-picker">
              <span class="input-group-addon input-group-prepend border-right">
                <span class="icon-calendar input-group-text calendar-icon"></span>
              </span>
              <input type="text" id="fechaDesde" class="form-control" readonly style="background: white; width: 120px; text-align: center; cursor: not-allowed;">
            </div>
          </li>
          <li class="nav-item d-none d-lg-block mx-1">
              <i class="mdi mdi-arrow-right-bold text-muted"></i>
          </li>
          <li class="nav-item d-none d-lg-block mx-1">
              <div class="input-group date navbar-date-picker">
                <span class="input-group-addon input-group-prepend border-right">
                  <span class="icon-calendar input-group-text calendar-icon"></span>
                </span>
                <input type="text" id="fechaHasta" class="form-control" readonly style="background: white; width: 120px; text-align: center; cursor: not-allowed;">
              </div>
          </li>
          <!-- COMUNICADOS DEL SISTEMA (Icono de carta) -->
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
                <div class="text-center py-3">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
              </div>
            </div>
          </li>

          <!-- NOTIFICACIONES DE TICKETS (Icono de campana) -->
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
                <div class="text-center py-3">
                  <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
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
                <p class="fw-light text-muted mb-0"><a href="/cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="395855555c5754564b5c5756795e54585055175a5654"><?php echo htmlspecialchars($_SESSION["user_email"] ?? ""); ?></a></p>
              </div>
              <a class="dropdown-item" href="perfil.php"><i class="dropdown-item-icon mdi mdi-account-outline text-primary me-2"></i> Mi Perfil <span class="badge badge-pill badge-danger">1</span></a>
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
    <!-- partial -->
    <div class="container-fluid page-body-wrapper">
      <!-- partial:partials/_settings-panel.html -->
      <div class="theme-setting-wrapper">
        <div id="settings-trigger"><i class="ti-settings"></i></div>
        <div id="theme-settings" class="settings-panel">
          <i class="settings-close ti-close"></i>
          <p class="settings-heading">SIDEBAR SKINS</p>
          <div class="sidebar-bg-options selected" id="sidebar-light-theme"><div class="img-ss rounded-circle bg-light border me-3"></div>Light</div>
          <div class="sidebar-bg-options" id="sidebar-dark-theme"><div class="img-ss rounded-circle bg-dark border me-3"></div>Dark</div>
          <p class="settings-heading mt-2">HEADER SKINS</p>
          <div class="color-tiles mx-0 px-4">
            <div class="tiles success"></div>
            <div class="tiles warning"></div>
            <div class="tiles danger"></div>
            <div class="tiles info"></div>
            <div class="tiles dark"></div>
            <div class="tiles default"></div>
          </div>
        </div>
      </div>
      <div id="right-sidebar" class="settings-panel">
        <i class="settings-close ti-close"></i>
        <ul class="nav nav-tabs border-top" id="setting-panel" role="tablist">
          <li class="nav-item">
            <a class="nav-link active" id="todo-tab" data-bs-toggle="tab" href="#todo-section" role="tab" aria-controls="todo-section" aria-expanded="true">TO DO LIST</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" id="chats-tab" data-bs-toggle="tab" href="#chats-section" role="tab" aria-controls="chats-section">CHATS</a>
          </li>
        </ul>
        <div class="tab-content" id="setting-content">
          <div class="tab-pane fade show active scroll-wrapper" id="todo-section" role="tabpanel" aria-labelledby="todo-section">
            <div class="add-items d-flex px-3 mb-0">
              <form class="form w-100">
                <div class="form-group d-flex">
                  <input type="text" class="form-control todo-list-input" placeholder="Add To-do">
                  <button type="submit" class="add btn btn-primary todo-list-add-btn" id="add-task">Add</button>
                </div>
              </form>
            </div>
            <div class="list-wrapper px-3">
              <ul class="d-flex flex-column-reverse todo-list">
                <li>
                  <div class="form-check">
                    <label class="form-check-label">
                      <input class="checkbox" type="checkbox">
                      Team review meeting at 3.00 PM
                    </label>
                  </div>
                  <i class="remove ti-close"></i>
                </li>
                <li>
                  <div class="form-check">
                    <label class="form-check-label">
                      <input class="checkbox" type="checkbox">
                      Prepare for presentation
                    </label>
                  </div>
                  <i class="remove ti-close"></i>
                </li>
                <li>
                  <div class="form-check">
                    <label class="form-check-label">
                      <input class="checkbox" type="checkbox">
                      Resolve all the low priority tickets due today
                    </label>
                  </div>
                  <i class="remove ti-close"></i>
                </li>
                <li class="completed">
                  <div class="form-check">
                    <label class="form-check-label">
                      <input class="checkbox" type="checkbox" checked>
                      Schedule meeting for next week
                    </label>
                  </div>
                  <i class="remove ti-close"></i>
                </li>
                <li class="completed">
                  <div class="form-check">
                    <label class="form-check-label">
                      <input class="checkbox" type="checkbox" checked>
                      Project review
                    </label>
                  </div>
                  <i class="remove ti-close"></i>
                </li>
              </ul>
            </div>
            <h4 class="px-3 text-muted mt-5 fw-light mb-0">Events</h4>
            <div class="events pt-4 px-3">
              <div class="wrapper d-flex mb-2">
                <i class="ti-control-record text-primary me-2"></i>
                <span>Feb 11 2018</span>
              </div>
              <p class="mb-0 font-weight-thin text-gray">Creating component page build a js</p>
              <p class="text-gray mb-0">The total number of sessions</p>
            </div>
            <div class="events pt-4 px-3">
              <div class="wrapper d-flex mb-2">
                <i class="ti-control-record text-primary me-2"></i>
                <span>Feb 7 2018</span>
              </div>
              <p class="mb-0 font-weight-thin text-gray">Meeting with Alisa</p>
              <p class="text-gray mb-0 ">Call Sarah Graves</p>
            </div>
          </div>
          <!-- To do section tab ends -->
          <div class="tab-pane fade" id="chats-section" role="tabpanel" aria-labelledby="chats-section">
            <div class="d-flex align-items-center justify-content-between border-bottom">
              <p class="settings-heading border-top-0 mb-3 pl-3 pt-0 border-bottom-0 pb-0">Friends</p>
              <small class="settings-heading border-top-0 mb-3 pt-0 border-bottom-0 pb-0 pr-3 fw-normal">See All</small>
            </div>
            <ul class="chat-list">
              <li class="list active">
                <div class="profile"><img src="template/images/faces/face1.jpg" alt="image"><span class="online"></span></div>
                <div class="info">
                  <p>Thomas Douglas</p>
                  <p>Available</p>
                </div>
                <small class="text-muted my-auto">19 min</small>
              </li>
              <li class="list">
                <div class="profile"><img src="template/images/faces/face2.jpg" alt="image"><span class="offline"></span></div>
                <div class="info">
                  <div class="wrapper d-flex">
                    <p>Catherine</p>
                  </div>
                  <p>Away</p>
                </div>
                <div class="badge badge-success badge-pill my-auto mx-2">4</div>
                <small class="text-muted my-auto">23 min</small>
              </li>
              <li class="list">
                <div class="profile"><img src="template/images/faces/face3.jpg" alt="image"><span class="online"></span></div>
                <div class="info">
                  <p>Daniel Russell</p>
                  <p>Available</p>
                </div>
                <small class="text-muted my-auto">14 min</small>
              </li>
              <li class="list">
                <div class="profile"><img src="template/images/faces/face4.jpg" alt="image"><span class="offline"></span></div>
                <div class="info">
                  <p>James Richardson</p>
                  <p>Away</p>
                </div>
                <small class="text-muted my-auto">2 min</small>
              </li>
              <li class="list">
                <div class="profile"><img src="template/images/faces/face5.jpg" alt="image"><span class="online"></span></div>
                <div class="info">
                  <p>Madeline Kennedy</p>
                  <p>Available</p>
                </div>
                <small class="text-muted my-auto">5 min</small>
              </li>
              <li class="list">
                <div class="profile"><img src="template/images/faces/face6.jpg" alt="image"><span class="online"></span></div>
                <div class="info">
                  <p>Sarah Graves</p>
                  <p>Available</p>
                </div>
                <small class="text-muted my-auto">47 min</small>
              </li>
            </ul>
          </div>
          <!-- chat tab ends -->
        </div>
      </div>
      <!-- partial -->

      <!-- partial:partials/_sidebar.html -->
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <!-- Dashboard - ACTIVE -->
          <li class="nav-item active">
            <a class="nav-link" href="dashboard.php">
              <i class="mdi mdi-view-dashboard menu-icon"></i>
              <span class="menu-title">Dashboard</span>
            </a>
          </li>
          
          <li class="nav-item nav-category">GESTION DE TICKETS</li>
          
          <!-- Tickets -->
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
                <?php if ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe'): ?>
                <li class="nav-item"> <a class="nav-link d-flex align-items-center justify-content-between" href="tickets-mis.php"><span>Mis Tickets</span><span class="count-mis-sidebar sidebar-badge">0</span></a></li>
                <?php endif; ?>
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
      <!-- partial -->

      <!-- partial -->
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-sm-12">
              <div class="home-tab">
                <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                  <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Ult. Semana</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="profile-tab" data-bs-toggle="tab" href="#audiences" role="tab" aria-selected="false">Ult Mes</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="contact-tab" data-bs-toggle="tab" href="#demographics" role="tab" aria-selected="false">Ult A&ntilde;o</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link border-0" id="more-tab" data-bs-toggle="tab" href="#more" role="tab" aria-selected="false">Personalizado</a>
                    </li>
                  </ul>
                  <div>
                    <div class="btn-wrapper">
                      <a href="#" class="btn btn-outline-secondary btn-share"><i class="mdi mdi-share-variant"></i> Compartir</a>
                      <a href="#" class="btn btn-outline-secondary btn-print"><i class="mdi mdi-printer"></i> Imprimir</a>
                      <a href="#" class="btn btn-primary text-white me-0 btn-export"><i class="mdi mdi-download"></i> Exportar</a>
                    </div>
                  </div>
                </div>
                <div class="tab-content tab-content-basic">
                  <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview"> 
                    <div class="row">
                      <div class="col-sm-12">
                        <!-- REEMPLAZAR los cards por estos con IDs -->
                        <div class="statistics-details d-flex align-items-center justify-content-between">
                          <div>
                            <p class="statistics-title">Ticket Abiertos</p>
                            <h3 class="rate-percentage" id="ticketsAbiertos">12</h3>
                            <p class="text-success d-flex" id="ticketsAbiertosComp">
                              <i class="mdi mdi-menu-up"></i><span>+0.1%</span>
                            </p>
                          </div>
                          <div>
                            <p class="statistics-title">Ticket en Atención</p>
                            <h3 class="rate-percentage" id="ticketsProceso">4</h3>
                            <p class="text-success d-flex" id="ticketsProcesoComp">
                              <i class="mdi mdi-menu-up"></i><span>+0.1%</span>
                            </p>
                          </div>
                          <div>
                              <p class="statistics-title">Ticket Resueltos</p>
                              <h3 class="rate-percentage" id="ticketsResueltos">
                                  <span class="spinner-border spinner-border-sm" role="status"></span>
                              </h3>
                              <p class="text-success d-flex" id="ticketsResueltosComp">
                                  <i class="mdi mdi-menu-up"></i><span>+0%</span>
                              </p>
                          </div>
                          <div class="d-none d-md-block">
                            <p class="statistics-title">Tiempo Promedio</p>
                            <h3 class="rate-percentage" id="tiempoPromedio">2d:30min</h3>
                            <p class="text-success d-flex" id="tiempoPromedioComp">
                              <i class="mdi mdi-menu-down"></i><span>+0.8%</span>
                            </p>
                          </div>
                          <div class="d-none d-md-block">
                              <p class="statistics-title">Canal Frecuente</p>
                              <h3 class="rate-percentage" id="canalFrecuente">Correo</h3>
                              <p class="text-success d-flex">
                                  <i class="mdi mdi-menu-up"></i><span id="canalFrecuenteTotal">12 Registros</span>
                              </p>
                          </div>
                          <div class="d-none d-md-block">
                              <p class="statistics-title">Falla Frecuente</p>
                              <h3 class="rate-percentage" id="fallaFrecuente">Hardware</h3>
                              <p class="text-success d-flex">
                                  <i class="mdi mdi-menu-up"></i><span id="fallaFrecuenteTotal">10 Registros</span>
                              </p>
                          </div>
                        </div>
                      </div>
                    </div> 
                    <div class="row">
                      <div class="col-lg-8 d-flex flex-column">
                        <div class="row flex-grow">
                          <div class="col-12 col-lg-4 col-lg-12 grid-margin stretch-card">
                            <div class="card card-rounded">
                              <div class="card-body">
                                <div class="d-sm-flex justify-content-between align-items-start">
                                  <div>
                                    <h4 class="card-title card-title-dash">Resultados Mantenimientos</h4>
                                    <h5 class="card-subtitle card-subtitle-dash">Clonsa Ingenieria</h5>
                                  </div>
                                  <div class="mx-5" id="performance-line-legend"></div>
                                </div>
                                <div class="chartjs-wrapper mt-5" style="position: relative; min-height: 300px;">
                                  <canvas id="performaneLine" style="opacity: 0; transition: opacity 0.3s;"></canvas>
                                  
                                  <!-- Loading spinner -->
                                  <div id="chart-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                    <div class="spinner-border text-primary" role="status">
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                      <div class="col-lg-4 d-flex flex-column">
                        <div class="row flex-grow">
                          <div class="col-md-6 col-lg-12 grid-margin stretch-card">
                            <div class="card bg-primary card-rounded">
                                <div class="card-body pb-0">
                                    <h4 class="card-title card-title-dash text-white mb-4">Total Ticket's Sira</h4>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <p class="status-summary-ight-white mb-1">Movimientos</p>
                                            <h2 class="text-info" id="totalTicketsValue">0</h2>
                                        </div>
                                        <div class="col-sm-8">
                                            <div class="status-summary-chart-wrapper pb-4">
                                                <canvas id="status-summary"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                          </div>
                          <div class="col-md-6 col-lg-12 grid-margin stretch-card">
                            <div class="card card-rounded">
                                <div class="card-body">
                                    <p><b> <h4 class="text-black fw-bold text-success text-center">ULTIMOS HISTORICOS SIRA</h4> </b></p>
                                    <br>
                                    <div class="row" id="actividadesCirculos">
                                        <!-- Los círculos se generan dinámicamente aquí -->
                                    </div>
                                </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-lg-8 d-flex flex-column">
                        <div class="row flex-grow">
                          <div class="col-12 grid-margin stretch-card">
                            <div class="card card-rounded">
                              <div class="card-body">
                                <div class="d-sm-flex justify-content-between align-items-start">
                                  <div>
                                    <h4 class="card-title card-title-dash">Historico Personal Clonsa</h4>
                                   <p class="card-subtitle card-subtitle-dash">Top Ticket Resueltos - SIRA Clonsa Ingenieria 2026</p>
                                  </div>
                                  <div>
                                    <div class="dropdown" id="filtroActividadesDropdown">
                                      <button class="btn btn-secondary dropdown-toggle toggle-dark btn-lg mb-0 me-0" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <span id="filtroActividadTexto">Todos</span>
                                      </button>
                                      <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2" id="dropdownActividadesMenu">
                                        <!-- Se carga dinámicamente -->
                                        <h6 class="dropdown-header">Cargando...</h6>
                                      </div>
                                    </div>
                                  </div>
                                </div>

                                <div class="d-sm-flex align-items-center justify-content-between mb-3">
                                  <div class="d-flex align-items-center gap-3">
                                    <h2 class="mb-0 fw-bold">
                                      <i class="mdi mdi-trophy text-warning"></i>
                                      <span id="topEmpleadoNombre">Cargando...</span>
                                    </h2>
                                    <h4 class="mb-0" id="topEmpleadoTickets">0 Completados</h4>
                                    <h4 class="mb-0 text-success" id="topEmpleadoPorcentaje">(0%)</h4>
                                  </div>
                                </div>
                                <div class="chartjs-bar-wrapper" style="min-height: 400px;">
                                  <canvas id="marketingOverview"></canvas>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>



                      <div class="col-lg-4 d-flex flex-column">
                        <div class="row flex-grow">
                          <div class="col-12 grid-margin stretch-card">
                            <div class="card card-rounded">
                              <div class="card-body">
                                <div class="row">
                                  <div class="col-lg-12">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                      <h4 class="card-title card-title-dash mb-0">Ultimos Ticket Creados</h4>
                                      <a href="tickets.php" class="text-primary" style="font-size: 13px;">Ver Todos <i class="mdi mdi-arrow-right"></i></a>
                                    </div>

                                    <!-- Contenedor dinámico para últimos tickets -->
                                    <div id="ultimosTicketsContainer">
                                      <div class="text-center py-4">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status">
                                          <span class="visually-hidden">Cargando...</span>
                                        </div>
                                      </div>
                                    </div>

                                  </div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
        <!-- partial:partials/_footer.html -->
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
        <!-- partial -->
      </div>
      <!-- main-panel ends -->
    </div>
    <!-- page-body-wrapper ends -->
  </div>
  <!-- container-scroller -->
   
  <!-- plugins:js -->
  <script src="template/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- Plugin js for this page -->
  <script src="template/vendors/chart.js/Chart.min.js"></script>
  <script src="template/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
  <script src="template/vendors/progressbar.js/progressbar.min.js"></script>

  <!-- End plugin js for this page -->
  <!-- inject:js -->
  <script src="template/js/off-canvas.js"></script>
  <script src="template/js/hoverable-collapse.js"></script>
  <script src="template/js/template.js"></script>
  <script src="template/js/settings.js"></script>
  <script src="template/js/todolist.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page-->
  <script src="template/js/dashboard.js"></script>
  <script src="template/js/Chart.roundedBarCharts.js"></script>

  <!-- ⭐ SESSION MANAGER ⭐ -->
  <script>
      const SESSION_TIMEOUT = <?php echo $SESSION_TIMEOUT_JS; ?>;
      const SESSION_POPUP_TIMEOUT = <?php echo $SESSION_POPUP_TIMEOUT_JS; ?>;
      
      console.log('=== SESSION MANAGER CONFIG ===');
      console.log('SESSION_TIMEOUT:', SESSION_TIMEOUT, 'segundos');
      console.log('SESSION_POPUP_TIMEOUT:', SESSION_POPUP_TIMEOUT, 'segundos');
    </script>
    
  <script src="assets/js/session-manager.js"></script>

  <!-- Flatpickr (Date Picker) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

  <!-- ProgressBar.js para círculos dinámicos -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/progressbar.js/1.1.0/progressbar.min.js"></script>

  <!-- Dashboard Filters -->
  <script src="assets/js/dashboard-filters.js"></script>

  <!-- Top Empleados -->
  <script src="assets/js/top-empleados.js?v=<?php echo time(); ?>"></script>
  <script src="assets/js/ultimos-tickets.js?v=<?php echo time(); ?>"></script>
  <!-- Notificaciones -->
  <script src="assets/js/notificaciones.js?v=<?php echo time(); ?>"></script>

  <!-- Html2Canvas para capturas de pantalla -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

  <!-- SweetAlert2 para alertas elegantes -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Dashboard Export (Share, Print, Export) -->
  <script src="assets/js/dashboard-export.js?v=<?php echo time(); ?>"></script>

  <style>
/* ================================================
   ESTILOS PARA BADGES DE NOTIFICACIONES
   ================================================ */
.count-indicator {
    position: relative !important;
}
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
    text-align: center !important;
}
.count-indicator .badge-notif.show {
    display: flex !important;
}
.sidebar-badge {
    background: #dc3545;
    color: #fff;
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
.sidebar-badge.show {
    display: inline-flex;
}

/* ================================================
   ESTILOS PARA ITEMS DE NOTIFICACIÓN - PROFESIONAL
   ================================================ */
.notif-item {
    padding: 10px 14px !important;
    border-bottom: 1px solid #eee;
    transition: all 0.2s ease;
    text-decoration: none !important;
}
.notif-item:hover {
    background-color: #f5f7fa !important;
}
.notif-item.unread {
    background-color: #eef4ff !important;
}
.notif-item:last-child {
    border-bottom: none;
}

/* Fila principal de notificación */
.notif-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Indicador de estado (punto rojo o icono visto) */
.status-indicator {
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.status-indicator .unread-dot {
    width: 9px;
    height: 9px;
    background: #dc3545;
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
    animation: pulse-dot 2s infinite;
}
@keyframes pulse-dot {
    0%, 100% { box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15); }
    50% { box-shadow: 0 0 0 5px rgba(220, 53, 69, 0.1); }
}

/* Icono de la notificación */
.notif-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.notif-icon i {
    font-size: 18px;
}

/* Contenido de la notificación */
.notif-content {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.notif-title {
    font-size: 12px;
    font-weight: 500;
    color: #333;
    margin: 0;
    line-height: 1.3;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.notif-item.unread .notif-title {
    font-weight: 600;
    color: #1a1a1a;
}
.notif-subtitle {
    font-size: 11px;
    color: #666;
    margin: 0;
    line-height: 1.3;
}
.notif-time {
    font-size: 10px;
    color: #1F3BB3;
    display: flex;
    align-items: center;
    gap: 2px;
    margin-top: 1px;
}
.notif-time i {
    font-size: 10px;
}

/* ================================================
   ESTILOS PERSONALIZADOS PARA GRÁFICO DE MANTENIMIENTOS
   ================================================ */

/* Contenedor del header del gráfico - CRÍTICO */
.card-rounded .card-body .d-sm-flex {
    display: flex !important;
    justify-content: space-between !important;
    align-items: flex-start !important;
    margin-bottom: 20px !important;
    flex-wrap: nowrap !important; /* ✅ Evita salto de línea */
}

/* Contenedor izquierdo (título) */
.card-rounded .card-body .d-sm-flex > div:first-child {
    flex: 0 0 auto !important; /* ✅ No crece ni se encoge */
    margin-right: 20px !important;
}

/* Contenedor de leyenda */
#performance-line-legend {
    flex: 0 1 auto !important;
    margin-left: auto !important;
}

#performance-line-legend .chartjs-legend,
#performance-line-legend > div {
    display: block !important;
}

#performance-line-legend .chartjs-legend ul,
#performance-line-legend .legend-list {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    justify-content: flex-end !important;
    align-items: center !important;
    gap: 0 !important;
    padding: 0 !important;
    margin: 0 !important;
    list-style: none !important;
}

#performance-line-legend .chartjs-legend .legend-item,
#performance-line-legend .legend-list .legend-item,
#performance-line-legend li.legend-item {
    display: inline-flex !important;
    align-items: center !important;
    font-size: 13px !important;
    color: #6B778C !important;
    white-space: nowrap !important;
    cursor: pointer !important;
    padding: 6px 15px !important;
    margin: 0 5px !important;
    border-radius: 6px !important;
    transition: all 0.2s ease !important;
    user-select: none !important;
    background-color: transparent !important;
    line-height: 1.2 !important;
}

#performance-line-legend .chartjs-legend .legend-item:hover,
#performance-line-legend .legend-list .legend-item:hover,
#performance-line-legend li.legend-item:hover {
    background-color: rgba(102, 126, 234, 0.1) !important;
    transform: translateY(-1px);
}

#performance-line-legend .chartjs-legend .legend-item:active,
#performance-line-legend .legend-list .legend-item:active,
#performance-line-legend li.legend-item:active {
    transform: translateY(0);
}

#performance-line-legend .chartjs-legend .legend-color,
#performance-line-legend .legend-list .legend-color,
#performance-line-legend .legend-color {
    display: inline-block !important;
    width: 12px !important;
    height: 12px !important;
    min-width: 12px !important;
    min-height: 12px !important;
    border-radius: 50% !important;
    margin-right: 8px !important;
    flex-shrink: 0 !important;
}

#performance-line-legend .chartjs-legend .legend-text,
#performance-line-legend .legend-list .legend-text,
#performance-line-legend .legend-text {
    font-weight: 400 !important;
    line-height: 1.2 !important;
    display: inline !important;
}

/* Estilo cuando está deshabilitado */
#performance-line-legend .legend-item-hidden {
    opacity: 0.35 !important;
}

#performance-line-legend .legend-item-hidden .legend-color {
    background-color: #bbb !important;
}

#performance-line-legend .legend-item-hidden .legend-text {
    text-decoration: line-through !important;
    color: #bbb !important;
}

/* Animación suave para el canvas */
#performaneLine {
    transition: opacity 0.3s ease-in-out;
}

/* Loading spinner */
#chart-loading {
    z-index: 10;
}

/* Mejorar responsive del gráfico */
.chartjs-wrapper {
    position: relative;
    height: 300px;
}

/* Responsive */
@media (max-width: 1200px) {
    #performance-line-legend .legend-item,
    #performance-line-legend li.legend-item {
        padding: 5px 10px !important;
        margin: 0 3px !important;
        font-size: 12px !important;
    }
}

@media (max-width: 992px) {
    #performance-line-legend .legend-item,
    #performance-line-legend li.legend-item {
        font-size: 11px !important;
        padding: 4px 8px !important;
        margin: 0 2px !important;
    }

    #performance-line-legend .legend-color {
        width: 10px !important;
        height: 10px !important;
        min-width: 10px !important;
        min-height: 10px !important;
        margin-right: 6px !important;
    }
}

@media (max-width: 768px) {
    .chartjs-wrapper {
        height: 250px;
    }

    #performance-line-legend .legend-item,
    #performance-line-legend li.legend-item {
        font-size: 10px !important;
        padding: 3px 6px !important;
        margin: 0 2px !important;
    }

    #performance-line-legend .legend-color {
        width: 8px !important;
        height: 8px !important;
        min-width: 8px !important;
        min-height: 8px !important;
        margin-right: 4px !important;
    }
}

@media (max-width: 576px) {
    .card-rounded .card-body .d-sm-flex {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
    
    #performance-line-legend {
        margin-top: 15px !important;
        margin-left: 0 !important;
        width: 100% !important;
    }
    
    #performance-line-legend .chartjs-legend ul {
        justify-content: flex-start !important;
        flex-wrap: wrap !important;
        gap: 12px !important;
    }
}


/* ================================================
   ESTILOS PARA STATUS SUMMARY - CÍRCULOS DINÁMICOS
   ================================================ */

   #actividadesCirculos {
    width: 100%;
}

#actividadesCirculos .circle-progress-width {
    width: 60px;
    height: 60px;
}

#actividadesCirculos .progressbar-text {
    font-family: "Ubuntu", sans-serif;
    font-size: 1rem !important;
    font-weight: 600;
    color: #667eea;
}

#actividadesCirculos .text-small {
    font-size: 0.875rem;
    color: #6c757d;
}

@media (max-width: 576px) {
    #actividadesCirculos .circle-progress-width {
        width: 50px;
        height: 50px;
    }
    
    #actividadesCirculos .progressbar-text {
        font-size: 0.875rem !important;
    }
}

/* ✅ Alineación mejorada de círculos */
#actividadesCirculos .d-flex {
    align-items: center !important;
    min-height: 70px;
}

#actividadesCirculos .flex-grow-1 {
    overflow: hidden;
}

#actividadesCirculos .text-truncate {
    max-width: 100%;
}

/* ================================================
   STATUS SUMMARY - CÍRCULOS PROFESIONALES
   ================================================ */

   #actividadesCirculos .circle-progress-container {
    width: 70px;
    height: 70px;
    flex-shrink: 0;
}

#actividadesCirculos .progressbar-js-circle {
    width: 100% !important;
    height: 100% !important;
}

#actividadesCirculos .progressbar-text {
    font-family: "Ubuntu", sans-serif !important;
    font-size: 0.95rem !important;
    font-weight: 600 !important;
    color: #667eea !important;
}

#actividadesCirculos .activity-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #2c3e50;
    line-height: 1.3;
    word-wrap: break-word;
}

#actividadesCirculos .gap-3 {
    gap: 1rem !important;
}

/* Responsive */
@media (max-width: 576px) {
    #actividadesCirculos .circle-progress-container {
        width: 60px;
        height: 60px;
    }
    
    #actividadesCirculos .progressbar-text {
        font-size: 0.85rem !important;
    }
    
    #actividadesCirculos .activity-name {
        font-size: 0.8rem;
    }
}

/* Dropdown actividades */
.actividad-toggle {
    padding: 8px 16px !important;
}

.actividad-toggle .actividad-check {
    display: none;
}

.actividad-toggle.visible .actividad-check {
    display: block;
}

.selected-activity-badge {
    margin-left: 10px;
}

/* Loading spinner */
#chart-loading {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ================================================
   COMPARATIVAS - COLORES DE FLECHAS
   ================================================ */

   .statistics-details .text-success {
    color: #28a745 !important; /* Verde - Bueno */
}

.statistics-details .text-danger {
    color: #dc3545 !important; /* Rojo - Malo */
}

.statistics-details .text-info {
    color: #17a2b8 !important; /* Celeste - Neutral/0% */
}

.statistics-details .text-success i,
.statistics-details .text-danger i,
.statistics-details .text-info i {
    font-size: 1.1rem;
    vertical-align: middle;
    margin-right: 2px;
}

/* ================================================
   CÍRCULOS STATUS SUMMARY - NOMBRES CORTOS
   ================================================ */

#actividadesCirculos .activity-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #2c3e50;
    line-height: 1.3;
    word-wrap: break-word;
}

/* ================================================
   ESTILOS PARA BOTONES DE EXPORTACIÓN
   ================================================ */
.btn-wrapper .btn {
    padding: 8px 16px;
    font-size: 13px;
    font-weight: 500;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.btn-wrapper .btn-outline-secondary {
    border-color: #d1d5db;
    color: #6b7280;
}

.btn-wrapper .btn-outline-secondary:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
    color: #374151;
}

.btn-wrapper .btn i {
    margin-right: 5px;
}

/* ================================================
   ESTILOS PARA IMPRESIÓN
   ================================================ */
@media print {
    /* Ocultar sidebar y elementos no necesarios */
    .sidebar,
    .navbar,
    .btn-wrapper,
    .nav-tabs,
    footer,
    .settings-panel,
    .sidebar-offcanvas,
    #shareModal,
    .modal-backdrop {
        display: none !important;
    }

    /* Expandir contenido principal */
    .main-panel {
        width: 100% !important;
        margin-left: 0 !important;
        padding: 0 !important;
    }

    .content-wrapper {
        padding: 10px !important;
    }

    /* Asegurar que los gráficos se vean */
    .card {
        break-inside: avoid;
        page-break-inside: avoid;
        margin-bottom: 15px !important;
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }

    /* Ajustar tamaños */
    body {
        font-size: 12px !important;
        background: white !important;
    }

    .statistics-details .statistics-title {
        font-size: 11px !important;
    }

    .statistics-details .rate-percentage {
        font-size: 20px !important;
    }

    /* Encabezado de impresión */
    .print-header {
        display: block !important;
        text-align: center;
        padding: 20px 0;
        border-bottom: 2px solid #333;
        margin-bottom: 20px;
    }

    .print-header h1 {
        font-size: 24px;
        margin: 0;
    }

    .print-header p {
        font-size: 12px;
        color: #666;
        margin: 5px 0 0;
    }

    /* Ocultar elementos interactivos */
    .dropdown-toggle::after,
    .btn,
    button {
        display: none !important;
    }

    /* Mostrar todos los tabs */
    .tab-content > .tab-pane {
        display: block !important;
        opacity: 1 !important;
    }

    /* ================================================
       LEYENDA DEL GRÁFICO - IMPRESIÓN
       ================================================ */

    /* Contenedor principal de la leyenda */
    #performance-line-legend {
        margin-left: 0 !important;
        margin-top: 15px !important;
        width: auto !important;
        flex: 1 1 auto !important;
    }

    /* Lista de leyenda - usar display table para mejor alineación */
    #performance-line-legend .chartjs-legend ul,
    #performance-line-legend .legend-list,
    #performance-line-legend ul {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        justify-content: flex-end !important;
        align-items: center !important;
        gap: 30px !important;
        padding: 5px 0 !important;
        margin: 0 !important;
        list-style: none !important;
    }

    /* Items de leyenda */
    #performance-line-legend .chartjs-legend .legend-item,
    #performance-line-legend .legend-item,
    #performance-line-legend li {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        font-size: 10px !important;
        padding: 3px 8px !important;
        white-space: nowrap !important;
        margin: 0 !important;
        background: transparent !important;
    }

    /* Círculos de color */
    #performance-line-legend .legend-color {
        width: 8px !important;
        height: 8px !important;
        min-width: 8px !important;
        min-height: 8px !important;
        border-radius: 50% !important;
        flex-shrink: 0 !important;
        display: inline-block !important;
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }

    /* Texto de leyenda */
    #performance-line-legend .legend-text {
        font-size: 10px !important;
        line-height: 1 !important;
    }

    /* Forzar que el contenedor flex sea en fila */
    .card-rounded .card-body > .d-sm-flex {
        flex-direction: row !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: nowrap !important;
    }

    /* Canvas del gráfico */
    .chartjs-wrapper {
        height: 200px !important;
        min-height: 200px !important;
    }

    canvas {
        max-width: 100% !important;
        height: auto !important;
    }

    /* Asegurar colores al imprimir */
    * {
        print-color-adjust: exact !important;
        -webkit-print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
}

/* Clase para cuando se está imprimiendo */
body.printing-dashboard .sidebar,
body.printing-dashboard .navbar {
    display: none !important;
}

body.printing-dashboard .main-panel {
    width: 100% !important;
    margin-left: 0 !important;
}

</style>
<script src="js/sidebar-badges.js"></script>
</body>
</html>






