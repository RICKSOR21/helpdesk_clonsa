<?php
// crear-logs.php - Ejecutar una sola vez
// Coloca este archivo en: C:\laragon\www\helpdesk\

$logs_dir = __DIR__ . '/logs';

if (!file_exists($logs_dir)) {
    if (mkdir($logs_dir, 0777, true)) {
        echo "✅ Carpeta 'logs' creada exitosamente en: $logs_dir<br>";
    } else {
        echo "❌ No se pudo crear la carpeta 'logs'<br>";
    }
} else {
    echo "✅ La carpeta 'logs' ya existe<br>";
}

// Crear archivo de log vacío
$log_file = $logs_dir . '/login_debug.txt';
if (!file_exists($log_file)) {
    if (file_put_contents($log_file, "Log iniciado: " . date('Y-m-d H:i:s') . "\n")) {
        echo "✅ Archivo de log creado: $log_file<br>";
    } else {
        echo "❌ No se pudo crear el archivo de log<br>";
    }
} else {
    echo "✅ El archivo de log ya existe<br>";
}

echo "<hr>";
echo "<h3>Todo listo para usar el sistema de login</h3>";
echo "<p><a href='login.php'>Ir al Login</a></p>";
?>
