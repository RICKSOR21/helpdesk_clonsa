<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Verificación de TRIGGERS en la base de datos</h2>";

try {
    $dbname = $db->query("SELECT DATABASE()")->fetchColumn();
    echo "<p>Base de datos: <strong>$dbname</strong></p>";

    // Listar todos los triggers
    $result = $db->query("SHOW TRIGGERS");
    $triggers = $result->fetchAll(PDO::FETCH_ASSOC);

    if(count($triggers) > 0) {
        echo "<h3>Triggers encontrados (" . count($triggers) . "):</h3>";
        foreach($triggers as $trigger) {
            echo "<div style='background:#f5f5f5; padding:15px; margin:10px 0; border:1px solid #ddd;'>";
            echo "<h4 style='color:blue; margin:0;'>Trigger: {$trigger['Trigger']}</h4>";
            echo "<p><strong>Tabla:</strong> {$trigger['Table']}</p>";
            echo "<p><strong>Evento:</strong> {$trigger['Event']} {$trigger['Timing']}</p>";
            echo "<p><strong>Código:</strong></p>";
            echo "<pre style='background:#fff; padding:10px; border:1px solid #ccc; overflow:auto;'>" . htmlspecialchars($trigger['Statement']) . "</pre>";
            echo "</div>";
        }

        echo "<h3 style='color:orange;'>⚠️ TRIGGERS ENCONTRADOS - Estos podrían estar causando el error!</h3>";
        echo "<p>Revisa si algún trigger hace referencia a una columna 'descripcion' que no existe en alguna tabla relacionada.</p>";

    } else {
        echo "<p style='color:green'>✓ No hay triggers en esta base de datos</p>";
    }

    // Verificar procedimientos almacenados
    echo "<h2>Procedimientos almacenados:</h2>";
    $result = $db->query("SHOW PROCEDURE STATUS WHERE Db = '$dbname'");
    $procedures = $result->fetchAll(PDO::FETCH_ASSOC);

    if(count($procedures) > 0) {
        echo "<p>Procedimientos encontrados: " . count($procedures) . "</p>";
        foreach($procedures as $proc) {
            echo "<p>- {$proc['Name']}</p>";
        }
    } else {
        echo "<p style='color:green'>✓ No hay procedimientos almacenados</p>";
    }

} catch(Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
