<?php
// includes/functions.php - Funciones auxiliares del sistema

/**
 * Sanitizar entrada de texto
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validar email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generar token seguro
 */
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Formatear fecha en español
 */
function format_date_es($date) {
    $timestamp = strtotime($date);
    $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
              'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $mes = $meses[date('n', $timestamp) - 1];
    return date('d', $timestamp) . ' de ' . $mes . ' de ' . date('Y', $timestamp);
}

/**
 * Calcular tiempo transcurrido
 */
function time_elapsed($datetime) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->d == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) {
                return 'Ahora';
            }
            return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
        }
        return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
    } elseif ($diff->d < 7) {
        return $diff->d . ' día' . ($diff->d > 1 ? 's' : '');
    } elseif ($diff->d < 30) {
        $weeks = floor($diff->d / 7);
        return $weeks . ' semana' . ($weeks > 1 ? 's' : '');
    } elseif ($diff->m < 12) {
        return $diff->m . ' mes' . ($diff->m > 1 ? 'es' : '');
    } else {
        return $diff->y . ' año' . ($diff->y > 1 ? 's' : '');
    }
}

/**
 * Validar sesión activa
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Verificar permisos de rol
 */
function has_permission($required_role) {
    if (!isset($_SESSION['user_rol'])) {
        return false;
    }
    
    $roles_hierarchy = [
        'Admin' => 5,
        'Jefe Soporte Técnico' => 4,
        'Jefe TI' => 4,
        'Jefe Administrativo' => 4,
        'Usuario' => 1
    ];
    
    $user_level = $roles_hierarchy[$_SESSION['user_rol']] ?? 0;
    $required_level = $roles_hierarchy[$required_role] ?? 0;
    
    return $user_level >= $required_level;
}

/**
 * Registrar actividad en log
 */
function log_activity($message, $level = 'INFO') {
    $log_file = __DIR__ . '/../logs/system.log';
    $log_dir = dirname($log_file);
    
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'] ?? 'GUEST';
    $log_entry = "[$timestamp] [$level] [User:$user_id] $message\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Generar código único para tickets
 */
function generate_ticket_code() {
    $year = date('Y');
    $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    return "TKT-$year-$random";
}

/**
 * Obtener badge de prioridad
 */
function get_priority_badge($priority_name, $color) {
    return "<span class='badge' style='background-color: $color; color: white;'>$priority_name</span>";
}

/**
 * Obtener badge de estado
 */
function get_status_badge($status_name, $color) {
    return "<span class='badge' style='background-color: $color; color: white;'>$status_name</span>";
}

/**
 * Convertir bytes a formato legible
 */
function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Validar tipo de archivo permitido
 */
function validate_file_type($filename) {
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip'];
    $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($file_extension, $allowed_extensions);
}

/**
 * Generar nombre de archivo único
 */
function generate_unique_filename($original_filename) {
    $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
    $filename = pathinfo($original_filename, PATHINFO_FILENAME);
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);
    return $filename . '_' . time() . '_' . uniqid() . '.' . $extension;
}

/**
 * Escapar HTML
 */
function escape_html($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Truncar texto
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}
?>