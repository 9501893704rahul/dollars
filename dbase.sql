-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Feb 06, 2026 at 04:24 AM
-- Server version: 8.0.45
-- PHP Version: 8.4.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dzm_hkchecklist`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` bigint UNSIGNED NOT NULL,
  `log_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint UNSIGNED DEFAULT NULL,
  `causer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint UNSIGNED DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `log_name`, `description`, `subject_type`, `event`, `subject_id`, `causer_type`, `causer_id`, `properties`, `batch_uuid`, `created_at`, `updated_at`) VALUES
(1, 'default', 'Session started', 'App\\Models\\CleaningSession', 'started', 1, 'App\\Models\\User', 6, '[]', NULL, '2026-01-10 18:03:42', '2026-01-10 18:03:42'),
(2, 'default', 'Session started', 'App\\Models\\CleaningSession', 'started', 2, 'App\\Models\\User', 8, '[]', NULL, '2026-01-26 02:33:16', '2026-01-26 02:33:16');

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `checklist_items`
--

CREATE TABLE `checklist_items` (
  `id` bigint UNSIGNED NOT NULL,
  `session_id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED DEFAULT NULL,
  `task_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `checked` tinyint(1) NOT NULL DEFAULT '0',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cleaning_sessions`
--

CREATE TABLE `cleaning_sessions` (
  `id` bigint UNSIGNED NOT NULL,
  `property_id` bigint UNSIGNED NOT NULL,
  `owner_id` bigint UNSIGNED NOT NULL,
  `housekeeper_id` bigint UNSIGNED NOT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time DEFAULT NULL,
  `status` enum('pending','in_progress','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `gps_confirmed_at` timestamp NULL DEFAULT NULL,
  `start_latitude` decimal(10,7) DEFAULT NULL,
  `start_longitude` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint UNSIGNED NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint UNSIGNED NOT NULL,
  `reserved_at` int UNSIGNED DEFAULT NULL,
  `available_at` int UNSIGNED NOT NULL,
  `created_at` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_10_16_165046_create_properties_table', 1),
(5, '2025_10_16_165158_create_rooms_table', 1),
(6, '2025_10_16_165307_create_tasks_table', 1),
(7, '2025_10_16_165414_create_cleaning_sessions_table', 1),
(8, '2025_10_16_165520_create_checklist_items_table', 1),
(9, '2025_10_16_165701_create_room_photos_table', 1),
(10, '2025_10_17_010810_create_permission_tables', 1),
(11, '2025_10_18_091955_create_activity_log_table', 1),
(12, '2025_10_18_091956_add_event_column_to_activity_log_table', 1),
(13, '2025_10_18_091957_add_batch_uuid_column_to_activity_log_table', 1),
(14, '2025_11_01_113006_create_property_room_table', 1),
(15, '2025_11_01_113224_create_room_task_table', 1),
(16, '2025_11_01_113501_task_media_table', 1),
(17, '2026_01_07_011950_add_profile_fields_to_users_table', 2),
(18, '2026_01_07_020552_create_settings_table', 2),
(19, '2026_01_09_115301_add_phase_to_tasks_table', 3),
(20, '2026_01_09_115302_create_property_tasks_table', 3),
(21, '2026_01_09_115338_make_room_id_nullable_in_checklist_items_table', 3),
(22, '2026_01_11_210154_add_scheduled_time_to_cleaning_sessions_table', 4);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 1),
(3, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(3, 'App\\Models\\User', 3),
(1, 'App\\Models\\User', 4),
(3, 'App\\Models\\User', 5),
(3, 'App\\Models\\User', 6),
(1, 'App\\Models\\User', 7),
(3, 'App\\Models\\User', 8);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'properties.view', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(2, 'properties.manage', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(3, 'rooms.manage', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(4, 'tasks.manage', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(5, 'sessions.view', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(6, 'sessions.manage', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(7, 'sessions.view_all', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(8, 'users.view', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(9, 'users.manage', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(10, 'roles.assign', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` bigint UNSIGNED NOT NULL,
  `owner_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_path` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `beds` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `baths` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `geo_radius_m` int UNSIGNED NOT NULL DEFAULT '150',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `owner_id`, `name`, `address`, `photo_path`, `beds`, `baths`, `latitude`, `longitude`, `geo_radius_m`, `created_at`, `updated_at`) VALUES
(1, 1, 'Testing', 'Dhaka, Bangladesh', 'properties/C05DsWF1A5bfO0kEOFTzEtaJtAnDIAHcZkOvuONF.png', 0, 0, 23.7643863, 90.3890144, 150, '2026-01-02 14:58:39', '2026-01-02 14:58:39'),
(2, 1, 'Skyline 776', '776 Euclid Ave Cleveland OH 44114', 'properties/ypYxCL7WZ9qY55LQwfiqV1l9mG7GJgIv6kKKAg2V.jpg', 0, 0, 41.5002067, -81.6877670, 150, '2026-01-05 22:45:07', '2026-01-05 22:45:07'),
(3, 1, 'Vista Cay', '4024 Breakview Drive Orlando FL 32819', 'properties/Cl6yy17YAOUi8jkC9kBIEcxhlgDPXQtGXgAYFENk.jpg', 0, 0, 28.4730000, 81.4380000, 150, '2026-01-10 17:21:41', '2026-01-10 17:21:41');

-- --------------------------------------------------------

--
-- Table structure for table `property_room`
--

CREATE TABLE `property_room` (
  `id` bigint UNSIGNED NOT NULL,
  `property_id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `sort_order` smallint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `property_room`
--

INSERT INTO `property_room` (`id`, `property_id`, `room_id`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 2, 3, 3, '2026-01-05 22:45:07', '2026-01-09 22:53:09'),
(3, 2, 1, 5, '2026-01-05 22:45:07', '2026-01-09 22:53:09'),
(5, 2, 5, 6, '2026-01-05 22:45:07', '2026-01-09 22:53:09'),
(6, 2, 6, 4, '2026-01-05 22:49:30', '2026-01-09 22:53:09'),
(7, 2, 7, 1, '2026-01-05 22:49:42', '2026-01-09 22:53:09'),
(8, 2, 8, 2, '2026-01-05 22:49:51', '2026-01-09 22:53:09'),
(9, 3, 9, 1, '2026-01-10 17:21:41', '2026-01-10 17:27:57'),
(10, 3, 3, 11, '2026-01-10 17:21:41', '2026-01-10 17:27:57'),
(13, 3, 7, 8, '2026-01-10 17:21:41', '2026-01-10 17:27:57'),
(14, 3, 1, 4, '2026-01-10 17:21:41', '2026-01-10 17:27:57'),
(15, 3, 6, 3, '2026-01-10 17:21:41', '2026-01-10 17:27:57'),
(16, 3, 5, 6, '2026-01-10 17:21:41', '2026-01-10 17:27:57'),
(17, 3, 10, 2, '2026-01-10 17:22:38', '2026-01-10 17:27:57'),
(18, 3, 11, 5, '2026-01-10 17:23:22', '2026-01-10 17:27:57'),
(19, 3, 12, 14, '2026-01-10 17:24:02', '2026-01-10 17:27:57'),
(20, 3, 13, 10, '2026-01-10 17:24:18', '2026-01-10 17:27:57'),
(21, 3, 14, 12, '2026-01-10 17:24:33', '2026-01-10 17:27:57'),
(22, 3, 15, 15, '2026-01-10 17:24:49', '2026-01-10 17:27:57'),
(23, 3, 16, 16, '2026-01-10 17:25:58', '2026-01-10 17:27:57'),
(24, 3, 17, 13, '2026-01-10 17:26:19', '2026-01-10 17:27:57'),
(25, 3, 18, 9, '2026-01-10 17:26:34', '2026-01-10 17:27:57'),
(26, 3, 19, 7, '2026-01-10 17:27:02', '2026-01-10 17:27:57'),
(27, 3, 21, 17, '2026-01-26 00:39:51', '2026-01-26 00:39:51'),
(28, 2, 9, 7, '2026-01-26 00:48:37', '2026-01-26 00:48:37'),
(29, 2, 22, 8, '2026-01-26 01:34:17', '2026-01-26 01:34:17'),
(30, 2, 23, 9, '2026-01-26 01:36:18', '2026-01-26 01:36:18'),
(31, 2, 24, 10, '2026-01-26 01:39:27', '2026-01-26 01:39:27');

-- --------------------------------------------------------

--
-- Table structure for table `property_tasks`
--

CREATE TABLE `property_tasks` (
  `id` bigint UNSIGNED NOT NULL,
  `property_id` bigint UNSIGNED NOT NULL,
  `task_id` bigint UNSIGNED NOT NULL,
  `sort_order` smallint UNSIGNED NOT NULL DEFAULT '0',
  `instructions` text COLLATE utf8mb4_unicode_ci,
  `visible_to_owner` tinyint(1) NOT NULL DEFAULT '1',
  `visible_to_housekeeper` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(2, 'owner', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(3, 'housekeeper', 'web', '2025-12-30 12:22:03', '2025-12-30 12:22:03');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(10, 1),
(1, 2),
(2, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 2),
(8, 2),
(10, 2),
(5, 3),
(6, 3);

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 'Kitchen', 1, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(2, 'Bedroom', 1, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(3, 'Bathroom', 1, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(5, 'Living Room', 1, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(6, 'Laundry', 1, '2026-01-05 22:49:30', '2026-01-05 22:49:30'),
(7, 'Hallway', 1, '2026-01-05 22:49:42', '2026-01-05 22:49:42'),
(8, 'Closet', 1, '2026-01-05 22:49:51', '2026-01-06 16:47:57'),
(9, '!BEFORE CLEANING INSTRUCTIONS', 0, '2026-01-06 16:30:03', '2026-01-26 00:48:37'),
(10, 'Foyer', 0, '2026-01-10 17:22:38', '2026-01-10 17:22:38'),
(11, 'Dining Room', 1, '2026-01-10 17:23:22', '2026-01-10 17:23:22'),
(12, 'Master Bedroom', 1, '2026-01-10 17:24:02', '2026-01-10 17:24:02'),
(13, 'Queen Bedroom', 0, '2026-01-10 17:24:18', '2026-01-10 17:24:18'),
(14, 'King Bedroom', 0, '2026-01-10 17:24:33', '2026-01-10 17:24:33'),
(15, 'Master Bathroom', 0, '2026-01-10 17:24:49', '2026-01-10 17:24:49'),
(16, 'Master Bedroom Closet', 0, '2026-01-10 17:25:58', '2026-01-10 17:25:58'),
(17, 'King Bedroom Closet', 0, '2026-01-10 17:26:19', '2026-01-10 17:26:19'),
(18, 'Linen Closet', 0, '2026-01-10 17:26:34', '2026-01-10 17:26:34'),
(19, 'Patio', 0, '2026-01-10 17:27:02', '2026-01-12 05:27:32'),
(20, 'Tested by Khokon', 0, '2026-01-14 19:52:41', '2026-01-14 19:52:41'),
(21, 'AFTER CLEANING INSTRUCTIONS', 0, '2026-01-26 00:39:51', '2026-01-26 00:39:51'),
(22, 'Kitchen/Dining Room', 0, '2026-01-26 01:34:17', '2026-01-26 01:34:17'),
(23, 'Bedroom Area', 0, '2026-01-26 01:36:18', '2026-01-26 01:36:18'),
(24, 'WHEN DONE', 0, '2026-01-26 01:39:27', '2026-01-26 01:39:27'),
(25, 'Entry Hallway', 0, '2026-01-26 01:41:39', '2026-01-26 01:41:39');

-- --------------------------------------------------------

--
-- Table structure for table `room_photos`
--

CREATE TABLE `room_photos` (
  `id` bigint UNSIGNED NOT NULL,
  `session_id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `captured_at` timestamp NULL DEFAULT NULL,
  `has_timestamp_overlay` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `room_task`
--

CREATE TABLE `room_task` (
  `id` bigint UNSIGNED NOT NULL,
  `room_id` bigint UNSIGNED NOT NULL,
  `task_id` bigint UNSIGNED NOT NULL,
  `sort_order` smallint UNSIGNED NOT NULL DEFAULT '0',
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `visible_to_owner` tinyint(1) NOT NULL DEFAULT '1',
  `visible_to_housekeeper` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `room_task`
--

INSERT INTO `room_task` (`id`, `room_id`, `task_id`, `sort_order`, `instructions`, `visible_to_owner`, `visible_to_housekeeper`, `created_at`, `updated_at`) VALUES
(1, 3, 1, 16, NULL, 1, 1, '2026-01-05 22:50:21', '2026-01-26 21:16:11'),
(2, 3, 12, 17, NULL, 1, 1, '2026-01-05 22:50:40', '2026-01-26 21:16:11'),
(3, 3, 13, 2, NULL, 1, 1, '2026-01-05 22:51:08', '2026-01-26 21:16:11'),
(4, 3, 14, 10, NULL, 1, 1, '2026-01-05 22:51:20', '2026-01-26 21:16:11'),
(5, 3, 15, 1, NULL, 1, 1, '2026-01-05 22:51:43', '2026-01-26 21:16:11'),
(12, 8, 1, 1, NULL, 1, 1, '2026-01-06 16:44:37', '2026-01-06 16:44:37'),
(13, 8, 12, 2, NULL, 1, 1, '2026-01-06 16:44:37', '2026-01-06 16:44:37'),
(14, 8, 22, 3, NULL, 1, 1, '2026-01-06 16:44:37', '2026-01-06 16:44:37'),
(15, 8, 23, 4, NULL, 1, 1, '2026-01-06 16:44:37', '2026-01-06 16:44:37'),
(16, 8, 24, 5, NULL, 1, 1, '2026-01-06 16:44:37', '2026-01-06 16:44:37'),
(17, 8, 25, 6, NULL, 1, 1, '2026-01-06 16:44:37', '2026-01-06 16:44:37'),
(18, 7, 1, 4, NULL, 1, 1, '2026-01-06 16:50:48', '2026-01-26 21:13:04'),
(20, 7, 24, 2, NULL, 1, 1, '2026-01-06 16:50:48', '2026-01-26 21:13:04'),
(24, 7, 29, 3, NULL, 1, 1, '2026-01-06 16:50:48', '2026-01-26 21:13:04'),
(27, 6, 32, 3, NULL, 1, 1, '2026-01-06 16:54:46', '2026-01-26 21:04:00'),
(31, 6, 36, 1, NULL, 1, 1, '2026-01-06 16:54:46', '2026-01-26 21:04:00'),
(32, 1, 1, 25, NULL, 1, 1, '2026-01-09 22:53:40', '2026-01-26 21:10:35'),
(33, 1, 12, 26, NULL, 1, 1, '2026-01-09 22:54:19', '2026-01-26 21:10:35'),
(34, 5, 1, 14, NULL, 1, 1, '2026-01-09 22:56:55', '2026-01-26 21:12:23'),
(45, 19, 46, 5, NULL, 1, 1, '2026-01-12 05:26:20', '2026-01-26 21:12:43'),
(53, 20, 47, 0, NULL, 1, 1, '2026-01-14 19:53:14', '2026-01-14 19:53:14'),
(54, 20, 49, 0, NULL, 1, 1, '2026-01-14 19:53:14', '2026-01-14 19:53:14'),
(55, 20, 23, 0, NULL, 1, 1, '2026-01-14 19:53:14', '2026-01-14 19:53:14'),
(56, 20, 53, 0, NULL, 1, 1, '2026-01-14 19:53:14', '2026-01-14 19:53:14'),
(57, 20, 24, 0, NULL, 1, 1, '2026-01-14 19:53:14', '2026-01-14 19:53:14'),
(58, 20, 54, 1, NULL, 1, 1, '2026-01-14 19:53:48', '2026-01-14 19:53:48'),
(59, 20, 55, 2, NULL, 1, 1, '2026-01-14 19:54:15', '2026-01-14 19:54:15'),
(69, 10, 1, 3, NULL, 1, 1, '2026-01-25 23:46:34', '2026-01-26 21:03:28'),
(70, 10, 12, 4, NULL, 1, 1, '2026-01-25 23:46:34', '2026-01-26 21:03:28'),
(71, 10, 59, 1, NULL, 1, 1, '2026-01-25 23:46:34', '2026-01-26 21:03:28'),
(72, 1, 60, 2, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(73, 1, 61, 6, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(74, 1, 62, 5, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(75, 1, 63, 4, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(76, 1, 64, 3, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(77, 1, 65, 7, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(78, 1, 66, 11, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(79, 1, 67, 8, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(80, 1, 68, 10, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(81, 1, 69, 9, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(82, 1, 70, 13, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(83, 1, 71, 14, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(84, 1, 72, 12, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(85, 1, 73, 15, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(86, 1, 74, 16, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(87, 1, 75, 24, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(88, 1, 76, 27, NULL, 1, 1, '2026-01-25 23:50:32', '2026-01-26 21:10:35'),
(89, 10, 77, 2, NULL, 1, 1, '2026-01-25 23:51:18', '2026-01-26 21:03:28'),
(90, 5, 78, 1, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(91, 5, 79, 2, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(93, 5, 81, 3, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(94, 5, 82, 5, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(95, 5, 83, 4, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(96, 5, 84, 6, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(97, 5, 85, 8, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(98, 5, 86, 7, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(99, 5, 87, 9, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(100, 5, 88, 10, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(101, 5, 89, 11, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(102, 5, 90, 12, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(103, 5, 91, 13, NULL, 1, 1, '2026-01-26 00:03:20', '2026-01-26 21:12:23'),
(104, 11, 78, 1, NULL, 1, 1, '2026-01-26 00:05:11', '2026-01-26 21:11:26'),
(105, 11, 80, 3, NULL, 1, 1, '2026-01-26 00:05:11', '2026-01-26 21:11:26'),
(106, 11, 92, 4, NULL, 1, 1, '2026-01-26 00:05:11', '2026-01-26 21:11:26'),
(107, 11, 93, 5, NULL, 1, 1, '2026-01-26 00:05:11', '2026-01-26 21:11:26'),
(108, 13, 94, 2, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(109, 13, 95, 10, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(110, 13, 96, 11, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(111, 13, 97, 12, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(112, 13, 98, 7, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(113, 13, 99, 8, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(114, 13, 100, 6, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(116, 13, 102, 3, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(117, 13, 103, 13, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(118, 13, 104, 5, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(119, 13, 105, 9, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(120, 13, 106, 4, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(121, 13, 107, 14, NULL, 1, 1, '2026-01-26 00:09:52', '2026-01-26 21:14:48'),
(122, 14, 94, 1, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(123, 14, 95, 12, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(124, 14, 97, 13, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(125, 14, 108, 6, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(126, 14, 109, 7, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(127, 14, 101, 4, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(128, 14, 99, 9, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(129, 14, 100, 5, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(130, 14, 102, 3, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(131, 14, 103, 11, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(132, 14, 104, 8, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(133, 14, 105, 10, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(134, 14, 79, 2, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(135, 14, 110, 14, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(136, 14, 107, 15, NULL, 1, 1, '2026-01-26 00:13:36', '2026-01-26 21:17:10'),
(139, 3, 113, 15, NULL, 1, 1, '2026-01-26 00:18:05', '2026-01-26 21:16:11'),
(142, 3, 116, 5, NULL, 1, 1, '2026-01-26 00:18:05', '2026-01-26 21:16:11'),
(143, 3, 117, 9, NULL, 1, 1, '2026-01-26 00:18:05', '2026-01-26 21:16:11'),
(145, 3, 119, 13, NULL, 1, 1, '2026-01-26 00:18:05', '2026-01-26 21:16:11'),
(146, 3, 120, 14, NULL, 1, 1, '2026-01-26 00:18:05', '2026-01-26 21:16:11'),
(148, 3, 122, 7, NULL, 1, 1, '2026-01-26 00:18:06', '2026-01-26 21:16:11'),
(149, 3, 123, 8, NULL, 1, 1, '2026-01-26 00:18:06', '2026-01-26 21:16:11'),
(150, 3, 107, 18, NULL, 1, 1, '2026-01-26 00:18:06', '2026-01-26 21:16:11'),
(151, 12, 94, 1, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(152, 12, 95, 9, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(154, 12, 97, 11, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(155, 12, 124, 4, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(156, 12, 99, 5, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(157, 12, 125, 6, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(159, 12, 102, 3, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(160, 12, 103, 12, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(161, 12, 104, 7, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(162, 12, 105, 8, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(163, 12, 126, 2, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(164, 12, 127, 10, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(165, 12, 107, 13, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(166, 12, 128, 14, NULL, 1, 1, '2026-01-26 00:21:23', '2026-01-26 21:17:59'),
(167, 15, 129, 1, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(168, 15, 112, 11, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(169, 15, 130, 7, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(171, 15, 117, 9, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(174, 15, 120, 10, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(175, 15, 121, 2, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(176, 15, 122, 3, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(177, 15, 123, 6, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(178, 15, 116, 4, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(179, 15, 131, 5, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(180, 15, 132, 12, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(181, 15, 107, 13, NULL, 1, 1, '2026-01-26 00:25:04', '2026-01-26 21:20:15'),
(182, 19, 133, 2, NULL, 1, 1, '2026-01-26 00:28:27', '2026-01-26 21:12:43'),
(183, 19, 134, 1, NULL, 1, 1, '2026-01-26 00:28:27', '2026-01-26 21:12:43'),
(184, 19, 135, 3, NULL, 1, 1, '2026-01-26 00:28:27', '2026-01-26 21:12:43'),
(186, 19, 136, 4, NULL, 1, 1, '2026-01-26 00:28:27', '2026-01-26 21:12:43'),
(187, 19, 137, 6, NULL, 1, 1, '2026-01-26 00:28:27', '2026-01-26 21:12:43'),
(189, 18, 139, 1, NULL, 1, 1, '2026-01-26 00:33:29', '2026-01-26 04:15:59'),
(192, 18, 142, 4, NULL, 1, 1, '2026-01-26 00:33:29', '2026-01-26 04:16:54'),
(194, 18, 144, 6, NULL, 1, 1, '2026-01-26 00:33:29', '2026-01-26 04:17:41'),
(198, 6, 148, 8, NULL, 1, 1, '2026-01-26 00:35:45', '2026-01-26 21:04:00'),
(202, 6, 152, 4, NULL, 1, 1, '2026-01-26 00:35:45', '2026-01-26 21:04:00'),
(203, 6, 153, 5, NULL, 1, 1, '2026-01-26 00:35:45', '2026-01-26 21:04:00'),
(204, 6, 154, 2, NULL, 1, 1, '2026-01-26 00:35:45', '2026-01-26 21:04:00'),
(205, 6, 155, 7, NULL, 1, 1, '2026-01-26 00:35:45', '2026-01-26 21:04:00'),
(206, 21, 156, 1, NULL, 1, 1, '2026-01-26 00:40:34', '2026-01-26 06:23:23'),
(207, 21, 157, 2, NULL, 1, 1, '2026-01-26 00:40:34', '2026-01-26 06:23:23'),
(208, 21, 158, 3, NULL, 1, 1, '2026-01-26 00:40:34', '2026-01-26 06:23:23'),
(209, 21, 159, 5, NULL, 1, 1, '2026-01-26 00:40:34', '2026-01-26 06:23:23'),
(211, 21, 161, 6, NULL, 1, 1, '2026-01-26 00:40:34', '2026-01-26 06:23:23'),
(249, 6, 199, 9, NULL, 1, 1, '2026-01-26 01:18:49', '2026-01-26 21:04:00'),
(250, 6, 200, 6, NULL, 1, 1, '2026-01-26 01:21:15', '2026-01-26 21:04:00'),
(253, 3, 203, 12, NULL, 1, 1, '2026-01-26 01:29:07', '2026-01-26 21:16:11'),
(258, 3, 208, 3, NULL, 1, 1, '2026-01-26 01:30:54', '2026-01-26 21:16:11'),
(261, 3, 211, 11, NULL, 1, 1, '2026-01-26 01:31:29', '2026-01-26 21:16:11'),
(263, 3, 213, 6, NULL, 1, 1, '2026-01-26 01:32:02', '2026-01-26 21:16:11'),
(264, 3, 214, 4, NULL, 1, 1, '2026-01-26 01:32:02', '2026-01-26 21:16:11'),
(265, 22, 215, 1, NULL, 1, 1, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(266, 22, 216, 2, NULL, 1, 1, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(267, 22, 217, 3, NULL, 1, 1, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(268, 22, 218, 4, NULL, 1, 1, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(269, 22, 219, 5, NULL, 1, 1, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(270, 22, 220, 6, NULL, 1, 1, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(271, 22, 221, 7, NULL, 1, 1, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(272, 22, 222, 8, NULL, 1, 1, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(273, 22, 223, 9, NULL, 1, 1, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(274, 22, 224, 10, NULL, 1, 1, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(275, 22, 225, 11, NULL, 1, 1, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(276, 23, 226, 1, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(277, 23, 227, 2, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(278, 23, 228, 3, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(279, 23, 229, 4, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(280, 23, 230, 5, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(281, 23, 231, 6, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(282, 23, 232, 7, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(283, 23, 233, 8, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(284, 23, 234, 9, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(285, 23, 235, 10, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(286, 23, 236, 11, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(287, 23, 237, 12, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(288, 23, 238, 13, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(289, 23, 239, 14, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(290, 23, 240, 15, NULL, 1, 1, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(291, 24, 241, 1, NULL, 1, 1, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(292, 24, 242, 2, NULL, 1, 1, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(293, 24, 243, 3, NULL, 1, 1, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(294, 24, 244, 4, NULL, 1, 1, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(295, 24, 245, 5, NULL, 1, 1, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(296, 24, 246, 6, NULL, 1, 1, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(297, 24, 247, 7, NULL, 1, 1, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(298, 24, 248, 8, NULL, 1, 1, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(299, 24, 249, 9, NULL, 1, 1, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(300, 7, 250, 1, NULL, 1, 1, '2026-01-26 01:43:00', '2026-01-26 21:13:04'),
(307, 9, 257, 1, NULL, 1, 1, '2026-01-26 02:45:22', '2026-01-26 02:45:22'),
(308, 9, 258, 2, NULL, 1, 1, '2026-01-26 04:01:10', '2026-01-26 04:01:10'),
(309, 13, 259, 1, NULL, 1, 1, '2026-01-26 04:18:33', '2026-01-26 21:14:48'),
(310, 17, 260, 1, 'Making sure closet door is locked', 1, 1, '2026-01-26 06:06:45', '2026-01-26 06:06:45'),
(311, 16, 260, 1, NULL, 1, 1, '2026-01-26 06:10:04', '2026-01-26 06:10:04'),
(312, 1, 261, 17, NULL, 1, 1, '2026-01-26 21:08:00', '2026-01-26 21:10:35'),
(313, 1, 262, 19, NULL, 1, 1, '2026-01-26 21:08:00', '2026-01-26 21:10:35'),
(314, 1, 263, 22, NULL, 1, 1, '2026-01-26 21:08:00', '2026-01-26 21:10:35'),
(315, 1, 264, 20, NULL, 1, 1, '2026-01-26 21:08:00', '2026-01-26 21:10:35'),
(316, 1, 265, 23, NULL, 1, 1, '2026-01-26 21:08:00', '2026-01-26 21:10:35'),
(317, 1, 266, 18, NULL, 1, 1, '2026-01-26 21:09:41', '2026-01-26 21:10:35'),
(318, 1, 267, 21, NULL, 1, 1, '2026-01-26 21:09:41', '2026-01-26 21:10:35'),
(319, 1, 268, 1, NULL, 1, 1, '2026-01-26 21:09:41', '2026-01-26 21:10:35'),
(320, 11, 269, 2, NULL, 1, 1, '2026-01-26 21:11:19', '2026-01-26 21:11:26'),
(321, 18, 270, 7, NULL, 1, 1, '2026-01-26 21:13:41', '2026-01-26 21:13:41'),
(322, 15, 203, 8, NULL, 1, 1, '2026-01-26 21:20:07', '2026-01-26 21:20:15');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('9nv7U9t2R4m9KzW2cJGyNiXKFTmJIchFhE3pVvRB', NULL, '127.0.0.1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Cursor/2.2.44 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSmdMVlpGZ3pEMEQzTzJFclFLSlFUR0ZNVW9lb01sNmlqN0gzbUZ1byI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1767119011);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `created_at`, `updated_at`) VALUES
(1, 'application_logo_path', 'logos/MobzCQHlNWpcaRgoNOCIgUGS42zdZBPaA3jSZblA.png', '2026-01-07 22:55:36', '2026-01-07 23:35:14'),
(2, 'favicon_path', 'favicons/Ino9XN7ltM93IZNToNrt88hGTiQDYfbnBuHx2fZu.png', '2026-01-07 22:55:36', '2026-01-09 22:25:48'),
(3, 'site_name', 'Room Ready', '2026-01-07 22:55:36', '2026-01-07 22:57:32'),
(4, 'theme_color', '#06b6d4', '2026-01-07 22:55:36', '2026-01-09 22:24:59'),
(5, 'button_primary_color', '#66aff0', '2026-01-07 22:55:36', '2026-01-07 22:57:32'),
(6, 'button_success_color', '#10b981', '2026-01-07 22:55:36', '2026-01-07 22:55:36'),
(7, 'button_danger_color', '#ef4444', '2026-01-07 22:55:36', '2026-01-07 22:55:36'),
(8, 'button_warning_color', '#f59e0b', '2026-01-07 22:55:36', '2026-01-07 22:55:36'),
(9, 'button_info_color', '#06b6d4', '2026-01-07 22:55:36', '2026-01-07 22:55:36'),
(10, 'logo_alignment', 'center', '2026-01-09 22:24:59', '2026-01-09 22:24:59');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '0',
  `type` enum('room','inventory') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'room',
  `phase` enum('pre_cleaning','during_cleaning','post_cleaning') COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Phase when task should be performed (null = room-level tasks)',
  `instructions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `name`, `is_default`, `type`, `phase`, `instructions`, `created_at`, `updated_at`) VALUES
(1, 'Sweep Floor', 1, 'room', NULL, NULL, '2025-12-30 12:22:03', '2026-01-14 20:04:10'),
(2, 'Mop floor', 1, 'room', NULL, NULL, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(3, 'Dust surfaces', 1, 'room', NULL, NULL, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(4, 'Empty trash', 1, 'room', NULL, NULL, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(5, 'Make bed', 1, 'room', NULL, NULL, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(6, 'Wipe counters', 1, 'room', NULL, NULL, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(7, 'Clean mirror', 1, 'room', NULL, NULL, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(8, 'Restock soap', 1, 'inventory', NULL, NULL, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(9, 'Restock toilet paper', 1, 'inventory', NULL, NULL, '2025-12-30 12:22:03', '2026-01-05 22:48:26'),
(10, 'Restock coffee/tea', 1, 'inventory', NULL, NULL, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(11, 'Replace towels', 1, 'inventory', NULL, NULL, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(12, 'Steam Clean Floor', 0, 'room', NULL, NULL, '2026-01-05 22:50:40', '2026-01-05 22:50:40'),
(13, 'Clean Sink', 0, 'room', NULL, NULL, '2026-01-05 22:51:08', '2026-01-05 22:51:08'),
(14, 'Clean Tub/Shower', 0, 'room', NULL, NULL, '2026-01-05 22:51:20', '2026-01-05 22:51:20'),
(15, 'Remove Floor Mat', 0, 'room', NULL, NULL, '2026-01-05 22:51:43', '2026-01-05 22:51:43'),
(16, 'Obtain Key For Linen Closet From Top Shelf Above Kitchen Sink', 0, 'room', NULL, NULL, '2026-01-06 16:31:53', '2026-01-06 16:31:53'),
(17, 'Tap Key To Sensor On Linen Closet To Unlock', 0, 'room', NULL, NULL, '2026-01-06 16:31:53', '2026-01-06 16:31:53'),
(18, 'Replace Key Linene Closet Key On Top Of Shelf In Kitchen', 0, 'room', NULL, NULL, '2026-01-06 16:32:46', '2026-01-09 21:59:28'),
(19, 'Make Sure That Lights In Kitchen Turn On With Motion', 0, 'room', NULL, NULL, '2026-01-06 16:38:19', '2026-01-10 17:30:03'),
(20, 'Make Sure That Lights In Hallway Turn On With Motion', 0, 'room', NULL, NULL, '2026-01-06 16:38:19', '2026-01-06 16:38:19'),
(21, 'Make Sure That Lights In Bathroom Turn On With Motion', 0, 'room', NULL, NULL, '2026-01-06 16:38:19', '2026-01-06 16:38:19'),
(22, 'Make Sure Printer Is Turned On', 0, 'room', NULL, NULL, '2026-01-06 16:44:37', '2026-01-06 16:44:37'),
(23, 'All Hangers Should Be Together In The Middle Of The Bottom Shelf On Left Side', 0, 'room', NULL, NULL, '2026-01-06 16:44:37', '2026-01-06 16:44:37'),
(24, 'Check Walls For Visible Damage Or Scuffs', 0, 'room', NULL, NULL, '2026-01-06 16:44:37', '2026-01-06 16:44:37'),
(25, 'Nothing Else Besides Hangers & Printer Should Be In Closet', 0, 'room', NULL, NULL, '2026-01-06 16:44:37', '2026-01-06 16:44:37'),
(26, 'Verify CLE OHIO Art Piece Is Present', 0, 'room', NULL, NULL, '2026-01-06 16:50:48', '2026-01-06 16:50:48'),
(27, 'Verify Sound Detector Is Present', 0, 'room', NULL, NULL, '2026-01-06 16:50:48', '2026-01-06 16:50:48'),
(28, 'Verify Skyline Art Piece Is Present', 0, 'room', NULL, NULL, '2026-01-06 16:50:48', '2026-01-06 16:50:48'),
(29, 'Dust Art Pieces On Wall', 0, 'room', NULL, NULL, '2026-01-06 16:50:48', '2026-01-06 16:50:48'),
(30, 'Leave Washer Door Cracked Open To Air Out', 0, 'room', NULL, NULL, '2026-01-06 16:54:46', '2026-01-06 16:54:46'),
(31, 'Verify Floor Steamer Present', 0, 'room', NULL, NULL, '2026-01-06 16:54:46', '2026-01-06 16:54:46'),
(32, 'Verify Broom/dustpan Present', 0, 'inventory', NULL, NULL, '2026-01-06 16:54:46', '2026-01-26 04:04:26'),
(33, 'Verify Shark Vacuum Present', 0, 'room', NULL, NULL, '2026-01-06 16:54:46', '2026-01-06 16:54:46'),
(34, 'Verify Laundry Basket Present', 0, 'room', NULL, NULL, '2026-01-06 16:54:46', '2026-01-06 16:54:46'),
(35, 'Leave Guest With A Laundry Pod (on top of the washer)', 0, 'room', NULL, NULL, '2026-01-06 16:54:46', '2026-01-06 16:54:46'),
(36, 'Wash Linens & Towels', 0, 'room', NULL, 'Be sure to add the dirty wrags from the last clean. Only use OxiClean (not the guest laundry pods). Use these settings: WASHER- Quick Wash (If stains need removed, use the Stain Steam setting instead), Hot water. DRYER- Timed dry for 60 minutes, High Heat. Don\'t dry the duvet cover, instead hang it over the shower rod in the bathroom, it will air dry by the time the rest of the load is dry. Check the dryer periodically to separate laundry that gets stuck inside of linens.', '2026-01-06 16:54:46', '2026-01-06 16:59:40'),
(37, 'Make Sure Lights In Queen Room Turn On With Motion', 0, 'room', NULL, NULL, '2026-01-10 17:31:08', '2026-01-10 17:31:08'),
(38, 'Make Sure Lights In King Room Turn On With Motion', 0, 'room', NULL, NULL, '2026-01-10 17:31:26', '2026-01-10 17:31:26'),
(39, 'Make Sure Lights In Master Bed Room Turn On With Motion', 0, 'room', NULL, NULL, '2026-01-10 17:32:04', '2026-01-10 17:32:04'),
(40, 'Make Sure Lights In Master Bath Room Turn On With Motion', 0, 'room', NULL, NULL, '2026-01-10 17:32:27', '2026-01-10 17:32:27'),
(41, 'Make Sure Lights In Foyer Turn On With Motion', 0, 'room', NULL, NULL, '2026-01-10 17:33:08', '2026-01-10 17:33:08'),
(42, 'Obtain Linen Closet Key', 0, 'room', NULL, NULL, '2026-01-10 17:36:54', '2026-01-10 17:36:54'),
(43, 'Start Charging Ring Doorbell Camera', 0, 'room', NULL, NULL, '2026-01-10 17:37:37', '2026-01-10 17:37:37'),
(44, 'Start Charging Noise Detector', 0, 'room', NULL, NULL, '2026-01-10 17:38:16', '2026-01-10 17:38:16'),
(45, 'Start Charging Round Vanity Mirror In Master Bathroom', 0, 'room', NULL, NULL, '2026-01-10 17:39:00', '2026-01-10 17:39:00'),
(46, 'Sweep', 0, 'room', NULL, NULL, '2026-01-12 05:26:20', '2026-01-12 05:26:20'),
(47, 'New Testing Task', 0, 'room', NULL, NULL, '2026-01-14 19:09:16', '2026-01-14 19:09:16'),
(48, 'Hello World Task Title', 0, 'room', NULL, NULL, '2026-01-14 19:13:41', '2026-01-14 19:13:41'),
(49, 'New Testing Task Name', 0, 'room', NULL, NULL, '2026-01-14 19:51:18', '2026-01-14 19:51:18'),
(50, 'New Task 1', 0, 'room', NULL, NULL, '2026-01-14 19:52:07', '2026-01-14 19:52:07'),
(51, 'New Task 2', 0, 'room', NULL, NULL, '2026-01-14 19:52:07', '2026-01-14 19:52:07'),
(52, 'New Task 3', 0, 'room', NULL, NULL, '2026-01-14 19:52:07', '2026-01-14 19:52:07'),
(53, 'Check Text Is Capitalize', 0, 'room', NULL, NULL, '2026-01-14 19:52:07', '2026-01-14 19:52:07'),
(54, 'Add Task By Preveiw Panel', 0, 'room', NULL, NULL, '2026-01-14 19:53:48', '2026-01-14 19:53:48'),
(55, 'Hello World', 0, 'room', NULL, NULL, '2026-01-14 19:54:15', '2026-01-14 19:54:15'),
(56, 'Test 3', 0, 'room', NULL, NULL, '2026-01-14 20:05:23', '2026-01-14 20:05:23'),
(57, 'Test 4', 0, 'room', NULL, NULL, '2026-01-14 20:05:23', '2026-01-14 20:05:23'),
(59, 'Verify Light Switch Is In The Up Position', 0, 'room', NULL, NULL, '2026-01-25 23:46:34', '2026-01-25 23:46:34'),
(60, 'All Countertop Surfaces, Wipe Down And Sanitized', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(61, 'Spot Check All Drawers For Anything Left Behind', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(62, 'Spot Check Drawers For Cleanliness', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(63, 'Spot Check For Missing Items', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(64, 'Clean Sink Out Be Sure To Clean The Rubber Catch Trap In Sink Also', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(65, 'Check Plates Dishes Pans, Silverware For Cleanliness And Neat Organization', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(66, 'Utensils, Keurig, Toaster And Anything Else That Sits On The Counter Should Be Nice And Organized', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(67, 'Refill Coffee K Cups Carousel', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(68, 'Refill Keurig Water To The Top Line Level And Be Sure Machine Is Turned Off', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(69, 'Open Keurig, Remove Coffee If Need Be And Leave Lid Open', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(70, 'Be Sure To Leave One Dish Washer Pod And One Trash Can Liner Under The Sink', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(71, 'Make Sure There Is A Guest Towel Left Hanging Over The Door By The Sink', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(72, 'Empty Dishwasher And Put Dishes Away', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(73, 'Discard Anything Left Over By Guests That Will Spoil Or That Is Opened', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(74, 'Wipe Refrigerator Inside (drawers/shelves)', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-26 21:06:40'),
(75, 'Lysol Disinfectant Wipes, Paper Towels, Cleaning Supplies Left Under Sink', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(76, 'Lock Kitchen Door (owners Closet)', 0, 'room', NULL, NULL, '2026-01-25 23:50:32', '2026-01-25 23:50:32'),
(77, 'Make Sure There Are 2 Umbrellas Present And In The Corner By The Front Door', 0, 'inventory', NULL, NULL, '2026-01-25 23:51:18', '2026-01-26 04:07:36'),
(78, 'All Surfaces Dusted Or Wiped', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 00:03:20'),
(79, 'Windows Closed And Locked', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 00:03:20'),
(80, 'Carpet Swept', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 00:03:20'),
(81, 'Couches In Their Place, Cleaned If Need Be', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 04:09:56'),
(82, 'Look For Any Stains Or Specs Of Dirt, Hair, Dust Or Lint, Use Lint Roller As Needed', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 00:03:20'),
(83, 'Couch Pillows Nice And Neat', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 00:03:20'),
(84, 'Glass Cleaned On Coffee Table And Streak Free', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 00:03:20'),
(85, 'Both Cube Pieces Dusted And In The Correct Place', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 00:03:20'),
(86, 'Coaster And Remotes Left On Coffee Table Surface', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 00:03:20'),
(87, 'Be Sure Tv Works', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 04:11:17'),
(88, 'Xbox And 2 Controllers Present And Nicely Under The Tv', 0, 'inventory', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 04:11:29'),
(89, 'Check Inside The Game Cover For Call Of Duty, There Should Be 3 Disks', 0, 'inventory', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 04:11:50'),
(90, 'Make Sure Printer Has Paper', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 00:03:20'),
(91, 'Make Sure Microphone Light Is On Soundbar', 0, 'room', NULL, NULL, '2026-01-26 00:03:20', '2026-01-26 00:03:20'),
(92, 'Bar Stools Pushed Under Table', 0, 'room', NULL, NULL, '2026-01-26 00:05:11', '2026-01-26 00:05:11'),
(93, 'Décor Vase In Middle Of Hightop Dining Room Table', 0, 'room', NULL, NULL, '2026-01-26 00:05:11', '2026-01-26 00:05:11'),
(94, 'Linens Removed, And Washed, And Changed', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 00:09:52'),
(95, 'Make The Bed (tags From Sheets And Duvet/duvet Cover Are Always At The Foot Of The Bed)', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 00:09:52'),
(96, 'Be Sure To Tuck In The King Pillow Case So That The Pillow Side Is Not Visible', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 00:09:52'),
(97, 'Floor Vacuumed', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 00:09:52'),
(98, 'Nightstand Cleaned Top And Inside Drawers', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 04:19:19'),
(99, 'Remote Control Sanitized', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 00:09:52'),
(100, 'Dresser Wiped Off Inside And Out', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 04:19:36'),
(101, 'Open Drawers Under Bed Dust And Make Sure Blankets Are Present', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 04:22:12'),
(102, 'Make Sure Tv Works', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 04:19:49'),
(103, 'Blinds Need To Left Open', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 00:09:52'),
(104, 'Empty Trashcan And Replace Liner', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 00:09:52'),
(105, 'Change Plug In Air Freshener (if Needed)', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 00:09:52'),
(106, 'Window Closed And Locked', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 00:09:52'),
(107, 'Manager Check! Double Check Everywhere For Dirty Spots Or Hair On White Linens', 0, 'room', NULL, NULL, '2026-01-26 00:09:52', '2026-01-26 00:09:52'),
(108, 'Nightstand Wiped Off', 0, 'room', NULL, NULL, '2026-01-26 00:13:36', '2026-01-26 00:13:36'),
(109, 'Nightstand Organized And Looking Neat', 0, 'room', NULL, NULL, '2026-01-26 00:13:36', '2026-01-26 00:13:36'),
(110, 'Lock Closet Door (owners Closet)', 0, 'room', NULL, NULL, '2026-01-26 00:13:36', '2026-01-26 00:13:36'),
(111, 'Hallway Floor Vacuumed', 0, 'room', NULL, NULL, '2026-01-26 00:18:05', '2026-01-26 00:18:05'),
(112, 'Floor Swept And Mopped', 0, 'room', NULL, NULL, '2026-01-26 00:18:05', '2026-01-26 00:18:05'),
(113, 'Bath Mats Swept No Footprints Visible On Guest Bath Rugs', 0, 'room', NULL, NULL, '2026-01-26 00:18:05', '2026-01-26 00:18:05'),
(114, 'Bathtub Clean Walls And Tub', 0, 'room', NULL, NULL, '2026-01-26 00:18:05', '2026-01-26 00:18:05'),
(115, 'Double Check For Dirty Spots Or Hair', 0, 'room', NULL, NULL, '2026-01-26 00:18:05', '2026-01-26 00:18:05'),
(116, 'Hair Dryer Cord Rolled Up And In Appropriate Bag', 0, 'room', NULL, NULL, '2026-01-26 00:18:05', '2026-01-26 00:18:05'),
(117, 'Toilet Cleaned Inside And Out Completely', 0, 'room', NULL, NULL, '2026-01-26 00:18:05', '2026-01-26 00:18:05'),
(118, 'Towels Folded And Hung Nice And Neat From The Rack.', 0, 'room', NULL, NULL, '2026-01-26 00:18:05', '2026-01-26 00:18:05'),
(119, 'Toilet Paper Dispenser. Three Rolls Inside Cabinet, One Roll On The Dispenser', 0, 'room', NULL, NULL, '2026-01-26 00:18:05', '2026-01-26 00:18:05'),
(120, 'Trash Can – Empty And Insert New Liner', 0, 'room', NULL, NULL, '2026-01-26 00:18:05', '2026-01-26 00:18:05'),
(121, 'Sink & Countertop Cleaned (check Inside Cabinets And Drawers For Anything Left Behind And Also Cleanliness)', 0, 'room', NULL, NULL, '2026-01-26 00:18:05', '2026-01-26 00:18:05'),
(122, 'Mirrors Cleaned Check To Make Sure They Are Streak Free', 0, 'room', NULL, NULL, '2026-01-26 00:18:06', '2026-01-26 00:18:06'),
(123, 'Refill Make Up Wipes (10 Per Stay) And Fold Makeup Towel Into A Pocket', 0, 'room', NULL, NULL, '2026-01-26 00:18:06', '2026-01-26 00:18:06'),
(124, 'Nightstand And Bedside Trays Stand Wiped Off', 0, 'room', NULL, NULL, '2026-01-26 00:21:23', '2026-01-26 00:21:23'),
(125, 'Dresser Wiped Off Streak Free', 0, 'room', NULL, NULL, '2026-01-26 00:21:23', '2026-01-26 00:21:23'),
(126, 'Make Sure Window Is Closed And Locked', 0, 'room', NULL, NULL, '2026-01-26 00:21:23', '2026-01-26 00:21:23'),
(127, 'Make Sure Owners Closet Door Is Locked', 0, 'room', NULL, NULL, '2026-01-26 00:21:23', '2026-01-26 00:21:23'),
(128, 'Lock The Door To Master Bedroom If Not Being Rented (ask Mike Each Time)', 0, 'room', NULL, NULL, '2026-01-26 00:21:23', '2026-01-26 00:21:23'),
(129, 'Remove Bath Mats First', 0, 'room', NULL, NULL, '2026-01-26 00:25:04', '2026-01-26 00:25:04'),
(130, 'Shower Clean Walls And Floor And Shower Doors', 0, 'room', NULL, NULL, '2026-01-26 00:25:04', '2026-01-26 00:25:04'),
(131, 'Be Sure Smart Speaker Microphone Light Is On', 0, 'room', NULL, NULL, '2026-01-26 00:25:04', '2026-01-26 00:25:04'),
(132, 'Replace Bath Mats, They Will Get Swept And The Tag Underneath Should Not Be Showing', 0, 'room', NULL, NULL, '2026-01-26 00:25:04', '2026-01-26 00:25:04'),
(133, 'Wipe Glass Coffee Table', 0, 'room', NULL, NULL, '2026-01-26 00:28:27', '2026-01-26 00:28:27'),
(134, 'Wash Ashtray Out', 0, 'room', NULL, NULL, '2026-01-26 00:28:27', '2026-01-26 00:28:27'),
(135, 'Wipe Tops Of Railings And Dust Cobwebs Inside Corners Of Rails', 0, 'room', NULL, NULL, '2026-01-26 00:28:27', '2026-01-26 00:28:27'),
(136, 'Neatly Organize The Patio Furniture', 0, 'room', NULL, NULL, '2026-01-26 00:28:27', '2026-01-26 00:28:27'),
(137, 'Make Sure Door Is Closed', 0, 'room', NULL, NULL, '2026-01-26 00:28:27', '2026-01-26 00:28:27'),
(138, 'Take Pictures Of Everything - Total Of 5 Pictures Taken Around, Displaying Contents, Condition Of Everything.', 0, 'room', NULL, NULL, '2026-01-26 00:28:27', '2026-01-26 00:28:27'),
(139, 'Everything Needs To Be Neat And Well Organized', 0, 'room', NULL, NULL, '2026-01-26 00:33:29', '2026-01-26 04:15:59'),
(140, 'Make Sure Fitted Sheets Are Folded Properly And Not Looking Disorganized Like This', 0, 'room', NULL, NULL, '2026-01-26 00:33:29', '2026-01-26 00:33:29'),
(141, 'Make Sure Everything Is Where It’s Supposed To Be.', 0, 'room', NULL, NULL, '2026-01-26 00:33:29', '2026-01-26 00:33:29'),
(142, 'Throw Dirty Wrags From Last Clean In With Laundry', 0, 'room', NULL, NULL, '2026-01-26 00:33:29', '2026-01-26 04:16:54'),
(143, 'Towels And Linens Should Always Be Folded Edge To Edge. Take Your Time Don’t Rush Through It That Creates Disorganization In The Closet This Is An Example Of What They Should Look Like', 0, 'room', NULL, NULL, '2026-01-26 00:33:29', '2026-01-26 00:33:29'),
(144, 'Notify Of Any Low Product Items', 0, 'room', NULL, NULL, '2026-01-26 00:33:29', '2026-01-26 04:17:41'),
(145, 'Do Not Mix King Pillow Cases With Queen', 0, 'room', NULL, NULL, '2026-01-26 00:33:29', '2026-01-26 00:33:29'),
(146, 'Noticed There’s A Place For Each That’s Why There’s A Place For Each So That Way You Know Which One Goes On Which Pillow.', 0, 'room', NULL, NULL, '2026-01-26 00:33:29', '2026-01-26 00:33:29'),
(147, 'The Linen Closet Should Have Everything Neatly Folded And Looking Like This Picture To The Left.', 0, 'room', NULL, NULL, '2026-01-26 00:33:29', '2026-01-26 00:33:29'),
(148, 'Make Sure Nothing Is Left In The Laundry', 0, 'room', NULL, NULL, '2026-01-26 00:35:45', '2026-01-26 04:03:26'),
(149, '6 Pool Towels Are All Accounted For And Neatly Stacked On The Shelf Above Laundry Machines', 0, 'room', NULL, NULL, '2026-01-26 00:35:45', '2026-01-26 00:35:45'),
(150, '2 Small Fans Should Be On The Shelf Above Laundry Machines', 0, 'room', NULL, NULL, '2026-01-26 00:35:45', '2026-01-26 00:35:45'),
(151, 'First Aid Kit Should Be On Shelf Above Laundry Machines', 0, 'room', NULL, NULL, '2026-01-26 00:35:45', '2026-01-26 00:35:45'),
(152, 'Iron Should Be On Shelf Above Laundry Machines With Cord Fully Retracted Inside', 0, 'inventory', NULL, NULL, '2026-01-26 00:35:45', '2026-01-26 04:04:37'),
(153, 'Iron Board Should Be Hanging From The Right Side Shelf Next To The Wall By The Dryer', 0, 'inventory', NULL, NULL, '2026-01-26 00:35:45', '2026-01-26 04:04:46'),
(154, 'Be Sure To Leave One Laundry Pod On Top Of Washing Machine', 0, 'room', NULL, NULL, '2026-01-26 00:35:45', '2026-01-26 00:35:45'),
(155, 'Be Sure Everything On Top Shelf Is Neatly Arranged', 0, 'room', NULL, NULL, '2026-01-26 00:35:45', '2026-01-26 04:05:36'),
(156, 'Take All Trash And Place In Valet Container Outside The Door', 0, 'room', NULL, NULL, '2026-01-26 00:40:34', '2026-01-26 00:40:34'),
(157, 'Be Sure Everything Is Folded And Put Away And Nothing Has Been Left In Laundry', 0, 'room', NULL, NULL, '2026-01-26 00:40:34', '2026-01-26 00:40:34'),
(158, 'Be Sure Housekeeping Closet Is Locked', 0, 'room', NULL, NULL, '2026-01-26 00:40:34', '2026-01-26 00:40:34'),
(159, 'Double Check Everything One Last Time As A Manager, As If You Were A Guest Checking In And Looking For Hairs On White Surfaces That May Have Been Missed, Dirt, Streaks, Etc. Anything That May Cause A Guest Complaint.', 0, 'room', NULL, NULL, '2026-01-26 00:40:34', '2026-01-26 00:40:34'),
(160, 'Take Pictures Of Every Room From 4 Different Angles', 0, 'room', NULL, NULL, '2026-01-26 00:40:34', '2026-01-26 00:40:34'),
(161, 'Say Alexa Goodbye', 0, 'room', NULL, NULL, '2026-01-26 00:40:34', '2026-01-26 06:23:17'),
(162, 'Walk Into Every Room To Make Sure That Lights Come On (except Lr), If Not Then Check The Switches', 0, 'room', NULL, NULL, '2026-01-26 00:40:34', '2026-01-26 00:40:34'),
(163, 'Lock Up And Leave', 0, 'room', NULL, NULL, '2026-01-26 00:40:34', '2026-01-26 00:40:34'),
(164, 'Get Magic Card From Top Shelf And Open The Linen Closet.', 0, 'room', NULL, NULL, '2026-01-26 00:56:53', '2026-01-26 00:56:53'),
(165, 'Immediately Put The Card Back On Top Of The Shelf. This Prevents Any Accidental Lockout Situation.', 0, 'room', NULL, NULL, '2026-01-26 00:56:53', '2026-01-26 00:56:53'),
(166, 'Begin With Stripping Bed And Doing Laundry While You Are Cleaning The Rest Of The Place.', 0, 'room', NULL, NULL, '2026-01-26 00:56:53', '2026-01-26 00:56:53'),
(167, 'Check Linens And Towels For Stains & Pre-treat Stains If Needed', 0, 'room', NULL, NULL, '2026-01-26 00:56:53', '2026-01-26 00:56:53'),
(168, 'In Washer Add Oxiclean And Bleach (never Use A Pod Those Are For Guests Only)', 0, 'room', NULL, NULL, '2026-01-26 00:56:53', '2026-01-26 00:56:53'),
(199, 'Clean Lint Trap Inside Of Dryer', 0, 'room', NULL, NULL, '2026-01-26 01:18:49', '2026-01-26 01:18:49'),
(200, 'Utility Step Ladder Present', 0, 'inventory', NULL, NULL, '2026-01-26 01:21:15', '2026-01-26 04:06:07'),
(201, 'Small Waste Basket-make Sure It Is There', 0, 'room', NULL, NULL, '2026-01-26 01:21:45', '2026-01-26 01:21:45'),
(202, 'Fire Extinguisher- Make Sure It Is There', 0, 'room', NULL, NULL, '2026-01-26 01:21:45', '2026-01-26 01:21:45'),
(203, 'Fill Shower Dispenser As Needed', 0, 'room', NULL, NULL, '2026-01-26 01:29:07', '2026-01-26 01:29:07'),
(204, 'Remove Bath Mat From The Room Before You Clean, Be Sure Not To Expose It To Any Chemicals Otherwise It Will Stain', 0, 'room', NULL, NULL, '2026-01-26 01:29:43', '2026-01-26 01:29:43'),
(205, 'Dust Top Of Speaker', 0, 'room', NULL, NULL, '2026-01-26 01:30:12', '2026-01-26 01:30:12'),
(206, 'Be Sure Microphone Light For Speaker Is On (lit)', 0, 'room', NULL, NULL, '2026-01-26 01:30:54', '2026-01-26 01:30:54'),
(207, 'Clean And Sanitize Sink', 0, 'room', NULL, NULL, '2026-01-26 01:30:54', '2026-01-26 01:30:54'),
(208, 'Polish Sink Chrome', 0, 'room', NULL, NULL, '2026-01-26 01:30:54', '2026-01-26 01:30:54'),
(209, 'Wipe Out Drawers And Under The Sink', 0, 'room', NULL, NULL, '2026-01-26 01:30:54', '2026-01-26 01:30:54'),
(210, 'Wipe Out Medicine Cabinet', 0, 'room', NULL, NULL, '2026-01-26 01:30:54', '2026-01-26 01:30:54'),
(211, 'Spray Inside Shower Liner', 0, 'room', NULL, NULL, '2026-01-26 01:31:29', '2026-01-26 01:31:29'),
(212, 'Empty Trash Can And Replace Liner Leaving An Additional Liner In The Bottom Inside The Trash Can', 0, 'room', NULL, NULL, '2026-01-26 01:32:02', '2026-01-26 01:32:02'),
(213, 'Wipe Front Of Sink', 0, 'room', NULL, NULL, '2026-01-26 01:32:02', '2026-01-26 01:32:02'),
(214, 'Wipe Out Drawers', 0, 'room', NULL, NULL, '2026-01-26 01:32:02', '2026-01-26 01:32:02'),
(215, 'Wash And Put Away Dishes If Need Be', 0, 'room', NULL, NULL, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(216, 'Clean Outside And Polish Microwave And Fridge With Stainless Steel Cleaner (no Other Cleaners)', 0, 'room', NULL, NULL, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(217, 'Wipe Out Inside Shelves And Drawers Of Fridge', 0, 'room', NULL, NULL, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(218, 'Wipe Inside Of Microwave', 0, 'room', NULL, NULL, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(219, 'Clean Glass Cooktop With Cooktop Cleaner (no Other Cleaners)', 0, 'room', NULL, NULL, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(220, 'Wipe Countertop And Shelves', 0, 'room', NULL, NULL, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(221, 'Check Drawers For Pots And Pans Make Sure It Looks Neat And Organized', 0, 'room', NULL, NULL, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(222, 'Stage Coffee Maker And Cups On Tray', 0, 'room', NULL, NULL, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(223, 'Stage Shelves For Dishes', 0, 'room', NULL, NULL, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(224, 'Guest Supplies: Leave Guest A Sponge, Dish Soap Under Sink, Hand Soap, Paper Towels On Counter', 0, 'room', NULL, NULL, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(225, 'Guest Supplies: Coffee Setup: 2 Of Each (6 Total) K Pods Of Coffee, Coffee Cups, Lids, Sleeves, Sugar, Creamer', 0, 'room', NULL, NULL, '2026-01-26 01:35:38', '2026-01-26 01:35:38'),
(226, 'Inspect All Linens For Stains And Treat If Needed', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(227, 'Strip Bed Linens And Wash On Hot', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(228, 'Dust Nightstands And Decor On Nightstands, Wipe If Needed', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(229, 'Check Drawers On Nightstands And Footer Of The Bed For Cleanliness', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(230, 'Turn On Tv And Inspect For Damages', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(231, 'Clean Screen (only If Needed) With Special Wipe In Packets', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(232, 'Sanitize Remote With Lysol Wipe', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(233, 'Ensure Tv Picture Is Set To Skyline Of Cle', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(234, 'Make The Bed: All Tags At Footer Of The Bed: Fitted Sheet Then Flat Sheet Then Duvet Insert, Then Fitted Duvet Cover, Then Arrange Pillows With Pillow Cases (ensure Flaps On Pillow Cases Are Closed To Not Expose Pillow', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(235, 'Ensure Microphone Light Is Lit On Soundbar', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(236, 'Clean Inside And Outside Window And Door Glass', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(237, 'Make Sure Balcony Door Handle Is Horizontal So That Way You Can Just Push The Door Open', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(238, 'Leave Tv On Skyline Image', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(239, '2 White Speakers Face Inward (sonos Logo Pointed Toward The Bed)', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(240, 'Make Sure Blue Light Is Emitting From Usb Ports On The Nightstands. If Not, Touch The Usb With Your Finger To Turn Them On', 0, 'room', NULL, NULL, '2026-01-26 01:38:33', '2026-01-26 01:38:33'),
(241, 'Do Manager Check And Look For Anything Out Of Place', 0, 'room', NULL, NULL, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(242, 'Look At The Place From The Moment You Enter And Inspect Everything As A Guest', 0, 'room', NULL, NULL, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(243, 'Look At Everything That Is White Very Closely (linens, Bed, Towels, Pillows, Sink, Bathtub, Shelves, Drawers Etc) For Hair, And Dust And Anything You May Have Overlooked', 0, 'room', NULL, NULL, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(244, 'Ensure The Microphone Lights On Both Speakers Are Turned On', 0, 'room', NULL, NULL, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(245, 'Ensure Tv Is Is On And Cleveland Skyline Is The Image (if Same Day Check In)', 0, 'room', NULL, NULL, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(246, 'Say Alexa Turn Off All The Lights', 0, 'room', NULL, NULL, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(247, 'Say Alexa Turn On Accent Lights', 0, 'room', NULL, NULL, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(248, 'Lockup The Apartment', 0, 'room', NULL, NULL, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(249, 'Replace Key And Parking Card In Lock Box And Take Picture And Then Scramble The Numbers On The Key Pad Put Box Back Into The Cabinet With All The Others', 0, 'room', NULL, NULL, '2026-01-26 01:39:44', '2026-01-26 01:39:44'),
(250, 'Verify Overhead Light Comes On Automatically Upon Entry', 0, 'room', NULL, NULL, '2026-01-26 01:43:00', '2026-01-26 04:14:16'),
(251, 'Check Closet Doors For Functionality', 0, 'room', NULL, NULL, '2026-01-26 01:43:00', '2026-01-26 01:43:00'),
(252, 'Arrange Hangers Altogether In The Middle Of The Lower Left Shelf On Rod', 0, 'room', NULL, NULL, '2026-01-26 01:43:00', '2026-01-26 01:43:00'),
(253, 'Verify Printer Is Present And Connected And In Working Order No Visible Damages', 0, 'room', NULL, NULL, '2026-01-26 01:43:00', '2026-01-26 01:43:00'),
(254, 'Clear Out Any Other Items And Dispose Of Properly', 0, 'room', NULL, NULL, '2026-01-26 01:43:00', '2026-01-26 01:43:00'),
(255, 'Change August Lock Batteries As Needed', 0, 'room', NULL, NULL, '2026-01-26 01:43:00', '2026-01-26 01:43:00'),
(256, 'Verify All Lights Come On (led Strip In Entryway, Led Strip In Living Room On Left And Right Side As Well As Kitchen Overhead Lights, Bathroom Lights, And Triangle Lights On Wall And Cleveland Script Sign) Say Alexa Turn On All The Lights', 0, 'room', NULL, NULL, '2026-01-26 01:44:31', '2026-01-26 01:44:31'),
(257, 'Say Alexa Turn On All The Lights', 0, 'room', NULL, NULL, '2026-01-26 02:45:22', '2026-01-26 02:45:22'),
(258, 'Get Key And Unlock Linen Closet', 0, 'room', NULL, NULL, '2026-01-26 04:01:10', '2026-01-26 04:01:10'),
(259, 'Spot Check Linens For Stain And Treat If Need Be', 0, 'room', NULL, NULL, '2026-01-26 04:18:33', '2026-01-26 04:18:33'),
(260, 'Making Sure The Closet Door Is Locked', 0, 'room', NULL, NULL, '2026-01-26 06:06:45', '2026-01-26 06:06:45'),
(261, 'Polish Outside Of Refrigerator', 0, 'room', NULL, NULL, '2026-01-26 21:08:00', '2026-01-26 21:08:00'),
(262, 'Polish Outside Of Microwave', 0, 'room', NULL, NULL, '2026-01-26 21:08:00', '2026-01-26 21:08:00'),
(263, 'Polish Outside Of Oven', 0, 'room', NULL, NULL, '2026-01-26 21:08:00', '2026-01-26 21:08:00'),
(264, 'Clean Glasstop Of Stove', 0, 'room', NULL, NULL, '2026-01-26 21:08:00', '2026-01-26 21:08:00'),
(265, 'Polish Outside Of Dishwasher', 0, 'room', NULL, NULL, '2026-01-26 21:08:00', '2026-01-26 21:08:00'),
(266, 'Clean Inside Of Microwave', 0, 'room', NULL, NULL, '2026-01-26 21:09:41', '2026-01-26 21:09:41'),
(267, 'Clean Inside Of Oven Door (if Needed)', 0, 'room', NULL, NULL, '2026-01-26 21:09:41', '2026-01-26 21:09:41'),
(268, 'Run Dishwasher (if Guest Did Not Do Dishes) Report To Manager If You Have To Do This Task', 0, 'room', NULL, NULL, '2026-01-26 21:09:41', '2026-01-26 21:09:41'),
(269, 'Dust Artwork', 0, 'room', NULL, NULL, '2026-01-26 21:11:19', '2026-01-26 21:11:19'),
(270, 'Throw Wrags From Todays Clean Into White Basket', 0, 'room', NULL, NULL, '2026-01-26 21:13:41', '2026-01-26 21:13:41');

-- --------------------------------------------------------

--
-- Table structure for table `task_media`
--

CREATE TABLE `task_media` (
  `id` bigint UNSIGNED NOT NULL,
  `task_id` bigint UNSIGNED NOT NULL,
  `type` enum('image','video') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `thumbnail` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `caption` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` smallint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_photo_path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone_number`, `profile_photo_path`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Mr. Admin', 'admin@example.com', NULL, NULL, '2025-12-30 12:22:03', '$2y$12$t3IKbfh4.mU6VRIWaARP0Or5IIWsRKoG1FPQzLCrin6NYwzIaFXMi', 'ncYUjTufcNII2wDi3mlhTJZPyiHMT6JfTG1vhfQUwpdKa0cNeo0TP78P31EO', '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(2, 'Mr. Owner', 'owner@example.com', NULL, NULL, '2025-12-30 12:22:03', '$2y$12$UzmfYQK9eLIA9HWi4ptGkeMBJoRU/IRnrVDWQo9eF1iONGU4WEZFG', NULL, '2025-12-30 12:22:03', '2025-12-30 12:22:03'),
(3, 'Mr. Housekeeper', 'housekeeper@example.com', NULL, NULL, '2025-12-30 12:22:03', '$2y$12$2zd83E48HZpmLCrTFl81QON9pJLa3y8NCrrfJddZU8UEBDi0ii9jK', NULL, '2025-12-30 12:22:03', '2025-12-30 12:22:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subject` (`subject_type`,`subject_id`),
  ADD KEY `causer` (`causer_type`,`causer_id`),
  ADD KEY `activity_log_log_name_index` (`log_name`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `checklist_items`
--
ALTER TABLE `checklist_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `checklist_items_session_id_foreign` (`session_id`),
  ADD KEY `checklist_items_room_id_foreign` (`room_id`),
  ADD KEY `checklist_items_task_id_foreign` (`task_id`),
  ADD KEY `checklist_items_user_id_foreign` (`user_id`);

--
-- Indexes for table `cleaning_sessions`
--
ALTER TABLE `cleaning_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cleaning_sessions_property_id_foreign` (`property_id`),
  ADD KEY `cleaning_sessions_owner_id_foreign` (`owner_id`),
  ADD KEY `cleaning_sessions_housekeeper_id_foreign` (`housekeeper_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `properties_owner_id_foreign` (`owner_id`);

--
-- Indexes for table `property_room`
--
ALTER TABLE `property_room`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `property_room_property_id_room_id_unique` (`property_id`,`room_id`),
  ADD KEY `property_room_room_id_foreign` (`room_id`);

--
-- Indexes for table `property_tasks`
--
ALTER TABLE `property_tasks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `property_tasks_property_id_task_id_unique` (`property_id`,`task_id`),
  ADD KEY `property_tasks_task_id_foreign` (`task_id`),
  ADD KEY `property_tasks_sort_order_index` (`sort_order`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `room_photos`
--
ALTER TABLE `room_photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `room_photos_session_id_foreign` (`session_id`),
  ADD KEY `room_photos_room_id_foreign` (`room_id`);

--
-- Indexes for table `room_task`
--
ALTER TABLE `room_task`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_task_room_id_task_id_unique` (`room_id`,`task_id`),
  ADD KEY `room_task_task_id_foreign` (`task_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `settings_key_unique` (`key`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `task_media`
--
ALTER TABLE `task_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_media_task_id_foreign` (`task_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `checklist_items`
--
ALTER TABLE `checklist_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=634;

--
-- AUTO_INCREMENT for table `cleaning_sessions`
--
ALTER TABLE `cleaning_sessions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `property_room`
--
ALTER TABLE `property_room`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `property_tasks`
--
ALTER TABLE `property_tasks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `room_photos`
--
ALTER TABLE `room_photos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `room_task`
--
ALTER TABLE `room_task`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=323;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=271;

--
-- AUTO_INCREMENT for table `task_media`
--
ALTER TABLE `task_media`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `checklist_items`
--
ALTER TABLE `checklist_items`
  ADD CONSTRAINT `checklist_items_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `checklist_items_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `cleaning_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `checklist_items_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `checklist_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cleaning_sessions`
--
ALTER TABLE `cleaning_sessions`
  ADD CONSTRAINT `cleaning_sessions_housekeeper_id_foreign` FOREIGN KEY (`housekeeper_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cleaning_sessions_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cleaning_sessions_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_room`
--
ALTER TABLE `property_room`
  ADD CONSTRAINT `property_room_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_room_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `property_tasks`
--
ALTER TABLE `property_tasks`
  ADD CONSTRAINT `property_tasks_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `property_tasks_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_photos`
--
ALTER TABLE `room_photos`
  ADD CONSTRAINT `room_photos_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_photos_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `cleaning_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `room_task`
--
ALTER TABLE `room_task`
  ADD CONSTRAINT `room_task_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `room_task_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_media`
--
ALTER TABLE `task_media`
  ADD CONSTRAINT `task_media_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
