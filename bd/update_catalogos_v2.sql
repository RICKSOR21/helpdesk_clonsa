-- =====================================================
-- ACTUALIZACIÓN DE CATÁLOGOS POR DEPARTAMENTO
-- Departamentos: 1=General, 2=Soporte Técnico, 3=Administración, 4=IT & Desarrollo
-- =====================================================

-- =====================================================
-- 1. AGREGAR COLUMNA departamento_id A LAS TABLAS
-- =====================================================

-- tipos_falla
ALTER TABLE tipos_falla ADD COLUMN departamento_id INT NULL;

-- ubicaciones
ALTER TABLE ubicaciones ADD COLUMN departamento_id INT NULL;

-- equipos
ALTER TABLE equipos ADD COLUMN departamento_id INT NULL;

-- codigos_equipo
ALTER TABLE codigos_equipo ADD COLUMN departamento_id INT NULL;

-- =====================================================
-- 2. ACTIVIDADES - Limpiar y agregar relaciones
-- =====================================================

-- Limpiar relaciones existentes
DELETE FROM actividades_departamentos;

-- Insertar actividades si no existen
INSERT INTO actividades (nombre, descripcion, activo) VALUES
('Mantto Correctivo', 'Mantenimiento correctivo de equipos', 1),
('Mantto Predictivo', 'Mantenimiento predictivo de equipos', 1),
('Mantto Preventivo', 'Mantenimiento preventivo de equipos', 1),
('Software Radar MSR', 'Soporte de software Radar MSR', 1),
('Facturación & Cobranzas', 'Gestión de facturación y cobranzas', 1),
('Gestión Documentaria', 'Gestión de documentos administrativos', 1),
('Desarrollo & Tecnología', 'Desarrollo de software y tecnología', 1),
('Soporte Oficina Chile', 'Soporte técnico oficina Chile', 1),
('Soporte Oficina Perú', 'Soporte técnico oficina Perú', 1)
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- Relacionar actividades con Soporte Técnico (2)
INSERT INTO actividades_departamentos (actividad_id, departamento_id)
SELECT id, 2 FROM actividades WHERE nombre IN ('Mantto Correctivo', 'Mantto Predictivo', 'Mantto Preventivo', 'Software Radar MSR');

-- Relacionar actividades con Administración (3)
INSERT INTO actividades_departamentos (actividad_id, departamento_id)
SELECT id, 3 FROM actividades WHERE nombre IN ('Facturación & Cobranzas', 'Gestión Documentaria');

-- Relacionar actividades con IT & Desarrollo (4)
INSERT INTO actividades_departamentos (actividad_id, departamento_id)
SELECT id, 4 FROM actividades WHERE nombre IN ('Desarrollo & Tecnología', 'Software Radar MSR', 'Soporte Oficina Chile', 'Soporte Oficina Perú');

-- =====================================================
-- 3. TIPOS DE FALLA
-- =====================================================

-- Actualizar tipos existentes para Soporte Técnico
UPDATE tipos_falla SET departamento_id = 2 WHERE nombre IN ('Comunicación', 'Configuración', 'Energía', 'Software', 'Hardware', 'Usuario');

-- Insertar tipos para Soporte Técnico si no existen
INSERT INTO tipos_falla (nombre, descripcion, departamento_id, activo) VALUES
('Comunicación', 'Fallas relacionadas con comunicación', 2, 1),
('Configuración', 'Fallas de configuración', 2, 1),
('Energía', 'Fallas relacionadas con energía eléctrica', 2, 1),
('Software', 'Fallas de software', 2, 1),
('Hardware', 'Fallas de hardware', 2, 1),
('Usuario', 'Error de usuario', 2, 1)
ON DUPLICATE KEY UPDATE departamento_id = 2;

-- Tipos de Falla para IT & Desarrollo (4)
INSERT INTO tipos_falla (nombre, descripcion, departamento_id, activo) VALUES
('Bug de Sistema', 'Error o bug en el sistema', 4, 1),
('Error de Integración', 'Falla en integración de sistemas', 4, 1),
('Problema de Red', 'Problemas de conectividad de red', 4, 1);

-- Tipos de Falla para Administración (3)
INSERT INTO tipos_falla (nombre, descripcion, departamento_id, activo) VALUES
('Error de Facturación', 'Error en proceso de facturación', 3, 1),
('Documento Extraviado', 'Documento perdido o extraviado', 3, 1),
('Error de Registro', 'Error en registro de información', 3, 1);

-- =====================================================
-- 4. UBICACIONES
-- =====================================================

-- Actualizar ubicaciones existentes (mineras) para Soporte Técnico
UPDATE ubicaciones SET departamento_id = 2 WHERE departamento_id IS NULL;

-- Ubicaciones para Administración (3)
INSERT INTO ubicaciones (nombre, descripcion, departamento_id, activo) VALUES
('Oficina Central Lima', 'Oficina principal en Lima', 3, 1),
('Oficina Contabilidad', 'Área de contabilidad', 3, 1),
('Archivo Central', 'Archivo de documentos', 3, 1);

-- Ubicaciones para IT & Desarrollo (4)
INSERT INTO ubicaciones (nombre, descripcion, departamento_id, activo) VALUES
('Data Center Principal', 'Centro de datos principal', 4, 1),
('Oficina IT Lima', 'Oficina de IT en Lima', 4, 1),
('Oficina IT Chile', 'Oficina de IT en Chile', 4, 1);

-- =====================================================
-- 5. EQUIPOS
-- =====================================================

-- Actualizar equipos existentes para Soporte Técnico
UPDATE equipos SET departamento_id = 2 WHERE departamento_id IS NULL;

-- Equipos para Administración (3)
INSERT INTO equipos (nombre, descripcion, departamento_id, activo) VALUES
('Sistema Contable', 'Sistema de contabilidad', 3, 1),
('Sistema de Facturación', 'Sistema de facturación electrónica', 3, 1),
('Gestor Documental', 'Sistema de gestión documental', 3, 1);

-- Equipos para IT & Desarrollo (4)
INSERT INTO equipos (nombre, descripcion, departamento_id, activo) VALUES
('Servidor Web', 'Servidor de aplicaciones web', 4, 1),
('Servidor BD', 'Servidor de base de datos', 4, 1),
('Sistema ERP', 'Sistema ERP corporativo', 4, 1);

-- =====================================================
-- 6. CÓDIGOS DE EQUIPO
-- =====================================================

-- Actualizar códigos existentes para Soporte Técnico
UPDATE codigos_equipo SET departamento_id = 2 WHERE departamento_id IS NULL;

-- Códigos para Administración (3)
INSERT INTO codigos_equipo (codigo, descripcion, departamento_id, activo) VALUES
('ADM-CONT-001', 'Sistema Contable Principal', 3, 1),
('ADM-FACT-001', 'Sistema de Facturación', 3, 1),
('ADM-DOC-001', 'Gestor Documental', 3, 1);

-- Códigos para IT & Desarrollo (4)
INSERT INTO codigos_equipo (codigo, descripcion, departamento_id, activo) VALUES
('IT-WEB-001', 'Servidor Web Principal', 4, 1),
('IT-BD-001', 'Servidor Base de Datos', 4, 1),
('IT-ERP-001', 'Sistema ERP', 4, 1);

-- =====================================================
-- VERIFICACIÓN
-- =====================================================
SELECT 'Actividades por departamento:' as info;
SELECT d.nombre as departamento, GROUP_CONCAT(a.nombre SEPARATOR ', ') as actividades
FROM departamentos d
LEFT JOIN actividades_departamentos ad ON d.id = ad.departamento_id
LEFT JOIN actividades a ON ad.actividad_id = a.id
WHERE d.activo = 1
GROUP BY d.id, d.nombre;

SELECT 'Tipos de falla por departamento:' as info;
SELECT d.nombre as departamento, GROUP_CONCAT(tf.nombre SEPARATOR ', ') as tipos_falla
FROM departamentos d
LEFT JOIN tipos_falla tf ON d.id = tf.departamento_id
WHERE d.activo = 1
GROUP BY d.id, d.nombre;

SELECT 'Ubicaciones por departamento:' as info;
SELECT d.nombre as departamento, COUNT(u.id) as cantidad
FROM departamentos d
LEFT JOIN ubicaciones u ON d.id = u.departamento_id
WHERE d.activo = 1
GROUP BY d.id, d.nombre;
