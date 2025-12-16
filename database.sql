-- Base de datos para Sistema de Gestión de Salón de Manicura
-- Crear base de datos
CREATE DATABASE IF NOT EXISTS agendanails;
USE agendanails;

-- Tabla de clientas
CREATE TABLE clientas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    telefono VARCHAR(20),
    email VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notas TEXT,
    activa BOOLEAN DEFAULT TRUE
);

-- Tabla de servicios
CREATE TABLE servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    duracion_minutos INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE
);

-- Tabla de citas/agenda
CREATE TABLE citas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    estado ENUM('pendiente', 'confirmada', 'en_proceso', 'completada', 'cancelada') DEFAULT 'pendiente',
    notas TEXT,
    total DECIMAL(10,2) DEFAULT 0,
    abono DECIMAL(10,2) DEFAULT 0,
    saldo_pendiente DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientas(id) ON DELETE CASCADE,
    INDEX idx_fecha (fecha),
    INDEX idx_cliente (cliente_id),
    INDEX idx_fecha_hora (fecha, hora_inicio, hora_fin)
);

-- Tabla de servicios por cita
CREATE TABLE cita_servicios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    servicio_id INT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    notas TEXT,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
    FOREIGN KEY (servicio_id) REFERENCES servicios(id) ON DELETE CASCADE
);

-- Tabla de proveedores
CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    contacto VARCHAR(100),
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de categorías de insumos
CREATE TABLE categorias_insumos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    descripcion TEXT
);

-- Tabla de insumos
CREATE TABLE insumos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    categoria_id INT,
    proveedor_id INT,
    precio_compra DECIMAL(10,2),
    precio_venta DECIMAL(10,2),
    stock_actual INT DEFAULT 0,
    stock_minimo INT DEFAULT 5,
    unidad_medida VARCHAR(20) DEFAULT 'unidad',
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias_insumos(id) ON DELETE SET NULL,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL
);

-- Tabla de movimientos de inventario
CREATE TABLE movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    insumo_id INT NOT NULL,
    tipo ENUM('entrada', 'salida') NOT NULL,
    cantidad INT NOT NULL,
    motivo VARCHAR(100),
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario VARCHAR(50),
    FOREIGN KEY (insumo_id) REFERENCES insumos(id) ON DELETE CASCADE
);

-- Tabla de abonos
CREATE TABLE abonos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cita_id INT NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha_abono TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    metodo_pago ENUM('efectivo', 'transferencia', 'tarjeta') DEFAULT 'efectivo',
    notas TEXT,
    FOREIGN KEY (cita_id) REFERENCES citas(id) ON DELETE CASCADE,
    INDEX idx_cita (cita_id)
);

-- Insertar servicios básicos
INSERT INTO servicios (nombre, descripcion, precio, duracion_minutos) VALUES
('Esmaltado Común', 'Incluye: retiro de esmalte anterior, limado, corte de cutícula, hidratación de manos y aplicación de esmalte común', 15000, 60),
('Esmaltado Semi-permanente', 'Incluye: retiro de esmalte anterior, limado, corte de cutícula, hidratación de manos y aplicación de esmalte semipermanente', 22000, 75),
('Manicura Spa', 'Incluye: retiro de esmalte, limado, corte de cutícula, exfoliación, hidratación profunda de manos y cutículas', 18000, 70),
('Capping/Refuerzo', 'Incluye: retiro de esmalte anterior, limado, corte de cutícula, aplicación de gel reforzador e hidratación', 28000, 90),
('Extensiones de Uñas', 'Incluye: preparación completa de uñas, limado, corte de cutícula, colocación de extensiones, limado de forma e hidratación', 35000, 120),
('Manicura Express', 'Servicio rápido: limado básico, corte de cutícula e hidratación (sin esmalte)', 8000, 30),
('Decoración Nail Art', 'Diseños personalizados sobre base de esmalte (adicional a servicio base)', 8000, 30),
('Reparación de Uña', 'Reparación de uña quebrada o dañada con gel o fibra', 5000, 20);

-- Insertar categorías de insumos
INSERT INTO categorias_insumos (nombre, descripcion) VALUES
('Esmaltes', 'Esmaltes de uñas de diferentes tipos'),
('Herramientas', 'Herramientas para manicura'),
('Tratamientos', 'Productos para tratamiento de uñas'),
('Limpieza', 'Productos de limpieza y desinfección'),
('Accesorios', 'Accesorios varios para manicura');

-- Tabla de usuarios (manicuristas)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'manicurista') DEFAULT 'manicurista',
    activo BOOLEAN DEFAULT TRUE,
    ultimo_acceso TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Usuario administrador por defecto (password: admin123)
-- Primero eliminar si existe para evitar duplicados
DELETE FROM usuarios WHERE username = 'admin';

-- Crear usuario admin con hash correcto
INSERT INTO usuarios (nombre, username, password, rol) VALUES 
('Administrador', 'admin', '$2y$10$3QnTtGGOxGTMrykAJOOHsO8yL0K8u8TtCqMFYVxGaKqO2MhrvuGNq', 'admin');

-- Crear índices adicionales para optimizar consultas
CREATE INDEX idx_citas_fecha_hora ON citas(fecha, hora_inicio);
CREATE INDEX idx_insumos_stock ON insumos(stock_actual, stock_minimo);
CREATE INDEX idx_movimientos_fecha ON movimientos_inventario(fecha);