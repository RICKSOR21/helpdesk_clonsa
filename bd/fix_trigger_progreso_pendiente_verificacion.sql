-- Corrige trigger legado que envia estado_id=5 al llegar a 100%.
-- Flujo correcto: 100% => estado_id=4 (Resuelto), luego la app maneja pendiente_aprobacion.

DROP TRIGGER IF EXISTS before_update_ticket_progreso;

DELIMITER //
CREATE TRIGGER before_update_ticket_progreso
BEFORE UPDATE ON tickets
FOR EACH ROW
BEGIN
    IF NEW.progreso = 100 AND OLD.progreso <> 100 THEN
        SET NEW.estado_id = 4;
        SET NEW.fecha_resolucion = NOW();
    END IF;
END//
DELIMITER ;

