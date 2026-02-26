-- Eliminar estado "Pendiente Usuario" (id=3) que no se usará en el sistema
-- Primero verificar si hay tickets con este estado y cambiarlos a "En Proceso" (id=2)

UPDATE tickets SET estado_id = 2 WHERE estado_id = 3;

-- Eliminar el estado
DELETE FROM estados WHERE id = 3;

-- Verificar estados finales
SELECT id, nombre, color FROM estados ORDER BY id;
