-- Script de actualización para agregar el módulo de proveedores
-- Ejecutar este script en la base de datos omega_cars existente

USE omega_cars;

-- Crear tabla de proveedores
CREATE TABLE IF NOT EXISTS proveedores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  empresa VARCHAR(100),
  email VARCHAR(100),
  telefono VARCHAR(20),
  direccion TEXT,
  pais VARCHAR(50),
  notas TEXT,
  fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agregar columnas a la tabla de productos si no existen
ALTER TABLE productos
ADD COLUMN IF NOT EXISTS origen ENUM('comprado', 'fabricado') DEFAULT 'comprado',
ADD COLUMN IF NOT EXISTS proveedor_id INT DEFAULT NULL;

-- Agregar clave foránea si no existe
-- Nota: En MySQL, necesitamos verificar si la clave foránea existe antes de agregarla
-- Esta es una versión simplificada, ajusta según tu versión de MySQL

SET @constraint_exists = (
  SELECT COUNT(*)
  FROM information_schema.TABLE_CONSTRAINTS
  WHERE CONSTRAINT_SCHEMA = 'omega_cars'
  AND TABLE_NAME = 'productos'
  AND CONSTRAINT_NAME = 'productos_ibfk_1'
);

SET @sql = IF(@constraint_exists = 0,
  'ALTER TABLE productos ADD CONSTRAINT productos_ibfk_1 FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL',
  'SELECT "La clave foránea ya existe" AS mensaje'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Mensaje de confirmación
SELECT 'Módulo de proveedores instalado correctamente' AS resultado;
