CREATE DATABASE IF NOT EXISTS avanza_centrovet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE avanza_centrovet;

CREATE TABLE usuarios (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(120) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 rol ENUM('cliente','admin','veterinario') NOT NULL DEFAULT 'cliente',
 telefono VARCHAR(30), direccion VARCHAR(255), estado TINYINT(1) DEFAULT 1,
 fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE mascotas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 nombre VARCHAR(100) NOT NULL,
 especie VARCHAR(60) NOT NULL,
 raza VARCHAR(100), sexo ENUM('Macho','Hembra','No especificado') DEFAULT 'No especificado',
 fecha_nacimiento DATE NULL, peso DECIMAL(6,2) NULL, alergias VARCHAR(255), observaciones TEXT,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE veterinarios (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL UNIQUE,
 especialidad VARCHAR(120), numero_colegiado VARCHAR(60), estado TINYINT(1) DEFAULT 1,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

CREATE TABLE servicios (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(120) NOT NULL,
 descripcion TEXT,
 precio DECIMAL(10,2) NOT NULL DEFAULT 0,
 duracion_min INT NOT NULL DEFAULT 30,
 estado TINYINT(1) DEFAULT 1
);

CREATE TABLE citas (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 mascota_id INT NOT NULL,
 veterinario_id INT NOT NULL,
 servicio_id INT NOT NULL,
 fecha_cita DATE NOT NULL,
 hora_cita TIME NOT NULL,
 motivo TEXT,
 observaciones TEXT,
 estado ENUM('pendiente','confirmada','reprogramada','en_atencion','finalizada','cancelada','no_asistio') DEFAULT 'pendiente',
 fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_vet_fecha_hora (veterinario_id, fecha_cita, hora_cita),
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
 FOREIGN KEY (mascota_id) REFERENCES mascotas(id),
 FOREIGN KEY (veterinario_id) REFERENCES veterinarios(id),
 FOREIGN KEY (servicio_id) REFERENCES servicios(id)
);

CREATE TABLE categorias (
 id INT AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE productos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 categoria_id INT NULL,
 nombre VARCHAR(150) NOT NULL,
 descripcion TEXT,
 precio DECIMAL(10,2) NOT NULL,
 stock INT NOT NULL DEFAULT 0,
 stock_minimo INT NOT NULL DEFAULT 5,
 imagen VARCHAR(255), activo TINYINT(1) DEFAULT 1,
 fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

CREATE TABLE pedidos (
 id INT AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT NOT NULL,
 subtotal DECIMAL(10,2) NOT NULL,
 envio DECIMAL(10,2) NOT NULL DEFAULT 0,
 total DECIMAL(10,2) NOT NULL,
 metodo_pago VARCHAR(50) NOT NULL,
 direccion_envio VARCHAR(255),
 estado ENUM('pendiente','procesando','enviado','completado','cancelado') DEFAULT 'pendiente',
 fecha_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

CREATE TABLE detalle_pedido (
 id INT AUTO_INCREMENT PRIMARY KEY,
 pedido_id INT NOT NULL,
 producto_id INT NOT NULL,
 cantidad INT NOT NULL,
 precio_unitario DECIMAL(10,2) NOT NULL,
 subtotal DECIMAL(10,2) NOT NULL,
 FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
 FOREIGN KEY (producto_id) REFERENCES productos(id)
);

INSERT INTO usuarios (nombre,email,password,rol,telefono) VALUES
('Administrador Avanza','admin@avanzacentrovet.local','$2y$12$Eftwcb03GE5eY/psWiqxveiDU9EREZpy.Uu9PRCqgz/ix9UNvRkgm','admin','5555-0101'),
('Dra. Andrea Vet','vet@avanzacentrovet.local','$2y$12$Eftwcb03GE5eY/psWiqxveiDU9EREZpy.Uu9PRCqgz/ix9UNvRkgm','veterinario','5555-0102'),
('Cliente Demo','cliente@avanzacentrovet.local','$2y$12$Eftwcb03GE5eY/psWiqxveiDU9EREZpy.Uu9PRCqgz/ix9UNvRkgm','cliente','5555-0103');
INSERT INTO veterinarios (usuario_id,especialidad,numero_colegiado) VALUES (2,'Medicina general','CV-001');
INSERT INTO mascotas (usuario_id,nombre,especie,raza,sexo,peso) VALUES (3,'Luna','Perro','Mestiza','Hembra',12.50);
INSERT INTO servicios (nombre,descripcion,precio,duracion_min) VALUES
('Consulta general','Evaluación veterinaria general',125.00,30),
('Vacunación','Aplicación de vacuna según esquema indicado por el profesional',100.00,20),
('Desparasitación','Servicio veterinario de desparasitación',85.00,20),
('Grooming','Baño, limpieza y arreglo básico',150.00,60),
('Laboratorio','Toma y gestión de exámenes básicos',175.00,30);
INSERT INTO categorias (nombre) VALUES ('Alimento para perros'),('Alimento para gatos'),('Higiene'),('Accesorios'),('Juguetes');
INSERT INTO productos (categoria_id,nombre,descripcion,precio,stock,imagen) VALUES
(1,'Alimento Premium Adulto Perro 2 kg','Alimento seco completo para perros adultos.',165.00,20,'alimento_perro_adulto.svg'),
(1,'Alimento Cachorro 2 kg','Fórmula de crecimiento para cachorros.',175.00,18,'alimento_cachorro.svg'),
(2,'Alimento Premium Gato Adulto 1.5 kg','Alimento seco balanceado para gatos adultos.',155.00,16,'alimento_gato_adulto.svg'),
(2,'Alimento Gato Esterilizado 1.5 kg','Fórmula para gatos adultos esterilizados.',170.00,12,'alimento_gato_esterilizado.svg'),
(3,'Shampoo suave para mascotas 250 ml','Producto de higiene para baño regular.',55.00,24,'shampoo_mascotas.svg'),
(3,'Toallitas de limpieza para mascotas','Toallitas húmedas para higiene diaria.',38.00,30,'toallitas_mascotas.svg'),
(4,'Correa ajustable mediana','Correa resistente de uso diario.',68.00,15,'correa_ajustable.svg'),
(4,'Collar ajustable mediano','Collar cómodo y regulable.',48.00,22,'collar_ajustable.svg'),
(4,'Plato antideslizante','Plato para alimento o agua.',45.00,19,'plato_antideslizante.svg'),
(5,'Pelota interactiva','Juguete recreativo para mascotas.',42.00,25,'pelota_interactiva.svg');
