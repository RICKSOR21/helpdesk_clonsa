<?php
// reset-password.php
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: login.php');
    exit;
}

// Verificar token
require_once 'config/config.php';

try {
    $db = getDBConnection();
    
    $query = "SELECT id, username, email, nombre_completo FROM usuarios 
              WHERE reset_token = :token 
              AND reset_token_expires > NOW() 
              AND activo = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $token_error = true;
    }
} catch(PDOException $e) {
    die("Error de conexión");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Restablecer Contraseña - Helpdesk Clonsa</title>
  <link rel="stylesheet" href="template/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="template/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="template/css/vertical-layout-light/style.css">
  <style>
    .auth-form-light {
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
    }
    .password-toggle {
      position: absolute;
      right: 15px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      color: #6c757d;
      z-index: 10;
    }
    .password-toggle:hover {
      color: #4B49AC;
    }
    .form-group {
      position: relative;
    }
    .password-strength {
      height: 5px;
      border-radius: 3px;
      transition: all 0.3s;
      margin-top: 5px;
    }
  </style>
</head>
<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center auth px-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="row w-100 mx-0">
          <div class="col-lg-5 col-md-7 col-sm-9 mx-auto">
            <div class="auth-form-light text-center py-5 px-4 px-sm-5">
              
              <?php if (isset($token_error)): ?>
                <!-- Token inválido o expirado -->
                <div class="text-center mb-4">
                  <i class="mdi mdi-alert-circle-outline" style="font-size: 80px; color: #dc3545;"></i>
                </div>
                <h4 class="font-weight-bold text-danger mb-3">¡Token Inválido o Expirado!</h4>
                <p class="text-muted mb-4">
                  El enlace de recuperación no es válido o ha expirado. Por favor solicita uno nuevo.
                </p>
                <a href="login.php" class="btn btn-primary btn-lg px-5">
                  <i class="mdi mdi-arrow-left"></i> Volver al Login
                </a>
                
              <?php else: ?>
                <!-- Formulario válido -->
                <div class="text-center mb-4">
                  <i class="mdi mdi-lock-reset" style="font-size: 60px; color: #4B49AC;"></i>
                </div>
                <h4 class="font-weight-bold mb-2">Restablecer Contraseña</h4>
                <p class="text-muted mb-4">
                  Hola <strong><?php echo htmlspecialchars($user['nombre_completo']); ?></strong><br>
                  Ingresa tu nueva contraseña
                </p>
                
                <div id="alert-container"></div>
                
                <form id="resetPasswordForm" class="text-left">
                  <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
                  
                  <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" class="form-control form-control-lg" 
                           id="new_password" placeholder="Mínimo 8 caracteres" required>
                    <i class="mdi mdi-eye password-toggle" id="togglePassword1"></i>
                    <div class="password-strength" id="passwordStrength"></div>
                    <small class="form-text text-muted" id="strengthText"></small>
                  </div>
                  
                  <div class="form-group">
                    <label>Confirmar Contraseña</label>
                    <input type="password" class="form-control form-control-lg" 
                           id="confirm_password" placeholder="Repite la contraseña" required>
                    <i class="mdi mdi-eye password-toggle" id="togglePassword2"></i>
                    <small class="form-text text-muted" id="matchText"></small>
                  </div>
                  
                  <div class="mt-4 text-center">
                    <button type="submit" class="btn btn-primary btn-lg px-5">
                      <i class="mdi mdi-check"></i> Cambiar Contraseña
                    </button>
                  </div>
                </form>
              <?php endif; ?>
              
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="template/vendors/js/vendor.bundle.base.js"></script>
  <script>
    $(document).ready(function() {
      // Toggle passwords
      $('#togglePassword1').on('click', function() {
        const field = $('#new_password');
        const type = field.attr('type') === 'password' ? 'text' : 'password';
        field.attr('type', type);
        $(this).toggleClass('mdi-eye mdi-eye-off');
      });
      
      $('#togglePassword2').on('click', function() {
        const field = $('#confirm_password');
        const type = field.attr('type') === 'password' ? 'text' : 'password';
        field.attr('type', type);
        $(this).toggleClass('mdi-eye mdi-eye-off');
      });
      
      // Verificar fortaleza de contraseña
      $('#new_password').on('keyup', function() {
        const password = $(this).val();
        const strength = checkPasswordStrength(password);
        
        $('#passwordStrength').css({
          'width': strength.percent + '%',
          'background-color': strength.color
        });
        $('#strengthText').text(strength.text).css('color', strength.color);
      });
      
      // Verificar coincidencia
      $('#confirm_password').on('keyup', function() {
        const password = $('#new_password').val();
        const confirm = $(this).val();
        
        if (confirm.length > 0) {
          if (password === confirm) {
            $('#matchText').text('✓ Las contraseñas coinciden').css('color', '#28a745');
          } else {
            $('#matchText').text('✗ Las contraseñas no coinciden').css('color', '#dc3545');
          }
        } else {
          $('#matchText').text('');
        }
      });
      
      // Submit form
      $('#resetPasswordForm').on('submit', function(e) {
        e.preventDefault();
        
        const token = $('#token').val();
        const newPassword = $('#new_password').val();
        const confirmPassword = $('#confirm_password').val();
        
        if (newPassword.length < 8) {
          showAlert('La contraseña debe tener al menos 8 caracteres', 'warning');
          return;
        }
        
        if (newPassword !== confirmPassword) {
          showAlert('Las contraseñas no coinciden', 'danger');
          return;
        }
        
        $('button[type="submit"]').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Procesando...');
        
        $.ajax({
          url: 'api/reset-password.php',
          method: 'POST',
          dataType: 'json',
          data: {
            token: token,
            password: newPassword
          },
          success: function(response) {
            if (response.success) {
              showAlert('<i class="mdi mdi-check-circle"></i> ' + response.message, 'success');
              setTimeout(function() {
                window.location.href = 'login.php';
              }, 2000);
            } else {
              showAlert('<i class="mdi mdi-alert"></i> ' + response.message, 'danger');
              $('button[type="submit"]').prop('disabled', false).html('<i class="mdi mdi-check"></i> Cambiar Contraseña');
            }
          },
          error: function() {
            showAlert('Error al procesar la solicitud', 'danger');
            $('button[type="submit"]').prop('disabled', false).html('<i class="mdi mdi-check"></i> Cambiar Contraseña');
          }
        });
      });
      
      function checkPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 25;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 25;
        if (/\d/.test(password)) strength += 12.5;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 12.5;
        
        if (strength <= 25) {
          return { percent: strength, color: '#dc3545', text: 'Débil' };
        } else if (strength <= 50) {
          return { percent: strength, color: '#ffc107', text: 'Regular' };
        } else if (strength <= 75) {
          return { percent: strength, color: '#17a2b8', text: 'Buena' };
        } else {
          return { percent: 100, color: '#28a745', text: 'Fuerte' };
        }
      }
      
      function showAlert(message, type) {
        const alertClass = type === 'danger' ? 'alert-danger' : 
                          type === 'success' ? 'alert-success' : 
                          type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const alert = `
          <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
              <span>&times;</span>
            </button>
          </div>
        `;
        $('#alert-container').html(alert);
      }
    });
  </script>
</body>
</html>