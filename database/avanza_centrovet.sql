-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: db
-- Tiempo de generación: 30-08-2026 a las 19:57:25
-- Versión del servidor: 8.0.43
-- Versión de PHP: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `avanza_centrovet`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(4, 'Accesorios'),
(2, 'Alimento para gatos'),
(1, 'Alimento para perros'),
(3, 'Higiene'),
(5, 'Juguetes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `mascota_id` int NOT NULL,
  `veterinario_id` int NOT NULL,
  `servicio_id` int NOT NULL,
  `fecha_cita` date NOT NULL,
  `hora_cita` time NOT NULL,
  `motivo` text,
  `observaciones` text,
  `estado` enum('pendiente','confirmada','reprogramada','en_atencion','finalizada','cancelada','no_asistio') DEFAULT 'pendiente',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedido`
--

CREATE TABLE `detalle_pedido` (
  `id` int NOT NULL,
  `pedido_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mascotas`
--

CREATE TABLE `mascotas` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `especie` varchar(60) NOT NULL,
  `raza` varchar(100) DEFAULT NULL,
  `sexo` enum('Macho','Hembra','No especificado') DEFAULT 'No especificado',
  `fecha_nacimiento` date DEFAULT NULL,
  `peso` decimal(6,2) DEFAULT NULL,
  `alergias` varchar(255) DEFAULT NULL,
  `observaciones` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `mascotas`
--

INSERT INTO `mascotas` (`id`, `usuario_id`, `nombre`, `especie`, `raza`, `sexo`, `fecha_nacimiento`, `peso`, `alergias`, `observaciones`) VALUES
(1, 3, 'Luna', 'Perro', 'Mestiza', 'Hembra', NULL, 12.50, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `envio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL,
  `metodo_pago` varchar(50) NOT NULL,
  `direccion_envio` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','procesando','enviado','completado','cancelado') DEFAULT 'pendiente',
  `fecha_pedido` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int NOT NULL,
  `categoria_id` int DEFAULT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text,
  `precio` decimal(10,2) NOT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `stock_minimo` int NOT NULL DEFAULT '5',
  `imagen` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `descripcion`, `precio`, `stock`, `stock_minimo`, `imagen`, `activo`, `fecha_creacion`) VALUES
(1, 1, 'Alimento Premium Adulto Perro 2 kg', 'Alimento seco completo para perros adultos.', 165.00, 20, 5, 'producto_1788118126_6a94846e6e484.png', 1, '2026-08-30 19:23:30'),
(2, 1, 'Alimento Cachorro 2 kg', 'Formula de crecimiento para cachorros.', 175.00, 18, 5, 'producto_1788118114_6a9484626ff3c.png', 1, '2026-08-30 19:23:30'),
(3, 2, 'Alimento Premium Gato Adulto 1.5 kg', 'Alimento seco balanceado para gatos adultos.', 155.00, 16, 5, 'producto_1788118085_6a948445bdebe.png', 1, '2026-08-30 19:23:30'),
(4, 2, 'Alimento Gato Esterilizado 1.5 kg', 'Forrmula para gatos adultos esterilizados.', 170.00, 12, 5, 'producto_1788118096_6a948450c7e89.png', 1, '2026-08-30 19:23:30'),
(5, 3, 'Shampoo suave para mascotas 250 ml', 'Producto de higiene para baño regular.', 55.00, 24, 5, 'producto_1788118053_6a948425d7c10.jpg', 1, '2026-08-30 19:23:30'),
(6, 3, 'Toallitas de limpieza para mascotas', 'Toallitas humedas para higiene diaria.', 38.00, 30, 5, 'producto_1788118020_6a9484044fd1d.jpeg', 1, '2026-08-30 19:23:30'),
(7, 4, 'Correa ajustable mediana', 'Correa resistente de uso diario.', 68.00, 15, 5, 'producto_1788118002_6a9483f297727.jpg', 1, '2026-08-30 19:23:30'),
(8, 4, 'Collar ajustable mediano', 'Collar comodo y regulable.', 48.00, 22, 5, 'producto_1788117989_6a9483e52b8dd.jpeg', 1, '2026-08-30 19:23:30'),
(9, 4, 'Plato antideslizante', 'Plato para alimento o agua.', 45.00, 19, 5, 'producto_1788117969_6a9483d1df49a.jpg', 1, '2026-08-30 19:23:30'),
(10, 5, 'Pelota interactiva', 'Juguete recreativo para mascotas.', 42.00, 25, 5, 'producto_1788117943_6a9483b7e1e13.jpg', 1, '2026-08-30 19:23:30');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` int NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `descripcion` text,
  `precio` decimal(10,2) NOT NULL DEFAULT '0.00',
  `duracion_min` int NOT NULL DEFAULT '30',
  `estado` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `nombre`, `descripcion`, `precio`, `duracion_min`, `estado`) VALUES
(1, 'Consulta general', 'Evaluacion veterinaria general', 125.00, 30, 1),
(2, 'Vacunacion', 'Aplicacion de vacuna segun esquema indicado por el profesional', 100.00, 20, 1),
(3, 'Desparasitacion', 'Servicio veterinario de desparasitacion', 85.00, 20, 1),
(4, 'Grooming', 'Baño, limpieza y arreglo basico', 150.00, 60, 1),
(5, 'Laboratorio', 'Toma y gestion de examenes basicos', 175.00, 30, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('cliente','admin','veterinario') NOT NULL DEFAULT 'cliente',
  `telefono` varchar(30) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT '1',
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `telefono`, `direccion`, `estado`, `fecha_registro`) VALUES
(1, 'Mirka Temaj Admin', 'admin@avanzacentrovet.local', '$2y$12$Eftwcb03GE5eY/psWiqxveiDU9EREZpy.Uu9PRCqgz/ix9UNvRkgm', 'admin', '5555-0101', NULL, 1, '2026-08-30 19:23:30'),
(2, 'Dra. Lesly Temaj', 'vet@avanzacentrovet.local', '$2y$12$Eftwcb03GE5eY/psWiqxveiDU9EREZpy.Uu9PRCqgz/ix9UNvRkgm', 'veterinario', '5555-0102', NULL, 1, '2026-08-30 19:23:30'),
(3, 'Luisa Navarro', 'cliente@avanzacentrovet.local', '$2y$12$Eftwcb03GE5eY/psWiqxveiDU9EREZpy.Uu9PRCqgz/ix9UNvRkgm', 'cliente', '5555-0103', NULL, 1, '2026-08-30 19:23:30'),
(4, 'Carlos Lopez', 'carlos.lopez@gmail.com', '$2y$10$Fg5CDuUwiGB6gx9qvr6nC.cAQ/JThC3FwyBaat8NyFtvn0rfxbJ2K', 'cliente', '+50247264900', '5ta av 4-89 colonia linda vista', 1, '2026-08-30 19:52:06');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `veterinarios`
--

CREATE TABLE `veterinarios` (
  `id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `especialidad` varchar(120) DEFAULT NULL,
  `numero_colegiado` varchar(60) DEFAULT NULL,
  `estado` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `veterinarios`
--

INSERT INTO `veterinarios` (`id`, `usuario_id`, `especialidad`, `numero_colegiado`, `estado`) VALUES
(1, 2, 'Medicina general', 'CV-001', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_vet_fecha_hora` (`veterinario_id`,`fecha_cita`,`hora_cita`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `mascota_id` (`mascota_id`),
  ADD KEY `servicio_id` (`servicio_id`);

--
-- Indices de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pedido_id` (`pedido_id`),
  ADD KEY `producto_id` (`producto_id`);

--
-- Indices de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `veterinarios`
--
ALTER TABLE `veterinarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_id` (`usuario_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `mascotas`
--
ALTER TABLE `mascotas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `veterinarios`
--
ALTER TABLE `veterinarios`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`mascota_id`) REFERENCES `mascotas` (`id`),
  ADD CONSTRAINT `citas_ibfk_3` FOREIGN KEY (`veterinario_id`) REFERENCES `veterinarios` (`id`),
  ADD CONSTRAINT `citas_ibfk_4` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`);

--
-- Filtros para la tabla `detalle_pedido`
--
ALTER TABLE `detalle_pedido`
  ADD CONSTRAINT `detalle_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detalle_pedido_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `mascotas`
--
ALTER TABLE `mascotas`
  ADD CONSTRAINT `mascotas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `veterinarios`
--
ALTER TABLE `veterinarios`
  ADD CONSTRAINT `veterinarios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
