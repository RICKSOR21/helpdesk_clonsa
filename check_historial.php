<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Verificación de tabla historial</h2>";

try {
    $result = $db->query("DESCRIBE historial");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test INSERT en historial
    echo "<h3>Test INSERT en historial:</h3>";
    $db->beginTransaction();
    $query = "INSERT INTO historial (ticket_id, usuario_id, accion, descripcion) VALUES (1, 1, 'test', 'Test descripcion')";
    $db->exec($query);
    echo "<p style='color:green'>✓ INSERT en historial exitoso</p>";
    $db->rollBack();

} catch(Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    if(isset($db)) $db->rollBack();
}

echo "<h2>Verificación de tabla ticket_archivos</h2>";

try {
    $result = $db->query("DESCRIBE ticket_archivos");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch(Exception $e) {
    echo "<p style='color:red'>Error tabla ticket_archivos: " . $e->getMessage() . "</p>";
    echo "<p>Creando tabla...</p>";

    try {
        $sql = "CREATE TABLE IF NOT EXISTS ticket_archivos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            nombre_original VARCHAR(255) NOT NULL,
            nombre_archivo VARCHAR(255) NOT NULL,
            ruta VARCHAR(500) NOT NULL,
            tamano INT NOT NULL DEFAULT 0,
            tipo_mime VARCHAR(100) NULL,
            usuario_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $db->exec($sql);
        echo "<p style='color:green'>✓ Tabla ticket_archivos creada</p>";
    } catch(Exception $e2) {
        echo "<p style='color:red'>Error creando tabla: " . $e2->getMessage() . "</p>";
    }
}
?>
