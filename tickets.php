<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Usuario';
$user_rol = $_SESSION['user_rol'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tickets - Helpdesk Clonsa</title>
  <link rel="stylesheet" href="template/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="template/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="template/css/vertical-layout-light/style.css">
  <style>
    .progress-cell { min-width: 150px; }
    .filter-section { background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
  </style>
</head>
<body>
  <div class="container-scroller">
    <!-- Navbar -->
    <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
      <div class="navbar-brand-wrapper d-flex justify-content-center">
        <div class="navbar-brand-inner-wrapper d-flex justify-content-between align-items-center w-100">  
          <a class="navbar-brand brand-logo" href="dashboard.php"><h3 class="text-primary mb-0">Helpdesk</h3></a>
          <a class="navbar-brand brand-logo-mini" href="dashboard.php"><h4 class="text-primary mb-0">HD</h4></a>
        </div>  
      </div>
      <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
        <ul class="navbar-nav navbar-nav-right">
          <li class="nav-item nav-profile dropdown">
            <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
              <span class="nav-profile-name"><?php echo htmlspecialchars($user_name); ?></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right navbar-dropdown">
              <a class="dropdown-item" href="api/logout.php"><i class="mdi mdi-logout text-primary"></i> Cerrar Sesión</a>
            </div>
          </li>
        </ul>
      </div>
    </nav>
    
    <div class="container-fluid page-body-wrapper">
      <!-- Sidebar -->
      <nav class="sidebar sidebar-offcanvas" id="sidebar">
        <ul class="nav">
          <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="mdi mdi-view-dashboard menu-icon"></i><span class="menu-title">Dashboard</span></a></li>
          <li class="nav-item active"><a class="nav-link" href="tickets.php"><i class="mdi mdi-ticket menu-icon"></i><span class="menu-title">Tickets</span></a></li>
          <li class="nav-item"><a class="nav-link" href="nuevo-ticket.php"><i class="mdi mdi-plus-circle menu-icon"></i><span class="menu-title">Nuevo Ticket</span></a></li>
        </ul>
      </nav>
      
      <div class="main-panel">
        <div class="content-wrapper">
          <div class="row">
            <div class="col-md-12 grid-margin">
              <div class="d-flex justify-content-between align-items-center">
                <h4 class="font-weight-bold mb-0">Gestión de Tickets</h4>
                <button class="btn btn-primary" onclick="location.href='nuevo-ticket.php'"><i class="mdi mdi-plus"></i> Nuevo Ticket</button>
              </div>
            </div>
          </div>
          
          <!-- Filtros -->
          <div class="row">
            <div class="col-md-12">
              <div class="filter-section">
                <h5 class="mb-3">Filtros</h5>
                <div class="row">
                  <div class="col-md-3">
                    <div class="form-group">
                      <label>Buscar</label>
                      <input type="text" class="form-control" id="searchInput" placeholder="Código o título...">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label>Estado</label>
                      <select class="form-control" id="filterEstado">
                        <option value="">Todos</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label>Actividad</label>
                      <select class="form-control" id="filterActividad">
                        <option value="">Todas</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label>Ubicación</label>
                      <select class="form-control" id="filterUbicacion">
                        <option value="">Todas</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group">
                      <label>Progreso</label>
                      <select class="form-control" id="filterProgreso">
                        <option value="">Todos</option>
                        <option value="0-25">0-25%</option>
                        <option value="26-50">26-50%</option>
                        <option value="51-75">51-75%</option>
                        <option value="76-99">76-99%</option>
                        <option value="100">100%</option>
                      </select>
                    </div>
                  </div>
                  <div class="col-md-1">
                    <div class="form-group">
                      <label>&nbsp;</label>
                      <button class="btn btn-secondary btn-block" onclick="clearFilters()">Limpiar</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Tabla de Tickets -->
          <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table table-hover" id="ticketsTable">
                      <thead>
                        <tr>
                          <th>Código</th>
                          <th>Título</th>
                          <th>Actividad</th>
                          <th>Ubicación</th>
                          <th>Estado</th>
                          <th>Progreso</th>
                          <th>Creado</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody id="ticketsBody">
                        <tr><td colspan="8" class="text-center">Cargando tickets...</td></tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <script src="template/vendors/js/vendor.bundle.base.js"></script>
  <script>
    let allTickets = [];
    
    $(document).ready(function() {
      loadCatalogos();
      loadTickets();
      
      $('#searchInput, #filterEstado, #filterActividad, #filterUbicacion, #filterProgreso').on('change keyup', function() {
        filterTickets();
      });
    });
    
    function loadCatalogos() {
      $.get('api/catalogos.php?tipo=estados', function(response) {
        if(response.success) {
          response.data.forEach(item => {
            $('#filterEstado').append(`<option value="${item.id}">${item.nombre}</option>`);
          });
        }
      });
      
      $.get('api/catalogos.php?tipo=actividades', function(response) {
        if(response.success) {
          response.data.forEach(item => {
            $('#filterActividad').append(`<option value="${item.id}">${item.nombre}</option>`);
          });
        }
      });
      
      $.get('api/catalogos.php?tipo=ubicaciones', function(response) {
        if(response.success) {
          response.data.forEach(item => {
            $('#filterUbicacion').append(`<option value="${item.id}">${item.nombre}</option>`);
          });
        }
      });
    }
    
    function loadTickets() {
      $.get('api/tickets.php?action=listar', function(response) {
        if(response.success) {
          allTickets = response.data;
          renderTickets(allTickets);
        } else {
          $('#ticketsBody').html(`<tr><td colspan="8" class="text-center text-danger">Error al cargar tickets</td></tr>`);
        }
      });
    }
    
    function renderTickets(tickets) {
      if(tickets.length === 0) {
        $('#ticketsBody').html(`<tr><td colspan="8" class="text-center">No se encontraron tickets</td></tr>`);
        return;
      }
      
      let html = '';
      tickets.forEach(ticket => {
        const progresoColor = ticket.progreso == 100 ? 'success' : (ticket.progreso >= 50 ? 'info' : 'warning');
        html += `
          <tr>
            <td><a href="ticket-detalle.php?codigo=${ticket.codigo}">${ticket.codigo}</a></td>
            <td>${ticket.titulo}</td>
            <td><span class="badge" style="background-color: ${ticket.actividad_color}">${ticket.actividad || 'N/A'}</span></td>
            <td>${ticket.ubicacion || 'N/A'}</td>
            <td><span class="badge" style="background-color: ${ticket.estado_color}; color: white;">${ticket.estado}</span></td>
            <td class="progress-cell">
              <div class="progress" style="height: 20px;">
                <div class="progress-bar bg-${progresoColor}" style="width: ${ticket.progreso}%">${ticket.progreso}%</div>
              </div>
            </td>
            <td>${formatDate(ticket.created_at)}</td>
            <td>
              <button class="btn btn-sm btn-primary" onclick="verTicket('${ticket.codigo}')"><i class="mdi mdi-eye"></i></button>
              <button class="btn btn-sm btn-success" onclick="editarProgreso('${ticket.codigo}', ${ticket.progreso})"><i class="mdi mdi-pencil"></i></button>
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
      
      let filtered = allTickets.filter(ticket => {
        const matchSearch = !search || ticket.codigo.toLowerCase().includes(search) || ticket.titulo.toLowerCase().includes(search);
        const matchEstado = !estado || ticket.estado_id == estado;
        const matchActividad = !actividad || ticket.actividad_id == actividad;
        const matchUbicacion = !ubicacion || ticket.ubicacion_id == ubicacion;
        let matchProgreso = true;
        if(progreso) {
          if(progreso === '100') {
            matchProgreso = ticket.progreso == 100;
          } else {
            const [min, max] = progreso.split('-').map(Number);
            matchProgreso = ticket.progreso >= min && ticket.progreso <= max;
          }
        }
        return matchSearch && matchEstado && matchActividad && matchUbicacion && matchProgreso;
      });
      
      renderTickets(filtered);
    }
    
    function clearFilters() {
      $('#searchInput').val('');
      $('#filterEstado, #filterActividad, #filterUbicacion, #filterProgreso').val('');
      renderTickets(allTickets);
    }
    
    function formatDate(dateString) {
      const date = new Date(dateString);
      return date.toLocaleDateString('es-PE') + ' ' + date.toLocaleTimeString('es-PE', {hour: '2-digit', minute: '2-digit'});
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
  </script>
</body>
</html>
