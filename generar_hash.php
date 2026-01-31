<?php
// generar_hash.php
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h2>Generador de Hash de Contraseña</h2>";
echo "<p><strong>Contraseña:</strong> $password</p>";
echo "<p><strong>Hash generado:</strong></p>";
echo "<textarea style='width:100%; height:100px;'>$hash</textarea>";
echo "<br><br>";
echo "<h3>SQL para actualizar TODOS los usuarios:</h3>";
echo "<textarea style='width:100%; height:150px;'>UPDATE usuarios SET password = '$hash';</textarea>";
echo "<br><br>";
echo "<p><em>Copia el SQL de arriba y ejecútalo en tu base de datos</em></p>";
?>