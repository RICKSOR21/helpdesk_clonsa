<?php
// api/catalogos.php
header('Content-Type: application/json');
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$tipo = $_GET['tipo'] ?? '';

$response = ['success' => false, 'data' => []];

try {
    switch($tipo) {
        case 'canales':
            $query = "SELECT id, nombre FROM canales_atencion WHERE activo = 1 ORDER BY nombre";
            break;
        case 'actividades':
            $query = "SELECT id, nombre, color FROM actividades WHERE activo = 1 ORDER BY nombre";
            break;
        case 'tipos_falla':
            $query = "SELECT id, nombre, icono FROM tipos_falla WHERE activo = 1 ORDER BY nombre";
            break;
        case 'ubicaciones':
            $query = "SELECT id, nombre FROM ubicaciones WHERE activo = 1 ORDER BY nombre";
            break;
        case 'equipos':
            $query = "SELECT id, nombre FROM equipos WHERE activo = 1 ORDER BY nombre";
            break;
        case 'codigos_equipo':
            $query = "SELECT id, codigo, descripcion FROM codigos_equipo WHERE activo = 1 ORDER BY codigo";
            break;
        case 'prioridades':
            $query = "SELECT id, nombre, color FROM prioridades ORDER BY nivel";
            break;
        case 'estados':
            $query = "SELECT id, nombre, color FROM estados ORDER BY id";
            break;
        case 'areas':
            $query = "SELECT id, nombre FROM areas WHERE activo = 1 ORDER BY nombre";
            break;
        case 'usuarios':
            $query = "SELECT id, nombre_completo as nombre, username FROM usuarios WHERE activo = 1 ORDER BY nombre_completo";
            break;
        case 'todos':
            $result = [];
            $catalogos = ['canales', 'actividades', 'tipos_falla', 'ubicaciones', 'equipos', 'codigos_equipo', 'prioridades', 'estados', 'areas', 'usuarios'];
            foreach($catalogos as $cat) {
                $_GET['tipo'] = $cat;
                include(__FILE__);
            }
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Tipo de catálogo no válido']);
            exit;
    }
    
    if (isset($query)) {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response['success'] = true;
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
?>
