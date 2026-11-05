-- bodega.sql · Esquema y datos de prueba para la bodega «Doña Rosa»
-- MySQL 8 (XAMPP) · PHP 8 · sin procedimientos almacenados ni triggers

CREATE DATABASE IF NOT EXISTS bodega
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE bodega;

-- ────────────────────────────────────────────
-- Tablas (tipos exactos del design.md)
-- ────────────────────────────────────────────

CREATE TABLE producto (
    id     INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    stock  INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_producto_nombre (nombre),
    CONSTRAINT chk_producto_precio CHECK (precio > 0),
    CONSTRAINT chk_producto_stock  CHECK (stock >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE venta (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    fecha     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cliente   VARCHAR(100) NOT NULL DEFAULT '',
    subtotal  DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total     DECIMAL(10,2) NOT NULL,
    CONSTRAINT chk_venta_descuento CHECK (descuento >= 0),
    CONSTRAINT chk_venta_total     CHECK (total = subtotal - descuento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE detalle_venta (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    venta_id        INT NOT NULL,
    producto_id     INT NOT NULL,
    cantidad        INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_detalle_venta    FOREIGN KEY (venta_id)    REFERENCES venta(id),
    CONSTRAINT fk_detalle_producto FOREIGN KEY (producto_id) REFERENCES producto(id),
    CONSTRAINT chk_detalle_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ────────────────────────────────────────────
-- Datos de prueba · 6 productos de bodega peruana
-- Precios entre S/ 2,50 y S/ 12,00 · stock entre 0 y 50
-- ────────────────────────────────────────────

INSERT INTO producto (nombre, precio, stock) VALUES
    ('Arroz Costeño',    2.50, 50),
    ('Aceite Vegetal',   12.00, 30),
    ('Leche Gloria',      8.50, 25),
    ('Azúcar Rubia',      5.00, 0),   -- stock 0: producto agotado
    ('Atún Florida',      6.50, 40),
    ('Gaseosa Inca Kola', 11.00, 20);
