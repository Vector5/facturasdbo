-- =====================================================
-- Sistema de Facturación - Bodega de Almacenes
-- Base de datos MySQL
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------
-- Tabla: administradores
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `administradores` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(100) NOT NULL,
    `pin_hash` VARCHAR(255) NOT NULL,
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla: facturas
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `facturas` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `numero_factura` VARCHAR(20) NOT NULL,
    `fecha` DATE NOT NULL,
    `nombre_cliente` VARCHAR(200) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `telefono` VARCHAR(30) DEFAULT NULL,
    `numero_bodega` VARCHAR(20) NOT NULL,
    `periodo_facturado` VARCHAR(100) NOT NULL,
    `valor` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
    `observaciones` TEXT DEFAULT NULL,
    `estado` ENUM('pendiente', 'pagada', 'anulada', 'vencida') NOT NULL DEFAULT 'pendiente',
    `pdf_generado` TINYINT(1) NOT NULL DEFAULT 0,
    `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_numero_factura` (`numero_factura`),
    KEY `idx_estado` (`estado`),
    KEY `idx_fecha` (`fecha`),
    KEY `idx_cliente` (`nombre_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Tabla: accesos
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `accesos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `fecha_hora` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `direccion_ip` VARCHAR(45) NOT NULL,
    `resultado` ENUM('exitoso', 'fallido') NOT NULL,
    `navegador` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_ip_fecha` (`direccion_ip`, `fecha_hora`),
    KEY `idx_resultado` (`resultado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- Insertar administrador por defecto (PIN: 1234)
-- -----------------------------------------------------
INSERT INTO `administradores` (`nombre`, `pin_hash`, `fecha_creacion`) VALUES
('Administrador', '$2y$12$0OorEVdmDUyM1yLZx32VE.eUCnOkaHsKUJ4CLicgvtremcKUKGSzC', NOW());

SET FOREIGN_KEY_CHECKS = 1;
