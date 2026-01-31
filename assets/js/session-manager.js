/**
 * Session Manager - Control de Timeout de Sesión
 * Archivo: assets/js/session-manager.js
 */

console.log('🚀 session-manager.js cargado');

document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM cargado');
    console.log('Body classes:', document.body.className);
    console.log('Tiene authenticated:', document.body.classList.contains('authenticated'));
    
    if (!document.body.classList.contains('authenticated')) {
        console.log('⚠️ No autenticado - Session Manager no se inicia');
        return;
    }
    
    console.log('✅ Usuario autenticado');
    
    if (typeof SESSION_TIMEOUT === 'undefined' || typeof SESSION_POPUP_TIMEOUT === 'undefined') {
        console.error('❌ SESSION_TIMEOUT o SESSION_POPUP_TIMEOUT no definidos');
        return;
    }
    
    console.log('⚙️ Configuración:');
    console.log('   SESSION_TIMEOUT:', SESSION_TIMEOUT, 'segundos');
    console.log('   SESSION_POPUP_TIMEOUT:', SESSION_POPUP_TIMEOUT, 'segundos');
    
    if (SESSION_TIMEOUT <= SESSION_POPUP_TIMEOUT) {
        console.error('❌ SESSION_TIMEOUT debe ser MAYOR que SESSION_POPUP_TIMEOUT');
        return;
    }
    
    console.log('✅ Configuración válida');
    
    new SessionManager({
        sessionTimeout: SESSION_TIMEOUT,
        popupTimeout: SESSION_POPUP_TIMEOUT
    });
});

class SessionManager {
    constructor(options = {}) {
        console.log('🔧 SessionManager constructor');
        
        this.sessionTimeout = (options.sessionTimeout || 3600) * 1000;
        this.popupTimeout = (options.popupTimeout || 60) * 1000;
        this.checkInterval = 5000;
        
        this.hasJQuery = typeof jQuery !== 'undefined' && typeof jQuery.fn.modal !== 'undefined';
        console.log('jQuery disponible:', this.hasJQuery ? 'SÍ ✅' : 'NO');
        
        this.lastActivity = Date.now();
        this.popupTimer = null;
        this.checkTimer = null;
        this.popupCountdown = null;
        this.popupShown = false;
        
        this.init();
    }
    
    init() {
        this.lastActivity = Date.now();
        console.log('✅ Timer iniciado desde 0');
        
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        events.forEach(event => {
            document.addEventListener(event, () => this.resetActivity(), true);
        });
        
        this.createPopupHTML();
        this.startChecking();
        
        console.log('✅ Session Manager inicializado');
        console.log('🎉 Sistema listo');
    }
    
    resetActivity() {
        if (!this.popupShown) {
            this.lastActivity = Date.now();
        }
    }
    
    startChecking() {
        console.log('🔍 Iniciando verificación periódica');
        this.checkTimer = setInterval(() => {
            this.checkSession();
        }, this.checkInterval);
    }
    
    checkSession() {
        const now = Date.now();
        const inactiveTime = now - this.lastActivity;
        const inactiveSeconds = Math.floor(inactiveTime / 1000);
        const totalSeconds = Math.floor(this.sessionTimeout / 1000);
        
        console.log('⏱️ Inactividad:', inactiveSeconds + 's de ' + totalSeconds + 's');
        
        if (inactiveTime >= this.sessionTimeout) {
            console.log('⏰ SESIÓN EXPIRADA');
            this.logout();
            return;
        }
        
        const timeUntilPopup = this.sessionTimeout - this.popupTimeout;
        if (inactiveTime >= timeUntilPopup && !this.popupShown) {
            console.log('⏰ MOSTRANDO POPUP');
            this.showPopup();
        }
    }
    
    showPopup() {
        this.popupShown = true;
        const modal = document.getElementById('sessionTimeoutModal');
        
        if (!modal) {
            console.error('❌ Modal no encontrado');
            return;
        }
        
        console.log('🎭 Mostrando modal');
        
        if (this.hasJQuery) {
            $('#sessionTimeoutModal').modal({
                backdrop: 'static',
                keyboard: false
            });
            $('#sessionTimeoutModal').modal('show');
        } else {
            const backdrop = document.getElementById('sessionTimeoutBackdrop');
            if (backdrop) {
                backdrop.style.display = 'block';
                setTimeout(() => backdrop.classList.add('show'), 10);
            }
            modal.style.display = 'block';
            setTimeout(() => modal.classList.add('show'), 10);
            document.body.classList.add('modal-open');
        }
        
        this.startCountdown();
    }
    
    hidePopup() {
        console.log('🔒 Ocultando popup');
        const modal = document.getElementById('sessionTimeoutModal');
        const backdrop = document.getElementById('sessionTimeoutBackdrop');
        
        if (this.hasJQuery) {
            $('#sessionTimeoutModal').modal('hide');
        } else {
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.style.display = 'none', 150);
            }
            if (backdrop) {
                backdrop.classList.remove('show');
                setTimeout(() => backdrop.style.display = 'none', 150);
            }
            document.body.classList.remove('modal-open');
        }
        
        this.popupShown = false;
        
        if (this.popupCountdown) {
            clearInterval(this.popupCountdown);
            this.popupCountdown = null;
        }
    }
    
    startCountdown() {
        const countdownElement = document.getElementById('sessionCountdown');
        let remainingSeconds = Math.floor(this.popupTimeout / 1000);
        
        console.log('⏲️ Countdown iniciado:', remainingSeconds, 'segundos');
        
        const updateCountdown = () => {
            if (countdownElement) {
                countdownElement.textContent = remainingSeconds;
                
                if (remainingSeconds <= 10) {
                    countdownElement.style.color = '#dc3545';
                    countdownElement.classList.add('pulse');
                } else if (remainingSeconds <= 30) {
                    countdownElement.style.color = '#fd7e14';
                } else {
                    countdownElement.style.color = '#ffc107';
                }
            }
            
            remainingSeconds--;
            
            if (remainingSeconds < 0) {
                console.log('⏰ Countdown terminado');
                clearInterval(this.popupCountdown);
                this.logout();
            }
        };
        
        updateCountdown();
        this.popupCountdown = setInterval(updateCountdown, 1000);
    }
    
    keepSession() {
        console.log('🔄 Manteniendo sesión');
        
        fetch('api/keep-session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            console.log('✅ Respuesta:', data);
            
            if (data.success) {
                this.lastActivity = Date.now();
                this.hidePopup();
                console.log('✅ Sesión renovada');
            } else {
                console.error('❌ Error:', data.message);
                this.logout();
            }
        })
        .catch(error => {
            console.error('❌ Error de red:', error);
            this.logout();
        });
    }
    
    logout() {
        console.log('🚪 Cerrando sesión');
        
        if (this.checkTimer) clearInterval(this.checkTimer);
        if (this.popupCountdown) clearInterval(this.popupCountdown);
        
        window.location.href = 'api/logout.php';
    }
    
    createPopupHTML() {
        const modalHTML = `
<div class="modal fade" id="sessionTimeoutModal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
      <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0; border: none;">
        <h5 class="modal-title" style="font-weight: 600;">
          <i class="mdi mdi-clock-alert" style="font-size: 1.5rem; margin-right: 10px;"></i>
          Sesión por Expirar
        </h5>
      </div>
      <div class="modal-body text-center" style="padding: 2rem;">
        <div class="mb-4">
          <i class="mdi mdi-timer-sand" style="font-size: 4rem; color: #ffc107;"></i>
        </div>
        <h4 style="color: #2c3e50; margin-bottom: 1rem;">Tu sesión está a punto de expirar</h4>
        <p style="color: #7f8c8d; font-size: 1.1rem;">
          Por seguridad, tu sesión se cerrará en:
        </p>
        <div style="margin: 2rem 0;">
          <span id="sessionCountdown" style="font-size: 3rem; font-weight: bold; color: #ffc107;">60</span>
          <p style="color: #95a5a6; margin-top: 0.5rem;">segundos</p>
        </div>
        <p style="color: #7f8c8d; font-size: 0.95rem;">
          ¿Deseas continuar trabajando?
        </p>
      </div>
      <div class="modal-footer" style="border: none; padding: 0 2rem 2rem; justify-content: center; gap: 1rem;">
        <button type="button" class="btn btn-lg" id="btnKeepSession" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 30px; border-radius: 25px; font-weight: 600; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">
          <i class="mdi mdi-check-circle"></i> Mantener Sesión
        </button>
        <button type="button" class="btn btn-outline-secondary btn-lg" id="btnLogout" style="padding: 12px 30px; border-radius: 25px; font-weight: 600;">
          <i class="mdi mdi-logout"></i> Cerrar Sesión
        </button>
      </div>
    </div>
  </div>
</div>

<div id="sessionTimeoutBackdrop" class="modal-backdrop fade" style="display: none;"></div>

<style>
@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}
.pulse {
  animation: pulse 1s infinite;
}
#btnKeepSession:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}
#sessionTimeoutModal {
  z-index: 9999;
}
#sessionTimeoutBackdrop {
  z-index: 9998;
}
</style>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        const btnKeep = document.getElementById('btnKeepSession');
        const btnLogout = document.getElementById('btnLogout');
        
        if (btnKeep) {
            btnKeep.addEventListener('click', () => this.keepSession());
        }
        
        if (btnLogout) {
            btnLogout.addEventListener('click', () => this.logout());
        }
        
        console.log('✅ HTML del modal creado');
    }
}

console.log('📦 Clase SessionManager lista');