-- Script para agregar soporte de imágenes a productos
USE omega_cars;

-- Agregar columna para imagen
ALTER TABLE productos
ADD COLUMN imagen VARCHAR(255) DEFAULT NULL AFTER stock;

-- Crear directorio de imágenes (esto se hará desde PHP)
-- Este script solo actualiza la base de datos
