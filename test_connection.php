<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "=== PRUEBA DE CONEXIÓN A BASE DE DATOS ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "✓ Conexión exitosa a la base de datos: " . DB_NAME . "\n\n";
    
    // Probar consultas básicas
    echo "=== VERIFICACIÓN DE TABLAS ===\n";
    
    $tables = ['usuarios', 'tickets', 'estados', 'actividades', 'ubicaciones', 'departamentos'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as total FROM $table");
            $result = $stmt->fetch();
            echo "✓ Tabla '$table': {$result['total']} registros\n";
        } catch (Exception $e) {
            echo "✗ Error en tabla '$table': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== INFORMACIÓN DE CONFIGURACIÓN ===\n";
    echo "Host: " . DB_HOST . "\n";
    echo "Base de datos: " . DB_NAME . "\n";
    echo "Usuario: " . DB_USER . "\n";
    echo "Charset: " . DB_CHARSET . "\n";
    echo "Entorno: " . (IS_DEVELOPMENT ? 'DESARROLLO' : 'PRODUCCIÓN') . "\n";
    
} catch (Exception $e) {
    echo "✗ Error de conexión: " . $e->getMessage() . "\n";
    echo "Detalles: " . $e->getTraceAsString() . "\n";
}
?>
