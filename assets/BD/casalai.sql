-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-11-2025 a las 03:04:14
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `casalai`
--
CREATE DATABASE IF NOT EXISTS `casalai` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `casalai`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_cartucho_de_tinta`
--

CREATE TABLE `cat_cartucho_de_tinta` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `numero` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_cartucho_de_tinta`
--

INSERT INTO `cat_cartucho_de_tinta` (`id`, `id_producto`, `numero`, `color`, `capacidad`) VALUES
(1, 34, 1004, 'Multicolor', 1000),
(2, 35, 1005, 'Multicolor', 1000),
(3, 36, 1006, 'Multicolor', 1500);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_impresoras`
--

CREATE TABLE `cat_impresoras` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `peso` float DEFAULT NULL,
  `alto` float DEFAULT NULL,
  `ancho` float DEFAULT NULL,
  `largo` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_impresoras`
--

INSERT INTO `cat_impresoras` (`id`, `id_producto`, `peso`, `alto`, `ancho`, `largo`) VALUES
(1, 28, 10, 10, 10, 10),
(2, 29, 20, 20, 20, 20),
(3, 30, 30, 15, 15, 15);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_otros`
--

CREATE TABLE `cat_otros` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_otros`
--

INSERT INTO `cat_otros` (`id`, `id_producto`, `descripcion`) VALUES
(1, 40, NULL),
(2, 41, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_protector_de_voltaje`
--

CREATE TABLE `cat_protector_de_voltaje` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `voltaje_de_entrada` varchar(50) DEFAULT NULL,
  `voltaje_de_salida` varchar(50) DEFAULT NULL,
  `tomas` int(11) DEFAULT NULL,
  `capacidad` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_protector_de_voltaje`
--

INSERT INTO `cat_protector_de_voltaje` (`id`, `id_producto`, `voltaje_de_entrada`, `voltaje_de_salida`, `tomas`, `capacidad`) VALUES
(1, 37, '1200W', '800W', 3, 3),
(2, 38, '1500W', '1000W', 1, 5),
(3, 39, '3200W', '1800W', 6, 12);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cat_tintas`
--

CREATE TABLE `cat_tintas` (
  `id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `numero` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `volumen` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `cat_tintas`
--

INSERT INTO `cat_tintas` (`id`, `id_producto`, `numero`, `color`, `tipo`, `volumen`) VALUES
(1, 31, 1001, 'Multicolor', 'Liquidas', 100),
(2, 32, 1002, 'Multicolor', 'Liquidas', 450),
(3, 33, 1003, 'Multicolor', 'Inyeccion', 750);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `dolar_cache`
--

CREATE TABLE `dolar_cache` (
  `id` int(11) NOT NULL,
  `precio` decimal(10,4) NOT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `dolar_cache`
--

INSERT INTO `dolar_cache` (`id`, `precio`, `fecha`) VALUES
(7079, 226.1305, '2025-11-04 22:02:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_carrito`
--

CREATE TABLE `tbl_carrito` (
  `id_carrito` int(11) NOT NULL,
  `id_cliente` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_carrito`
--

INSERT INTO `tbl_carrito` (`id_carrito`, `id_cliente`, `fecha_creacion`) VALUES
(10, 11, '2025-07-11 16:50:50'),
(11, 3, '2025-09-02 01:21:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_carritodetalle`
--

CREATE TABLE `tbl_carritodetalle` (
  `id_carrito_detalle` int(11) NOT NULL,
  `id_carrito` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `estatus` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_carritodetalle`
--

INSERT INTO `tbl_carritodetalle` (`id_carrito_detalle`, `id_carrito`, `id_producto`, `cantidad`, `estatus`) VALUES
(43, 11, 34, 1, 'pendiente'),
(44, 11, 40, 1, 'pendiente'),
(45, 11, 32, 1, 'pendiente'),
(46, 11, 41, 1, 'pendiente'),
(47, 11, 30, 1, 'pendiente'),
(55, 10, 32, 1, 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_categoria`
--

CREATE TABLE `tbl_categoria` (
  `id_categoria` int(2) NOT NULL,
  `nombre_categoria` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_categoria`
--

INSERT INTO `tbl_categoria` (`id_categoria`, `nombre_categoria`) VALUES
(11, 'Impresoras'),
(12, 'Tintas'),
(13, 'Cartucho de Tinta'),
(14, 'Protector de Voltaje'),
(15, 'Otros');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_clientes`
--

CREATE TABLE `tbl_clientes` (
  `id_clientes` int(11) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `cedula` varchar(10) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `correo` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_clientes`
--

INSERT INTO `tbl_clientes` (`id_clientes`, `nombre`, `cedula`, `direccion`, `telefono`, `correo`, `activo`) VALUES
(10, 'Gabriel Mujica', '29958676', 'mi casa', '0424-678-8765', 'fhhggjjkkkj@gmail.com', 1),
(11, 'Edith Urdaneta', '10844463', 'Los Horcones', '0416-747-4336', 'urdavedith.pnfi@gmail.com', 1),
(12, 'Diego Lopez', '31766917', 'Venezuela estado Zulia\r\nMaracaibo', '0414-575-3363', 'diego0510lopez@gmail.com', 1),
(13, 'Diego Lopez', '5322432', '', '0414-575-3363', 'diego0510lopez@gmail.com', 1),
(14, 'Juan Lai', '25874668', '', '0412-125-6985', 'juanlai@gmail.com', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_combo`
--

CREATE TABLE `tbl_combo` (
  `id_combo` int(11) NOT NULL,
  `nombre_combo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_combo`
--

INSERT INTO `tbl_combo` (`id_combo`, `nombre_combo`, `descripcion`, `fecha_creacion`, `activo`) VALUES
(14, 'COMBO ANIVERSARIO', 'TODO PARA IMPRIMIR', '2025-09-02 01:59:53', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_combo_detalle`
--

CREATE TABLE `tbl_combo_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_combo` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_combo_detalle`
--

INSERT INTO `tbl_combo_detalle` (`id_detalle`, `id_combo`, `id_producto`, `cantidad`) VALUES
(15, 14, 32, 1),
(16, 14, 41, 1),
(17, 14, 30, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_cuentas`
--

CREATE TABLE `tbl_cuentas` (
  `id_cuenta` int(11) NOT NULL,
  `nombre_banco` varchar(20) NOT NULL,
  `numero_cuenta` varchar(25) DEFAULT NULL,
  `rif_cuenta` varchar(15) NOT NULL,
  `telefono_cuenta` varchar(15) DEFAULT NULL,
  `correo_cuenta` varchar(50) DEFAULT NULL,
  `metodos` set('Pago Movil','Transferencia','Zelle','Efectivo','Efectivo $') NOT NULL,
  `estado` enum('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_cuentas`
--

INSERT INTO `tbl_cuentas` (`id_cuenta`, `nombre_banco`, `numero_cuenta`, `rif_cuenta`, `telefono_cuenta`, `correo_cuenta`, `metodos`, `estado`) VALUES
(0, 'Caja en Bs', NULL, 'J406452157', NULL, NULL, 'Efectivo', 'habilitado'),
(1, 'Caja en $', NULL, 'J406452157', NULL, NULL, 'Efectivo $', 'habilitado'),
(8, 'Banesco', '1234567890', '0123456789', '0990812808', 'ejemplo@gmail.com', 'Transferencia', 'habilitado'),
(9, 'Bancamiga', '1234-5678-90-5857575765', 'J-01234567-8', '0990-812-8088', 'ejemplo@gmail.com68', 'Pago Movil', 'habilitado'),
(10, 'Venezuela', '87654321', '0123456789', '04141580151', 'ejemplo@gmail.com', 'Pago Movil,Transferencia', 'habilitado'),
(24, 'Mercantil', '1247-8624-44-4444355559', 'J-12345678-9', '0414-158-0151', 'diego0510lopez@gmail.com', 'Pago Movil,Transferencia', 'habilitado'),
(25, 'Mercantil', '1247-8624-44-4444355389', 'J-12345678-9', '0414-158-0151', 'diego0510lopez@gmail.com', 'Pago Movil,Transferencia', 'habilitado'),
(26, 'Mercantil', '1247-8624-56-3452253234', 'J-12345678-9', '0414-158-0151', 'diego0510lopez@gmail.com', 'Pago Movil,Transferencia', 'habilitado'),
(27, 'Mercantil', '1247-8624-44-4444355554', 'J-12345678-9', '2414-124-3241', 'diego0510lopez@gmail.com', 'Pago Movil,Transferencia', 'habilitado'),
(28, 'Mercantil', '1247-8624-44-4444355550', 'J-12345678-9', '2414-124-3241', 'diego0510lopez@gmail.com', 'Pago Movil,Transferencia', 'habilitado'),
(29, 'Mercantil', '1247-8624-44-4444355504', 'J-12345678-9', '2414-124-3241', 'diego0510lopez@gmail.com', 'Pago Movil,Transferencia', 'habilitado'),
(30, 'Mercantil', '1247-8624-44-4444359550', 'J-12345678-9', '2414-124-3241', 'diego0510lopez@gmail.com', 'Pago Movil,Transferencia', 'habilitado'),
(31, 'Zelle', '1247-8624-56-0876896596', 'J-12345678-9', '0414-158-0151', 'diego0510lopez@gmail.com', 'Zelle', 'habilitado'),
(34, 'BNC', '1247862', '143123423442', '24141243241', 'EJEMPLO@GMAIL.COM', 'Pago Movil,Transferencia', 'habilitado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_despachos`
--

CREATE TABLE `tbl_despachos` (
  `id_despachos` int(11) NOT NULL,
  `id_clientes` int(11) NOT NULL,
  `fecha_despacho` date NOT NULL,
  `tipocompra` varchar(10) NOT NULL,
  `estado` enum('Por Despachar','Despachado') NOT NULL DEFAULT 'Por Despachar',
  `activo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_despachos`
--

INSERT INTO `tbl_despachos` (`id_despachos`, `id_clientes`, `fecha_despacho`, `tipocompra`, `estado`, `activo`) VALUES
(3, 12, '2025-07-23', 'Presencial', 'Despachado', 1),
(4, 12, '2025-09-01', 'Online', 'Por Despachar', 1),
(5, 12, '2025-09-01', 'Presencial', 'Por Despachar', 1),
(7, 12, '2025-10-12', 'Presencial', 'Por Despachar', 0),
(8, 12, '2025-10-12', 'Presencial', 'Por Despachar', 0),
(9, 12, '2025-10-12', 'Presencial', 'Por Despachar', 0),
(11, 12, '2025-10-12', 'Presencial', 'Por Despachar', 0),
(12, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(13, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(14, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(15, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(16, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(17, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(18, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(19, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(20, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(21, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(22, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(23, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(24, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(25, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(26, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(32, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0),
(34, 12, '2025-10-13', 'Presencial', 'Por Despachar', 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_despacho_detalle`
--

CREATE TABLE `tbl_despacho_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_despacho` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_despacho_detalle`
--

INSERT INTO `tbl_despacho_detalle` (`id_detalle`, `id_despacho`, `id_producto`, `cantidad`) VALUES
(2, 3, 31, 1),
(3, 3, 32, 1),
(4, 4, 28, 1),
(5, 5, 41, 1),
(7, 7, 41, 1),
(8, 8, 40, 1),
(9, 9, 41, 1),
(11, 11, 41, 1),
(12, 12, 41, 1),
(13, 13, 41, 1),
(14, 14, 28, 1),
(15, 15, 28, 1),
(16, 16, 31, 1),
(17, 17, 31, 1),
(18, 18, 31, 1),
(19, 19, 31, 1),
(20, 20, 31, 1),
(21, 21, 31, 1),
(22, 22, 31, 1),
(23, 23, 31, 1),
(24, 24, 31, 1),
(25, 25, 31, 1),
(26, 26, 41, 1),
(32, 32, 41, 1),
(34, 34, 41, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_detalles_pago`
--

CREATE TABLE `tbl_detalles_pago` (
  `id_detalles` int(11) NOT NULL,
  `id_factura` int(11) NOT NULL,
  `id_cuenta` int(11) NOT NULL,
  `observaciones` varchar(200) NOT NULL,
  `referencia` varchar(30) NOT NULL,
  `fecha` date NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `monto` float(8,2) NOT NULL,
  `comprobante` varchar(255) NOT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'En Proceso'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_detalles_pago`
--

INSERT INTO `tbl_detalles_pago` (`id_detalles`, `id_factura`, `id_cuenta`, `observaciones`, `referencia`, `fecha`, `tipo`, `monto`, `comprobante`, `estatus`) VALUES
(43, 33, 29, '', '2423452', '2025-08-28', 'Pago Movil', 170000.00, 'assets/img/comprobantes/comprobante_1756429769_0.jpg', 'En Proceso'),
(44, 33, 25, '', '123456', '2025-08-28', 'Transferencia', 615.70, 'assets/img/comprobantes/comprobante_1756428648_0.jpg', 'En Proceso'),
(45, 35, 25, '', '1253257578', '2025-09-01', 'Pago Movil', 148444.00, 'assets/img/comprobantes/1756772165_imagen_2025-09-01_201134160.png', 'Pago Procesado'),
(46, 36, 27, '', '098098', '2025-09-01', 'Transferencia', 448.40, 'assets/img/comprobantes/1756773692_Imagen_de_WhatsApp_2025-08-06_a_las_20.29.40_9e358cd4-removebg-previ', 'Pago Procesado'),
(47, 33, 30, '', '998787', '2025-10-12', 'Pago Movil', 226488.95, 'assets/img/comprobantes/comprobante_1760307198_0.png', 'En Proceso'),
(48, 33, 10, '', '0979633', '2025-10-12', 'Pago Movil', 226488.95, 'assets/img/comprobantes/comprobante_1760308540_0.png', 'En Proceso'),
(49, 33, 28, '', '754353', '2025-10-12', 'Pago Movil', 226488.95, 'assets/img/comprobantes/comprobante_1760308787_0.png', 'En Proceso'),
(50, 60, 30, '', '884675465463', '2025-10-13', 'Pago Movil', 686.41, 'assets/img/comprobantes/1760406723_Grafico_Despachos_Estado.png', 'En Proceso'),
(52, 62, 30, '', '8747857544', '2025-10-13', 'Pago Movil', 686.00, 'assets/img/comprobantes/1760407024_Grafico_Despachos_Estado.png', 'En Proceso');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_detalle_recepcion_productos`
--

CREATE TABLE `tbl_detalle_recepcion_productos` (
  `id_detalle_recepcion_productos` int(11) NOT NULL,
  `id_recepcion` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `costo` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_detalle_recepcion_productos`
--

INSERT INTO `tbl_detalle_recepcion_productos` (`id_detalle_recepcion_productos`, `id_recepcion`, `id_producto`, `costo`, `cantidad`) VALUES
(12, 10, 33, 20, 2),
(13, 10, 32, 30, 4),
(14, 11, 32, 2500, 1),
(15, 11, 29, 123, 1),
(16, 11, 28, 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_facturas`
--

CREATE TABLE `tbl_facturas` (
  `id_factura` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `cliente` int(11) NOT NULL,
  `descuento` int(3) DEFAULT NULL,
  `estatus` varchar(20) NOT NULL DEFAULT 'Borrador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_facturas`
--

INSERT INTO `tbl_facturas` (`id_factura`, `fecha`, `cliente`, `descuento`, `estatus`) VALUES
(33, '2025-07-11', 11, 0, 'En Proceso'),
(34, '2025-07-22', 10, 0, 'Borrador'),
(35, '2025-09-01', 12, 0, 'Pagada'),
(36, '2025-09-01', 12, 0, 'Pagada'),
(37, '2025-10-12', 11, 0, 'Borrador'),
(38, '2025-10-12', 11, 0, 'Borrador'),
(39, '2025-10-12', 12, 0, 'Borrador'),
(40, '2025-10-13', 12, 0, 'Borrador'),
(41, '2025-10-13', 12, 0, 'Borrador'),
(42, '2025-10-13', 12, 0, 'Borrador'),
(43, '2025-10-13', 12, 0, 'Borrador'),
(44, '2025-10-13', 12, 0, 'Borrador'),
(45, '2025-10-13', 12, 0, 'Borrador'),
(46, '2025-10-13', 12, 0, 'Borrador'),
(47, '2025-10-13', 12, 0, 'Borrador'),
(48, '2025-10-13', 12, 0, 'Borrador'),
(49, '2025-10-13', 12, 0, 'Borrador'),
(50, '2025-10-13', 12, 0, 'Borrador'),
(51, '2025-10-13', 12, 0, 'Borrador'),
(52, '2025-10-13', 12, 0, 'Borrador'),
(53, '2025-10-13', 12, 0, 'Borrador'),
(54, '2025-10-13', 12, 0, 'Borrador'),
(60, '2025-10-13', 12, 0, 'Borrador'),
(62, '2025-10-13', 12, 0, 'Borrador');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_factura_detalle`
--

CREATE TABLE `tbl_factura_detalle` (
  `id` int(11) NOT NULL,
  `factura_id` int(11) NOT NULL,
  `id_producto` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_factura_detalle`
--

INSERT INTO `tbl_factura_detalle` (`id`, `factura_id`, `id_producto`, `cantidad`) VALUES
(21, 33, 28, 1),
(22, 35, 28, 1),
(23, 36, 41, 1),
(24, 37, 32, 3),
(25, 37, 41, 1),
(26, 37, 30, 1),
(27, 38, 28, 1),
(28, 38, 32, 1),
(29, 38, 41, 1),
(30, 38, 30, 1),
(31, 39, 41, 1),
(32, 40, 41, 1),
(33, 41, 41, 1),
(34, 42, 28, 1),
(35, 43, 28, 1),
(36, 44, 31, 1),
(37, 45, 31, 1),
(38, 46, 31, 1),
(39, 47, 31, 1),
(40, 48, 31, 1),
(41, 49, 31, 1),
(42, 50, 31, 1),
(43, 51, 31, 1),
(44, 52, 31, 1),
(45, 53, 31, 1),
(46, 54, 41, 1),
(52, 60, 41, 1),
(54, 62, 41, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_ingresos_egresos`
--

CREATE TABLE `tbl_ingresos_egresos` (
  `id_finanzas` int(11) NOT NULL,
  `id_despacho` int(11) DEFAULT NULL,
  `id_detalle_recepcion_productos` int(11) DEFAULT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `monto` float(6,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha` date NOT NULL,
  `estado` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_ingresos_egresos`
--

INSERT INTO `tbl_ingresos_egresos` (`id_finanzas`, `id_despacho`, `id_detalle_recepcion_productos`, `tipo`, `monto`, `descripcion`, `fecha`, `estado`) VALUES
(8, NULL, 12, 'egreso', 40.00, 'Compra: ImpriColor (x2)', '2025-07-22', 1),
(9, NULL, 13, 'egreso', 120.00, 'Compra: Tinta Arcoiris (x4)', '2025-07-22', 1),
(10, 3, NULL, 'ingreso', 18.00, 'Venta: Colormedia (x1), Tinta Arcoiris (x1)', '2025-07-23', 1),
(11, NULL, 14, 'egreso', 2500.00, 'Compra: Tinta Arcoiris (x1)', '2025-07-27', 1),
(12, NULL, 15, 'egreso', 123.00, 'Compra: Impresora Maxi (x1)', '2025-07-27', 1),
(13, NULL, 16, 'egreso', 1.00, 'Compra: Impresora Super (x1)', '2025-07-27', 1),
(14, 4, NULL, 'ingreso', 1000.00, 'Venta: Impresora Super (x1)', '2025-09-01', 1),
(15, 5, NULL, 'ingreso', 3.00, 'Venta: Rema de Papel  (x1)', '2025-09-01', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_marcas`
--

CREATE TABLE `tbl_marcas` (
  `id_marca` int(11) NOT NULL,
  `nombre_marca` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_marcas`
--

INSERT INTO `tbl_marcas` (`id_marca`, `nombre_marca`) VALUES
(1, 'Epson'),
(2, 'HP'),
(3, 'Canon'),
(4, 'Inktec'),
(5, 'TexPrint'),
(6, 'Sawgrass'),
(7, 'Cosmos Ink'),
(8, 'Azon'),
(9, 'Sublimagic'),
(10, 'Brother'),
(11, 'Forza'),
(12, 'Tripp Lite'),
(13, 'CDP'),
(14, 'Koblenz'),
(15, 'Epson'),
(16, 'HP'),
(17, 'Canon'),
(18, 'Inktec'),
(19, 'TexPrint'),
(20, 'Sawgrass'),
(21, 'Cosmos Ink'),
(22, 'Azon'),
(23, 'Sublimagic'),
(24, 'Brother'),
(25, 'Forza'),
(26, 'Tripp Lite'),
(27, 'CDP'),
(28, 'Koblenz'),
(29, 'Pokemon'),
(30, 'Digimon'),
(31, 'Nintendo');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_modelos`
--

CREATE TABLE `tbl_modelos` (
  `id_modelo` int(11) NOT NULL,
  `nombre_modelo` varchar(25) NOT NULL,
  `id_marca` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_modelos`
--

INSERT INTO `tbl_modelos` (`id_modelo`, `nombre_modelo`, `id_marca`) VALUES
(1, 'L32508', NULL),
(2, 'L32106', NULL),
(3, 'L8055', NULL),
(4, 'L18001', NULL),
(5, 'L13001', NULL),
(6, 'F170911', 2),
(7, 'F5709', 2),
(8, 'Smart Tank 515', 2),
(9, 'DeskJet 2775', 2),
(10, 'LaserJet Pro M404dn', 2),
(11, 'PIXMA G3110', 3),
(12, 'PIXMA G6010', 3),
(13, 'i-SENSYS MF445dw', 3),
(14, 'Sublinova', 4),
(15, 'SubliJet', 6),
(16, 'L3250', 1),
(17, 'L3210', 1),
(18, 'L805', 1),
(19, 'L1800', 1),
(20, 'L1300', 1),
(21, 'F170', 1),
(22, 'F570', 1),
(23, 'Smart Tank 515', 2),
(24, 'DeskJet 2775', 2),
(25, 'LaserJet Pro M404dn', 2),
(26, 'PIXMA G3110', 3),
(27, 'PIXMA G6010', 3),
(28, 'i-SENSYS MF445dw', 3),
(29, 'Sublinova', 4),
(30, 'SubliJet', 6),
(31, 'Sublime', 8),
(32, 'Durabrite', 15),
(33, 'Innobella', 10),
(34, 'ChromaLife 100+', 3),
(35, 'T664 ', 1),
(36, 'T673 ', 1),
(37, 'T774', 1),
(38, '664 ', 2),
(39, '662 ', 2),
(40, '680 ', 2),
(41, '955 ', 2),
(42, '950', 2),
(43, 'PG-145 ', 3),
(44, 'CL-146 ', 3),
(45, 'GI-190', 3),
(46, 'FVR-1211', 11),
(47, 'FVR-2202', 11),
(48, 'LR2000', 12),
(49, 'AVR750U', 12),
(50, 'R2-1200 ', 13),
(51, 'UPS 600VA', 13),
(52, '1000VA', 13),
(53, 'AVR-1000', 14),
(54, '520 Joules', 14),
(55, 'Sublime', 8),
(56, 'Durabrite', 15),
(57, 'Innobella', 10),
(58, 'ChromaLife 100+', 3),
(59, 'T664 ', 1),
(60, 'T673 ', 1),
(61, 'T774', 1),
(62, '664 ', 2),
(63, '662 ', 2),
(64, '680 ', 2),
(65, '955 ', 2),
(66, '950', 2),
(67, 'PG-145 ', 3),
(68, 'CL-146 ', 3),
(69, 'GI-190', 3),
(70, 'FVR-1211', 11),
(71, 'FVR-2202', 11),
(72, 'LR2000', 12),
(73, 'AVR750U', 12),
(74, 'R2-1200 ', 13),
(75, 'UPS 600VA', 13),
(76, '1000VA', 13),
(77, 'AVR-1000', 14),
(78, '520 Joulesj', 3),
(79, 'Ejemplo', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_modulos`
--

CREATE TABLE `tbl_modulos` (
  `id_modulo` int(11) NOT NULL,
  `nombre_modulo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_modulos`
--

INSERT INTO `tbl_modulos` (`id_modulo`, `nombre_modulo`) VALUES
(1, 'Usuario'),
(2, 'Recepcion'),
(3, 'Despacho'),
(4, 'Marcas'),
(5, 'Modelos'),
(6, 'Productos'),
(7, 'Categorias'),
(8, 'Proveedores'),
(9, 'Clientes'),
(10, 'Catalogo'),
(11, 'Carrito'),
(12, 'Pasarela'),
(13, 'Prefactura'),
(14, 'Ordenes de despacho'),
(15, 'Cuentas bancarias'),
(16, 'Finanzas'),
(17, 'Permisos'),
(18, 'Roles'),
(19, 'Bitacora'),
(20, 'Backup');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_orden_despachos`
--

CREATE TABLE `tbl_orden_despachos` (
  `id_orden_despachos` int(11) NOT NULL,
  `id_factura` int(11) NOT NULL,
  `cliente` varchar(50) NOT NULL,
  `fecha_despacho` date NOT NULL,
  `estado` enum('Por Entregar','Entregada') NOT NULL DEFAULT 'Por Entregar',
  `activo` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_orden_despachos`
--

INSERT INTO `tbl_orden_despachos` (`id_orden_despachos`, `id_factura`, `cliente`, `fecha_despacho`, `estado`, `activo`) VALUES
(3, 33, 'David Medina', '2025-07-23', 'Por Entregar', 1),
(4, 33, 'David Medina', '2025-07-24', 'Por Entregar', 1),
(5, 33, 'David Medina', '2025-07-24', 'Por Entregar', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_productos`
--

CREATE TABLE `tbl_productos` (
  `id_producto` int(11) NOT NULL,
  `serial` varchar(20) NOT NULL,
  `nombre_producto` varchar(20) NOT NULL,
  `descripcion_producto` varchar(255) DEFAULT NULL,
  `id_modelo` int(11) DEFAULT NULL,
  `id_categoria` int(2) DEFAULT NULL,
  `stock` int(3) DEFAULT NULL,
  `stock_minimo` int(3) DEFAULT NULL,
  `stock_maximo` int(3) DEFAULT NULL,
  `clausula_garantia` varchar(150) NOT NULL,
  `precio` float(10,2) DEFAULT NULL,
  `estado` varchar(20) DEFAULT '1',
  `imagen` varchar(255) DEFAULT NULL COMMENT 'Ruta de la imagen del producto en formato IMGProductosproducto_X.jpeg donde X es el id_producto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_productos`
--

INSERT INTO `tbl_productos` (`id_producto`, `serial`, `nombre_producto`, `descripcion_producto`, `id_modelo`, `id_categoria`, `stock`, `stock_minimo`, `stock_maximo`, `clausula_garantia`, `precio`, `estado`, `imagen`) VALUES
(28, '0001', 'EPSON EcoTank L3250', 'Impresora multifuncional imprime, copia, escanea y Wi‑Fi', 16, 11, 50, 10, 100, 'Garantia Valida hasta los 3 meses ', 372.66, 'habilitado', 'assets\\img\\productos\\producto_29.jpg'),
(29, '0002', 'HP DeskJet 2775', 'Imprime, copia y escanea. Garantía de 1 año. Imprime desde el teléfono. Recarga fácil.', 24, 11, 50, 10, 100, 'Garantía para 1 mes', 243.33, 'habilitado', 'assets\\img\\productos\\producto_31.jpg'),
(30, '0003', 'Cutter 360', 'Corta en todas direcciones. Ideal para manualidades.', 31, 15, 50, 10, 100, 'Garantía valida en los primeros 365 días', 10.75, 'habilitado', 'assets\\img\\productos\\producto_42.jpg'),
(31, '0004', 'Pegamento Simbi para', 'Pegamento para papel. Ideal para reparar billetes.', 32, 15, 20, 10, 50, 'Sin Garantía', 7.16, 'habilitado', 'assets\\img\\productos\\producto_43.jpg'),
(32, '0005', 'Cortadoras de papel', 'Cortadoras especiales para papelería.', 33, 15, 20, 5, 50, 'Sin Garantía', 8.00, 'habilitado', 'assets\\img\\productos\\producto_44.jpg'),
(33, '0006', 'Resma de papel HP ca', 'Resmas de papel originales HP. Calidad en cada hoja, perfecta para oficina (tamaño carta).', 31, 15, 30, 10, 70, 'Sin Garantía', 7.16, 'habilitado', 'assets\\img\\productos\\producto_45.jpg'),
(34, '0007', 'Pendrive Kingston 64', 'Unidad flash USB 3.2 Gen 1. Compatible y de alto rendimiento.', 31, 15, 10, 5, 20, 'Garantía de 1 mes de duración', 7.16, 'habilitado', 'assets\\img\\productos\\producto_46.jpg'),
(35, '0008', 'Tarjeta SD Kingston ', 'Tarjeta microSD con adaptador. Velocidades de hasta 150MB/s en lectura.', 31, 15, 7, 5, 20, 'Garantía de 1 mes de duración', 8.84, 'habilitado', 'assets\\img\\productos\\producto_47.jpg'),
(36, '0009', 'Pendrive Kingston 12', 'Unidad flash USB 3.2 Gen 1. Gran capacidad para tus archivos.', 31, 15, 10, 5, 25, 'Garantía de 1 mes de duración', 10.96, 'habilitado', 'assets\\img\\productos\\producto_48.jpg'),
(37, '0010', 'Cable USB para impre', 'Cable USB para impresoras, dos metros de largo.', 31, 11, 12, 10, 40, 'Garantía de 1 mes de duración', 7.16, 'habilitado', 'assets\\img\\productos\\producto_49.jpg'),
(38, '0011', 'Auriculares Redmi Bu', 'Sonido de alta fidelidad con cancelación de ruido.', 31, 15, 16, 5, 20, 'Garantía de 1 mes de duración', 37.97, 'habilitado', 'assets\\img\\productos\\producto_50.jpg'),
(39, '0012', 'Cinta Epson S0156312', 'Cinta original para impresoras Epson LX300 y LX350.', 31, 15, 7, 3, 15, 'Garantía de 1 mes de duración', 20.00, 'habilitado', 'assets\\img\\productos\\producto_51.jpg'),
(40, '0013', 'Tinta HP Original GT', 'Botella de tinta negra original HP GT52/GT53.', 39, 12, 20, 10, 100, 'Garantía de 1 mes de duración', 31.52, 'habilitado', 'assets\\img\\productos\\producto_52.jpg'),
(41, '0014', 'Kit de tintas CasaLa', 'Kit de tintas para impresoras con sistema adaptado en CasaLai.', 55, 12, 15, 5, 50, 'Sin Garantia', 57.32, 'habilitado', 'assets\\img\\productos\\producto_53.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_proveedores`
--

CREATE TABLE `tbl_proveedores` (
  `id_proveedor` int(11) NOT NULL,
  `nombre_proveedor` varchar(50) NOT NULL,
  `rif_proveedor` varchar(15) DEFAULT NULL,
  `nombre_representante` varchar(50) DEFAULT NULL,
  `rif_representante` varchar(15) DEFAULT NULL,
  `correo_proveedor` varchar(50) DEFAULT NULL,
  `direccion_proveedor` text DEFAULT NULL,
  `telefono_1` varchar(15) DEFAULT NULL,
  `telefono_2` varchar(15) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `estado` enum('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_proveedores`
--

INSERT INTO `tbl_proveedores` (`id_proveedor`, `nombre_proveedor`, `rif_proveedor`, `nombre_representante`, `rif_representante`, `correo_proveedor`, `direccion_proveedor`, `telefono_1`, `telefono_2`, `observacion`, `estado`) VALUES
(1, 'Aliexpres', 'V-12332125-7', 'Brayan Mendoza', 'J-98778954-7', 'ejemplo@gmail.com', 'calle 32 con carrera 18 y 19', '0412-258-8989', '0424-654-4554', 'Buena calidad de productos, envio gratis', 'habilitado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_recepcion_productos`
--

CREATE TABLE `tbl_recepcion_productos` (
  `id_recepcion` int(11) NOT NULL,
  `id_proveedor` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `correlativo` varchar(255) NOT NULL,
  `tamanocompra` enum('Pequeño','Mediano','Grande') NOT NULL,
  `estado` enum('habilitado','anulado') NOT NULL DEFAULT 'habilitado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_recepcion_productos`
--

INSERT INTO `tbl_recepcion_productos` (`id_recepcion`, `id_proveedor`, `fecha`, `correlativo`, `tamanocompra`, `estado`) VALUES
(10, 1, '2025-07-22', '1235', 'Mediano', 'habilitado'),
(11, 1, '2025-07-27', '00012', 'Grande', 'habilitado');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cat_cartucho_de_tinta`
--
ALTER TABLE `cat_cartucho_de_tinta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `cat_impresoras`
--
ALTER TABLE `cat_impresoras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `cat_otros`
--
ALTER TABLE `cat_otros`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `cat_protector_de_voltaje`
--
ALTER TABLE `cat_protector_de_voltaje`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `cat_tintas`
--
ALTER TABLE `cat_tintas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `dolar_cache`
--
ALTER TABLE `dolar_cache`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `tbl_carrito`
--
ALTER TABLE `tbl_carrito`
  ADD PRIMARY KEY (`id_carrito`),
  ADD KEY `id_cliente` (`id_cliente`);

--
-- Indices de la tabla `tbl_carritodetalle`
--
ALTER TABLE `tbl_carritodetalle`
  ADD PRIMARY KEY (`id_carrito_detalle`),
  ADD KEY `id_carrito` (`id_carrito`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `tbl_categoria`
--
ALTER TABLE `tbl_categoria`
  ADD PRIMARY KEY (`id_categoria`);

--
-- Indices de la tabla `tbl_clientes`
--
ALTER TABLE `tbl_clientes`
  ADD PRIMARY KEY (`id_clientes`),
  ADD UNIQUE KEY `cedula` (`cedula`);

--
-- Indices de la tabla `tbl_combo`
--
ALTER TABLE `tbl_combo`
  ADD PRIMARY KEY (`id_combo`);

--
-- Indices de la tabla `tbl_combo_detalle`
--
ALTER TABLE `tbl_combo_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_combo` (`id_combo`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `tbl_cuentas`
--
ALTER TABLE `tbl_cuentas`
  ADD PRIMARY KEY (`id_cuenta`);

--
-- Indices de la tabla `tbl_despachos`
--
ALTER TABLE `tbl_despachos`
  ADD PRIMARY KEY (`id_despachos`),
  ADD KEY `id_clientes` (`id_clientes`);

--
-- Indices de la tabla `tbl_despacho_detalle`
--
ALTER TABLE `tbl_despacho_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `id_despacho` (`id_despacho`),
  ADD KEY `id_producto` (`id_producto`);

--
-- Indices de la tabla `tbl_detalles_pago`
--
ALTER TABLE `tbl_detalles_pago`
  ADD PRIMARY KEY (`id_detalles`),
  ADD KEY `tbl_detalles_pago` (`id_factura`),
  ADD KEY `tbl_detalles_pago1` (`id_cuenta`);

--
-- Indices de la tabla `tbl_detalle_recepcion_productos`
--
ALTER TABLE `tbl_detalle_recepcion_productos`
  ADD PRIMARY KEY (`id_detalle_recepcion_productos`),
  ADD KEY `fk_detalle_recepcion` (`id_recepcion`),
  ADD KEY `fk_detalle_producto` (`id_producto`);

--
-- Indices de la tabla `tbl_facturas`
--
ALTER TABLE `tbl_facturas`
  ADD PRIMARY KEY (`id_factura`),
  ADD KEY `cliente` (`cliente`);

--
-- Indices de la tabla `tbl_factura_detalle`
--
ALTER TABLE `tbl_factura_detalle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `factura_id` (`factura_id`),
  ADD KEY `tbl_factura_detalle` (`id_producto`);

--
-- Indices de la tabla `tbl_ingresos_egresos`
--
ALTER TABLE `tbl_ingresos_egresos`
  ADD PRIMARY KEY (`id_finanzas`),
  ADD KEY `id_despacho` (`id_despacho`,`id_detalle_recepcion_productos`),
  ADD KEY `id_detalle_recepcion_productos` (`id_detalle_recepcion_productos`);

--
-- Indices de la tabla `tbl_marcas`
--
ALTER TABLE `tbl_marcas`
  ADD PRIMARY KEY (`id_marca`);

--
-- Indices de la tabla `tbl_modelos`
--
ALTER TABLE `tbl_modelos`
  ADD PRIMARY KEY (`id_modelo`),
  ADD KEY `fk_modelo_marca` (`id_marca`);

--
-- Indices de la tabla `tbl_modulos`
--
ALTER TABLE `tbl_modulos`
  ADD PRIMARY KEY (`id_modulo`);

--
-- Indices de la tabla `tbl_orden_despachos`
--
ALTER TABLE `tbl_orden_despachos`
  ADD PRIMARY KEY (`id_orden_despachos`),
  ADD KEY `id_factura` (`id_factura`);

--
-- Indices de la tabla `tbl_productos`
--
ALTER TABLE `tbl_productos`
  ADD PRIMARY KEY (`id_producto`),
  ADD KEY `fk_producto_categoria` (`id_categoria`),
  ADD KEY `fk_producto_modelo` (`id_modelo`);

--
-- Indices de la tabla `tbl_proveedores`
--
ALTER TABLE `tbl_proveedores`
  ADD PRIMARY KEY (`id_proveedor`);

--
-- Indices de la tabla `tbl_recepcion_productos`
--
ALTER TABLE `tbl_recepcion_productos`
  ADD PRIMARY KEY (`id_recepcion`),
  ADD KEY `fk_recepcion_proveedor` (`id_proveedor`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cat_cartucho_de_tinta`
--
ALTER TABLE `cat_cartucho_de_tinta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cat_impresoras`
--
ALTER TABLE `cat_impresoras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cat_otros`
--
ALTER TABLE `cat_otros`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `cat_protector_de_voltaje`
--
ALTER TABLE `cat_protector_de_voltaje`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `cat_tintas`
--
ALTER TABLE `cat_tintas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `dolar_cache`
--
ALTER TABLE `dolar_cache`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7080;

--
-- AUTO_INCREMENT de la tabla `tbl_carrito`
--
ALTER TABLE `tbl_carrito`
  MODIFY `id_carrito` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `tbl_carritodetalle`
--
ALTER TABLE `tbl_carritodetalle`
  MODIFY `id_carrito_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT de la tabla `tbl_categoria`
--
ALTER TABLE `tbl_categoria`
  MODIFY `id_categoria` int(2) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `tbl_clientes`
--
ALTER TABLE `tbl_clientes`
  MODIFY `id_clientes` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `tbl_combo`
--
ALTER TABLE `tbl_combo`
  MODIFY `id_combo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `tbl_combo_detalle`
--
ALTER TABLE `tbl_combo_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `tbl_cuentas`
--
ALTER TABLE `tbl_cuentas`
  MODIFY `id_cuenta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `tbl_despachos`
--
ALTER TABLE `tbl_despachos`
  MODIFY `id_despachos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `tbl_despacho_detalle`
--
ALTER TABLE `tbl_despacho_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `tbl_detalles_pago`
--
ALTER TABLE `tbl_detalles_pago`
  MODIFY `id_detalles` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT de la tabla `tbl_detalle_recepcion_productos`
--
ALTER TABLE `tbl_detalle_recepcion_productos`
  MODIFY `id_detalle_recepcion_productos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `tbl_facturas`
--
ALTER TABLE `tbl_facturas`
  MODIFY `id_factura` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT de la tabla `tbl_factura_detalle`
--
ALTER TABLE `tbl_factura_detalle`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de la tabla `tbl_ingresos_egresos`
--
ALTER TABLE `tbl_ingresos_egresos`
  MODIFY `id_finanzas` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `tbl_marcas`
--
ALTER TABLE `tbl_marcas`
  MODIFY `id_marca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de la tabla `tbl_modelos`
--
ALTER TABLE `tbl_modelos`
  MODIFY `id_modelo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT de la tabla `tbl_modulos`
--
ALTER TABLE `tbl_modulos`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `tbl_orden_despachos`
--
ALTER TABLE `tbl_orden_despachos`
  MODIFY `id_orden_despachos` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tbl_productos`
--
ALTER TABLE `tbl_productos`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `tbl_proveedores`
--
ALTER TABLE `tbl_proveedores`
  MODIFY `id_proveedor` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1003;

--
-- AUTO_INCREMENT de la tabla `tbl_recepcion_productos`
--
ALTER TABLE `tbl_recepcion_productos`
  MODIFY `id_recepcion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cat_cartucho_de_tinta`
--
ALTER TABLE `cat_cartucho_de_tinta`
  ADD CONSTRAINT `cat_cartucho_de_tinta_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cat_impresoras`
--
ALTER TABLE `cat_impresoras`
  ADD CONSTRAINT `cat_impresoras_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cat_otros`
--
ALTER TABLE `cat_otros`
  ADD CONSTRAINT `cat_otros_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cat_protector_de_voltaje`
--
ALTER TABLE `cat_protector_de_voltaje`
  ADD CONSTRAINT `cat_protector_de_voltaje_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `cat_tintas`
--
ALTER TABLE `cat_tintas`
  ADD CONSTRAINT `cat_tintas_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tbl_carritodetalle`
--
ALTER TABLE `tbl_carritodetalle`
  ADD CONSTRAINT `tbl_carritodetalle_ibfk_1` FOREIGN KEY (`id_carrito`) REFERENCES `tbl_carrito` (`id_carrito`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_carritodetalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_combo_detalle`
--
ALTER TABLE `tbl_combo_detalle`
  ADD CONSTRAINT `tbl_combo_detalle_ibfk_1` FOREIGN KEY (`id_combo`) REFERENCES `tbl_combo` (`id_combo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_combo_detalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_despachos`
--
ALTER TABLE `tbl_despachos`
  ADD CONSTRAINT `tbl_despachos_ibfk_1` FOREIGN KEY (`id_clientes`) REFERENCES `tbl_clientes` (`id_clientes`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_despacho_detalle`
--
ALTER TABLE `tbl_despacho_detalle`
  ADD CONSTRAINT `tbl_despacho_detalle_ibfk_1` FOREIGN KEY (`id_despacho`) REFERENCES `tbl_despachos` (`id_despachos`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_despacho_detalle_ibfk_2` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`);

--
-- Filtros para la tabla `tbl_detalles_pago`
--
ALTER TABLE `tbl_detalles_pago`
  ADD CONSTRAINT `fk_id_cuenta` FOREIGN KEY (`id_cuenta`) REFERENCES `tbl_cuentas` (`id_cuenta`),
  ADD CONSTRAINT `fk_id_factura` FOREIGN KEY (`id_factura`) REFERENCES `tbl_facturas` (`id_factura`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tbl_detalle_recepcion_productos`
--
ALTER TABLE `tbl_detalle_recepcion_productos`
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`),
  ADD CONSTRAINT `fk_detalle_recepcion` FOREIGN KEY (`id_recepcion`) REFERENCES `tbl_recepcion_productos` (`id_recepcion`),
  ADD CONSTRAINT `tbl_detalles_recepcion_productos` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tbl_facturas`
--
ALTER TABLE `tbl_facturas`
  ADD CONSTRAINT `tbl_facturas_ibfk_1` FOREIGN KEY (`cliente`) REFERENCES `tbl_clientes` (`id_clientes`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_factura_detalle`
--
ALTER TABLE `tbl_factura_detalle`
  ADD CONSTRAINT `factura_detalle_ibfk_1` FOREIGN KEY (`factura_id`) REFERENCES `tbl_facturas` (`id_factura`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbl_factura_detalle` FOREIGN KEY (`id_producto`) REFERENCES `tbl_productos` (`id_producto`) ON DELETE CASCADE;

--
-- Filtros para la tabla `tbl_ingresos_egresos`
--
ALTER TABLE `tbl_ingresos_egresos`
  ADD CONSTRAINT `tbl_ingresos_egresos_ibfk_1` FOREIGN KEY (`id_despacho`) REFERENCES `tbl_despachos` (`id_despachos`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_ingresos_egresos_ibfk_2` FOREIGN KEY (`id_detalle_recepcion_productos`) REFERENCES `tbl_detalle_recepcion_productos` (`id_detalle_recepcion_productos`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_modelos`
--
ALTER TABLE `tbl_modelos`
  ADD CONSTRAINT `fk_modelo_marca` FOREIGN KEY (`id_marca`) REFERENCES `tbl_marcas` (`id_marca`),
  ADD CONSTRAINT `modelo_ibfk_1` FOREIGN KEY (`id_marca`) REFERENCES `tbl_marcas` (`id_marca`);

--
-- Filtros para la tabla `tbl_orden_despachos`
--
ALTER TABLE `tbl_orden_despachos`
  ADD CONSTRAINT `tbl_orden_despachos_ibfk_1` FOREIGN KEY (`id_factura`) REFERENCES `tbl_facturas` (`id_factura`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_productos`
--
ALTER TABLE `tbl_productos`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `tbl_categoria` (`id_categoria`),
  ADD CONSTRAINT `fk_producto_modelo` FOREIGN KEY (`id_modelo`) REFERENCES `tbl_modelos` (`id_modelo`),
  ADD CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`id_modelo`) REFERENCES `tbl_modelos` (`id_modelo`),
  ADD CONSTRAINT `productos_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `tbl_categoria` (`id_categoria`);

--
-- Filtros para la tabla `tbl_recepcion_productos`
--
ALTER TABLE `tbl_recepcion_productos`
  ADD CONSTRAINT `fk_recepcion_proveedor` FOREIGN KEY (`id_proveedor`) REFERENCES `tbl_proveedores` (`id_proveedor`),
  ADD CONSTRAINT `tbl_recepcion_productos` FOREIGN KEY (`id_proveedor`) REFERENCES `tbl_proveedores` (`id_proveedor`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
