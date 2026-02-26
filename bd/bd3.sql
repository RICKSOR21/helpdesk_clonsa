-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         8.4.3 - MySQL Community Server - GPL
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para helpdesk_clonsa
CREATE DATABASE IF NOT EXISTS `helpdesk_clonsa` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `helpdesk_clonsa`;

-- Volcando estructura para tabla helpdesk_clonsa.actividades
CREATE TABLE IF NOT EXISTS `actividades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.actividades: ~12 rows (aproximadamente)
INSERT INTO `actividades` (`id`, `nombre`, `descripcion`, `color`, `activo`, `created_at`) VALUES
	(1, 'Mantenimiento Preventivo', 'Mantenimiento programado', '#28a745', 1, '2026-01-23 16:13:59'),
	(2, 'Mantenimiento Correctivo', 'Reparación de fallas', '#dc3545', 1, '2026-01-23 16:13:59'),
	(3, 'Mantenimiento Predictivo', 'Mantenimiento predictivo', '#17a2b8', 1, '2026-01-23 16:13:59'),
	(4, 'Software Radar MSR', 'Software Radar MSR', '#ffc107', 1, '2026-01-23 16:13:59'),
	(5, 'Soporte Oficina Perú', 'Soporte Oficina Perú', '#28a745', 1, '2026-01-23 16:13:59'),
	(6, 'Soporte Oficina Chile', 'Soporte Oficina Chile', '#dc3545', 1, '2026-01-23 16:13:59'),
	(7, 'Desarrollo & Tecnología', 'Desarrollo y Tecnología', '#17a2b8', 1, '2026-01-23 16:13:59'),
	(8, 'Gestión Documentaria', NULL, '#9C27B0', 1, '2026-02-01 21:26:14'),
	(9, 'Facturación & Cobranzas', NULL, '#E91E63', 1, '2026-02-01 21:26:14'),
	(10, 'Mantto Correctivo', 'Mantenimiento correctivo de equipos', NULL, 1, '2026-02-11 00:02:23'),
	(11, 'Mantto Predictivo', 'Mantenimiento predictivo de equipos', NULL, 1, '2026-02-11 00:02:23'),
	(12, 'Mantto Preventivo', 'Mantenimiento preventivo de equipos', NULL, 1, '2026-02-11 00:02:23');

-- Volcando estructura para tabla helpdesk_clonsa.actividades_departamentos
CREATE TABLE IF NOT EXISTS `actividades_departamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actividad_id` int NOT NULL,
  `departamento_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_actividad_dept` (`actividad_id`,`departamento_id`),
  KEY `departamento_id` (`departamento_id`),
  CONSTRAINT `actividades_departamentos_ibfk_1` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `actividades_departamentos_ibfk_2` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.actividades_departamentos: ~10 rows (aproximadamente)
INSERT INTO `actividades_departamentos` (`id`, `actividad_id`, `departamento_id`, `created_at`) VALUES
	(16, 10, 2, '2026-02-11 00:02:23'),
	(17, 11, 2, '2026-02-11 00:02:23'),
	(18, 12, 2, '2026-02-11 00:02:23'),
	(19, 4, 2, '2026-02-11 00:02:23'),
	(23, 9, 3, '2026-02-11 00:02:23'),
	(24, 8, 3, '2026-02-11 00:02:23'),
	(26, 7, 4, '2026-02-11 00:02:23'),
	(27, 4, 4, '2026-02-11 00:02:23'),
	(28, 6, 4, '2026-02-11 00:02:23'),
	(29, 5, 4, '2026-02-11 00:02:23');

-- Volcando estructura para tabla helpdesk_clonsa.adjuntos
CREATE TABLE IF NOT EXISTS `adjuntos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int DEFAULT NULL,
  `comentario_id` int DEFAULT NULL,
  `usuario_id` int NOT NULL,
  `nombre_archivo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_archivo` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_archivo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tamano_kb` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `comentario_id` (`comentario_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `adjuntos_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `adjuntos_ibfk_2` FOREIGN KEY (`comentario_id`) REFERENCES `comentarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `adjuntos_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.adjuntos: ~0 rows (aproximadamente)

-- Volcando estructura para tabla helpdesk_clonsa.areas
CREATE TABLE IF NOT EXISTS `areas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.areas: ~5 rows (aproximadamente)
INSERT INTO `areas` (`id`, `nombre`, `descripcion`, `activo`, `created_at`) VALUES
	(1, 'Soporte Técnico', 'Área de soporte técnico', 1, '2026-01-23 16:13:59'),
	(2, 'Tecnología (TI)', 'Área de TI', 1, '2026-01-23 16:13:59'),
	(3, 'Administrativo', 'Área administrativa', 1, '2026-01-23 16:13:59'),
	(4, 'Operaciones', 'Área de operaciones', 1, '2026-01-23 16:13:59'),
	(5, 'General', 'Área general', 1, '2026-01-23 16:13:59');

-- Volcando estructura para tabla helpdesk_clonsa.canales_atencion
CREATE TABLE IF NOT EXISTS `canales_atencion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.canales_atencion: ~5 rows (aproximadamente)
INSERT INTO `canales_atencion` (`id`, `nombre`, `descripcion`, `activo`, `created_at`) VALUES
	(1, 'Telefónico', 'Atención por teléfono', 1, '2026-01-23 16:13:59'),
	(2, 'Correo', 'Atención por email', 1, '2026-01-23 16:13:59'),
	(3, 'Presencial', 'Atención en oficina', 1, '2026-01-23 16:13:59'),
	(4, 'Chat', 'Atención por chat', 1, '2026-01-23 16:13:59'),
	(5, 'WhatsApp', 'Atención por WhatsApp', 1, '2026-01-23 16:13:59');

-- Volcando estructura para tabla helpdesk_clonsa.codigos_equipo
CREATE TABLE IF NOT EXISTS `codigos_equipo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipo_id` int DEFAULT NULL,
  `ubicacion_id` int DEFAULT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `departamento_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `equipo_id` (`equipo_id`),
  KEY `ubicacion_id` (`ubicacion_id`),
  CONSTRAINT `codigos_equipo_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `codigos_equipo_ibfk_2` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.codigos_equipo: ~11 rows (aproximadamente)
INSERT INTO `codigos_equipo` (`id`, `codigo`, `equipo_id`, `ubicacion_id`, `descripcion`, `activo`, `created_at`, `departamento_id`) VALUES
	(1, 'MSR181', 2, 1, 'Radar MSRConnect 181', 1, '2026-01-23 16:13:59', 2),
	(2, 'MSR184', 2, 1, 'Radar MSRConnect 184', 1, '2026-01-23 16:13:59', 2),
	(3, 'MSR234', 2, 2, 'Radar MSRConnect 234', 1, '2026-01-23 16:13:59', 2),
	(4, 'CAM001', 1, 1, 'Cámara 001', 1, '2026-01-23 16:13:59', 2),
	(5, 'CAM002', 1, 2, 'Cámara 002', 1, '2026-01-23 16:13:59', 2),
	(6, 'ADM-CONT-001', NULL, NULL, 'Sistema Contable Principal', 1, '2026-02-11 00:02:23', 3),
	(7, 'ADM-FACT-001', NULL, NULL, 'Sistema de Facturación', 1, '2026-02-11 00:02:23', 3),
	(8, 'ADM-DOC-001', NULL, NULL, 'Gestor Documental', 1, '2026-02-11 00:02:23', 3),
	(9, 'IT-WEB-001', NULL, NULL, 'Servidor Web Principal', 1, '2026-02-11 00:02:23', 4),
	(10, 'IT-BD-001', NULL, NULL, 'Servidor Base de Datos', 1, '2026-02-11 00:02:23', 4),
	(11, 'IT-ERP-001', NULL, NULL, 'Sistema ERP', 1, '2026-02-11 00:02:23', 4);

-- Volcando estructura para tabla helpdesk_clonsa.comentarios
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `comentario` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_privado` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_ticket` (`ticket_id`),
  CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.comentarios: ~0 rows (aproximadamente)

-- Volcando estructura para tabla helpdesk_clonsa.comentario_archivos
CREATE TABLE IF NOT EXISTS `comentario_archivos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `comentario_id` int NOT NULL,
  `nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamano` int DEFAULT '0',
  `tipo_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_comentario_id` (`comentario_id`),
  CONSTRAINT `comentario_archivos_ibfk_1` FOREIGN KEY (`comentario_id`) REFERENCES `ticket_comentarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.comentario_archivos: ~2 rows (aproximadamente)
INSERT INTO `comentario_archivos` (`id`, `comentario_id`, `nombre_original`, `nombre_archivo`, `ruta`, `tamano`, `tipo_mime`, `created_at`) VALUES
	(5, 41, 'evidencia_rechazo_1771092487863.png', '1771092492_rechazo_evidencia_rechazo_1771092487863.png', 'uploads/comentarios/TKN-IT-74/1771092492_rechazo_evidencia_rechazo_1771092487863.png', 295227, 'image/png', '2026-02-14 18:08:12'),
	(6, 44, 'evidencia_rechazo_1771093009080.png', '1771093017_rechazo_evidencia_rechazo_1771093009080.png', 'uploads/comentarios/TKN-ST-107/1771093017_rechazo_evidencia_rechazo_1771093009080.png', 99124, 'image/png', '2026-02-14 18:16:57');

-- Volcando estructura para tabla helpdesk_clonsa.comunicados
CREATE TABLE IF NOT EXISTS `comunicados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenido` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('actualizacion','mantenimiento','alerta','informativo') COLLATE utf8mb4_unicode_ci DEFAULT 'informativo',
  `icono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'mdi-information',
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#1F3BB3',
  `activo` tinyint(1) DEFAULT '1',
  `fecha_publicacion` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` datetime DEFAULT NULL,
  `creado_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `creado_por` (`creado_por`),
  CONSTRAINT `comunicados_ibfk_1` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.comunicados: ~3 rows (aproximadamente)
INSERT INTO `comunicados` (`id`, `titulo`, `contenido`, `tipo`, `icono`, `color`, `activo`, `fecha_publicacion`, `fecha_expiracion`, `creado_por`, `created_at`) VALUES
	(1, 'Nueva actualización v2.5 disponible', 'Se ha lanzado la versión 2.5 del sistema con mejoras en el dashboard y corrección de errores. Por favor actualice su sistema.', 'actualizacion', 'mdi-update', '#4CAF50', 1, '2026-02-01 08:04:58', NULL, 1, '2026-02-01 13:04:58'),
	(2, 'Mantenimiento programado', 'El sistema estará en mantenimiento el día 15 de febrero de 8:00 PM a 10:00 PM. Disculpe las molestias.', 'mantenimiento', 'mdi-wrench', '#FF9800', 1, '2026-02-01 08:04:58', NULL, 1, '2026-02-01 13:04:58'),
	(3, 'Nuevas políticas de seguridad', 'Se han implementado nuevas políticas de seguridad. Por favor revise sus credenciales y actualice su contraseña si es necesario.', 'alerta', 'mdi-shield-check', '#E91E63', 1, '2026-02-01 08:04:58', NULL, 1, '2026-02-01 13:04:58'),
	(4, 'Nuevo Actualizacion v2.6 Disponible', 'Se ha lanzado la versión 2.6 del sistema con mejoras en el dashboard y corrección de errores', 'actualizacion', 'mdi-update', '#4CAF50', 1, '2026-02-15 10:41:30', NULL, 1, '2026-02-15 15:41:30');

-- Volcando estructura para tabla helpdesk_clonsa.departamentos
CREATE TABLE IF NOT EXISTS `departamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abreviatura` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `jefe_id` int DEFAULT NULL COMMENT 'FK a usuarios - jefe del departamento',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_departamentos_jefe` (`jefe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.departamentos: ~4 rows (aproximadamente)
INSERT INTO `departamentos` (`id`, `nombre`, `abreviatura`, `descripcion`, `jefe_id`, `activo`, `created_at`, `updated_at`) VALUES
	(1, 'General', 'GN', 'Todos los departamentos', NULL, 1, '2026-01-25 22:02:52', '2026-02-01 13:04:02'),
	(2, 'Soporte Técnico', 'ST', 'Atención técnica y mantenimiento de equipos', 3, 1, '2026-01-25 22:02:52', '2026-02-01 13:04:02'),
	(3, 'Administración', 'AD', 'Gestión administrativa y recursos', 11, 1, '2026-01-25 22:02:52', '2026-02-01 13:04:02'),
	(4, 'IT & Desarrollo', 'IT', 'Desarrollo de software y sistemas', 8, 1, '2026-01-25 22:02:52', '2026-02-01 13:04:02');

-- Volcando estructura para tabla helpdesk_clonsa.equipos
CREATE TABLE IF NOT EXISTS `equipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `departamento_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.equipos: ~19 rows (aproximadamente)
INSERT INTO `equipos` (`id`, `nombre`, `descripcion`, `activo`, `created_at`, `departamento_id`) VALUES
	(1, 'Cámara', 'Cámara de seguridad', 1, '2026-01-23 16:13:59', 2),
	(2, 'Radar', 'Sistema de radar', 1, '2026-01-23 16:13:59', 2),
	(3, 'Servidor', 'Servidor', 1, '2026-01-23 16:13:59', 2),
	(4, 'Estación de Trabajo', 'PC', 1, '2026-01-23 16:13:59', 2),
	(5, 'Laptop', 'Laptop', 1, '2026-01-23 16:13:59', 2),
	(6, 'Switch', 'Switch de red', 1, '2026-01-23 16:13:59', 2),
	(7, 'Router', 'Router', 1, '2026-01-23 16:13:59', 2),
	(8, 'Radar MSR181', 'Equipo Radar MSR181', 1, '2026-02-10 04:10:47', 2),
	(9, 'Radar MSR184', 'Equipo Radar MSR184', 1, '2026-02-10 04:10:47', 2),
	(10, 'Radar MSR100', 'Equipo Radar MSR100', 1, '2026-02-10 04:10:47', 2),
	(11, 'Radar MSR234', 'Equipo Radar MSR234', 1, '2026-02-10 04:10:47', 2),
	(12, 'Radar MSR237', 'Equipo Radar MSR237', 1, '2026-02-10 04:10:48', 2),
	(13, 'Radar MSR103', 'Equipo Radar MSR103', 1, '2026-02-10 04:10:48', 2),
	(14, 'Sistema Contable', 'Sistema de contabilidad', 1, '2026-02-11 00:02:23', 3),
	(15, 'Sistema de Facturación', 'Sistema de facturación electrónica', 1, '2026-02-11 00:02:23', 3),
	(16, 'Gestor Documental', 'Sistema de gestión documental', 1, '2026-02-11 00:02:23', 3),
	(17, 'Servidor Web', 'Servidor de aplicaciones web', 1, '2026-02-11 00:02:23', 4),
	(18, 'Servidor BD', 'Servidor de base de datos', 1, '2026-02-11 00:02:23', 4),
	(19, 'Sistema ERP', 'Sistema ERP corporativo', 1, '2026-02-11 00:02:23', 4);

-- Volcando estructura para tabla helpdesk_clonsa.estados
CREATE TABLE IF NOT EXISTS `estados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_final` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.estados: ~4 rows (aproximadamente)
INSERT INTO `estados` (`id`, `nombre`, `descripcion`, `color`, `es_final`, `created_at`) VALUES
	(1, 'Abierto', 'Ticket recién creado', '#fd7e14', 0, '2026-01-23 16:13:59'),
	(2, 'En Atención', 'Ticket en atención', '#17a2b8', 0, '2026-01-23 16:13:59'),
	(4, 'Resuelto', 'Ticket resuelto', '#28a745', 1, '2026-01-23 16:13:59'),
	(5, 'Rechazado', 'Ticket cerrado', '#dc3545', 1, '2026-01-23 16:13:59');

-- Volcando estructura para tabla helpdesk_clonsa.historial
CREATE TABLE IF NOT EXISTS `historial` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `accion` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `campo_modificado` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor_anterior` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `valor_nuevo` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_ticket` (`ticket_id`),
  CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=574 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.historial: ~472 rows (aproximadamente)
INSERT INTO `historial` (`id`, `ticket_id`, `usuario_id`, `accion`, `campo_modificado`, `valor_anterior`, `valor_nuevo`, `created_at`) VALUES
	(23, 44, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(24, 45, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(25, 46, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(26, 47, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(27, 48, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(28, 49, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(29, 50, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(30, 51, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(31, 52, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(32, 53, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(33, 54, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(34, 55, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(35, 56, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(36, 57, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(37, 58, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(38, 59, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(39, 60, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(40, 61, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(41, 62, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(42, 63, 1, 'Ticket creado', NULL, NULL, NULL, '2026-01-27 23:13:27'),
	(43, 64, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(44, 65, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(45, 66, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(46, 67, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(47, 68, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(48, 69, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(49, 70, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(50, 71, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(51, 72, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(52, 73, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(53, 74, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(54, 75, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(55, 76, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(56, 77, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(57, 78, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(58, 79, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(59, 80, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(60, 81, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(61, 82, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(62, 83, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(63, 84, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(64, 85, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(65, 86, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(66, 87, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(67, 88, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(68, 89, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(69, 90, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(70, 91, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(71, 92, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(72, 93, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(73, 94, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(74, 95, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(75, 96, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(76, 97, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(77, 98, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(78, 99, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(79, 100, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(80, 101, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(81, 102, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(82, 103, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(83, 104, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(84, 105, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(85, 106, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(86, 107, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(87, 108, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(88, 109, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(89, 110, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(90, 111, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(91, 112, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(92, 113, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(93, 114, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(94, 115, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(95, 116, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(96, 117, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(97, 118, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(98, 119, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(99, 120, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(100, 121, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(101, 122, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(102, 123, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(103, 124, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(104, 125, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(105, 126, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(106, 127, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(107, 128, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(108, 129, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(109, 130, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(110, 131, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(111, 132, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(112, 133, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(113, 134, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(114, 135, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(115, 136, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(116, 137, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(117, 138, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(118, 139, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(119, 140, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(120, 141, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(121, 142, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(122, 143, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(123, 144, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(124, 145, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(125, 146, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(126, 147, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(127, 148, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(128, 149, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(129, 150, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(130, 151, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(131, 152, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(132, 153, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(133, 154, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(134, 155, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(135, 156, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(136, 157, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(137, 158, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(138, 159, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(139, 160, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(140, 161, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(141, 162, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(142, 163, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(143, 164, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(144, 165, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(145, 166, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(146, 167, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(147, 168, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(148, 169, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(149, 170, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(150, 171, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(151, 172, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(152, 173, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(153, 174, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(154, 175, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(155, 176, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(156, 177, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(157, 178, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(158, 179, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(159, 180, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(160, 181, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(161, 182, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(162, 183, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(163, 184, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(164, 185, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(165, 186, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(166, 187, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(167, 188, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(168, 189, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(169, 190, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(170, 191, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(171, 192, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(172, 193, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(173, 194, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(174, 195, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(175, 196, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(176, 197, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(177, 198, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(178, 199, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(179, 200, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(180, 201, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(181, 202, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(182, 203, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(183, 204, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(184, 205, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(185, 206, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(186, 207, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(187, 208, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(188, 209, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(189, 210, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(190, 211, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(191, 212, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(192, 213, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(193, 214, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(194, 215, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(195, 216, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(196, 217, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(197, 218, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(198, 219, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(199, 220, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(200, 221, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(201, 222, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(202, 223, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 13:07:13'),
	(203, 224, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(204, 225, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(205, 226, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(206, 227, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(207, 228, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(208, 229, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(209, 230, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(210, 231, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(211, 232, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(212, 233, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(213, 234, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(214, 235, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(215, 236, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(216, 237, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(217, 238, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(218, 239, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(219, 240, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(220, 241, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(221, 242, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(222, 243, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(223, 244, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(224, 245, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(225, 246, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(226, 247, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(227, 248, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(228, 249, 4, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(229, 250, 5, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(230, 251, 6, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(231, 252, 7, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(232, 253, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17'),
	(233, 244, 3, 'Estado cambiado', 'estado', 'Cerrado', 'En Proceso', '2026-01-31 18:29:10'),
	(234, 244, 3, 'Progreso actualizado', 'progreso', '100%', '50%', '2026-01-31 18:29:10'),
	(235, 245, 4, 'Estado cambiado', 'estado', 'Cerrado', 'En Proceso', '2026-01-31 18:29:10'),
	(236, 245, 4, 'Progreso actualizado', 'progreso', '100%', '50%', '2026-01-31 18:29:10'),
	(237, 246, 8, 'Estado cambiado', 'estado', 'Cerrado', 'En Proceso', '2026-01-31 18:29:10'),
	(238, 246, 8, 'Progreso actualizado', 'progreso', '100%', '50%', '2026-01-31 18:29:10'),
	(239, 247, 9, 'Estado cambiado', 'estado', 'Cerrado', 'En Proceso', '2026-01-31 18:29:10'),
	(240, 247, 9, 'Progreso actualizado', 'progreso', '100%', '50%', '2026-01-31 18:29:10'),
	(241, 248, 11, 'Estado cambiado', 'estado', 'Cerrado', 'Pendiente Usuario', '2026-01-31 18:29:10'),
	(242, 248, 11, 'Progreso actualizado', 'progreso', '100%', '30%', '2026-01-31 18:29:10'),
	(243, 249, 12, 'Estado cambiado', 'estado', 'Cerrado', 'Pendiente Usuario', '2026-01-31 18:29:10'),
	(244, 249, 12, 'Progreso actualizado', 'progreso', '100%', '30%', '2026-01-31 18:29:10'),
	(245, 250, 13, 'Estado cambiado', 'estado', 'Cerrado', 'Resuelto', '2026-01-31 18:29:10'),
	(246, 251, 7, 'Estado cambiado', 'estado', 'Cerrado', 'Resuelto', '2026-01-31 18:29:10'),
	(247, 252, 10, 'Estado cambiado', 'estado', 'Cerrado', 'Resuelto', '2026-01-31 18:29:10'),
	(248, 254, 8, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:23:12'),
	(249, 255, 8, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:23:12'),
	(250, 256, 8, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:23:12'),
	(251, 257, 9, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:23:12'),
	(252, 258, 9, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:23:12'),
	(253, 259, 10, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:23:12'),
	(254, 260, 3, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(255, 261, 3, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(256, 262, 3, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(257, 263, 3, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(258, 264, 4, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(259, 265, 4, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(260, 266, 4, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(261, 267, 5, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(262, 268, 5, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(263, 269, 6, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(264, 270, 6, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(265, 271, 7, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 03:31:48'),
	(266, 272, 1, 'Ticket creado', NULL, NULL, NULL, '2026-02-01 13:09:16'),
	(276, 281, 1, 'Ticket creado', NULL, NULL, NULL, '2026-02-10 16:23:35'),
	(277, 282, 1, 'Ticket creado', NULL, NULL, NULL, '2026-02-10 16:35:02'),
	(278, 283, 1, 'Ticket creado', NULL, NULL, NULL, '2026-02-11 00:29:24'),
	(280, 285, 1, 'Ticket creado', NULL, NULL, NULL, '2026-02-11 00:50:34'),
	(281, 286, 1, 'Ticket creado', NULL, NULL, NULL, '2026-02-11 00:56:58'),
	(282, 287, 1, 'Ticket creado', NULL, NULL, NULL, '2026-02-11 01:14:04'),
	(283, 288, 1, 'Ticket creado', NULL, NULL, NULL, '2026-02-11 01:15:39'),
	(284, 288, 9, 'Progreso actualizado', 'progreso', '0%', '60%', '2026-02-12 00:38:26'),
	(285, 288, 9, 'Estado cambiado', 'estado', 'Abierto', 'Resuelto', '2026-02-12 00:38:39'),
	(286, 288, 9, 'Estado cambiado', 'estado', 'Resuelto', 'Rechazado', '2026-02-12 00:38:51'),
	(287, 288, 9, 'Estado cambiado', 'estado', 'Rechazado', 'Abierto', '2026-02-12 00:39:02'),
	(288, 288, 9, 'Estado cambiado', 'estado', 'Abierto', 'Cerrado', '2026-02-12 00:42:55'),
	(289, 288, 9, 'Progreso actualizado', 'progreso', '60%', '100%', '2026-02-12 05:11:04'),
	(290, 288, 9, 'Progreso actualizado', 'progreso', '100%', '5%', '2026-02-12 05:11:19'),
	(291, 288, 9, 'Progreso actualizado', 'progreso', '5%', '60%', '2026-02-12 05:11:20'),
	(292, 288, 8, 'Progreso actualizado', 'progreso', '60%', '75%', '2026-02-12 05:54:18'),
	(293, 288, 8, 'Progreso actualizado', 'progreso', '75%', '95%', '2026-02-12 05:54:22'),
	(294, 288, 8, 'Progreso actualizado', 'progreso', '95%', '98%', '2026-02-12 05:57:56'),
	(295, 288, 8, 'Progreso actualizado', 'progreso', '98%', '100%', '2026-02-12 05:58:06'),
	(296, 288, 8, 'Progreso actualizado', 'progreso', '100%', '0%', '2026-02-12 06:03:27'),
	(297, 287, 1, 'Estado cambiado', 'estado', 'Abierto', 'Cerrado', '2026-02-12 06:09:18'),
	(298, 287, 1, 'Progreso actualizado', 'progreso', '0%', '100%', '2026-02-12 06:09:18'),
	(299, 286, 8, 'Estado cambiado', 'estado', 'Abierto', 'Cerrado', '2026-02-12 14:13:57'),
	(300, 286, 8, 'Progreso actualizado', 'progreso', '0%', '100%', '2026-02-12 14:13:57'),
	(301, 285, 1, 'Estado cambiado', 'estado', 'Abierto', 'Cerrado', '2026-02-12 14:14:07'),
	(302, 285, 1, 'Progreso actualizado', 'progreso', '0%', '100%', '2026-02-12 14:14:07'),
	(307, 248, 11, 'Estado cambiado', 'estado', 'Pendiente Usuario', 'En Proceso', '2026-02-12 22:10:16'),
	(308, 249, 12, 'Estado cambiado', 'estado', 'Pendiente Usuario', 'En Proceso', '2026-02-12 22:10:16'),
	(309, 289, 1, 'Ticket creado', NULL, NULL, NULL, '2026-02-12 22:12:54'),
	(310, 289, 7, 'Estado cambiado', 'estado', 'Abierto', 'Cerrado', '2026-02-12 22:21:59'),
	(311, 289, 7, 'Progreso actualizado', 'progreso', '0%', '100%', '2026-02-12 22:21:59'),
	(312, 288, 8, 'Progreso actualizado', 'progreso', '0%', '100%', '2026-02-12 22:22:07'),
	(313, 64, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(314, 65, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(315, 66, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(316, 67, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(317, 68, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(318, 69, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(319, 70, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(320, 71, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(321, 72, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(322, 73, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(323, 74, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(324, 75, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(325, 76, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(326, 77, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(327, 78, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(328, 79, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(329, 80, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(330, 81, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(331, 82, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(332, 83, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(333, 84, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(334, 85, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(335, 86, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(336, 87, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(337, 88, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(338, 89, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(339, 90, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(340, 91, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(341, 92, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(342, 93, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(343, 94, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(344, 95, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(345, 96, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(346, 97, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(347, 98, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(348, 99, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(349, 100, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(350, 101, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(351, 102, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(352, 103, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(353, 104, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(354, 105, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(355, 106, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(356, 107, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(357, 108, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(358, 109, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(359, 110, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(360, 111, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(361, 112, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(362, 113, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(363, 114, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(364, 115, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(365, 116, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(366, 117, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(367, 118, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(368, 119, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(369, 120, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(370, 121, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(371, 122, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(372, 123, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(373, 124, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(374, 125, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(375, 126, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(376, 127, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(377, 128, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(378, 129, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(379, 130, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(380, 131, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(381, 132, 6, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(382, 133, 6, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(383, 134, 6, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(384, 135, 6, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(385, 136, 6, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(386, 137, 6, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(387, 138, 6, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(388, 139, 6, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(389, 140, 6, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(390, 141, 6, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(391, 142, 7, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(392, 143, 7, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(393, 144, 7, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(394, 145, 7, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(395, 146, 7, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(396, 147, 7, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(397, 148, 7, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(398, 149, 7, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(399, 150, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(400, 151, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(401, 152, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(402, 153, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(403, 154, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(404, 155, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(405, 156, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(406, 157, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(407, 158, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(408, 159, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(409, 160, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(410, 161, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(411, 162, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(412, 163, 12, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(413, 164, 12, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(414, 165, 12, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(415, 166, 12, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(416, 167, 12, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(417, 168, 12, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(418, 169, 12, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(419, 170, 12, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(420, 171, 12, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(421, 172, 13, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(422, 173, 13, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(423, 174, 13, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(424, 175, 13, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(425, 176, 13, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(426, 177, 13, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(427, 178, 13, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(428, 179, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(429, 180, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(430, 181, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(431, 182, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(432, 183, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(433, 184, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(434, 185, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(435, 186, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(436, 187, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(437, 188, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(438, 189, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(439, 190, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(440, 191, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(441, 192, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(442, 193, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(443, 194, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(444, 195, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(445, 196, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(446, 197, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(447, 198, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(448, 199, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(449, 200, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(450, 201, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(451, 202, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(452, 203, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(453, 204, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(454, 205, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(455, 206, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(456, 207, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(457, 208, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(458, 209, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(459, 210, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(460, 211, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(461, 212, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(462, 213, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(463, 214, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(464, 215, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(465, 216, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(466, 217, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(467, 218, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(468, 219, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(469, 220, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(470, 221, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(471, 222, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(472, 223, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(473, 224, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(474, 225, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(475, 226, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(476, 227, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(477, 228, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(478, 229, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(479, 230, 12, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(480, 231, 13, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(481, 232, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(482, 233, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(483, 234, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(484, 235, 4, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(485, 236, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(486, 237, 9, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(487, 238, 11, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(488, 239, 12, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(489, 240, 13, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(490, 241, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(491, 242, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(492, 243, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(493, 253, 10, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(495, 285, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(496, 286, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(497, 287, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(498, 288, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(499, 289, 7, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 22:39:57'),
	(501, 289, 7, 'Estado cambiado', 'estado', 'Rechazado', 'En Atención', '2026-02-12 22:43:27'),
	(502, 288, 8, 'Progreso actualizado', 'progreso', '90%', '100%', '2026-02-12 22:59:50'),
	(503, 288, 8, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-12 23:02:43'),
	(504, 289, 7, 'Estado cambiado', 'estado', 'En Atención', 'Rechazado', '2026-02-12 23:03:09'),
	(505, 289, 7, 'Progreso actualizado', 'progreso', '90%', '100%', '2026-02-12 23:03:09'),
	(506, 289, 7, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-12 23:03:18'),
	(507, 288, 8, 'Estado cambiado', 'estado', 'Resuelto', 'Rechazado', '2026-02-12 23:09:15'),
	(508, 288, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-12 23:09:15'),
	(509, 288, 8, 'Estado cambiado', 'estado', 'Rechazado', 'En Atención', '2026-02-12 23:13:27'),
	(510, 288, 8, 'Progreso actualizado', 'progreso', '90%', '99%', '2026-02-12 23:13:27'),
	(511, 288, 8, 'Estado cambiado', 'estado', 'En Atención', 'Rechazado', '2026-02-12 23:14:00'),
	(512, 288, 8, 'Progreso actualizado', 'progreso', '99%', '100%', '2026-02-12 23:14:00'),
	(513, 288, 8, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-12 23:14:14'),
	(514, 287, 1, 'Progreso actualizado', 'progreso', '90%', '100%', '2026-02-12 23:14:26'),
	(515, 287, 1, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-12 23:14:42'),
	(516, 286, 8, 'Progreso actualizado', 'progreso', '90%', '100%', '2026-02-12 23:15:33'),
	(517, 286, 8, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-12 23:15:50'),
	(518, 285, 1, 'Progreso actualizado', 'progreso', '90%', '100%', '2026-02-12 23:18:51'),
	(519, 285, 1, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-12 23:18:57'),
	(520, 283, 3, 'Estado cambiado', 'estado', 'Abierto', 'En Atención', '2026-02-12 23:21:44'),
	(521, 283, 3, 'Progreso actualizado', 'progreso', '0%', '10%', '2026-02-12 23:21:44'),
	(522, 283, 3, 'Estado cambiado', 'estado', 'En Atención', 'Rechazado', '2026-02-12 23:22:10'),
	(523, 283, 3, 'Progreso actualizado', 'progreso', '10%', '100%', '2026-02-12 23:22:10'),
	(524, 283, 3, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-12 23:22:37'),
	(525, 282, 3, 'Estado cambiado', 'estado', 'Abierto', 'Rechazado', '2026-02-14 17:19:55'),
	(526, 282, 3, 'Progreso actualizado', 'progreso', '0%', '100%', '2026-02-14 17:19:55'),
	(527, 282, 3, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-14 17:20:05'),
	(528, 282, 3, 'Estado cambiado', 'estado', 'Resuelto', 'Rechazado', '2026-02-14 17:21:38'),
	(529, 282, 3, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-14 17:21:38'),
	(530, 282, 3, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-14 17:28:20'),
	(531, 282, 3, 'Progreso actualizado', 'progreso', '90%', '100%', '2026-02-14 17:28:20'),
	(534, 288, 8, 'Estado cambiado', 'estado', 'Resuelto', 'Rechazado', '2026-02-14 17:40:30'),
	(535, 288, 8, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-14 17:40:30'),
	(538, 287, 1, 'Estado cambiado', 'estado', 'Resuelto', 'Rechazado', '2026-02-14 18:08:12'),
	(539, 287, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-14 18:08:12'),
	(540, 281, 1, 'Estado cambiado', 'estado', 'Abierto', 'Resuelto', '2026-02-14 18:16:32'),
	(541, 281, 1, 'Progreso actualizado', 'progreso', '0%', '100%', '2026-02-14 18:16:32'),
	(542, 285, 1, 'Estado cambiado', 'estado', 'Resuelto', 'Rechazado', '2026-02-14 18:16:57'),
	(543, 285, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-14 18:16:57'),
	(544, 288, 8, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-14 18:22:55'),
	(545, 288, 8, 'Progreso actualizado', 'progreso', '90%', '100%', '2026-02-14 18:22:55'),
	(546, 287, 1, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-14 18:23:07'),
	(547, 287, 1, 'Progreso actualizado', 'progreso', '90%', '100%', '2026-02-14 18:23:07'),
	(548, 285, 1, 'Estado cambiado', 'estado', 'Rechazado', 'En Atención', '2026-02-15 01:25:35'),
	(549, 285, 1, 'Progreso actualizado', 'progreso', '90%', '91%', '2026-02-15 01:25:35'),
	(550, 285, 1, 'Estado cambiado', 'estado', 'En Atención', 'Resuelto', '2026-02-15 01:26:21'),
	(551, 285, 1, 'Progreso actualizado', 'progreso', '91%', '100%', '2026-02-15 01:26:21'),
	(552, 253, 9, 'Estado cambiado', 'estado', 'Rechazado', 'En Atención', '2026-02-15 01:34:48'),
	(553, 290, 1, 'Ticket creado', NULL, NULL, NULL, '2026-02-15 02:02:54'),
	(554, 290, 1, 'Estado cambiado', 'estado', 'Abierto', 'En Atención', '2026-02-18 21:29:00'),
	(555, 290, 1, 'Progreso actualizado', 'progreso', '0%', '40%', '2026-02-18 21:29:00'),
	(556, 290, 1, 'Estado cambiado', 'estado', 'En Atención', 'Resuelto', '2026-02-18 21:29:13'),
	(557, 290, 1, 'Progreso actualizado', 'progreso', '40%', '100%', '2026-02-18 21:29:13'),
	(558, 290, 1, 'Estado cambiado', 'estado', 'Resuelto', 'Rechazado', '2026-02-18 21:30:07'),
	(559, 290, 1, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-18 21:30:07'),
	(560, 290, 1, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-18 21:30:33'),
	(561, 290, 1, 'Progreso actualizado', 'progreso', '90%', '100%', '2026-02-18 21:30:33'),
	(562, 291, 3, 'Ticket creado', NULL, NULL, NULL, '2026-02-19 23:48:48'),
	(563, 244, 3, 'Progreso actualizado', 'progreso', '50%', '60%', '2026-02-19 23:57:35'),
	(564, 101, 6, 'Estado cambiado', 'estado', 'Rechazado', 'En Atención', '2026-02-20 00:02:00'),
	(565, 292, 3, 'Ticket creado', NULL, NULL, NULL, '2026-02-20 16:29:01'),
	(566, 292, 10, 'Estado cambiado', 'estado', 'Abierto', 'En Atención', '2026-02-20 19:18:41'),
	(567, 292, 10, 'Progreso actualizado', 'progreso', '0%', '50%', '2026-02-20 19:18:41'),
	(568, 291, 5, 'Estado cambiado', 'estado', 'Abierto', 'Resuelto', '2026-02-20 19:18:58'),
	(569, 291, 5, 'Progreso actualizado', 'progreso', '0%', '100%', '2026-02-20 19:18:58'),
	(570, 291, 5, 'Estado cambiado', 'estado', 'Resuelto', 'Rechazado', '2026-02-20 19:19:36'),
	(571, 291, 5, 'Progreso actualizado', 'progreso', '100%', '90%', '2026-02-20 19:19:36'),
	(572, 291, 5, 'Estado cambiado', 'estado', 'Rechazado', 'Resuelto', '2026-02-20 19:19:51'),
	(573, 291, 5, 'Progreso actualizado', 'progreso', '90%', '100%', '2026-02-20 19:19:51');

-- Volcando estructura para tabla helpdesk_clonsa.notificaciones
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `ticket_id` int DEFAULT NULL,
  `tipo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `leida` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_leida` (`leida`),
  CONSTRAINT `notificaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notificaciones_ibfk_2` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.notificaciones: ~0 rows (aproximadamente)

-- Volcando estructura para tabla helpdesk_clonsa.notificaciones_leidas
CREATE TABLE IF NOT EXISTS `notificaciones_leidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `tipo` enum('ticket','comunicado') COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia_id` int NOT NULL,
  `leida_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_notif` (`usuario_id`,`tipo`,`referencia_id`),
  CONSTRAINT `notificaciones_leidas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.notificaciones_leidas: ~8 rows (aproximadamente)
INSERT INTO `notificaciones_leidas` (`id`, `usuario_id`, `tipo`, `referencia_id`, `leida_at`) VALUES
	(9, 1, 'comunicado', 1, '2026-02-01 13:36:04'),
	(10, 1, 'ticket', 252, '2026-02-01 13:36:13'),
	(11, 1, 'ticket', 285, '2026-02-11 00:51:02'),
	(12, 1, '', 287, '2026-02-15 01:16:56'),
	(13, 1, '', 285, '2026-02-15 01:42:55'),
	(15, 1, '', 290, '2026-02-15 02:53:15'),
	(16, 1, 'ticket', 290, '2026-02-16 17:12:55'),
	(17, 1, '', 288, '2026-02-16 22:19:35'),
	(18, 3, 'ticket', 292, '2026-02-20 18:40:06'),
	(19, 3, '', 84, '2026-02-20 18:40:17');

-- Volcando estructura para tabla helpdesk_clonsa.prioridades
CREATE TABLE IF NOT EXISTS `prioridades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel` int NOT NULL,
  `color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.prioridades: ~4 rows (aproximadamente)
INSERT INTO `prioridades` (`id`, `nombre`, `nivel`, `color`, `created_at`) VALUES
	(1, 'Baja', 1, '#28a745', '2026-01-23 16:13:59'),
	(2, 'Media', 2, '#ffc107', '2026-01-23 16:13:59'),
	(3, 'Alta', 3, '#fd7e14', '2026-01-23 16:13:59'),
	(4, 'Crítica', 4, '#dc3545', '2026-01-23 16:13:59');

-- Volcando estructura para tabla helpdesk_clonsa.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `permisos` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.roles: ~3 rows (aproximadamente)
INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `permisos`, `created_at`, `updated_at`) VALUES
	(1, 'Administrador', 'Acceso total al sistema', '{"crear": true, "editar": true, "eliminar": true, "ver_todo": true, "gestionar_usuarios": true}', '2026-01-23 16:13:59', '2026-01-26 00:08:43'),
	(2, 'Jefe', 'Jefe de departamento', '{"crear": true, "editar": true, "asignar": true, "ver_area": true, "gestionar_equipo": true}', '2026-01-23 16:13:59', '2026-01-26 00:08:43'),
	(3, 'Usuario', 'Usuario final del sistema', '{"crear": true, "editar": true, "asignar": true, "ver_area": true, "gestionar_equipo": true}', '2026-01-23 16:13:59', '2026-01-26 00:08:43');

-- Volcando estructura para tabla helpdesk_clonsa.tickets
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `departamento_id` int DEFAULT '1',
  `codigo` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int NOT NULL,
  `area_id` int DEFAULT NULL,
  `prioridad_id` int DEFAULT '2',
  `estado_id` int DEFAULT '1',
  `asignado_a` int DEFAULT NULL,
  `canal_atencion_id` int DEFAULT NULL,
  `actividad_id` int DEFAULT NULL,
  `tipo_falla_id` int DEFAULT NULL,
  `ubicacion_id` int DEFAULT NULL,
  `equipo_id` int DEFAULT NULL,
  `codigo_equipo_id` int DEFAULT NULL,
  `progreso` int DEFAULT '0',
  `pendiente_aprobacion` tinyint(1) DEFAULT '0',
  `aprobado_por` int DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `fecha_limite` timestamp NULL DEFAULT NULL,
  `fecha_resolucion` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `solicitante_nombre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `solicitante_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `solicitante_telefono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `area_id` (`area_id`),
  KEY `prioridad_id` (`prioridad_id`),
  KEY `asignado_a` (`asignado_a`),
  KEY `canal_atencion_id` (`canal_atencion_id`),
  KEY `actividad_id` (`actividad_id`),
  KEY `tipo_falla_id` (`tipo_falla_id`),
  KEY `ubicacion_id` (`ubicacion_id`),
  KEY `equipo_id` (`equipo_id`),
  KEY `codigo_equipo_id` (`codigo_equipo_id`),
  KEY `idx_codigo` (`codigo`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_estado` (`estado_id`),
  KEY `idx_progreso` (`progreso`),
  KEY `fk_tickets_departamento` (`departamento_id`),
  CONSTRAINT `fk_tickets_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `tickets_ibfk_10` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_11` FOREIGN KEY (`codigo_equipo_id`) REFERENCES `codigos_equipo` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`prioridad_id`) REFERENCES `prioridades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`estado_id`) REFERENCES `estados` (`id`),
  CONSTRAINT `tickets_ibfk_5` FOREIGN KEY (`asignado_a`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_6` FOREIGN KEY (`canal_atencion_id`) REFERENCES `canales_atencion` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_7` FOREIGN KEY (`actividad_id`) REFERENCES `actividades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_8` FOREIGN KEY (`tipo_falla_id`) REFERENCES `tipos_falla` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_9` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=293 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.tickets: ~237 rows (aproximadamente)
INSERT INTO `tickets` (`id`, `departamento_id`, `codigo`, `titulo`, `descripcion`, `usuario_id`, `area_id`, `prioridad_id`, `estado_id`, `asignado_a`, `canal_atencion_id`, `actividad_id`, `tipo_falla_id`, `ubicacion_id`, `equipo_id`, `codigo_equipo_id`, `progreso`, `pendiente_aprobacion`, `aprobado_por`, `fecha_aprobacion`, `fecha_limite`, `fecha_resolucion`, `created_at`, `updated_at`, `solicitante_nombre`, `solicitante_email`, `solicitante_telefono`) VALUES
	(44, 2, 'TKT-2026-0001', 'Mantenimiento preventivo', 'Revisión mensual', 1, NULL, 1, 1, 1, 1, 1, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-20 14:00:00', '2026-01-27 23:13:27', NULL, NULL, NULL),
	(45, 2, 'TKT-2026-0002', 'Actualización antivirus', 'Actualización', 1, NULL, 1, 1, 1, 1, 1, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-21 15:30:00', '2026-01-27 23:13:27', NULL, NULL, NULL),
	(46, 2, 'TKT-2026-0003', 'Backup semanal', 'Verificación', 1, NULL, 1, 1, 1, 1, 1, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-22 13:00:00', '2026-01-27 23:13:27', NULL, NULL, NULL),
	(47, 2, 'TKT-2026-0004', 'Falla impresora', 'Papel atascado', 1, NULL, 1, 1, 1, 1, 2, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-19 19:20:00', '2026-01-27 23:13:27', NULL, NULL, NULL),
	(48, 2, 'TKT-2026-0005', 'PC no enciende', 'No responde', 1, NULL, 1, 1, 1, 1, 2, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-23 16:00:00', '2026-01-27 23:13:27', NULL, NULL, NULL),
	(49, 2, 'TKT-2026-0006', 'Teclado fallando', 'Teclas rotas', 1, NULL, 1, 1, 1, 1, 2, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-24 20:45:00', '2026-01-27 23:13:27', NULL, NULL, NULL),
	(50, 2, 'TKT-2026-0007', 'Análisis rendimiento', 'Monitoreo', 1, NULL, 1, 1, 1, 1, 3, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-18 13:30:00', '2026-01-27 23:13:27', NULL, NULL, NULL),
	(51, 2, 'TKT-2026-0008', 'Revisión logs', 'Análisis', 1, NULL, 1, 1, 1, 1, 3, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-25 14:15:00', '2026-01-27 23:13:27', NULL, NULL, NULL),
	(52, 2, 'TKT-2026-0009', 'Config radar MSR', 'Instalación', 1, NULL, 1, 1, 1, 1, 4, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-26 12:00:00', '2026-01-27 23:13:27', NULL, NULL, NULL),
	(53, 4, 'TKT-2026-0010', 'Actualización MSR', 'Upgrade v3.2', 8, NULL, 1, 1, 1, 1, 4, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-20 15:00:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(54, 4, 'TKT-2026-0011', 'Bug en MSR', 'Error reporte', 9, NULL, 1, 1, 1, 1, 4, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-22 18:30:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(55, 4, 'TKT-2026-0012', 'Soporte Lima', 'Config VPN', 10, NULL, 1, 1, 1, 1, 5, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-19 16:00:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(56, 4, 'TKT-2026-0013', 'Instalación Office', 'Office 365', 8, NULL, 1, 1, 1, 1, 5, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-21 14:30:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(57, 4, 'TKT-2026-0014', 'Migración servidor', 'Santiago', 9, NULL, 1, 1, 1, 1, 6, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-23 13:00:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(58, 4, 'TKT-2026-0015', 'Soporte Chile', 'Conectividad', 10, NULL, 1, 1, 1, 1, 6, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-24 15:15:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(59, 4, 'TKT-2026-0016', 'Desarrollo', 'Facturación', 8, NULL, 1, 1, 1, 1, 7, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-18 19:00:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(60, 4, 'TKT-2026-0017', 'Integración API', 'Proveedor', 9, NULL, 1, 1, 1, 1, 7, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-25 16:30:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(61, 2, 'TKT-2026-0018', 'Mantenimiento UPS', 'Revisión', 1, NULL, 1, 1, 1, 1, 1, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-17 12:30:00', '2026-01-27 23:13:27', NULL, NULL, NULL),
	(62, 2, 'TKT-2026-0019', 'Reparación switch', 'Puertos', 1, NULL, 1, 1, 1, 1, 2, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-20 21:00:00', '2026-01-27 23:13:27', NULL, NULL, NULL),
	(63, 4, 'TKT-2026-0020', 'Deploy', 'Producción', 10, NULL, 1, 1, 1, 1, 7, 1, 1, 1, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-01-26 17:00:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(64, 1, 'TKT-2026-0021', 'Gestión Documental', 'Organizar documentación', 3, NULL, 2, 5, 1, 1, 7, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-17 17:00:00', '2026-01-17 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(65, 1, 'TKT-2026-0022', 'Compra Suministros', 'Adquisición material', 4, NULL, 2, 5, 1, 2, 7, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-17 22:00:00', '2026-01-17 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(66, 1, 'TKT-2026-0023', 'Coordinación Evento', 'Organizar reunión', 5, NULL, 1, 5, 1, 3, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-18 21:00:00', '2026-01-18 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(67, 1, 'TKT-2026-0024', 'Actualización Políticas', 'Revisar manual', 6, NULL, 2, 5, 1, 1, 7, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-19 20:00:00', '2026-01-19 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(68, 1, 'TKT-2026-0025', 'Gestión Contratos', 'Renovación contratos', 7, NULL, 2, 5, 1, 2, 7, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-20 19:30:00', '2026-01-20 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(69, 1, 'TKT-2026-0026', 'Auditoría Interna', 'Preparar documentación', 3, NULL, 1, 5, 1, 3, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 22:00:00', '2026-01-21 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(70, 1, 'TKT-2026-0027', 'Planificación Presupuesto', 'Elaborar presupuesto', 4, NULL, 1, 5, 1, 1, 7, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-23 21:00:00', '2026-01-22 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(71, 1, 'TKT-2026-0028', 'Gestión Licencias', 'Renovar licencias', 5, NULL, 2, 5, 1, 2, 7, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-23 19:00:00', '2026-01-23 15:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(72, 1, 'TKT-2026-0029', 'Coordinación Capacitación', 'Organizar programa', 6, NULL, 2, 5, 1, 3, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 17:45:00', '2026-01-24 13:15:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(73, 1, 'TKT-2026-0030', 'Gestión Seguros', 'Renovación pólizas', 7, NULL, 2, 5, 1, 1, 7, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 18:30:00', '2026-01-25 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(74, 1, 'TKT-2026-0031', 'Actualización Base Datos', 'Depurar información', 3, NULL, 2, 5, 1, 2, 7, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 20:00:00', '2026-01-26 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(75, 1, 'TKT-2026-0032', 'Coordinación Logística', 'Gestionar calendario', 4, NULL, 2, 5, 1, 3, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 18:15:00', '2026-01-27 13:45:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(76, 1, 'TKT-2026-0033', 'Gestión Archivo', 'Digitalizar documentos', 5, NULL, 2, 5, 1, 1, 7, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 21:30:00', '2026-01-28 14:15:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(77, 1, 'TKT-2026-0034', 'Actualización Directorio', 'Actualizar extensiones', 6, NULL, 2, 5, 1, 2, 7, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 22:00:00', '2026-01-28 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(78, 1, 'TKT-2026-0035', 'Coordinación Viajes', 'Gestionar itinerarios', 7, NULL, 2, 5, 1, 3, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 17:00:00', '2026-01-29 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(79, 1, 'TKT-2026-0036', 'Gestión Correspondencia', 'Atender requerimientos', 3, NULL, 3, 5, 1, 1, 7, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 21:45:00', '2026-01-29 18:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(80, 1, 'TKT-2026-0037', 'Actualización Organigrama', 'Actualizar estructura', 4, NULL, 2, 5, 1, 2, 7, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 17:30:00', '2026-01-30 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(81, 1, 'TKT-2026-0038', 'Relaciones Públicas', 'Gestionar comunicados', 5, NULL, 2, 5, 1, 3, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 22:30:00', '2026-01-30 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(82, 2, 'TKT-2026-0039', 'Mant. Preventivo Radar', 'Revisión mensual', 4, NULL, 2, 5, 3, 1, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-17 19:00:00', '2026-01-17 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(83, 2, 'TKT-2026-0040', 'Mant. Correctivo Radar', 'Reparación sensor', 5, NULL, 3, 5, 3, 2, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-17 23:30:00', '2026-01-17 20:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(84, 2, 'TKT-2026-0041', 'Mant. Preventivo CCTV', 'Limpieza cámaras', 6, NULL, 2, 5, 3, 3, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-18 17:30:00', '2026-01-18 13:30:00', '2026-02-19 23:55:51', '', '', ''),
	(85, 2, 'TKT-2026-0042', 'Mant. Predictivo Equipos', 'Análisis vibraciones', 7, NULL, 2, 5, 3, 1, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-18 22:00:00', '2026-01-18 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(86, 2, 'TKT-2026-0043', 'Mant. Correctivo PLC', 'Reparar controlador', 4, NULL, 3, 5, 3, 2, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-19 20:00:00', '2026-01-19 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(87, 2, 'TKT-2026-0044', 'Mant. Preventivo Compresor', 'Cambio filtros', 5, NULL, 2, 5, 3, 3, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-20 17:45:00', '2026-01-20 13:15:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(88, 2, 'TKT-2026-0045', 'Mant. Correctivo Motor', 'Reparar bobinado', 6, NULL, 3, 5, 3, 1, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-20 23:00:00', '2026-01-20 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(89, 2, 'TKT-2026-0046', 'Mant. Preventivo UPS', 'Prueba baterías', 7, NULL, 2, 5, 3, 2, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-21 18:00:00', '2026-01-21 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(90, 2, 'TKT-2026-0047', 'Mant. Predictivo Bomba', 'Análisis temperatura', 4, NULL, 2, 5, 3, 3, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 17:00:00', '2026-01-22 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(91, 2, 'TKT-2026-0048', 'Mant. Correctivo Variador', 'Reemplazar tarjeta', 5, NULL, 3, 5, 3, 1, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 22:30:00', '2026-01-22 19:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(92, 2, 'TKT-2026-0049', 'Mant. Preventivo Generador', 'Cambio aceite', 6, NULL, 2, 5, 3, 2, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-23 18:15:00', '2026-01-23 13:45:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(93, 2, 'TKT-2026-0050', 'Mant. Correctivo Sensor', 'Reemplazar sensor', 7, NULL, 3, 5, 3, 3, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 16:30:00', '2026-01-24 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(94, 2, 'TKT-2026-0051', 'Mant. Preventivo Panel', 'Medición parámetros', 4, NULL, 2, 5, 3, 1, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 22:00:00', '2026-01-24 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(95, 2, 'TKT-2026-0052', 'Mant. Predictivo Transform', 'Termografía', 5, NULL, 2, 5, 3, 2, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 19:00:00', '2026-01-25 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(96, 2, 'TKT-2026-0053', 'Mant. Correctivo Aire', 'Reparar clima', 6, NULL, 3, 5, 3, 3, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 17:45:00', '2026-01-26 14:15:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(97, 2, 'TKT-2026-0054', 'Mant. Preventivo Extintores', 'Recarga', 7, NULL, 2, 5, 3, 1, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 21:30:00', '2026-01-26 19:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(98, 2, 'TKT-2026-0055', 'Mant. Correctivo Montacargas', 'Reparar hidráulico', 4, NULL, 3, 5, 3, 2, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 18:00:00', '2026-01-27 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(99, 2, 'TKT-2026-0056', 'Mant. Preventivo Grúa', 'Inspección', 5, NULL, 2, 5, 3, 3, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 21:00:00', '2026-01-28 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(100, 2, 'TKT-2026-0057', 'Mant. Predictivo Rodamiento', 'Análisis', 6, NULL, 2, 5, 3, 1, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 17:15:00', '2026-01-29 13:15:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(101, 2, 'TKT-2026-0058', 'Mant. Correctivo Válvula', 'Reemplazar', 7, NULL, 3, 2, 6, 2, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 21:30:00', '2026-01-29 19:00:00', '2026-02-20 00:02:00', NULL, NULL, NULL),
	(102, 2, 'TKT-2026-0059', 'Mant. Preventivo Cinta', 'Ajuste', 4, NULL, 2, 5, 3, 3, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 17:30:00', '2026-01-30 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(103, 2, 'TKT-2026-0060', 'Mant. Predictivo Motor', 'Termografía', 5, NULL, 2, 5, 3, 1, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 22:00:00', '2026-01-30 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(104, 2, 'TKT-2026-0061', 'Mant. Prev. Excavadora', 'Servicio 500h', 6, NULL, 2, 5, 4, 1, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-17 22:00:00', '2026-01-17 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(105, 2, 'TKT-2026-0062', 'Mant. Corr. Bomba', 'Reparar sello', 7, NULL, 3, 5, 4, 2, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-18 18:30:00', '2026-01-18 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(106, 2, 'TKT-2026-0063', 'Mant. Prev. Camión', 'Cambio aceite', 4, NULL, 2, 5, 4, 3, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-19 17:00:00', '2026-01-19 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(107, 2, 'TKT-2026-0064', 'Mant. Pred. Rodillo', 'Medición', 5, NULL, 2, 5, 4, 1, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-20 19:00:00', '2026-01-20 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(108, 2, 'TKT-2026-0065', 'Mant. Corr. Retroexc', 'Reparar cilindro', 6, NULL, 3, 5, 4, 2, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-21 20:00:00', '2026-01-21 13:15:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(109, 2, 'TKT-2026-0066', 'Mant. Prev. Motoniveladora', 'Lubricación', 7, NULL, 2, 5, 4, 3, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 18:00:00', '2026-01-22 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(110, 2, 'TKT-2026-0067', 'Mant. Corr. Tractor', 'Reemplazar zapatas', 4, NULL, 3, 5, 4, 1, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-23 21:00:00', '2026-01-23 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(111, 2, 'TKT-2026-0068', 'Mant. Prev. Cargador', 'Inspección', 5, NULL, 2, 5, 4, 2, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 17:30:00', '2026-01-24 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(112, 2, 'TKT-2026-0069', 'Mant. Pred. Martillo', 'Análisis presión', 6, NULL, 2, 5, 4, 3, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 18:45:00', '2026-01-25 14:15:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(113, 2, 'TKT-2026-0070', 'Mant. Corr. Perforadora', 'Reparar', 7, NULL, 3, 5, 4, 1, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 19:00:00', '2026-01-26 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(114, 2, 'TKT-2026-0071', 'Mant. Prev. Pala', 'Cambio filtros', 4, NULL, 2, 5, 4, 2, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 18:00:00', '2026-01-27 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(115, 2, 'TKT-2026-0072', 'Mant. Corr. Cisterna', 'Reparar bomba', 5, NULL, 3, 5, 4, 3, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 18:30:00', '2026-01-28 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(116, 2, 'TKT-2026-0073', 'Mant. Prev. Minicargador', 'Servicio 250h', 6, NULL, 2, 5, 4, 1, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 19:15:00', '2026-01-29 13:45:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(117, 2, 'TKT-2026-0074', 'Mant. Pred. Vibro', 'Análisis', 7, NULL, 2, 5, 4, 2, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 22:30:00', '2026-01-29 20:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(118, 2, 'TKT-2026-0075', 'Mant. Corr. Mezcladora', 'Reemplazar', 4, NULL, 3, 5, 4, 3, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 17:00:00', '2026-01-30 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(119, 2, 'TKT-2026-0076', 'Mant. Prev. Chancadora', 'Inspección', 5, NULL, 2, 5, 4, 1, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 22:30:00', '2026-01-30 18:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(120, 2, 'TKT-2026-0077', 'Mant. Prev. Fajas', 'Ajuste', 6, NULL, 2, 5, 5, 1, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-17 19:00:00', '2026-01-17 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(121, 2, 'TKT-2026-0078', 'Mant. Corr. Zarandas', 'Reemplazar', 7, NULL, 3, 5, 5, 2, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-19 18:00:00', '2026-01-19 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(122, 2, 'TKT-2026-0079', 'Mant. Prev. Molino', 'Cambio', 4, NULL, 2, 5, 5, 3, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 22:00:00', '2026-01-21 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(123, 2, 'TKT-2026-0080', 'Mant. Pred. Separador', 'Medición', 5, NULL, 2, 5, 5, 1, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-23 18:00:00', '2026-01-23 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(124, 2, 'TKT-2026-0081', 'Mant. Corr. Bomba Lodos', 'Reparar', 6, NULL, 3, 5, 5, 2, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 20:00:00', '2026-01-24 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(125, 2, 'TKT-2026-0082', 'Mant. Prev. Filtro', 'Inspección', 7, NULL, 2, 5, 5, 3, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 17:30:00', '2026-01-25 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(126, 2, 'TKT-2026-0083', 'Mant. Corr. Espesador', 'Reparar', 4, NULL, 3, 5, 5, 1, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 19:00:00', '2026-01-26 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(127, 2, 'TKT-2026-0084', 'Mant. Prev. Ciclones', 'Reemplazo', 5, NULL, 2, 5, 5, 2, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 19:00:00', '2026-01-27 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(128, 2, 'TKT-2026-0085', 'Mant. Pred. Reductor', 'Análisis', 6, NULL, 2, 5, 5, 3, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 17:15:00', '2026-01-28 13:15:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(129, 2, 'TKT-2026-0086', 'Mant. Corr. Válvula', 'Reemplazar', 7, NULL, 3, 5, 5, 1, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 17:00:00', '2026-01-29 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(130, 2, 'TKT-2026-0087', 'Mant. Prev. Agitador', 'Balanceo', 4, NULL, 2, 5, 5, 2, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 18:00:00', '2026-01-30 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(131, 2, 'TKT-2026-0088', 'Mant. Pred. Motor AT', 'Termografía', 5, NULL, 2, 5, 5, 3, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 22:30:00', '2026-01-30 19:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(132, 2, 'TKT-2026-0089', 'Mant. Prev. Ventiladores', 'Limpieza', 7, NULL, 2, 5, 6, 1, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-18 17:00:00', '2026-01-18 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(133, 2, 'TKT-2026-0090', 'Mant. Corr. Sensor', 'Calibrar', 4, NULL, 3, 5, 6, 2, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-20 18:00:00', '2026-01-20 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(134, 2, 'TKT-2026-0091', 'Mant. Prev. Neumático', 'Revisión', 5, NULL, 2, 5, 6, 3, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 19:00:00', '2026-01-22 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(135, 2, 'TKT-2026-0092', 'Mant. Pred. Eje', 'Medición', 6, NULL, 2, 5, 6, 1, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 18:00:00', '2026-01-24 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(136, 2, 'TKT-2026-0093', 'Mant. Corr. Switch', 'Reemplazar', 7, NULL, 3, 5, 6, 2, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 19:00:00', '2026-01-25 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(137, 2, 'TKT-2026-0094', 'Mant. Prev. Iluminación', 'Cambio', 4, NULL, 2, 5, 6, 3, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 17:00:00', '2026-01-27 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(138, 2, 'TKT-2026-0095', 'Mant. Corr. Tablero', 'Reparar', 5, NULL, 3, 5, 6, 1, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 18:30:00', '2026-01-28 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(139, 2, 'TKT-2026-0096', 'Mant. Prev. Detección', 'Prueba', 6, NULL, 2, 5, 6, 2, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 19:00:00', '2026-01-29 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(140, 2, 'TKT-2026-0097', 'Mant. Pred. Acople', 'Análisis', 7, NULL, 2, 5, 6, 3, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 17:30:00', '2026-01-30 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(141, 2, 'TKT-2026-0098', 'Mant. Corr. Encoder', 'Reemplazar', 4, NULL, 3, 5, 6, 1, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 22:00:00', '2026-01-30 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(142, 2, 'TKT-2026-0099', 'Mant. Prev. Seguridad', 'Prueba', 5, NULL, 2, 5, 7, 1, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-19 17:00:00', '2026-01-19 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(143, 2, 'TKT-2026-0100', 'Mant. Corr. Barrera', 'Reparar', 6, NULL, 3, 5, 7, 2, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-21 18:00:00', '2026-01-21 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(144, 2, 'TKT-2026-0101', 'Mant. Prev. Pulsadores', 'Verificar', 7, NULL, 2, 5, 7, 3, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-23 19:00:00', '2026-01-23 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(145, 2, 'TKT-2026-0102', 'Mant. Pred. Frenos', 'Análisis', 4, NULL, 2, 5, 7, 1, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 17:00:00', '2026-01-25 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(146, 2, 'TKT-2026-0103', 'Mant. Corr. Interlock', 'Reparar', 5, NULL, 3, 5, 7, 2, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 19:00:00', '2026-01-26 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(147, 2, 'TKT-2026-0104', 'Mant. Prev. Sirenas', 'Prueba', 6, NULL, 2, 5, 7, 3, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 17:30:00', '2026-01-28 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(148, 2, 'TKT-2026-0105', 'Mant. Corr. Relay', 'Reemplazar', 7, NULL, 3, 5, 7, 1, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 18:00:00', '2026-01-29 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(149, 2, 'TKT-2026-0106', 'Mant. Prev. Señalización', 'Verificar', 4, NULL, 2, 5, 7, 2, 3, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 19:00:00', '2026-01-30 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(150, 3, 'TKT-2026-0107', 'Procesamiento Nómina', 'Calcular', 11, NULL, 1, 5, 11, 1, 8, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-17 21:00:00', '2026-01-17 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(151, 3, 'TKT-2026-0108', 'Declaración PDT', 'SUNAT', 12, NULL, 1, 5, 11, 2, 9, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-18 19:00:00', '2026-01-18 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(152, 3, 'TKT-2026-0109', 'Proceso Selección', 'Reclutamiento', 13, NULL, 2, 5, 11, 3, 8, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 22:00:00', '2026-01-19 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(153, 3, 'TKT-2026-0110', 'Evaluación', 'Procesar', 11, NULL, 2, 5, 11, 1, 9, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-21 21:00:00', '2026-01-20 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(154, 3, 'TKT-2026-0111', 'Actualización Legajos', 'Digitalizar', 12, NULL, 2, 5, 11, 2, 8, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 20:00:00', '2026-01-22 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(155, 3, 'TKT-2026-0112', 'Liquidación', 'Calcular CTS', 13, NULL, 1, 5, 11, 3, 9, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-23 19:00:00', '2026-01-23 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(156, 3, 'TKT-2026-0113', 'Inducción', 'Capacitación', 11, NULL, 2, 5, 11, 1, 8, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 18:30:00', '2026-01-24 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(157, 3, 'TKT-2026-0114', 'Renovación', 'Gestionar', 12, NULL, 2, 5, 11, 2, 9, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 20:00:00', '2026-01-25 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(158, 3, 'TKT-2026-0115', 'Proceso Vacaciones', 'Aprobar', 13, NULL, 2, 5, 11, 3, 8, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 17:30:00', '2026-01-26 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(159, 3, 'TKT-2026-0116', 'Gestión Seguros', 'Renovar', 11, NULL, 2, 5, 11, 1, 9, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 19:00:00', '2026-01-27 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(160, 3, 'TKT-2026-0117', 'Capacitación SST', 'Organizar', 12, NULL, 2, 5, 11, 2, 8, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 20:30:00', '2026-01-28 15:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(161, 3, 'TKT-2026-0118', 'Proceso Cese', 'Gestionar', 13, NULL, 2, 5, 11, 3, 9, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 18:00:00', '2026-01-29 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(162, 3, 'TKT-2026-0119', 'Actualización MOF', 'Revisar', 11, NULL, 2, 5, 11, 1, 8, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 21:00:00', '2026-01-30 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(163, 3, 'TKT-2026-0120', 'Compra Equipos', 'Adquisición', 12, NULL, 2, 5, 12, 1, 9, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-20 22:00:00', '2026-01-18 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(164, 3, 'TKT-2026-0121', 'Cotización', 'Proformas', 13, NULL, 2, 5, 12, 2, 8, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-19 19:00:00', '2026-01-19 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(165, 3, 'TKT-2026-0122', 'Orden Compra', 'Emitir', 11, NULL, 2, 5, 12, 3, 9, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-21 20:00:00', '2026-01-21 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(166, 3, 'TKT-2026-0123', 'Evaluación', 'Calificar', 12, NULL, 2, 5, 12, 1, 8, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-23 18:30:00', '2026-01-23 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(167, 3, 'TKT-2026-0124', 'Gestión Stock', 'Inventario', 13, NULL, 2, 5, 12, 2, 9, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 21:00:00', '2026-01-24 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(168, 3, 'TKT-2026-0125', 'Compra Repuestos', 'Emergencia', 11, NULL, 3, 5, 12, 3, 8, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 17:00:00', '2026-01-26 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(169, 3, 'TKT-2026-0126', 'Negociación', 'Renovar', 12, NULL, 2, 5, 12, 1, 9, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 22:00:00', '2026-01-27 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(170, 3, 'TKT-2026-0127', 'Control Calidad', 'Inspeccionar', 13, NULL, 2, 5, 12, 2, 8, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 18:00:00', '2026-01-29 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(171, 3, 'TKT-2026-0128', 'Actualización', 'Revisar', 11, NULL, 2, 5, 12, 3, 9, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 20:30:00', '2026-01-30 15:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(172, 3, 'TKT-2026-0129', 'Facturación', 'Emitir', 12, NULL, 1, 5, 13, 1, 8, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-17 20:00:00', '2026-01-17 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(173, 3, 'TKT-2026-0130', 'Libro Ventas', 'Actualizar', 13, NULL, 2, 5, 13, 2, 9, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-20 18:00:00', '2026-01-20 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(174, 3, 'TKT-2026-0131', 'Conciliación', 'Cuadrar', 11, NULL, 2, 5, 13, 3, 8, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 19:00:00', '2026-01-22 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(175, 3, 'TKT-2026-0132', 'Declaración IGV', 'PDT 621', 12, NULL, 1, 5, 13, 1, 9, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 17:00:00', '2026-01-24 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(176, 3, 'TKT-2026-0133', 'Análisis Cuentas', 'Reporte', 13, NULL, 2, 5, 13, 2, 8, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 20:00:00', '2026-01-26 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(177, 3, 'TKT-2026-0134', 'Provisión', 'Registrar', 11, NULL, 2, 5, 13, 3, 9, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 19:00:00', '2026-01-28 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(178, 3, 'TKT-2026-0135', 'Balance', 'Elaborar', 12, NULL, 1, 5, 13, 1, 8, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 21:00:00', '2026-01-30 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(179, 4, 'TKT-2026-0136', 'Desarrollo Dashboard', 'Panel', 8, NULL, 2, 5, 8, 1, 6, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-19 22:00:00', '2026-01-17 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(180, 4, 'TKT-2026-0137', 'Software Radar', 'Actualizar', 9, NULL, 1, 5, 8, 2, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-17 22:00:00', '2026-01-17 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(181, 4, 'TKT-2026-0138', 'Config Linux', 'Instalar', 10, NULL, 2, 5, 8, 3, 5, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-18 20:00:00', '2026-01-18 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(182, 4, 'TKT-2026-0139', 'Desarrollo IoT', 'Plataforma', 8, NULL, 2, 5, 8, 1, 6, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-21 22:00:00', '2026-01-19 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(183, 4, 'TKT-2026-0140', 'Soporte FleetCart', 'Resolver', 9, NULL, 3, 5, 8, 2, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-20 18:00:00', '2026-01-20 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(184, 4, 'TKT-2026-0141', 'Docker', 'Migrar', 10, NULL, 2, 5, 8, 3, 5, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 21:00:00', '2026-01-21 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(185, 4, 'TKT-2026-0142', 'Desarrollo ESP32', 'Programar', 8, NULL, 2, 5, 8, 1, 6, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 22:00:00', '2026-01-22 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(186, 4, 'TKT-2026-0143', 'Software Backup', 'Configurar', 9, NULL, 1, 5, 8, 2, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-23 19:00:00', '2026-01-23 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(187, 4, 'TKT-2026-0144', 'Soporte MySQL', 'Optimizar', 10, NULL, 2, 5, 8, 3, 5, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 20:00:00', '2026-01-24 14:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(188, 4, 'TKT-2026-0145', 'Desarrollo API', 'Crear', 8, NULL, 2, 5, 8, 1, 6, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 22:00:00', '2026-01-25 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(189, 4, 'TKT-2026-0146', 'Config VPN', 'Acceso', 9, NULL, 3, 5, 8, 2, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 18:00:00', '2026-01-26 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(190, 4, 'TKT-2026-0147', 'Optimización', 'Mejorar', 10, NULL, 1, 5, 8, 3, 5, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 21:00:00', '2026-01-27 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(191, 4, 'TKT-2026-0148', 'Desarrollo Asistencia', 'Biométrico', 8, NULL, 2, 5, 8, 1, 6, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 22:00:00', '2026-01-28 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(192, 4, 'TKT-2026-0149', 'Soporte AD', 'Resolver', 9, NULL, 3, 5, 8, 2, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 21:30:00', '2026-01-28 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(193, 4, 'TKT-2026-0150', 'CI/CD', 'Pipeline', 10, NULL, 2, 5, 8, 3, 5, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 22:00:00', '2026-01-29 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(194, 4, 'TKT-2026-0151', 'Desarrollo BI', 'Tablero', 8, NULL, 2, 5, 8, 1, 6, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 22:00:00', '2026-01-30 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(195, 4, 'TKT-2026-0152', 'Interfaz', 'Rediseñar', 9, NULL, 2, 5, 8, 2, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 20:00:00', '2026-01-30 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(196, 4, 'TKT-2026-0153', 'Firewall', 'Seguridad', 10, NULL, 3, 5, 8, 3, 5, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 19:00:00', '2026-01-30 16:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(197, 4, 'TKT-2026-0154', 'Reportes', 'Generador', 8, NULL, 2, 5, 8, 1, 6, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 23:00:00', '2026-01-30 20:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(198, 4, 'TKT-2026-0155', 'Inventario', 'Control', 9, NULL, 2, 5, 9, 1, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-19 22:00:00', '2026-01-17 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(199, 4, 'TKT-2026-0156', 'Soporte Red', 'VLAN', 10, NULL, 3, 5, 9, 2, 5, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-18 19:00:00', '2026-01-18 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(200, 4, 'TKT-2026-0157', 'App Móvil', 'Android', 8, NULL, 2, 5, 9, 3, 6, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 22:00:00', '2026-01-20 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(201, 4, 'TKT-2026-0158', 'ERP', 'Resolver', 9, NULL, 3, 5, 9, 1, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-21 19:00:00', '2026-01-21 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(202, 4, 'TKT-2026-0159', 'Backup', 'Resolver', 10, NULL, 3, 5, 9, 2, 5, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-23 18:00:00', '2026-01-23 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(203, 4, 'TKT-2026-0160', 'Nómina', 'Módulo', 8, NULL, 2, 5, 9, 3, 6, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 22:00:00', '2026-01-24 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(204, 4, 'TKT-2026-0161', 'WiFi', 'Ampliar', 9, NULL, 3, 5, 9, 1, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 19:00:00', '2026-01-25 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(205, 4, 'TKT-2026-0162', 'Portal Web', 'Panel', 10, NULL, 2, 5, 9, 2, 5, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 22:00:00', '2026-01-27 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(206, 4, 'TKT-2026-0163', 'Ubuntu', 'Actualizar', 8, NULL, 2, 5, 9, 3, 6, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 17:00:00', '2026-01-28 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(207, 4, 'TKT-2026-0164', 'SCADA', 'Monitoreo', 9, NULL, 2, 5, 9, 1, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 22:00:00', '2026-01-29 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(208, 4, 'TKT-2026-0165', 'Docker', 'Memoria', 10, NULL, 3, 5, 9, 2, 5, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 21:00:00', '2026-01-29 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(209, 4, 'TKT-2026-0166', 'Git', 'Gitea', 8, NULL, 2, 5, 9, 3, 6, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 17:00:00', '2026-01-30 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(210, 4, 'TKT-2026-0167', 'Facturación', 'Electrónica', 9, NULL, 2, 5, 9, 1, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 22:00:00', '2026-01-30 18:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(211, 4, 'TKT-2026-0168', 'DNS', 'Configurar', 10, NULL, 3, 5, 9, 2, 5, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 21:00:00', '2026-01-30 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(212, 4, 'TKT-2026-0169', 'GraphQL', 'API', 8, NULL, 2, 5, 9, 3, 6, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 23:30:00', '2026-01-30 20:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(213, 4, 'TKT-2026-0170', 'Helpdesk', 'Sistema', 9, NULL, 1, 5, 10, 1, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-20 22:00:00', '2026-01-17 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(214, 4, 'TKT-2026-0171', 'Nginx', 'Servidor', 10, NULL, 2, 5, 10, 2, 5, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-19 19:00:00', '2026-01-19 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(215, 4, 'TKT-2026-0172', 'WhatsApp', 'Integración', 8, NULL, 2, 5, 10, 3, 6, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-23 22:00:00', '2026-01-21 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(216, 4, 'TKT-2026-0173', 'Zabbix', 'Alertas', 9, NULL, 2, 5, 10, 1, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-22 20:00:00', '2026-01-22 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(217, 4, 'TKT-2026-0174', 'Backoffice', 'Panel', 10, NULL, 2, 5, 10, 2, 5, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-26 22:00:00', '2026-01-24 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(218, 4, 'TKT-2026-0175', 'SSL', 'Renovar', 8, NULL, 3, 5, 10, 3, 6, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 16:00:00', '2026-01-25 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(219, 4, 'TKT-2026-0176', 'Push', 'Notificaciones', 9, NULL, 2, 5, 10, 1, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 22:00:00', '2026-01-27 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(220, 4, 'TKT-2026-0177', 'Redis', 'Caché', 10, NULL, 2, 5, 10, 2, 5, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 19:00:00', '2026-01-28 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(221, 4, 'TKT-2026-0178', 'OAuth', 'Login', 8, NULL, 2, 5, 10, 3, 6, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-29 21:30:00', '2026-01-29 13:30:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(222, 4, 'TKT-2026-0179', 'K8s', 'Cluster', 9, NULL, 2, 5, 10, 1, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 22:00:00', '2026-01-30 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(223, 4, 'TKT-2026-0180', 'Excel', 'Reportes', 10, NULL, 2, 5, 10, 2, 5, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-30 23:00:00', '2026-01-30 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(224, 2, 'TKT-2026-0181', 'Mant. Radar', 'Preventivo', 4, NULL, 2, 5, 3, 1, 3, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 19:00:00', '2026-01-24 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(225, 2, 'TKT-2026-0182', 'Mant. CCTV', 'Limpieza', 5, NULL, 2, 5, 3, 2, 1, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 20:00:00', '2026-01-24 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(226, 2, 'TKT-2026-0183', 'Mant. Compresor', 'Cambio filtros', 6, NULL, 2, 5, 4, 3, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 21:00:00', '2026-01-24 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(227, 4, 'TKT-2026-0184', 'Desarrollo API', 'Crear endpoints', 8, NULL, 2, 5, 8, 1, 6, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 22:00:00', '2026-01-24 16:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(228, 4, 'TKT-2026-0185', 'Soporte MySQL', 'Optimizar', 9, NULL, 2, 5, 8, 2, 7, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 23:00:00', '2026-01-24 17:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(229, 3, 'TKT-2026-0186', 'Gestión Nómina', 'Calcular', 13, NULL, 1, 5, 11, 3, 9, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 22:00:00', '2026-01-24 18:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(230, 3, 'TKT-2026-0187', 'Compra Equipos', 'Adquisición', 11, NULL, 2, 5, 12, 1, 8, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-24 23:00:00', '2026-01-24 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(231, 3, 'TKT-2026-0188', 'Facturación', 'Emitir', 12, NULL, 1, 5, 13, 2, 9, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 00:00:00', '2026-01-24 20:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(232, 2, 'TKT-2026-0189', 'Mant. UPS', 'Prueba baterías', 7, NULL, 2, 5, 5, 3, 2, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 01:00:00', '2026-01-24 21:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(233, 4, 'TKT-2026-0190', 'Config VPN', 'Acceso remoto', 10, NULL, 3, 5, 9, 1, 6, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-25 02:00:00', '2026-01-24 22:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(234, 2, 'TKT-2026-0191', 'Mant. Motor', 'Reparar', 4, NULL, 3, 5, 3, 2, 1, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 19:00:00', '2026-01-27 13:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(235, 2, 'TKT-2026-0192', 'Mant. Válvula', 'Reemplazar', 5, NULL, 3, 5, 4, 3, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 20:00:00', '2026-01-27 14:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(236, 4, 'TKT-2026-0193', 'Desarrollo BI', 'Tablero', 8, NULL, 2, 5, 8, 1, 6, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 21:00:00', '2026-01-27 15:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(237, 4, 'TKT-2026-0194', 'Soporte Red', 'VLAN', 9, NULL, 3, 5, 9, 2, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 22:00:00', '2026-01-27 16:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(238, 3, 'TKT-2026-0195', 'Proceso Selección', 'Reclutamiento', 13, NULL, 2, 5, 11, 3, 8, 5, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-27 23:00:00', '2026-01-27 17:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(239, 3, 'TKT-2026-0196', 'Cotización', 'Proformas', 11, NULL, 2, 5, 12, 1, 9, 6, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 00:00:00', '2026-01-27 18:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(240, 3, 'TKT-2026-0197', 'Conciliación', 'Cuadrar cuentas', 12, NULL, 2, 5, 13, 2, 8, 1, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 01:00:00', '2026-01-27 19:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(241, 2, 'TKT-2026-0198', 'Mant. Generador', 'Cambio aceite', 6, NULL, 2, 5, 5, 3, 2, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 02:00:00', '2026-01-27 20:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(242, 4, 'TKT-2026-0199', 'Desarrollo Portal', 'Panel', 10, NULL, 2, 5, 10, 1, 6, 3, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 03:00:00', '2026-01-27 21:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(243, 4, 'TKT-2026-0200', 'Helpdesk', 'Sistema', 8, NULL, 1, 5, 10, 2, 7, 4, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-28 04:00:00', '2026-01-27 22:00:00', '2026-02-12 22:39:57', NULL, NULL, NULL),
	(244, 2, 'TKT-2026-0201', 'Mant. Cinta', 'Ajuste', 4, NULL, 2, 2, 5, 3, 2, 5, NULL, NULL, NULL, 60, 0, NULL, NULL, NULL, '2026-01-30 17:00:00', '2026-01-30 13:00:00', '2026-02-20 00:01:22', NULL, NULL, NULL),
	(245, 2, 'TKT-2026-0202', 'Mant. Sensor', 'Reemplazar', 5, NULL, 3, 2, 4, 1, 3, 6, NULL, NULL, NULL, 50, 0, NULL, NULL, NULL, '2026-01-30 18:00:00', '2026-01-30 14:00:00', '2026-01-31 22:23:44', NULL, NULL, NULL),
	(246, 4, 'TKT-2026-0203', 'Desarrollo Reportes', 'Generador', 9, NULL, 2, 2, 8, 2, 7, 1, NULL, NULL, NULL, 50, 0, NULL, NULL, NULL, '2026-01-30 19:00:00', '2026-01-30 15:00:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(247, 4, 'TKT-2026-0204', 'Soporte Docker', 'Memoria', 10, NULL, 3, 2, 9, 3, 5, 2, NULL, NULL, NULL, 50, 0, NULL, NULL, NULL, '2026-01-30 20:00:00', '2026-01-30 16:00:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(248, 3, 'TKT-2026-0205', 'Actualización MOF', 'Revisar', 13, NULL, 2, 2, 11, 1, 9, 3, NULL, NULL, NULL, 30, 0, NULL, NULL, NULL, '2026-01-30 21:00:00', '2026-01-30 17:00:00', '2026-02-12 22:10:16', NULL, NULL, NULL),
	(249, 3, 'TKT-2026-0206', 'Actualización', 'Revisar', 11, NULL, 2, 2, 12, 2, 8, 4, NULL, NULL, NULL, 30, 0, NULL, NULL, NULL, '2026-01-30 22:00:00', '2026-01-30 18:00:00', '2026-02-12 22:10:16', NULL, NULL, NULL),
	(250, 3, 'TKT-2026-0207', 'Balance', 'Elaborar', 12, NULL, 1, 4, 13, 3, 9, 5, NULL, NULL, NULL, 100, 0, NULL, NULL, NULL, '2026-01-30 23:00:00', '2026-01-30 19:00:00', '2026-02-01 21:50:53', NULL, NULL, NULL),
	(251, 2, 'TKT-2026-0208', 'Mant. Señalización', 'Verificar', 6, NULL, 2, 4, 7, 1, 3, 6, NULL, NULL, NULL, 100, 0, NULL, NULL, NULL, '2026-01-31 00:00:00', '2026-01-30 20:00:00', '2026-01-31 22:23:44', NULL, NULL, NULL),
	(252, 4, 'TKT-2026-0209', 'Desarrollo Excel', 'Reportes', 8, NULL, 2, 4, 10, 2, 7, 1, NULL, NULL, NULL, 100, 0, NULL, NULL, NULL, '2026-01-31 01:00:00', '2026-01-30 21:00:00', '2026-02-02 04:07:57', NULL, NULL, NULL),
	(253, 4, 'TKT-2026-0210', 'Config K8s', 'Cluster', 9, NULL, 2, 2, 9, 3, 5, 2, NULL, NULL, NULL, 90, 0, NULL, NULL, NULL, '2026-01-31 02:00:00', '2026-01-30 22:00:00', '2026-02-15 01:34:48', '', '', ''),
	(254, 4, 'TKT-2026-0211', 'Prueba Software Radar MSR - Jack 1', 'Ticket de prueba', 8, NULL, 2, 4, 8, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-25 17:00:00', '2026-01-25 15:00:00', '2026-02-01 03:23:12', NULL, NULL, NULL),
	(255, 4, 'TKT-2026-0212', 'Prueba Software Radar MSR - Jack 2', 'Ticket de prueba', 8, NULL, 2, 5, 8, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-26 19:00:00', '2026-01-26 15:00:00', '2026-02-01 03:23:12', NULL, NULL, NULL),
	(256, 4, 'TKT-2026-0213', 'Prueba Software Radar MSR - Jack 3', 'Ticket de prueba', 8, NULL, 2, 4, 8, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-27 16:00:00', '2026-01-27 14:00:00', '2026-02-01 03:23:12', NULL, NULL, NULL),
	(257, 4, 'TKT-2026-0214', 'Prueba Software Radar MSR - Richard 1', 'Ticket de prueba', 9, NULL, 2, 4, 9, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-25 21:00:00', '2026-01-25 19:00:00', '2026-02-01 03:23:12', NULL, NULL, NULL),
	(258, 4, 'TKT-2026-0215', 'Prueba Software Radar MSR - Richard 2', 'Ticket de prueba', 9, NULL, 2, 5, 9, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-28 15:00:00', '2026-01-28 13:00:00', '2026-02-01 03:23:12', NULL, NULL, NULL),
	(259, 4, 'TKT-2026-0216', 'Prueba Software Radar MSR - Carlos 1', 'Ticket de prueba', 10, NULL, 2, 4, 10, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-29 18:00:00', '2026-01-29 16:00:00', '2026-02-01 03:23:12', NULL, NULL, NULL),
	(260, 2, 'TKT-2026-0217', 'Software Radar MSR - Amador 1', 'Ticket de prueba', 3, NULL, 2, 4, 3, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-25 15:00:00', '2026-01-25 13:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(261, 2, 'TKT-2026-0218', 'Software Radar MSR - Amador 2', 'Ticket de prueba', 3, NULL, 2, 5, 3, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-26 16:00:00', '2026-01-26 14:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(262, 2, 'TKT-2026-0219', 'Software Radar MSR - Amador 3', 'Ticket de prueba', 3, NULL, 2, 4, 3, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-27 17:00:00', '2026-01-27 15:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(263, 2, 'TKT-2026-0220', 'Software Radar MSR - Amador 4', 'Ticket de prueba', 3, NULL, 2, 5, 3, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-28 18:00:00', '2026-01-28 16:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(264, 2, 'TKT-2026-0221', 'Software Radar MSR - Ivan 1', 'Ticket de prueba', 4, NULL, 2, 4, 4, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-25 21:00:00', '2026-01-25 19:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(265, 2, 'TKT-2026-0222', 'Software Radar MSR - Ivan 2', 'Ticket de prueba', 4, NULL, 2, 5, 4, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-26 22:00:00', '2026-01-26 20:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(266, 2, 'TKT-2026-0223', 'Software Radar MSR - Ivan 3', 'Ticket de prueba', 4, NULL, 2, 4, 4, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-27 23:00:00', '2026-01-27 21:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(267, 2, 'TKT-2026-0224', 'Software Radar MSR - Fernando 1', 'Ticket de prueba', 5, NULL, 2, 4, 5, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-28 15:00:00', '2026-01-28 13:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(268, 2, 'TKT-2026-0225', 'Software Radar MSR - Fernando 2', 'Ticket de prueba', 5, NULL, 2, 5, 5, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-29 16:00:00', '2026-01-29 14:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(269, 2, 'TKT-2026-0226', 'Software Radar MSR - Daniel 1', 'Ticket de prueba', 6, NULL, 2, 4, 6, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-25 17:00:00', '2026-01-25 15:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(270, 2, 'TKT-2026-0227', 'Software Radar MSR - Daniel 2', 'Ticket de prueba', 6, NULL, 2, 5, 6, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-26 18:00:00', '2026-01-26 16:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(271, 2, 'TKT-2026-0228', 'Software Radar MSR - Luis 1', 'Ticket de prueba', 7, NULL, 2, 4, 7, NULL, 4, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, '2026-01-27 19:00:00', '2026-01-27 17:00:00', '2026-02-01 03:31:48', NULL, NULL, NULL),
	(272, 2, 'TKN-ST-102', 'Prueba nuevo formato código', 'Ticket de prueba para validar el nuevo formato TKN-XX-##', 1, NULL, 2, 1, 4, NULL, 1, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, '2026-02-01 13:09:16', '2026-02-18 21:32:54', NULL, NULL, NULL),
	(281, 2, 'TKN-ST-103', 'Problemas con Radar MSR237', 'Problemas con Radar MSR237', 1, NULL, 2, 4, 1, 3, 7, 3, 3, 8, 2, 100, 0, 1, '2026-02-14 13:16:40', NULL, '2026-02-14 18:16:32', '2026-02-10 16:23:35', '2026-02-14 18:16:40', 'Alex L.', 'jtunoquesa@unprg.edu.pe', '9212546263'),
	(282, 2, 'TKN-ST-104', 'Problemas con Radar MSR237', 'clonsa_ssoma@clonsa.com', 1, NULL, 3, 4, 3, 2, 4, 1, 5, 4, 2, 100, 0, 1, '2026-02-14 13:03:07', NULL, '2026-02-14 17:28:20', '2026-02-10 16:35:02', '2026-02-14 18:03:07', 'Alex L', 'jtunoquesa@unprg.edu.pe', '9212546263'),
	(283, 2, 'TKN-ST-105', 'Problemas con Radar MSR237', 'Problemas con Radar MSR237', 1, NULL, 2, 4, 3, 2, 12, 3, 9, 1, 4, 100, 0, 1, '2026-02-12 18:22:52', NULL, '2026-02-12 23:22:37', '2026-02-11 00:29:24', '2026-02-12 23:22:52', 'Alex L.', 'jtunoquesa@unprg.edu.pe', '9212546263'),
	(285, 2, 'TKN-ST-107', 'Problemas con Radar MSR237', 'Problemas con Radar MSR237', 1, NULL, 3, 4, 1, 3, 11, 3, 9, 4, 2, 100, 0, 1, '2026-02-14 20:26:30', NULL, '2026-02-15 01:26:21', '2026-02-11 00:50:34', '2026-02-15 01:26:30', 'Alex L.', 'jtunoquesa@unprg.edu.pe', '9212546263'),
	(286, 4, 'TKN-IT-73', 'Problemas con Radar MSR237', 'Problemas con Radar MSR237', 1, NULL, 2, 4, 8, 2, 4, 7, 13, 17, 11, 100, 0, 1, '2026-02-14 13:16:23', NULL, '2026-02-12 23:15:50', '2026-02-11 00:56:58', '2026-02-14 18:16:23', 'Alex L.', 'jtunoquesa@unprg.edu.pe', '9212546263'),
	(287, 4, 'TKN-IT-74', 'Problemas con Radar MSR237', 'Problemas con Radar MSR237', 1, NULL, 2, 4, 1, 3, 4, 8, 15, 17, 11, 100, 0, 1, '2026-02-14 20:24:48', NULL, '2026-02-14 18:23:07', '2026-02-11 01:14:04', '2026-02-15 01:24:48', 'Alex L.', 'jtunoquesa@unprg.edu.pe', '9212546263'),
	(288, 4, 'TKN-IT-75', 'Problemas con Radar MSR237', 'Problemas con Radar MSR237', 1, NULL, 2, 4, 8, 5, 7, 8, 15, 18, 10, 100, 0, 1, '2026-02-14 20:22:46', NULL, '2026-02-14 18:22:55', '2026-02-11 01:15:39', '2026-02-15 01:22:46', 'Alex L.', 'jtunoquesa@unprg.edu.pe', '9212546263'),
	(289, 2, 'TKN-ST-108', 'Problemas con Radar MSR237', 'Problemas con Radar MSR237', 1, NULL, 2, 4, 7, 2, 11, 3, 8, 5, 1, 100, 0, 1, '2026-02-12 18:07:48', NULL, '2026-02-12 23:03:18', '2026-02-12 22:12:54', '2026-02-12 23:07:48', 'Alex L.', 'jtunoquesa@unprg.edu.pe', '9212546263'),
	(290, 2, 'TKN-ST-109', 'Ticket de prueba Codex 20260214_210254', 'Ticket creado de prueba para validacion visual en modulo.', 1, NULL, 2, 4, 1, 1, 1, NULL, NULL, NULL, NULL, 100, 0, 1, '2026-02-18 16:30:41', NULL, '2026-02-18 21:30:33', '2026-02-15 02:02:54', '2026-02-18 21:30:41', NULL, NULL, NULL),
	(291, 2, 'TKN-ST-110', 'Problemas con Radar MSR237', 'Problemas con Radar MSR237', 3, NULL, 3, 4, 5, 4, 11, 3, 4, 5, 5, 100, 0, 1, '2026-02-20 14:19:59', NULL, '2026-02-20 19:19:51', '2026-02-19 23:48:48', '2026-02-20 19:19:59', 'Alex L.', 'jtunoquesa@unprg.edu.pe', '9212546263'),
	(292, 2, 'TKN-ST-111', 'Problemas con Radar MSR237', 'Problemas con Radar MSR237', 3, NULL, 3, 2, 4, 1, 4, 5, 8, 2, 1, 50, 0, NULL, NULL, NULL, NULL, '2026-02-20 16:29:01', '2026-02-20 20:13:10', '', '', '');

-- Volcando estructura para tabla helpdesk_clonsa.ticket_archivos
CREATE TABLE IF NOT EXISTS `ticket_archivos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamano` int NOT NULL DEFAULT '0',
  `tipo_mime` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `ticket_archivos_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_archivos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.ticket_archivos: ~9 rows (aproximadamente)
INSERT INTO `ticket_archivos` (`id`, `ticket_id`, `nombre_original`, `nombre_archivo`, `ruta`, `tamano`, `tipo_mime`, `usuario_id`, `created_at`) VALUES
	(10, 281, 'Requerimientos Técnicos - MSR Connect 2025 Rev06.pdf', '1770740615_0_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 'uploads/tickets/TKN-ST-103/1770740615_0_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 1341830, 'application/pdf', 1, '2026-02-10 16:23:35'),
	(11, 281, 'Requerimientos Técnicos - MSR Connect 2025 Rev06.pdf', '1770740615_1_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 'uploads/tickets/TKN-ST-103/1770740615_1_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 1341830, 'application/pdf', 1, '2026-02-10 16:23:35'),
	(12, 282, 'Requerimientos Técnicos - MSR Connect 2025 Rev06.pdf', '1770741302_0_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 'uploads/tickets/TKN-ST-104/1770741302_0_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 1341830, 'application/pdf', 1, '2026-02-10 16:35:02'),
	(13, 282, 'Requerimientos Técnicos - MSR Connect 2025 Rev06.pdf', '1770741302_1_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 'uploads/tickets/TKN-ST-104/1770741302_1_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 1341830, 'application/pdf', 1, '2026-02-10 16:35:02'),
	(15, 283, 'Requerimientos Técnicos - MSR Connect 2025 Rev06.pdf', '1770769764_1_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 'uploads/tickets/TKN-ST-105/1770769764_1_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 1341830, 'application/pdf', 1, '2026-02-11 00:29:24'),
	(18, 285, 'Requerimientos Técnicos - MSR Connect 2025 Rev06.pdf', '1770771034_0_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 'uploads/tickets/TKN-ST-107/1770771034_0_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 1341830, 'application/pdf', 1, '2026-02-11 00:50:34'),
	(19, 285, 'Requerimientos Técnicos - MSR Connect 2025 Rev06.pdf', '1770771034_1_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 'uploads/tickets/TKN-ST-107/1770771034_1_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 1341830, 'application/pdf', 1, '2026-02-11 00:50:34'),
	(20, 288, 'Requerimientos Técnicos - MSR Connect 2025 Rev06.pdf', '1770772539_0_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 'uploads/tickets/TKN-IT-75/1770772539_0_Requerimientos_T__cnicos_-_MSR_Connect_2025_Rev06.pdf', 1341830, 'application/pdf', 1, '2026-02-11 01:15:39'),
	(21, 289, 'me.txt', '1770934374_0_me.txt', 'uploads/tickets/TKN-ST-108/1770934374_0_me.txt', 0, 'text/plain', 1, '2026-02-12 22:12:54');

-- Volcando estructura para tabla helpdesk_clonsa.ticket_comentarios
CREATE TABLE IF NOT EXISTS `ticket_comentarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('comentario','solucion','nota_interna') COLLATE utf8mb4_unicode_ci DEFAULT 'comentario',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_usuario_id` (`usuario_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `ticket_comentarios_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.ticket_comentarios: ~53 rows (aproximadamente)
INSERT INTO `ticket_comentarios` (`id`, `ticket_id`, `usuario_id`, `mensaje`, `tipo`, `created_at`, `updated_at`) VALUES
	(24, 289, 1, '✅ **TICKET APROBADO**\nAprobado. Sin Observaciones', 'nota_interna', '2026-02-12 23:07:48', '2026-02-12 23:07:48'),
	(26, 283, 1, 'h', 'comentario', '2026-02-12 23:21:32', '2026-02-12 23:21:32'),
	(28, 283, 1, 'hhjj', 'comentario', '2026-02-12 23:22:01', '2026-02-12 23:22:01'),
	(29, 283, 1, 'iyt5t', 'comentario', '2026-02-12 23:22:04', '2026-02-12 23:22:04'),
	(30, 283, 1, 'tyu', 'comentario', '2026-02-12 23:22:05', '2026-02-12 23:22:05'),
	(31, 283, 1, 'uuu', 'comentario', '2026-02-12 23:22:06', '2026-02-12 23:22:06'),
	(32, 283, 1, '✅ **TICKET APROBADO**\nEl cierre del ticket ha sido aprobado.', 'nota_interna', '2026-02-12 23:22:52', '2026-02-12 23:22:52'),
	(33, 282, 1, 'TEST1', 'comentario', '2026-02-14 17:19:38', '2026-02-14 17:19:38'),
	(35, 282, 1, 'TEST2', 'comentario', '2026-02-14 17:19:45', '2026-02-14 17:19:45'),
	(36, 282, 1, 'TEST3', 'comentario', '2026-02-14 17:19:49', '2026-02-14 17:19:49'),
	(37, 282, 1, '❌ **TICKET RECHAZADO**\n**Motivo:** Observacion de solución', 'nota_interna', '2026-02-14 17:21:38', '2026-02-14 17:21:38'),
	(38, 288, 1, '❌ **TICKET RECHAZADO**\n**Motivo:** Rechazado', 'nota_interna', '2026-02-14 17:40:30', '2026-02-14 17:40:30'),
	(39, 282, 1, '✅ **TICKET APROBADO**\nTE', 'nota_interna', '2026-02-14 18:03:07', '2026-02-14 18:03:07'),
	(41, 287, 1, '❌ **TICKET RECHAZADO**\n**Motivo:** 1234', 'nota_interna', '2026-02-14 18:08:12', '2026-02-14 18:08:12'),
	(42, 286, 1, '✅ **TICKET APROBADO**\nEl cierre del ticket ha sido aprobado.', 'nota_interna', '2026-02-14 18:16:23', '2026-02-14 18:16:23'),
	(43, 281, 1, '✅ **TICKET APROBADO**\nEl cierre del ticket ha sido aprobado.', 'nota_interna', '2026-02-14 18:16:40', '2026-02-14 18:16:40'),
	(44, 285, 1, '❌ **TICKET RECHAZADO**\n**Motivo:** 1', 'nota_interna', '2026-02-14 18:16:57', '2026-02-14 18:16:57'),
	(45, 272, 1, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nMotivo: Vacaciones', 'nota_interna', '2026-02-14 18:33:40', '2026-02-14 18:33:40'),
	(46, 253, 1, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.', 'nota_interna', '2026-02-14 18:37:21', '2026-02-14 18:37:21'),
	(47, 272, 1, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.', 'nota_interna', '2026-02-14 18:42:19', '2026-02-14 18:42:19'),
	(49, 288, 1, '✅ **TICKET APROBADO**\nEl cierre del ticket ha sido aprobado.', 'nota_interna', '2026-02-15 01:22:46', '2026-02-15 01:22:46'),
	(50, 287, 1, '✅ **TICKET APROBADO**\nEl cierre del ticket ha sido aprobado.', 'nota_interna', '2026-02-15 01:24:48', '2026-02-15 01:24:48'),
	(51, 285, 1, '✅ **TICKET APROBADO**\nEl cierre del ticket ha sido aprobado.', 'nota_interna', '2026-02-15 01:26:30', '2026-02-15 01:26:30'),
	(52, 253, 1, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Jack Tuñoque S.\nA: Carlos Medina', 'nota_interna', '2026-02-15 01:32:36', '2026-02-15 01:32:36'),
	(53, 253, 1, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Carlos Medina\nA: Richard Arias', 'nota_interna', '2026-02-15 01:34:48', '2026-02-15 01:34:48'),
	(54, 290, 1, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Amador Contreras L.\nA: Administrador General\nMotivo: Transferencia de prueba para validar modulo Asignados a Mi', 'nota_interna', '2026-02-15 02:04:09', '2026-02-15 02:04:09'),
	(55, 290, 1, 'AVANCE 1# Se reviso el radar msr237', 'comentario', '2026-02-18 21:28:52', '2026-02-18 21:28:52'),
	(56, 290, 1, '❌ **TICKET RECHAZADO**\n**Motivo:** porque', 'nota_interna', '2026-02-18 21:30:07', '2026-02-18 21:30:07'),
	(57, 290, 1, 'nuevamente solucionado', 'comentario', '2026-02-18 21:30:30', '2026-02-18 21:30:30'),
	(58, 290, 1, '✅ **TICKET APROBADO**\nEl cierre del ticket ha sido aprobado.', 'nota_interna', '2026-02-18 21:30:41', '2026-02-18 21:30:41'),
	(59, 272, 1, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Fernando Quispe\nA: Iván Rodriguez\nMotivo: prueba', 'nota_interna', '2026-02-18 21:32:54', '2026-02-18 21:32:54'),
	(60, 244, 3, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Amador Contreras L.\nA: Fernando Quispe', 'nota_interna', '2026-02-20 00:01:22', '2026-02-20 00:01:22'),
	(61, 101, 3, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Amador Contreras L.\nA: Daniel Lazarte', 'nota_interna', '2026-02-20 00:02:00', '2026-02-20 00:02:00'),
	(62, 291, 3, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Fernando Quispe\nA: Iván Rodriguez', 'nota_interna', '2026-02-20 00:07:20', '2026-02-20 00:07:20'),
	(63, 291, 4, 'SOLICITUD DE TRANSFERENCIA\nUsuario solicita transferir ticket a otro responsable.', 'nota_interna', '2026-02-20 00:08:49', '2026-02-20 00:08:49'),
	(64, 291, 3, 'SOLICITUD DE TRANSFERENCIA RECHAZADA\nSolicitud rechazada por Jefe/Administrador.', 'nota_interna', '2026-02-20 13:47:58', '2026-02-20 13:47:58'),
	(65, 291, 3, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Iván Rodriguez\nA: Fernando Quispe', 'nota_interna', '2026-02-20 13:51:08', '2026-02-20 13:51:08'),
	(66, 291, 3, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Fernando Quispe\nA: Daniel Lazarte', 'nota_interna', '2026-02-20 14:38:16', '2026-02-20 14:38:16'),
	(67, 291, 3, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Daniel Lazarte\nA: Fernando Quispe', 'nota_interna', '2026-02-20 14:38:23', '2026-02-20 14:38:23'),
	(68, 292, 4, 'SOLICITUD DE TRANSFERENCIA\nEstimado Amador Contreras L., se solicita transferir el ticket TKN-ST-111 al responsable "Carlos Medina".', 'nota_interna', '2026-02-20 16:41:43', '2026-02-20 16:41:43'),
	(69, 292, 8, 'TICKET TRANSFERIDO\nTransferencia aprobada por Jefe/Administrador.\nDe: Iván Rodriguez\nA: Carlos Medina', 'nota_interna', '2026-02-20 16:43:14', '2026-02-20 16:43:14'),
	(70, 292, 1, 'Test1', 'comentario', '2026-02-20 19:18:30', '2026-02-20 19:18:30'),
	(71, 291, 1, '❌ **TICKET RECHAZADO**\n**Motivo:** Test', 'nota_interna', '2026-02-20 19:19:36', '2026-02-20 19:19:36'),
	(72, 291, 1, '✅ **TICKET APROBADO**\nEl cierre del ticket ha sido aprobado.', 'nota_interna', '2026-02-20 19:19:59', '2026-02-20 19:19:59'),
	(73, 292, 1, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Carlos Medina\nA: Fernando Quispe', 'nota_interna', '2026-02-20 19:20:44', '2026-02-20 19:20:44'),
	(74, 292, 1, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Fernando Quispe\nA: Iván Rodriguez', 'nota_interna', '2026-02-20 19:30:52', '2026-02-20 19:30:52'),
	(75, 292, 3, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Iván Rodriguez\nA: Luis Ruiz', 'nota_interna', '2026-02-20 19:56:32', '2026-02-20 19:56:32'),
	(76, 292, 3, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Luis Ruiz\nA: Fernando Quispe', 'nota_interna', '2026-02-20 19:56:41', '2026-02-20 19:56:41'),
	(77, 292, 5, 'SOLICITUD DE TRANSFERENCIA\nEstimado Amador Contreras L., se solicita transferir el ticket TKN-ST-111 al responsable "Daniel Lazarte".', 'nota_interna', '2026-02-20 19:57:16', '2026-02-20 19:57:16'),
	(78, 292, 3, 'TICKET TRANSFERIDO\nTransferencia aprobada por Jefe/Administrador.\nDe: Fernando Quispe\nA: Daniel Lazarte', 'nota_interna', '2026-02-20 19:57:26', '2026-02-20 19:57:26'),
	(79, 292, 3, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Daniel Lazarte\nA: Luis Ruiz', 'nota_interna', '2026-02-20 20:09:51', '2026-02-20 20:09:51'),
	(80, 292, 3, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Luis Ruiz\nA: Iván Rodriguez', 'nota_interna', '2026-02-20 20:10:11', '2026-02-20 20:10:11'),
	(81, 292, 4, 'SOLICITUD DE TRANSFERENCIA\nEstimado Amador Contreras L., se solicita transferir el ticket TKN-ST-111 al responsable "Amador Contreras L.".', 'nota_interna', '2026-02-20 20:10:32', '2026-02-20 20:10:32'),
	(82, 292, 3, 'TICKET TRANSFERIDO\nTransferencia aprobada por Jefe/Administrador.\nDe: Iván Rodriguez\nA: Amador Contreras L.', 'nota_interna', '2026-02-20 20:10:41', '2026-02-20 20:10:41'),
	(83, 292, 3, 'TICKET TRANSFERIDO\nTransferencia directa ejecutada por Jefe/Administrador.\nDe: Amador Contreras L.\nA: Iván Rodriguez', 'nota_interna', '2026-02-20 20:13:10', '2026-02-20 20:13:10');

-- Volcando estructura para tabla helpdesk_clonsa.ticket_contadores
CREATE TABLE IF NOT EXISTS `ticket_contadores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `departamento_id` int NOT NULL,
  `ultimo_numero` int DEFAULT '0',
  `prefijo` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'TKN',
  PRIMARY KEY (`id`),
  UNIQUE KEY `departamento_id` (`departamento_id`),
  CONSTRAINT `ticket_contadores_ibfk_1` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.ticket_contadores: ~4 rows (aproximadamente)
INSERT INTO `ticket_contadores` (`id`, `departamento_id`, `ultimo_numero`, `prefijo`) VALUES
	(1, 1, 18, 'TKN'),
	(2, 2, 111, 'TKN'),
	(3, 4, 75, 'TKN'),
	(4, 3, 39, 'TKN');

-- Volcando estructura para tabla helpdesk_clonsa.ticket_transferencias
CREATE TABLE IF NOT EXISTS `ticket_transferencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `usuario_origen` int DEFAULT NULL,
  `usuario_destino` int NOT NULL,
  `solicitado_por` int NOT NULL,
  `motivo` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('pendiente','aprobada','rechazada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `aprobado_por` int DEFAULT NULL,
  `comentario_aprobacion` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_estado` (`ticket_id`,`estado`),
  KEY `idx_solicitado_por` (`solicitado_por`),
  KEY `idx_usuario_destino` (`usuario_destino`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.ticket_transferencias: ~24 rows (aproximadamente)
INSERT INTO `ticket_transferencias` (`id`, `ticket_id`, `usuario_origen`, `usuario_destino`, `solicitado_por`, `motivo`, `estado`, `aprobado_por`, `comentario_aprobacion`, `created_at`, `updated_at`) VALUES
	(1, 272, 1, 6, 1, 'Vacaciones', 'aprobada', 1, 'Transferencia directa de Jefe/Administrador', '2026-02-14 18:33:40', '2026-02-14 18:33:40'),
	(2, 253, 10, 8, 1, NULL, 'aprobada', 1, 'Transferencia directa de Jefe/Administrador', '2026-02-14 18:37:21', '2026-02-14 18:37:21'),
	(3, 272, 6, 5, 1, NULL, 'aprobada', 1, 'Transferencia directa de Jefe/Administrador', '2026-02-14 18:42:19', '2026-02-14 18:42:19'),
	(4, 253, 8, 10, 1, NULL, 'aprobada', 1, 'Transferencia directa de Jefe/Administrador', '2026-02-15 01:32:36', '2026-02-15 01:32:36'),
	(5, 253, 10, 9, 1, NULL, 'aprobada', 1, 'Transferencia directa de Jefe/Administrador', '2026-02-15 01:34:48', '2026-02-15 01:34:48'),
	(6, 290, 3, 1, 1, 'Transferencia de prueba para validar modulo Asignados a Mi', 'aprobada', 1, 'Transferencia directa de prueba', '2026-02-15 02:04:09', '2026-02-15 02:04:09'),
	(7, 272, 5, 4, 1, 'prueba', 'aprobada', 1, 'Transferencia directa de Jefe/Administrador', '2026-02-18 21:32:54', '2026-02-18 21:32:54'),
	(8, 244, 3, 5, 3, NULL, 'aprobada', 3, 'Transferencia directa de Jefe/Administrador', '2026-02-20 00:01:22', '2026-02-20 00:01:22'),
	(9, 101, 3, 6, 3, NULL, 'aprobada', 3, 'Transferencia directa de Jefe/Administrador', '2026-02-20 00:02:00', '2026-02-20 00:02:00'),
	(10, 291, 5, 4, 3, NULL, 'aprobada', 3, 'Transferencia directa de Jefe/Administrador', '2026-02-20 00:07:20', '2026-02-20 00:07:20'),
	(11, 291, 4, 7, 4, NULL, 'rechazada', 3, 'Solicitud rechazada', '2026-02-20 00:08:49', '2026-02-20 13:47:58'),
	(12, 291, 4, 5, 3, NULL, 'aprobada', 3, 'Transferencia directa de Jefe/Administrador', '2026-02-20 13:51:08', '2026-02-20 13:51:08'),
	(13, 291, 5, 6, 3, NULL, 'aprobada', 3, 'Transferencia directa de Jefe/Administrador', '2026-02-20 14:38:16', '2026-02-20 14:38:16'),
	(14, 291, 6, 5, 3, NULL, 'aprobada', 3, 'Transferencia directa de Jefe/Administrador', '2026-02-20 14:38:23', '2026-02-20 14:38:23'),
	(15, 292, 4, 10, 4, NULL, 'aprobada', 8, 'Solicitud aprobada', '2026-02-20 16:41:43', '2026-02-20 16:43:14'),
	(16, 292, 10, 5, 1, NULL, 'aprobada', 1, 'Transferencia directa de Jefe/Administrador', '2026-02-20 19:20:44', '2026-02-20 19:20:44'),
	(17, 292, 5, 4, 1, NULL, 'aprobada', 1, 'Transferencia directa de Jefe/Administrador', '2026-02-20 19:30:52', '2026-02-20 19:30:52'),
	(18, 292, 4, 7, 3, NULL, 'aprobada', 3, 'Transferencia directa de Jefe/Administrador', '2026-02-20 19:56:32', '2026-02-20 19:56:32'),
	(19, 292, 7, 5, 3, NULL, 'aprobada', 3, 'Transferencia directa de Jefe/Administrador', '2026-02-20 19:56:41', '2026-02-20 19:56:41'),
	(20, 292, 5, 6, 5, NULL, 'aprobada', 3, 'Solicitud aprobada', '2026-02-20 19:57:16', '2026-02-20 19:57:26'),
	(21, 292, 6, 7, 3, NULL, 'aprobada', 3, 'Transferencia directa de Jefe/Administrador', '2026-02-20 20:09:51', '2026-02-20 20:09:51'),
	(22, 292, 7, 4, 3, NULL, 'aprobada', 3, 'Transferencia directa de Jefe/Administrador', '2026-02-20 20:10:11', '2026-02-20 20:10:11'),
	(23, 292, 4, 3, 4, NULL, 'aprobada', 3, 'Solicitud aprobada', '2026-02-20 20:10:32', '2026-02-20 20:10:41'),
	(24, 292, 3, 4, 3, NULL, 'aprobada', 3, 'Transferencia directa de Jefe/Administrador', '2026-02-20 20:13:10', '2026-02-20 20:13:10');

-- Volcando estructura para tabla helpdesk_clonsa.tipos_falla
CREATE TABLE IF NOT EXISTS `tipos_falla` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `icono` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `departamento_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.tipos_falla: ~12 rows (aproximadamente)
INSERT INTO `tipos_falla` (`id`, `nombre`, `descripcion`, `icono`, `activo`, `created_at`, `departamento_id`) VALUES
	(1, 'Hardware', 'Fallas físicas', 'mdi-memory', 1, '2026-01-23 16:13:59', 2),
	(2, 'Software', 'Fallas de software', 'mdi-application', 1, '2026-01-23 16:13:59', 2),
	(3, 'Energía', 'Problemas eléctricos', 'mdi-flash', 1, '2026-01-23 16:13:59', 2),
	(4, 'Comunicación', 'Problemas de red', 'mdi-wifi-off', 1, '2026-01-23 16:13:59', 2),
	(5, 'Configuración', 'Errores de config', 'mdi-cog', 1, '2026-01-23 16:13:59', 2),
	(6, 'Usuario', 'Error de usuario', 'mdi-account-alert', 1, '2026-01-23 16:13:59', 2),
	(7, 'Bug de Sistema', 'Error o bug en el sistema', NULL, 1, '2026-02-11 00:02:23', 4),
	(8, 'Error de Integración', 'Falla en integración de sistemas', NULL, 1, '2026-02-11 00:02:23', 4),
	(9, 'Problema de Red', 'Problemas de conectividad de red', NULL, 1, '2026-02-11 00:02:23', 4),
	(10, 'Error de Facturación', 'Error en proceso de facturación', NULL, 1, '2026-02-11 00:02:23', 3),
	(11, 'Documento Extraviado', 'Documento perdido o extraviado', NULL, 1, '2026-02-11 00:02:23', 3),
	(12, 'Error de Registro', 'Error en registro de información', NULL, 1, '2026-02-11 00:02:23', 3);

-- Volcando estructura para tabla helpdesk_clonsa.ubicaciones
CREATE TABLE IF NOT EXISTS `ubicaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `departamento_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.ubicaciones: ~15 rows (aproximadamente)
INSERT INTO `ubicaciones` (`id`, `nombre`, `descripcion`, `activo`, `created_at`, `departamento_id`) VALUES
	(1, 'UM Bambas', 'Unidad Minera Las Bambas', 1, '2026-01-23 16:13:59', 2),
	(2, 'UM Yanacocha', 'Unidad Minera Yanacocha', 1, '2026-01-23 16:13:59', 2),
	(3, 'Oficina Principal', 'Oficina central', 1, '2026-01-23 16:13:59', 2),
	(4, 'Almacén', 'Almacén', 1, '2026-01-23 16:13:59', 2),
	(5, 'Cuajone', 'Unidad Minera Cuajone', 1, '2026-02-10 04:10:47', 2),
	(6, 'Chinalco', 'Unidad Minera Chinalco', 1, '2026-02-10 04:10:47', 2),
	(7, 'Yanacocha', 'Unidad Minera Yanacocha', 1, '2026-02-10 04:10:47', 2),
	(8, 'Coimolache', 'Unidad Minera Coimolache', 1, '2026-02-10 04:10:47', 2),
	(9, 'Antapaccay', 'Unidad Minera Antapaccay', 1, '2026-02-10 04:10:47', 2),
	(10, 'Oficina Central Lima', 'Oficina principal en Lima', 1, '2026-02-11 00:02:23', 3),
	(11, 'Oficina Contabilidad', 'Área de contabilidad', 1, '2026-02-11 00:02:23', 3),
	(12, 'Archivo Central', 'Archivo de documentos', 1, '2026-02-11 00:02:23', 3),
	(13, 'Data Center Principal', 'Centro de datos principal', 1, '2026-02-11 00:02:23', 4),
	(14, 'Oficina IT Lima', 'Oficina de IT en Lima', 1, '2026-02-11 00:02:23', 4),
	(15, 'Oficina IT Chile', 'Oficina de IT en Chile', 1, '2026-02-11 00:02:23', 4);

-- Volcando estructura para tabla helpdesk_clonsa.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_completo` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol_id` int NOT NULL,
  `departamento_id` int DEFAULT NULL,
  `area_id` int DEFAULT NULL,
  `avatar` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png',
  `activo` tinyint(1) DEFAULT '1',
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `rol_id` (`rol_id`),
  KEY `area_id` (`area_id`),
  KEY `idx_reset_token` (`reset_token`),
  KEY `idx_email` (`email`),
  KEY `fk_usuarios_departamento` (`departamento_id`),
  CONSTRAINT `fk_usuarios_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.usuarios: ~15 rows (aproximadamente)
INSERT INTO `usuarios` (`id`, `username`, `email`, `password`, `nombre_completo`, `telefono`, `rol_id`, `departamento_id`, `area_id`, `avatar`, `activo`, `ultimo_acceso`, `reset_token`, `reset_token_expires`, `created_at`) VALUES
	(1, 'admin', 'admin@clonsa.pe', '$2y$12$3QqyOXkbm1KfUMH6rnIkgOw9pvFtQZ9DUqm3.GbY2DQhFSjBr3tGm', 'Administrador General', NULL, 1, 1, NULL, 'default-avatar.png', 1, '2026-02-20 19:30:19', '748592', '2026-01-23 21:16:36', '2026-01-23 16:13:59'),
	(3, 'acontreras', 'acontreras@clonsa.com', '$2y$12$b77o8VBYZ0.PmC84/wqdC.3686u8IkEuu1J88pPXVle/zi6qm88kq', 'Amador Contreras L.', NULL, 2, 2, NULL, 'default-avatar.png', 1, '2026-02-20 21:15:03', NULL, NULL, '2026-01-25 23:18:44'),
	(4, 'irodriguez', 'irodriguez@clonsa.com', '$2y$12$NSSbSR7xfJ0v5/Jk51RaH.J63zlU1O5JlCZoJf7RsnVW0OuDYiQ7S', 'Iván Rodriguez', NULL, 3, 2, NULL, 'default-avatar.png', 1, '2026-02-22 03:00:09', NULL, NULL, '2026-01-25 23:18:44'),
	(5, 'fquispe', 'fquispe@clonsa.com', '$2y$12$U4MJ3K.BPfEOmB5nEhPSWeDSEEioQtRwE.yOdhqJG.qd9VhFlCIOm', 'Fernando Quispe', NULL, 3, 2, NULL, 'default-avatar.png', 1, '2026-02-20 19:56:59', NULL, NULL, '2026-01-25 23:18:44'),
	(6, 'dlazarte', 'dlazarte@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Daniel Lazarte', NULL, 3, 2, NULL, 'default-avatar.png', 1, '2026-01-25 23:35:40', NULL, NULL, '2026-01-25 23:18:44'),
	(7, 'lruiz', 'lruiz@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Luis Ruiz', NULL, 3, 2, NULL, 'default-avatar.png', 1, '2026-01-25 23:55:41', NULL, NULL, '2026-01-25 23:18:44'),
	(8, 'jtunoque', 'jtunoque@clonsa.com', '$2y$12$X01EUax9uIpWJpnOgZeFvOK4mR94Urszi40GDh3/3SRrG/XYn8tjW', 'Jack Tuñoque S.', NULL, 2, 4, NULL, 'default-avatar.png', 1, '2026-02-20 16:42:52', NULL, NULL, '2026-01-25 23:18:44'),
	(9, 'rarias', 'rarias@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Richard Arias', NULL, 3, 4, NULL, 'default-avatar.png', 1, NULL, NULL, NULL, '2026-01-25 23:18:44'),
	(10, 'cmedina', 'cmedina@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Carlos Medina', NULL, 3, 4, NULL, 'default-avatar.png', 1, '2026-01-25 23:35:19', NULL, NULL, '2026-01-25 23:18:44'),
	(11, 'ejimenez', 'ejimenez@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Enzo Jimenez', NULL, 2, 3, NULL, 'default-avatar.png', 1, '2026-01-25 23:35:28', NULL, NULL, '2026-01-25 23:18:44'),
	(12, 'garias', 'garias@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Geraldine Arias', NULL, 3, 3, NULL, 'default-avatar.png', 1, NULL, NULL, NULL, '2026-01-25 23:18:44'),
	(13, 'jagreda', 'jagreda@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Jesus Agreda', NULL, 3, 3, NULL, 'default-avatar.png', 1, NULL, NULL, NULL, '2026-01-25 23:18:44'),
	(14, 'prueba', 'admin@clonsa.com', '$2y$12$VFMtPRWfTKHpiHQSITLhTuRHJR3EHt4fPhJs9lWGo7.fQeZ4ohaHu', 'Prueba Dev', '989225914', 3, NULL, NULL, 'default-avatar.png', 1, NULL, NULL, NULL, '2026-02-15 03:21:18'),
	(15, 'admin1', 'jtunoquesa@unprg.edu.pe', '$2y$12$dfoP6uLkgGAjWFh67.6r7ufjtOC5PN8V2.epu8rPmLzRGq7HOuEcy', 'admin1', '989225914', 3, 2, NULL, 'default-avatar.png', 1, NULL, NULL, NULL, '2026-02-15 03:23:36'),
	(16, 'admin123456', 'admin@gmail.com', '$2y$12$2YKMrsi.Wl3BzB7QTnDzceTapaDp7K.faoA5GsebXJswYoddKbl3u', 'admin123456', '987564123', 3, 4, NULL, 'default-avatar.png', 1, NULL, NULL, NULL, '2026-02-15 03:32:40');

-- Volcando estructura para vista helpdesk_clonsa.vista_tickets
-- Creando tabla temporal para superar errores de dependencia de VIEW
CREATE TABLE `vista_tickets` (
	`id` INT(10) NOT NULL,
	`codigo` VARCHAR(20) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`titulo` VARCHAR(200) NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`descripcion` TEXT NOT NULL COLLATE 'utf8mb4_unicode_ci',
	`progreso` INT(10) NULL,
	`creador` VARCHAR(150) NULL COLLATE 'utf8mb4_unicode_ci',
	`asignado` VARCHAR(150) NULL COLLATE 'utf8mb4_unicode_ci',
	`area` VARCHAR(100) NULL COLLATE 'utf8mb4_unicode_ci',
	`prioridad` VARCHAR(50) NULL COLLATE 'utf8mb4_unicode_ci',
	`prioridad_color` VARCHAR(20) NULL COLLATE 'utf8mb4_unicode_ci',
	`estado` VARCHAR(50) NULL COLLATE 'utf8mb4_unicode_ci',
	`estado_color` VARCHAR(20) NULL COLLATE 'utf8mb4_unicode_ci',
	`canal` VARCHAR(100) NULL COLLATE 'utf8mb4_unicode_ci',
	`actividad` VARCHAR(100) NULL COLLATE 'utf8mb4_unicode_ci',
	`tipo_falla` VARCHAR(100) NULL COLLATE 'utf8mb4_unicode_ci',
	`ubicacion` VARCHAR(100) NULL COLLATE 'utf8mb4_unicode_ci',
	`equipo` VARCHAR(100) NULL COLLATE 'utf8mb4_unicode_ci',
	`codigo_equipo` VARCHAR(50) NULL COLLATE 'utf8mb4_unicode_ci',
	`created_at` TIMESTAMP NULL,
	`updated_at` TIMESTAMP NULL,
	`fecha_resolucion` TIMESTAMP NULL,
	`horas_transcurridas` BIGINT(19) NULL
) ENGINE=MyISAM;

-- Volcando estructura para disparador helpdesk_clonsa.after_insert_ticket
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `after_insert_ticket` AFTER INSERT ON `tickets` FOR EACH ROW BEGIN
    INSERT INTO historial (ticket_id, usuario_id, accion)
    VALUES (NEW.id, NEW.usuario_id, 'Ticket creado');
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Volcando estructura para disparador helpdesk_clonsa.after_update_ticket
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `after_update_ticket` AFTER UPDATE ON `tickets` FOR EACH ROW BEGIN
    IF OLD.estado_id != NEW.estado_id THEN
        INSERT INTO historial (ticket_id, usuario_id, accion, campo_modificado, valor_anterior, valor_nuevo)
        SELECT NEW.id, NEW.asignado_a, 'Estado cambiado', 'estado', 
               e1.nombre, e2.nombre
        FROM estados e1, estados e2
        WHERE e1.id = OLD.estado_id AND e2.id = NEW.estado_id;
    END IF;
    
    IF OLD.progreso != NEW.progreso THEN
        INSERT INTO historial (ticket_id, usuario_id, accion, campo_modificado, valor_anterior, valor_nuevo)
        VALUES (NEW.id, NEW.asignado_a, 'Progreso actualizado', 'progreso', 
                CONCAT(OLD.progreso, '%'), CONCAT(NEW.progreso, '%'));
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Volcando estructura para disparador helpdesk_clonsa.before_insert_ticket
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `before_insert_ticket` BEFORE INSERT ON `tickets` FOR EACH ROW BEGIN
    DECLARE next_num INT;
    DECLARE dept_abrev VARCHAR(5);
    DECLARE dept_id INT;
    DECLARE num_digits INT;
    
    
    IF NEW.departamento_id IS NULL OR NEW.departamento_id = 0 THEN
        SELECT departamento_id INTO dept_id FROM usuarios WHERE id = NEW.usuario_id;
        SET NEW.departamento_id = dept_id;
    ELSE
        SET dept_id = NEW.departamento_id;
    END IF;
    
    
    SELECT COALESCE(abreviatura, 'GN') INTO dept_abrev FROM departamentos WHERE id = dept_id;
    IF dept_abrev IS NULL THEN
        SET dept_abrev = 'GN';
    END IF;
    
    
    SELECT COALESCE(ultimo_numero, 0) + 1 INTO next_num 
    FROM ticket_contadores 
    WHERE departamento_id = dept_id;
    
    IF next_num IS NULL THEN
        SET next_num = 1;
        INSERT INTO ticket_contadores (departamento_id, ultimo_numero, prefijo) VALUES (dept_id, 1, 'TKN');
    ELSE
        UPDATE ticket_contadores SET ultimo_numero = next_num WHERE departamento_id = dept_id;
    END IF;
    
    
    IF next_num < 100 THEN
        SET NEW.codigo = CONCAT('TKN-', dept_abrev, '-', LPAD(next_num, 2, '0'));
    ELSE
        SET NEW.codigo = CONCAT('TKN-', dept_abrev, '-', next_num);
    END IF;
    
    
    IF NEW.asignado_a IS NULL THEN
        SET NEW.asignado_a = NEW.usuario_id;
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Volcando estructura para disparador helpdesk_clonsa.before_update_ticket_progreso
SET @OLDTMP_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';
DELIMITER //
CREATE TRIGGER `before_update_ticket_progreso` BEFORE UPDATE ON `tickets` FOR EACH ROW BEGIN
    IF NEW.progreso = 100 AND OLD.progreso <> 100 THEN
        SET NEW.estado_id = 4;
        SET NEW.fecha_resolucion = NOW();
    END IF;
END//
DELIMITER ;
SET SQL_MODE=@OLDTMP_SQL_MODE;

-- Volcando estructura para vista helpdesk_clonsa.vista_tickets
-- Eliminando tabla temporal y crear estructura final de VIEW
DROP TABLE IF EXISTS `vista_tickets`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `vista_tickets` AS select `t`.`id` AS `id`,`t`.`codigo` AS `codigo`,`t`.`titulo` AS `titulo`,`t`.`descripcion` AS `descripcion`,`t`.`progreso` AS `progreso`,`u1`.`nombre_completo` AS `creador`,`u2`.`nombre_completo` AS `asignado`,`a`.`nombre` AS `area`,`p`.`nombre` AS `prioridad`,`p`.`color` AS `prioridad_color`,`e`.`nombre` AS `estado`,`e`.`color` AS `estado_color`,`ca`.`nombre` AS `canal`,`act`.`nombre` AS `actividad`,`tf`.`nombre` AS `tipo_falla`,`ub`.`nombre` AS `ubicacion`,`eq`.`nombre` AS `equipo`,`ce`.`codigo` AS `codigo_equipo`,`t`.`created_at` AS `created_at`,`t`.`updated_at` AS `updated_at`,`t`.`fecha_resolucion` AS `fecha_resolucion`,timestampdiff(HOUR,`t`.`created_at`,coalesce(`t`.`fecha_resolucion`,now())) AS `horas_transcurridas` from (((((((((((`tickets` `t` left join `usuarios` `u1` on((`t`.`usuario_id` = `u1`.`id`))) left join `usuarios` `u2` on((`t`.`asignado_a` = `u2`.`id`))) left join `areas` `a` on((`t`.`area_id` = `a`.`id`))) left join `prioridades` `p` on((`t`.`prioridad_id` = `p`.`id`))) left join `estados` `e` on((`t`.`estado_id` = `e`.`id`))) left join `canales_atencion` `ca` on((`t`.`canal_atencion_id` = `ca`.`id`))) left join `actividades` `act` on((`t`.`actividad_id` = `act`.`id`))) left join `tipos_falla` `tf` on((`t`.`tipo_falla_id` = `tf`.`id`))) left join `ubicaciones` `ub` on((`t`.`ubicacion_id` = `ub`.`id`))) left join `equipos` `eq` on((`t`.`equipo_id` = `eq`.`id`))) left join `codigos_equipo` `ce` on((`t`.`codigo_equipo_id` = `ce`.`id`)));

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
