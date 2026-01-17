-- ============================================================================
-- ISO 27001 COMPLIANCE PLATFORM - DATABASE SCHEMA
-- Version: 2.0
-- Normalization: 3NF
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- TABLA: empresas
-- Propósito: Datos de organizaciones (multi-tenant root)
-- ============================================================================
CREATE TABLE IF NOT EXISTS `empresas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(255) NOT NULL,
  `ruc` VARCHAR(11) NOT NULL,
  `sector` VARCHAR(100) NULL,
  `telefono` VARCHAR(20) NULL,
  `email` VARCHAR(255) NULL,
  `direccion` TEXT NULL,
  `metadata` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_empresas_ruc` (`ruc`),
  KEY `idx_empresas_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: usuarios
-- Propósito: Usuarios del sistema por empresa
-- ============================================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol` ENUM('super_admin', 'admin_empresa', 'auditor', 'consultor') NOT NULL DEFAULT 'consultor',
  `estado` ENUM('activo', 'inactivo', 'bloqueado') NOT NULL DEFAULT 'activo',
  `ultimo_acceso` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_email_empresa` (`email`, `empresa_id`),
  KEY `idx_usuarios_empresa` (`empresa_id`),
  KEY `idx_usuarios_estado` (`estado`),
  KEY `idx_usuarios_rol` (`rol`),
  CONSTRAINT `fk_usuarios_empresa` FOREIGN KEY (`empresa_id`) 
    REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: controles_dominio
-- Propósito: 4 dominios de ISO 27001:2022
-- ============================================================================
CREATE TABLE IF NOT EXISTS `controles_dominio` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(10) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dominio_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: controles
-- Propósito: 93 controles de ISO 27001:2022 Anexo A
-- ============================================================================
CREATE TABLE IF NOT EXISTS `controles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `dominio_id` INT UNSIGNED NOT NULL,
  `codigo` VARCHAR(10) NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `objetivo` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_controles_codigo` (`codigo`),
  KEY `idx_controles_dominio` (`dominio_id`),
  CONSTRAINT `fk_controles_dominio` FOREIGN KEY (`dominio_id`) 
    REFERENCES `controles_dominio` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: soa_entries
-- Propósito: SOA (Statement of Applicability) por empresa
-- ============================================================================
CREATE TABLE IF NOT EXISTS `soa_entries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `control_id` INT UNSIGNED NOT NULL,
  `aplicable` TINYINT(1) NOT NULL DEFAULT 1,
  `estado` ENUM('no_implementado', 'parcial', 'implementado') NOT NULL DEFAULT 'no_implementado',
  `justificacion_no_aplicable` TEXT NULL,
  `fecha_evaluacion` TIMESTAMP NULL,
  `evaluado_por` INT UNSIGNED NULL,
  `notas` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_soa_empresa_control` (`empresa_id`, `control_id`),
  KEY `idx_soa_empresa` (`empresa_id`),
  KEY `idx_soa_control` (`control_id`),
  KEY `idx_soa_estado` (`estado`),
  KEY `idx_soa_aplicable` (`aplicable`),
  CONSTRAINT `fk_soa_empresa` FOREIGN KEY (`empresa_id`) 
    REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_soa_control` FOREIGN KEY (`control_id`) 
    REFERENCES `controles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_soa_evaluador` FOREIGN KEY (`evaluado_por`) 
    REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: gap_items
-- Propósito: Brechas identificadas en controles
-- ============================================================================
CREATE TABLE IF NOT EXISTS `gap_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `soa_id` INT UNSIGNED NOT NULL,
  `brecha` TEXT NOT NULL,
  `objetivo` TEXT NOT NULL,
  `prioridad` ENUM('alta', 'media', 'baja') NOT NULL DEFAULT 'media',
  `responsable` VARCHAR(255) NULL,
  `fecha_compromiso` DATE NULL,
  `fecha_real_cierre` DATE NULL,
  `estado_gap` ENUM('activo', 'cerrado', 'eliminado') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gap_soa` (`soa_id`),
  KEY `idx_gap_prioridad` (`prioridad`),
  KEY `idx_gap_estado` (`estado_gap`),
  KEY `idx_gap_fecha_compromiso` (`fecha_compromiso`),
  FULLTEXT KEY `ft_gap_brecha` (`brecha`),
  CONSTRAINT `fk_gap_soa` FOREIGN KEY (`soa_id`) 
    REFERENCES `soa_entries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: acciones
-- Propósito: Acciones correctivas para cerrar GAPs
-- ============================================================================
CREATE TABLE IF NOT EXISTS `acciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `gap_id` INT UNSIGNED NOT NULL,
  `descripcion` TEXT NOT NULL,
  `responsable` VARCHAR(255) NULL,
  `fecha_compromiso` DATE NULL,
  `fecha_completado` DATE NULL,
  `estado` ENUM('pendiente', 'en_progreso', 'completada') NOT NULL DEFAULT 'pendiente',
  `estado_accion` ENUM('activo', 'eliminada') NOT NULL DEFAULT 'activo',
  `notas` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_acciones_gap` (`gap_id`),
  KEY `idx_acciones_estado` (`estado`),
  KEY `idx_acciones_estado_accion` (`estado_accion`),
  KEY `idx_acciones_fecha` (`fecha_compromiso`),
  CONSTRAINT `fk_acciones_gap` FOREIGN KEY (`gap_id`) 
    REFERENCES `gap_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: evidencias
-- Propósito: Documentos que demuestran implementación de controles
-- ============================================================================
CREATE TABLE IF NOT EXISTS `evidencias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `control_id` INT UNSIGNED NOT NULL,
  `tipo` VARCHAR(100) NOT NULL,
  `descripcion` TEXT NOT NULL,
  `archivo` VARCHAR(255) NOT NULL,
  `nombre_original` VARCHAR(255) NOT NULL,
  `ruta` VARCHAR(500) NOT NULL,
  `tamanio` INT UNSIGNED NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `hash` VARCHAR(64) NOT NULL,
  `estado_validacion` ENUM('pendiente', 'aprobada', 'rechazada') NOT NULL DEFAULT 'pendiente',
  `validado_por` INT UNSIGNED NULL,
  `fecha_validacion` TIMESTAMP NULL,
  `comentarios_validacion` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_evidencias_empresa` (`empresa_id`),
  KEY `idx_evidencias_control` (`control_id`),
  KEY `idx_evidencias_estado` (`estado_validacion`),
  KEY `idx_evidencias_hash` (`hash`),
  CONSTRAINT `fk_evidencias_empresa` FOREIGN KEY (`empresa_id`) 
    REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_evidencias_control` FOREIGN KEY (`control_id`) 
    REFERENCES `controles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_evidencias_validador` FOREIGN KEY (`validado_por`) 
    REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: requerimientos_base
-- Propósito: 7 documentos maestros obligatorios ISO 27001
-- ============================================================================
CREATE TABLE IF NOT EXISTS `requerimientos_base` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero` INT UNSIGNED NOT NULL,
  `identificador` VARCHAR(50) NOT NULL,
  `descripcion` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_req_numero` (`numero`),
  UNIQUE KEY `uk_req_identificador` (`identificador`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: empresa_requerimientos
-- Propósito: Estado de requerimientos por empresa
-- ============================================================================
CREATE TABLE IF NOT EXISTS `empresa_requerimientos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NOT NULL,
  `requerimiento_id` INT UNSIGNED NOT NULL,
  `estado` ENUM('pendiente', 'en_proceso', 'completado') NOT NULL DEFAULT 'pendiente',
  `fecha_completado` TIMESTAMP NULL,
  `observaciones` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_empresa_req` (`empresa_id`, `requerimiento_id`),
  KEY `idx_empresa_req_empresa` (`empresa_id`),
  KEY `idx_empresa_req_estado` (`estado`),
  CONSTRAINT `fk_empresa_req_empresa` FOREIGN KEY (`empresa_id`) 
    REFERENCES `empresas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_empresa_req_requerimiento` FOREIGN KEY (`requerimiento_id`) 
    REFERENCES `requerimientos_base` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: requerimientos_controles
-- Propósito: Relación M:N entre requerimientos y controles
-- ============================================================================
CREATE TABLE IF NOT EXISTS `requerimientos_controles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `requerimiento_base_id` INT UNSIGNED NOT NULL,
  `control_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_req_control` (`requerimiento_base_id`, `control_id`),
  KEY `idx_req_controles_req` (`requerimiento_base_id`),
  KEY `idx_req_controles_control` (`control_id`),
  CONSTRAINT `fk_req_controles_req` FOREIGN KEY (`requerimiento_base_id`) 
    REFERENCES `requerimientos_base` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_req_controles_control` FOREIGN KEY (`control_id`) 
    REFERENCES `controles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- TABLA: audit_logs
-- Propósito: Auditoría completa de cambios
-- ============================================================================
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `empresa_id` INT UNSIGNED NULL,
  `usuario_id` INT UNSIGNED NULL,
  `tabla` VARCHAR(100) NOT NULL,
  `accion` ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
  `registro_id` INT UNSIGNED NOT NULL,
  `datos_previos` JSON NULL,
  `datos_nuevos` JSON NULL,
  `ip` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(500) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_empresa` (`empresa_id`),
  KEY `idx_audit_usuario` (`usuario_id`),
  KEY `idx_audit_tabla` (`tabla`),
  KEY `idx_audit_accion` (`accion`),
  KEY `idx_audit_fecha` (`created_at`),
  CONSTRAINT `fk_audit_empresa` FOREIGN KEY (`empresa_id`) 
    REFERENCES `empresas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_audit_usuario` FOREIGN KEY (`usuario_id`) 
    REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
