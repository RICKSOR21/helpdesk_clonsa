<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Arreglando tabla historial</h2>";

try {
    // Ver estructura actual
    echo "<h3>Estructura actual de historial:</h3>";
    $result = $db->query("DESCRIBE historial");
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background:#f0f0f0;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $color = ($row['Field'] == 'descripcion' && $row['Null'] == 'NO') ? 'background:#ffcccc;' : '';
        echo "<tr style='$color'>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Modificar columna descripcion para permitir NULL
    echo "<h3>Modificando columna descripcion para permitir NULL...</h3>";

    $db->exec("ALTER TABLE historial MODIFY COLUMN descripcion TEXT NULL");
    echo "<p style='color:green'>✓ Columna 'descripcion' ahora permite NULL</p>";

    // Test del trigger
    echo "<h3>Test de INSERT en tickets (para probar el trigger):</h3>";
    $db->beginTransaction();

    $query = "INSERT INTO tickets (titulo, descripcion, usuario_id) VALUES ('TEST TRIGGER', 'Probando trigger', 1)";
    $db->exec($query);
    $ticket_id = $db->lastInsertId();

    echo "<p style='color:green'>✓ INSERT exitoso! Ticket ID: $ticket_id</p>";

    // Verificar que el historial se creó
    $result = $db->query("SELECT * FROM historial WHERE ticket_id = $ticket_id");
    $historial = $result->fetch(PDO::FETCH_ASSOC);

    if($historial) {
        echo "<p style='color:green'>✓ Trigger funcionó! Registro en historial creado automáticamente</p>";
        echo "<pre>" . print_r($historial, true) . "</pre>";
    }

    $db->rollBack();
    echo "<p style='color:blue'>↩ Rollback ejecutado (datos de prueba no guardados)</p>";

    echo "<hr>";
    echo "<h2 style='color:green'>✓ ¡PROBLEMA SOLUCIONADO!</h2>";
    echo "<p>Ahora puedes crear tickets sin errores.</p>";
    echo "<p><a href='tickets-create.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>Ir a Crear Ticket</a></p>";

} catch(Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
    if(isset($db)) {
        try { $db->rollBack(); } catch(Exception $ex) {}
    }
}
?>
