<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Estructura de la tabla tickets</h2>";

try {
    $result = $db->query("DESCRIBE tickets");
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

    // Verificar si descripcion existe
    echo "<h3>Buscando columna 'descripcion':</h3>";
    $check = $db->query("SHOW COLUMNS FROM tickets LIKE 'descripcion'");
    if($check->rowCount() > 0) {
        echo "<p style='color:green;'>La columna 'descripcion' EXISTE en la tabla.</p>";
    } else {
        echo "<p style='color:red;'>La columna 'descripcion' NO EXISTE en la tabla.</p>";
    }

    // Mostrar nombre de la base de datos
    $dbname = $db->query("SELECT DATABASE()")->fetchColumn();
    echo "<p>Base de datos actual: <strong>$dbname</strong></p>";

} catch(Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>
