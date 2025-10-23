-- Crear la base de datos
CREATE DATABASE omega_cars;
USE omega_cars;

-- Tabla de usuarios
CREATE TABLE usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  contraseña VARCHAR(255) NOT NULL,
  pregunta_seguridad_1 TEXT NOT NULL,
  respuesta_1 VARCHAR(255) NOT NULL,
  pregunta_seguridad_2 TEXT NOT NULL,
  respuesta_2 VARCHAR(255) NOT NULL,
  pregunta_seguridad_3 TEXT NOT NULL,
  respuesta_3 VARCHAR(255) NOT NULL
);

-- Tabla de proveedores
CREATE TABLE proveedores (
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

-- Tabla de productos
CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  marca VARCHAR(100) NOT NULL,
  descripcion TEXT NOT NULL,
  precio DECIMAL(10,2) NOT NULL,
  precio_descuento DECIMAL(10,2) DEFAULT NULL,
  categoria VARCHAR(100),
  stock INT DEFAULT 0,
  imagen VARCHAR(255) DEFAULT NULL,
  origen ENUM('comprado', 'fabricado') DEFAULT 'comprado',
  proveedor_id INT DEFAULT NULL,
  FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL
);

-- Tabla de ventas (encabezado)
CREATE TABLE ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  cliente_nombre VARCHAR(100) NOT NULL,
  cliente_email VARCHAR(100) NOT NULL,
  cliente_telefono VARCHAR(20) NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de detalle de ventas (productos de cada venta)
CREATE TABLE detalle_ventas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  venta_id INT NOT NULL,
  producto_id INT NOT NULL,
  cantidad INT NOT NULL,
  precio_unitario DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
  FOREIGN KEY (producto_id) REFERENCES productos(id)
);
