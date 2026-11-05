-- migracion_cliente.sql · Agrega la columna cliente a una base `bodega` ya creada.
-- Ejecutar solo si la base se creó con una versión de bodega.sql sin esta columna.
USE bodega;
ALTER TABLE venta ADD COLUMN cliente VARCHAR(100) NOT NULL DEFAULT '' AFTER fecha;
