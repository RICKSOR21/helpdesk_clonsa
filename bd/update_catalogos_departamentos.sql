-- =====================================================
-- ACTUALIZACIÓN DE CATÁLOGOS POR DEPARTAMENTO
-- =====================================================

-- Primero verificamos los departamentos existentes
-- SELECT * FROM departamentos;
-- Asumiendo: 1=General, 2=Soporte Técnico, 3=Administración, 4=IT & Desarrollo

-- =====================================================
-- 1. ACTIVIDADES - Ya existe la tabla actividades_departamentos
-- =====================================================

-- Limpiar actividades existentes y agregar nuevas
DELETE FROM actividades_departamentos;

-- Verificar actividades existentes y agregar las faltantes
-- Soporte Técnico (departamento_id = 2)
INSERT IGNORE INTO actividades (nombre, descripcion, activo) VALUES
('Mantto Correctivo', 'Mantenimiento correctivo de equipos', 1),
('Mantto Predictivo', 'Mantenimiento predictivo de equipos', 1),
('Mantto Preventivo', 'Mantenimiento preventivo de equipos', 1),
('Software Radar MSR', 'Soporte de software Radar MSR', 1);

-- Administración (departamento_id = 3)
INSERT IGNORE INTO actividades (nombre, descripcion, activo) VALUES
('Facturación & Cobranzas', 'Gestión de facturación y cobranzas', 1),
('Gestión Documentaria', 'Gestión de documentos administrativos', 1);

-- IT & Desarrollo (departamento_id = 4)
INSERT IGNORE INTO actividades (nombre, descripcion, activo) VALUES
('Desarrollo & Tecnología', 'Desarrollo de software y tecnología', 1),
('Soporte Oficina Chile', 'Soporte técnico oficina Chile', 1),
('Soporte Oficina Perú', 'Soporte técnico oficina Perú', 1);

-- Relacionar actividades con departamentos
-- Soporte Técnico
INSERT INTO actividades_departamentos (actividad_id, departamento_id)
SELECT id, 2 FROM actividades WHERE nombre IN ('Mantto Correctivo', 'Mantto Predictivo', 'Mantto Preventivo', 'Software Radar MSR');

-- Administración
INSERT INTO actividades_departamentos (actividad_id, departamento_id)
SELECT id, 3 FROM actividades WHERE nombre IN ('Facturación & Cobranzas', 'Gestión Documentaria');

-- IT & Desarrollo
INSERT INTO actividades_departamentos (actividad_id, departamento_id)
SELECT id, 4 FROM actividades WHERE nombre IN ('Desarrollo & Tecnología', 'Software Radar MSR', 'Soporte Oficina Chile', 'Soporte Oficina Perú');

-- =====================================================
-- 2. TIPOS DE FALLA - Agregar columna departamento_id
-- =====================================================

-- Agregar columna departamento_id si no existe
ALTER TABLE tipos_falla ADD COLUMN IF NOT EXISTS departamento_id INT NULL;
ALTER TABLE tipos_falla ADD CONSTRAINT fk_tipos_falla_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL;

-- Limpiar y agregar tipos de falla
DELETE FROM tipos_falla;

-- Tipos de Falla para Soporte Técnico (departamento_id = 2)
INSERT INTO tipos_falla (nombre, descripcion, departamento_id, activo) VALUES
('Comunicación', 'Fallas relacionadas con comunicación', 2, 1),
('Configuración', 'Fallas de configuración', 2, 1),
('Energía', 'Fallas relacionadas con energía eléctrica', 2, 1),
('Software', 'Fallas de software', 2, 1),
('Hardware', 'Fallas de hardware', 2, 1),
('Usuario', 'Error de usuario', 2, 1);

-- Tipos de Falla para IT & Desarrollo (departamento_id = 4)
INSERT INTO tipos_falla (nombre, descripcion, departamento_id, activo) VALUES
('Bug de Sistema', 'Error o bug en el sistema', 4, 1),
('Error de Integración', 'Falla en integración de sistemas', 4, 1),
('Problema de Red', 'Problemas de conectividad de red', 4, 1),
('Error de Base de Datos', 'Problemas con base de datos', 4, 1);

-- Tipos de Falla para Administración (departamento_id = 3)
INSERT INTO tipos_falla (nombre, descripcion, departamento_id, activo) VALUES
('Error de Facturación', 'Error en proceso de facturación', 3, 1),
('Documento Extraviado', 'Documento perdido o extraviado', 3, 1),
('Error de Registro', 'Error en registro de información', 3, 1);

-- =====================================================
-- 3. UBICACIONES - Agregar columna departamento_id
-- =====================================================

-- Agregar columna departamento_id si no existe
ALTER TABLE ubicaciones ADD COLUMN IF NOT EXISTS departamento_id INT NULL;
ALTER TABLE ubicaciones ADD CONSTRAINT fk_ubicaciones_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL;

-- Actualizar ubicaciones existentes (mineras) para Soporte Técnico
UPDATE ubicaciones SET departamento_id = 2 WHERE departamento_id IS NULL;

-- Ubicaciones para Administración (departamento_id = 3)
INSERT INTO ubicaciones (nombre, descripcion, departamento_id, activo) VALUES
('Oficina Central Lima', 'Oficina principal en Lima', 3, 1),
('Oficina Contabilidad', 'Área de contabilidad', 3, 1),
('Archivo Central', 'Archivo de documentos', 3, 1);

-- Ubicaciones para IT & Desarrollo (departamento_id = 4)
INSERT INTO ubicaciones (nombre, descripcion, departamento_id, activo) VALUES
('Data Center Principal', 'Centro de datos principal', 4, 1),
('Oficina IT Lima', 'Oficina de IT en Lima', 4, 1),
('Oficina IT Chile', 'Oficina de IT en Chile', 4, 1);

-- =====================================================
-- 4. EQUIPOS - Agregar columna departamento_id
-- =====================================================

-- Agregar columna departamento_id si no existe
ALTER TABLE equipos ADD COLUMN IF NOT EXISTS departamento_id INT NULL;
ALTER TABLE equipos ADD CONSTRAINT fk_equipos_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL;

-- Actualizar equipos existentes para Soporte Técnico
UPDATE equipos SET departamento_id = 2 WHERE departamento_id IS NULL;

-- Equipos para Administración (departamento_id = 3)
INSERT INTO equipos (nombre, descripcion, departamento_id, activo) VALUES
('Sistema Contable', 'Sistema de contabilidad', 3, 1),
('Sistema de Facturación', 'Sistema de facturación electrónica', 3, 1),
('Gestor Documental', 'Sistema de gestión documental', 3, 1);

-- Equipos para IT & Desarrollo (departamento_id = 4)
INSERT INTO equipos (nombre, descripcion, departamento_id, activo) VALUES
('Servidor Web', 'Servidor de aplicaciones web', 4, 1),
('Servidor BD', 'Servidor de base de datos', 4, 1),
('Sistema ERP', 'Sistema ERP corporativo', 4, 1);

-- =====================================================
-- 5. CÓDIGOS DE EQUIPO - Agregar columna departamento_id
-- =====================================================

-- Agregar columna departamento_id si no existe
ALTER TABLE codigos_equipo ADD COLUMN IF NOT EXISTS departamento_id INT NULL;
ALTER TABLE codigos_equipo ADD CONSTRAINT fk_codigos_equipo_departamento FOREIGN KEY (departamento_id) REFERENCES departamentos(id) ON DELETE SET NULL;

-- Actualizar códigos existentes para Soporte Técnico
UPDATE codigos_equipo SET departamento_id = 2 WHERE departamento_id IS NULL;

-- Códigos para Administración (departamento_id = 3)
INSERT INTO codigos_equipo (codigo, equipo_id, ubicacion_id, departamento_id, activo) VALUES
('ADM-CONT-001', (SELECT id FROM equipos WHERE nombre = 'Sistema Contable' LIMIT 1), (SELECT id FROM ubicaciones WHERE nombre = 'Oficina Central Lima' AND departamento_id = 3 LIMIT 1), 3, 1),
('ADM-FACT-001', (SELECT id FROM equipos WHERE nombre = 'Sistema de Facturación' LIMIT 1), (SELECT id FROM ubicaciones WHERE nombre = 'Oficina Contabilidad' AND departamento_id = 3 LIMIT 1), 3, 1),
('ADM-DOC-001', (SELECT id FROM equipos WHERE nombre = 'Gestor Documental' LIMIT 1), (SELECT id FROM ubicaciones WHERE nombre = 'Archivo Central' AND departamento_id = 3 LIMIT 1), 3, 1);

-- Códigos para IT & Desarrollo (departamento_id = 4)
INSERT INTO codigos_equipo (codigo, equipo_id, ubicacion_id, departamento_id, activo) VALUES
('IT-WEB-001', (SELECT id FROM equipos WHERE nombre = 'Servidor Web' LIMIT 1), (SELECT id FROM ubicaciones WHERE nombre = 'Data Center Principal' AND departamento_id = 4 LIMIT 1), 4, 1),
('IT-BD-001', (SELECT id FROM equipos WHERE nombre = 'Servidor BD' LIMIT 1), (SELECT id FROM ubicaciones WHERE nombre = 'Data Center Principal' AND departamento_id = 4 LIMIT 1), 4, 1),
('IT-ERP-001', (SELECT id FROM equipos WHERE nombre = 'Sistema ERP' LIMIT 1), (SELECT id FROM ubicaciones WHERE nombre = 'Oficina IT Lima' AND departamento_id = 4 LIMIT 1), 4, 1);

-- =====================================================
-- VERIFICACIÓN
-- =====================================================
-- SELECT 'Actividades por departamento:' as info;
-- SELECT a.nombre, d.nombre as departamento FROM actividades a
-- JOIN actividades_departamentos ad ON a.id = ad.actividad_id
-- JOIN departamentos d ON ad.departamento_id = d.id ORDER BY d.nombre, a.nombre;

-- SELECT 'Tipos de falla por departamento:' as info;
-- SELECT tf.nombre, d.nombre as departamento FROM tipos_falla tf
-- LEFT JOIN departamentos d ON tf.departamento_id = d.id ORDER BY d.nombre, tf.nombre;
