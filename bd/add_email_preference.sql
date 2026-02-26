-- Agregar columna de preferencia de notificaciones por correo
-- Solo aplica filtro para admins (checkbox en gestion de usuarios)
ALTER TABLE usuarios ADD COLUMN recibir_notificaciones_email TINYINT(1) NOT NULL DEFAULT 1;
