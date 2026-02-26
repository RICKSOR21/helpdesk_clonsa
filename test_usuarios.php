<?php
require_once 'config/database.php';
$db = (new Database())->getConnection();

echo "=== USUARIOS ===\n";
$r = $db->query('SELECT id, nombre_completo, departamento_id FROM usuarios WHERE activo = 1');
while($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} - {$row['nombre_completo']} - Dept: " . ($row['departamento_id'] ?? 'NULL') . "\n";
}

echo "\n=== DEPARTAMENTOS ===\n";
$r = $db->query('SELECT id, nombre FROM departamentos WHERE activo = 1');
while($row = $r->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$row['id']} - {$row['nombre']}\n";
}

echo "\n=== USUARIOS POR DEPARTAMENTO (Soporte Tecnico = probablemente ID 1 o 2) ===\n";
$stmt = $db->prepare('SELECT id, nombre_completo, departamento_id FROM usuarios WHERE departamento_id = ? AND activo = 1');
$stmt->execute([1]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Departamento 1: " . count($users) . " usuarios\n";
print_r($users);

$stmt->execute([2]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Departamento 2: " . count($users) . " usuarios\n";
print_r($users);
