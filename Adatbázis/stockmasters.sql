-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Gép: localhost
-- Létrehozás ideje: 2026. Feb 22. 09:19
-- Kiszolgáló verziója: 8.0.44
-- PHP verzió: 8.2.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Adatbázis: `stockmasters`
--

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `assets`
--

CREATE TABLE `assets` (
  `ID` int NOT NULL,
  `Symbol` varchar(32) NOT NULL,
  `Name` varchar(255) NOT NULL,
  `IsTradable` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- A tábla adatainak kiíratása `assets`
--

INSERT INTO `assets` (`ID`, `Symbol`, `Name`, `IsTradable`) VALUES
(170, 'AAPL', 'Apple Inc.', 1),
(171, 'MSFT', 'Microsoft Corporation', 1),
(172, 'GOOGL', 'Alphabet Inc. Class A', 1),
(173, 'GOOG', 'Alphabet Inc. Class C', 1),
(174, 'AMZN', 'Amazon.com Inc.', 1),
(175, 'META', 'Meta Platforms Inc.', 1),
(176, 'TSLA', 'Tesla Inc.', 1),
(177, 'NVDA', 'NVIDIA', 1),
(178, 'JPM', 'JPMorgan Chase & Co.', 1),
(179, 'JNJ', 'Johnson & Johnson', 1),
(180, 'V', 'Visa Inc.', 1),
(181, 'MA', 'Mastercard Incorporated', 1),
(182, 'HD', 'Home Depot Inc.', 1),
(183, 'DIS', 'Walt Disney Company', 1),
(184, 'NFLX', 'Netflix Inc.', 1),
(185, 'ADBE', 'Adobe Inc.', 1),
(186, 'CSCO', 'Cisco Systems Inc.', 1),
(187, 'INTC', 'Intel', 1),
(188, 'ORCL', 'Oracle', 1),
(189, 'PEP', 'PepsiCo Inc.', 1),
(190, 'KO', 'Coca-Cola Company', 1),
(191, 'PFE', 'Pfizer Inc.', 1),
(192, 'MRK', 'Merck & Co. Inc.', 1),
(193, 'BAC', 'Bank of America Corporation', 1),
(194, 'C', 'Citigroup Inc.', 1),
(195, 'XOM', 'Exxon Mobil Corporation', 1),
(196, 'CVX', 'Chevron Corporation', 1),
(197, 'WMT', 'Walmart Inc.', 1),
(198, 'NKE', 'Nike Inc.', 1),
(199, 'MCD', 'McDonald’s Corporation', 1),
(200, 'T', 'AT&T Inc.', 1),
(201, 'CRM', 'Salesforce Inc.', 1),
(202, 'AVGO', 'Broadcom Inc.', 1),
(203, 'TXN', 'Texas Instruments Incorporated', 1),
(204, 'AMD', 'Advanced Micro Devices Inc.', 1),
(205, 'QCOM', 'Qualcomm Incorporated', 1),
(206, 'PYPL', 'PayPal Holdings Inc.', 1),
(207, 'SHOP', 'Shopify Inc.', 1),
(208, 'UBER', 'Uber Technologies Inc.', 1),
(209, 'GS', 'Goldman Sachs Group Inc.', 1),
(210, 'MS', 'Morgan Stanley', 1),
(211, 'BLK', 'BlackRock Inc.', 1),
(212, 'UNH', 'UnitedHealth Group Incorporated', 1),
(213, 'UPS', 'United Parcel Service Inc.', 1),
(214, 'FDX', 'FedEx Corporation', 1),
(215, 'BA', 'Boeing Company', 1),
(216, 'CAT', 'Caterpillar Inc.', 1),
(217, 'GE', 'General Electric Company', 1),
(218, 'GM', 'General Motors Company', 1),
(219, 'F', 'Ford Motor Company', 1),
(220, 'WFC', 'Wells Fargo & Company', 1),
(221, 'COST', 'Costco Wholesale Corporation', 1),
(222, 'TMO', 'Thermo Fisher Scientific Inc.', 1),
(223, 'HON', 'Honeywell International Inc.', 1),
(224, 'IBM', 'International Business Machines Corporation', 1),
(225, 'SPY', 'SPDR S&P 500 ETF Trust', 1);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `candles`
--

CREATE TABLE `candles` (
  `id` bigint UNSIGNED NOT NULL,
  `symbol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tf` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `open_ts` bigint UNSIGNED NOT NULL,
  `close_ts` bigint UNSIGNED NOT NULL,
  `open` decimal(16,6) NOT NULL,
  `high` decimal(16,6) NOT NULL,
  `low` decimal(16,6) NOT NULL,
  `close` decimal(16,6) NOT NULL,
  `ticks` bigint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- A tábla adatainak kiíratása `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_02_20_000001_create_price_ticks_table', 1),
(2, '2026_02_20_000002_create_candles_table', 1);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `notifications`
--

CREATE TABLE `notifications` (
  `ID` int NOT NULL,
  `UserID` int DEFAULT NULL,
  `Title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `CreatedAt` datetime DEFAULT NULL,
  `IsRead` bit(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `positions`
--

CREATE TABLE `positions` (
  `ID` int NOT NULL,
  `UserID` int NOT NULL,
  `AssetID` int NOT NULL,
  `OpenTime` datetime NOT NULL,
  `CloseTime` datetime DEFAULT NULL,
  `Quantity` decimal(12,2) NOT NULL,
  `EntryPrice` decimal(12,4) NOT NULL,
  `ExitPrice` decimal(12,4) DEFAULT NULL,
  `PositionType` enum('buy','sell') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `IsOpen` tinyint(1) NOT NULL DEFAULT '1',
  `ProfitLoss` decimal(12,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `positions`
--

INSERT INTO `positions` (`ID`, `UserID`, `AssetID`, `OpenTime`, `CloseTime`, `Quantity`, `EntryPrice`, `ExitPrice`, `PositionType`, `IsOpen`, `ProfitLoss`) VALUES
(1, 2, 1, '2025-12-10 09:01:13', NULL, 1.00, 277.1800, NULL, 'buy', 1, NULL),
(2, 2, 1, '2025-12-10 09:01:18', NULL, 1.00, 277.1800, NULL, 'buy', 1, NULL),
(3, 2, 1, '2025-12-10 09:01:19', NULL, 1.00, 277.1800, NULL, 'sell', 1, NULL),
(4, 2, 1, '2025-12-10 09:01:20', NULL, 1.00, 277.1800, NULL, 'sell', 1, NULL),
(5, 2, 1, '2025-12-10 09:01:21', NULL, 1.00, 277.1800, NULL, 'sell', 1, NULL),
(6, 2, 1, '2025-12-10 09:01:22', NULL, 1.00, 277.1800, NULL, 'sell', 1, NULL),
(7, 2, 1, '2025-12-10 09:01:23', NULL, 1.00, 277.1800, NULL, 'sell', 1, NULL),
(8, 2, 1, '2025-12-10 09:01:24', NULL, 1.00, 277.1800, NULL, 'buy', 1, NULL),
(9, 2, 1, '2025-12-10 09:01:27', NULL, 10.00, 277.1800, NULL, 'buy', 1, NULL),
(10, 2, 1, '2025-12-10 09:01:28', NULL, 10.00, 277.1800, NULL, 'buy', 1, NULL),
(11, 2, 1, '2025-12-10 09:01:29', NULL, 10.00, 277.1800, NULL, 'buy', 1, NULL),
(12, 2, 1, '2025-12-10 09:01:32', NULL, 10.00, 277.1800, NULL, 'sell', 1, NULL),
(13, 2, 1, '2025-12-10 09:01:33', NULL, 10.00, 277.1800, NULL, 'sell', 1, NULL),
(14, 2, 1, '2025-12-10 09:01:33', NULL, 10.00, 277.1800, NULL, 'sell', 1, NULL),
(15, 2, 1, '2025-12-10 09:01:33', NULL, 10.00, 277.1800, NULL, 'sell', 1, NULL),
(16, 2, 1, '2025-12-10 09:01:33', NULL, 10.00, 277.1800, NULL, 'sell', 1, NULL),
(17, 2, 1, '2025-12-10 09:01:33', NULL, 10.00, 277.1800, NULL, 'sell', 1, NULL),
(18, 2, 1, '2025-12-10 09:01:34', NULL, 10.00, 277.1800, NULL, 'sell', 1, NULL),
(19, 2, 1, '2025-12-10 09:01:34', NULL, 10.00, 277.1800, NULL, 'sell', 1, NULL),
(20, 2, 1, '2025-12-10 09:01:34', NULL, 10.00, 277.1800, NULL, 'sell', 1, NULL),
(21, 2, 1, '2025-12-10 09:01:34', NULL, 10.00, 277.1800, NULL, 'sell', 1, NULL),
(22, 2, 4, '2025-12-10 09:04:44', NULL, 1.00, 317.7500, NULL, 'buy', 1, NULL),
(23, 2, 4, '2025-12-10 09:04:46', NULL, 1.00, 317.7500, NULL, 'buy', 1, NULL),
(24, 2, 4, '2025-12-10 09:04:47', NULL, 1.00, 317.7500, NULL, 'buy', 1, NULL),
(25, 2, 4, '2025-12-10 09:04:47', NULL, 1.00, 317.7500, NULL, 'buy', 1, NULL),
(26, 2, 4, '2025-12-10 09:04:48', NULL, 1.00, 317.7500, NULL, 'buy', 1, NULL),
(27, 2, 4, '2025-12-10 09:04:48', NULL, 1.00, 317.7500, NULL, 'buy', 1, NULL),
(28, 2, 4, '2025-12-10 09:04:52', NULL, 1.00, 317.7500, NULL, 'sell', 1, NULL),
(29, 2, 4, '2025-12-10 09:04:52', NULL, 1.00, 317.7500, NULL, 'sell', 1, NULL),
(30, 2, 4, '2025-12-10 09:04:53', NULL, 1.00, 317.7500, NULL, 'sell', 1, NULL),
(31, 2, 4, '2025-12-10 09:04:53', NULL, 1.00, 317.7500, NULL, 'sell', 1, NULL),
(32, 2, 4, '2025-12-10 09:04:53', NULL, 1.00, 317.7500, NULL, 'sell', 1, NULL),
(33, 2, 3, '2025-12-10 09:16:35', NULL, 1.00, 317.0800, NULL, 'buy', 1, NULL),
(34, 2, 3, '2025-12-10 09:16:38', NULL, 1.00, 317.0800, NULL, 'sell', 1, NULL),
(35, 2, 3, '2025-12-10 09:16:42', NULL, 1.00, 317.0800, NULL, 'buy', 1, NULL),
(36, 2, 3, '2025-12-10 09:16:43', NULL, 1.00, 317.0800, NULL, 'sell', 1, NULL),
(37, 2, 3, '2025-12-10 09:16:45', NULL, 1.00, 317.0800, NULL, 'buy', 1, NULL),
(38, 2, 3, '2025-12-10 09:16:45', NULL, 1.00, 317.0800, NULL, 'sell', 1, NULL),
(39, 2, 3, '2025-12-10 09:16:46', NULL, 1.00, 317.0800, NULL, 'buy', 1, NULL),
(40, 2, 3, '2025-12-10 09:16:47', NULL, 1.00, 317.0800, NULL, 'sell', 1, NULL),
(41, 2, 3, '2025-12-10 09:16:48', NULL, 1.00, 317.0800, NULL, 'buy', 1, NULL),
(42, 2, 4, '2025-12-10 09:33:04', NULL, 1.00, 317.7500, NULL, 'sell', 1, NULL),
(43, 2, 3, '2025-12-10 09:33:07', NULL, 1.00, 317.0800, NULL, 'sell', 1, NULL),
(44, 2, 40, '2025-12-10 09:33:19', NULL, 35.00, 221.6200, NULL, 'buy', 1, NULL),
(45, 2, 1, '2025-12-10 09:34:27', NULL, 15.00, 277.1800, NULL, 'buy', 1, NULL),
(46, 2, 1, '2025-12-10 09:34:31', NULL, 15.00, 277.1800, NULL, 'buy', 1, NULL),
(47, 2, 1, '2025-12-10 09:39:52', NULL, 1.00, 277.1800, NULL, 'buy', 1, NULL),
(48, 2, 1, '2025-12-10 09:40:29', NULL, 1.00, 277.1800, NULL, 'buy', 1, NULL),
(49, 2, 5, '2025-12-10 09:41:34', NULL, 1.00, 227.9200, NULL, 'buy', 1, NULL),
(50, 2, 5, '2025-12-10 09:41:35', NULL, 1.00, 227.9200, NULL, 'buy', 1, NULL),
(51, 2, 5, '2025-12-10 09:41:36', NULL, 1.00, 227.9200, NULL, 'buy', 1, NULL),
(52, 2, 5, '2025-12-10 09:41:36', NULL, 1.00, 227.9200, NULL, 'buy', 1, NULL),
(53, 2, 5, '2025-12-10 09:41:36', NULL, 1.00, 227.9200, NULL, 'buy', 1, NULL),
(54, 2, 5, '2025-12-10 09:41:36', NULL, 1.00, 227.9200, NULL, 'buy', 1, NULL),
(55, 2, 5, '2025-12-10 09:41:36', NULL, 1.00, 227.9200, NULL, 'buy', 1, NULL),
(56, 2, 5, '2025-12-10 09:41:37', NULL, 1.00, 227.9200, NULL, 'buy', 1, NULL),
(57, 2, 5, '2025-12-10 09:41:37', NULL, 1.00, 227.9200, NULL, 'buy', 1, NULL),
(58, 2, 5, '2025-12-10 09:41:37', NULL, 1.00, 227.9200, NULL, 'buy', 1, NULL),
(59, 2, 5, '2025-12-12 11:06:24', NULL, 10.00, 230.2800, NULL, 'sell', 1, NULL),
(60, 2, 170, '2026-01-20 09:27:58', '2026-01-23 09:10:13', 15.00, 255.5300, 248.3500, 'buy', 0, -107.70),
(61, 2, 170, '2026-01-20 09:28:00', '2026-01-23 09:10:13', 15.00, 255.5300, 248.3500, 'sell', 0, 107.70),
(62, 2, 170, '2026-01-20 09:28:02', '2026-01-23 09:10:13', 15.00, 255.5300, 248.3500, 'buy', 0, -107.70),
(63, 2, 170, '2026-01-20 09:28:04', '2026-01-23 09:10:13', 15.00, 255.5300, 248.3500, 'sell', 0, 107.70),
(64, 2, 170, '2026-01-20 09:28:06', '2026-01-23 09:10:13', 15.00, 255.5300, 248.3500, 'buy', 0, -107.70),
(65, 2, 170, '2026-01-20 09:28:13', '2026-01-23 09:10:13', 5.00, 255.5300, 248.3500, 'sell', 0, 35.90),
(66, 2, 170, '2026-01-20 09:28:15', '2026-01-23 09:10:13', 5.00, 255.5300, 248.3500, 'sell', 0, 35.90),
(67, 2, 170, '2026-01-20 09:28:16', '2026-01-23 09:10:13', 5.00, 255.5300, 248.3500, 'sell', 0, 35.90),
(68, 2, 170, '2026-01-20 09:28:23', '2026-01-23 09:10:13', 15.00, 255.5300, 248.3500, 'buy', 0, -107.70),
(69, 2, 170, '2026-01-20 09:41:24', '2026-01-23 09:10:13', 15.00, 255.5300, 248.3500, 'sell', 0, 107.70),
(70, 2, 170, '2026-01-20 09:41:27', '2026-01-23 09:10:13', 15.00, 255.5300, 248.3500, 'buy', 0, -107.70),
(71, 2, 170, '2026-01-20 09:41:29', '2026-01-23 09:10:13', 15.00, 255.5300, 248.3500, 'sell', 0, 107.70),
(72, 2, 185, '2026-01-20 09:42:29', '2026-01-23 08:52:32', 15.00, 296.1200, 299.7300, 'buy', 0, 54.15),
(73, 2, 185, '2026-01-20 09:42:48', '2026-01-23 08:52:32', 15.00, 296.1200, 299.7300, 'sell', 0, -54.15),
(74, 2, 170, '2026-01-20 09:46:37', '2026-01-23 09:10:13', 2.00, 255.5300, 248.3500, 'buy', 0, -14.36),
(75, 2, 185, '2026-01-20 09:46:44', '2026-01-23 08:52:32', 10.00, 296.1200, 299.7300, 'buy', 0, 36.10),
(76, 2, 170, '2026-01-20 09:55:57', '2026-01-23 09:10:13', 2.00, 255.5300, 248.3500, 'sell', 0, 14.36),
(77, 2, 215, '2026-01-23 08:39:00', '2026-01-23 09:10:27', 15.00, 251.4100, 251.4100, 'buy', 0, 0.00),
(78, 2, 215, '2026-01-23 08:39:01', '2026-01-23 09:10:27', 15.00, 251.4100, 251.4100, 'sell', 0, 0.00),
(79, 2, 174, '2026-01-23 08:39:05', '2026-01-23 08:47:58', 15.00, 234.3400, 234.3400, 'buy', 0, 0.00),
(80, 2, 174, '2026-01-23 08:39:06', '2026-01-23 08:47:58', 15.00, 234.3400, 234.3400, 'sell', 0, 0.00),
(81, 2, 170, '2026-01-23 08:39:14', '2026-01-23 09:10:13', 15.00, 248.3500, 248.3500, 'buy', 0, 0.00),
(82, 2, 170, '2026-01-23 08:39:18', '2026-01-23 09:10:13', 15.00, 248.3500, 248.3500, 'sell', 0, 0.00),
(83, 2, 170, '2026-01-23 08:39:23', '2026-01-23 09:10:13', 15.00, 248.3500, 248.3500, 'buy', 0, 0.00),
(84, 2, 170, '2026-01-23 08:39:28', '2026-01-23 09:10:13', 15.00, 248.3500, 248.3500, 'sell', 0, 0.00),
(85, 2, 194, '2026-01-23 08:39:32', NULL, 15.00, 115.6600, NULL, 'buy', 1, NULL),
(86, 2, 194, '2026-01-23 08:39:34', NULL, 15.00, 115.6600, NULL, 'sell', 1, NULL),
(87, 2, 194, '2026-01-23 08:42:44', NULL, 15.00, 115.6600, NULL, 'buy', 1, NULL),
(88, 2, 194, '2026-01-23 08:43:03', NULL, 15.00, 115.6600, NULL, 'sell', 1, NULL),
(89, 2, 174, '2026-01-23 08:45:44', '2026-01-23 08:47:58', 1.00, 234.3400, 234.3400, 'buy', 0, 0.00),
(90, 2, 174, '2026-01-23 08:46:08', '2026-01-23 08:47:58', 1.00, 234.3400, 234.3400, 'sell', 0, 0.00),
(91, 2, 204, '2026-01-23 08:46:45', '2026-01-23 09:10:20', 1.00, 253.7300, 253.7300, 'buy', 0, 0.00),
(92, 2, 204, '2026-01-23 08:46:50', '2026-01-23 09:10:20', 1.00, 253.7300, 253.7300, 'sell', 0, 0.00),
(93, 2, 174, '2026-01-23 08:47:54', '2026-01-23 08:47:58', 1.00, 234.3400, 234.3400, 'buy', 0, 0.00),
(94, 2, 170, '2026-01-23 09:10:09', '2026-01-23 09:10:13', 1.00, 248.3500, 248.3500, 'buy', 0, 0.00),
(95, 2, 185, '2026-01-23 09:10:15', '2026-01-23 09:10:16', 1.00, 299.7300, 299.7300, 'buy', 0, 0.00),
(96, 2, 204, '2026-01-23 09:10:18', '2026-01-23 09:10:20', 1.00, 253.7300, 253.7300, 'buy', 0, 0.00),
(97, 2, 174, '2026-01-23 09:10:22', '2026-01-23 09:10:23', 1.00, 234.3400, 234.3400, 'buy', 0, 0.00),
(98, 2, 215, '2026-01-23 09:10:26', '2026-01-23 09:10:27', 1.00, 251.4100, 251.4100, 'buy', 0, 0.00),
(99, 2, 170, '2026-01-23 09:40:35', '2026-01-23 09:40:43', 1.00, 248.3750, 248.3250, 'buy', 0, -0.05),
(100, 2, 170, '2026-01-23 09:40:38', '2026-01-23 09:40:43', 1.00, 248.3250, 248.3750, 'sell', 0, -0.05),
(101, 2, 170, '2026-01-23 09:40:40', '2026-01-23 09:40:43', 1.00, 248.3750, 248.3250, 'buy', 0, -0.05),
(102, 2, 170, '2026-01-23 09:40:41', '2026-01-23 09:40:43', 1.00, 248.3250, 248.3750, 'sell', 0, -0.05),
(103, 2, 170, '2026-01-23 09:40:42', '2026-01-23 09:40:43', 1.00, 248.3750, 248.3250, 'buy', 0, -0.05),
(104, 2, 170, '2026-01-23 09:40:46', '2026-01-23 09:40:47', 1.00, 248.3750, 248.3250, 'buy', 0, -0.05),
(105, 2, 170, '2026-01-23 09:40:49', '2026-01-23 09:40:49', 1.00, 248.3750, 248.3250, 'buy', 0, -0.05),
(106, 2, 170, '2026-01-23 09:41:41', '2026-01-23 09:42:12', 2.00, 248.3750, 248.3250, 'buy', 0, -0.10),
(107, 2, 170, '2026-01-23 09:41:42', '2026-01-23 09:42:12', 2.00, 248.3250, 248.3750, 'sell', 0, -0.10),
(108, 2, 170, '2026-01-23 09:41:52', '2026-01-23 09:42:12', 1.00, 248.3750, 248.3250, 'buy', 0, -0.05),
(109, 2, 170, '2026-01-23 09:41:53', '2026-01-23 09:42:12', 1.00, 248.3250, 248.3750, 'sell', 0, -0.05),
(110, 2, 170, '2026-01-23 09:41:58', '2026-01-23 09:42:12', 2.00, 248.3750, 248.3250, 'buy', 0, -0.10),
(111, 2, 170, '2026-01-23 09:41:59', '2026-01-23 09:42:12', 2.00, 248.3250, 248.3750, 'sell', 0, -0.10),
(112, 2, 170, '2026-01-23 09:42:03', '2026-01-23 09:42:12', 4.00, 248.3750, 248.3250, 'buy', 0, -0.20),
(113, 2, 170, '2026-01-23 09:42:03', '2026-01-23 09:42:12', 4.00, 248.3250, 248.3750, 'sell', 0, -0.20),
(114, 2, 170, '2026-01-23 09:42:10', '2026-01-23 09:42:12', 4.00, 248.3750, 248.3250, 'buy', 0, -0.20),
(115, 2, 170, '2026-01-25 09:49:10', NULL, 1.00, 248.0650, NULL, 'buy', 1, NULL),
(116, 2, 185, '2026-01-25 10:10:15', '2026-01-25 10:11:46', 1.00, 301.0950, 301.0450, 'buy', 0, -0.05),
(117, 2, 204, '2026-01-26 08:48:02', '2026-01-26 08:48:08', 1.00, 259.6550, 259.7050, 'sell', 0, -0.05);

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `pricedata`
--

CREATE TABLE `pricedata` (
  `ID` int NOT NULL,
  `AssetID` int DEFAULT NULL,
  `Timestamp` datetime DEFAULT NULL,
  `OpenPrice` decimal(10,4) DEFAULT NULL,
  `HighPrice` decimal(10,4) DEFAULT NULL,
  `LowPrice` decimal(10,4) DEFAULT NULL,
  `ClosePrice` decimal(10,4) DEFAULT NULL,
  `Volume` bigint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `price_ticks`
--

CREATE TABLE `price_ticks` (
  `id` bigint UNSIGNED NOT NULL,
  `symbol` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ts` bigint UNSIGNED NOT NULL,
  `price` decimal(16,6) NOT NULL,
  `bid` decimal(16,6) DEFAULT NULL,
  `ask` decimal(16,6) DEFAULT NULL,
  `source` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'finnhub',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `transactionslog`
--

CREATE TABLE `transactionslog` (
  `ID` int NOT NULL,
  `UserID` int DEFAULT NULL,
  `Type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `TransactionTime` datetime DEFAULT NULL,
  `Description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `transactionslog`
--

INSERT INTO `transactionslog` (`ID`, `UserID`, `Type`, `Amount`, `TransactionTime`, `Description`) VALUES
(1, 2, 'deposit', 10000.00, '2026-01-25 13:10:33', 'Befizetés');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `tutorialprogress`
--

CREATE TABLE `tutorialprogress` (
  `ID` int NOT NULL,
  `UserID` int DEFAULT NULL,
  `TutorialID` int DEFAULT NULL,
  `IsCompleted` bit(1) DEFAULT NULL,
  `StartedAt` datetime DEFAULT NULL,
  `CompletedAt` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `tutorials`
--

CREATE TABLE `tutorials` (
  `ID` int NOT NULL,
  `Title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `DifficultyLevel` int DEFAULT NULL,
  `Tags` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `Content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `users`
--

CREATE TABLE `users` (
  `ID` int NOT NULL,
  `Username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `PasswordHash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `RegistrationDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `IsLoggedIn` tinyint(1) NOT NULL DEFAULT '0',
  `PreferredTheme` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'dark',
  `NotificationsEnabled` tinyint(1) NOT NULL DEFAULT '1',
  `DemoBalance` decimal(15,2) NOT NULL DEFAULT '10000.00',
  `RealBalance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `PreferredCurrency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'USD'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `users`
--

INSERT INTO `users` (`ID`, `Username`, `Email`, `PasswordHash`, `RegistrationDate`, `IsLoggedIn`, `PreferredTheme`, `NotificationsEnabled`, `DemoBalance`, `RealBalance`, `PreferredCurrency`) VALUES
(1, 'csakibalazs545', 'csakinet29@gmail.com', '$2y$10$EsjbudiCCm/NbFksBMkDXOcYX2zghKuVxkcVHNfkZmBWDJqTx4jXS', '2025-12-09 12:12:51', 0, 'dark', 1, 10000.00, 0.00, 'USD'),
(2, 'csakibalazs', 'csaki.balazs@diak.szi-pg.hu', '$2y$10$4bt8JvNeH5i51Dc6dceV7OawF8lffwg9ZRKUgNSut1GEV8rAsLZjG', '2025-12-09 12:13:48', 0, 'dark', 1, 19945.11, 0.00, 'USD'),
(3, 'laravel', 'csaki.laravel@gmail.com', '$2y$10$IQd3Nhq9bjUzxdsCKD8lse6sNpqDgt6TxQN78BLqge00nFwzDjLxG', '2026-02-17 19:15:19', 0, 'dark', 1, 10000.00, 0.00, 'USD');

-- --------------------------------------------------------

--
-- Tábla szerkezet ehhez a táblához `usersettings`
--

CREATE TABLE `usersettings` (
  `ID` int NOT NULL,
  `UserID` int DEFAULT NULL,
  `AutoLogin` bit(1) DEFAULT NULL,
  `ReceiveNotifications` bit(1) DEFAULT NULL,
  `PreferredChartTheme` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `PreferredChartInterval` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `NewsLimit` int NOT NULL DEFAULT '8',
  `NewsPerSymbolLimit` int NOT NULL DEFAULT '3',
  `NewsPortfolioTotalLimit` int NOT NULL DEFAULT '20',
  `CalendarLimit` int NOT NULL DEFAULT '8'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- A tábla adatainak kiíratása `usersettings`
--

INSERT INTO `usersettings` (`ID`, `UserID`, `AutoLogin`, `ReceiveNotifications`, `PreferredChartTheme`, `PreferredChartInterval`, `NewsLimit`, `NewsPerSymbolLimit`, `NewsPortfolioTotalLimit`, `CalendarLimit`) VALUES
(1, 2, b'0', b'1', 'dark', '15m', 8, 3, 6, 6);

--
-- Indexek a kiírt táblákhoz
--

--
-- A tábla indexei `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_symbol` (`Symbol`);

--
-- A tábla indexei `candles`
--
ALTER TABLE `candles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_candles_symbol_tf_open` (`symbol`,`tf`,`open_ts`),
  ADD KEY `ix_candles_symbol_tf_open` (`symbol`,`tf`,`open_ts`);

--
-- A tábla indexei `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- A tábla indexei `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `UserID` (`UserID`);

--
-- A tábla indexei `positions`
--
ALTER TABLE `positions`
  ADD PRIMARY KEY (`ID`);

--
-- A tábla indexei `pricedata`
--
ALTER TABLE `pricedata`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `AssetID` (`AssetID`);

--
-- A tábla indexei `price_ticks`
--
ALTER TABLE `price_ticks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ux_ticks_symbol_ts` (`symbol`,`ts`),
  ADD KEY `ix_ticks_symbol_ts` (`symbol`,`ts`),
  ADD KEY `price_ticks_symbol_index` (`symbol`);

--
-- A tábla indexei `transactionslog`
--
ALTER TABLE `transactionslog`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `UserID` (`UserID`);

--
-- A tábla indexei `tutorialprogress`
--
ALTER TABLE `tutorialprogress`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `UserID` (`UserID`),
  ADD KEY `TutorialID` (`TutorialID`);

--
-- A tábla indexei `tutorials`
--
ALTER TABLE `tutorials`
  ADD PRIMARY KEY (`ID`);

--
-- A tábla indexei `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID`);

--
-- A tábla indexei `usersettings`
--
ALTER TABLE `usersettings`
  ADD PRIMARY KEY (`ID`),
  ADD UNIQUE KEY `uq_usersettings_userid` (`UserID`),
  ADD KEY `UserID` (`UserID`);

--
-- A kiírt táblák AUTO_INCREMENT értéke
--

--
-- AUTO_INCREMENT a táblához `assets`
--
ALTER TABLE `assets`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;

--
-- AUTO_INCREMENT a táblához `candles`
--
ALTER TABLE `candles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT a táblához `notifications`
--
ALTER TABLE `notifications`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `positions`
--
ALTER TABLE `positions`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT a táblához `pricedata`
--
ALTER TABLE `pricedata`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `price_ticks`
--
ALTER TABLE `price_ticks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `transactionslog`
--
ALTER TABLE `transactionslog`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT a táblához `tutorialprogress`
--
ALTER TABLE `tutorialprogress`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `tutorials`
--
ALTER TABLE `tutorials`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT a táblához `users`
--
ALTER TABLE `users`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT a táblához `usersettings`
--
ALTER TABLE `usersettings`
  MODIFY `ID` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Megkötések a kiírt táblákhoz
--

--
-- Megkötések a táblához `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `pricedata`
--
ALTER TABLE `pricedata`
  ADD CONSTRAINT `pricedata_ibfk_1` FOREIGN KEY (`AssetID`) REFERENCES `assets` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `transactionslog`
--
ALTER TABLE `transactionslog`
  ADD CONSTRAINT `transactionslog_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `tutorialprogress`
--
ALTER TABLE `tutorialprogress`
  ADD CONSTRAINT `tutorialprogress_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tutorialprogress_ibfk_2` FOREIGN KEY (`TutorialID`) REFERENCES `tutorials` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Megkötések a táblához `usersettings`
--
ALTER TABLE `usersettings`
  ADD CONSTRAINT `usersettings_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
