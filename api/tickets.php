<?php
header('Content-Type: application/json');
require_once '../config/session.php';
session_start();

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/EmailHelper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$response = ['success' => false, 'message' => ''];

function ensure_transferencias_table($db) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS ticket_transferencias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            usuario_origen INT NULL,
            usuario_destino INT NOT NULL,
            solicitado_por INT NOT NULL,
            motivo TEXT NULL,
            estado ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
            aprobado_por INT NULL,
            comentario_aprobacion TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ticket_estado (ticket_id, estado),
            INDEX idx_solicitado_por (solicitado_por),
            INDEX idx_usuario_destino (usuario_destino),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function get_user_display_name($db, $user_id, $fallback = 'Usuario') {
    if(empty($user_id)) {
        return $fallback;
    }
    $stmt = $db->prepare("SELECT nombre_completo FROM usuarios WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if($row && !empty($row['nombre_completo'])) {
        return $row['nombre_completo'];
    }
    return $fallback;
}

try {
    switch($action) {
        case 'listar':
            ensure_transferencias_table($db);

            $user_rol        = $_SESSION['user_rol']        ?? 'Usuario';
            $departamento_id = $_SESSION['departamento_id'] ?? 0;


            // Base SELECT
            $selectBase = "SELECT t.*,
                      e.nombre as estado, e.color as estado_color,
                      act.nombre as actividad, act.color as actividad_color,
                      ub.nombre as ubicacion,
                      eq.nombre as equipo,
                      ca.nombre as canal,
                      u1.nombre_completo as creador,
                      u2.nombre_completo as asignado,
                      t.usuario_id as creador_id,
                      t.asignado_a as asignado_id,
                      (SELECT tt.usuario_destino FROM ticket_transferencias tt
                        WHERE tt.ticket_id = t.id AND tt.estado = 'pendiente'
                        ORDER BY tt.created_at DESC LIMIT 1
                      ) as transfer_destino_id,
                      (SELECT u_dest.nombre_completo FROM ticket_transferencias tt
                        INNER JOIN usuarios u_dest ON u_dest.id = tt.usuario_destino
                        WHERE tt.ticket_id = t.id AND tt.estado = 'pendiente'
                        ORDER BY tt.created_at DESC LIMIT 1
                      ) as transfer_destino_nombre,
                      (SELECT u_sol.nombre_completo FROM ticket_transferencias tt
                        INNER JOIN usuarios u_sol ON u_sol.id = tt.solicitado_por
                        WHERE tt.ticket_id = t.id AND tt.estado = 'pendiente'
                        ORDER BY tt.created_at DESC LIMIT 1
                      ) as transfer_solicitado_por_nombre,
                      EXISTS(
                        SELECT 1 FROM ticket_transferencias tt
                        WHERE tt.ticket_id = t.id AND tt.estado = 'pendiente'
                      ) as transferencia_pendiente,
                      (
                        SELECT COUNT(*) FROM ticket_transferencias tt2
                        WHERE tt2.ticket_id = t.id AND tt2.estado = 'aprobada'
                      ) as transferencia_aprobada,
                      EXISTS(
                        SELECT 1 FROM ticket_transferencias tt3
                        WHERE tt3.ticket_id = t.id
                          AND tt3.estado = 'aprobada'
                          AND tt3.usuario_destino = :current_user_id
                      ) as transferido_a_mi,
                      (SELECT u_orig.nombre_completo
                        FROM ticket_transferencias tt4
                        INNER JOIN usuarios u_orig ON u_orig.id = tt4.usuario_origen
                        WHERE tt4.ticket_id = t.id AND tt4.estado = 'aprobada'
                        ORDER BY tt4.updated_at DESC LIMIT 1
                      ) as ultima_transfer_origen_nombre,
                      d.nombre as departamento_nombre,
                      (
                        SELECT GROUP_CONCAT(d2.nombre ORDER BY d2.nombre SEPARATOR ' & ')
                        FROM actividades_departamentos ad2
                        INNER JOIN departamentos d2 ON d2.id = ad2.departamento_id
                        WHERE ad2.actividad_id = t.actividad_id
                      ) as departamentos_actividad,
                      (
                        SELECT GROUP_CONCAT(ad3.departamento_id ORDER BY ad3.departamento_id SEPARATOR ',')
                        FROM actividades_departamentos ad3
                        WHERE ad3.actividad_id = t.actividad_id
                      ) as departamentos_actividad_ids
                      FROM tickets t
                      LEFT JOIN estados e ON t.estado_id = e.id
                      LEFT JOIN actividades act ON t.actividad_id = act.id
                      LEFT JOIN ubicaciones ub ON t.ubicacion_id = ub.id
                      LEFT JOIN equipos eq ON t.equipo_id = eq.id
                      LEFT JOIN canales_atencion ca ON t.canal_atencion_id = ca.id
                      LEFT JOIN usuarios u1 ON t.usuario_id = u1.id
                      LEFT JOIN usuarios u2 ON t.asignado_a = u2.id
                      LEFT JOIN departamentos d ON t.departamento_id = d.id";

            $params = [':current_user_id' => $user_id];

            // Superadmin: Administrador del departamento General (id=1) ve todo sin filtro
            $es_superadmin = ($user_rol === 'Administrador' || $user_rol === 'Admin')
                             && (int)$departamento_id === 1;

            if ($es_superadmin) {
                // Ve absolutamente todos los tickets
                $query = $selectBase . " ORDER BY t.created_at DESC";

            } elseif ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe') {
                // Admin/Jefe de área: tickets de su departamento + creados/asignados a él
                // + tickets con transferencia pendiente hacia usuarios de su departamento
                $query = $selectBase . "
                      WHERE (
                        EXISTS (
                          SELECT 1 FROM actividades_departamentos ad
                          WHERE ad.actividad_id = t.actividad_id
                            AND ad.departamento_id = :departamento_id
                        )
                        OR t.usuario_id  = :usuario_creador
                        OR t.asignado_a  = :usuario_asignado
                        OR EXISTS (
                          SELECT 1 FROM ticket_transferencias tt_pend
                          INNER JOIN usuarios u_dest ON u_dest.id = tt_pend.usuario_destino
                          WHERE tt_pend.ticket_id = t.id
                            AND tt_pend.estado = 'pendiente'
                            AND u_dest.departamento_id = :departamento_id2
                        )
                      )
                      ORDER BY t.created_at DESC";
                $params[':departamento_id']   = $departamento_id;
                $params[':departamento_id2']  = $departamento_id;
                $params[':usuario_creador']   = $user_id;
                $params[':usuario_asignado']  = $user_id;
            } else {
                // Usuario normal: solo tickets donde soy responsable actual
                // 1) Asignados a mí (vigentes)
                // 2) Creados por mí Y (sin asignar a otro O asignados a mí mismo)
                $query = $selectBase . "
                      WHERE (
                        t.asignado_a = :usuario_asignado
                        OR (t.usuario_id = :usuario_creador AND (t.asignado_a IS NULL OR t.asignado_a = 0 OR t.asignado_a = :usuario_mismo))
                      )
                      ORDER BY t.created_at DESC";
                $params[':usuario_creador']  = $user_id;
                $params[':usuario_asignado'] = $user_id;
                $params[':usuario_mismo']    = $user_id;
            }

            $stmt = $db->prepare($query);
            foreach ($params as $key => $val) {
                $stmt->bindValue($key, $val, PDO::PARAM_INT);
            }
            $stmt->execute();
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            break;
            
        case 'crear':
            $titulo = $_POST['titulo'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $canal_id = !empty($_POST['canal_atencion_id']) ? $_POST['canal_atencion_id'] : null;
            $actividad_id = !empty($_POST['actividad_id']) ? $_POST['actividad_id'] : null;
            $tipo_falla_id = !empty($_POST['tipo_falla_id']) ? $_POST['tipo_falla_id'] : null;
            $ubicacion_id = !empty($_POST['ubicacion_id']) ? $_POST['ubicacion_id'] : null;
            $equipo_id = !empty($_POST['equipo_id']) ? $_POST['equipo_id'] : null;
            $codigo_equipo_id = !empty($_POST['codigo_equipo_id']) ? $_POST['codigo_equipo_id'] : null;
            $prioridad_id = !empty($_POST['prioridad_id']) ? $_POST['prioridad_id'] : 2;
            $departamento_id = !empty($_POST['departamento_id']) ? $_POST['departamento_id'] : null;
            $asignado_a = !empty($_POST['asignado_a']) ? $_POST['asignado_a'] : null;
            $solicitante_nombre = $_POST['solicitante_nombre'] ?? null;
            $solicitante_email = $_POST['solicitante_email'] ?? null;
            $solicitante_telefono = $_POST['solicitante_telefono'] ?? null;

            if(empty($titulo) || empty($descripcion)) {
                $response['message'] = 'Titulo y descripcion son requeridos';
                break;
            }

            // Iniciar transaccion
            $db->beginTransaction();

            try {
                $query = "INSERT INTO tickets (titulo, descripcion, usuario_id, departamento_id, prioridad_id,
                          canal_atencion_id, actividad_id, tipo_falla_id, ubicacion_id, equipo_id, codigo_equipo_id,
                          asignado_a, solicitante_nombre, solicitante_email, solicitante_telefono)
                          VALUES (:titulo, :descripcion, :usuario_id, :departamento_id, :prioridad_id,
                          :canal_id, :actividad_id, :tipo_falla_id, :ubicacion_id, :equipo_id, :codigo_equipo_id,
                          :asignado_a, :solicitante_nombre, :solicitante_email, :solicitante_telefono)";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':titulo', $titulo);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':usuario_id', $user_id);
                $stmt->bindParam(':departamento_id', $departamento_id);
                $stmt->bindParam(':prioridad_id', $prioridad_id);
                $stmt->bindParam(':canal_id', $canal_id);
                $stmt->bindParam(':actividad_id', $actividad_id);
                $stmt->bindParam(':tipo_falla_id', $tipo_falla_id);
                $stmt->bindParam(':ubicacion_id', $ubicacion_id);
                $stmt->bindParam(':equipo_id', $equipo_id);
                $stmt->bindParam(':codigo_equipo_id', $codigo_equipo_id);
                $stmt->bindParam(':asignado_a', $asignado_a);
                $stmt->bindParam(':solicitante_nombre', $solicitante_nombre);
                $stmt->bindParam(':solicitante_email', $solicitante_email);
                $stmt->bindParam(':solicitante_telefono', $solicitante_telefono);

                $stmt->execute();
                $ticket_id = $db->lastInsertId();

                // Obtener codigo del ticket
                $query = "SELECT codigo FROM tickets WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $ticket_id);
                $stmt->execute();
                $codigo = $stmt->fetch(PDO::FETCH_ASSOC)['codigo'];

                // Procesar archivos adjuntos
                $archivos_subidos = [];
                $max_size = 10 * 1024 * 1024; // 10MB

                if(isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
                    $upload_dir = '../uploads/tickets/' . $codigo . '/';

                    // Crear directorio si no existe
                    if(!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $files = $_FILES['archivos'];
                    $file_count = count($files['name']);

                    for($i = 0; $i < $file_count; $i++) {
                        if($files['error'][$i] === UPLOAD_ERR_OK) {
                            $file_size = $files['size'][$i];

                            // Validar tamano (max 10MB)
                            if($file_size > $max_size) {
                                continue; // Saltar archivos muy grandes
                            }

                            $original_name = $files['name'][$i];
                            $extension = pathinfo($original_name, PATHINFO_EXTENSION);
                            $safe_name = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $original_name);
                            $file_path = $upload_dir . $safe_name;

                            if(move_uploaded_file($files['tmp_name'][$i], $file_path)) {
                                // Guardar en base de datos
                                $query = "INSERT INTO ticket_archivos (ticket_id, nombre_original, nombre_archivo, ruta, tamano, tipo_mime, usuario_id)
                                          VALUES (:ticket_id, :nombre_original, :nombre_archivo, :ruta, :tamano, :tipo_mime, :usuario_id)";
                                $stmt = $db->prepare($query);
                                $stmt->execute([
                                    ':ticket_id' => $ticket_id,
                                    ':nombre_original' => $original_name,
                                    ':nombre_archivo' => $safe_name,
                                    ':ruta' => 'uploads/tickets/' . $codigo . '/' . $safe_name,
                                    ':tamano' => $file_size,
                                    ':tipo_mime' => $files['type'][$i],
                                    ':usuario_id' => $user_id
                                ]);

                                $archivos_subidos[] = $original_name;
                            }
                        }
                    }
                }

                // Registrar en historial (el trigger ya lo hace, pero por si acaso)
                // Nota: La tabla historial no tiene columna 'descripcion', el trigger after_insert_ticket ya registra la creación

                $db->commit();

                // Notificar por correo
                EmailHelper::notifyTicketEvent('ticket_creado', [
                    'ticket_id'   => $ticket_id,
                    'codigo'      => $codigo,
                    'titulo'      => $titulo,
                    'descripcion' => $descripcion,
                ], $user_id, $db);

                $response['success'] = true;
                $response['message'] = 'Ticket creado exitosamente';
                $response['codigo'] = $codigo;
                $response['ticket_id'] = $ticket_id;
                $response['archivos'] = $archivos_subidos;

            } catch(Exception $e) {
                $db->rollBack();
                $response['message'] = 'Error al crear ticket: ' . $e->getMessage();
            }
            break;

        case 'actualizar':
            $ticket_id = $_POST['ticket_id'] ?? '';
            $codigo = $_POST['codigo'] ?? '';
            $titulo = $_POST['titulo'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $canal_id = !empty($_POST['canal_atencion_id']) ? $_POST['canal_atencion_id'] : null;
            $actividad_id = !empty($_POST['actividad_id']) ? $_POST['actividad_id'] : null;
            $tipo_falla_id = !empty($_POST['tipo_falla_id']) ? $_POST['tipo_falla_id'] : null;
            $ubicacion_id = !empty($_POST['ubicacion_id']) ? $_POST['ubicacion_id'] : null;
            $equipo_id = !empty($_POST['equipo_id']) ? $_POST['equipo_id'] : null;
            $codigo_equipo_id = !empty($_POST['codigo_equipo_id']) ? $_POST['codigo_equipo_id'] : null;
            $prioridad_id = !empty($_POST['prioridad_id']) ? $_POST['prioridad_id'] : null;
            $departamento_id = !empty($_POST['departamento_id']) ? $_POST['departamento_id'] : null;
            $asignado_a = !empty($_POST['asignado_a']) ? $_POST['asignado_a'] : null;
            $estado_id = !empty($_POST['estado_id']) ? $_POST['estado_id'] : null;
            $progreso = isset($_POST['progreso']) ? $_POST['progreso'] : null;
            $solicitante_nombre = $_POST['solicitante_nombre'] ?? null;
            $solicitante_email = $_POST['solicitante_email'] ?? null;
            $solicitante_telefono = $_POST['solicitante_telefono'] ?? null;

            if((empty($ticket_id) && empty($codigo)) || empty($titulo) || empty($descripcion)) {
                $response['message'] = 'Datos incompletos';
                break;
            }

            // Verificar que el ticket existe (por ID o por codigo)
            if(!empty($ticket_id)) {
                $stmt = $db->prepare("SELECT id, usuario_id, asignado_a, estado_id, progreso FROM tickets WHERE id = ?");
                $stmt->execute([$ticket_id]);
            } else {
                $stmt = $db->prepare("SELECT id, usuario_id, asignado_a, estado_id, progreso FROM tickets WHERE codigo = ?");
                $stmt->execute([$codigo]);
            }
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if($ticket) {
                $ticket_id = $ticket['id'];
                // Si no se envió estado_id, mantener el actual
                if($estado_id === null) {
                    $estado_id = $ticket['estado_id'];
                }
                // Si no se envió progreso, mantener el actual
                if($progreso === null) {
                    $progreso = $ticket['progreso'];
                }
            }

            if(!$ticket) {
                $response['message'] = 'Ticket no encontrado';
                break;
            }

            // Verificar permisos
            $user_rol = $_SESSION['user_rol'] ?? '';
            $puede_editar = ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe' ||
                            $ticket['usuario_id'] == $user_id || $ticket['asignado_a'] == $user_id);

            if(!$puede_editar) {
                $response['message'] = 'No tiene permisos para editar este ticket';
                break;
            }

            $query = "UPDATE tickets SET
                      titulo = :titulo,
                      descripcion = :descripcion,
                      departamento_id = :departamento_id,
                      prioridad_id = :prioridad_id,
                      estado_id = :estado_id,
                      progreso = :progreso,
                      canal_atencion_id = :canal_id,
                      actividad_id = :actividad_id,
                      tipo_falla_id = :tipo_falla_id,
                      ubicacion_id = :ubicacion_id,
                      equipo_id = :equipo_id,
                      codigo_equipo_id = :codigo_equipo_id,
                      asignado_a = :asignado_a,
                      solicitante_nombre = :solicitante_nombre,
                      solicitante_email = :solicitante_email,
                      solicitante_telefono = :solicitante_telefono,
                      updated_at = NOW()
                      WHERE id = :ticket_id";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':departamento_id', $departamento_id);
            $stmt->bindParam(':prioridad_id', $prioridad_id);
            $stmt->bindParam(':estado_id', $estado_id);
            $stmt->bindParam(':progreso', $progreso);
            $stmt->bindParam(':canal_id', $canal_id);
            $stmt->bindParam(':actividad_id', $actividad_id);
            $stmt->bindParam(':tipo_falla_id', $tipo_falla_id);
            $stmt->bindParam(':ubicacion_id', $ubicacion_id);
            $stmt->bindParam(':equipo_id', $equipo_id);
            $stmt->bindParam(':codigo_equipo_id', $codigo_equipo_id);
            $stmt->bindParam(':asignado_a', $asignado_a);
            $stmt->bindParam(':solicitante_nombre', $solicitante_nombre);
            $stmt->bindParam(':solicitante_email', $solicitante_email);
            $stmt->bindParam(':solicitante_telefono', $solicitante_telefono);
            $stmt->bindParam(':ticket_id', $ticket_id);

            if($stmt->execute()) {
                // Notificar por correo
                EmailHelper::notifyTicketEvent('ticket_actualizado', [
                    'ticket_id' => $ticket_id,
                    'titulo'    => $titulo,
                ], $user_id, $db);

                $response['success'] = true;
                $response['message'] = 'Ticket actualizado correctamente';
            } else {
                $response['message'] = 'Error al actualizar el ticket';
            }
            break;

        case 'eliminar_archivo':
            $archivo_id = $_POST['archivo_id'] ?? '';

            if(empty($archivo_id)) {
                $response['message'] = 'ID de archivo requerido';
                break;
            }

            // Obtener información del archivo
            $stmt = $db->prepare("SELECT ta.*, t.usuario_id, t.asignado_a FROM ticket_archivos ta
                                  INNER JOIN tickets t ON ta.ticket_id = t.id
                                  WHERE ta.id = ?");
            $stmt->execute([$archivo_id]);
            $archivo = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$archivo) {
                $response['message'] = 'Archivo no encontrado';
                break;
            }

            // Verificar permisos
            $user_rol = $_SESSION['user_rol'] ?? '';
            $puede_eliminar = ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe' ||
                              $archivo['usuario_id'] == $user_id || $archivo['asignado_a'] == $user_id);

            if(!$puede_eliminar) {
                $response['message'] = 'No tiene permisos para eliminar este archivo';
                break;
            }

            // Eliminar archivo físico
            $ruta_completa = '../' . $archivo['ruta'];
            if(file_exists($ruta_completa)) {
                unlink($ruta_completa);
            }

            // Eliminar registro de la base de datos
            $stmt = $db->prepare("DELETE FROM ticket_archivos WHERE id = ?");
            if($stmt->execute([$archivo_id])) {
                $response['success'] = true;
                $response['message'] = 'Archivo eliminado correctamente';
            } else {
                $response['message'] = 'Error al eliminar el archivo de la base de datos';
            }
            break;

        case 'actualizar_progreso':
            $codigo = $_POST['codigo'] ?? '';
            $progreso = intval($_POST['progreso'] ?? 0);

            if(empty($codigo)) {
                $response['message'] = 'Codigo de ticket requerido';
                break;
            }

            // Obtener estado actual del ticket
            $stmt = $db->prepare("SELECT estado_id, progreso as progreso_actual FROM tickets WHERE codigo = ?");
            $stmt->execute([$codigo]);
            $ticketActual = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$ticketActual) {
                $response['message'] = 'Ticket no encontrado';
                break;
            }

            // Normalizar rango permitido
            if ($progreso < 0) $progreso = 0;
            if ($progreso > 100) $progreso = 100;

            // Determinar nuevo estado basado en el progreso
            $nuevo_estado = $ticketActual['estado_id'];
            if($progreso == 0) {
                $nuevo_estado = 1; // Abierto
            } elseif($progreso > 0 && $progreso < 100) {
                $nuevo_estado = 2; // En Atencion
            }

            // Si llega a 100%, debe quedar pendiente de verificacion.
            // Se hace en 2 updates para neutralizar triggers antiguos que forzaban estado 5.
            if ($progreso >= 100) {
                $db->beginTransaction();
                try {
                    $stmt = $db->prepare("UPDATE tickets SET progreso = 100, updated_at = NOW() WHERE codigo = :codigo");
                    $stmt->bindParam(':codigo', $codigo);
                    $stmt->execute();

                    $stmt = $db->prepare("UPDATE tickets SET estado_id = 4, pendiente_aprobacion = 1, fecha_resolucion = NOW(), updated_at = NOW() WHERE codigo = :codigo");
                    $stmt->bindParam(':codigo', $codigo);
                    $stmt->execute();

                    $db->commit();

                    // Notificar pendiente de verificacion (100%)
                    $stmtTkId = $db->prepare("SELECT id FROM tickets WHERE codigo = ?");
                    $stmtTkId->execute([$codigo]);
                    $tkRow = $stmtTkId->fetch(PDO::FETCH_ASSOC);
                    if ($tkRow) {
                        EmailHelper::notifyTicketEvent('pendiente_verificacion', [
                            'ticket_id' => $tkRow['id'],
                            'codigo'    => $codigo,
                            'progreso'  => 100,
                        ], $user_id, $db);
                    }

                    $response['success'] = true;
                    $response['message'] = 'Progreso actualizado. Ticket pendiente de verificacion.';
                    $response['nuevo_estado'] = 4;
                    $response['pendiente_aprobacion'] = 1;
                } catch(Exception $e) {
                    $db->rollBack();
                    $response['message'] = 'Error al actualizar progreso: ' . $e->getMessage();
                }
            } else {
                $query = "UPDATE tickets SET progreso = :progreso, estado_id = :estado_id, pendiente_aprobacion = 0, updated_at = NOW() WHERE codigo = :codigo";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':progreso', $progreso);
                $stmt->bindParam(':estado_id', $nuevo_estado);
                $stmt->bindParam(':codigo', $codigo);

                if($stmt->execute()) {
                    // Notificar progreso actualizado
                    $stmtTkId = $db->prepare("SELECT id FROM tickets WHERE codigo = ?");
                    $stmtTkId->execute([$codigo]);
                    $tkRow = $stmtTkId->fetch(PDO::FETCH_ASSOC);
                    if ($tkRow) {
                        EmailHelper::notifyTicketEvent('progreso_actualizado', [
                            'ticket_id' => $tkRow['id'],
                            'codigo'    => $codigo,
                            'progreso'  => $progreso,
                        ], $user_id, $db);
                    }

                    $response['success'] = true;
                    $response['message'] = 'Progreso actualizado';
                    $response['nuevo_estado'] = $nuevo_estado;
                    $response['pendiente_aprobacion'] = 0;
                } else {
                    $response['message'] = 'Error al actualizar progreso';
                }
            }
            break;

        case 'actualizar_estado':
            $ticket_id = $_POST['ticket_id'] ?? '';
            $estado_id = $_POST['estado_id'] ?? '';

            if(empty($ticket_id) || empty($estado_id)) {
                $response['message'] = 'ID de ticket y estado son requeridos';
                break;
            }

            // Verificar que el ticket existe
            $stmt = $db->prepare("SELECT id, usuario_id, asignado_a FROM tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$ticket) {
                $response['message'] = 'Ticket no encontrado';
                break;
            }

            // Verificar permisos
            $user_rol = $_SESSION['user_rol'] ?? '';
            $puede_editar = ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe' ||
                            $ticket['usuario_id'] == $user_id || $ticket['asignado_a'] == $user_id);

            if(!$puede_editar) {
                $response['message'] = 'No tiene permisos para modificar este ticket';
                break;
            }

            $query = "UPDATE tickets SET estado_id = :estado_id, updated_at = NOW() WHERE id = :ticket_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':estado_id', $estado_id);
            $stmt->bindParam(':ticket_id', $ticket_id);

            if($stmt->execute()) {
                // Notificar cambio de estado
                EmailHelper::notifyTicketEvent('estado_actualizado', [
                    'ticket_id' => $ticket_id,
                ], $user_id, $db);

                $response['success'] = true;
                $response['message'] = 'Estado actualizado';
            } else {
                $response['message'] = 'Error al actualizar estado';
            }
            break;

        case 'cerrar_ticket':
            $codigo = $_POST['codigo'] ?? '';

            if(empty($codigo)) {
                $response['message'] = 'Código de ticket requerido';
                break;
            }

            // Verificar que el ticket existe
            $stmt = $db->prepare("SELECT id, usuario_id, asignado_a, estado_id FROM tickets WHERE codigo = ?");
            $stmt->execute([$codigo]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$ticket) {
                $response['message'] = 'Ticket no encontrado';
                break;
            }

            // Verificar que no este ya resuelto/aprobado
            if($ticket['estado_id'] == 4) {
                // Obtener pendiente_aprobacion
                $stmt2 = $db->prepare("SELECT pendiente_aprobacion FROM tickets WHERE codigo = ?");
                $stmt2->execute([$codigo]);
                $ticketData = $stmt2->fetch(PDO::FETCH_ASSOC);
                if($ticketData && $ticketData['pendiente_aprobacion'] == 0) {
                    $response['message'] = 'El ticket ya está resuelto y aprobado';
                    break;
                }
                if($ticketData && $ticketData['pendiente_aprobacion'] == 1) {
                    $response['message'] = 'El ticket ya está pendiente de verificación';
                    break;
                }
            }

            // Verificar permisos
            $user_rol = $_SESSION['user_rol'] ?? '';
            $puede_cerrar = ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe' ||
                            $ticket['usuario_id'] == $user_id || $ticket['asignado_a'] == $user_id);

            if(!$puede_cerrar) {
                $response['message'] = 'No tiene permisos para cerrar este ticket';
                break;
            }

            // Cerrar ticket: estado_id = 4 (Resuelto), progreso = 100, pendiente_aprobacion = 1
            $query = "UPDATE tickets SET estado_id = 4, progreso = 100, pendiente_aprobacion = 1, fecha_resolucion = NOW(), updated_at = NOW() WHERE codigo = :codigo";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':codigo', $codigo);

            if($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Ticket cerrado correctamente. Pendiente de aprobación.';
            } else {
                $response['message'] = 'Error al cerrar el ticket';
            }
            break;

        case 'aprobar_cierre':
            $codigo = $_POST['codigo'] ?? '';
            $comentario = $_POST['comentario'] ?? '';

            if(empty($codigo)) {
                $response['message'] = 'Código de ticket requerido';
                break;
            }

            // Verificar que el ticket existe
            $stmt = $db->prepare("SELECT id, estado_id, pendiente_aprobacion FROM tickets WHERE codigo = ?");
            $stmt->execute([$codigo]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$ticket) {
                $response['message'] = 'Ticket no encontrado';
                break;
            }

            // Verificar que está pendiente de aprobación
            if(!$ticket['pendiente_aprobacion']) {
                $response['message'] = 'Este ticket no requiere aprobación';
                break;
            }

            // Solo Jefe o Administrador pueden aprobar
            $user_rol = $_SESSION['user_rol'] ?? '';
            if($user_rol !== 'Administrador' && $user_rol !== 'Admin' && $user_rol !== 'Jefe') {
                $response['message'] = 'Solo un Jefe o Administrador puede aprobar el cierre';
                break;
            }

            $db->beginTransaction();
            try {
                // Aprobar cierre: estado_id = 4 (Resuelto), pendiente_aprobacion = 0
                $query = "UPDATE tickets SET estado_id = 4, pendiente_aprobacion = 0, aprobado_por = :aprobado_por, fecha_aprobacion = NOW(), updated_at = NOW() WHERE codigo = :codigo";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':aprobado_por', $user_id);
                $stmt->bindParam(':codigo', $codigo);
                $stmt->execute();

                // Agregar comentario de aprobación como nota interna
                $comentario_texto = "✅ **TICKET APROBADO**\n" . ($comentario ? $comentario : "El cierre del ticket ha sido aprobado.");
                $query = "INSERT INTO ticket_comentarios (ticket_id, usuario_id, mensaje, tipo) VALUES (:ticket_id, :usuario_id, :mensaje, 'nota_interna')";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':ticket_id', $ticket['id']);
                $stmt->bindParam(':usuario_id', $user_id);
                $stmt->bindParam(':mensaje', $comentario_texto);
                $stmt->execute();
                $db->commit();

                // Notificar aprobacion
                EmailHelper::notifyTicketEvent('ticket_aprobado', [
                    'ticket_id'  => $ticket['id'],
                    'codigo'     => $codigo,
                    'comentario' => $comentario,
                ], $user_id, $db);

                $response['success'] = true;
                $response['message'] = 'Cierre aprobado correctamente';
            } catch(Exception $e) {
                $db->rollBack();
                $response['message'] = 'Error al aprobar el cierre: ' . $e->getMessage();
            }
            break;

        case 'rechazar_cierre':
            $codigo = $_POST['codigo'] ?? '';
            $comentario = $_POST['comentario'] ?? '';

            if(empty($codigo)) {
                $response['message'] = 'Código de ticket requerido';
                break;
            }

            if(empty($comentario)) {
                $response['message'] = 'Debe indicar el motivo del rechazo';
                break;
            }

            // Verificar que el ticket existe
            $stmt = $db->prepare("SELECT id, estado_id, pendiente_aprobacion FROM tickets WHERE codigo = ?");
            $stmt->execute([$codigo]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$ticket) {
                $response['message'] = 'Ticket no encontrado';
                break;
            }

            // Verificar que está pendiente de aprobación
            if(!$ticket['pendiente_aprobacion']) {
                $response['message'] = 'Este ticket no requiere aprobación';
                break;
            }

            // Solo Jefe o Administrador pueden rechazar
            $user_rol = $_SESSION['user_rol'] ?? '';
            if($user_rol !== 'Administrador' && $user_rol !== 'Admin' && $user_rol !== 'Jefe') {
                $response['message'] = 'Solo un Jefe o Administrador puede rechazar el cierre';
                break;
            }

            $db->beginTransaction();
            try {
                // Rechazar: estado_id = 5 (Rechazado), progreso = 90, pendiente_aprobacion = 0
                $query = "UPDATE tickets SET estado_id = 5, progreso = 90, pendiente_aprobacion = 0, fecha_resolucion = NULL, updated_at = NOW() WHERE codigo = :codigo";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':codigo', $codigo);
                $stmt->execute();

                // Agregar comentario de rechazo como nota interna
                $comentario_texto = "❌ **TICKET RECHAZADO**\n**Motivo:** " . $comentario;
                $query = "INSERT INTO ticket_comentarios (ticket_id, usuario_id, mensaje, tipo) VALUES (:ticket_id, :usuario_id, :mensaje, 'nota_interna')";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':ticket_id', $ticket['id']);
                $stmt->bindParam(':usuario_id', $user_id);
                $stmt->bindParam(':mensaje', $comentario_texto);
                $stmt->execute();
                $comentario_id = $db->lastInsertId();

                // Guardar evidencia opcional del rechazo como adjunto del comentario interno.
                if(isset($_FILES['archivo_rechazo']) && ($_FILES['archivo_rechazo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                    $archivo = $_FILES['archivo_rechazo'];

                    if(($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                        throw new Exception('No se pudo cargar el archivo de evidencia');
                    }

                    $max_size = 10 * 1024 * 1024; // 10MB
                    if(($archivo['size'] ?? 0) > $max_size) {
                        throw new Exception('La evidencia excede 10MB');
                    }

                    $allowed_mimes = [
                        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/plain'
                    ];

                    $mime = $archivo['type'] ?? '';
                    if(!in_array($mime, $allowed_mimes, true)) {
                        throw new Exception('Tipo de archivo no permitido para evidencia');
                    }

                    $stmtCodigo = $db->prepare("SELECT codigo FROM tickets WHERE id = ?");
                    $stmtCodigo->execute([$ticket['id']]);
                    $ticket_codigo = $stmtCodigo->fetchColumn();
                    if(!$ticket_codigo) {
                        throw new Exception('No se pudo obtener el codigo del ticket');
                    }

                    $upload_dir = '../uploads/comentarios/' . $ticket_codigo . '/';
                    if(!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
                        throw new Exception('No se pudo crear el directorio de evidencia');
                    }

                    $original_name = $archivo['name'] ?? 'evidencia';
                    $safe_name = time() . '_rechazo_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $original_name);
                    $target_path = $upload_dir . $safe_name;

                    if(!move_uploaded_file($archivo['tmp_name'], $target_path)) {
                        throw new Exception('No se pudo guardar la evidencia');
                    }

                    $stmtAdjunto = $db->prepare("INSERT INTO comentario_archivos (comentario_id, nombre_original, nombre_archivo, ruta, tamano, tipo_mime)
                                                 VALUES (:comentario_id, :nombre_original, :nombre_archivo, :ruta, :tamano, :tipo_mime)");
                    $stmtAdjunto->execute([
                        ':comentario_id' => $comentario_id,
                        ':nombre_original' => $original_name,
                        ':nombre_archivo' => $safe_name,
                        ':ruta' => 'uploads/comentarios/' . $ticket_codigo . '/' . $safe_name,
                        ':tamano' => $archivo['size'] ?? 0,
                        ':tipo_mime' => $mime
                    ]);
                }

                $db->commit();

                // Notificar rechazo
                EmailHelper::notifyTicketEvent('ticket_rechazado', [
                    'ticket_id' => $ticket['id'],
                    'codigo'    => $codigo,
                    'motivo'    => $comentario,
                ], $user_id, $db);

                $response['success'] = true;
                $response['message'] = 'Cierre rechazado. El ticket vuelve a En Atención con 90%.';
            } catch(Exception $e) {
                $db->rollBack();
                $response['message'] = 'Error al rechazar el cierre: ' . $e->getMessage();
            }
            break;

        case 'historial_progreso':
            $ticket_id = $_GET['ticket_id'] ?? '';

            if(empty($ticket_id)) {
                $response['message'] = 'ID de ticket requerido';
                break;
            }

            $query = "SELECT h.*, u.nombre_completo as usuario_nombre
                      FROM historial h
                      LEFT JOIN usuarios u ON h.usuario_id = u.id
                      WHERE h.ticket_id = :ticket_id AND h.campo_modificado = 'progreso'
                      ORDER BY h.created_at DESC
                      LIMIT 50";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':ticket_id', $ticket_id);
            $stmt->execute();
            $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Agregar evento de aprobacion del jefe/admin para mostrarlo en la linea de tiempo.
            $stmtAprob = $db->prepare("SELECT t.fecha_aprobacion, t.aprobado_por, u.nombre_completo as aprobado_por_nombre
                                       FROM tickets t
                                       LEFT JOIN usuarios u ON t.aprobado_por = u.id
                                       WHERE t.id = ? AND t.fecha_aprobacion IS NOT NULL");
            $stmtAprob->execute([$ticket_id]);
            $eventoAprob = $stmtAprob->fetch(PDO::FETCH_ASSOC);

            if ($eventoAprob) {
                $historial[] = [
                    'id' => 'aprobacion_' . $ticket_id,
                    'ticket_id' => $ticket_id,
                    'usuario_id' => $eventoAprob['aprobado_por'],
                    'accion' => 'Cierre aprobado',
                    'campo_modificado' => 'aprobacion',
                    'valor_anterior' => null,
                    'valor_nuevo' => 'Aprobado',
                    'created_at' => $eventoAprob['fecha_aprobacion'],
                    'usuario_nombre' => $eventoAprob['aprobado_por_nombre'] ?? 'Jefe/Administrador'
                ];
            }

            // Agregar eventos de rechazo (pueden existir varios en el tiempo).
            $stmtRechazos = $db->prepare("SELECT c.id, c.usuario_id, c.created_at, c.mensaje, u.nombre_completo as usuario_nombre
                                          FROM ticket_comentarios c
                                          LEFT JOIN usuarios u ON c.usuario_id = u.id
                                          WHERE c.ticket_id = ?
                                            AND c.tipo = 'nota_interna'
                                            AND c.mensaje LIKE '%TICKET RECHAZADO%'
                                          ORDER BY c.created_at DESC");
            $stmtRechazos->execute([$ticket_id]);
            $rechazos = $stmtRechazos->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rechazos as $rej) {
                $motivo = null;
                if (!empty($rej['mensaje']) && preg_match('/Motivo:\s*(.+)$/isu', $rej['mensaje'], $m)) {
                    $motivo = trim($m[1]);
                }

                $historial[] = [
                    'id' => 'rechazo_' . $rej['id'],
                    'ticket_id' => $ticket_id,
                    'usuario_id' => $rej['usuario_id'],
                    'accion' => 'Cierre rechazado',
                    'campo_modificado' => 'rechazo',
                    'valor_anterior' => null,
                    'valor_nuevo' => 'Rechazado',
                    'created_at' => $rej['created_at'],
                    'usuario_nombre' => $rej['usuario_nombre'] ?? 'Jefe/Administrador',
                    'motivo' => $motivo
                ];
            }

            // Agregar eventos de transferencias (todos los estados: pendiente, aprobada, rechazada).
            ensure_transferencias_table($db);
            $stmtTransfer = $db->prepare("SELECT tt.id, tt.ticket_id, tt.usuario_origen, tt.usuario_destino,
                                                 tt.solicitado_por, tt.aprobado_por, tt.motivo, tt.comentario_aprobacion,
                                                 tt.estado, tt.created_at, tt.updated_at,
                                                 uo.nombre_completo as usuario_origen_nombre,
                                                 ud.nombre_completo as usuario_destino_nombre,
                                                 us.nombre_completo as solicitado_por_nombre,
                                                 ua.nombre_completo as aprobado_por_nombre
                                          FROM ticket_transferencias tt
                                          LEFT JOIN usuarios uo ON uo.id = tt.usuario_origen
                                          LEFT JOIN usuarios ud ON ud.id = tt.usuario_destino
                                          LEFT JOIN usuarios us ON us.id = tt.solicitado_por
                                          LEFT JOIN usuarios ua ON ua.id = tt.aprobado_por
                                          WHERE tt.ticket_id = ?
                                          ORDER BY tt.created_at DESC");
            $stmtTransfer->execute([$ticket_id]);
            $transferencias = $stmtTransfer->fetchAll(PDO::FETCH_ASSOC);

            foreach ($transferencias as $tr) {
                $estado = $tr['estado'];
                switch ($estado) {
                    case 'aprobada':
                        $accion = 'Transferencia aprobada';
                        $campo = 'transferencia_aprobada';
                        $fechaEvento = $tr['updated_at'] ?: $tr['created_at'];
                        $usuarioEvento = $tr['aprobado_por'] ?: $tr['solicitado_por'];
                        $nombreEvento = $tr['aprobado_por_nombre'] ?: ($tr['solicitado_por_nombre'] ?: 'Jefe/Administrador');
                        break;
                    case 'rechazada':
                        $accion = 'Transferencia rechazada';
                        $campo = 'transferencia_rechazada';
                        $fechaEvento = $tr['updated_at'] ?: $tr['created_at'];
                        $usuarioEvento = $tr['aprobado_por'] ?: $tr['solicitado_por'];
                        $nombreEvento = $tr['aprobado_por_nombre'] ?: ($tr['solicitado_por_nombre'] ?: 'Jefe/Administrador');
                        break;
                    default: // pendiente
                        $accion = 'Transferencia solicitada';
                        $campo = 'transferencia_pendiente';
                        $fechaEvento = $tr['created_at'];
                        $usuarioEvento = $tr['solicitado_por'];
                        $nombreEvento = $tr['solicitado_por_nombre'] ?: 'Usuario';
                        break;
                }

                $historial[] = [
                    'id' => 'transferencia_' . $tr['id'],
                    'ticket_id' => $ticket_id,
                    'usuario_id' => $usuarioEvento,
                    'accion' => $accion,
                    'campo_modificado' => $campo,
                    'valor_anterior' => $tr['usuario_origen_nombre'] ?: 'Sin asignar',
                    'valor_nuevo' => $tr['usuario_destino_nombre'] ?: 'Sin destino',
                    'created_at' => $fechaEvento,
                    'usuario_nombre' => $nombreEvento,
                    'estado_transferencia' => $estado,
                    'motivo' => $tr['motivo'],
                    'comentario' => $tr['comentario_aprobacion']
                ];
            }

            usort($historial, function($a, $b) {
                return strtotime($b['created_at']) <=> strtotime($a['created_at']);
            });

            $response['data'] = $historial;
            $response['success'] = true;
            break;

        case 'obtener_transferencia_pendiente':
            ensure_transferencias_table($db);
            $ticket_id = intval($_GET['ticket_id'] ?? 0);
            if($ticket_id <= 0) {
                $response['message'] = 'ID de ticket requerido';
                break;
            }

            $stmt = $db->prepare("SELECT id, usuario_id, asignado_a FROM tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$ticket) {
                $response['message'] = 'Ticket no encontrado';
                break;
            }

            $user_rol = $_SESSION['user_rol'] ?? '';
            $puede_ver = ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe' ||
                          $ticket['usuario_id'] == $user_id || $ticket['asignado_a'] == $user_id);
            if(!$puede_ver) {
                $response['message'] = 'No tiene permisos para ver transferencias';
                break;
            }

            $stmt = $db->prepare("SELECT tt.*,
                                         us.nombre_completo as solicitado_por_nombre,
                                         ud.nombre_completo as usuario_destino_nombre,
                                         ua.nombre_completo as aprobado_por_nombre
                                  FROM ticket_transferencias tt
                                  LEFT JOIN usuarios us ON us.id = tt.solicitado_por
                                  LEFT JOIN usuarios ud ON ud.id = tt.usuario_destino
                                  LEFT JOIN usuarios ua ON ua.id = tt.aprobado_por
                                  WHERE tt.ticket_id = ? AND tt.estado = 'pendiente'
                                  ORDER BY tt.created_at DESC
                                  LIMIT 1");
            $stmt->execute([$ticket_id]);
            $response['data'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            $response['success'] = true;
            break;

        case 'solicitar_transferencia':
            ensure_transferencias_table($db);
            $ticket_id = intval($_POST['ticket_id'] ?? 0);
            $usuario_destino = intval($_POST['usuario_destino'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? '');

            if($ticket_id <= 0 || $usuario_destino <= 0) {
                $response['message'] = 'Datos incompletos para solicitar transferencia';
                break;
            }

            $stmt = $db->prepare("SELECT id, codigo, departamento_id, actividad_id, estado_id, usuario_id, asignado_a
                                  FROM tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$ticket) {
                $response['message'] = 'Ticket no encontrado';
                break;
            }

            if(!in_array((int)$ticket['estado_id'], [1, 2, 5], true)) {
                $response['message'] = 'Solo se puede transferir tickets en Abierto, En Atencion o Rechazado';
                break;
            }

            $user_rol = $_SESSION['user_rol'] ?? '';
            if($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe') {
                $response['message'] = 'Use transferencia directa para este rol';
                break;
            }

            $es_relacionado = ($ticket['usuario_id'] == $user_id || $ticket['asignado_a'] == $user_id);
            if(!$es_relacionado) {
                $response['message'] = 'No tiene permisos para solicitar transferencia de este ticket';
                break;
            }

            $stmt = $db->prepare("SELECT id, activo, departamento_id FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_destino]);
            $destino = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$destino || (int)$destino['activo'] !== 1) {
                $response['message'] = 'Usuario destino no valido';
                break;
            }

            // Verificar que el destino pertenezca a algún departamento vinculado a la actividad del ticket
            // (soporta actividades compartidas entre múltiples departamentos)
            $stmtDepts = $db->prepare("
                SELECT COUNT(*) FROM actividades_departamentos
                WHERE actividad_id = :actividad_id
                  AND departamento_id = :departamento_destino
            ");
            $stmtDepts->execute([
                ':actividad_id'         => $ticket['actividad_id'],
                ':departamento_destino' => $destino['departamento_id']
            ]);
            $perteneceActividad = (int)$stmtDepts->fetchColumn() > 0;
            $mismoDepto = (int)$destino['departamento_id'] === (int)$ticket['departamento_id'];

            if(!$perteneceActividad && !$mismoDepto) {
                $response['message'] = 'Solo puede transferir a usuarios del departamento vinculado a esta actividad';
                break;
            }

            $stmt = $db->prepare("SELECT id FROM ticket_transferencias WHERE ticket_id = ? AND estado = 'pendiente' LIMIT 1");
            $stmt->execute([$ticket_id]);
            if($stmt->fetch()) {
                $response['message'] = 'Ya existe una solicitud de transferencia pendiente para este ticket';
                break;
            }

            $db->beginTransaction();
            try {
                $stmt = $db->prepare("INSERT INTO ticket_transferencias
                                      (ticket_id, usuario_origen, usuario_destino, solicitado_por, motivo, estado)
                                      VALUES (:ticket_id, :usuario_origen, :usuario_destino, :solicitado_por, :motivo, 'pendiente')");
                $stmt->execute([
                    ':ticket_id' => $ticket_id,
                    ':usuario_origen' => $ticket['asignado_a'] ?: null,
                    ':usuario_destino' => $usuario_destino,
                    ':solicitado_por' => $user_id,
                    ':motivo' => $motivo ?: null
                ]);

                // Obtener nombre del jefe/admin del departamento
                $stmtJefe = $db->prepare("SELECT u.nombre_completo FROM usuarios u
                                          INNER JOIN roles r ON r.id = u.rol_id
                                          WHERE u.departamento_id = ? AND r.nombre IN ('Jefe','Administrador','Admin') AND u.activo = 1
                                          ORDER BY r.nombre ASC LIMIT 1");
                $stmtJefe->execute([$ticket['departamento_id']]);
                $jefeRow = $stmtJefe->fetch(PDO::FETCH_ASSOC);
                $jefeNombre = $jefeRow ? $jefeRow['nombre_completo'] : 'Jefe de área';

                // Obtener nombre del usuario destino
                $stmtDest = $db->prepare("SELECT nombre_completo FROM usuarios WHERE id = ? LIMIT 1");
                $stmtDest->execute([$usuario_destino]);
                $destRow = $stmtDest->fetch(PDO::FETCH_ASSOC);
                $destNombre = $destRow ? $destRow['nombre_completo'] : 'nuevo responsable';

                $nota = "SOLICITUD DE TRANSFERENCIA\nEstimado {$jefeNombre}, se solicita transferir el ticket {$ticket['codigo']} al responsable \"{$destNombre}\"."
                      . ($motivo ? "\nMotivo: " . $motivo : '');
                $stmt = $db->prepare("INSERT INTO ticket_comentarios (ticket_id, usuario_id, mensaje, tipo)
                                      VALUES (?, ?, ?, 'nota_interna')");
                $stmt->execute([$ticket_id, $user_id, $nota]);

                $db->commit();

                // Notificar solicitud de transferencia
                EmailHelper::notifyTicketEvent('transferencia_solicitada', [
                    'ticket_id'      => $ticket_id,
                    'codigo'         => $ticket['codigo'],
                    'destino_nombre' => $destNombre,
                    'motivo'         => $motivo,
                ], $user_id, $db);

                $response['success'] = true;
                $response['message'] = 'Solicitud enviada. Debe ser aprobada por Jefe o Administrador.';
            } catch(Exception $e) {
                $db->rollBack();
                $response['message'] = 'Error al solicitar transferencia: ' . $e->getMessage();
            }
            break;

        case 'transferir_ticket_directo':
            ensure_transferencias_table($db);
            $ticket_id = intval($_POST['ticket_id'] ?? 0);
            $usuario_destino = intval($_POST['usuario_destino'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? '');

            if($ticket_id <= 0 || $usuario_destino <= 0) {
                $response['message'] = 'Datos incompletos para transferir';
                break;
            }

            $user_rol = $_SESSION['user_rol'] ?? '';
            if($user_rol !== 'Administrador' && $user_rol !== 'Admin' && $user_rol !== 'Jefe') {
                $response['message'] = 'Solo Jefe o Administrador puede transferir directamente';
                break;
            }

            $stmt = $db->prepare("SELECT id, codigo, departamento_id, actividad_id, estado_id, asignado_a
                                  FROM tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$ticket) {
                $response['message'] = 'Ticket no encontrado';
                break;
            }

            if(!in_array((int)$ticket['estado_id'], [1, 2, 5], true)) {
                $response['message'] = 'Solo se puede transferir tickets en Abierto, En Atencion o Rechazado';
                break;
            }

            $stmt = $db->prepare("SELECT id, activo, departamento_id FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_destino]);
            $destino = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$destino || (int)$destino['activo'] !== 1) {
                $response['message'] = 'Usuario destino no valido';
                break;
            }

            // Verificar que el destino pertenezca a algún departamento vinculado a la actividad del ticket
            // (soporta actividades compartidas entre múltiples departamentos)
            $stmtDepts = $db->prepare("
                SELECT COUNT(*) FROM actividades_departamentos
                WHERE actividad_id = :actividad_id
                  AND departamento_id = :departamento_destino
            ");
            $stmtDepts->execute([
                ':actividad_id'         => $ticket['actividad_id'],
                ':departamento_destino' => $destino['departamento_id']
            ]);
            $perteneceActividad = (int)$stmtDepts->fetchColumn() > 0;
            $mismoDepto = (int)$destino['departamento_id'] === (int)$ticket['departamento_id'];

            if(!$perteneceActividad && !$mismoDepto) {
                $response['message'] = 'Solo puede transferir a usuarios del departamento vinculado a esta actividad';
                break;
            }

            $db->beginTransaction();
            try {
                $origen_nombre = get_user_display_name($db, $ticket['asignado_a'], 'Sin asignar');
                $destino_nombre = get_user_display_name($db, $usuario_destino, 'Usuario destino');

                $stmt = $db->prepare("UPDATE tickets
                                      SET asignado_a = :asignado_a,
                                          estado_id = CASE WHEN estado_id = 5 THEN 2 ELSE estado_id END,
                                          pendiente_aprobacion = CASE WHEN estado_id = 5 THEN 0 ELSE pendiente_aprobacion END,
                                          updated_at = NOW()
                                      WHERE id = :ticket_id");
                $stmt->execute([
                    ':asignado_a' => $usuario_destino,
                    ':ticket_id' => $ticket_id
                ]);

                $stmt = $db->prepare("UPDATE ticket_transferencias
                                      SET estado = 'rechazada', aprobado_por = :aprobado_por, comentario_aprobacion = 'Reemplazada por transferencia directa', updated_at = NOW()
                                      WHERE ticket_id = :ticket_id AND estado = 'pendiente'");
                $stmt->execute([
                    ':aprobado_por' => $user_id,
                    ':ticket_id' => $ticket_id
                ]);

                $stmt = $db->prepare("INSERT INTO ticket_transferencias
                                      (ticket_id, usuario_origen, usuario_destino, solicitado_por, motivo, estado, aprobado_por, comentario_aprobacion)
                                      VALUES (:ticket_id, :usuario_origen, :usuario_destino, :solicitado_por, :motivo, 'aprobada', :aprobado_por, :comentario)");
                $stmt->execute([
                    ':ticket_id' => $ticket_id,
                    ':usuario_origen' => $ticket['asignado_a'] ?: null,
                    ':usuario_destino' => $usuario_destino,
                    ':solicitado_por' => $user_id,
                    ':motivo' => $motivo ?: null,
                    ':aprobado_por' => $user_id,
                    ':comentario' => 'Transferencia directa de Jefe/Administrador'
                ]);

                $nota = "TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador."
                      . "\nDe: " . $origen_nombre
                      . "\nA: " . $destino_nombre
                      . ($motivo ? "\nMotivo: " . $motivo : '');
                $stmt = $db->prepare("INSERT INTO ticket_comentarios (ticket_id, usuario_id, mensaje, tipo)
                                      VALUES (?, ?, ?, 'nota_interna')");
                $stmt->execute([$ticket_id, $user_id, $nota]);

                $db->commit();

                // Notificar transferencia directa
                EmailHelper::notifyTicketEvent('transferencia_directa', [
                    'ticket_id'      => $ticket_id,
                    'codigo'         => $ticket['codigo'],
                    'origen_nombre'  => $origen_nombre,
                    'destino_nombre' => $destino_nombre,
                    'motivo'         => $motivo,
                ], $user_id, $db);

                $response['success'] = true;
                $response['message'] = 'Ticket transferido correctamente';
            } catch(Exception $e) {
                $db->rollBack();
                $response['message'] = 'Error al transferir ticket: ' . $e->getMessage();
            }
            break;

        case 'responder_transferencia':
            ensure_transferencias_table($db);
            $transferencia_id = intval($_POST['transferencia_id'] ?? 0);
            $decision = strtolower(trim($_POST['decision'] ?? ''));
            $comentario = trim($_POST['comentario'] ?? '');

            if($transferencia_id <= 0 || !in_array($decision, ['aprobar', 'rechazar'], true)) {
                $response['message'] = 'Datos incompletos para responder transferencia';
                break;
            }

            $user_rol = $_SESSION['user_rol'] ?? '';
            if($user_rol !== 'Administrador' && $user_rol !== 'Admin' && $user_rol !== 'Jefe') {
                $response['message'] = 'Solo Jefe o Administrador puede responder solicitudes';
                break;
            }

            $stmt = $db->prepare("SELECT tt.*, t.codigo, t.estado_id,
                                         uo.nombre_completo as usuario_origen_nombre,
                                         ud.nombre_completo as usuario_destino_nombre
                                  FROM ticket_transferencias tt
                                  INNER JOIN tickets t ON t.id = tt.ticket_id
                                  LEFT JOIN usuarios uo ON uo.id = tt.usuario_origen
                                  LEFT JOIN usuarios ud ON ud.id = tt.usuario_destino
                                  WHERE tt.id = ?");
            $stmt->execute([$transferencia_id]);
            $transfer = $stmt->fetch(PDO::FETCH_ASSOC);
            if(!$transfer) {
                $response['message'] = 'Solicitud de transferencia no encontrada';
                break;
            }

            if($transfer['estado'] !== 'pendiente') {
                $response['message'] = 'Esta solicitud ya fue procesada';
                break;
            }

            $db->beginTransaction();
            try {
                if($decision === 'aprobar') {
                    $stmt = $db->prepare("UPDATE tickets
                                          SET asignado_a = :asignado_a,
                                              estado_id = CASE WHEN estado_id = 5 THEN 2 ELSE estado_id END,
                                              pendiente_aprobacion = CASE WHEN estado_id = 5 THEN 0 ELSE pendiente_aprobacion END,
                                              updated_at = NOW()
                                          WHERE id = :ticket_id");
                    $stmt->execute([
                        ':asignado_a' => $transfer['usuario_destino'],
                        ':ticket_id' => $transfer['ticket_id']
                    ]);

                    $stmt = $db->prepare("UPDATE ticket_transferencias
                                          SET estado = 'aprobada', aprobado_por = :aprobado_por, comentario_aprobacion = :comentario, updated_at = NOW()
                                          WHERE id = :id");
                    $stmt->execute([
                        ':aprobado_por' => $user_id,
                        ':comentario' => $comentario ?: 'Solicitud aprobada',
                        ':id' => $transferencia_id
                    ]);

                    $origen_nombre = $transfer['usuario_origen_nombre'] ?: 'Sin asignar';
                    $destino_nombre = $transfer['usuario_destino_nombre'] ?: get_user_display_name($db, $transfer['usuario_destino'], 'Usuario destino');
                    $nota = "TICKET TRANSFERIDO\nTransferencia aprobada por Jefe/Administrador."
                          . "\nDe: " . $origen_nombre
                          . "\nA: " . $destino_nombre
                          . ($comentario ? "\nComentario: " . $comentario : '');
                    $stmt = $db->prepare("INSERT INTO ticket_comentarios (ticket_id, usuario_id, mensaje, tipo)
                                          VALUES (?, ?, ?, 'nota_interna')");
                    $stmt->execute([$transfer['ticket_id'], $user_id, $nota]);

                    $response['success'] = true;
                    $response['message'] = 'Transferencia aprobada y ticket reasignado';
                } else {
                    $stmt = $db->prepare("UPDATE ticket_transferencias
                                          SET estado = 'rechazada', aprobado_por = :aprobado_por, comentario_aprobacion = :comentario, updated_at = NOW()
                                          WHERE id = :id");
                    $stmt->execute([
                        ':aprobado_por' => $user_id,
                        ':comentario' => $comentario ?: 'Solicitud rechazada',
                        ':id' => $transferencia_id
                    ]);

                    $nota = "SOLICITUD DE TRANSFERENCIA RECHAZADA\nSolicitud rechazada por Jefe/Administrador."
                          . ($comentario ? "\nComentario: " . $comentario : '');
                    $stmt = $db->prepare("INSERT INTO ticket_comentarios (ticket_id, usuario_id, mensaje, tipo)
                                          VALUES (?, ?, ?, 'nota_interna')");
                    $stmt->execute([$transfer['ticket_id'], $user_id, $nota]);

                    $response['success'] = true;
                    $response['message'] = 'Solicitud de transferencia rechazada';
                }

                $db->commit();

                // Notificar respuesta a transferencia
                $emailEvent = ($decision === 'aprobar') ? 'transferencia_aprobada' : 'transferencia_rechazada';
                EmailHelper::notifyTicketEvent($emailEvent, [
                    'ticket_id'      => $transfer['ticket_id'],
                    'codigo'         => $transfer['codigo'],
                    'destino_nombre' => $transfer['usuario_destino_nombre'] ?? '',
                    'comentario'     => $comentario,
                ], $user_id, $db);

            } catch(Exception $e) {
                $db->rollBack();
                $response['message'] = 'Error al responder transferencia: ' . $e->getMessage();
            }
            break;

        case 'detalle':
            $codigo = $_GET['codigo'] ?? '';
            if(empty($codigo)) {
                $response['message'] = 'Código requerido';
                break;
            }
            
            $query = "SELECT t.*, 
                      e.nombre as estado, e.color as estado_color,
                      p.nombre as prioridad, p.color as prioridad_color,
                      a.nombre as area,
                      act.nombre as actividad,
                      ca.nombre as canal,
                      tf.nombre as tipo_falla,
                      ub.nombre as ubicacion,
                      eq.nombre as equipo,
                      ce.codigo as codigo_equipo_str,
                      u1.nombre_completo as creador,
                      u2.nombre_completo as asignado
                      FROM tickets t
                      LEFT JOIN estados e ON t.estado_id = e.id
                      LEFT JOIN prioridades p ON t.prioridad_id = p.id
                      LEFT JOIN areas a ON t.area_id = a.id
                      LEFT JOIN actividades act ON t.actividad_id = act.id
                      LEFT JOIN canales_atencion ca ON t.canal_atencion_id = ca.id
                      LEFT JOIN tipos_falla tf ON t.tipo_falla_id = tf.id
                      LEFT JOIN ubicaciones ub ON t.ubicacion_id = ub.id
                      LEFT JOIN equipos eq ON t.equipo_id = eq.id
                      LEFT JOIN codigos_equipo ce ON t.codigo_equipo_id = ce.id
                      LEFT JOIN usuarios u1 ON t.usuario_id = u1.id
                      LEFT JOIN usuarios u2 ON t.asignado_a = u2.id
                      WHERE t.codigo = :codigo";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':codigo', $codigo);
            $stmt->execute();
            $response['data'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($response['data']) {
                // Obtener comentarios
                $query = "SELECT c.*, u.nombre_completo as usuario
                          FROM ticket_comentarios c
                          INNER JOIN usuarios u ON c.usuario_id = u.id
                          WHERE c.ticket_id = :ticket_id
                          ORDER BY c.created_at ASC";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':ticket_id', $response['data']['id']);
                $stmt->execute();
                $response['data']['comentarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Obtener historial
                $query = "SELECT h.*, u.nombre_completo as usuario
                          FROM historial h
                          LEFT JOIN usuarios u ON h.usuario_id = u.id
                          WHERE h.ticket_id = :ticket_id
                          ORDER BY h.created_at DESC
                          LIMIT 20";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':ticket_id', $response['data']['id']);
                $stmt->execute();
                $response['data']['historial'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response['success'] = true;
            } else {
                $response['message'] = 'Ticket no encontrado';
            }
            break;

        case 'eliminar':
            $ticket_id = $_POST['ticket_id'] ?? '';

            if(empty($ticket_id)) {
                $response['message'] = 'ID de ticket requerido';
                break;
            }

            // Verificar que el ticket existe
            $stmt = $db->prepare("SELECT id, codigo, usuario_id, estado_id, pendiente_aprobacion FROM tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$ticket) {
                $response['message'] = 'Ticket no encontrado';
                break;
            }

            // No permitir eliminar tickets resueltos (aprobados)
            if($ticket['estado_id'] == 4 && $ticket['pendiente_aprobacion'] == 0) {
                $response['message'] = 'No se puede eliminar un ticket resuelto';
                break;
            }

            // Verificar permisos: Admin/Jefe pueden eliminar cualquiera, Usuario solo los suyos
            $user_rol = $_SESSION['user_rol'] ?? '';
            $puede_eliminar = ($user_rol === 'Administrador' || $user_rol === 'Admin' || $user_rol === 'Jefe' || $ticket['usuario_id'] == $user_id);

            if(!$puede_eliminar) {
                $response['message'] = 'No tiene permisos para eliminar este ticket';
                break;
            }

            $db->beginTransaction();
            try {
                // Eliminar archivos adjuntos físicos
                $stmt = $db->prepare("SELECT ruta FROM ticket_archivos WHERE ticket_id = ?");
                $stmt->execute([$ticket_id]);
                $archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach($archivos as $archivo) {
                    $ruta_completa = '../' . $archivo['ruta'];
                    if(file_exists($ruta_completa)) {
                        unlink($ruta_completa);
                    }
                }

                // Eliminar registros relacionados
                $db->prepare("DELETE FROM ticket_archivos WHERE ticket_id = ?")->execute([$ticket_id]);
                $db->prepare("DELETE FROM ticket_comentarios WHERE ticket_id = ?")->execute([$ticket_id]);
                $db->prepare("DELETE FROM historial WHERE ticket_id = ?")->execute([$ticket_id]);

                // Eliminar el ticket
                $db->prepare("DELETE FROM tickets WHERE id = ?")->execute([$ticket_id]);

                $db->commit();
                $response['success'] = true;
                $response['message'] = 'Ticket eliminado correctamente';
            } catch(Exception $e) {
                $db->rollBack();
                $response['message'] = 'Error al eliminar el ticket: ' . $e->getMessage();
            }
            break;

        case 'conteos_sidebar':
            $uid     = (int)($_SESSION['user_id']        ?? 0);
            $rol     = $_SESSION['user_rol']              ?? 'Usuario';
            $dpto    = (int)($_SESSION['departamento_id'] ?? 0);
            $esSA    = ($rol === 'Administrador' || $rol === 'Admin') && $dpto === 1;
            $esAdmin = ($rol === 'Administrador' || $rol === 'Admin' || $rol === 'Jefe');

            // ── TODOS (según rol) ────────────────────────────────────────
            if ($esSA) {
                $stmtTodos = $db->query("SELECT COUNT(*) FROM tickets");
            } elseif ($esAdmin) {
                $stmtTodos = $db->prepare("SELECT COUNT(*) FROM tickets t
                    WHERE (EXISTS(SELECT 1 FROM actividades_departamentos ad WHERE ad.actividad_id=t.actividad_id AND ad.departamento_id=:d1)
                           OR t.usuario_id=:u1 OR t.asignado_a=:u2
                           OR EXISTS(SELECT 1 FROM ticket_transferencias tp INNER JOIN usuarios up ON up.id=tp.usuario_destino WHERE tp.ticket_id=t.id AND tp.estado='pendiente' AND up.departamento_id=:d2))");
                $stmtTodos->execute([':d1'=>$dpto,':u1'=>$uid,':u2'=>$uid,':d2'=>$dpto]);
            } else {
                $stmtTodos = $db->prepare("SELECT COUNT(*) FROM tickets t WHERE t.usuario_id=:u1 OR t.asignado_a=:u2");
                $stmtTodos->execute([':u1'=>$uid,':u2'=>$uid]);
            }
            $cntTodos = (int)$stmtTodos->fetchColumn();

            // ── MIS TICKETS (creados por mí) ─────────────────────────────
            $stmtMis = $db->prepare("SELECT COUNT(*) FROM tickets WHERE usuario_id=:uid");
            $stmtMis->execute([':uid'=>$uid]);
            $cntMis = (int)$stmtMis->fetchColumn();

            // ── ASIGNADOS A MÍ ───────────────────────────────────────────
            $stmtAsig = $db->prepare("SELECT COUNT(*) FROM tickets WHERE asignado_a=:uid");
            $stmtAsig->execute([':uid'=>$uid]);
            $cntAsig = (int)$stmtAsig->fetchColumn();

            // ── NOTIFICACIONES NO LEÍDAS ─────────────────────────────────
            $stmtNotif = $db->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id=:uid AND leida=0");
            $stmtNotif->execute([':uid'=>$uid]);
            $cntNotif = (int)$stmtNotif->fetchColumn();

            $response['success'] = true;
            $response['data'] = [
                'todos'         => $cntTodos,
                'mis'           => $cntMis,
                'asignados'     => $cntAsig,
                'notificaciones'=> $cntNotif,
            ];
            break;

        default:
            $response['message'] = 'Acción no válida';
    }
} catch(Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>

