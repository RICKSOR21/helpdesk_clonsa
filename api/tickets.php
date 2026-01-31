<?php
header('Content-Type: application/json');
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

$response = ['success' => false, 'message' => ''];

try {
    switch($action) {
        case 'listar':
            $query = "SELECT t.*, 
                      e.nombre as estado, e.color as estado_color,
                      act.nombre as actividad, act.color as actividad_color,
                      ub.nombre as ubicacion,
                      eq.nombre as equipo,
                      u1.nombre_completo as creador,
                      u2.nombre_completo as asignado
                      FROM tickets t
                      LEFT JOIN estados e ON t.estado_id = e.id
                      LEFT JOIN actividades act ON t.actividad_id = act.id
                      LEFT JOIN ubicaciones ub ON t.ubicacion_id = ub.id
                      LEFT JOIN equipos eq ON t.equipo_id = eq.id
                      LEFT JOIN usuarios u1 ON t.usuario_id = u1.id
                      LEFT JOIN usuarios u2 ON t.asignado_a = u2.id
                      ORDER BY t.created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['success'] = true;
            break;
            
        case 'crear':
            $titulo = $_POST['titulo'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $canal_id = $_POST['canal_atencion_id'] ?? null;
            $actividad_id = $_POST['actividad_id'] ?? null;
            $tipo_falla_id = $_POST['tipo_falla_id'] ?? null;
            $ubicacion_id = $_POST['ubicacion_id'] ?? null;
            $equipo_id = $_POST['equipo_id'] ?? null;
            $codigo_equipo_id = $_POST['codigo_equipo_id'] ?? null;
            $prioridad_id = $_POST['prioridad_id'] ?? 2;
            $area_id = $_POST['area_id'] ?? null;
            
            if(empty($titulo) || empty($descripcion)) {
                $response['message'] = 'Título y descripción son requeridos';
                break;
            }
            
            $query = "INSERT INTO tickets (titulo, descripcion, usuario_id, area_id, prioridad_id,
                      canal_atencion_id, actividad_id, tipo_falla_id, ubicacion_id, equipo_id, codigo_equipo_id)
                      VALUES (:titulo, :descripcion, :usuario_id, :area_id, :prioridad_id,
                      :canal_id, :actividad_id, :tipo_falla_id, :ubicacion_id, :equipo_id, :codigo_equipo_id)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':titulo', $titulo);
            $stmt->bindParam(':descripcion', $descripcion);
            $stmt->bindParam(':usuario_id', $user_id);
            $stmt->bindParam(':area_id', $area_id);
            $stmt->bindParam(':prioridad_id', $prioridad_id);
            $stmt->bindParam(':canal_id', $canal_id);
            $stmt->bindParam(':actividad_id', $actividad_id);
            $stmt->bindParam(':tipo_falla_id', $tipo_falla_id);
            $stmt->bindParam(':ubicacion_id', $ubicacion_id);
            $stmt->bindParam(':equipo_id', $equipo_id);
            $stmt->bindParam(':codigo_equipo_id', $codigo_equipo_id);
            
            if($stmt->execute()) {
                $ticket_id = $db->lastInsertId();
                $query = "SELECT codigo FROM tickets WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':id', $ticket_id);
                $stmt->execute();
                $codigo = $stmt->fetch(PDO::FETCH_ASSOC)['codigo'];
                
                $response['success'] = true;
                $response['message'] = 'Ticket creado exitosamente';
                $response['codigo'] = $codigo;
            } else {
                $response['message'] = 'Error al crear ticket';
            }
            break;
            
        case 'actualizar_progreso':
            $codigo = $_POST['codigo'] ?? '';
            $progreso = $_POST['progreso'] ?? 0;
            
            if(empty($codigo)) {
                $response['message'] = 'Código de ticket requerido';
                break;
            }
            
            $query = "UPDATE tickets SET progreso = :progreso WHERE codigo = :codigo";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':progreso', $progreso);
            $stmt->bindParam(':codigo', $codigo);
            
            if($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Progreso actualizado';
            } else {
                $response['message'] = 'Error al actualizar progreso';
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
                          FROM comentarios c
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
            
        default:
            $response['message'] = 'Acción no válida';
    }
} catch(Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
