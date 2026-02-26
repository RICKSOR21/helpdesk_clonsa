<?php
require_once 'config/session.php';
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Login - Helpdesk Clonsa</title>
  <link rel="stylesheet" href="template/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="template/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="template/css/vertical-layout-light/style.css">
  <link rel="shortcut icon" href="template/images/favicon.svg" />
  <style>
    /* ═══════════════════════════════════════════════════════════════
       FONDO CON IMAGEN - CON !IMPORTANT PARA FORZAR
       ═══════════════════════════════════════════════════════════════ */
    
    html {
      height: 100%;
    }
    
    body { 
      margin: 0 !important;
      padding: 0 !important;
      min-height: 100vh !important;
      background: url('/helpdesk/template/images/fondo6.jpg') no-repeat center center fixed !important;
      background-size: cover !important;
    }
    
    .container-scroller {
      position: relative;
      z-index: 1;
      min-height: 100vh;
    }
    
    .page-body-wrapper {
      background: none !important;
    }
    
    .content-wrapper {
      min-height: 100vh;
      background: none !important;
    }
    
    /* Formulario con transparencia */
    .auth-form-light { 
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3) !important; 
      border-radius: 20px !important; 
      background: rgba(255, 255, 255, 0.95) !important;
      backdrop-filter: blur(10px) !important;
      border: 1px solid rgba(255, 255, 255, 0.2) !important;
    }
    
    .brand-logo { margin-bottom: 30px; }
    .brand-logo i { 
      font-size: 70px; 
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
      -webkit-background-clip: text; 
      -webkit-text-fill-color: transparent; 
      background-clip: text; 
      margin-bottom: 10px; 
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }
    .brand-logo h2 { 
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
      -webkit-background-clip: text; 
      -webkit-text-fill-color: transparent; 
      background-clip: text; 
      font-weight: 700; 
    }
    
    .password-toggle { 
      position: absolute; 
      right: 15px; 
      top: 50%; 
      transform: translateY(-50%); 
      cursor: pointer; 
      color: #6c757d; 
      z-index: 10; 
      font-size: 20px; 
    }
    .password-toggle:hover { color: #4B49AC; }
    
    .form-group { position: relative; }
    .input-icon { 
      position: absolute; 
      left: 15px; 
      top: 50%; 
      transform: translateY(-50%); 
      color: #6c757d; 
      font-size: 20px; 
    }
    
    .form-control { 
      padding-left: 45px !important; 
      border: 2px solid #e9ecef; 
      border-radius: 10px; 
      transition: all 0.3s;
      background: rgba(255, 255, 255, 0.9);
    }
    .form-control:focus { 
      border-color: #667eea; 
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
      background: white;
    }
    
    .forgot-password { 
      color: #667eea; 
      text-decoration: none; 
      font-size: 14px; 
      transition: all 0.3s; 
      font-weight: 500; 
    }
    .forgot-password:hover { color: #764ba2; text-decoration: underline; }
    
    .auth-form-btn { 
      border-radius: 25px; 
      padding: 14px 50px; 
      font-weight: 600; 
      text-transform: uppercase; 
      letter-spacing: 1px; 
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
      border: none; 
      transition: all 0.3s; 
    }
    .auth-form-btn:hover { 
      transform: translateY(-2px); 
      box-shadow: 0 10px 30px rgba(102, 126, 234, 0.5); 
    }
    
    .page-title { color: #2c3e50; font-weight: 600; }
    .page-subtitle { color: #6c757d; }
    .modal-content { border-radius: 15px; border: none; }
    .verification-code-input { 
      font-size: 24px; 
      text-align: center; 
      letter-spacing: 10px; 
      font-weight: bold; 
    }
    
    /* Estilos del contador regresivo */
    .countdown-container {
      background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
      border: 2px solid #667eea30;
      border-radius: 10px;
      padding: 15px;
      margin-top: 15px;
      text-align: center;
    }
    .countdown-timer {
      font-size: 32px;
      font-weight: bold;
      color: #667eea;
      font-family: 'Courier New', monospace;
      letter-spacing: 3px;
      margin: 10px 0;
    }
    .countdown-timer.warning {
      color: #ffc107;
      animation: pulse 1s infinite;
    }
    .countdown-timer.danger {
      color: #dc3545;
      animation: pulse 0.5s infinite;
    }
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.6; }
    }
    .countdown-label {
      font-size: 13px;
      color: #6c757d;
      margin-bottom: 5px;
    }
    .countdown-icon {
      font-size: 24px;
      margin-bottom: 5px;
    }
  </style>
</head>
<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center auth px-0">
        <div class="row w-100 mx-0">
          <div class="col-lg-5 col-md-7 col-sm-9 mx-auto">
            <div class="auth-form-light text-center py-5 px-4 px-sm-5">
            <div class="brand-logo">
              <h1 class="mb-2" style="font-size: 56px; font-weight: 800; color: #667eea; letter-spacing: 2px;">SIRA</h1>
              <h6 class="text-muted font-weight-light" style="font-size: 14px; margin-bottom: 15px;">
                Sistema Interno de Tickets y Operaciones - Clonsa Ingeniería
              </h6>
            </div>

            <i class="mdi mdi-account-hard-hat" style="font-size: 50px; color: #667eea; margin-top: 10px; margin-bottom: 20px;"></i>

              
              <h4 class="font-weight-bold mb-2 page-title">¡Bienvenido de nuevo!</h4>
              <h6 class="font-weight-light mb-4 page-subtitle">Inicia sesión para continuar</h6>
              
              <div id="alert-container"></div>
              
              <form class="pt-3" id="loginForm">
                <div class="form-group">
                  <i class="mdi mdi-account input-icon"></i>
                  <input type="text" class="form-control form-control-lg" id="username" 
                         placeholder="Usuario o Email" required autocomplete="username">
                </div>
                
                <div class="form-group">
                  <i class="mdi mdi-lock input-icon"></i>
                  <input type="password" class="form-control form-control-lg" id="password" 
                         placeholder="Contraseña" required autocomplete="current-password">
                  <i class="mdi mdi-eye password-toggle" id="togglePassword"></i>
                </div>
                
                <div class="mt-3 text-right">
                  <a href="#" class="forgot-password" id="forgotPasswordLink">
                    ¿Olvidaste tu contraseña?
                  </a>
                </div>
                
                <div class="mt-4 text-center">
                  <button type="submit" class="btn btn-primary btn-lg auth-form-btn">
                    INICIAR SESIÓN
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Paso 1: Solicitar Email -->
  <div class="modal fade" id="forgotPasswordModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title font-weight-bold w-100 text-center">
            <i class="mdi mdi-email-lock" style="font-size: 50px; color: #667eea;"></i>
            <br>Recuperar Contraseña
          </h5>
          <button type="button" class="close" onclick="cerrarModal('forgotPasswordModal')">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body px-4 pt-2">
          <p class="text-muted text-center mb-4">
            Ingresa tu correo electrónico y te enviaremos un código de verificación de 6 dígitos.
          </p>
          
          <div id="alert-container-modal"></div>
          
          <form id="forgotPasswordForm" onsubmit="return false;">
            <div class="form-group">
              <label class="font-weight-500">Correo Electrónico</label>
              <input type="email" class="form-control form-control-lg" 
                     id="recovery_email" placeholder="tu@email.com" required
                     style="padding-left: 15px;">
            </div>
          </form>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" onclick="cerrarModal('forgotPasswordModal')">Cancelar</button>
          <button type="button" class="btn btn-primary" id="sendCodeBtn" 
                  style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
            <i class="mdi mdi-send"></i> Enviar Código
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Paso 2: Verificar Código -->
  <div class="modal fade" id="verifyCodeModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title font-weight-bold w-100 text-center">
            <i class="mdi mdi-shield-lock" style="font-size: 50px; color: #667eea;"></i>
            <br>Verificar Código
          </h5>
          <button type="button" class="close" onclick="cerrarModal('verifyCodeModal')">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body px-4 pt-2">
          <p class="text-muted text-center mb-4">
            Ingresa el código de 6 dígitos que enviamos a tu correo electrónico.
          </p>
          
          <div id="alert-container-verify"></div>
          
          <form id="verifyCodeForm" onsubmit="return false;">
            <div class="form-group">
              <label class="font-weight-500 text-center d-block">Código de Verificación</label>
              <input type="text" class="form-control form-control-lg verification-code-input" 
                     id="verification_code" placeholder="000000" maxlength="6" required
                     pattern="[0-9]{6}" inputmode="numeric">
            </div>
          </form>
          
          <!-- Contador Regresivo -->
          <div class="countdown-container">
            <div class="countdown-icon">⏱️</div>
            <div class="countdown-label">El código expira en:</div>
            <div class="countdown-timer" id="countdownTimer">15:00</div>
            <small class="text-muted">Tiempo restante para usar este código</small>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" id="backToEmailBtn">
            <i class="mdi mdi-arrow-left"></i> Atrás
          </button>
          <button type="button" class="btn btn-primary" id="verifyCodeBtn"
                  style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
            <i class="mdi mdi-check"></i> Verificar
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Paso 3: Nueva Contraseña -->
  <div class="modal fade" id="newPasswordModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title font-weight-bold w-100 text-center">
            <i class="mdi mdi-lock-reset" style="font-size: 50px; color: #28a745;"></i>
            <br>Nueva Contraseña
          </h5>
          <button type="button" class="close" onclick="cerrarModal('newPasswordModal')">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body px-4 pt-2">
          <p class="text-success text-center mb-3">
            <i class="mdi mdi-check-circle"></i> <strong>Código verificado correctamente</strong>
          </p>
          <p class="text-muted text-center mb-4">
            Ingresa tu nueva contraseña.
          </p>
          
          <div id="alert-container-password"></div>
          
          <form id="newPasswordForm" onsubmit="return false;">
            <div class="form-group">
              <label class="font-weight-500">Nueva Contraseña</label>
              <div class="position-relative">
                <input type="password" class="form-control form-control-lg" 
                       id="new_password" placeholder="Mínimo 8 caracteres" required
                       style="padding-left: 15px; padding-right: 45px;">
                <i class="mdi mdi-eye password-toggle" id="toggleNewPassword"></i>
              </div>
              <div class="password-strength mt-2" id="passwordStrength" style="height: 5px; border-radius: 3px;"></div>
              <small class="form-text text-muted" id="strengthText"></small>
            </div>
            
            <div class="form-group">
              <label class="font-weight-500">Confirmar Contraseña</label>
              <div class="position-relative">
                <input type="password" class="form-control form-control-lg" 
                       id="confirm_password" placeholder="Repite la contraseña" required
                       style="padding-left: 15px; padding-right: 45px;">
                <i class="mdi mdi-eye password-toggle" id="toggleConfirmPassword"></i>
              </div>
              <small class="form-text text-muted" id="matchText"></small>
            </div>
          </form>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" onclick="cerrarModal('newPasswordModal')">Cancelar</button>
          <button type="button" class="btn btn-success" id="changePasswordBtn">
            <i class="mdi mdi-check-circle"></i> Cambiar Contraseña
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="template/vendors/js/vendor.bundle.base.js"></script>
  <script>
    let recoveryEmail = '';
    let countdownInterval = null;
    let countdownSeconds = 900; // 15 minutos = 900 segundos
    
    function cerrarModal(modalId) {
      $('#' + modalId).modal('hide');
      limpiarModal(modalId);
    }
    
    function limpiarModal(modalId) {
      if (modalId === 'forgotPasswordModal') {
        $('#alert-container-modal').html('');
        $('#recovery_email').val('');
      } else if (modalId === 'verifyCodeModal') {
        $('#alert-container-verify').html('');
        $('#verification_code').val('');
        stopCountdown();
      } else if (modalId === 'newPasswordModal') {
        $('#alert-container-password').html('');
        $('#new_password').val('');
        $('#confirm_password').val('');
        $('#passwordStrength').css('width', '0%');
        $('#strengthText').text('');
        $('#matchText').text('');
      }
    }
    
    // Funciones del contador regresivo
    function startCountdown() {
      countdownSeconds = 900; // Reiniciar a 15 minutos
      updateCountdownDisplay();
      
      countdownInterval = setInterval(function() {
        countdownSeconds--;
        updateCountdownDisplay();
        
        if (countdownSeconds <= 0) {
          stopCountdown();
          showAlertVerify('El código ha expirado. Solicita uno nuevo.', 'danger');
          $('#verifyCodeBtn').prop('disabled', true);
          $('#verification_code').prop('disabled', true);
        }
      }, 1000);
    }
    
    function stopCountdown() {
      if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
      }
    }
    
    function updateCountdownDisplay() {
      const minutes = Math.floor(countdownSeconds / 60);
      const seconds = countdownSeconds % 60;
      const display = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
      
      const $timer = $('#countdownTimer');
      $timer.text(display);
      
      // Cambiar color según tiempo restante
      $timer.removeClass('warning danger');
      if (countdownSeconds <= 60) {
        $timer.addClass('danger'); // Rojo último minuto
      } else if (countdownSeconds <= 300) {
        $timer.addClass('warning'); // Amarillo últimos 5 minutos
      }
    }
    
    $(document).ready(function() {
      $('.modal').on('hide.bs.modal', function(e) {
        if (e.target.id === 'forgotPasswordModal' || 
            e.target.id === 'verifyCodeModal' || 
            e.target.id === 'newPasswordModal') {
          limpiarModal(e.target.id);
        }
      });
      
      $('#forgotPasswordLink').on('click', function(e) {
        e.preventDefault();
        $('#forgotPasswordModal').modal('show');
      });
      
      $('#togglePassword, #toggleNewPassword, #toggleConfirmPassword').on('click', function() {
        const target = $(this).prev('input');
        const type = target.attr('type') === 'password' ? 'text' : 'password';
        target.attr('type', type);
        $(this).toggleClass('mdi-eye mdi-eye-off');
      });
      
      // Login
      $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        const username = $('#username').val().trim();
        const password = $('#password').val();
        
        if (!username || !password) {
          showAlert('Por favor completa todos los campos', 'warning');
          return;
        }
        
        showAlertLoading('Iniciando sesión...', 'info');
        $('.auth-form-btn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Cargando...');
        
        $.ajax({
          url: 'api/login.php',
          method: 'POST',
          dataType: 'json',
          data: { username: username, password: password },
          success: function(response) {
            if(response.success) {
              showAlertLoading('¡Bienvenido! Redirigiendo...', 'success');
              setTimeout(() => window.location.replace('dashboard.php'), 800);
            } else {
              showAlert((response.message || 'Error al iniciar sesión'), 'danger');
              $('.auth-form-btn').prop('disabled', false).html('INICIAR SESIÓN');
            }
          },
          error: function() {
            showAlert('Error al conectar con el servidor', 'danger');
            $('.auth-form-btn').prop('disabled', false).html('INICIAR SESIÓN');
          }
        });
      });
      
      // PASO 1: Enviar código
      $('#sendCodeBtn').on('click', function() {
        const email = $('#recovery_email').val().trim();
        
        if (!email || !validateEmail(email)) {
          showAlertModal('Por favor ingresa un correo electrónico válido', 'warning');
          return;
        }
        
        recoveryEmail = email;
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Enviando...');
        
        $.ajax({
          url: 'api/send-recovery-code.php',
          method: 'POST',
          dataType: 'json',
          data: { email: email },
          success: function(response) {
            if(response.success) {
              showAlertModal('Código enviado exitosamente. Revisa tu correo electrónico.', 'success');
              setTimeout(() => {
                cerrarModal('forgotPasswordModal');
                $('#verifyCodeModal').modal('show');
                startCountdown(); // Iniciar contador cuando abre el modal
              }, 2000);
            } else {
              showAlertModal(response.message || 'Error al enviar el código', 'danger');
            }
            $('#sendCodeBtn').prop('disabled', false).html('<i class="mdi mdi-send"></i> Enviar Código');
          },
          error: function(xhr) {
            showAlertModal('Error de conexión. Intenta nuevamente.', 'danger');
            $('#sendCodeBtn').prop('disabled', false).html('<i class="mdi mdi-send"></i> Enviar Código');
          }
        });
      });
      
      // PASO 2: Verificar código
      $('#verifyCodeBtn').on('click', function() {
        const code = $('#verification_code').val().trim();
        
        if (!code || code.length !== 6) {
          showAlertVerify('Ingresa el código de 6 dígitos', 'warning');
          return;
        }
        
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Verificando...');
        
        $.ajax({
          url: 'api/verify-recovery-code.php',
          method: 'POST',
          dataType: 'json',
          data: { email: recoveryEmail, code: code },
          success: function(response) {
            if(response.success) {
              stopCountdown(); // Detener contador al verificar correctamente
              showAlertVerify('¡Código correcto!', 'success');
              setTimeout(() => {
                cerrarModal('verifyCodeModal');
                $('#newPasswordModal').modal('show');
              }, 1000);
            } else {
              showAlertVerify(response.message || 'Código inválido o expirado', 'danger');
            }
            $('#verifyCodeBtn').prop('disabled', false).html('<i class="mdi mdi-check"></i> Verificar');
          },
          error: function() {
            showAlertVerify('Error al verificar el código', 'danger');
            $('#verifyCodeBtn').prop('disabled', false).html('<i class="mdi mdi-check"></i> Verificar');
          }
        });
      });
      
      // PASO 3: Cambiar contraseña
      $('#changePasswordBtn').on('click', function() {
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (newPassword.length < 8) {
          showAlertPassword('La contraseña debe tener al menos 8 caracteres', 'warning');
          return;
        }
        
        if (newPassword !== confirmPassword) {
          showAlertPassword('Las contraseñas no coinciden', 'danger');
          return;
        }
        
        $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Guardando...');
        
        $.ajax({
          url: 'api/change-password-recovery.php',
          method: 'POST',
          dataType: 'json',
          data: { email: recoveryEmail, password: newPassword },
          success: function(response) {
            if(response.success) {
              showAlertPassword(response.message, 'success');
              setTimeout(() => {
                cerrarModal('newPasswordModal');
                showAlert('¡Contraseña actualizada! Ya puedes iniciar sesión.', 'success');
              }, 2000);
            } else {
              showAlertPassword(response.message, 'danger');
            }
            $('#changePasswordBtn').prop('disabled', false).html('<i class="mdi mdi-check-circle"></i> Cambiar Contraseña');
          },
          error: function() {
            showAlertPassword('Error al cambiar la contraseña', 'danger');
            $('#changePasswordBtn').prop('disabled', false).html('<i class="mdi mdi-check-circle"></i> Cambiar Contraseña');
          }
        });
      });
      
      $('#backToEmailBtn').on('click', () => {
        cerrarModal('verifyCodeModal');
        $('#forgotPasswordModal').modal('show');
      });
      
      $('#verification_code').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
      });
      
      $('#new_password').on('keyup', function() {
        const strength = checkPasswordStrength($(this).val());
        $('#passwordStrength').css({'width': strength.percent + '%', 'background-color': strength.color});
        $('#strengthText').text(strength.text).css('color', strength.color);
      });
      
      $('#confirm_password').on('keyup', function() {
        const password = $('#new_password').val();
        const confirm = $(this).val();
        if (confirm.length > 0) {
          $('#matchText').text(password === confirm ? '✓ Coinciden' : '✗ No coinciden')
                         .css('color', password === confirm ? '#28a745' : '#dc3545');
        } else {
          $('#matchText').text('');
        }
      });
      
      function checkPasswordStrength(password) {
        let strength = 0;
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 25;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
        if (/\d/.test(password)) strength += 12.5;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 12.5;
        
        if (strength <= 25) return { percent: strength, color: '#dc3545', text: 'Débil' };
        if (strength <= 50) return { percent: strength, color: '#ffc107', text: 'Regular' };
        if (strength <= 75) return { percent: strength, color: '#17a2b8', text: 'Buena' };
        return { percent: 100, color: '#28a745', text: 'Fuerte' };
      }
      
      function validateEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
      }
      
      function showAlert(message, type) {
        const icon = type === 'danger' ? '🔴' : type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️';
        $('#alert-container').html(`
          <div class="alert alert-${type} fade show" role="alert">
            ${icon} ${message}
          </div>
        `);
        setTimeout(() => $('.alert').fadeOut(), 5000);
      }
      
      function showAlertLoading(message, type) {
        $('#alert-container').html(`
          <div class="alert alert-${type} fade show" role="alert" style="display: flex; align-items: center; justify-content: center;">
            <span class="spinner-border spinner-border-sm mr-2"></span>${message}
          </div>
        `);
      }
      
      function showAlertModal(msg, type) { 
        const icon = type === 'danger' ? '🔴' : type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️';
        $('#alert-container-modal').html(`<div class="alert alert-${type} fade show">${icon} ${msg}</div>`); 
      }
      
      function showAlertVerify(msg, type) { 
        const icon = type === 'danger' ? '🔴' : type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️';
        $('#alert-container-verify').html(`<div class="alert alert-${type} fade show">${icon} ${msg}</div>`); 
      }
      
      function showAlertPassword(msg, type) { 
        const icon = type === 'danger' ? '🔴' : type === 'success' ? '✅' : type === 'warning' ? '⚠️' : 'ℹ️';
        $('#alert-container-password').html(`<div class="alert alert-${type} fade show">${icon} ${msg}</div>`); 
      }
    });
  </script>
</body>
</html>