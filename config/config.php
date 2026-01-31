<?php
/**
 * Archivo de Configuración - Helpdesk Clonsa Ingeniería
 * config/config.php
 * 
 * IMPORTANTE: Este archivo contiene información sensible.
 * NO subir a repositorios públicos (agregar a .gitignore)
 */

// ═══════════════════════════════════════════════════════════════════
// DETECCIÓN AUTOMÁTICA DE ENTORNO
// ═══════════════════════════════════════════════════════════════════

define('IS_DEVELOPMENT', in_array($_SERVER['SERVER_NAME'] ?? 'localhost', [
    'localhost',
    '127.0.0.1',
    'helpdesk.test'
]));

// ═══════════════════════════════════════════════════════════════════
// CONFIGURACIÓN DE BASE DE DATOS
// ═══════════════════════════════════════════════════════════════════

if (IS_DEVELOPMENT) {
    // DESARROLLO (Laragon local)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'helpdesk_clonsa');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // PRODUCCIÓN (Hosting)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'usuario_clonsa_helpdesk');  // ⚠️ Cambiar con prefijo de cPanel
    define('DB_USER', 'usuario_clonsa_user');      // ⚠️ Cambiar con prefijo de cPanel
    define('DB_PASS', 'TU_PASSWORD_SEGURA_AQUI');  // ⚠️ CAMBIAR ESTO
}

define('DB_CHARSET', 'utf8mb4');

// ═══════════════════════════════════════════════════════════════════
// CONFIGURACIÓN DE LA APLICACIÓN
// ═══════════════════════════════════════════════════════════════════

define('APP_NAME', 'Helpdesk Clonsa');
define('APP_VERSION', '1.0.0');
define('APP_URL', IS_DEVELOPMENT ? 'http://localhost/helpdesk' : 'https://helpdesk.clonsa.pe');

// ═══════════════════════════════════════════════════════════════════
// CONFIGURACIÓN DE EMAIL (SMTP)
// ═══════════════════════════════════════════════════════════════════

define('SMTP_ENABLED', !IS_DEVELOPMENT);  // Solo enviar emails en producción

if (IS_DEVELOPMENT) {
    // En desarrollo: No enviar emails reales (solo logs)
    define('SMTP_HOST', 'smtp.gmail.com');
    define('SMTP_PORT', 587);
    define('SMTP_SECURE', 'tls');
    define('SMTP_USER', 'helpdesk@clonsa.pe');
    define('SMTP_PASS', '');
} else {
    // PRODUCCIÓN: Configurar según tu proveedor
    
    // OPCIÓN 1: Email del Hosting (Recomendado) ✅
    define('SMTP_HOST', 'mail.clonsa.pe');      // O mail.tudominio.com
    define('SMTP_PORT', 465);
    define('SMTP_SECURE', 'ssl');
    define('SMTP_USER', 'helpdesk@clonsa.pe');  // ⚠️ Crear este email en cPanel
    define('SMTP_PASS', 'password_del_email');  // ⚠️ CAMBIAR ESTO
    
    // OPCIÓN 2: Gmail (Descomentar si usas Gmail)
    // define('SMTP_HOST', 'smtp.gmail.com');
    // define('SMTP_PORT', 587);
    // define('SMTP_SECURE', 'tls');
    // define('SMTP_USER', 'tu-email@gmail.com');
    // define('SMTP_PASS', 'contraseña-app-16-digitos');  // Contraseña de aplicación
    
    // OPCIÓN 3: SendGrid (Descomentar si usas SendGrid)
    // define('SMTP_HOST', 'smtp.sendgrid.net');
    // define('SMTP_PORT', 587);
    // define('SMTP_SECURE', 'tls');
    // define('SMTP_USER', 'apikey');
    // define('SMTP_PASS', 'TU_API_KEY_DE_SENDGRID');
}

// Email remitente
define('SMTP_FROM', 'helpdesk@clonsa.pe');
define('SMTP_FROM_NAME', 'Helpdesk Clonsa Ingeniería');

// Alias para compatibilidad con código existente
define('EMAIL_FROM', SMTP_FROM);
define('EMAIL_FROM_NAME', SMTP_FROM_NAME);

// ═══════════════════════════════════════════════════════════════════
// CONFIGURACIÓN DE SESIONES Y TIMEOUT
// ═══════════════════════════════════════════════════════════════════

// ⏱️ TIMEOUT DE SESIÓN - Configurable aquí
// Tiempo de inactividad antes de mostrar popup (en segundos)
define('SESSION_TIMEOUT', 60);  // 1 hora = 3600 segundos
                                   // 1800 = 30 minutos
                                   // 7200 = 2 horas

// Tiempo de espera del popup antes de cerrar sesión automáticamente (en segundos)
define('SESSION_POPUP_TIMEOUT', 30);  // 1 minuto = 60 segundos
                                       // 30 = 30 segundos
                                       // 120 = 2 minutos

// Configuración de sesión PHP
define('SESSION_LIFETIME', SESSION_TIMEOUT);  // Usar el mismo valor que SESSION_TIMEOUT
define('SESSION_NAME', 'HELPDESK_SESSION');

//ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
//session_set_cookie_params(SESSION_LIFETIME);

// ═══════════════════════════════════════════════════════════════════
// CONFIGURACIÓN DE SEGURIDAD
// ═══════════════════════════════════════════════════════════════════

define('PASSWORD_SALT', 'ClonsaHelpdesk2026!');
define('TOKEN_EXPIRATION', 3600);  // 1 hora para recuperación de contraseña
define('TOKEN_EXPIRATION_MINUTES', 15);  // Para mensajes de usuario (15 minutos)
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900);  // 15 minutos
define('LOCKOUT_TIME_MINUTES', 30);

// ═══════════════════════════════════════════════════════════════════
// CONFIGURACIÓN DE ARCHIVOS
// ═══════════════════════════════════════════════════════════════════

define('ROOT_PATH', dirname(__DIR__));
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('LOGS_PATH', ROOT_PATH . '/logs');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Alias para compatibilidad
define('UPLOAD_DIR', UPLOADS_PATH . '/');

define('MAX_FILE_SIZE', 10485760);  // 10MB en bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar']);

// ═══════════════════════════════════════════════════════════════════
// CONFIGURACIÓN DE ZONA HORARIA
// ═══════════════════════════════════════════════════════════════════

date_default_timezone_set('America/Lima');

// ═══════════════════════════════════════════════════════════════════
// CONFIGURACIÓN DE ERRORES
// ═══════════════════════════════════════════════════════════════════

if (IS_DEVELOPMENT) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    define('DEBUG_MODE', true);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
    define('DEBUG_MODE', false);
}

// ═══════════════════════════════════════════════════════════════════
// HEADERS DE SEGURIDAD
// ═══════════════════════════════════════════════════════════════════

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Solo HTTPS en producción
if (!IS_DEVELOPMENT && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ═══════════════════════════════════════════════════════════════════
// FUNCIÓN DE CONEXIÓN A BASE DE DATOS
// ═══════════════════════════════════════════════════════════════════

/**
 * Obtiene una conexión PDO a la base de datos
 * @return PDO
 * @throws PDOException
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        return $pdo;
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            die("Error de conexión: " . $e->getMessage());
        } else {
            error_log("Database connection error: " . $e->getMessage());
            die("Error de conexión a la base de datos. Contacta al administrador.");
        }
    }
}

/**
 * Obtiene una conexión PDO (alias para compatibilidad con código existente)
 * @return PDO
 */
function getConnection() {
    return getDBConnection();
}

// ═══════════════════════════════════════════════════════════════════
// VERIFICACIÓN Y CREACIÓN DE CARPETAS NECESARIAS
// ═══════════════════════════════════════════════════════════════════

$required_dirs = [LOGS_PATH, UPLOADS_PATH];
foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ═══════════════════════════════════════════════════════════════════
// FUNCIONES AUXILIARES
// ═══════════════════════════════════════════════════════════════════

/**
 * Verifica si estamos en modo desarrollo
 * @return bool
 */
function isDevelopment() {
    return IS_DEVELOPMENT;
}

/**
 * Verifica si estamos en modo producción
 * @return bool
 */
function isProduction() {
    return !IS_DEVELOPMENT;
}

/**
 * Obtiene la URL base de la aplicación
 * @return string
 */
function getBaseUrl() {
    return APP_URL;
}

/**
 * Escribe un log en el archivo especificado
 * @param string $filename Nombre del archivo de log
 * @param string $message Mensaje a escribir
 */
function writeLog($filename, $message) {
    $log_file = LOGS_PATH . '/' . $filename;
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}\n";
    file_put_contents($log_message, $log_message, FILE_APPEND);
}

// ═══════════════════════════════════════════════════════════════════
// ALERTAS DE CONFIGURACIÓN (Solo en desarrollo)
// ═══════════════════════════════════════════════════════════════════

if (IS_DEVELOPMENT && DEBUG_MODE) {
    // Todo OK en desarrollo
} else if (!IS_DEVELOPMENT) {
    // Verificar que se hayan configurado las credenciales en producción
    if (DB_PASS === 'TU_PASSWORD_SEGURA_AQUI') {
        error_log('⚠️ WARNING: Database password not configured in production!');
    }
    
    if (SMTP_PASS === 'password_del_email' || SMTP_PASS === '') {
        error_log('⚠️ WARNING: SMTP password not configured in production!');
    }
}

// ═══════════════════════════════════════════════════════════════════
// INFORMACIÓN DE DEBUG (Solo visible en desarrollo)
// ═══════════════════════════════════════════════════════════════════

if (IS_DEVELOPMENT && DEBUG_MODE && php_sapi_name() !== 'cli') {
    // Comentar esto en producción o cuando no necesites el debug
    /*
    echo "<!-- DEBUG INFO:\n";
    echo "Environment: " . (IS_DEVELOPMENT ? 'DEVELOPMENT' : 'PRODUCTION') . "\n";
    echo "Database: " . DB_NAME . "\n";
    echo "SMTP Enabled: " . (SMTP_ENABLED ? 'Yes' : 'No') . "\n";
    echo "App URL: " . APP_URL . "\n";
    echo "Session Timeout: " . SESSION_TIMEOUT . " seconds (" . (SESSION_TIMEOUT/60) . " minutes)\n";
    echo "Popup Timeout: " . SESSION_POPUP_TIMEOUT . " seconds\n";
    echo "-->\n";
    */
}

?>