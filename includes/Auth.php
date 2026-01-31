<?php
/**
 * CLASE DE AUTENTICACIÓN
 * Manejo de login, logout, sesiones y recuperación de contraseña
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

class Auth {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Iniciar sesión
     */
    public function login($username, $password) {
        try {
            // Buscar usuario por username o email
            $query = "SELECT u.*, r.nombre as rol_nombre, r.permisos, a.nombre as area_nombre 
                      FROM usuarios u 
                      INNER JOIN roles r ON u.rol_id = r.id 
                      LEFT JOIN areas a ON u.area_id = a.id 
                      WHERE (u.username = :username OR u.email = :username) 
                      AND u.activo = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                log_activity("Intento de login fallido - Usuario no encontrado: $username", 'WARNING');
                return [
                    'success' => false,
                    'message' => 'Usuario o contraseña incorrectos'
                ];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verificar contraseña
            if (!password_verify($password, $user['password'])) {
                log_activity("Intento de login fallido - Contraseña incorrecta: $username", 'WARNING');
                return [
                    'success' => false,
                    'message' => 'Usuario o contraseña incorrectos'
                ];
            }
            
            // Actualizar último acceso
            $this->updateLastAccess($user['id']);
            
            // Crear sesión
            $this->createSession($user);
            
            // Generar token de sesión
            $session_token = $this->createSessionToken($user['id']);
            
            log_activity("Login exitoso para usuario: {$user['username']}", 'INFO');
            
            return [
                'success' => true,
                'message' => 'Login exitoso',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'nombre_completo' => $user['nombre_completo'],
                    'email' => $user['email'],
                    'rol' => $user['rol_nombre'],
                    'area' => $user['area_nombre'],
                    'avatar' => $user['avatar']
                ],
                'token' => $session_token
            ];
            
        } catch (Exception $e) {
            log_activity("Error en login: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Error al procesar la solicitud'
            ];
        }
    }

    /**
     * Crear sesión PHP
     */
    private function createSession($user) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['nombre_completo'] = $user['nombre_completo'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['rol_id'] = $user['rol_id'];
        $_SESSION['rol_nombre'] = $user['rol_nombre'];
        $_SESSION['area_id'] = $user['area_id'];
        $_SESSION['avatar'] = $user['avatar'];
        $_SESSION['permisos'] = json_decode($user['permisos'], true);
        
        // ⭐ CRÍTICO: Resetear timestamps al iniciar sesión
        $_SESSION['last_activity'] = time();
        $_SESSION['session_created'] = time();
        
        $_SESSION['ip_address'] = get_client_ip();
        
        // Log para debug
        error_log("Nueva sesión creada - User: {$user['username']} - Timestamp: " . time());
    }

    /**
     * Crear token de sesión en BD
     */
    private function createSessionToken($user_id) {
        $token = generate_token(64);
        $expira_en = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
        $ip_address = get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $query = "INSERT INTO sesiones (usuario_id, token, ip_address, user_agent, expira_en) 
                  VALUES (:usuario_id, :token, :ip_address, :user_agent, :expira_en)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':usuario_id', $user_id);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->bindParam(':user_agent', $user_agent);
        $stmt->bindParam(':expira_en', $expira_en);
        $stmt->execute();
        
        return $token;
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        try {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            
            $username = $_SESSION['username'] ?? 'Unknown';
            
            // Eliminar token de sesión de BD si existe
            if (isset($_SESSION['session_token'])) {
                $query = "DELETE FROM sesiones WHERE token = :token";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':token', $_SESSION['session_token']);
                $stmt->execute();
            }
            
            // Destruir sesión PHP
            session_unset();
            session_destroy();
            
            log_activity("Logout exitoso para usuario: $username", 'INFO');
            
            return [
                'success' => true,
                'message' => 'Sesión cerrada correctamente'
            ];
            
        } catch (Exception $e) {
            log_activity("Error en logout: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Error al cerrar sesión'
            ];
        }
    }

    /**
     * Verificar token de sesión
     */
    public function verifyToken($token) {
        try {
            $query = "SELECT s.*, u.username, u.activo 
                      FROM sesiones s 
                      INNER JOIN usuarios u ON s.usuario_id = u.id 
                      WHERE s.token = :token 
                      AND s.expira_en > NOW() 
                      AND u.activo = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Token inválido o expirado'
                ];
            }
            
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'user_id' => $session['usuario_id']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al verificar token'
            ];
        }
    }

    /**
     * Solicitar recuperación de contraseña
     */
    public function requestPasswordReset($email) {
        try {
            // Buscar usuario por email
            $query = "SELECT id, username, nombre_completo FROM usuarios WHERE email = :email AND activo = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                // No revelar si el email existe o no (seguridad)
                return [
                    'success' => true,
                    'message' => 'Si el email existe, recibirás instrucciones para recuperar tu contraseña'
                ];
            }
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generar token
            $token = generate_token(32);
            $expira_en = date('Y-m-d H:i:s', time() + TOKEN_EXPIRATION);
            
            // Guardar token en BD
            $query = "INSERT INTO password_resets (usuario_id, token, expira_en) 
                      VALUES (:usuario_id, :token, :expira_en)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':usuario_id', $user['id']);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':expira_en', $expira_en);
            $stmt->execute();
            
            // Crear link de recuperación
            $reset_link = APP_URL . "/reset-password.php?token=" . $token;
            
            // Enviar email
            $subject = "Recuperación de contraseña - " . APP_NAME;
            $message = "
                <h2>Recuperación de contraseña</h2>
                <p>Hola {$user['nombre_completo']},</p>
                <p>Has solicitado recuperar tu contraseña. Haz clic en el siguiente enlace para crear una nueva:</p>
                <p><a href='$reset_link'>$reset_link</a></p>
                <p>Este enlace expirará en 1 hora.</p>
                <p>Si no solicitaste este cambio, ignora este mensaje.</p>
            ";
            
            send_email($email, $subject, $message);
            
            log_activity("Solicitud de recuperación de contraseña para: {$user['username']}", 'INFO');
            
            return [
                'success' => true,
                'message' => 'Si el email existe, recibirás instrucciones para recuperar tu contraseña'
            ];
            
        } catch (Exception $e) {
            log_activity("Error en recuperación de contraseña: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Error al procesar la solicitud'
            ];
        }
    }

    /**
     * Verificar token de recuperación
     */
    public function verifyResetToken($token) {
        try {
            $query = "SELECT pr.*, u.username, u.email, u.nombre_completo 
                      FROM password_resets pr 
                      INNER JOIN usuarios u ON pr.usuario_id = u.id 
                      WHERE pr.token = :token 
                      AND pr.expira_en > NOW() 
                      AND pr.usado = 0 
                      AND u.activo = 1";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'Token inválido o expirado'
                ];
            }
            
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'user' => [
                    'id' => $reset['usuario_id'],
                    'username' => $reset['username'],
                    'email' => $reset['email'],
                    'nombre_completo' => $reset['nombre_completo']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al verificar token'
            ];
        }
    }

    /**
     * Resetear contraseña
     */
    public function resetPassword($token, $new_password) {
        try {
            // Verificar token
            $verify = $this->verifyResetToken($token);
            if (!$verify['success']) {
                return $verify;
            }
            
            $user_id = $verify['user']['id'];
            
            // Validar fortaleza de contraseña
            $errors = validate_password_strength($new_password);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'La contraseña no cumple los requisitos',
                    'errors' => $errors
                ];
            }
            
            // Hashear nueva contraseña
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Actualizar contraseña
            $query = "UPDATE usuarios SET password = :password WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $password_hash);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            // Marcar token como usado
            $query = "UPDATE password_resets SET usado = 1 WHERE token = :token";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            
            log_activity("Contraseña restablecida para usuario ID: $user_id", 'INFO');
            
            return [
                'success' => true,
                'message' => 'Contraseña actualizada correctamente'
            ];
            
        } catch (Exception $e) {
            log_activity("Error al resetear contraseña: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Error al actualizar la contraseña'
            ];
        }
    }

    /**
     * Cambiar contraseña (usuario autenticado)
     */
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            // Verificar contraseña actual
            $query = "SELECT password FROM usuarios WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!password_verify($current_password, $user['password'])) {
                return [
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ];
            }
            
            // Validar nueva contraseña
            $errors = validate_password_strength($new_password);
            if (!empty($errors)) {
                return [
                    'success' => false,
                    'message' => 'La contraseña no cumple los requisitos',
                    'errors' => $errors
                ];
            }
            
            // Actualizar contraseña
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $query = "UPDATE usuarios SET password = :password WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':password', $password_hash);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            log_activity("Contraseña cambiada por usuario ID: $user_id", 'INFO');
            
            return [
                'success' => true,
                'message' => 'Contraseña actualizada correctamente'
            ];
            
        } catch (Exception $e) {
            log_activity("Error al cambiar contraseña: " . $e->getMessage(), 'ERROR');
            return [
                'success' => false,
                'message' => 'Error al cambiar la contraseña'
            ];
        }
    }

    /**
     * Actualizar último acceso
     */
    private function updateLastAccess($user_id) {
        $query = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $user_id);
        $stmt->execute();
    }

    /**
     * Limpiar sesiones expiradas
     */
    public function cleanExpiredSessions() {
        try {
            $query = "DELETE FROM sesiones WHERE expira_en < NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $deleted = $stmt->rowCount();
            log_activity("Limpieza de sesiones expiradas: $deleted sesiones eliminadas", 'INFO');
            
        } catch (Exception $e) {
            log_activity("Error al limpiar sesiones: " . $e->getMessage(), 'ERROR');
        }
    }

    /**
     * Limpiar tokens de recuperación expirados
     */
    public function cleanExpiredTokens() {
        try {
            $query = "DELETE FROM password_resets WHERE expira_en < NOW() OR usado = 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $deleted = $stmt->rowCount();
            log_activity("Limpieza de tokens expirados: $deleted tokens eliminados", 'INFO');
            
        } catch (Exception $e) {
            log_activity("Error al limpiar tokens: " . $e->getMessage(), 'ERROR');
        }
    }
}

?>