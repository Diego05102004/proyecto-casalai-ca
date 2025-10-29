-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-10-2025 a las 04:42:22
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
-- Base de datos: `seguridadlai`
--
CREATE DATABASE IF NOT EXISTS `seguridadlai` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `seguridadlai`;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_alertas`
--

CREATE TABLE `tbl_alertas` (
  `id_alerta` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `tipo` varchar(20) NOT NULL,
  `mensaje` varchar(150) NOT NULL,
  `id_usuario` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_bitacora`
--

CREATE TABLE `tbl_bitacora` (
  `id_bitacora` int(11) NOT NULL,
  `fecha_hora` text NOT NULL,
  `accion` varchar(50) NOT NULL,
  `datos_nuevos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_nuevos`)),
  `datos_viejos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`datos_viejos`)),
  `id_modulo` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `prioridad` enum('baja','media','alta') NOT NULL DEFAULT 'media',
  `descripcion` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_bitacora`
--

INSERT INTO `tbl_bitacora` (`id_bitacora`, `fecha_hora`, `accion`, `datos_nuevos`, `datos_viejos`, `id_modulo`, `id_usuario`, `prioridad`, `descripcion`) VALUES
(48, '2025-09-08 07:40:13', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(49, '2025-09-08 07:40:19', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(50, '2025-09-08 07:47:42', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(51, '2025-09-08 07:48:53', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(52, '2025-09-08 07:50:28', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(53, '2025-09-08 07:51:46', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(54, '2025-09-08 07:55:23', 'ACCESO', NULL, NULL, 4, 8, 'media', 'El usuario accedió al al modulo de marcas'),
(55, '2025-09-08 07:55:30', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(56, '2025-09-08 07:55:48', 'ACCESO', NULL, NULL, 4, 8, 'media', 'El usuario accedió al al modulo de marcas'),
(57, '2025-09-08 08:00:35', 'ACCESO', NULL, NULL, 4, 8, 'media', 'El usuario accedió al al modulo de marcas'),
(58, '2025-09-08 08:01:12', 'ACCESO', NULL, NULL, 4, 8, 'media', 'El usuario accedió al al modulo de marcas'),
(59, '2025-09-08 08:01:24', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(60, '2025-09-08 08:06:17', 'ACCESO', NULL, NULL, 4, 8, 'media', 'El usuario accedió al al modulo de marcas'),
(61, '2025-09-08 08:06:25', 'ELIMINACIÓN', NULL, NULL, 4, 8, 'alta', 'El usuario elimino de los registros la marca Array'),
(62, '2025-09-08 08:07:13', 'ACCESO', NULL, NULL, 4, 8, 'media', 'El usuario accedió al al modulo de marcas'),
(63, '2025-09-08 08:07:21', 'ELIMINACIÓN', NULL, NULL, 4, 8, 'alta', 'El usuario elimino de los registros la marca Pokemon'),
(64, '2025-09-08 08:07:28', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(65, '2025-09-08 21:27:50', 'ACCESO', NULL, NULL, 4, 8, 'media', 'El usuario accedió al al modulo de marcas'),
(66, '2025-09-08 21:27:50', 'ACCESO', NULL, NULL, 4, 8, 'media', 'El usuario accedió al al modulo de marcas'),
(67, '2025-09-08 21:32:08', 'ACCESO', NULL, NULL, 4, 8, 'media', 'El usuario accedió al al modulo de marcas'),
(68, '2025-09-08 21:32:19', 'MODIFICACIÓN', NULL, NULL, 4, 8, 'alta', 'El usuario modifico los  la marca TexPrint a TexPrint1'),
(69, '2025-09-08 21:32:23', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(70, '2025-09-08 21:40:00', 'ACCESAR', NULL, NULL, 4, 8, 'media', 'El usuario accedió al al modulo de marcas'),
(71, '2025-09-08 21:40:11', 'INCLUIR', NULL, NULL, 4, 8, 'alta', 'El usuario incluyó la marca: {\n    \"id_marca\": 31,\n    \"nombre_marca\": \"YUGIOH\"\n}'),
(72, '2025-09-08 21:40:21', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(73, '2025-09-08 21:41:08', 'ELIMINAR', NULL, NULL, 4, 8, 'alta', 'El usuario elimino de los registros la marca YUGIOH'),
(74, '2025-09-08 21:41:22', 'INCLUIR', NULL, NULL, 4, 8, 'alta', 'El usuario incluyó la marca: id_marca: 32, nombre_marca: YUGIOH'),
(75, '2025-09-08 21:41:29', 'ACCESO', NULL, NULL, 19, 8, 'alta', 'El usuario accedió al al modulo de bitacora'),
(76, '2025-09-08 22:25:03', 'ACCESO', NULL, NULL, 19, 3, 'alta', 'El usuario accedió al al modulo de bitacora'),
(77, '2025-09-08 22:31:50', 'ACCESAR', NULL, NULL, 1, 3, 'media', 'El usuario accedió al al modulo de Usuarios'),
(78, '2025-09-08 22:31:58', 'ACCESO', NULL, NULL, 19, 3, 'alta', 'El usuario accedió al al modulo de bitacora'),
(79, '2025-09-09 22:06:04', 'ACCESO', NULL, NULL, 19, 3, 'alta', 'El usuario accedió al al modulo de bitacora'),
(80, '2025-09-09 22:06:12', 'ACCESAR', NULL, NULL, 1, 3, 'media', 'El usuario accedió al al modulo de Usuarios'),
(81, '2025-09-09 22:07:04', 'MODIFICAR', NULL, NULL, 1, 3, 'media', 'Cambio de estatus de usuario: 15 a inhabilitado'),
(82, '2025-09-09 22:11:51', 'ACCESAR', NULL, NULL, 18, 3, 'media', 'El usuario accedió al al modulo de Roles'),
(83, '2025-09-09 22:11:59', 'ACCESAR', NULL, NULL, 18, 3, 'media', 'El usuario accedió al al modulo de Roles'),
(84, '2025-09-09 22:14:50', 'ACCESAR', NULL, NULL, 2, 3, 'media', 'El usuario accedió al al modulo de Recepcion'),
(85, '2025-09-09 22:20:17', 'ACCESAR', NULL, NULL, 8, 3, 'media', 'El usuario accedió al al modulo de Proveedores'),
(86, '2025-09-09 22:30:00', 'ACCESAR', NULL, NULL, 6, 3, 'media', 'El usuario accedió al al modulo de Productos'),
(87, '2025-09-09 22:30:02', 'ACCESAR', NULL, NULL, 6, 3, 'media', 'El usuario accedió al al modulo de Productos'),
(88, '2025-09-09 22:31:02', 'ACCESO', NULL, NULL, 19, 3, 'alta', 'El usuario accedió al al modulo de bitacora'),
(89, '2025-09-09 22:37:03', 'ACCESAR', NULL, NULL, 17, 3, 'media', 'El usuario accedió al al modulo de Permisos'),
(90, '2025-09-09 22:37:25', 'ACCESAR', NULL, NULL, 17, 3, 'media', 'El usuario accedió al al modulo de Permisos'),
(91, '2025-09-09 22:38:03', 'MODIFICAR', NULL, NULL, 17, 3, 'media', 'El usuario modificó los permisos de los roles del sistema'),
(92, '2025-09-09 22:38:05', 'ACCESAR', NULL, NULL, 17, 3, 'media', 'El usuario accedió al al modulo de Permisos'),
(93, '2025-09-09 22:38:09', 'ACCESO', NULL, NULL, 19, 3, 'alta', 'El usuario accedió al al modulo de bitacora'),
(94, '2025-09-09 22:38:34', 'ACCESO', NULL, NULL, 19, 3, 'alta', 'El usuario accedió al al modulo de bitacora'),
(95, '2025-09-09 22:42:03', 'ACCESO', NULL, NULL, 19, 3, 'alta', 'El usuario accedió al al modulo de bitacora'),
(96, '2025-09-09 22:47:36', 'ACCESAR', NULL, NULL, 12, 3, 'media', 'El usuario accedió al al modulo de Pasarela de pagos'),
(97, '2025-10-06 21:44:35', 'ACCESAR', NULL, NULL, 3, 3, 'media', 'El usuario accedió al al modulo de Despachos'),
(98, '2025-10-06 21:45:35', 'ACCESAR', NULL, NULL, 3, 3, 'media', 'El usuario accedió al al modulo de Despachos'),
(99, '2025-10-06 21:45:53', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(100, '2025-10-06 21:51:51', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(101, '2025-10-06 21:54:25', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(102, '2025-10-06 22:07:54', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(103, '2025-10-06 22:08:43', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(104, '2025-10-06 22:08:54', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(105, '2025-10-06 22:09:07', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(106, '2025-10-06 22:09:15', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(107, '2025-10-06 22:11:27', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(108, '2025-10-06 22:13:35', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(109, '2025-10-06 22:14:41', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(110, '2025-10-06 22:14:45', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(111, '2025-10-06 22:14:57', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(112, '2025-10-06 22:19:19', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al módulo de Ordenes de Despacho'),
(113, '2025-10-06 22:20:20', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(114, '2025-10-06 22:22:14', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(115, '2025-10-06 22:23:07', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(116, '2025-10-06 22:23:11', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(117, '2025-10-06 22:23:21', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(118, '2025-10-06 22:23:34', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(119, '2025-10-06 22:23:53', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(120, '2025-10-06 22:28:32', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(121, '2025-10-06 22:28:44', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(122, '2025-10-07 19:21:12', 'ACCESAR', NULL, NULL, 10, 11, 'media', 'El usuario accedió al al modulo de Catálogo'),
(123, '2025-10-07 19:21:38', 'ACCESAR', NULL, NULL, 10, 11, 'media', 'El usuario accedió al al modulo de Catálogo'),
(124, '2025-10-07 19:22:16', 'ACCESAR', NULL, NULL, 10, 11, 'media', 'El usuario accedió al al modulo de Catálogo'),
(125, '2025-10-07 19:27:43', 'ACCESAR', NULL, NULL, 10, 11, 'media', 'El usuario accedió al al modulo de Catálogo'),
(126, '2025-10-07 19:28:22', 'ACCESAR', NULL, NULL, 10, 11, 'media', 'El usuario accedió al al modulo de Catálogo'),
(127, '2025-10-07 19:29:03', 'ACCESAR', NULL, NULL, 10, 11, 'media', 'El usuario accedió al al modulo de Catálogo'),
(128, '2025-10-07 19:33:29', 'ACCESAR', NULL, NULL, 10, 4, 'media', 'El usuario accedió al al modulo de Catálogo'),
(129, '2025-10-07 19:34:51', 'ACCESAR', NULL, NULL, 1, 3, 'media', 'El usuario accedió al al modulo de Usuarios'),
(130, '2025-10-07 19:38:02', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(131, '2025-10-07 19:40:51', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(132, '2025-10-07 19:45:09', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(133, '2025-10-07 20:19:34', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(134, '2025-10-07 20:20:04', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(135, '2025-10-07 20:38:12', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(136, '2025-10-07 20:54:36', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(137, '2025-10-07 20:55:52', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(138, '2025-10-07 20:59:20', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(139, '2025-10-07 21:02:36', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(140, '2025-10-07 21:03:10', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(141, '2025-10-07 21:03:19', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(142, '2025-10-07 21:06:52', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(143, '2025-10-07 21:07:54', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(144, '2025-10-07 21:08:43', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(145, '2025-10-07 21:09:34', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(146, '2025-10-07 21:10:03', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(147, '2025-10-07 21:10:39', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(148, '2025-10-07 21:10:45', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(149, '2025-10-07 21:16:00', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(150, '2025-10-07 21:16:51', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(151, '2025-10-07 21:18:45', 'ACCESAR', NULL, NULL, 14, 3, 'media', 'El usuario accedió al al modulo de Ordenes de Despacho'),
(152, '2025-10-07 21:18:58', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(153, '2025-10-07 21:21:07', 'ACCESAR', NULL, NULL, 9, 3, 'media', 'El usuario accedió al al modulo de Clientes'),
(154, '2025-10-07 21:21:12', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(155, '2025-10-07 21:49:11', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(156, '2025-10-07 21:52:30', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(157, '2025-10-07 21:54:02', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(158, '2025-10-07 21:56:38', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(159, '2025-10-07 22:01:26', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(160, '2025-10-07 22:06:18', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(161, '2025-10-08 20:29:16', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(162, '2025-10-08 20:31:25', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(163, '2025-10-08 20:32:02', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(164, '2025-10-08 20:32:43', 'ACCESAR', NULL, NULL, 7, 3, 'media', 'El usuario accedió al al modulo de Categorias'),
(165, '2025-10-08 20:41:10', 'ACCESAR', NULL, NULL, 7, 3, 'media', 'El usuario accedió al al modulo de Categorias'),
(166, '2025-10-08 20:43:32', 'ACCESAR', NULL, NULL, 7, 3, 'media', 'El usuario accedió al al modulo de Categorias'),
(167, '2025-10-08 20:43:58', 'MODIFICAR', NULL, NULL, 7, 3, 'media', 'El usuario modificó la categoría ID: 15'),
(168, '2025-10-08 20:44:05', 'MODIFICAR', NULL, NULL, 7, 3, 'media', 'El usuario modificó la categoría ID: 15'),
(169, '2025-10-08 20:45:00', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(170, '2025-10-08 20:45:38', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(171, '2025-10-08 20:47:10', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(172, '2025-10-08 20:47:41', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(173, '2025-10-08 20:52:01', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(174, '2025-10-08 20:52:22', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(175, '2025-10-08 20:53:23', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(176, '2025-10-08 20:56:02', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(177, '2025-10-08 20:57:04', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(178, '2025-10-08 20:57:21', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(179, '2025-10-08 21:02:39', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(180, '2025-10-08 21:12:26', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(181, '2025-10-08 21:14:29', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(182, '2025-10-08 21:19:11', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(183, '2025-10-08 21:26:05', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(184, '2025-10-08 21:35:16', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(185, '2025-10-08 21:38:11', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(186, '2025-10-08 21:38:49', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(187, '2025-10-08 21:40:18', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(188, '2025-10-08 21:41:50', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(189, '2025-10-08 21:42:57', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(190, '2025-10-08 21:43:54', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(191, '2025-10-08 21:51:17', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(192, '2025-10-08 21:57:45', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(193, '2025-10-08 22:03:37', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(194, '2025-10-08 22:04:21', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(195, '2025-10-08 22:04:44', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(196, '2025-10-08 22:05:04', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(197, '2025-10-08 22:06:31', 'ACCESAR', NULL, NULL, 21, 3, 'media', 'El usuario accedió al al modulo de Compra Física'),
(198, '2025-10-08 22:06:55', 'ACCESAR', NULL, NULL, 15, 3, 'media', 'El usuario accedió al al modulo de Cuentas Bancarias'),
(199, '2025-10-11 09:59:36', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(200, '2025-10-11 10:01:21', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(201, '2025-10-11 10:02:53', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(202, '2025-10-11 10:02:55', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(203, '2025-10-11 10:02:56', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(204, '2025-10-11 10:02:56', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(205, '2025-10-11 10:02:57', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(206, '2025-10-11 10:02:57', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(207, '2025-10-11 10:02:58', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(208, '2025-10-11 10:02:58', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(209, '2025-10-11 10:02:59', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(210, '2025-10-11 10:02:59', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(211, '2025-10-11 10:02:59', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(212, '2025-10-11 10:03:00', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(213, '2025-10-11 10:03:00', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(214, '2025-10-11 10:04:31', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(215, '2025-10-11 10:04:57', 'ACCESAR', NULL, NULL, 4, 16, 'media', 'El usuario accedió al al modulo de marcas'),
(216, '2025-10-12 11:58:16', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(217, '2025-10-12 12:18:50', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(218, '2025-10-12 12:19:11', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(219, '2025-10-12 12:20:10', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(220, '2025-10-12 12:20:26', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(221, '2025-10-12 12:20:53', 'ACCESAR', NULL, NULL, 9, 16, 'media', 'El usuario accedió al al modulo de Clientes'),
(222, '2025-10-12 12:21:03', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(223, '2025-10-12 12:23:08', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(224, '2025-10-12 12:23:22', 'ACCESAR', NULL, NULL, 10, 16, 'media', 'El usuario accedió al al modulo de Catálogo'),
(225, '2025-10-12 12:23:44', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(226, '2025-10-12 12:38:39', 'INCLUIR', NULL, NULL, 3, 16, 'alta', 'El usuario Darckort incluyó la compra física: 62'),
(227, '2025-10-12 12:52:17', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(228, '2025-10-12 12:52:23', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(229, '2025-10-12 12:52:30', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(230, '2025-10-12 12:52:32', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(231, '2025-10-12 12:52:39', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(232, '2025-10-12 13:04:38', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(233, '2025-10-12 13:04:48', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(234, '2025-10-12 13:04:55', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(235, '2025-10-12 14:30:31', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(236, '2025-10-12 14:30:55', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(237, '2025-10-12 14:35:04', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(238, '2025-10-12 14:35:45', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(239, '2025-10-12 14:35:59', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(240, '2025-10-12 14:38:32', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(241, '2025-10-12 14:40:47', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(242, '2025-10-12 14:41:17', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(243, '2025-10-12 14:55:35', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(244, '2025-10-12 15:01:25', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(245, '2025-10-12 15:07:07', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(246, '2025-10-12 15:14:56', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(247, '2025-10-12 15:15:42', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(248, '2025-10-12 15:36:48', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(249, '2025-10-12 15:43:22', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(250, '2025-10-12 15:45:07', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(251, '2025-10-12 16:22:21', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(252, '2025-10-12 16:23:03', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(253, '2025-10-12 16:24:06', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(254, '2025-10-12 16:42:39', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(255, '2025-10-12 17:00:07', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(256, '2025-10-12 17:14:20', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(257, '2025-10-12 17:21:46', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(258, '2025-10-12 17:28:07', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(259, '2025-10-12 17:28:13', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(260, '2025-10-12 17:30:17', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(261, '2025-10-12 17:58:31', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(262, '2025-10-12 18:00:19', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(263, '2025-10-12 18:44:18', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(264, '2025-10-12 18:44:35', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(265, '2025-10-12 18:45:06', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(266, '2025-10-12 18:45:16', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(267, '2025-10-12 18:45:30', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(268, '2025-10-12 18:45:55', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(269, '2025-10-13 19:38:34', 'ACCESAR', NULL, NULL, 21, 16, 'media', 'El usuario accedió al al modulo de Compra Física'),
(270, '2025-10-13 21:36:38', 'ACCESAR', NULL, NULL, 1, 16, 'media', 'El usuario accedió al al modulo de Usuarios'),
(271, '2025-10-13 21:36:41', 'MODIFICAR', NULL, NULL, 1, 16, 'media', 'Cambio de estatus de usuario: 4 a inhabilitado'),
(272, '2025-10-13 21:36:43', 'MODIFICAR', NULL, NULL, 1, 16, 'media', 'Cambio de estatus de usuario: 4 a habilitado'),
(273, '2025-10-13 22:03:06', 'ACCESAR', NULL, NULL, 2, 16, 'media', 'El usuario accedió al al modulo de Recepcion'),
(275, '2025-10-13 22:13:54', 'Actualizó su información personal', NULL, NULL, 22, 16, '', 'UPDATE'),
(276, '2025-10-13 22:14:33', 'Actualizó su información personal', NULL, NULL, 22, 16, '', 'UPDATE'),
(277, '2025-10-13 22:15:11', 'Cambió su foto de perfil', NULL, NULL, 22, 16, '', 'UPDATE'),
(278, '2025-10-13 22:32:50', 'Actualizó su información personal', NULL, NULL, 22, 16, '', 'UPDATE'),
(279, '2025-10-13 22:33:27', 'Cambió su foto de perfil', NULL, NULL, 22, 16, '', 'UPDATE'),
(280, '2025-10-13 22:34:20', 'Actualizó su información personal', NULL, NULL, 22, 16, '', 'UPDATE'),
(281, '2025-10-13 22:36:35', 'Actualizó su información personal', NULL, NULL, 22, 16, '', 'UPDATE'),
(282, '2025-10-13 22:37:03', 'Cambió su foto de perfil', NULL, NULL, 22, 16, '', 'UPDATE');

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
(13, 'Pedidos'),
(14, 'Ordenes de despacho'),
(15, 'Cuentas bancarias'),
(16, 'Finanzas'),
(17, 'Permisos'),
(18, 'Roles'),
(19, 'Bitacora'),
(20, 'Respaldo'),
(21, 'Compra Física'),
(22, 'Perfil de Usuario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_notificaciones`
--

CREATE TABLE `tbl_notificaciones` (
  `id_notificacion` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `tipo` enum('pago','factura','despacho','sistema') NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `id_referencia` int(11) DEFAULT NULL COMMENT 'ID en la otra base de datos',
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `prioridad` enum('baja','media','alta') NOT NULL DEFAULT 'media'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_notificaciones`
--

INSERT INTO `tbl_notificaciones` (`id_notificacion`, `id_usuario`, `tipo`, `titulo`, `mensaje`, `id_referencia`, `fecha_hora`, `leido`, `prioridad`) VALUES
(1, 17, '', 'Recepción modificada', 'Has modificado la recepción #1235', 10, '2025-07-27 19:27:03', 0, 'media'),
(2, 7, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 0, 'media'),
(3, 8, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 0, 'media'),
(4, 15, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 0, 'media'),
(5, 17, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 0, 'media'),
(6, 3, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 0, 'media'),
(7, 5, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 0, 'media'),
(8, 9, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 0, 'media'),
(9, 16, 'despacho', 'Nueva compra física registrada', 'Se ha registrado una nueva compra física #62 con 4 unidades por el usuario Darckort', NULL, '2025-10-12 12:38:39', 0, 'media');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_permisos`
--

CREATE TABLE `tbl_permisos` (
  `id` int(11) NOT NULL,
  `accion` varchar(10) NOT NULL,
  `id_rol` int(11) NOT NULL,
  `id_modulo` int(11) NOT NULL,
  `estatus` enum('Permitido','No Permitido') NOT NULL DEFAULT 'Permitido'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_permisos`
--

INSERT INTO `tbl_permisos` (`id`, `accion`, `id_rol`, `id_modulo`, `estatus`) VALUES
(5, 'ingresar', 6, 1, 'Permitido'),
(10, 'consultar', 6, 1, 'Permitido'),
(15, 'modificar', 6, 1, 'Permitido'),
(20, 'incluir', 6, 1, 'Permitido'),
(25, 'eliminar', 6, 1, 'Permitido'),
(30, 'reportar', 6, 1, 'Permitido'),
(35, 'ingresar', 6, 2, 'Permitido'),
(40, 'consultar', 6, 2, 'Permitido'),
(45, 'modificar', 6, 2, 'Permitido'),
(50, 'incluir', 6, 2, 'Permitido'),
(55, 'eliminar', 6, 2, 'Permitido'),
(60, 'reportar', 6, 2, 'Permitido'),
(65, 'ingresar', 6, 3, 'Permitido'),
(70, 'consultar', 6, 3, 'Permitido'),
(75, 'modificar', 6, 3, 'Permitido'),
(80, 'incluir', 6, 3, 'Permitido'),
(85, 'eliminar', 6, 3, 'Permitido'),
(90, 'reportar', 6, 3, 'Permitido'),
(95, 'ingresar', 6, 4, 'Permitido'),
(100, 'consultar', 6, 4, 'Permitido'),
(105, 'modificar', 6, 4, 'Permitido'),
(110, 'incluir', 6, 4, 'Permitido'),
(115, 'eliminar', 6, 4, 'Permitido'),
(120, 'reportar', 6, 4, 'Permitido'),
(125, 'ingresar', 6, 5, 'Permitido'),
(130, 'consultar', 6, 5, 'Permitido'),
(135, 'modificar', 6, 5, 'Permitido'),
(140, 'incluir', 6, 5, 'Permitido'),
(145, 'eliminar', 6, 5, 'Permitido'),
(150, 'reportar', 6, 5, 'Permitido'),
(155, 'ingresar', 6, 6, 'Permitido'),
(160, 'consultar', 6, 6, 'Permitido'),
(165, 'modificar', 6, 6, 'Permitido'),
(170, 'incluir', 6, 6, 'Permitido'),
(175, 'eliminar', 6, 6, 'Permitido'),
(180, 'reportar', 6, 6, 'Permitido'),
(185, 'ingresar', 6, 7, 'Permitido'),
(190, 'consultar', 6, 7, 'Permitido'),
(195, 'modificar', 6, 7, 'Permitido'),
(200, 'incluir', 6, 7, 'Permitido'),
(205, 'eliminar', 6, 7, 'Permitido'),
(210, 'reportar', 6, 7, 'Permitido'),
(215, 'ingresar', 6, 8, 'Permitido'),
(220, 'consultar', 6, 8, 'Permitido'),
(225, 'modificar', 6, 8, 'Permitido'),
(230, 'incluir', 6, 8, 'Permitido'),
(235, 'eliminar', 6, 8, 'Permitido'),
(240, 'reportar', 6, 8, 'Permitido'),
(245, 'ingresar', 6, 9, 'Permitido'),
(250, 'consultar', 6, 9, 'Permitido'),
(255, 'modificar', 6, 9, 'Permitido'),
(260, 'incluir', 6, 9, 'Permitido'),
(265, 'eliminar', 6, 9, 'Permitido'),
(270, 'reportar', 6, 9, 'Permitido'),
(275, 'ingresar', 6, 10, 'Permitido'),
(280, 'consultar', 6, 10, 'Permitido'),
(285, 'modificar', 6, 10, 'Permitido'),
(290, 'incluir', 6, 10, 'Permitido'),
(295, 'eliminar', 6, 10, 'Permitido'),
(300, 'reportar', 6, 10, 'Permitido'),
(305, 'ingresar', 6, 11, 'Permitido'),
(310, 'consultar', 6, 11, 'Permitido'),
(315, 'modificar', 6, 11, 'Permitido'),
(320, 'incluir', 6, 11, 'Permitido'),
(325, 'eliminar', 6, 11, 'Permitido'),
(330, 'reportar', 6, 11, 'Permitido'),
(335, 'ingresar', 6, 12, 'Permitido'),
(340, 'consultar', 6, 12, 'Permitido'),
(345, 'modificar', 6, 12, 'Permitido'),
(350, 'incluir', 6, 12, 'Permitido'),
(355, 'eliminar', 6, 12, 'Permitido'),
(360, 'reportar', 6, 12, 'Permitido'),
(365, 'ingresar', 6, 13, 'Permitido'),
(370, 'consultar', 6, 13, 'Permitido'),
(375, 'modificar', 6, 13, 'Permitido'),
(380, 'incluir', 6, 13, 'Permitido'),
(385, 'eliminar', 6, 13, 'Permitido'),
(390, 'reportar', 6, 13, 'Permitido'),
(395, 'ingresar', 6, 14, 'Permitido'),
(400, 'consultar', 6, 14, 'Permitido'),
(405, 'modificar', 6, 14, 'Permitido'),
(410, 'incluir', 6, 14, 'Permitido'),
(415, 'eliminar', 6, 14, 'Permitido'),
(420, 'reportar', 6, 14, 'Permitido'),
(425, 'ingresar', 6, 15, 'Permitido'),
(430, 'consultar', 6, 15, 'Permitido'),
(435, 'modificar', 6, 15, 'Permitido'),
(440, 'incluir', 6, 15, 'Permitido'),
(445, 'eliminar', 6, 15, 'Permitido'),
(450, 'reportar', 6, 15, 'Permitido'),
(455, 'ingresar', 6, 16, 'Permitido'),
(460, 'consultar', 6, 16, 'Permitido'),
(465, 'modificar', 6, 16, 'Permitido'),
(470, 'incluir', 6, 16, 'Permitido'),
(475, 'eliminar', 6, 16, 'Permitido'),
(480, 'reportar', 6, 16, 'Permitido'),
(485, 'ingresar', 6, 17, 'Permitido'),
(490, 'consultar', 6, 17, 'Permitido'),
(495, 'modificar', 6, 17, 'Permitido'),
(500, 'incluir', 6, 17, 'Permitido'),
(505, 'eliminar', 6, 17, 'Permitido'),
(510, 'reportar', 6, 17, 'Permitido'),
(515, 'ingresar', 6, 18, 'Permitido'),
(520, 'consultar', 6, 18, 'Permitido'),
(525, 'modificar', 6, 18, 'Permitido'),
(530, 'incluir', 6, 18, 'Permitido'),
(535, 'eliminar', 6, 18, 'Permitido'),
(540, 'reportar', 6, 18, 'Permitido'),
(545, 'ingresar', 6, 19, 'Permitido'),
(550, 'consultar', 6, 19, 'Permitido'),
(555, 'modificar', 6, 19, 'Permitido'),
(560, 'incluir', 6, 19, 'Permitido'),
(565, 'eliminar', 6, 19, 'Permitido'),
(570, 'reportar', 6, 19, 'Permitido'),
(575, 'ingresar', 6, 20, 'Permitido'),
(580, 'consultar', 6, 20, 'Permitido'),
(585, 'modificar', 6, 20, 'Permitido'),
(590, 'incluir', 6, 20, 'Permitido'),
(595, 'eliminar', 6, 20, 'Permitido'),
(600, 'reportar', 6, 20, 'Permitido'),
(5744, 'ingresar', 1, 1, 'Permitido'),
(5745, 'consultar', 1, 1, 'Permitido'),
(5746, 'incluir', 1, 1, 'Permitido'),
(5747, 'modificar', 1, 1, 'Permitido'),
(5748, 'eliminar', 1, 1, 'Permitido'),
(5749, 'generar re', 1, 1, 'No Permitido'),
(5750, 'ingresar', 1, 2, 'Permitido'),
(5751, 'consultar', 1, 2, 'Permitido'),
(5752, 'incluir', 1, 2, 'Permitido'),
(5753, 'modificar', 1, 2, 'Permitido'),
(5754, 'eliminar', 1, 2, 'Permitido'),
(5755, 'generar re', 1, 2, 'No Permitido'),
(5756, 'ingresar', 1, 3, 'Permitido'),
(5757, 'consultar', 1, 3, 'Permitido'),
(5758, 'incluir', 1, 3, 'Permitido'),
(5759, 'modificar', 1, 3, 'Permitido'),
(5760, 'eliminar', 1, 3, 'Permitido'),
(5761, 'generar re', 1, 3, 'No Permitido'),
(5762, 'ingresar', 1, 4, 'Permitido'),
(5763, 'consultar', 1, 4, 'Permitido'),
(5764, 'incluir', 1, 4, 'Permitido'),
(5765, 'modificar', 1, 4, 'Permitido'),
(5766, 'eliminar', 1, 4, 'Permitido'),
(5767, 'generar re', 1, 4, 'No Permitido'),
(5768, 'ingresar', 1, 5, 'Permitido'),
(5769, 'consultar', 1, 5, 'Permitido'),
(5770, 'incluir', 1, 5, 'Permitido'),
(5771, 'modificar', 1, 5, 'Permitido'),
(5772, 'eliminar', 1, 5, 'Permitido'),
(5773, 'generar re', 1, 5, 'No Permitido'),
(5774, 'ingresar', 1, 6, 'Permitido'),
(5775, 'consultar', 1, 6, 'Permitido'),
(5776, 'incluir', 1, 6, 'Permitido'),
(5777, 'modificar', 1, 6, 'Permitido'),
(5778, 'eliminar', 1, 6, 'Permitido'),
(5779, 'generar re', 1, 6, 'No Permitido'),
(5780, 'ingresar', 1, 7, 'Permitido'),
(5781, 'consultar', 1, 7, 'Permitido'),
(5782, 'incluir', 1, 7, 'Permitido'),
(5783, 'modificar', 1, 7, 'Permitido'),
(5784, 'eliminar', 1, 7, 'Permitido'),
(5785, 'generar re', 1, 7, 'No Permitido'),
(5786, 'ingresar', 1, 8, 'Permitido'),
(5787, 'consultar', 1, 8, 'Permitido'),
(5788, 'incluir', 1, 8, 'Permitido'),
(5789, 'modificar', 1, 8, 'Permitido'),
(5790, 'eliminar', 1, 8, 'Permitido'),
(5791, 'generar re', 1, 8, 'No Permitido'),
(5792, 'ingresar', 1, 9, 'Permitido'),
(5793, 'consultar', 1, 9, 'Permitido'),
(5794, 'incluir', 1, 9, 'Permitido'),
(5795, 'modificar', 1, 9, 'Permitido'),
(5796, 'eliminar', 1, 9, 'Permitido'),
(5797, 'generar re', 1, 9, 'No Permitido'),
(5798, 'ingresar', 1, 10, 'Permitido'),
(5799, 'consultar', 1, 10, 'Permitido'),
(5800, 'incluir', 1, 10, 'Permitido'),
(5801, 'modificar', 1, 10, 'Permitido'),
(5802, 'eliminar', 1, 10, 'Permitido'),
(5803, 'generar re', 1, 10, 'No Permitido'),
(5804, 'ingresar', 1, 11, 'Permitido'),
(5805, 'consultar', 1, 11, 'Permitido'),
(5806, 'incluir', 1, 11, 'Permitido'),
(5807, 'modificar', 1, 11, 'Permitido'),
(5808, 'eliminar', 1, 11, 'Permitido'),
(5809, 'generar re', 1, 11, 'No Permitido'),
(5810, 'ingresar', 1, 12, 'Permitido'),
(5811, 'consultar', 1, 12, 'Permitido'),
(5812, 'incluir', 1, 12, 'Permitido'),
(5813, 'modificar', 1, 12, 'Permitido'),
(5814, 'eliminar', 1, 12, 'Permitido'),
(5815, 'generar re', 1, 12, 'No Permitido'),
(5816, 'ingresar', 1, 13, 'Permitido'),
(5817, 'consultar', 1, 13, 'Permitido'),
(5818, 'incluir', 1, 13, 'Permitido'),
(5819, 'modificar', 1, 13, 'Permitido'),
(5820, 'eliminar', 1, 13, 'Permitido'),
(5821, 'generar re', 1, 13, 'No Permitido'),
(5822, 'ingresar', 1, 14, 'Permitido'),
(5823, 'consultar', 1, 14, 'Permitido'),
(5824, 'incluir', 1, 14, 'Permitido'),
(5825, 'modificar', 1, 14, 'Permitido'),
(5826, 'eliminar', 1, 14, 'Permitido'),
(5827, 'generar re', 1, 14, 'No Permitido'),
(5828, 'ingresar', 1, 15, 'Permitido'),
(5829, 'consultar', 1, 15, 'Permitido'),
(5830, 'incluir', 1, 15, 'Permitido'),
(5831, 'modificar', 1, 15, 'Permitido'),
(5832, 'eliminar', 1, 15, 'Permitido'),
(5833, 'generar re', 1, 15, 'No Permitido'),
(5834, 'ingresar', 1, 16, 'Permitido'),
(5835, 'consultar', 1, 16, 'Permitido'),
(5836, 'incluir', 1, 16, 'Permitido'),
(5837, 'modificar', 1, 16, 'Permitido'),
(5838, 'eliminar', 1, 16, 'Permitido'),
(5839, 'generar re', 1, 16, 'No Permitido'),
(5840, 'ingresar', 1, 17, 'Permitido'),
(5841, 'consultar', 1, 17, 'Permitido'),
(5842, 'incluir', 1, 17, 'Permitido'),
(5843, 'modificar', 1, 17, 'Permitido'),
(5844, 'eliminar', 1, 17, 'Permitido'),
(5845, 'generar re', 1, 17, 'No Permitido'),
(5846, 'ingresar', 1, 18, 'Permitido'),
(5847, 'consultar', 1, 18, 'Permitido'),
(5848, 'incluir', 1, 18, 'Permitido'),
(5849, 'modificar', 1, 18, 'Permitido'),
(5850, 'eliminar', 1, 18, 'Permitido'),
(5851, 'generar re', 1, 18, 'No Permitido'),
(5852, 'ingresar', 1, 19, 'Permitido'),
(5853, 'consultar', 1, 19, 'Permitido'),
(5854, 'incluir', 1, 19, 'Permitido'),
(5855, 'modificar', 1, 19, 'Permitido'),
(5856, 'eliminar', 1, 19, 'Permitido'),
(5857, 'generar re', 1, 19, 'No Permitido'),
(5858, 'ingresar', 1, 20, 'No Permitido'),
(5859, 'consultar', 1, 20, 'No Permitido'),
(5860, 'incluir', 1, 20, 'No Permitido'),
(5861, 'modificar', 1, 20, 'No Permitido'),
(5862, 'eliminar', 1, 20, 'No Permitido'),
(5863, 'generar re', 1, 20, 'No Permitido'),
(5864, 'ingresar', 1, 21, 'No Permitido'),
(5865, 'consultar', 1, 21, 'No Permitido'),
(5866, 'incluir', 1, 21, 'No Permitido'),
(5867, 'modificar', 1, 21, 'No Permitido'),
(5868, 'eliminar', 1, 21, 'No Permitido'),
(5869, 'generar re', 1, 21, 'No Permitido'),
(5870, 'ingresar', 2, 1, 'No Permitido'),
(5871, 'consultar', 2, 1, 'No Permitido'),
(5872, 'incluir', 2, 1, 'No Permitido'),
(5873, 'modificar', 2, 1, 'No Permitido'),
(5874, 'eliminar', 2, 1, 'No Permitido'),
(5875, 'generar re', 2, 1, 'No Permitido'),
(5876, 'ingresar', 2, 2, 'Permitido'),
(5877, 'consultar', 2, 2, 'Permitido'),
(5878, 'incluir', 2, 2, 'Permitido'),
(5879, 'modificar', 2, 2, 'Permitido'),
(5880, 'eliminar', 2, 2, 'Permitido'),
(5881, 'generar re', 2, 2, 'Permitido'),
(5882, 'ingresar', 2, 3, 'Permitido'),
(5883, 'consultar', 2, 3, 'Permitido'),
(5884, 'incluir', 2, 3, 'Permitido'),
(5885, 'modificar', 2, 3, 'Permitido'),
(5886, 'eliminar', 2, 3, 'Permitido'),
(5887, 'generar re', 2, 3, 'Permitido'),
(5888, 'ingresar', 2, 4, 'No Permitido'),
(5889, 'consultar', 2, 4, 'No Permitido'),
(5890, 'incluir', 2, 4, 'No Permitido'),
(5891, 'modificar', 2, 4, 'No Permitido'),
(5892, 'eliminar', 2, 4, 'No Permitido'),
(5893, 'generar re', 2, 4, 'No Permitido'),
(5894, 'ingresar', 2, 5, 'No Permitido'),
(5895, 'consultar', 2, 5, 'No Permitido'),
(5896, 'incluir', 2, 5, 'No Permitido'),
(5897, 'modificar', 2, 5, 'No Permitido'),
(5898, 'eliminar', 2, 5, 'No Permitido'),
(5899, 'generar re', 2, 5, 'No Permitido'),
(5900, 'ingresar', 2, 6, 'No Permitido'),
(5901, 'consultar', 2, 6, 'No Permitido'),
(5902, 'incluir', 2, 6, 'No Permitido'),
(5903, 'modificar', 2, 6, 'No Permitido'),
(5904, 'eliminar', 2, 6, 'No Permitido'),
(5905, 'generar re', 2, 6, 'No Permitido'),
(5906, 'ingresar', 2, 7, 'No Permitido'),
(5907, 'consultar', 2, 7, 'No Permitido'),
(5908, 'incluir', 2, 7, 'No Permitido'),
(5909, 'modificar', 2, 7, 'No Permitido'),
(5910, 'eliminar', 2, 7, 'No Permitido'),
(5911, 'generar re', 2, 7, 'No Permitido'),
(5912, 'ingresar', 2, 8, 'No Permitido'),
(5913, 'consultar', 2, 8, 'No Permitido'),
(5914, 'incluir', 2, 8, 'No Permitido'),
(5915, 'modificar', 2, 8, 'No Permitido'),
(5916, 'eliminar', 2, 8, 'No Permitido'),
(5917, 'generar re', 2, 8, 'No Permitido'),
(5918, 'ingresar', 2, 9, 'No Permitido'),
(5919, 'consultar', 2, 9, 'No Permitido'),
(5920, 'incluir', 2, 9, 'No Permitido'),
(5921, 'modificar', 2, 9, 'No Permitido'),
(5922, 'eliminar', 2, 9, 'No Permitido'),
(5923, 'generar re', 2, 9, 'No Permitido'),
(5924, 'ingresar', 2, 10, 'No Permitido'),
(5925, 'consultar', 2, 10, 'No Permitido'),
(5926, 'incluir', 2, 10, 'No Permitido'),
(5927, 'modificar', 2, 10, 'No Permitido'),
(5928, 'eliminar', 2, 10, 'No Permitido'),
(5929, 'generar re', 2, 10, 'No Permitido'),
(5930, 'ingresar', 2, 11, 'No Permitido'),
(5931, 'consultar', 2, 11, 'No Permitido'),
(5932, 'incluir', 2, 11, 'No Permitido'),
(5933, 'modificar', 2, 11, 'No Permitido'),
(5934, 'eliminar', 2, 11, 'No Permitido'),
(5935, 'generar re', 2, 11, 'No Permitido'),
(5936, 'ingresar', 2, 12, 'No Permitido'),
(5937, 'consultar', 2, 12, 'No Permitido'),
(5938, 'incluir', 2, 12, 'No Permitido'),
(5939, 'modificar', 2, 12, 'No Permitido'),
(5940, 'eliminar', 2, 12, 'No Permitido'),
(5941, 'generar re', 2, 12, 'No Permitido'),
(5942, 'ingresar', 2, 13, 'No Permitido'),
(5943, 'consultar', 2, 13, 'No Permitido'),
(5944, 'incluir', 2, 13, 'No Permitido'),
(5945, 'modificar', 2, 13, 'No Permitido'),
(5946, 'eliminar', 2, 13, 'No Permitido'),
(5947, 'generar re', 2, 13, 'No Permitido'),
(5948, 'ingresar', 2, 14, 'No Permitido'),
(5949, 'consultar', 2, 14, 'No Permitido'),
(5950, 'incluir', 2, 14, 'No Permitido'),
(5951, 'modificar', 2, 14, 'No Permitido'),
(5952, 'eliminar', 2, 14, 'No Permitido'),
(5953, 'generar re', 2, 14, 'No Permitido'),
(5954, 'ingresar', 2, 15, 'No Permitido'),
(5955, 'consultar', 2, 15, 'No Permitido'),
(5956, 'incluir', 2, 15, 'No Permitido'),
(5957, 'modificar', 2, 15, 'No Permitido'),
(5958, 'eliminar', 2, 15, 'No Permitido'),
(5959, 'generar re', 2, 15, 'No Permitido'),
(5960, 'ingresar', 2, 16, 'No Permitido'),
(5961, 'consultar', 2, 16, 'No Permitido'),
(5962, 'incluir', 2, 16, 'No Permitido'),
(5963, 'modificar', 2, 16, 'No Permitido'),
(5964, 'eliminar', 2, 16, 'No Permitido'),
(5965, 'generar re', 2, 16, 'No Permitido'),
(5966, 'ingresar', 2, 17, 'No Permitido'),
(5967, 'consultar', 2, 17, 'No Permitido'),
(5968, 'incluir', 2, 17, 'No Permitido'),
(5969, 'modificar', 2, 17, 'No Permitido'),
(5970, 'eliminar', 2, 17, 'No Permitido'),
(5971, 'generar re', 2, 17, 'No Permitido'),
(5972, 'ingresar', 2, 18, 'No Permitido'),
(5973, 'consultar', 2, 18, 'No Permitido'),
(5974, 'incluir', 2, 18, 'No Permitido'),
(5975, 'modificar', 2, 18, 'No Permitido'),
(5976, 'eliminar', 2, 18, 'No Permitido'),
(5977, 'generar re', 2, 18, 'No Permitido'),
(5978, 'ingresar', 2, 19, 'No Permitido'),
(5979, 'consultar', 2, 19, 'No Permitido'),
(5980, 'incluir', 2, 19, 'No Permitido'),
(5981, 'modificar', 2, 19, 'No Permitido'),
(5982, 'eliminar', 2, 19, 'No Permitido'),
(5983, 'generar re', 2, 19, 'No Permitido'),
(5984, 'ingresar', 2, 20, 'No Permitido'),
(5985, 'consultar', 2, 20, 'No Permitido'),
(5986, 'incluir', 2, 20, 'No Permitido'),
(5987, 'modificar', 2, 20, 'No Permitido'),
(5988, 'eliminar', 2, 20, 'No Permitido'),
(5989, 'generar re', 2, 20, 'No Permitido'),
(5990, 'ingresar', 2, 21, 'No Permitido'),
(5991, 'consultar', 2, 21, 'No Permitido'),
(5992, 'incluir', 2, 21, 'No Permitido'),
(5993, 'modificar', 2, 21, 'No Permitido'),
(5994, 'eliminar', 2, 21, 'No Permitido'),
(5995, 'generar re', 2, 21, 'No Permitido'),
(5996, 'ingresar', 3, 1, 'No Permitido'),
(5997, 'consultar', 3, 1, 'No Permitido'),
(5998, 'incluir', 3, 1, 'No Permitido'),
(5999, 'modificar', 3, 1, 'No Permitido'),
(6000, 'eliminar', 3, 1, 'No Permitido'),
(6001, 'generar re', 3, 1, 'No Permitido'),
(6002, 'ingresar', 3, 2, 'No Permitido'),
(6003, 'consultar', 3, 2, 'No Permitido'),
(6004, 'incluir', 3, 2, 'No Permitido'),
(6005, 'modificar', 3, 2, 'No Permitido'),
(6006, 'eliminar', 3, 2, 'No Permitido'),
(6007, 'generar re', 3, 2, 'No Permitido'),
(6008, 'ingresar', 3, 3, 'No Permitido'),
(6009, 'consultar', 3, 3, 'No Permitido'),
(6010, 'incluir', 3, 3, 'No Permitido'),
(6011, 'modificar', 3, 3, 'No Permitido'),
(6012, 'eliminar', 3, 3, 'No Permitido'),
(6013, 'generar re', 3, 3, 'No Permitido'),
(6014, 'ingresar', 3, 4, 'No Permitido'),
(6015, 'consultar', 3, 4, 'No Permitido'),
(6016, 'incluir', 3, 4, 'No Permitido'),
(6017, 'modificar', 3, 4, 'No Permitido'),
(6018, 'eliminar', 3, 4, 'No Permitido'),
(6019, 'generar re', 3, 4, 'No Permitido'),
(6020, 'ingresar', 3, 5, 'No Permitido'),
(6021, 'consultar', 3, 5, 'No Permitido'),
(6022, 'incluir', 3, 5, 'No Permitido'),
(6023, 'modificar', 3, 5, 'No Permitido'),
(6024, 'eliminar', 3, 5, 'No Permitido'),
(6025, 'generar re', 3, 5, 'No Permitido'),
(6026, 'ingresar', 3, 6, 'No Permitido'),
(6027, 'consultar', 3, 6, 'No Permitido'),
(6028, 'incluir', 3, 6, 'No Permitido'),
(6029, 'modificar', 3, 6, 'No Permitido'),
(6030, 'eliminar', 3, 6, 'No Permitido'),
(6031, 'generar re', 3, 6, 'No Permitido'),
(6032, 'ingresar', 3, 7, 'No Permitido'),
(6033, 'consultar', 3, 7, 'No Permitido'),
(6034, 'incluir', 3, 7, 'No Permitido'),
(6035, 'modificar', 3, 7, 'No Permitido'),
(6036, 'eliminar', 3, 7, 'No Permitido'),
(6037, 'generar re', 3, 7, 'No Permitido'),
(6038, 'ingresar', 3, 8, 'No Permitido'),
(6039, 'consultar', 3, 8, 'No Permitido'),
(6040, 'incluir', 3, 8, 'No Permitido'),
(6041, 'modificar', 3, 8, 'No Permitido'),
(6042, 'eliminar', 3, 8, 'No Permitido'),
(6043, 'generar re', 3, 8, 'No Permitido'),
(6044, 'ingresar', 3, 9, 'No Permitido'),
(6045, 'consultar', 3, 9, 'No Permitido'),
(6046, 'incluir', 3, 9, 'No Permitido'),
(6047, 'modificar', 3, 9, 'No Permitido'),
(6048, 'eliminar', 3, 9, 'No Permitido'),
(6049, 'generar re', 3, 9, 'No Permitido'),
(6050, 'ingresar', 3, 10, 'Permitido'),
(6051, 'consultar', 3, 10, 'Permitido'),
(6052, 'incluir', 3, 10, 'Permitido'),
(6053, 'modificar', 3, 10, 'Permitido'),
(6054, 'eliminar', 3, 10, 'Permitido'),
(6055, 'generar re', 3, 10, 'No Permitido'),
(6056, 'ingresar', 3, 11, 'Permitido'),
(6057, 'consultar', 3, 11, 'Permitido'),
(6058, 'incluir', 3, 11, 'Permitido'),
(6059, 'modificar', 3, 11, 'Permitido'),
(6060, 'eliminar', 3, 11, 'Permitido'),
(6061, 'generar re', 3, 11, 'No Permitido'),
(6062, 'ingresar', 3, 12, 'Permitido'),
(6063, 'consultar', 3, 12, 'Permitido'),
(6064, 'incluir', 3, 12, 'Permitido'),
(6065, 'modificar', 3, 12, 'Permitido'),
(6066, 'eliminar', 3, 12, 'Permitido'),
(6067, 'generar re', 3, 12, 'No Permitido'),
(6068, 'ingresar', 3, 13, 'Permitido'),
(6069, 'consultar', 3, 13, 'Permitido'),
(6070, 'incluir', 3, 13, 'Permitido'),
(6071, 'modificar', 3, 13, 'Permitido'),
(6072, 'eliminar', 3, 13, 'Permitido'),
(6073, 'generar re', 3, 13, 'No Permitido'),
(6074, 'ingresar', 3, 14, 'No Permitido'),
(6075, 'consultar', 3, 14, 'No Permitido'),
(6076, 'incluir', 3, 14, 'No Permitido'),
(6077, 'modificar', 3, 14, 'No Permitido'),
(6078, 'eliminar', 3, 14, 'No Permitido'),
(6079, 'generar re', 3, 14, 'No Permitido'),
(6080, 'ingresar', 3, 15, 'No Permitido'),
(6081, 'consultar', 3, 15, 'No Permitido'),
(6082, 'incluir', 3, 15, 'No Permitido'),
(6083, 'modificar', 3, 15, 'No Permitido'),
(6084, 'eliminar', 3, 15, 'No Permitido'),
(6085, 'generar re', 3, 15, 'No Permitido'),
(6086, 'ingresar', 3, 16, 'No Permitido'),
(6087, 'consultar', 3, 16, 'No Permitido'),
(6088, 'incluir', 3, 16, 'No Permitido'),
(6089, 'modificar', 3, 16, 'No Permitido'),
(6090, 'eliminar', 3, 16, 'No Permitido'),
(6091, 'generar re', 3, 16, 'No Permitido'),
(6092, 'ingresar', 3, 17, 'No Permitido'),
(6093, 'consultar', 3, 17, 'No Permitido'),
(6094, 'incluir', 3, 17, 'No Permitido'),
(6095, 'modificar', 3, 17, 'No Permitido'),
(6096, 'eliminar', 3, 17, 'No Permitido'),
(6097, 'generar re', 3, 17, 'No Permitido'),
(6098, 'ingresar', 3, 18, 'No Permitido'),
(6099, 'consultar', 3, 18, 'No Permitido'),
(6100, 'incluir', 3, 18, 'No Permitido'),
(6101, 'modificar', 3, 18, 'No Permitido'),
(6102, 'eliminar', 3, 18, 'No Permitido'),
(6103, 'generar re', 3, 18, 'No Permitido'),
(6104, 'ingresar', 3, 19, 'No Permitido'),
(6105, 'consultar', 3, 19, 'No Permitido'),
(6106, 'incluir', 3, 19, 'No Permitido'),
(6107, 'modificar', 3, 19, 'No Permitido'),
(6108, 'eliminar', 3, 19, 'No Permitido'),
(6109, 'generar re', 3, 19, 'No Permitido'),
(6110, 'ingresar', 3, 20, 'No Permitido'),
(6111, 'consultar', 3, 20, 'No Permitido'),
(6112, 'incluir', 3, 20, 'No Permitido'),
(6113, 'modificar', 3, 20, 'No Permitido'),
(6114, 'eliminar', 3, 20, 'No Permitido'),
(6115, 'generar re', 3, 20, 'No Permitido'),
(6116, 'ingresar', 3, 21, 'No Permitido'),
(6117, 'consultar', 3, 21, 'No Permitido'),
(6118, 'incluir', 3, 21, 'No Permitido'),
(6119, 'modificar', 3, 21, 'No Permitido'),
(6120, 'eliminar', 3, 21, 'No Permitido'),
(6121, 'generar re', 3, 21, 'No Permitido'),
(6122, 'ingresar', 4, 1, 'No Permitido'),
(6123, 'consultar', 4, 1, 'No Permitido'),
(6124, 'incluir', 4, 1, 'No Permitido'),
(6125, 'modificar', 4, 1, 'No Permitido'),
(6126, 'eliminar', 4, 1, 'No Permitido'),
(6127, 'generar re', 4, 1, 'No Permitido'),
(6128, 'ingresar', 4, 2, 'No Permitido'),
(6129, 'consultar', 4, 2, 'No Permitido'),
(6130, 'incluir', 4, 2, 'No Permitido'),
(6131, 'modificar', 4, 2, 'No Permitido'),
(6132, 'eliminar', 4, 2, 'No Permitido'),
(6133, 'generar re', 4, 2, 'No Permitido'),
(6134, 'ingresar', 4, 3, 'No Permitido'),
(6135, 'consultar', 4, 3, 'No Permitido'),
(6136, 'incluir', 4, 3, 'No Permitido'),
(6137, 'modificar', 4, 3, 'No Permitido'),
(6138, 'eliminar', 4, 3, 'No Permitido'),
(6139, 'generar re', 4, 3, 'No Permitido'),
(6140, 'ingresar', 4, 4, 'No Permitido'),
(6141, 'consultar', 4, 4, 'No Permitido'),
(6142, 'incluir', 4, 4, 'No Permitido'),
(6143, 'modificar', 4, 4, 'No Permitido'),
(6144, 'eliminar', 4, 4, 'No Permitido'),
(6145, 'generar re', 4, 4, 'No Permitido'),
(6146, 'ingresar', 4, 5, 'No Permitido'),
(6147, 'consultar', 4, 5, 'No Permitido'),
(6148, 'incluir', 4, 5, 'No Permitido'),
(6149, 'modificar', 4, 5, 'No Permitido'),
(6150, 'eliminar', 4, 5, 'No Permitido'),
(6151, 'generar re', 4, 5, 'No Permitido'),
(6152, 'ingresar', 4, 6, 'No Permitido'),
(6153, 'consultar', 4, 6, 'No Permitido'),
(6154, 'incluir', 4, 6, 'No Permitido'),
(6155, 'modificar', 4, 6, 'No Permitido'),
(6156, 'eliminar', 4, 6, 'No Permitido'),
(6157, 'generar re', 4, 6, 'No Permitido'),
(6158, 'ingresar', 4, 7, 'No Permitido'),
(6159, 'consultar', 4, 7, 'No Permitido'),
(6160, 'incluir', 4, 7, 'No Permitido'),
(6161, 'modificar', 4, 7, 'No Permitido'),
(6162, 'eliminar', 4, 7, 'No Permitido'),
(6163, 'generar re', 4, 7, 'No Permitido'),
(6164, 'ingresar', 4, 8, 'No Permitido'),
(6165, 'consultar', 4, 8, 'No Permitido'),
(6166, 'incluir', 4, 8, 'No Permitido'),
(6167, 'modificar', 4, 8, 'No Permitido'),
(6168, 'eliminar', 4, 8, 'No Permitido'),
(6169, 'generar re', 4, 8, 'No Permitido'),
(6170, 'ingresar', 4, 9, 'No Permitido'),
(6171, 'consultar', 4, 9, 'No Permitido'),
(6172, 'incluir', 4, 9, 'No Permitido'),
(6173, 'modificar', 4, 9, 'No Permitido'),
(6174, 'eliminar', 4, 9, 'No Permitido'),
(6175, 'generar re', 4, 9, 'No Permitido'),
(6176, 'ingresar', 4, 10, 'No Permitido'),
(6177, 'consultar', 4, 10, 'No Permitido'),
(6178, 'incluir', 4, 10, 'No Permitido'),
(6179, 'modificar', 4, 10, 'No Permitido'),
(6180, 'eliminar', 4, 10, 'No Permitido'),
(6181, 'generar re', 4, 10, 'No Permitido'),
(6182, 'ingresar', 4, 11, 'No Permitido'),
(6183, 'consultar', 4, 11, 'No Permitido'),
(6184, 'incluir', 4, 11, 'No Permitido'),
(6185, 'modificar', 4, 11, 'No Permitido'),
(6186, 'eliminar', 4, 11, 'No Permitido'),
(6187, 'generar re', 4, 11, 'No Permitido'),
(6188, 'ingresar', 4, 12, 'No Permitido'),
(6189, 'consultar', 4, 12, 'No Permitido'),
(6190, 'incluir', 4, 12, 'No Permitido'),
(6191, 'modificar', 4, 12, 'No Permitido'),
(6192, 'eliminar', 4, 12, 'No Permitido'),
(6193, 'generar re', 4, 12, 'No Permitido'),
(6194, 'ingresar', 4, 13, 'No Permitido'),
(6195, 'consultar', 4, 13, 'No Permitido'),
(6196, 'incluir', 4, 13, 'No Permitido'),
(6197, 'modificar', 4, 13, 'No Permitido'),
(6198, 'eliminar', 4, 13, 'No Permitido'),
(6199, 'generar re', 4, 13, 'No Permitido'),
(6200, 'ingresar', 4, 14, 'No Permitido'),
(6201, 'consultar', 4, 14, 'No Permitido'),
(6202, 'incluir', 4, 14, 'No Permitido'),
(6203, 'modificar', 4, 14, 'No Permitido'),
(6204, 'eliminar', 4, 14, 'No Permitido'),
(6205, 'generar re', 4, 14, 'No Permitido'),
(6206, 'ingresar', 4, 15, 'No Permitido'),
(6207, 'consultar', 4, 15, 'No Permitido'),
(6208, 'incluir', 4, 15, 'No Permitido'),
(6209, 'modificar', 4, 15, 'No Permitido'),
(6210, 'eliminar', 4, 15, 'No Permitido'),
(6211, 'generar re', 4, 15, 'No Permitido'),
(6212, 'ingresar', 4, 16, 'No Permitido'),
(6213, 'consultar', 4, 16, 'No Permitido'),
(6214, 'incluir', 4, 16, 'No Permitido'),
(6215, 'modificar', 4, 16, 'No Permitido'),
(6216, 'eliminar', 4, 16, 'No Permitido'),
(6217, 'generar re', 4, 16, 'No Permitido'),
(6218, 'ingresar', 4, 17, 'No Permitido'),
(6219, 'consultar', 4, 17, 'No Permitido'),
(6220, 'incluir', 4, 17, 'No Permitido'),
(6221, 'modificar', 4, 17, 'No Permitido'),
(6222, 'eliminar', 4, 17, 'No Permitido'),
(6223, 'generar re', 4, 17, 'No Permitido'),
(6224, 'ingresar', 4, 18, 'No Permitido'),
(6225, 'consultar', 4, 18, 'No Permitido'),
(6226, 'incluir', 4, 18, 'No Permitido'),
(6227, 'modificar', 4, 18, 'No Permitido'),
(6228, 'eliminar', 4, 18, 'No Permitido'),
(6229, 'generar re', 4, 18, 'No Permitido'),
(6230, 'ingresar', 4, 19, 'No Permitido'),
(6231, 'consultar', 4, 19, 'No Permitido'),
(6232, 'incluir', 4, 19, 'No Permitido'),
(6233, 'modificar', 4, 19, 'No Permitido'),
(6234, 'eliminar', 4, 19, 'No Permitido'),
(6235, 'generar re', 4, 19, 'No Permitido'),
(6236, 'ingresar', 4, 20, 'No Permitido'),
(6237, 'consultar', 4, 20, 'No Permitido'),
(6238, 'incluir', 4, 20, 'No Permitido'),
(6239, 'modificar', 4, 20, 'No Permitido'),
(6240, 'eliminar', 4, 20, 'No Permitido'),
(6241, 'generar re', 4, 20, 'No Permitido'),
(6242, 'ingresar', 4, 21, 'No Permitido'),
(6243, 'consultar', 4, 21, 'No Permitido'),
(6244, 'incluir', 4, 21, 'No Permitido'),
(6245, 'modificar', 4, 21, 'No Permitido'),
(6246, 'eliminar', 4, 21, 'No Permitido'),
(6247, 'generar re', 4, 21, 'No Permitido');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_recuperar`
--

CREATE TABLE `tbl_recuperar` (
  `id_recuperar` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `expiracion` datetime NOT NULL,
  `utilizado` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_rol`
--

CREATE TABLE `tbl_rol` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_rol`
--

INSERT INTO `tbl_rol` (`id_rol`, `nombre_rol`) VALUES
(1, 'Administrador'),
(2, 'Almacenista'),
(3, 'Cliente'),
(4, 'Desarrollador'),
(6, 'SuperUsuario');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tbl_usuarios`
--

CREATE TABLE `tbl_usuarios` (
  `id_usuario` int(11) NOT NULL,
  `username` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `cedula` varchar(8) DEFAULT NULL,
  `id_rol` int(11) NOT NULL,
  `correo` varchar(50) DEFAULT NULL,
  `nombres` varchar(50) DEFAULT NULL,
  `apellidos` varchar(50) DEFAULT NULL,
  `telefono` varchar(15) DEFAULT NULL,
  `estatus` enum('habilitado','inhabilitado') NOT NULL DEFAULT 'habilitado',
  `foto_perfil` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tbl_usuarios`
--

INSERT INTO `tbl_usuarios` (`id_usuario`, `username`, `password`, `cedula`, `id_rol`, `correo`, `nombres`, `apellidos`, `telefono`, `estatus`, `foto_perfil`) VALUES
(3, 'Diego', '$2y$10$bJfY45blf5qV66WzNf5.OOTPFjgCEePpBz07GQUc3B0qlKMNzJd8W', '30123123', 6, 'ejemplo@gmail.com', 'Diego', 'Compa', '0414-575-3363', 'habilitado', NULL),
(4, 'Simon', '$2y$10$bJfY45blf5qV66WzNf5.OOTPFjgCEePpBz07GQUc3B0qlKMNzJd8W', '29123123', 3, 'ejemplo@gmail.com', 'Simon Freitez', 'Cliente', '0414-000-0000', 'habilitado', NULL),
(5, 'SuperUsu', '$2y$10$w7nQw5p6Qw6nQw5p6Qw6nOQw5p6Qw6nQw5p6Qw6nQw5p6Qw6nQw6n', '30123456', 6, 'ejemplo@gmail.com', 'Diego Andres', 'Lopez Vivas', '0414-575-3363', 'habilitado', NULL),
(7, 'Ben10', '$2y$10$xYFm.SoVzcTO1Z8VNeoP.eVpI.s6YZ54sZqoN20ogR/n7uTHNf0yG', '30123789', 1, 'ggy@gmail.com', 'Pa', 'nose', '0414-000-0000', 'habilitado', NULL),
(8, 'DiegoS', '$2y$10$h1a1yYaAKtVeLks/nXvTkuj406CuHsFs/U.kevnBEac47PdG93WT6', '30456456', 1, 'ggy@gmail.com', 'Diego', 'Compa Vendedor', '0414-575-3363', 'habilitado', NULL),
(9, 'CasaLai', '$2y$10$KXRg/AUD.9Y7KubEvzy71e5dDR1GvGNy23XegAYwLjYWOBdcxzqx2', '30456789', 6, 'diego0510lopez@gmail.com', 'Casa', 'Lai', '0414-575-3363', 'habilitado', NULL),
(10, 'Gmujica', '$2y$10$iZNeKonr6qr.P109rwgEFOCc7Y.0E47sD/88YfB.Jyx6niGpf4CQi', '29958676', 3, 'fhhggjjkkkj@gmail.com', 'Gabriel', 'Mujica', '0424-678-8765', 'habilitado', NULL),
(11, 'edithu', '$2y$10$YfEtJDHi9CNZR1Xpx7J9Ze8CMx3g99o1dJ3h.RRZPXqlJjxWbT5Fi', '10844463', 3, 'urdavedith.pnfi@gmail.com', 'Edith', 'Urdaneta', '0416-747-4336', 'habilitado', NULL),
(15, 'Pato', '$2y$10$2OgFNgMxHcDgqjCvfCHsVOYLkc6Qq3QqSalImRPOaP51loMFpFHsa', '5322432', 1, 'diego0510lopez@gmail.com', 'Diego', 'Lopez', '0414-575-3363', 'inhabilitado', NULL),
(16, 'Darckort', '$2y$10$1xavkBCftrr0QLclZTk77eduhFhvGa3uWiuCva2qHKMQ/otwoGYaa', '28406324', 6, 'darckortgame@gmail.com', 'Braynt de Jesus', 'Medina Bricno', '0426-150-4714', 'habilitado', 'avatar_68edb74fbaaa4_file_000000005b786246b5a28d3be60c28d6.png'),
(17, 'Juanlai', '$2y$10$NAPB.g70SJM0juLf9jTha.LbRejgTZFWD87GfYgATpp2k./KfciK2', '25874668', 1, 'juanlai@gmail.com', 'Juan', 'Lai', '0412-125-6985', 'habilitado', NULL);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `tbl_alertas`
--
ALTER TABLE `tbl_alertas`
  ADD PRIMARY KEY (`id_alerta`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_bitacora`
--
ALTER TABLE `tbl_bitacora`
  ADD PRIMARY KEY (`id_bitacora`),
  ADD KEY `id_modulo` (`id_modulo`,`id_usuario`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_modulos`
--
ALTER TABLE `tbl_modulos`
  ADD PRIMARY KEY (`id_modulo`);

--
-- Indices de la tabla `tbl_notificaciones`
--
ALTER TABLE `tbl_notificaciones`
  ADD PRIMARY KEY (`id_notificacion`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_permisos`
--
ALTER TABLE `tbl_permisos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_rol` (`id_rol`,`id_modulo`),
  ADD KEY `id_modulo` (`id_modulo`);

--
-- Indices de la tabla `tbl_recuperar`
--
ALTER TABLE `tbl_recuperar`
  ADD PRIMARY KEY (`id_recuperar`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `tbl_rol`
--
ALTER TABLE `tbl_rol`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  ADD PRIMARY KEY (`id_usuario`),
  ADD KEY `id_rol` (`id_rol`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `tbl_alertas`
--
ALTER TABLE `tbl_alertas`
  MODIFY `id_alerta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_bitacora`
--
ALTER TABLE `tbl_bitacora`
  MODIFY `id_bitacora` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=283;

--
-- AUTO_INCREMENT de la tabla `tbl_modulos`
--
ALTER TABLE `tbl_modulos`
  MODIFY `id_modulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `tbl_notificaciones`
--
ALTER TABLE `tbl_notificaciones`
  MODIFY `id_notificacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `tbl_permisos`
--
ALTER TABLE `tbl_permisos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6248;

--
-- AUTO_INCREMENT de la tabla `tbl_recuperar`
--
ALTER TABLE `tbl_recuperar`
  MODIFY `id_recuperar` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `tbl_rol`
--
ALTER TABLE `tbl_rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `tbl_alertas`
--
ALTER TABLE `tbl_alertas`
  ADD CONSTRAINT `tbl_alertas_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_bitacora`
--
ALTER TABLE `tbl_bitacora`
  ADD CONSTRAINT `tbl_bitacora_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_bitacora_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `tbl_modulos` (`id_modulo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_notificaciones`
--
ALTER TABLE `tbl_notificaciones`
  ADD CONSTRAINT `fk_notif_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_permisos`
--
ALTER TABLE `tbl_permisos`
  ADD CONSTRAINT `tbl_permisos_ibfk_1` FOREIGN KEY (`id_rol`) REFERENCES `tbl_rol` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tbl_permisos_ibfk_2` FOREIGN KEY (`id_modulo`) REFERENCES `tbl_modulos` (`id_modulo`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_recuperar`
--
ALTER TABLE `tbl_recuperar`
  ADD CONSTRAINT `tbl_recuperar_ibfk_1` FOREIGN KEY (`id_usuario`) REFERENCES `tbl_usuarios` (`id_usuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `tbl_usuarios`
--
ALTER TABLE `tbl_usuarios`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`id_rol`) REFERENCES `tbl_rol` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
