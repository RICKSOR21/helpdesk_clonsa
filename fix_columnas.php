<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Agregando columnas faltantes a tickets</h2>";

try {
    // Todas las columnas necesarias para el formulario de tickets
    $columnas = [
        ['nombre' => 'descripcion', 'definicion' => 'TEXT NULL'],
        ['nombre' => 'departamento_id', 'definicion' => 'INT NULL'],
        ['nombre' => 'solicitante_nombre', 'definicion' => 'VARCHAR(255) NULL'],
        ['nombre' => 'solicitante_email', 'definicion' => 'VARCHAR(255) NULL'],
        ['nombre' => 'solicitante_telefono', 'definicion' => 'VARCHAR(50) NULL'],
        ['nombre' => 'canal_atencion_id', 'definicion' => 'INT NULL'],
        ['nombre' => 'actividad_id', 'definicion' => 'INT NULL'],
        ['nombre' => 'tipo_falla_id', 'definicion' => 'INT NULL'],
        ['nombre' => 'ubicacion_id', 'definicion' => 'INT NULL'],
        ['nombre' => 'equipo_id', 'definicion' => 'INT NULL'],
        ['nombre' => 'codigo_equipo_id', 'definicion' => 'INT NULL'],
        ['nombre' => 'asignado_a', 'definicion' => 'INT NULL'],
        ['nombre' => 'prioridad_id', 'definicion' => 'INT NULL DEFAULT 2'],
        ['nombre' => 'progreso', 'definicion' => 'INT NOT NULL DEFAULT 0']
    ];

    foreach($columnas as $col) {
        $check = $db->query("SHOW COLUMNS FROM tickets LIKE '{$col['nombre']}'");
        if($check->rowCount() == 0) {
            $sql = "ALTER TABLE tickets ADD COLUMN {$col['nombre']} {$col['definicion']}";
            $db->exec($sql);
            echo "<p style='color:green'>OK: Columna '{$col['nombre']}' agregada</p>";
        } else {
            echo "<p style='color:blue'>INFO: Columna '{$col['nombre']}' ya existe</p>";
        }
    }

    echo "<h3 style='color:green'>Columnas verificadas/agregadas exitosamente!</h3>";
    echo "<p><a href='tickets-create.php'>Ir a crear ticket</a></p>";

} catch(Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}
?>
