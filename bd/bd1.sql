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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.actividades: ~5 rows (aproximadamente)
INSERT INTO `actividades` (`id`, `nombre`, `descripcion`, `color`, `activo`, `created_at`) VALUES
	(1, 'Mantenimiento Preventivo', 'Mantenimiento programado', '#28a745', 1, '2026-01-23 16:13:59'),
	(2, 'Mantenimiento Correctivo', 'Reparación de fallas', '#dc3545', 1, '2026-01-23 16:13:59'),
	(3, 'Mantenimiento Predictivo', 'Mantenimiento predictivo', '#17a2b8', 1, '2026-01-23 16:13:59'),
	(4, 'Instalación', 'Instalación de equipo', '#ffc107', 1, '2026-01-23 16:13:59'),
	(5, 'Configuración', 'Configuración', '#6f42c1', 1, '2026-01-23 16:13:59');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.historial: ~0 rows (aproximadamente)

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

-- Volcando datos para la tabla helpdesk_clonsa.roles: ~5 rows (aproximadamente)
INSERT INTO `roles` (`id`, `nombre`, `descripcion`, `permisos`, `created_at`, `updated_at`) VALUES
	(1, 'Admin', 'Acceso total al sistema', '{"crear": true, "editar": true, "eliminar": true, "ver_todo": true, "gestionar_usuarios": true}', '2026-01-23 16:13:59', '2026-01-23 16:13:59'),
	(2, 'Jefe Soporte Técnico', 'Jefe del área de Soporte Técnico', '{"crear": true, "editar": true, "asignar": true, "ver_area": true, "gestionar_equipo": true}', '2026-01-23 16:13:59', '2026-01-23 16:13:59'),
	(3, 'Jefe TI', 'Jefe del área de Tecnología', '{"crear": true, "editar": true, "asignar": true, "ver_area": true, "gestionar_equipo": true}', '2026-01-23 16:13:59', '2026-01-23 16:13:59'),
	(4, 'Jefe Administrativo', 'Jefe del área Administrativa', '{"crear": true, "editar": true, "asignar": true, "ver_area": true, "gestionar_equipo": true}', '2026-01-23 16:13:59', '2026-01-23 16:13:59'),
	(5, 'Usuario', 'Usuario final del sistema', '{"crear": true, "ver_propios": true}', '2026-01-23 16:13:59', '2026-01-23 16:13:59');

-- Volcando estructura para tabla helpdesk_clonsa.tickets
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.tickets: ~0 rows (aproximadamente)

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
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `usuarios_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Volcando datos para la tabla helpdesk_clonsa.usuarios: ~1 rows (aproximadamente)
INSERT INTO `usuarios` (`id`, `username`, `email`, `password`, `nombre_completo`, `telefono`, `rol_id`, `area_id`, `avatar`, `activo`, `ultimo_acceso`, `reset_token`, `reset_token_expires`, `created_at`) VALUES
	(1, 'admin', 'admin@clonsa.pe', '$2y$12$nYfR3P/IGBABi28mjrIG9.0h5M8XxVBLz9OLaWapOW.zBZUPdFIoq', 'Administrador', NULL, 1, NULL, 'default-avatar.png', 1, '2026-01-25 21:43:14', '748592', '2026-01-23 21:16:36', '2026-01-23 16:13:59');

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
