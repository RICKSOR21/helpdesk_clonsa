-- Actualizar colores de estados
UPDATE estados SET color = '#fd7e14' WHERE id = 1; -- Abierto: naranja
UPDATE estados SET color = '#0d6efd' WHERE id = 2; -- En Proceso: azul

-- Agregar campo para aprobacion pendiente en tickets
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS pendiente_aprobacion TINYINT(1) DEFAULT 0 AFTER progreso;
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS aprobado_por INT DEFAULT NULL AFTER pendiente_aprobacion;
ALTER TABLE tickets ADD COLUMN IF NOT EXISTS fecha_aprobacion DATETIME DEFAULT NULL AFTER aprobado_por;

-- Agregar FK para aprobado_por
ALTER TABLE tickets ADD CONSTRAINT fk_tickets_aprobado FOREIGN KEY (aprobado_por) REFERENCES usuarios(id) ON DELETE SET NULL;
