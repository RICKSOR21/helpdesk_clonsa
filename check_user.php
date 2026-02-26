<?php
require_once 'config/session.php';
session_start();
require_once 'config/config.php';
require_once 'config/database.php';

echo "Session user_id: " . ($_SESSION['user_id'] ?? 'N/A') . "<br>";
echo "Session user_name: " . ($_SESSION['user_name'] ?? 'N/A') . "<br>";
echo "Session user_rol: " . ($_SESSION['user_rol'] ?? 'N/A') . "<br>";
echo "Session departamento_id: " . ($_SESSION['departamento_id'] ?? 'N/A') . "<br>";

// Verificar en BD
$db = Database::connect();
$stmt = $db->prepare("SELECT id, nombre_completo, rol, departamento_id FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['user_id'] ?? 0]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<br><b>BD:</b><br>";
echo "BD id: " . ($user['id'] ?? 'N/A') . "<br>";
echo "BD nombre: " . ($user['nombre_completo'] ?? 'N/A') . "<br>";
echo "BD rol: " . ($user['rol'] ?? 'N/A') . "<br>";
echo "BD departamento_id: " . ($user['departamento_id'] ?? 'N/A') . "<br>";
