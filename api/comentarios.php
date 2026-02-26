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
$user_name = $_SESSION['user_name'] ?? 'Usuario';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$response = ['success' => false, 'message' => ''];

try {
    switch($action) {
        case 'listar':
            $ticket_id = $_GET['ticket_id'] ?? '';

            if(empty($ticket_id)) {
                $response['message'] = 'ID de ticket requerido';
                break;
            }

            // Obtener comentarios con información del usuario
            $query = "SELECT c.*,
                      u.nombre_completo as usuario_nombre,
                      u.email as usuario_email
                      FROM ticket_comentarios c
                      INNER JOIN usuarios u ON c.usuario_id = u.id
                      WHERE c.ticket_id = :ticket_id
                      ORDER BY c.created_at ASC";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':ticket_id', $ticket_id);
            $stmt->execute();
            $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener archivos adjuntos de cada comentario
            foreach($comentarios as &$comentario) {
                $stmt = $db->prepare("SELECT * FROM comentario_archivos WHERE comentario_id = ?");
                $stmt->execute([$comentario['id']]);
                $comentario['archivos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $response['success'] = true;
            $response['data'] = $comentarios;
            $response['current_user_id'] = $user_id;
            break;

        case 'crear':
            $ticket_id = $_POST['ticket_id'] ?? '';
            $mensaje = $_POST['mensaje'] ?? '';
            $tipo = $_POST['tipo'] ?? 'comentario';

            if(empty($ticket_id) || empty($mensaje)) {
                $response['message'] = 'Ticket ID y mensaje son requeridos';
                break;
            }

            // Verificar que el ticket existe
            $stmt = $db->prepare("SELECT id, codigo FROM tickets WHERE id = ?");
            $stmt->execute([$ticket_id]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$ticket) {
                $response['message'] = 'Ticket no encontrado';
                break;
            }

            $db->beginTransaction();

            try {
                // Insertar comentario
                $query = "INSERT INTO ticket_comentarios (ticket_id, usuario_id, mensaje, tipo)
                          VALUES (:ticket_id, :usuario_id, :mensaje, :tipo)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':ticket_id', $ticket_id);
                $stmt->bindParam(':usuario_id', $user_id);
                $stmt->bindParam(':mensaje', $mensaje);
                $stmt->bindParam(':tipo', $tipo);
                $stmt->execute();

                $comentario_id = $db->lastInsertId();

                // Procesar archivos adjuntos
                $archivos_subidos = [];
                $max_size = 10 * 1024 * 1024; // 10MB

                if(isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {
                    $upload_dir = '../uploads/comentarios/' . $ticket['codigo'] . '/';

                    if(!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $files = $_FILES['archivos'];
                    $file_count = count($files['name']);

                    for($i = 0; $i < $file_count; $i++) {
                        if($files['error'][$i] === UPLOAD_ERR_OK) {
                            $file_size = $files['size'][$i];

                            if($file_size > $max_size) {
                                continue;
                            }

                            $original_name = $files['name'][$i];
                            $extension = pathinfo($original_name, PATHINFO_EXTENSION);
                            $safe_name = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $original_name);
                            $file_path = $upload_dir . $safe_name;

                            if(move_uploaded_file($files['tmp_name'][$i], $file_path)) {
                                $query = "INSERT INTO comentario_archivos (comentario_id, nombre_original, nombre_archivo, ruta, tamano, tipo_mime)
                                          VALUES (:comentario_id, :nombre_original, :nombre_archivo, :ruta, :tamano, :tipo_mime)";
                                $stmt = $db->prepare($query);
                                $stmt->execute([
                                    ':comentario_id' => $comentario_id,
                                    ':nombre_original' => $original_name,
                                    ':nombre_archivo' => $safe_name,
                                    ':ruta' => 'uploads/comentarios/' . $ticket['codigo'] . '/' . $safe_name,
                                    ':tamano' => $file_size,
                                    ':tipo_mime' => $files['type'][$i]
                                ]);

                                $archivos_subidos[] = [
                                    'id' => $db->lastInsertId(),
                                    'nombre_original' => $original_name,
                                    'ruta' => 'uploads/comentarios/' . $ticket['codigo'] . '/' . $safe_name,
                                    'tamano' => $file_size,
                                    'tipo_mime' => $files['type'][$i]
                                ];
                            }
                        }
                    }
                }

                $db->commit();

                // Notificar por correo
                EmailHelper::notifyTicketEvent('comentario_agregado', [
                    'ticket_id'  => $ticket_id,
                    'codigo'     => $ticket['codigo'],
                    'comentario' => $mensaje,
                ], $user_id, $db);

                // Obtener datos del comentario creado para devolver
                $response['success'] = true;
                $response['message'] = 'Comentario agregado correctamente';
                $response['comentario'] = [
                    'id' => $comentario_id,
                    'ticket_id' => $ticket_id,
                    'usuario_id' => $user_id,
                    'usuario_nombre' => $user_name,
                    'mensaje' => $mensaje,
                    'tipo' => $tipo,
                    'created_at' => date('Y-m-d H:i:s'),
                    'archivos' => $archivos_subidos
                ];

            } catch(Exception $e) {
                $db->rollBack();
                $response['message'] = 'Error al crear comentario: ' . $e->getMessage();
            }
            break;

        case 'eliminar':
            $comentario_id = $_POST['comentario_id'] ?? '';

            if(empty($comentario_id)) {
                $response['message'] = 'ID de comentario requerido';
                break;
            }

            // Verificar que el comentario existe y obtener estado del ticket
            $stmt = $db->prepare("SELECT c.*, t.codigo, t.estado_id, t.pendiente_aprobacion
                                  FROM ticket_comentarios c
                                  INNER JOIN tickets t ON c.ticket_id = t.id
                                  WHERE c.id = ?");
            $stmt->execute([$comentario_id]);
            $comentario = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$comentario) {
                $response['message'] = 'Comentario no encontrado';
                break;
            }

            // No permitir eliminar notas internas (aprobaciones/rechazos del sistema)
            if($comentario['tipo'] === 'nota_interna') {
                $response['message'] = 'Las notas de verificación no se pueden eliminar';
                break;
            }

            // Bloquear eliminacion cuando el ticket ya entro al flujo de verificacion
            // o ya fue revisado por Jefe/Administrador (aprobado o rechazado).
            $en_flujo_verificacion = (
                (int)$comentario['pendiente_aprobacion'] === 1 ||
                (int)$comentario['estado_id'] === 4 ||
                (int)$comentario['estado_id'] === 5
            );
            if($en_flujo_verificacion) {
                $response['message'] = 'No se pueden eliminar mensajes cuando el ticket ya paso por verificacion/aprobacion';
                break;
            }

            $user_rol = $_SESSION['user_rol'] ?? '';
            if($comentario['usuario_id'] != $user_id && $user_rol !== 'Administrador' && $user_rol !== 'Admin') {
                $response['message'] = 'No tiene permisos para eliminar este comentario';
                break;
            }

            // Eliminar archivos físicos
            $stmt = $db->prepare("SELECT ruta FROM comentario_archivos WHERE comentario_id = ?");
            $stmt->execute([$comentario_id]);
            $archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach($archivos as $archivo) {
                $ruta_completa = '../' . $archivo['ruta'];
                if(file_exists($ruta_completa)) {
                    unlink($ruta_completa);
                }
            }

            // Eliminar comentario (los archivos se eliminan en cascada)
            $stmt = $db->prepare("DELETE FROM ticket_comentarios WHERE id = ?");
            if($stmt->execute([$comentario_id])) {
                $response['success'] = true;
                $response['message'] = 'Comentario eliminado correctamente';
            } else {
                $response['message'] = 'Error al eliminar el comentario';
            }
            break;

        default:
            $response['message'] = 'Acción no válida';
    }
} catch(Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
