-- =============================================
-- ACTUALIZACIÓN COMPLETA DEL FLUJO DE ESTADOS
-- =============================================
-- Flujo:
--   Abierto (1) → En Atención (2) → Resuelto (4) con pendiente_aprobacion
--   Si aprueban: Resuelto (4) con pendiente_aprobacion=0 (ticket cerrado)
--   Si rechazan: Rechazado (5) (vuelve a 90%)

-- Primero actualizar tickets con estado 3 a estado 2 (En Atención)
UPDATE tickets SET estado_id = 2 WHERE estado_id = 3;

-- Eliminar estados que no se usarán (estado 3, 6, etc)
DELETE FROM estados WHERE id NOT IN (1, 2, 4, 5);

-- Actualizar los estados existentes con nombres y colores correctos
UPDATE estados SET nombre = 'Abierto', color = '#fd7e14' WHERE id = 1;
UPDATE estados SET nombre = 'En Atención', color = '#17a2b8' WHERE id = 2;
UPDATE estados SET nombre = 'Resuelto', color = '#28a745' WHERE id = 4;
UPDATE estados SET nombre = 'Rechazado', color = '#dc3545' WHERE id = 5;

-- Verificar que las columnas existan en tickets (usar sintaxis segura)
-- Si da error, ejecutar manualmente: ALTER TABLE tickets ADD COLUMN pendiente_aprobacion TINYINT(1) DEFAULT 0;
-- ALTER TABLE tickets ADD COLUMN IF NOT EXISTS pendiente_aprobacion TINYINT(1) DEFAULT 0;
-- ALTER TABLE tickets ADD COLUMN IF NOT EXISTS aprobado_por INT DEFAULT NULL;
-- ALTER TABLE tickets ADD COLUMN IF NOT EXISTS fecha_aprobacion DATETIME DEFAULT NULL;

-- Actualizar tickets que tengan estados eliminados al estado "En Atención"
UPDATE tickets SET estado_id = 2 WHERE estado_id NOT IN (1, 2, 4, 5);

-- Ver estados finales
SELECT id, nombre, color FROM estados ORDER BY id;
