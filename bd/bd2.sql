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
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.actividades: ~7 rows (aproximadamente)
INSERT INTO `actividades` (`id`, `nombre`, `descripcion`, `color`, `activo`, `created_at`) VALUES
	(1, 'Mantenimiento Preventivo', 'Mantenimiento programado', '#28a745', 1, '2026-01-23 16:13:59'),
	(2, 'Mantenimiento Correctivo', 'Reparación de fallas', '#dc3545', 1, '2026-01-23 16:13:59'),
	(3, 'Mantenimiento Predictivo', 'Mantenimiento predictivo', '#17a2b8', 1, '2026-01-23 16:13:59'),
	(4, 'Software Radar MSR', 'Software Radar MSR', '#ffc107', 1, '2026-01-23 16:13:59'),
	(5, 'Soporte Oficina Perú', 'Soporte Oficina Perú', '#28a745', 1, '2026-01-23 16:13:59'),
	(6, 'Soporte Oficina Chile', 'Soporte Oficina Chile', '#dc3545', 1, '2026-01-23 16:13:59'),
	(7, 'Desarrollo & Tecnología', 'Desarrollo y Tecnología', '#17a2b8', 1, '2026-01-23 16:13:59');

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.actividades_departamentos: ~8 rows (aproximadamente)
INSERT INTO `actividades_departamentos` (`id`, `actividad_id`, `departamento_id`, `created_at`) VALUES
	(1, 1, 2, '2026-01-26 06:03:20'),
	(2, 2, 2, '2026-01-26 06:03:20'),
	(3, 3, 2, '2026-01-26 06:03:20'),
	(4, 4, 2, '2026-01-26 06:03:20'),
	(5, 4, 4, '2026-01-26 06:03:20'),
	(6, 5, 4, '2026-01-26 06:03:20'),
	(7, 6, 4, '2026-01-26 06:03:20'),
	(8, 7, 4, '2026-01-26 06:03:20');

-- Volcando estructura para tabla helpdesk_clonsa.adjuntos
CREATE TABLE IF NOT EXISTS `adjuntos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int DEFAULT NULL,
  `comentario_id` int DEFAULT NULL,
  `usuario_id` int NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_archivo` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_archivo` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
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
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
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
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipo_id` int DEFAULT NULL,
  `ubicacion_id` int DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `equipo_id` (`equipo_id`),
  KEY `ubicacion_id` (`ubicacion_id`),
  CONSTRAINT `codigos_equipo_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `codigos_equipo_ibfk_2` FOREIGN KEY (`ubicacion_id`) REFERENCES `ubicaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.codigos_equipo: ~5 rows (aproximadamente)
INSERT INTO `codigos_equipo` (`id`, `codigo`, `equipo_id`, `ubicacion_id`, `descripcion`, `activo`, `created_at`) VALUES
	(1, 'MSR181', 2, 1, 'Radar MSRConnect 181', 1, '2026-01-23 16:13:59'),
	(2, 'MSR184', 2, 1, 'Radar MSRConnect 184', 1, '2026-01-23 16:13:59'),
	(3, 'MSR234', 2, 2, 'Radar MSRConnect 234', 1, '2026-01-23 16:13:59'),
	(4, 'CAM001', 1, 1, 'Cámara 001', 1, '2026-01-23 16:13:59'),
	(5, 'CAM002', 1, 2, 'Cámara 002', 1, '2026-01-23 16:13:59');

-- Volcando estructura para tabla helpdesk_clonsa.comentarios
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `es_privado` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_ticket` (`ticket_id`),
  CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.comentarios: ~0 rows (aproximadamente)

-- Volcando estructura para tabla helpdesk_clonsa.departamentos
CREATE TABLE IF NOT EXISTS `departamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `jefe_id` int DEFAULT NULL COMMENT 'FK a usuarios - jefe del departamento',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_departamentos_jefe` (`jefe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.departamentos: ~4 rows (aproximadamente)
INSERT INTO `departamentos` (`id`, `nombre`, `descripcion`, `jefe_id`, `activo`, `created_at`, `updated_at`) VALUES
	(1, 'General', 'Todos los departamentos', NULL, 1, '2026-01-25 22:02:52', '2026-01-25 22:02:52'),
	(2, 'Soporte Técnico', 'Atención técnica y mantenimiento de equipos', 3, 1, '2026-01-25 22:02:52', '2026-01-25 23:18:44'),
	(3, 'Administración', 'Gestión administrativa y recursos', 11, 1, '2026-01-25 22:02:52', '2026-01-25 23:18:44'),
	(4, 'IT & Desarrollo', 'Desarrollo de software y sistemas', 8, 1, '2026-01-25 22:02:52', '2026-01-25 23:18:44');

-- Volcando estructura para tabla helpdesk_clonsa.equipos
CREATE TABLE IF NOT EXISTS `equipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.equipos: ~7 rows (aproximadamente)
INSERT INTO `equipos` (`id`, `nombre`, `descripcion`, `activo`, `created_at`) VALUES
	(1, 'Cámara', 'Cámara de seguridad', 1, '2026-01-23 16:13:59'),
	(2, 'Radar', 'Sistema de radar', 1, '2026-01-23 16:13:59'),
	(3, 'Servidor', 'Servidor', 1, '2026-01-23 16:13:59'),
	(4, 'Estación de Trabajo', 'PC', 1, '2026-01-23 16:13:59'),
	(5, 'Laptop', 'Laptop', 1, '2026-01-23 16:13:59'),
	(6, 'Switch', 'Switch de red', 1, '2026-01-23 16:13:59'),
	(7, 'Router', 'Router', 1, '2026-01-23 16:13:59');

-- Volcando estructura para tabla helpdesk_clonsa.estados
CREATE TABLE IF NOT EXISTS `estados` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_final` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.estados: ~6 rows (aproximadamente)
INSERT INTO `estados` (`id`, `nombre`, `descripcion`, `color`, `es_final`, `created_at`) VALUES
	(1, 'Abierto', 'Ticket recién creado', '#6c757d', 0, '2026-01-23 16:13:59'),
	(2, 'En Proceso', 'Ticket en atención', '#0d6efd', 0, '2026-01-23 16:13:59'),
	(3, 'Pendiente Usuario', 'Esperando respuesta', '#ffc107', 0, '2026-01-23 16:13:59'),
	(4, 'Resuelto', 'Ticket resuelto', '#28a745', 1, '2026-01-23 16:13:59'),
	(5, 'Cerrado', 'Ticket cerrado', '#198754', 1, '2026-01-23 16:13:59'),
	(6, 'Rechazado', 'Ticket rechazado', '#dc3545', 1, '2026-01-23 16:13:59');

-- Volcando estructura para tabla helpdesk_clonsa.historial
CREATE TABLE IF NOT EXISTS `historial` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `accion` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `campo_modificado` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valor_anterior` text COLLATE utf8mb4_unicode_ci,
  `valor_nuevo` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_ticket` (`ticket_id`),
  CONSTRAINT `historial_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=233 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.historial: ~20 rows (aproximadamente)
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
	(232, 253, 3, 'Ticket creado', NULL, NULL, NULL, '2026-01-31 17:14:17');

-- Volcando estructura para tabla helpdesk_clonsa.notificaciones
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `ticket_id` int DEFAULT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` text COLLATE utf8mb4_unicode_ci NOT NULL,
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

-- Volcando estructura para tabla helpdesk_clonsa.prioridades
CREATE TABLE IF NOT EXISTS `prioridades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nivel` int NOT NULL,
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
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
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `fecha_limite` timestamp NULL DEFAULT NULL,
  `fecha_resolucion` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
) ENGINE=InnoDB AUTO_INCREMENT=254 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.tickets: ~20 rows (aproximadamente)
INSERT INTO `tickets` (`id`, `departamento_id`, `codigo`, `titulo`, `descripcion`, `usuario_id`, `area_id`, `prioridad_id`, `estado_id`, `asignado_a`, `canal_atencion_id`, `actividad_id`, `tipo_falla_id`, `ubicacion_id`, `equipo_id`, `codigo_equipo_id`, `progreso`, `fecha_limite`, `fecha_resolucion`, `created_at`, `updated_at`) VALUES
	(44, 2, 'TKT-2026-0001', 'Mantenimiento preventivo', 'Revisión mensual', 1, NULL, 1, 1, 1, 1, 1, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-20 14:00:00', '2026-01-27 23:13:27'),
	(45, 2, 'TKT-2026-0002', 'Actualización antivirus', 'Actualización', 1, NULL, 1, 1, 1, 1, 1, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-21 15:30:00', '2026-01-27 23:13:27'),
	(46, 2, 'TKT-2026-0003', 'Backup semanal', 'Verificación', 1, NULL, 1, 1, 1, 1, 1, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-22 13:00:00', '2026-01-27 23:13:27'),
	(47, 2, 'TKT-2026-0004', 'Falla impresora', 'Papel atascado', 1, NULL, 1, 1, 1, 1, 2, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-19 19:20:00', '2026-01-27 23:13:27'),
	(48, 2, 'TKT-2026-0005', 'PC no enciende', 'No responde', 1, NULL, 1, 1, 1, 1, 2, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-23 16:00:00', '2026-01-27 23:13:27'),
	(49, 2, 'TKT-2026-0006', 'Teclado fallando', 'Teclas rotas', 1, NULL, 1, 1, 1, 1, 2, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-24 20:45:00', '2026-01-27 23:13:27'),
	(50, 2, 'TKT-2026-0007', 'Análisis rendimiento', 'Monitoreo', 1, NULL, 1, 1, 1, 1, 3, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-18 13:30:00', '2026-01-27 23:13:27'),
	(51, 2, 'TKT-2026-0008', 'Revisión logs', 'Análisis', 1, NULL, 1, 1, 1, 1, 3, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-25 14:15:00', '2026-01-27 23:13:27'),
	(52, 2, 'TKT-2026-0009', 'Config radar MSR', 'Instalación', 1, NULL, 1, 1, 1, 1, 4, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-26 12:00:00', '2026-01-27 23:13:27'),
	(53, 4, 'TKT-2026-0010', 'Actualización MSR', 'Upgrade v3.2', 1, NULL, 1, 1, 1, 1, 4, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-20 15:00:00', '2026-01-27 23:13:27'),
	(54, 4, 'TKT-2026-0011', 'Bug en MSR', 'Error reporte', 1, NULL, 1, 1, 1, 1, 4, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-22 18:30:00', '2026-01-27 23:13:27'),
	(55, 4, 'TKT-2026-0012', 'Soporte Lima', 'Config VPN', 1, NULL, 1, 1, 1, 1, 5, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-19 16:00:00', '2026-01-27 23:13:27'),
	(56, 4, 'TKT-2026-0013', 'Instalación Office', 'Office 365', 1, NULL, 1, 1, 1, 1, 5, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-21 14:30:00', '2026-01-27 23:13:27'),
	(57, 4, 'TKT-2026-0014', 'Migración servidor', 'Santiago', 1, NULL, 1, 1, 1, 1, 6, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-23 13:00:00', '2026-01-27 23:13:27'),
	(58, 4, 'TKT-2026-0015', 'Soporte Chile', 'Conectividad', 1, NULL, 1, 1, 1, 1, 6, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-24 15:15:00', '2026-01-27 23:13:27'),
	(59, 4, 'TKT-2026-0016', 'Desarrollo', 'Facturación', 1, NULL, 1, 1, 1, 1, 7, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-18 19:00:00', '2026-01-27 23:13:27'),
	(60, 4, 'TKT-2026-0017', 'Integración API', 'Proveedor', 1, NULL, 1, 1, 1, 1, 7, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-25 16:30:00', '2026-01-27 23:13:27'),
	(61, 2, 'TKT-2026-0018', 'Mantenimiento UPS', 'Revisión', 1, NULL, 1, 1, 1, 1, 1, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-17 12:30:00', '2026-01-27 23:13:27'),
	(62, 2, 'TKT-2026-0019', 'Reparación switch', 'Puertos', 1, NULL, 1, 1, 1, 1, 2, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-20 21:00:00', '2026-01-27 23:13:27'),
	(63, 4, 'TKT-2026-0020', 'Deploy', 'Producción', 1, NULL, 1, 1, 1, 1, 7, 1, 1, 1, NULL, 0, NULL, NULL, '2026-01-26 17:00:00', '2026-01-27 23:13:27'),
	(64, 1, 'TKT-2026-0021', 'Gestión Documental', 'Organizar documentación', 3, NULL, 2, 5, 1, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-17 17:00:00', '2026-01-17 13:00:00', '2026-01-31 13:07:13'),
	(65, 1, 'TKT-2026-0022', 'Compra Suministros', 'Adquisición material', 4, NULL, 2, 5, 1, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-17 22:00:00', '2026-01-17 19:00:00', '2026-01-31 13:07:13'),
	(66, 1, 'TKT-2026-0023', 'Coordinación Evento', 'Organizar reunión', 5, NULL, 1, 5, 1, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-18 21:00:00', '2026-01-18 14:00:00', '2026-01-31 13:07:13'),
	(67, 1, 'TKT-2026-0024', 'Actualización Políticas', 'Revisar manual', 6, NULL, 2, 5, 1, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-19 20:00:00', '2026-01-19 13:30:00', '2026-01-31 13:07:13'),
	(68, 1, 'TKT-2026-0025', 'Gestión Contratos', 'Renovación contratos', 7, NULL, 2, 5, 1, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-20 19:30:00', '2026-01-20 15:00:00', '2026-01-31 13:07:13'),
	(69, 1, 'TKT-2026-0026', 'Auditoría Interna', 'Preparar documentación', 3, NULL, 1, 5, 1, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 22:00:00', '2026-01-21 13:00:00', '2026-01-31 13:07:13'),
	(70, 1, 'TKT-2026-0027', 'Planificación Presupuesto', 'Elaborar presupuesto', 4, NULL, 1, 5, 1, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-23 21:00:00', '2026-01-22 14:00:00', '2026-01-31 13:07:13'),
	(71, 1, 'TKT-2026-0028', 'Gestión Licencias', 'Renovar licencias', 5, NULL, 2, 5, 1, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-23 19:00:00', '2026-01-23 15:30:00', '2026-01-31 13:07:13'),
	(72, 1, 'TKT-2026-0029', 'Coordinación Capacitación', 'Organizar programa', 6, NULL, 2, 5, 1, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 17:45:00', '2026-01-24 13:15:00', '2026-01-31 13:07:13'),
	(73, 1, 'TKT-2026-0030', 'Gestión Seguros', 'Renovación pólizas', 7, NULL, 2, 5, 1, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 18:30:00', '2026-01-25 14:30:00', '2026-01-31 13:07:13'),
	(74, 1, 'TKT-2026-0031', 'Actualización Base Datos', 'Depurar información', 3, NULL, 2, 5, 1, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 20:00:00', '2026-01-26 15:00:00', '2026-01-31 13:07:13'),
	(75, 1, 'TKT-2026-0032', 'Coordinación Logística', 'Gestionar calendario', 4, NULL, 2, 5, 1, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 18:15:00', '2026-01-27 13:45:00', '2026-01-31 13:07:13'),
	(76, 1, 'TKT-2026-0033', 'Gestión Archivo', 'Digitalizar documentos', 5, NULL, 2, 5, 1, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 21:30:00', '2026-01-28 14:15:00', '2026-01-31 13:07:13'),
	(77, 1, 'TKT-2026-0034', 'Actualización Directorio', 'Actualizar extensiones', 6, NULL, 2, 5, 1, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 22:00:00', '2026-01-28 19:00:00', '2026-01-31 13:07:13'),
	(78, 1, 'TKT-2026-0035', 'Coordinación Viajes', 'Gestionar itinerarios', 7, NULL, 2, 5, 1, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 17:00:00', '2026-01-29 13:00:00', '2026-01-31 13:07:13'),
	(79, 1, 'TKT-2026-0036', 'Gestión Correspondencia', 'Atender requerimientos', 3, NULL, 3, 5, 1, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 21:45:00', '2026-01-29 18:30:00', '2026-01-31 13:07:13'),
	(80, 1, 'TKT-2026-0037', 'Actualización Organigrama', 'Actualizar estructura', 4, NULL, 2, 5, 1, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 17:30:00', '2026-01-30 14:00:00', '2026-01-31 13:07:13'),
	(81, 1, 'TKT-2026-0038', 'Relaciones Públicas', 'Gestionar comunicados', 5, NULL, 2, 5, 1, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 22:30:00', '2026-01-30 19:00:00', '2026-01-31 13:07:13'),
	(82, 2, 'TKT-2026-0039', 'Mant. Preventivo Radar', 'Revisión mensual', 4, NULL, 2, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-17 19:00:00', '2026-01-17 13:00:00', '2026-01-31 13:07:13'),
	(83, 2, 'TKT-2026-0040', 'Mant. Correctivo Radar', 'Reparación sensor', 5, NULL, 3, 5, 3, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-17 23:30:00', '2026-01-17 20:00:00', '2026-01-31 13:07:13'),
	(84, 2, 'TKT-2026-0041', 'Mant. Preventivo CCTV', 'Limpieza cámaras', 6, NULL, 2, 5, 3, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-18 17:30:00', '2026-01-18 13:30:00', '2026-01-31 13:07:13'),
	(85, 2, 'TKT-2026-0042', 'Mant. Predictivo Equipos', 'Análisis vibraciones', 7, NULL, 2, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-18 22:00:00', '2026-01-18 19:00:00', '2026-01-31 13:07:13'),
	(86, 2, 'TKT-2026-0043', 'Mant. Correctivo PLC', 'Reparar controlador', 4, NULL, 3, 5, 3, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-19 20:00:00', '2026-01-19 14:00:00', '2026-01-31 13:07:13'),
	(87, 2, 'TKT-2026-0044', 'Mant. Preventivo Compresor', 'Cambio filtros', 5, NULL, 2, 5, 3, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-20 17:45:00', '2026-01-20 13:15:00', '2026-01-31 13:07:13'),
	(88, 2, 'TKT-2026-0045', 'Mant. Correctivo Motor', 'Reparar bobinado', 6, NULL, 3, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-20 23:00:00', '2026-01-20 19:00:00', '2026-01-31 13:07:13'),
	(89, 2, 'TKT-2026-0046', 'Mant. Preventivo UPS', 'Prueba baterías', 7, NULL, 2, 5, 3, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-21 18:00:00', '2026-01-21 14:30:00', '2026-01-31 13:07:13'),
	(90, 2, 'TKT-2026-0047', 'Mant. Predictivo Bomba', 'Análisis temperatura', 4, NULL, 2, 5, 3, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 17:00:00', '2026-01-22 13:00:00', '2026-01-31 13:07:13'),
	(91, 2, 'TKT-2026-0048', 'Mant. Correctivo Variador', 'Reemplazar tarjeta', 5, NULL, 3, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 22:30:00', '2026-01-22 19:30:00', '2026-01-31 13:07:13'),
	(92, 2, 'TKT-2026-0049', 'Mant. Preventivo Generador', 'Cambio aceite', 6, NULL, 2, 5, 3, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-23 18:15:00', '2026-01-23 13:45:00', '2026-01-31 13:07:13'),
	(93, 2, 'TKT-2026-0050', 'Mant. Correctivo Sensor', 'Reemplazar sensor', 7, NULL, 3, 5, 3, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 16:30:00', '2026-01-24 14:00:00', '2026-01-31 13:07:13'),
	(94, 2, 'TKT-2026-0051', 'Mant. Preventivo Panel', 'Medición parámetros', 4, NULL, 2, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 22:00:00', '2026-01-24 19:00:00', '2026-01-31 13:07:13'),
	(95, 2, 'TKT-2026-0052', 'Mant. Predictivo Transform', 'Termografía', 5, NULL, 2, 5, 3, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 19:00:00', '2026-01-25 13:30:00', '2026-01-31 13:07:13'),
	(96, 2, 'TKT-2026-0053', 'Mant. Correctivo Aire', 'Reparar clima', 6, NULL, 3, 5, 3, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 17:45:00', '2026-01-26 14:15:00', '2026-01-31 13:07:13'),
	(97, 2, 'TKT-2026-0054', 'Mant. Preventivo Extintores', 'Recarga', 7, NULL, 2, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 21:30:00', '2026-01-26 19:30:00', '2026-01-31 13:07:13'),
	(98, 2, 'TKT-2026-0055', 'Mant. Correctivo Montacargas', 'Reparar hidráulico', 4, NULL, 3, 5, 3, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 18:00:00', '2026-01-27 13:00:00', '2026-01-31 13:07:13'),
	(99, 2, 'TKT-2026-0056', 'Mant. Preventivo Grúa', 'Inspección', 5, NULL, 2, 5, 3, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 21:00:00', '2026-01-28 14:00:00', '2026-01-31 13:07:13'),
	(100, 2, 'TKT-2026-0057', 'Mant. Predictivo Rodamiento', 'Análisis', 6, NULL, 2, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 17:15:00', '2026-01-29 13:15:00', '2026-01-31 13:07:13'),
	(101, 2, 'TKT-2026-0058', 'Mant. Correctivo Válvula', 'Reemplazar', 7, NULL, 3, 5, 3, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 21:30:00', '2026-01-29 19:00:00', '2026-01-31 13:07:13'),
	(102, 2, 'TKT-2026-0059', 'Mant. Preventivo Cinta', 'Ajuste', 4, NULL, 2, 5, 3, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 17:30:00', '2026-01-30 13:30:00', '2026-01-31 13:07:13'),
	(103, 2, 'TKT-2026-0060', 'Mant. Predictivo Motor', 'Termografía', 5, NULL, 2, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 22:00:00', '2026-01-30 19:00:00', '2026-01-31 13:07:13'),
	(104, 2, 'TKT-2026-0061', 'Mant. Prev. Excavadora', 'Servicio 500h', 6, NULL, 2, 5, 4, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-17 22:00:00', '2026-01-17 14:00:00', '2026-01-31 13:07:13'),
	(105, 2, 'TKT-2026-0062', 'Mant. Corr. Bomba', 'Reparar sello', 7, NULL, 3, 5, 4, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-18 18:30:00', '2026-01-18 15:00:00', '2026-01-31 13:07:13'),
	(106, 2, 'TKT-2026-0063', 'Mant. Prev. Camión', 'Cambio aceite', 4, NULL, 2, 5, 4, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-19 17:00:00', '2026-01-19 13:00:00', '2026-01-31 13:07:13'),
	(107, 2, 'TKT-2026-0064', 'Mant. Pred. Rodillo', 'Medición', 5, NULL, 2, 5, 4, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-20 19:00:00', '2026-01-20 14:30:00', '2026-01-31 13:07:13'),
	(108, 2, 'TKT-2026-0065', 'Mant. Corr. Retroexc', 'Reparar cilindro', 6, NULL, 3, 5, 4, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-21 20:00:00', '2026-01-21 13:15:00', '2026-01-31 13:07:13'),
	(109, 2, 'TKT-2026-0066', 'Mant. Prev. Motoniveladora', 'Lubricación', 7, NULL, 2, 5, 4, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 18:00:00', '2026-01-22 14:00:00', '2026-01-31 13:07:13'),
	(110, 2, 'TKT-2026-0067', 'Mant. Corr. Tractor', 'Reemplazar zapatas', 4, NULL, 3, 5, 4, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-23 21:00:00', '2026-01-23 15:00:00', '2026-01-31 13:07:13'),
	(111, 2, 'TKT-2026-0068', 'Mant. Prev. Cargador', 'Inspección', 5, NULL, 2, 5, 4, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 17:30:00', '2026-01-24 13:30:00', '2026-01-31 13:07:13'),
	(112, 2, 'TKT-2026-0069', 'Mant. Pred. Martillo', 'Análisis presión', 6, NULL, 2, 5, 4, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 18:45:00', '2026-01-25 14:15:00', '2026-01-31 13:07:13'),
	(113, 2, 'TKT-2026-0070', 'Mant. Corr. Perforadora', 'Reparar', 7, NULL, 3, 5, 4, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 19:00:00', '2026-01-26 13:00:00', '2026-01-31 13:07:13'),
	(114, 2, 'TKT-2026-0071', 'Mant. Prev. Pala', 'Cambio filtros', 4, NULL, 2, 5, 4, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 18:00:00', '2026-01-27 14:30:00', '2026-01-31 13:07:13'),
	(115, 2, 'TKT-2026-0072', 'Mant. Corr. Cisterna', 'Reparar bomba', 5, NULL, 3, 5, 4, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 18:30:00', '2026-01-28 15:00:00', '2026-01-31 13:07:13'),
	(116, 2, 'TKT-2026-0073', 'Mant. Prev. Minicargador', 'Servicio 250h', 6, NULL, 2, 5, 4, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 19:15:00', '2026-01-29 13:45:00', '2026-01-31 13:07:13'),
	(117, 2, 'TKT-2026-0074', 'Mant. Pred. Vibro', 'Análisis', 7, NULL, 2, 5, 4, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 22:30:00', '2026-01-29 20:00:00', '2026-01-31 13:07:13'),
	(118, 2, 'TKT-2026-0075', 'Mant. Corr. Mezcladora', 'Reemplazar', 4, NULL, 3, 5, 4, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 17:00:00', '2026-01-30 13:00:00', '2026-01-31 13:07:13'),
	(119, 2, 'TKT-2026-0076', 'Mant. Prev. Chancadora', 'Inspección', 5, NULL, 2, 5, 4, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 22:30:00', '2026-01-30 18:30:00', '2026-01-31 13:07:13'),
	(120, 2, 'TKT-2026-0077', 'Mant. Prev. Fajas', 'Ajuste', 6, NULL, 2, 5, 5, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-17 19:00:00', '2026-01-17 15:00:00', '2026-01-31 13:07:13'),
	(121, 2, 'TKT-2026-0078', 'Mant. Corr. Zarandas', 'Reemplazar', 7, NULL, 3, 5, 5, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-19 18:00:00', '2026-01-19 14:00:00', '2026-01-31 13:07:13'),
	(122, 2, 'TKT-2026-0079', 'Mant. Prev. Molino', 'Cambio', 4, NULL, 2, 5, 5, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 22:00:00', '2026-01-21 13:00:00', '2026-01-31 13:07:13'),
	(123, 2, 'TKT-2026-0080', 'Mant. Pred. Separador', 'Medición', 5, NULL, 2, 5, 5, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-23 18:00:00', '2026-01-23 14:30:00', '2026-01-31 13:07:13'),
	(124, 2, 'TKT-2026-0081', 'Mant. Corr. Bomba Lodos', 'Reparar', 6, NULL, 3, 5, 5, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 20:00:00', '2026-01-24 15:00:00', '2026-01-31 13:07:13'),
	(125, 2, 'TKT-2026-0082', 'Mant. Prev. Filtro', 'Inspección', 7, NULL, 2, 5, 5, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 17:30:00', '2026-01-25 13:30:00', '2026-01-31 13:07:13'),
	(126, 2, 'TKT-2026-0083', 'Mant. Corr. Espesador', 'Reparar', 4, NULL, 3, 5, 5, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 19:00:00', '2026-01-26 14:00:00', '2026-01-31 13:07:13'),
	(127, 2, 'TKT-2026-0084', 'Mant. Prev. Ciclones', 'Reemplazo', 5, NULL, 2, 5, 5, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 19:00:00', '2026-01-27 15:00:00', '2026-01-31 13:07:13'),
	(128, 2, 'TKT-2026-0085', 'Mant. Pred. Reductor', 'Análisis', 6, NULL, 2, 5, 5, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 17:15:00', '2026-01-28 13:15:00', '2026-01-31 13:07:13'),
	(129, 2, 'TKT-2026-0086', 'Mant. Corr. Válvula', 'Reemplazar', 7, NULL, 3, 5, 5, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 17:00:00', '2026-01-29 14:30:00', '2026-01-31 13:07:13'),
	(130, 2, 'TKT-2026-0087', 'Mant. Prev. Agitador', 'Balanceo', 4, NULL, 2, 5, 5, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 18:00:00', '2026-01-30 14:00:00', '2026-01-31 13:07:13'),
	(131, 2, 'TKT-2026-0088', 'Mant. Pred. Motor AT', 'Termografía', 5, NULL, 2, 5, 5, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 22:30:00', '2026-01-30 19:30:00', '2026-01-31 13:07:13'),
	(132, 2, 'TKT-2026-0089', 'Mant. Prev. Ventiladores', 'Limpieza', 7, NULL, 2, 5, 6, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-18 17:00:00', '2026-01-18 13:00:00', '2026-01-31 13:07:13'),
	(133, 2, 'TKT-2026-0090', 'Mant. Corr. Sensor', 'Calibrar', 4, NULL, 3, 5, 6, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-20 18:00:00', '2026-01-20 14:00:00', '2026-01-31 13:07:13'),
	(134, 2, 'TKT-2026-0091', 'Mant. Prev. Neumático', 'Revisión', 5, NULL, 2, 5, 6, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 19:00:00', '2026-01-22 13:30:00', '2026-01-31 13:07:13'),
	(135, 2, 'TKT-2026-0092', 'Mant. Pred. Eje', 'Medición', 6, NULL, 2, 5, 6, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 18:00:00', '2026-01-24 14:00:00', '2026-01-31 13:07:13'),
	(136, 2, 'TKT-2026-0093', 'Mant. Corr. Switch', 'Reemplazar', 7, NULL, 3, 5, 6, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 19:00:00', '2026-01-25 15:00:00', '2026-01-31 13:07:13'),
	(137, 2, 'TKT-2026-0094', 'Mant. Prev. Iluminación', 'Cambio', 4, NULL, 2, 5, 6, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 17:00:00', '2026-01-27 13:00:00', '2026-01-31 13:07:13'),
	(138, 2, 'TKT-2026-0095', 'Mant. Corr. Tablero', 'Reparar', 5, NULL, 3, 5, 6, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 18:30:00', '2026-01-28 14:30:00', '2026-01-31 13:07:13'),
	(139, 2, 'TKT-2026-0096', 'Mant. Prev. Detección', 'Prueba', 6, NULL, 2, 5, 6, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 19:00:00', '2026-01-29 15:00:00', '2026-01-31 13:07:13'),
	(140, 2, 'TKT-2026-0097', 'Mant. Pred. Acople', 'Análisis', 7, NULL, 2, 5, 6, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 17:30:00', '2026-01-30 13:30:00', '2026-01-31 13:07:13'),
	(141, 2, 'TKT-2026-0098', 'Mant. Corr. Encoder', 'Reemplazar', 4, NULL, 3, 5, 6, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 22:00:00', '2026-01-30 19:00:00', '2026-01-31 13:07:13'),
	(142, 2, 'TKT-2026-0099', 'Mant. Prev. Seguridad', 'Prueba', 5, NULL, 2, 5, 7, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-19 17:00:00', '2026-01-19 13:00:00', '2026-01-31 13:07:13'),
	(143, 2, 'TKT-2026-0100', 'Mant. Corr. Barrera', 'Reparar', 6, NULL, 3, 5, 7, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-21 18:00:00', '2026-01-21 14:00:00', '2026-01-31 13:07:13'),
	(144, 2, 'TKT-2026-0101', 'Mant. Prev. Pulsadores', 'Verificar', 7, NULL, 2, 5, 7, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-23 19:00:00', '2026-01-23 15:00:00', '2026-01-31 13:07:13'),
	(145, 2, 'TKT-2026-0102', 'Mant. Pred. Frenos', 'Análisis', 4, NULL, 2, 5, 7, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 17:00:00', '2026-01-25 13:00:00', '2026-01-31 13:07:13'),
	(146, 2, 'TKT-2026-0103', 'Mant. Corr. Interlock', 'Reparar', 5, NULL, 3, 5, 7, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 19:00:00', '2026-01-26 15:00:00', '2026-01-31 13:07:13'),
	(147, 2, 'TKT-2026-0104', 'Mant. Prev. Sirenas', 'Prueba', 6, NULL, 2, 5, 7, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 17:30:00', '2026-01-28 13:30:00', '2026-01-31 13:07:13'),
	(148, 2, 'TKT-2026-0105', 'Mant. Corr. Relay', 'Reemplazar', 7, NULL, 3, 5, 7, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 18:00:00', '2026-01-29 14:00:00', '2026-01-31 13:07:13'),
	(149, 2, 'TKT-2026-0106', 'Mant. Prev. Señalización', 'Verificar', 4, NULL, 2, 5, 7, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 19:00:00', '2026-01-30 15:00:00', '2026-01-31 13:07:13'),
	(150, 3, 'TKT-2026-0107', 'Procesamiento Nómina', 'Calcular', 3, NULL, 1, 5, 11, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-17 21:00:00', '2026-01-17 13:00:00', '2026-01-31 13:07:13'),
	(151, 3, 'TKT-2026-0108', 'Declaración PDT', 'SUNAT', 4, NULL, 1, 5, 11, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-18 19:00:00', '2026-01-18 14:00:00', '2026-01-31 13:07:13'),
	(152, 3, 'TKT-2026-0109', 'Proceso Selección', 'Reclutamiento', 5, NULL, 2, 5, 11, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 22:00:00', '2026-01-19 13:30:00', '2026-01-31 13:07:13'),
	(153, 3, 'TKT-2026-0110', 'Evaluación', 'Procesar', 6, NULL, 2, 5, 11, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-21 21:00:00', '2026-01-20 14:00:00', '2026-01-31 13:07:13'),
	(154, 3, 'TKT-2026-0111', 'Actualización Legajos', 'Digitalizar', 7, NULL, 2, 5, 11, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 20:00:00', '2026-01-22 15:00:00', '2026-01-31 13:07:13'),
	(155, 3, 'TKT-2026-0112', 'Liquidación', 'Calcular CTS', 3, NULL, 1, 5, 11, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-23 19:00:00', '2026-01-23 13:00:00', '2026-01-31 13:07:13'),
	(156, 3, 'TKT-2026-0113', 'Inducción', 'Capacitación', 4, NULL, 2, 5, 11, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 18:30:00', '2026-01-24 14:30:00', '2026-01-31 13:07:13'),
	(157, 3, 'TKT-2026-0114', 'Renovación', 'Gestionar', 5, NULL, 2, 5, 11, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 20:00:00', '2026-01-25 15:00:00', '2026-01-31 13:07:13'),
	(158, 3, 'TKT-2026-0115', 'Proceso Vacaciones', 'Aprobar', 6, NULL, 2, 5, 11, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 17:30:00', '2026-01-26 13:30:00', '2026-01-31 13:07:13'),
	(159, 3, 'TKT-2026-0116', 'Gestión Seguros', 'Renovar', 7, NULL, 2, 5, 11, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 19:00:00', '2026-01-27 14:00:00', '2026-01-31 13:07:13'),
	(160, 3, 'TKT-2026-0117', 'Capacitación SST', 'Organizar', 3, NULL, 2, 5, 11, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 20:30:00', '2026-01-28 15:30:00', '2026-01-31 13:07:13'),
	(161, 3, 'TKT-2026-0118', 'Proceso Cese', 'Gestionar', 4, NULL, 2, 5, 11, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 18:00:00', '2026-01-29 13:00:00', '2026-01-31 13:07:13'),
	(162, 3, 'TKT-2026-0119', 'Actualización MOF', 'Revisar', 5, NULL, 2, 5, 11, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 21:00:00', '2026-01-30 14:30:00', '2026-01-31 13:07:13'),
	(163, 3, 'TKT-2026-0120', 'Compra Equipos', 'Adquisición', 6, NULL, 2, 5, 12, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-20 22:00:00', '2026-01-18 13:00:00', '2026-01-31 13:07:13'),
	(164, 3, 'TKT-2026-0121', 'Cotización', 'Proformas', 7, NULL, 2, 5, 12, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-19 19:00:00', '2026-01-19 14:00:00', '2026-01-31 13:07:13'),
	(165, 3, 'TKT-2026-0122', 'Orden Compra', 'Emitir', 3, NULL, 2, 5, 12, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-21 20:00:00', '2026-01-21 15:00:00', '2026-01-31 13:07:13'),
	(166, 3, 'TKT-2026-0123', 'Evaluación', 'Calificar', 4, NULL, 2, 5, 12, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-23 18:30:00', '2026-01-23 13:30:00', '2026-01-31 13:07:13'),
	(167, 3, 'TKT-2026-0124', 'Gestión Stock', 'Inventario', 5, NULL, 2, 5, 12, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 21:00:00', '2026-01-24 14:30:00', '2026-01-31 13:07:13'),
	(168, 3, 'TKT-2026-0125', 'Compra Repuestos', 'Emergencia', 6, NULL, 3, 5, 12, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 17:00:00', '2026-01-26 13:00:00', '2026-01-31 13:07:13'),
	(169, 3, 'TKT-2026-0126', 'Negociación', 'Renovar', 7, NULL, 2, 5, 12, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 22:00:00', '2026-01-27 15:00:00', '2026-01-31 13:07:13'),
	(170, 3, 'TKT-2026-0127', 'Control Calidad', 'Inspeccionar', 3, NULL, 2, 5, 12, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 18:00:00', '2026-01-29 14:00:00', '2026-01-31 13:07:13'),
	(171, 3, 'TKT-2026-0128', 'Actualización', 'Revisar', 4, NULL, 2, 5, 12, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 20:30:00', '2026-01-30 15:30:00', '2026-01-31 13:07:13'),
	(172, 3, 'TKT-2026-0129', 'Facturación', 'Emitir', 5, NULL, 1, 5, 13, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-17 20:00:00', '2026-01-17 14:00:00', '2026-01-31 13:07:13'),
	(173, 3, 'TKT-2026-0130', 'Libro Ventas', 'Actualizar', 6, NULL, 2, 5, 13, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-20 18:00:00', '2026-01-20 13:30:00', '2026-01-31 13:07:13'),
	(174, 3, 'TKT-2026-0131', 'Conciliación', 'Cuadrar', 7, NULL, 2, 5, 13, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 19:00:00', '2026-01-22 14:00:00', '2026-01-31 13:07:13'),
	(175, 3, 'TKT-2026-0132', 'Declaración IGV', 'PDT 621', 3, NULL, 1, 5, 13, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 17:00:00', '2026-01-24 13:00:00', '2026-01-31 13:07:13'),
	(176, 3, 'TKT-2026-0133', 'Análisis Cuentas', 'Reporte', 4, NULL, 2, 5, 13, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 20:00:00', '2026-01-26 15:00:00', '2026-01-31 13:07:13'),
	(177, 3, 'TKT-2026-0134', 'Provisión', 'Registrar', 5, NULL, 2, 5, 13, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 19:00:00', '2026-01-28 14:30:00', '2026-01-31 13:07:13'),
	(178, 3, 'TKT-2026-0135', 'Balance', 'Elaborar', 6, NULL, 1, 5, 13, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 21:00:00', '2026-01-30 13:00:00', '2026-01-31 13:07:13'),
	(179, 4, 'TKT-2026-0136', 'Desarrollo Dashboard', 'Panel', 7, NULL, 2, 5, 8, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-19 22:00:00', '2026-01-17 13:00:00', '2026-01-31 13:07:13'),
	(180, 4, 'TKT-2026-0137', 'Software Radar', 'Actualizar', 3, NULL, 1, 5, 8, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-17 22:00:00', '2026-01-17 19:00:00', '2026-01-31 13:07:13'),
	(181, 4, 'TKT-2026-0138', 'Config Linux', 'Instalar', 4, NULL, 2, 5, 8, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-18 20:00:00', '2026-01-18 14:00:00', '2026-01-31 13:07:13'),
	(182, 4, 'TKT-2026-0139', 'Desarrollo IoT', 'Plataforma', 5, NULL, 2, 5, 8, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-21 22:00:00', '2026-01-19 13:00:00', '2026-01-31 13:07:13'),
	(183, 4, 'TKT-2026-0140', 'Soporte FleetCart', 'Resolver', 6, NULL, 3, 5, 8, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-20 18:00:00', '2026-01-20 15:00:00', '2026-01-31 13:07:13'),
	(184, 4, 'TKT-2026-0141', 'Docker', 'Migrar', 7, NULL, 2, 5, 8, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 21:00:00', '2026-01-21 14:00:00', '2026-01-31 13:07:13'),
	(185, 4, 'TKT-2026-0142', 'Desarrollo ESP32', 'Programar', 3, NULL, 2, 5, 8, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 22:00:00', '2026-01-22 13:30:00', '2026-01-31 13:07:13'),
	(186, 4, 'TKT-2026-0143', 'Software Backup', 'Configurar', 4, NULL, 1, 5, 8, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-23 19:00:00', '2026-01-23 15:00:00', '2026-01-31 13:07:13'),
	(187, 4, 'TKT-2026-0144', 'Soporte MySQL', 'Optimizar', 5, NULL, 2, 5, 8, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 20:00:00', '2026-01-24 14:30:00', '2026-01-31 13:07:13'),
	(188, 4, 'TKT-2026-0145', 'Desarrollo API', 'Crear', 6, NULL, 2, 5, 8, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 22:00:00', '2026-01-25 13:00:00', '2026-01-31 13:07:13'),
	(189, 4, 'TKT-2026-0146', 'Config VPN', 'Acceso', 7, NULL, 3, 5, 8, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 18:00:00', '2026-01-26 15:00:00', '2026-01-31 13:07:13'),
	(190, 4, 'TKT-2026-0147', 'Optimización', 'Mejorar', 3, NULL, 1, 5, 8, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 21:00:00', '2026-01-27 14:00:00', '2026-01-31 13:07:13'),
	(191, 4, 'TKT-2026-0148', 'Desarrollo Asistencia', 'Biométrico', 4, NULL, 2, 5, 8, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 22:00:00', '2026-01-28 13:30:00', '2026-01-31 13:07:13'),
	(192, 4, 'TKT-2026-0149', 'Soporte AD', 'Resolver', 5, NULL, 3, 5, 8, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 21:30:00', '2026-01-28 19:00:00', '2026-01-31 13:07:13'),
	(193, 4, 'TKT-2026-0150', 'CI/CD', 'Pipeline', 6, NULL, 2, 5, 8, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 22:00:00', '2026-01-29 13:00:00', '2026-01-31 13:07:13'),
	(194, 4, 'TKT-2026-0151', 'Desarrollo BI', 'Tablero', 7, NULL, 2, 5, 8, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 22:00:00', '2026-01-30 14:00:00', '2026-01-31 13:07:13'),
	(195, 4, 'TKT-2026-0152', 'Interfaz', 'Rediseñar', 3, NULL, 2, 5, 8, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 20:00:00', '2026-01-30 15:00:00', '2026-01-31 13:07:13'),
	(196, 4, 'TKT-2026-0153', 'Firewall', 'Seguridad', 4, NULL, 3, 5, 8, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 19:00:00', '2026-01-30 16:00:00', '2026-01-31 13:07:13'),
	(197, 4, 'TKT-2026-0154', 'Reportes', 'Generador', 5, NULL, 2, 5, 8, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 23:00:00', '2026-01-30 20:00:00', '2026-01-31 13:07:13'),
	(198, 4, 'TKT-2026-0155', 'Inventario', 'Control', 6, NULL, 2, 5, 9, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-19 22:00:00', '2026-01-17 14:00:00', '2026-01-31 13:07:13'),
	(199, 4, 'TKT-2026-0156', 'Soporte Red', 'VLAN', 7, NULL, 3, 5, 9, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-18 19:00:00', '2026-01-18 15:00:00', '2026-01-31 13:07:13'),
	(200, 4, 'TKT-2026-0157', 'App Móvil', 'Android', 3, NULL, 2, 5, 9, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 22:00:00', '2026-01-20 13:00:00', '2026-01-31 13:07:13'),
	(201, 4, 'TKT-2026-0158', 'ERP', 'Resolver', 4, NULL, 3, 5, 9, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-21 19:00:00', '2026-01-21 15:00:00', '2026-01-31 13:07:13'),
	(202, 4, 'TKT-2026-0159', 'Backup', 'Resolver', 5, NULL, 3, 5, 9, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-23 18:00:00', '2026-01-23 14:00:00', '2026-01-31 13:07:13'),
	(203, 4, 'TKT-2026-0160', 'Nómina', 'Módulo', 6, NULL, 2, 5, 9, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 22:00:00', '2026-01-24 13:00:00', '2026-01-31 13:07:13'),
	(204, 4, 'TKT-2026-0161', 'WiFi', 'Ampliar', 7, NULL, 3, 5, 9, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 19:00:00', '2026-01-25 15:00:00', '2026-01-31 13:07:13'),
	(205, 4, 'TKT-2026-0162', 'Portal Web', 'Panel', 3, NULL, 2, 5, 9, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 22:00:00', '2026-01-27 13:30:00', '2026-01-31 13:07:13'),
	(206, 4, 'TKT-2026-0163', 'Ubuntu', 'Actualizar', 4, NULL, 2, 5, 9, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 17:00:00', '2026-01-28 14:00:00', '2026-01-31 13:07:13'),
	(207, 4, 'TKT-2026-0164', 'SCADA', 'Monitoreo', 5, NULL, 2, 5, 9, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 22:00:00', '2026-01-29 13:00:00', '2026-01-31 13:07:13'),
	(208, 4, 'TKT-2026-0165', 'Docker', 'Memoria', 6, NULL, 3, 5, 9, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 21:00:00', '2026-01-29 19:00:00', '2026-01-31 13:07:13'),
	(209, 4, 'TKT-2026-0166', 'Git', 'Gitea', 7, NULL, 2, 5, 9, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 17:00:00', '2026-01-30 13:00:00', '2026-01-31 13:07:13'),
	(210, 4, 'TKT-2026-0167', 'Facturación', 'Electrónica', 3, NULL, 2, 5, 9, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 22:00:00', '2026-01-30 18:00:00', '2026-01-31 13:07:13'),
	(211, 4, 'TKT-2026-0168', 'DNS', 'Configurar', 4, NULL, 3, 5, 9, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 21:00:00', '2026-01-30 19:00:00', '2026-01-31 13:07:13'),
	(212, 4, 'TKT-2026-0169', 'GraphQL', 'API', 5, NULL, 2, 5, 9, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 23:30:00', '2026-01-30 20:30:00', '2026-01-31 13:07:13'),
	(213, 4, 'TKT-2026-0170', 'Helpdesk', 'Sistema', 6, NULL, 1, 5, 10, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-20 22:00:00', '2026-01-17 13:00:00', '2026-01-31 13:07:13'),
	(214, 4, 'TKT-2026-0171', 'Nginx', 'Servidor', 7, NULL, 2, 5, 10, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-19 19:00:00', '2026-01-19 14:00:00', '2026-01-31 13:07:13'),
	(215, 4, 'TKT-2026-0172', 'WhatsApp', 'Integración', 3, NULL, 2, 5, 10, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-23 22:00:00', '2026-01-21 13:00:00', '2026-01-31 13:07:13'),
	(216, 4, 'TKT-2026-0173', 'Zabbix', 'Alertas', 4, NULL, 2, 5, 10, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-22 20:00:00', '2026-01-22 15:00:00', '2026-01-31 13:07:13'),
	(217, 4, 'TKT-2026-0174', 'Backoffice', 'Panel', 5, NULL, 2, 5, 10, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-26 22:00:00', '2026-01-24 13:30:00', '2026-01-31 13:07:13'),
	(218, 4, 'TKT-2026-0175', 'SSL', 'Renovar', 6, NULL, 3, 5, 10, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 16:00:00', '2026-01-25 14:00:00', '2026-01-31 13:07:13'),
	(219, 4, 'TKT-2026-0176', 'Push', 'Notificaciones', 7, NULL, 2, 5, 10, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 22:00:00', '2026-01-27 13:00:00', '2026-01-31 13:07:13'),
	(220, 4, 'TKT-2026-0177', 'Redis', 'Caché', 3, NULL, 2, 5, 10, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 19:00:00', '2026-01-28 15:00:00', '2026-01-31 13:07:13'),
	(221, 4, 'TKT-2026-0178', 'OAuth', 'Login', 4, NULL, 2, 5, 10, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-29 21:30:00', '2026-01-29 13:30:00', '2026-01-31 13:07:13'),
	(222, 4, 'TKT-2026-0179', 'K8s', 'Cluster', 5, NULL, 2, 5, 10, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 22:00:00', '2026-01-30 14:00:00', '2026-01-31 13:07:13'),
	(223, 4, 'TKT-2026-0180', 'Excel', 'Reportes', 6, NULL, 2, 5, 10, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 23:00:00', '2026-01-30 19:00:00', '2026-01-31 13:07:13'),
	(224, 2, 'TKT-2026-0181', 'Mant. Radar', 'Preventivo', 4, NULL, 2, 5, 3, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 19:00:00', '2026-01-24 13:00:00', '2026-01-31 17:14:17'),
	(225, 2, 'TKT-2026-0182', 'Mant. CCTV', 'Limpieza', 5, NULL, 2, 5, 3, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 20:00:00', '2026-01-24 14:00:00', '2026-01-31 17:14:17'),
	(226, 2, 'TKT-2026-0183', 'Mant. Compresor', 'Cambio filtros', 6, NULL, 2, 5, 4, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 21:00:00', '2026-01-24 15:00:00', '2026-01-31 17:14:17'),
	(227, 4, 'TKT-2026-0184', 'Desarrollo API', 'Crear endpoints', 7, NULL, 2, 5, 8, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 22:00:00', '2026-01-24 16:00:00', '2026-01-31 17:14:17'),
	(228, 4, 'TKT-2026-0185', 'Soporte MySQL', 'Optimizar', 3, NULL, 2, 5, 8, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 23:00:00', '2026-01-24 17:00:00', '2026-01-31 17:14:17'),
	(229, 3, 'TKT-2026-0186', 'Gestión Nómina', 'Calcular', 4, NULL, 1, 5, 11, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 22:00:00', '2026-01-24 18:00:00', '2026-01-31 17:14:17'),
	(230, 3, 'TKT-2026-0187', 'Compra Equipos', 'Adquisición', 5, NULL, 2, 5, 12, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-24 23:00:00', '2026-01-24 19:00:00', '2026-01-31 17:14:17'),
	(231, 3, 'TKT-2026-0188', 'Facturación', 'Emitir', 6, NULL, 1, 5, 13, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 00:00:00', '2026-01-24 20:00:00', '2026-01-31 17:14:17'),
	(232, 2, 'TKT-2026-0189', 'Mant. UPS', 'Prueba baterías', 7, NULL, 2, 5, 5, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 01:00:00', '2026-01-24 21:00:00', '2026-01-31 17:14:17'),
	(233, 4, 'TKT-2026-0190', 'Config VPN', 'Acceso remoto', 3, NULL, 3, 5, 9, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-25 02:00:00', '2026-01-24 22:00:00', '2026-01-31 17:14:17'),
	(234, 2, 'TKT-2026-0191', 'Mant. Motor', 'Reparar', 4, NULL, 3, 5, 3, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 19:00:00', '2026-01-27 13:00:00', '2026-01-31 17:14:17'),
	(235, 2, 'TKT-2026-0192', 'Mant. Válvula', 'Reemplazar', 5, NULL, 3, 5, 4, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 20:00:00', '2026-01-27 14:00:00', '2026-01-31 17:14:17'),
	(236, 4, 'TKT-2026-0193', 'Desarrollo BI', 'Tablero', 6, NULL, 2, 5, 8, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 21:00:00', '2026-01-27 15:00:00', '2026-01-31 17:14:17'),
	(237, 4, 'TKT-2026-0194', 'Soporte Red', 'VLAN', 7, NULL, 3, 5, 9, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 22:00:00', '2026-01-27 16:00:00', '2026-01-31 17:14:17'),
	(238, 3, 'TKT-2026-0195', 'Proceso Selección', 'Reclutamiento', 3, NULL, 2, 5, 11, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-27 23:00:00', '2026-01-27 17:00:00', '2026-01-31 17:14:17'),
	(239, 3, 'TKT-2026-0196', 'Cotización', 'Proformas', 4, NULL, 2, 5, 12, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 00:00:00', '2026-01-27 18:00:00', '2026-01-31 17:14:17'),
	(240, 3, 'TKT-2026-0197', 'Conciliación', 'Cuadrar cuentas', 5, NULL, 2, 5, 13, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 01:00:00', '2026-01-27 19:00:00', '2026-01-31 17:14:17'),
	(241, 2, 'TKT-2026-0198', 'Mant. Generador', 'Cambio aceite', 6, NULL, 2, 5, 5, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 02:00:00', '2026-01-27 20:00:00', '2026-01-31 17:14:17'),
	(242, 4, 'TKT-2026-0199', 'Desarrollo Portal', 'Panel', 7, NULL, 2, 5, 10, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 03:00:00', '2026-01-27 21:00:00', '2026-01-31 17:14:17'),
	(243, 4, 'TKT-2026-0200', 'Helpdesk', 'Sistema', 3, NULL, 1, 5, 10, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-28 04:00:00', '2026-01-27 22:00:00', '2026-01-31 17:14:17'),
	(244, 2, 'TKT-2026-0201', 'Mant. Cinta', 'Ajuste', 4, NULL, 2, 5, 3, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 17:00:00', '2026-01-30 13:00:00', '2026-01-31 17:14:17'),
	(245, 2, 'TKT-2026-0202', 'Mant. Sensor', 'Reemplazar', 5, NULL, 3, 5, 4, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 18:00:00', '2026-01-30 14:00:00', '2026-01-31 17:14:17'),
	(246, 4, 'TKT-2026-0203', 'Desarrollo Reportes', 'Generador', 6, NULL, 2, 5, 8, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 19:00:00', '2026-01-30 15:00:00', '2026-01-31 17:14:17'),
	(247, 4, 'TKT-2026-0204', 'Soporte Docker', 'Memoria', 7, NULL, 3, 5, 9, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 20:00:00', '2026-01-30 16:00:00', '2026-01-31 17:14:17'),
	(248, 3, 'TKT-2026-0205', 'Actualización MOF', 'Revisar', 3, NULL, 2, 5, 11, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 21:00:00', '2026-01-30 17:00:00', '2026-01-31 17:14:17'),
	(249, 3, 'TKT-2026-0206', 'Actualización', 'Revisar', 4, NULL, 2, 5, 12, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 22:00:00', '2026-01-30 18:00:00', '2026-01-31 17:14:17'),
	(250, 3, 'TKT-2026-0207', 'Balance', 'Elaborar', 5, NULL, 1, 5, 13, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-30 23:00:00', '2026-01-30 19:00:00', '2026-01-31 17:14:17'),
	(251, 2, 'TKT-2026-0208', 'Mant. Señalización', 'Verificar', 6, NULL, 2, 5, 7, 1, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-31 00:00:00', '2026-01-30 20:00:00', '2026-01-31 17:14:17'),
	(252, 4, 'TKT-2026-0209', 'Desarrollo Excel', 'Reportes', 7, NULL, 2, 5, 10, 2, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-31 01:00:00', '2026-01-30 21:00:00', '2026-01-31 17:14:17'),
	(253, 4, 'TKT-2026-0210', 'Config K8s', 'Cluster', 3, NULL, 2, 5, 10, 3, NULL, NULL, NULL, NULL, NULL, 100, NULL, '2026-01-31 02:00:00', '2026-01-30 22:00:00', '2026-01-31 17:14:17');

-- Volcando estructura para tabla helpdesk_clonsa.tipos_falla
CREATE TABLE IF NOT EXISTS `tipos_falla` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `icono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.tipos_falla: ~6 rows (aproximadamente)
INSERT INTO `tipos_falla` (`id`, `nombre`, `descripcion`, `icono`, `activo`, `created_at`) VALUES
	(1, 'Hardware', 'Fallas físicas', 'mdi-memory', 1, '2026-01-23 16:13:59'),
	(2, 'Software', 'Fallas de software', 'mdi-application', 1, '2026-01-23 16:13:59'),
	(3, 'Energía', 'Problemas eléctricos', 'mdi-flash', 1, '2026-01-23 16:13:59'),
	(4, 'Comunicación', 'Problemas de red', 'mdi-wifi-off', 1, '2026-01-23 16:13:59'),
	(5, 'Configuración', 'Errores de config', 'mdi-cog', 1, '2026-01-23 16:13:59'),
	(6, 'Usuario', 'Error de usuario', 'mdi-account-alert', 1, '2026-01-23 16:13:59');

-- Volcando estructura para tabla helpdesk_clonsa.ubicaciones
CREATE TABLE IF NOT EXISTS `ubicaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.ubicaciones: ~4 rows (aproximadamente)
INSERT INTO `ubicaciones` (`id`, `nombre`, `descripcion`, `activo`, `created_at`) VALUES
	(1, 'UM Bambas', 'Unidad Minera Las Bambas', 1, '2026-01-23 16:13:59'),
	(2, 'UM Yanacocha', 'Unidad Minera Yanacocha', 1, '2026-01-23 16:13:59'),
	(3, 'Oficina Principal', 'Oficina central', 1, '2026-01-23 16:13:59'),
	(4, 'Almacén', 'Almacén', 1, '2026-01-23 16:13:59');

-- Volcando estructura para tabla helpdesk_clonsa.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_completo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol_id` int NOT NULL,
  `departamento_id` int DEFAULT NULL,
  `area_id` int DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'default-avatar.png',
  `activo` tinyint(1) DEFAULT '1',
  `ultimo_acceso` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.usuarios: ~12 rows (aproximadamente)
INSERT INTO `usuarios` (`id`, `username`, `email`, `password`, `nombre_completo`, `telefono`, `rol_id`, `departamento_id`, `area_id`, `avatar`, `activo`, `ultimo_acceso`, `reset_token`, `reset_token_expires`, `created_at`) VALUES
	(1, 'admin', 'admin@clonsa.pe', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Administrador General', NULL, 1, 1, NULL, 'default-avatar.png', 1, '2026-01-31 17:28:56', '748592', '2026-01-23 21:16:36', '2026-01-23 16:13:59'),
	(3, 'acontreras', 'acontreras@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Amador Contreras L.', NULL, 2, 2, NULL, 'default-avatar.png', 1, '2026-01-26 00:11:00', NULL, NULL, '2026-01-25 23:18:44'),
	(4, 'irodriguez', 'irodriguez@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Iván Rodriguez', NULL, 3, 2, NULL, 'default-avatar.png', 1, NULL, NULL, NULL, '2026-01-25 23:18:44'),
	(5, 'fquispe', 'fquispe@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Fernando Quispe', NULL, 3, 2, NULL, 'default-avatar.png', 1, NULL, NULL, NULL, '2026-01-25 23:18:44'),
	(6, 'dlazarte', 'dlazarte@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Daniel Lazarte', NULL, 3, 2, NULL, 'default-avatar.png', 1, '2026-01-25 23:35:40', NULL, NULL, '2026-01-25 23:18:44'),
	(7, 'lruiz', 'lruiz@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Luis Ruiz', NULL, 3, 2, NULL, 'default-avatar.png', 1, '2026-01-25 23:55:41', NULL, NULL, '2026-01-25 23:18:44'),
	(8, 'jtunoque', 'jtunoque@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Jack Tuñoque S.', NULL, 2, 4, NULL, 'default-avatar.png', 1, '2026-01-26 00:10:26', NULL, NULL, '2026-01-25 23:18:44'),
	(9, 'rarias', 'rarias@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Richard Arias', NULL, 3, 4, NULL, 'default-avatar.png', 1, NULL, NULL, NULL, '2026-01-25 23:18:44'),
	(10, 'cmedina', 'cmedina@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Carlos Medina', NULL, 3, 4, NULL, 'default-avatar.png', 1, '2026-01-25 23:35:19', NULL, NULL, '2026-01-25 23:18:44'),
	(11, 'ejimenez', 'ejimenez@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Enzo Jimenez', NULL, 2, 3, NULL, 'default-avatar.png', 1, '2026-01-25 23:35:28', NULL, NULL, '2026-01-25 23:18:44'),
	(12, 'garias', 'garias@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Geraldine Arias', NULL, 3, 3, NULL, 'default-avatar.png', 1, NULL, NULL, NULL, '2026-01-25 23:18:44'),
	(13, 'jagreda', 'jagreda@clonsa.com', '$2y$12$t/z4.pC8wl3ApKuXERP8DeSXzTpl2d39oK2oqGEbJpEodBIUY18ze', 'Jesus Agreda', NULL, 3, 3, NULL, 'default-avatar.png', 1, NULL, NULL, NULL, '2026-01-25 23:18:44');

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
    DECLARE year_val VARCHAR(4);
    SET year_val = YEAR(NOW());
    SELECT COALESCE(MAX(CAST(SUBSTRING(codigo, 10) AS UNSIGNED)), 0) + 1 
    INTO next_num 
    FROM tickets 
    WHERE codigo LIKE CONCAT('TKT-', year_val, '-%');
    SET NEW.codigo = CONCAT('TKT-', year_val, '-', LPAD(next_num, 4, '0'));
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
    IF NEW.progreso = 100 AND OLD.progreso != 100 THEN
        SET NEW.estado_id = 5;
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
