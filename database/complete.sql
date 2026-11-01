-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Oct 21, 2026 at 12:38 AM
-- Server version: 10.6.0-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `food`
--

-- --------------------------------------------------------

--
-- Table structure for table `custom_foods`
--

CREATE TABLE `custom_foods` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `food_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serving_size` decimal(8,2) NOT NULL DEFAULT 100.00,
  `serving_unit` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'g',
  `calories` decimal(8,1) NOT NULL,
  `protein_g` decimal(7,2) DEFAULT NULL,
  `carbs_g` decimal(7,2) DEFAULT NULL,
  `fat_g` decimal(7,2) DEFAULT NULL,
  `fiber_g` decimal(7,2) DEFAULT NULL,
  `sodium_mg` decimal(8,2) DEFAULT NULL,
  `sugar_g` decimal(7,2) DEFAULT NULL,
  `cholesterol_mg` decimal(7,2) DEFAULT NULL,
  `saturated_fat_g` decimal(7,2) DEFAULT NULL,
  `micronutrients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`micronutrients`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `custom_foods`
--

INSERT INTO `custom_foods` (`id`, `session_token`, `user_id`, `food_name`, `brand_name`, `serving_size`, `serving_unit`, `calories`, `protein_g`, `carbs_g`, `fat_g`, `fiber_g`, `sodium_mg`, `sugar_g`, `cholesterol_mg`, `saturated_fat_g`, `micronutrients`, `created_at`) VALUES
(2, '376cc3a4bf43331f22b68e3400e4933f', 1, 'Cream of Wheat', 'Bob\'s Red Mill', 49.00, 'g', 170.0, 5.00, 36.00, 0.00, 1.00, 0.00, 0.00, NULL, NULL, '{\"niacin_mg\":3,\"calcium_mg\":7,\"iron_mg\":2,\"potassium_mg\":46}', '2026-10-09 01:51:54'),
(3, '376cc3a4bf43331f22b68e3400e4933f', 1, 'Cream of Wheat - Organic', 'Bob\'s Red Mill', 49.00, 'grams', 180.0, 2.00, 37.00, 0.50, 2.00, NULL, NULL, NULL, NULL, '{\"calcium_mg\":9,\"iron_mg\":1,\"potassium_mg\":62}', '2026-10-09 02:00:40');

-- --------------------------------------------------------

--
-- Table structure for table `meal_entries`
--

CREATE TABLE `meal_entries` (
  `id` int(10) UNSIGNED NOT NULL,
  `meal_plan_id` int(10) UNSIGNED NOT NULL,
  `day_of_week` tinyint(3) UNSIGNED NOT NULL COMMENT '0=Monday … 6=Sunday',
  `meal_type` enum('breakfast','lunch','dinner','snack') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fdc_id` int(10) UNSIGNED NOT NULL COMMENT 'USDA FoodData Central ID',
  `food_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serving_size` decimal(8,2) NOT NULL DEFAULT 100.00,
  `serving_unit` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'g',
  `servings` decimal(5,2) NOT NULL DEFAULT 1.00,
  `calories` decimal(8,1) DEFAULT NULL,
  `protein_g` decimal(7,2) DEFAULT NULL,
  `carbs_g` decimal(7,2) DEFAULT NULL,
  `fat_g` decimal(7,2) DEFAULT NULL,
  `fiber_g` decimal(7,2) DEFAULT NULL,
  `sodium_mg` decimal(8,2) DEFAULT NULL,
  `sugar_g` decimal(7,2) DEFAULT NULL,
  `cholesterol_mg` decimal(7,2) DEFAULT NULL,
  `saturated_fat_g` decimal(7,2) DEFAULT NULL,
  `micronutrients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`micronutrients`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meal_entries`
--

INSERT INTO `meal_entries` (`id`, `meal_plan_id`, `day_of_week`, `meal_type`, `fdc_id`, `food_name`, `brand_name`, `serving_size`, `serving_unit`, `servings`, `calories`, `protein_g`, `carbs_g`, `fat_g`, `fiber_g`, `sodium_mg`, `sugar_g`, `cholesterol_mg`, `saturated_fat_g`, `micronutrients`, `created_at`) VALUES
(1, 1, 1, 'breakfast', 748967, 'Eggs, Grade A, Large, egg whole', NULL, 100.00, 'g', 1.00, 148.0, 12.40, 0.96, 9.96, 0.00, 129.00, NULL, 411.00, 3.20, NULL, '2026-10-07 15:07:17'),
(27, 1, 2, 'breakfast', 2708436, 'Cream of wheat, instant, made with water, no added fat', NULL, 100.00, 'g', 1.00, 67.0, 1.52, 14.42, 0.21, 0.50, 85.00, 3.64, 0.00, 0.03, '{\"vitamin_a_ug\":193,\"vitamin_c_mg\":0,\"vitamin_d_ug\":0,\"vitamin_e_mg\":0.02,\"vitamin_k_ug\":0.1,\"thiamin_mg\":0.173,\"riboflavin_mg\":0.166,\"niacin_mg\":2.436,\"vitamin_b6_mg\":0.256,\"folate_ug\":84,\"vitamin_b12_ug\":0,\"choline_mg\":3.3,\"calcium_mg\":54,\"iron_mg\":4.11,\"magnesium_mg\":6,\"phosphorus_mg\":15,\"potassium_mg\":17,\"zinc_mg\":0.15,\"copper_mg\":0.039,\"selenium_ug\":2.9}', '2026-10-08 14:32:01'),
(28, 1, 2, 'breakfast', 2626383, 'BLUEBERRIES', 'N/A', 140.00, 'g', 1.00, 79.8, 0.99, 16.94, 0.99, 4.06, 0.00, 12.00, 0.00, 0.00, '{\"vitamin_a_ug\":0,\"vitamin_c_mg\":3.64,\"calcium_mg\":0,\"iron_mg\":0}', '2026-10-08 14:32:17'),
(29, 1, 2, 'breakfast', 2642417, 'ALMONDS', 'O ORGANICS', 30.00, 'g', 1.00, 170.1, 6.00, 6.00, 15.00, 3.99, 0.00, 1.00, 0.00, 1.00, '{\"vitamin_d_ug\":0,\"calcium_mg\":60,\"iron_mg\":1.08,\"potassium_mg\":140.1}', '2026-10-08 14:32:29'),
(30, 1, 2, 'lunch', 1857258, 'TURKEY', 'HORMEL', 236.00, 'g', 1.00, 191.2, 23.01, 16.00, 3.00, 3.07, 1229.56, 3.99, 80.24, 0.99, '{\"vitamin_a_ug\":375.24,\"vitamin_c_mg\":0,\"calcium_mg\":99.12,\"iron_mg\":2.69}', '2026-10-08 14:32:56'),
(31, 1, 2, 'lunch', 2346388, 'Lettuce, iceberg, raw', NULL, 100.00, 'g', 1.00, NULL, 0.74, 3.37, 0.07, NULL, 16.10, NULL, NULL, NULL, '{\"vitamin_k_ug\":20.5,\"thiamin_mg\":0.056,\"niacin_mg\":0.218,\"vitamin_b6_mg\":0.04,\"calcium_mg\":14.2,\"iron_mg\":0.033,\"magnesium_mg\":6.31,\"phosphorus_mg\":19.2,\"potassium_mg\":139,\"zinc_mg\":0.173,\"copper_mg\":0.033,\"manganese_mg\":0.082}', '2026-10-08 14:33:11'),
(32, 1, 2, 'lunch', 2710085, 'Mustard', NULL, 100.00, 'g', 1.00, 69.0, 4.25, 5.30, 3.38, 4.30, 1100.00, 1.42, 0.00, 0.25, '{\"vitamin_a_ug\":4,\"vitamin_c_mg\":0.4,\"vitamin_d_ug\":0,\"vitamin_e_mg\":0.35,\"vitamin_k_ug\":1.5,\"thiamin_mg\":0.189,\"riboflavin_mg\":0.066,\"niacin_mg\":0.561,\"vitamin_b6_mg\":0.069,\"folate_ug\":7,\"vitamin_b12_ug\":0,\"choline_mg\":22.4,\"calcium_mg\":63,\"iron_mg\":1.59,\"magnesium_mg\":48,\"phosphorus_mg\":108,\"potassium_mg\":150,\"zinc_mg\":0.64,\"copper_mg\":0.074,\"selenium_ug\":34}', '2026-10-08 14:33:49'),
(33, 1, 2, 'snack', 1662236, 'KIWI', 'MIGHTIES', 148.00, 'g', 1.00, 90.3, 2.00, 22.05, 1.01, 4.00, 0.00, 12.99, 0.00, 0.00, '{\"vitamin_a_ug\":30.192,\"vitamin_c_mg\":137.936,\"vitamin_k_ug\":55.944,\"calcium_mg\":60.68,\"iron_mg\":0.355,\"potassium_mg\":460.28}', '2026-10-08 14:33:58'),
(34, 1, 2, 'dinner', 2117388, 'APPLE', 'BEST CHOICE', 240.00, 'ml', 1.00, 110.4, 0.00, 28.08, 0.00, NULL, 36.00, 28.08, NULL, NULL, '{\"vitamin_c_mg\":78}', '2026-10-08 14:34:42'),
(35, 1, 2, 'dinner', 2346388, 'Lettuce, iceberg, raw', NULL, 100.00, 'g', 2.00, NULL, 1.48, 6.74, 0.14, NULL, 32.20, NULL, NULL, NULL, '{\"vitamin_k_ug\":41,\"thiamin_mg\":0.112,\"niacin_mg\":0.436,\"vitamin_b6_mg\":0.08,\"calcium_mg\":28.4,\"iron_mg\":0.066,\"magnesium_mg\":12.62,\"phosphorus_mg\":38.4,\"potassium_mg\":278,\"zinc_mg\":0.346,\"copper_mg\":0.066,\"manganese_mg\":0.164}', '2026-10-08 14:35:01'),
(36, 1, 2, 'dinner', 2662224, 'SALTINE CRACKERS, SALTINE', 'MARKET PANTRY', 15.00, 'g', 1.00, 60.0, 1.00, 11.00, 1.50, 0.00, 124.95, 0.00, 0.00, 0.00, '{\"vitamin_d_ug\":0,\"calcium_mg\":0,\"iron_mg\":0.6,\"potassium_mg\":0}', '2026-10-08 14:35:39'),
(37, 1, 2, 'dinner', 2412181, 'THOUSAND ISLAND DRESSING, THOUSAND ISLAND', 'SHOPRITE', 30.00, 'ml', 1.00, 120.0, 0.00, 3.99, 12.00, 0.00, 260.10, 3.99, 9.90, 2.00, NULL, '2026-10-08 14:36:18'),
(39, 1, 2, 'dinner', 2706311, 'Fish, tuna, canned', NULL, 100.00, 'g', 2.00, 170.0, 38.00, 0.16, 1.88, 0.00, 438.00, 0.00, 72.00, 0.44, '{\"vitamin_a_ug\":34,\"vitamin_c_mg\":0,\"vitamin_d_ug\":2.4,\"vitamin_e_mg\":0.66,\"vitamin_k_ug\":0.4,\"thiamin_mg\":0.06,\"riboflavin_mg\":0.164,\"niacin_mg\":19.84,\"vitamin_b6_mg\":0.64,\"folate_ug\":8,\"vitamin_b12_ug\":5.14,\"choline_mg\":58.6,\"calcium_mg\":36,\"iron_mg\":3.34,\"magnesium_mg\":46,\"phosphorus_mg\":274,\"potassium_mg\":352,\"zinc_mg\":1.32,\"copper_mg\":0.096,\"selenium_ug\":135.6}', '2026-10-08 14:37:16'),
(42, 11, 2, 'snack', 1662236, 'KIWI', 'MIGHTIES', 148.00, 'g', 1.00, 90.3, 2.00, 22.05, 1.01, 4.00, 0.00, 12.99, 0.00, 0.00, '{\"vitamin_a_ug\":30.192,\"vitamin_c_mg\":137.936,\"vitamin_k_ug\":55.944,\"calcium_mg\":60.68,\"iron_mg\":0.355,\"potassium_mg\":460.28}', '2026-10-08 14:58:06'),
(45, 11, 2, 'lunch', 1857258, 'TURKEY', 'HORMEL', 236.00, 'g', 1.00, 191.2, 23.01, 16.00, 3.00, 3.07, 1229.56, 3.99, 80.24, 0.99, '{\"vitamin_a_ug\":375.24,\"vitamin_c_mg\":0,\"calcium_mg\":99.12,\"iron_mg\":2.69}', '2026-10-08 14:59:25'),
(46, 11, 2, 'lunch', 2346388, 'Lettuce, iceberg, raw', NULL, 100.00, 'g', 1.50, NULL, 1.11, 5.06, 0.11, NULL, 24.15, NULL, NULL, NULL, '{\"vitamin_k_ug\":30.75,\"thiamin_mg\":0.084,\"niacin_mg\":0.327,\"vitamin_b6_mg\":0.06,\"calcium_mg\":21.3,\"iron_mg\":0.05,\"magnesium_mg\":9.465,\"phosphorus_mg\":28.8,\"potassium_mg\":208.5,\"zinc_mg\":0.26,\"copper_mg\":0.05,\"manganese_mg\":0.123}', '2026-10-08 14:59:53'),
(47, 11, 2, 'dinner', 2346390, 'Lettuce, leaf, red, raw', NULL, 100.00, 'g', 1.00, NULL, 0.88, 3.26, 0.11, NULL, 24.90, NULL, NULL, NULL, '{\"vitamin_c_mg\":9.34,\"vitamin_k_ug\":73.2,\"thiamin_mg\":0.061,\"niacin_mg\":0.329,\"vitamin_b6_mg\":0.074,\"calcium_mg\":42.6,\"iron_mg\":0.402,\"magnesium_mg\":12.4,\"phosphorus_mg\":24.4,\"potassium_mg\":321,\"zinc_mg\":0.303,\"copper_mg\":0.038,\"manganese_mg\":0.177}', '2026-10-08 15:00:30'),
(48, 11, 2, 'breakfast', 2708436, 'Cream of wheat, instant, made with water, no added fat', NULL, 100.00, 'g', 1.00, 67.0, 1.52, 14.42, 0.21, 0.50, 85.00, 3.64, 0.00, 0.03, '{\"vitamin_a_ug\":193,\"vitamin_c_mg\":0,\"vitamin_d_ug\":0,\"vitamin_e_mg\":0.02,\"vitamin_k_ug\":0.1,\"thiamin_mg\":0.173,\"riboflavin_mg\":0.166,\"niacin_mg\":2.436,\"vitamin_b6_mg\":0.256,\"folate_ug\":84,\"vitamin_b12_ug\":0,\"choline_mg\":3.3,\"calcium_mg\":54,\"iron_mg\":4.11,\"magnesium_mg\":6,\"phosphorus_mg\":15,\"potassium_mg\":17,\"zinc_mg\":0.15,\"copper_mg\":0.039,\"selenium_ug\":2.9}', '2026-10-08 15:00:54'),
(49, 11, 2, 'breakfast', 2709275, 'Blueberries, raw', NULL, 100.00, 'g', 1.00, 64.0, 0.70, 14.57, 0.31, 2.40, 0.00, 9.36, 0.00, 0.03, '{\"vitamin_a_ug\":3,\"vitamin_c_mg\":8.1,\"vitamin_d_ug\":0,\"vitamin_e_mg\":0.57,\"vitamin_k_ug\":19.3,\"thiamin_mg\":0.037,\"riboflavin_mg\":0.041,\"niacin_mg\":0.418,\"vitamin_b6_mg\":0.052,\"folate_ug\":6,\"vitamin_b12_ug\":0,\"choline_mg\":6,\"calcium_mg\":12,\"iron_mg\":0.34,\"magnesium_mg\":6,\"phosphorus_mg\":13,\"potassium_mg\":86,\"zinc_mg\":0.09,\"copper_mg\":0.046,\"selenium_ug\":0.1}', '2026-10-08 15:01:09'),
(50, 11, 2, 'breakfast', 2642417, 'ALMONDS', 'O ORGANICS', 30.00, 'g', 1.00, 170.1, 6.00, 6.00, 15.00, 3.99, 0.00, 1.00, 0.00, 1.00, '{\"vitamin_d_ug\":0,\"calcium_mg\":60,\"iron_mg\":1.08,\"potassium_mg\":140.1}', '2026-10-08 15:01:35'),
(51, 11, 2, 'breakfast', 538197, 'COFFEE', 'Stumptown Coffee Roasters Inc.', 310.50, 'ml', 1.00, 3.1, 0.00, 0.99, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, NULL, '2026-10-08 15:02:06'),
(52, 11, 2, 'breakfast', 2705405, 'Soy milk, unsweetened', NULL, 100.00, 'g', 3.00, 114.0, 10.65, 3.87, 6.36, 0.00, 102.00, 1.68, 0.00, 0.93, '{\"vitamin_a_ug\":174,\"vitamin_c_mg\":0,\"vitamin_d_ug\":2.1,\"vitamin_e_mg\":0.48,\"vitamin_k_ug\":9,\"thiamin_mg\":0.189,\"riboflavin_mg\":0.252,\"niacin_mg\":0.708,\"vitamin_b6_mg\":0.165,\"folate_ug\":60,\"vitamin_b12_ug\":1.17,\"choline_mg\":70.8,\"calcium_mg\":303,\"iron_mg\":1.62,\"magnesium_mg\":66,\"phosphorus_mg\":207,\"potassium_mg\":474,\"zinc_mg\":0.93,\"copper_mg\":0.324,\"selenium_ug\":5.7}', '2026-10-08 15:02:37'),
(53, 11, 2, 'lunch', 2705405, 'Soy milk, unsweetened', NULL, 100.00, 'g', 2.00, 76.0, 7.10, 2.58, 4.24, 0.00, 68.00, 1.12, 0.00, 0.62, '{\"vitamin_a_ug\":116,\"vitamin_c_mg\":0,\"vitamin_d_ug\":1.4,\"vitamin_e_mg\":0.32,\"vitamin_k_ug\":6,\"thiamin_mg\":0.126,\"riboflavin_mg\":0.168,\"niacin_mg\":0.472,\"vitamin_b6_mg\":0.11,\"folate_ug\":40,\"vitamin_b12_ug\":0.78,\"choline_mg\":47.2,\"calcium_mg\":202,\"iron_mg\":1.08,\"magnesium_mg\":44,\"phosphorus_mg\":138,\"potassium_mg\":316,\"zinc_mg\":0.62,\"copper_mg\":0.216,\"selenium_ug\":3.8}', '2026-10-08 15:03:22'),
(54, 11, 2, 'lunch', 2710085, 'Mustard', NULL, 100.00, 'g', 1.00, 69.0, 4.25, 5.30, 3.38, 4.30, 1100.00, 1.42, 0.00, 0.25, '{\"vitamin_a_ug\":4,\"vitamin_c_mg\":0.4,\"vitamin_d_ug\":0,\"vitamin_e_mg\":0.35,\"vitamin_k_ug\":1.5,\"thiamin_mg\":0.189,\"riboflavin_mg\":0.066,\"niacin_mg\":0.561,\"vitamin_b6_mg\":0.069,\"folate_ug\":7,\"vitamin_b12_ug\":0,\"choline_mg\":22.4,\"calcium_mg\":63,\"iron_mg\":1.59,\"magnesium_mg\":48,\"phosphorus_mg\":108,\"potassium_mg\":150,\"zinc_mg\":0.64,\"copper_mg\":0.074,\"selenium_ug\":34}', '2026-10-08 15:03:55'),
(55, 11, 2, 'lunch', 2009368, 'WHOLE WHEAT', 'WILD WHEAT BAKERY', 57.00, 'g', 1.00, 120.3, 5.00, 25.02, 0.00, 3.02, 200.07, 0.00, 0.00, 0.00, '{\"vitamin_a_ug\":0,\"vitamin_c_mg\":0,\"calcium_mg\":19.95,\"iron_mg\":1.077}', '2026-10-08 15:04:11'),
(56, 11, 2, 'dinner', 2104711, 'TUNA SALAD', 'RALEY\'S', 100.00, 'g', 1.00, 210.0, 13.00, 1.00, 17.00, 0.00, 490.00, 0.00, 40.00, 3.00, '{\"vitamin_a_ug\":30,\"vitamin_c_mg\":1.2,\"calcium_mg\":20,\"iron_mg\":0.36}', '2026-10-08 15:04:34'),
(57, 11, 2, 'dinner', 1917330, 'WHOLE WHEAT', 'SHIRAZ LAVASH', 28.00, 'g', 1.00, 45.1, 4.00, 5.01, 0.00, 0.00, 89.04, 0.00, 0.00, 0.00, '{\"vitamin_a_ug\":0,\"vitamin_c_mg\":0,\"calcium_mg\":40.04,\"iron_mg\":1.439}', '2026-10-08 15:05:00'),
(58, 1, 2, 'lunch', 3, 'Cream of Wheat - Organic', 'Bob\'s Red Mill', 49.00, 'grams', 1.00, 180.0, 2.00, 37.00, 0.50, 2.00, NULL, NULL, NULL, NULL, '{\"calcium_mg\":9,\"iron_mg\":1,\"potassium_mg\":62}', '2026-10-09 02:00:43');

-- --------------------------------------------------------

--
-- Table structure for table `meal_plans`
--

CREATE TABLE `meal_plans` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `week_start` date NOT NULL COMMENT 'Always a Monday (YYYY-MM-DD)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `meal_plans`
--

INSERT INTO `meal_plans` (`id`, `session_token`, `user_id`, `week_start`, `created_at`) VALUES
(1, 'af94c910076aa7fdb0c1c6c7af53e5cd', 1, '2026-10-05', '2026-10-07 15:07:17'),
(11, 'user-2', 2, '2026-10-05', '2026-10-08 14:57:46');

-- --------------------------------------------------------

--
-- Table structure for table `nutritional_goals`
--

CREATE TABLE `nutritional_goals` (
  `id` int(10) UNSIGNED NOT NULL,
  `session_token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `calories` int(10) UNSIGNED NOT NULL DEFAULT 2000,
  `protein_g` decimal(6,1) NOT NULL DEFAULT 50.0,
  `carbs_g` decimal(6,1) NOT NULL DEFAULT 250.0,
  `fat_g` decimal(6,1) NOT NULL DEFAULT 65.0,
  `fiber_g` decimal(6,1) NOT NULL DEFAULT 25.0,
  `sodium_mg` decimal(8,1) NOT NULL DEFAULT 2300.0,
  `height_cm` decimal(5,1) DEFAULT NULL,
  `weight_kg` decimal(5,1) DEFAULT NULL,
  `age` tinyint(3) UNSIGNED DEFAULT NULL,
  `gender` enum('male','female') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activity_level` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight_goal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'lose | mild_lose | maintain | mild_gain | gain',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `nutritional_goals`
--

INSERT INTO `nutritional_goals` (`id`, `session_token`, `user_id`, `calories`, `protein_g`, `carbs_g`, `fat_g`, `fiber_g`, `sodium_mg`, `height_cm`, `weight_kg`, `age`, `gender`, `activity_level`, `weight_goal`, `created_at`, `updated_at`) VALUES
(4, 'af94c910076aa7fdb0c1c6c7af53e5cd', 1, 1200, 60.0, 150.0, 40.0, 17.0, 2300.0, 162.6, 61.7, 64, 'female', 'light', 'lose', '2026-10-08 13:59:53', '2026-10-08 13:59:53');

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'sha256 of the emailed verify/reset token',
  `token_expires` datetime DEFAULT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `token`, `token_expires`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Tori', 'Locke', 'torislocke@gmail.com', '$2y$10$cZTMJt8AydsU32aqKeiDNe7q.36rV6cpANp5LXed/iNDRzX6Zek/i', NULL, NULL, 'Active', '2026-10-07 15:18:11', '2026-10-08 14:48:53'),
(2, 'tbos', '', 'tori.bosler@hotmail.com', '$2y$10$5kqifIqhIm2fFDmVFAgdcO7NS.7xGxZwtsDusC.TODG/WVAEYxuf6', NULL, NULL, 'Active', '2026-10-08 14:45:11', '2026-10-08 14:48:53');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `custom_foods`
--
ALTER TABLE `custom_foods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cf_token` (`session_token`);

--
-- Indexes for table `meal_entries`
--
ALTER TABLE `meal_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_plan_day` (`meal_plan_id`,`day_of_week`);

--
-- Indexes for table `meal_plans`
--
ALTER TABLE `meal_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_plan` (`session_token`,`week_start`);

--
-- Indexes for table `nutritional_goals`
--
ALTER TABLE `nutritional_goals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token` (`session_token`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_token_hash` (`token_hash`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `custom_foods`
--
ALTER TABLE `custom_foods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `meal_entries`
--
ALTER TABLE `meal_entries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `meal_plans`
--
ALTER TABLE `meal_plans`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `nutritional_goals`
--
ALTER TABLE `nutritional_goals`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `meal_entries`
--
ALTER TABLE `meal_entries`
  ADD CONSTRAINT `fk_entry_plan` FOREIGN KEY (`meal_plan_id`) REFERENCES `meal_plans` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `fk_rt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
