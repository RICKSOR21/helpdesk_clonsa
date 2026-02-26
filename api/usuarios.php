<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../config/session.php';
session_start();

require_once '../config/config.php';
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$userRol = $_SESSION['user_rol'] ?? 'Usuario';
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

$response = ['success' => false, 'message' => '', 'data' => []];

function logCorreo($mensaje) {
    $logFile = dirname(__DIR__) . '/logs/email_usuarios.log';
    @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $mensaje . PHP_EOL, FILE_APPEND);
}

function enviarCorreoNuevoUsuario($toEmail, $nombreCompleto, $username, $passwordPlano) {
    $subject = 'Tu cuenta fue creada - ' . (defined('APP_NAME') ? APP_NAME : 'Helpdesk');
    $appName = defined('APP_NAME') ? APP_NAME : 'Helpdesk';
    $appUrl = defined('APP_URL') ? APP_URL : '';

    $html = '
    <div style="font-family:Arial,sans-serif;max-width:620px;margin:0 auto;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;">
      <div style="background:#1f3bb3;color:#fff;padding:14px 18px;">
        <h2 style="margin:0;font-size:18px;">Cuenta creada</h2>
      </div>
      <div style="padding:18px;color:#111827;font-size:14px;line-height:1.5;">
        <p>Hola <strong>' . htmlspecialchars($nombreCompleto, ENT_QUOTES, 'UTF-8') . '</strong>,</p>
        <p>Se ha creado tu acceso al sistema <strong>' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') . '</strong>.</p>
        <table style="border-collapse:collapse;width:100%;margin:12px 0;">
          <tr><td style="padding:8px;border:1px solid #e5e7eb;background:#f9fafb;"><strong>Usuario</strong></td><td style="padding:8px;border:1px solid #e5e7eb;">' . htmlspecialchars($username, ENT_QUOTES, 'UTF-8') . '</td></tr>
          <tr><td style="padding:8px;border:1px solid #e5e7eb;background:#f9fafb;"><strong>Clave temporal</strong></td><td style="padding:8px;border:1px solid #e5e7eb;">' . htmlspecialchars($passwordPlano, ENT_QUOTES, 'UTF-8') . '</td></tr>
        </table>
        <p>Ingresa al sistema y cambia tu clave al primer inicio.</p>
        ' . ($appUrl !== '' ? '<p><a href="' . htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') . '" style="display:inline-block;background:#1f3bb3;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;">Ingresar al sistema</a></p>' : '') . '
      </div>
    </div>';

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $fromEmail = defined('SMTP_FROM') ? SMTP_FROM : 'no-reply@localhost';
    $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Helpdesk';
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";

    $ok = @mail($toEmail, $subject, $html, $headers);
    return $ok;
}

function soloAdmin($rol) {
    return $rol === 'Administrador' || $rol === 'Admin';
}

function adminOJefe($rol) {
    return $rol === 'Administrador' || $rol === 'Admin' || $rol === 'Jefe';
}

function esRolAdministradorNombre($rolNombre) {
    return $rolNombre === 'Administrador' || $rolNombre === 'Admin';
}

try {
    switch ($action) {
        case 'por_departamento':
            $departamentoId = (int)($_GET['departamento_id'] ?? 0);
            $actividadId    = (int)($_GET['actividad_id']    ?? 0);
            $context        = $_GET['context'] ?? '';

            if ($departamentoId <= 0 && $actividadId <= 0) {
                $response['message'] = 'Departamento o actividad requerido';
                break;
            }

            // Usuario normal: solo puede asignarse a si mismo (excepto en transferencias)
            if ($userRol === 'Usuario' && $context !== 'transfer') {
                $stmt = $db->prepare("
                    SELECT u.id, u.nombre_completo, u.email, d.nombre as departamento_nombre
                    FROM usuarios u
                    INNER JOIN departamentos d ON d.id = u.departamento_id
                    WHERE u.id = :user_id AND u.activo = 1
                ");
                $stmt->execute([':user_id' => $currentUserId]);
            } elseif ($actividadId > 0) {
                // Traer todos los usuarios de TODOS los departamentos vinculados a la actividad
                $stmt = $db->prepare("
                    SELECT DISTINCT u.id, u.nombre_completo, u.email, d.nombre as departamento_nombre
                    FROM usuarios u
                    INNER JOIN departamentos d ON d.id = u.departamento_id
                    WHERE u.activo = 1
                      AND u.departamento_id IN (
                          SELECT departamento_id FROM actividades_departamentos WHERE actividad_id = :actividad_id
                      )
                    ORDER BY d.nombre, u.nombre_completo
                ");
                $stmt->execute([':actividad_id' => $actividadId]);
            } else {
                $stmt = $db->prepare("
                    SELECT u.id, u.nombre_completo, u.email, d.nombre as departamento_nombre
                    FROM usuarios u
                    INNER JOIN departamentos d ON d.id = u.departamento_id
                    WHERE u.departamento_id = :departamento_id
                      AND u.activo = 1
                    ORDER BY u.nombre_completo
                ");
                $stmt->execute([':departamento_id' => $departamentoId]);
            }

            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            break;

        case 'listar':
            if (!adminOJefe($userRol)) {
                $response['message'] = 'Sin permisos';
                break;
            }

            $esAdminGeneral = ($userRol === 'Administrador' || $userRol === 'Admin');
            $deptoSesion    = (int)($_SESSION['departamento_id'] ?? 0);

            if ($esAdminGeneral) {
                // Admin ve todos los usuarios
                $stmt = $db->query("
                    SELECT u.id, u.username, u.email, u.nombre_completo, u.telefono,
                           u.rol_id, r.nombre AS rol_nombre,
                           u.departamento_id, d.nombre AS departamento_nombre,
                           u.activo, u.ultimo_acceso, u.created_at
                    FROM usuarios u
                    INNER JOIN roles r ON r.id = u.rol_id
                    LEFT JOIN departamentos d ON d.id = u.departamento_id
                    ORDER BY u.nombre_completo
                ");
            } else {
                // Jefe: solo usuarios de su departamento
                $stmt = $db->prepare("
                    SELECT u.id, u.username, u.email, u.nombre_completo, u.telefono,
                           u.rol_id, r.nombre AS rol_nombre,
                           u.departamento_id, d.nombre AS departamento_nombre,
                           u.activo, u.ultimo_acceso, u.created_at
                    FROM usuarios u
                    INNER JOIN roles r ON r.id = u.rol_id
                    LEFT JOIN departamentos d ON d.id = u.departamento_id
                    WHERE u.departamento_id = :departamento_id
                    ORDER BY u.nombre_completo
                ");
                $stmt->execute([':departamento_id' => $deptoSesion]);
            }

            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            break;

        case 'catalogos':
            if (!adminOJefe($userRol)) {
                $response['message'] = 'Sin permisos';
                break;
            }

            $roles = $db->query("SELECT id, nombre FROM roles ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            $departamentos = $db->query("SELECT id, nombre FROM departamentos WHERE activo = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

            $response['data'] = [
                'roles' => $roles,
                'departamentos' => $departamentos
            ];
            $response['success'] = true;
            break;

        case 'obtener':
            if (!adminOJefe($userRol)) {
                $response['message'] = 'Sin permisos';
                break;
            }

            $usuarioId = (int)($_GET['usuario_id'] ?? 0);
            if ($usuarioId <= 0) {
                $response['message'] = 'Usuario invalido';
                break;
            }

            $stmt = $db->prepare("
                SELECT u.id, u.username, u.email, u.nombre_completo, u.telefono, u.rol_id, r.nombre AS rol_nombre, u.departamento_id, u.activo, u.recibir_notificaciones_email
                FROM usuarios u
                INNER JOIN roles r ON r.id = u.rol_id
                WHERE u.id = :id
                LIMIT 1
            ");
            $stmt->execute([':id' => $usuarioId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                $response['message'] = 'Usuario no encontrado';
                break;
            }

            $response['success'] = true;
            $response['data'] = $usuario;
            break;

        case 'crear':
            if (!soloAdmin($userRol)) {
                $response['message'] = 'Solo Administrador puede crear usuarios';
                break;
            }

            $nombre = trim($_POST['nombre_completo'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $rolId = (int)($_POST['rol_id'] ?? 0);
            $departamentoId = (int)($_POST['departamento_id'] ?? 0);
            $password = $_POST['password'] ?? '';
            $recibirEmail = (int)($_POST['recibir_notificaciones_email'] ?? 1);

            if ($nombre === '' || $username === '' || $email === '' || $password === '' || $rolId <= 0 || $departamentoId <= 0) {
                $response['message'] = 'Complete los campos obligatorios';
                break;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Email invalido';
                break;
            }

            if (strlen($password) < 6) {
                $response['message'] = 'La clave debe tener al menos 6 caracteres';
                break;
            }

            $stmtDup = $db->prepare("
                SELECT COUNT(*) AS total
                FROM usuarios
                WHERE username = :username OR email = :email
            ");
            $stmtDup->execute([
                ':username' => $username,
                ':email' => $email
            ]);
            $dup = (int)($stmtDup->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            if ($dup > 0) {
                $response['message'] = 'Username o email ya existe';
                break;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmtIns = $db->prepare("
                INSERT INTO usuarios (
                    username, email, password, nombre_completo, telefono,
                    rol_id, departamento_id, avatar, activo, recibir_notificaciones_email, created_at
                )
                VALUES (
                    :username, :email, :password, :nombre_completo, :telefono,
                    :rol_id, :departamento_id, 'default-avatar.png', 1, :recibir_email, NOW()
                )
            ");
            $stmtIns->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $passwordHash,
                ':nombre_completo' => $nombre,
                ':telefono' => ($telefono !== '' ? $telefono : null),
                ':rol_id' => $rolId,
                ':departamento_id' => $departamentoId,
                ':recibir_email' => $recibirEmail
            ]);

            $response['success'] = true;
            $response['message'] = 'Usuario creado correctamente';
            $response['data'] = ['id' => (int)$db->lastInsertId()];

            // Enviar correo de bienvenida con credenciales
            if (defined('SMTP_ENABLED') && SMTP_ENABLED) {
                $mailOk = enviarCorreoNuevoUsuario($email, $nombre, $username, $password);
                if (!$mailOk) {
                    logCorreo("Fallo envio correo nuevo usuario a {$email} (username={$username})");
                    $response['message'] .= '. No se pudo enviar el correo';
                    $response['mail_sent'] = false;
                } else {
                    logCorreo("Correo enviado a {$email} (username={$username})");
                    $response['mail_sent'] = true;
                }
            } else {
                logCorreo("SMTP deshabilitado. Usuario creado sin correo: {$email} (username={$username})");
                $response['message'] .= '. Correo no enviado: SMTP deshabilitado en configuracion';
                $response['mail_sent'] = false;
            }
            break;

        case 'actualizar':
            if (!soloAdmin($userRol)) {
                $response['message'] = 'Solo Administrador puede editar usuarios';
                break;
            }

            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            $nombre = trim($_POST['nombre_completo'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $rolId = (int)($_POST['rol_id'] ?? 0);
            $departamentoId = (int)($_POST['departamento_id'] ?? 0);
            $activo = (int)($_POST['activo'] ?? 1) === 1 ? 1 : 0;
            $password = trim($_POST['password'] ?? '');
            $recibirEmail = (int)($_POST['recibir_notificaciones_email'] ?? 1);

            if ($usuarioId <= 0 || $nombre === '' || $username === '' || $email === '' || $rolId <= 0 || $departamentoId <= 0) {
                $response['message'] = 'Complete los campos obligatorios';
                break;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Email invalido';
                break;
            }

            if ($usuarioId === $currentUserId && $activo === 0) {
                $response['message'] = 'No puede desactivar su propio usuario';
                break;
            }

            $stmtExists = $db->prepare("SELECT id FROM usuarios WHERE id = :id LIMIT 1");
            $stmtExists->execute([':id' => $usuarioId]);
            if (!$stmtExists->fetch(PDO::FETCH_ASSOC)) {
                $response['message'] = 'Usuario no encontrado';
                break;
            }

            if ($activo === 0) {
                $stmtRolObjetivo = $db->prepare("
                    SELECT r.nombre AS rol_nombre
                    FROM usuarios u
                    INNER JOIN roles r ON r.id = u.rol_id
                    WHERE u.id = :id
                    LIMIT 1
                ");
                $stmtRolObjetivo->execute([':id' => $usuarioId]);
                $target = $stmtRolObjetivo->fetch(PDO::FETCH_ASSOC);
                $rolActualObjetivo = trim((string)($target['rol_nombre'] ?? ''));

                $stmtRolNuevo = $db->prepare("SELECT nombre FROM roles WHERE id = :id LIMIT 1");
                $stmtRolNuevo->execute([':id' => $rolId]);
                $nuevoRolNombre = trim((string)($stmtRolNuevo->fetch(PDO::FETCH_ASSOC)['nombre'] ?? ''));

                if (esRolAdministradorNombre($rolActualObjetivo) || esRolAdministradorNombre($nuevoRolNombre)) {
                    $response['message'] = 'Los administradores no se pueden desactivar';
                    break;
                }
            }

            $stmtDup = $db->prepare("
                SELECT COUNT(*) AS total
                FROM usuarios
                WHERE (username = :username OR email = :email)
                  AND id <> :id
            ");
            $stmtDup->execute([
                ':username' => $username,
                ':email' => $email,
                ':id' => $usuarioId
            ]);
            $dup = (int)($stmtDup->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
            if ($dup > 0) {
                $response['message'] = 'Username o email ya existe';
                break;
            }

            if ($password !== '' && strlen($password) < 6) {
                $response['message'] = 'La clave debe tener al menos 6 caracteres';
                break;
            }

            if ($password !== '') {
                $stmtUp = $db->prepare("
                    UPDATE usuarios
                    SET nombre_completo = :nombre_completo,
                        username = :username,
                        email = :email,
                        telefono = :telefono,
                        rol_id = :rol_id,
                        departamento_id = :departamento_id,
                        activo = :activo,
                        recibir_notificaciones_email = :recibir_email,
                        password = :password
                    WHERE id = :id
                ");
                $stmtUp->execute([
                    ':nombre_completo' => $nombre,
                    ':username' => $username,
                    ':email' => $email,
                    ':telefono' => ($telefono !== '' ? $telefono : null),
                    ':rol_id' => $rolId,
                    ':departamento_id' => $departamentoId,
                    ':activo' => $activo,
                    ':recibir_email' => $recibirEmail,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':id' => $usuarioId
                ]);
            } else {
                $stmtUp = $db->prepare("
                    UPDATE usuarios
                    SET nombre_completo = :nombre_completo,
                        username = :username,
                        email = :email,
                        telefono = :telefono,
                        rol_id = :rol_id,
                        departamento_id = :departamento_id,
                        activo = :activo,
                        recibir_notificaciones_email = :recibir_email
                    WHERE id = :id
                ");
                $stmtUp->execute([
                    ':nombre_completo' => $nombre,
                    ':username' => $username,
                    ':email' => $email,
                    ':telefono' => ($telefono !== '' ? $telefono : null),
                    ':rol_id' => $rolId,
                    ':departamento_id' => $departamentoId,
                    ':activo' => $activo,
                    ':recibir_email' => $recibirEmail,
                    ':id' => $usuarioId
                ]);
            }

            $response['success'] = true;
            $response['message'] = 'Usuario actualizado correctamente';
            break;

        case 'cambiar_estado':
            if (!adminOJefe($userRol)) {
                $response['message'] = 'Sin permisos';
                break;
            }

            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            $activo = (int)($_POST['activo'] ?? 0) === 1 ? 1 : 0;

            if ($usuarioId <= 0) {
                $response['message'] = 'Usuario invalido';
                break;
            }
            if ($usuarioId === $currentUserId && $activo === 0) {
                $response['message'] = 'No puede desactivar su propio usuario';
                break;
            }

            if ($activo === 0) {
                $stmtRolObjetivo = $db->prepare("
                    SELECT r.nombre AS rol_nombre
                    FROM usuarios u
                    INNER JOIN roles r ON r.id = u.rol_id
                    WHERE u.id = :id
                    LIMIT 1
                ");
                $stmtRolObjetivo->execute([':id' => $usuarioId]);
                $target = $stmtRolObjetivo->fetch(PDO::FETCH_ASSOC);
                $rolObjetivo = trim((string)($target['rol_nombre'] ?? ''));
                if (esRolAdministradorNombre($rolObjetivo)) {
                    $response['message'] = 'Los administradores no se pueden desactivar';
                    break;
                }
            }

            if (!soloAdmin($userRol) && $activo === 0) {
                $response['message'] = 'Solo Administrador puede desactivar usuarios';
                break;
            }

            $stmtUp = $db->prepare("UPDATE usuarios SET activo = :activo WHERE id = :id");
            $stmtUp->execute([
                ':activo' => $activo,
                ':id' => $usuarioId
            ]);

            $response['success'] = true;
            $response['message'] = $activo ? 'Usuario activado' : 'Usuario desactivado';
            break;

        case 'eliminar':
            if (!soloAdmin($userRol)) {
                $response['message'] = 'Solo Administrador puede eliminar usuarios';
                break;
            }

            $usuarioId = (int)($_POST['usuario_id'] ?? 0);
            if ($usuarioId <= 0) {
                $response['message'] = 'Usuario invalido';
                break;
            }
            if ($usuarioId === $currentUserId) {
                $response['message'] = 'No puede eliminar su propio usuario';
                break;
            }

            $stmtExists = $db->prepare("SELECT id FROM usuarios WHERE id = :id LIMIT 1");
            $stmtExists->execute([':id' => $usuarioId]);
            if (!$stmtExists->fetch(PDO::FETCH_ASSOC)) {
                $response['message'] = 'Usuario no encontrado';
                break;
            }

            try {
                $stmtDel = $db->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmtDel->execute([':id' => $usuarioId]);
                $response['success'] = true;
                $response['message'] = 'Usuario eliminado correctamente';
            } catch (Exception $deleteEx) {
                $response['message'] = 'No se puede eliminar el usuario porque tiene registros relacionados. Puede desactivarlo.';
            }
            break;

        default:
            $response['message'] = 'Accion no valida';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
