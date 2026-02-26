<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Configuracion de tabla de archivos</h2>";

try {
    // Agregar columnas faltantes a tickets
    $queries = [
        "ALTER TABLE tickets ADD COLUMN IF NOT EXISTS solicitante_nombre VARCHAR(255) NULL",
        "ALTER TABLE tickets ADD COLUMN IF NOT EXISTS solicitante_email VARCHAR(255) NULL",
        "ALTER TABLE tickets ADD COLUMN IF NOT EXISTS solicitante_telefono VARCHAR(50) NULL"
    ];

    foreach($queries as $q) {
        try {
            $db->exec($q);
            echo "<p style='color:green'>OK: Columna agregada</p>";
        } catch(Exception $e) {
            // Columna ya existe, ignorar
        }
    }

    // Crear tabla de archivos
    $sql = "CREATE TABLE IF NOT EXISTS ticket_archivos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        nombre_original VARCHAR(255) NOT NULL,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta VARCHAR(500) NOT NULL,
        tamano INT NOT NULL DEFAULT 0,
        tipo_mime VARCHAR(100) NULL,
        usuario_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
    )";

    $db->exec($sql);
    echo "<p style='color:green'>OK: Tabla ticket_archivos creada/verificada</p>";

    // Crear directorio de uploads
    $upload_dir = 'uploads/tickets/';
    if(!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo "<p style='color:green'>OK: Directorio $upload_dir creado</p>";
    } else {
        echo "<p style='color:blue'>INFO: Directorio $upload_dir ya existe</p>";
    }

    echo "<h3 style='color:green'>Configuracion completada exitosamente!</h3>";
    echo "<p><a href='tickets-create.php'>Ir a crear ticket</a></p>";

} catch(Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
