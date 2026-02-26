<?php
require_once 'config/config.php';
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Debug Completo de la Tabla Tickets</h2>";

echo "<h3>1. Información de la conexión:</h3>";
try {
    $dbname = $db->query("SELECT DATABASE()")->fetchColumn();
    echo "<p>Base de datos conectada: <strong style='color:green'>$dbname</strong></p>";

    // Verificar que es la misma base de datos
    echo "<p>DB_NAME en config: <strong>" . DB_NAME . "</strong></p>";

    if($dbname === DB_NAME) {
        echo "<p style='color:green'>✓ La conexión está usando la base de datos correcta</p>";
    } else {
        echo "<p style='color:red'>✗ PROBLEMA: Las bases de datos no coinciden!</p>";
    }
} catch(Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>2. Estructura actual de la tabla tickets:</h3>";
try {
    $result = $db->query("DESCRIBE tickets");
    $columnas_existentes = [];

    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; font-size:12px;'>";
    echo "<tr style='background:#f0f0f0;'><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

    while($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $columnas_existentes[] = $row['Field'];
        echo "<tr>";
        echo "<td><strong>{$row['Field']}</strong></td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<p>Total de columnas: <strong>" . count($columnas_existentes) . "</strong></p>";

} catch(Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>3. Columnas requeridas para el formulario:</h3>";
$columnas_requeridas = [
    'id', 'codigo', 'titulo', 'descripcion', 'usuario_id', 'departamento_id',
    'prioridad_id', 'canal_atencion_id', 'actividad_id', 'tipo_falla_id',
    'ubicacion_id', 'equipo_id', 'codigo_equipo_id', 'asignado_a',
    'solicitante_nombre', 'solicitante_email', 'solicitante_telefono',
    'estado_id', 'progreso', 'created_at'
];

echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr style='background:#f0f0f0;'><th>Columna</th><th>Estado</th></tr>";

$faltantes = [];
foreach($columnas_requeridas as $col) {
    $existe = in_array($col, $columnas_existentes);
    $color = $existe ? 'green' : 'red';
    $estado = $existe ? '✓ Existe' : '✗ FALTA';
    echo "<tr><td>$col</td><td style='color:$color'>$estado</td></tr>";

    if(!$existe) {
        $faltantes[] = $col;
    }
}
echo "</table>";

echo "<h3>4. Intentar INSERT de prueba (sin ejecutar realmente):</h3>";
try {
    $query = "INSERT INTO tickets (titulo, descripcion, usuario_id, departamento_id, prioridad_id,
              canal_atencion_id, actividad_id, tipo_falla_id, ubicacion_id, equipo_id, codigo_equipo_id,
              asignado_a, solicitante_nombre, solicitante_email, solicitante_telefono)
              VALUES (:titulo, :descripcion, :usuario_id, :departamento_id, :prioridad_id,
              :canal_id, :actividad_id, :tipo_falla_id, :ubicacion_id, :equipo_id, :codigo_equipo_id,
              :asignado_a, :solicitante_nombre, :solicitante_email, :solicitante_telefono)";

    // Solo preparar, no ejecutar
    $stmt = $db->prepare($query);
    echo "<p style='color:green'>✓ La query de INSERT se puede preparar correctamente</p>";
    echo "<pre style='background:#f5f5f5; padding:10px; font-size:11px;'>$query</pre>";

} catch(Exception $e) {
    echo "<p style='color:red'>✗ Error al preparar la query: " . $e->getMessage() . "</p>";
}

echo "<h3>5. Test de INSERT real (con rollback):</h3>";
try {
    $db->beginTransaction();

    $query = "INSERT INTO tickets (titulo, descripcion, usuario_id) VALUES ('TEST', 'TEST DESC', 1)";
    $db->exec($query);

    $last_id = $db->lastInsertId();
    echo "<p style='color:green'>✓ INSERT exitoso! ID: $last_id</p>";

    // Rollback para no guardar el test
    $db->rollBack();
    echo "<p style='color:blue'>↩ Rollback ejecutado (no se guardó el registro de prueba)</p>";

} catch(Exception $e) {
    $db->rollBack();
    echo "<p style='color:red'>✗ Error en INSERT: " . $e->getMessage() . "</p>";

    // Si falla, intentar agregar la columna
    if(strpos($e->getMessage(), 'descripcion') !== false) {
        echo "<h4>Intentando agregar columna descripcion...</h4>";
        try {
            $db->exec("ALTER TABLE tickets ADD COLUMN descripcion TEXT NULL AFTER titulo");
            echo "<p style='color:green'>✓ Columna 'descripcion' agregada exitosamente!</p>";
        } catch(Exception $e2) {
            echo "<p style='color:orange'>Info: " . $e2->getMessage() . "</p>";
        }
    }
}

if(!empty($faltantes)) {
    echo "<h3>6. Agregar columnas faltantes:</h3>";

    $definiciones = [
        'descripcion' => 'TEXT NULL AFTER titulo',
        'departamento_id' => 'INT NULL',
        'solicitante_nombre' => 'VARCHAR(255) NULL',
        'solicitante_email' => 'VARCHAR(255) NULL',
        'solicitante_telefono' => 'VARCHAR(50) NULL',
        'canal_atencion_id' => 'INT NULL',
        'actividad_id' => 'INT NULL',
        'tipo_falla_id' => 'INT NULL',
        'ubicacion_id' => 'INT NULL',
        'equipo_id' => 'INT NULL',
        'codigo_equipo_id' => 'INT NULL',
        'asignado_a' => 'INT NULL',
        'prioridad_id' => 'INT NULL DEFAULT 2',
        'progreso' => 'INT NOT NULL DEFAULT 0'
    ];

    foreach($faltantes as $col) {
        if(isset($definiciones[$col])) {
            try {
                $sql = "ALTER TABLE tickets ADD COLUMN $col {$definiciones[$col]}";
                $db->exec($sql);
                echo "<p style='color:green'>✓ Columna '$col' agregada</p>";
            } catch(Exception $e) {
                echo "<p style='color:orange'>Info ($col): " . $e->getMessage() . "</p>";
            }
        }
    }
}

echo "<hr>";
echo "<p><a href='tickets-create.php' style='padding:10px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>Ir a Crear Ticket</a></p>";
?>
