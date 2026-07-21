-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 21, 2026 at 07:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cultivation`
--

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `className` varchar(255) DEFAULT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `className`, `alias`, `created_at`, `updated_at`) VALUES
(1, 'Class 6', 'class_6', '2026-07-02 02:46:40', '2026-07-02 02:46:40'),
(2, 'Class 7', 'class_7', '2026-07-02 02:46:40', '2026-07-02 02:46:40'),
(3, 'Class 8', 'class_8', '2026-07-02 02:46:40', '2026-07-02 02:46:40'),
(4, 'Class 9', 'class_9', '2026-07-02 02:46:40', '2026-07-02 02:46:40'),
(5, 'Class 10', 'class_10', '2026-07-02 02:46:40', '2026-07-02 02:46:40');

-- --------------------------------------------------------

--
-- Table structure for table `class_manages`
--

CREATE TABLE `class_manages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `className` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_manages`
--

INSERT INTO `class_manages` (`id`, `className`, `created_at`, `updated_at`) VALUES
(1, 'Class 6', '2026-07-02 02:46:40', '2026-07-02 02:46:40'),
(2, 'Class 7', '2026-07-02 02:46:40', '2026-07-02 02:46:40'),
(3, 'Class 8', '2026-07-02 02:46:40', '2026-07-02 02:46:40'),
(4, 'Class 9', '2026-07-02 02:46:40', '2026-07-02 02:46:40'),
(5, 'Class 10', '2026-07-02 02:46:40', '2026-07-02 02:46:40');

-- --------------------------------------------------------

--
-- Table structure for table `cultivation_admins`
--

CREATE TABLE `cultivation_admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `adminName` varchar(255) DEFAULT NULL,
  `adminUser` varchar(255) DEFAULT NULL,
  `userType` varchar(255) DEFAULT NULL,
  `loginPassword` varchar(255) DEFAULT NULL,
  `adminMobile` varchar(255) DEFAULT NULL,
  `adminMail` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `primary_class_id` bigint(20) UNSIGNED DEFAULT NULL,
  `primary_section_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cultivation_admins`
--

INSERT INTO `cultivation_admins` (`id`, `adminName`, `adminUser`, `userType`, `loginPassword`, `adminMobile`, `adminMail`, `avatar`, `created_at`, `updated_at`, `primary_class_id`, `primary_section_id`) VALUES
(1, 'System Admin', 'admin', '3', '$2y$12$SBAJRT3lvVO48YMoLC0lx.L7I7kLubWILIc84Z8mxFT7bytrFEVX6', '01700000001', 'admin@cultivation.local', NULL, '2026-07-02 02:46:40', '2026-07-02 02:46:40', NULL, NULL),
(2, 'MD MIZANUR RAHMAN AKHAND', 'mizanurrahman', '3', '$2y$12$SBAJRT3lvVO48YMoLC0lx.L7I7kLubWILIc84Z8mxFT7bytrFEVX6', '01611404041', 'mrakhand75@gmail.com', '98051643120260708.jpg', '2026-07-02 03:10:26', '2026-07-08 02:59:54', NULL, NULL),
(3, 'AHMED ULLAH', 'ahmed7399', '1', '$2y$12$i3ZfRYYgPxnO0x49n5tlD.SHgHlOg33/Cz2vI.VC/stVpzruAlA.O', '01773945584', 'ahmedullah7399@gmail.com', NULL, '2026-07-02 03:16:19', '2026-07-02 03:16:19', 3, 1),
(5, 'MD. KHORSHED ALAM', 'khorshedalamshs', '3', '$2y$12$z9v1q3pLJLyK3ZeheX43Mu6GeLWXBVuJ33fKsAebD6.YJuIq33sgW', '01958217805', 'khorshedalamshs@gmail.com', NULL, '2026-07-21 01:36:57', '2026-07-21 03:05:34', NULL, NULL),
(6, 'MST. TASLIMA AKTER', 'taslimashs', '1', '$2y$12$AxpSgw6R1t.tl9r3nixU4.T3R.7ILnTtYXXqxnWWzvO1V8RlL5oNG', '01828272222', 'taslima.akter3717@gmail.com', NULL, '2026-07-21 01:39:38', '2026-07-21 01:39:38', NULL, NULL),
(7, 'MD SHAFIQUL ISLAM', 'shafiqulshs', '1', '$2y$12$rQPEjv6CgrCkMwZ.dGpBcOqZp9/GoctPIoP98qhu.xnKFSxAeVc92', '01759466993', 'shafiqulislam6699@gmail.com', NULL, '2026-07-21 01:41:40', '2026-07-21 01:41:40', NULL, NULL),
(8, 'SUBORNO KUMAR', 'subornoshs', '1', '$2y$12$GJSqDB.LlCB.KHl5DW4hSud8ZhJxr6498zlA34.kKttAFOudIoBhG', '01770703142', 'subornokumar90@gmail.com', NULL, '2026-07-21 01:43:16', '2026-07-21 01:43:16', NULL, NULL),
(9, 'MD. ZAHIRUL ISLAM', 'zahirulshs', '1', '$2y$12$Xaw5OtWahTZewLc0d5PtiuzCztRCBZPjxagxDoGn1mZhNlUcpGGpG', '01716888713', 'mdzahirulislam1979@gmail.com', NULL, '2026-07-21 01:44:55', '2026-07-21 01:44:55', 2, 8),
(10, 'MOHAMMAD KAMAL HOSEN', 'kamalshs', '1', '$2y$12$R00Ru0Q5u2zv9L0F2PtS9Ofz2vqKDTfavuw1Y08rXRPUJdpkNkAFO', '01747354391', 'hosenkamal26061989@gmail.com', NULL, '2026-07-21 01:47:00', '2026-07-21 01:47:00', 3, 7),
(11, 'KOHINOOR SULTANA', 'kohinoorshs', '1', '$2y$12$mjlPL9pNhjzMWb7NaBAMP.cpDEJW/jvCyhcstkspKboUUDtGTKAW6', '01715534321', 'kohinoorsultana148@gmail.com', NULL, '2026-07-21 01:49:19', '2026-07-21 01:49:19', NULL, NULL),
(12, 'MST UMMAH SALMA', 'salmashs', '1', '$2y$12$D82/8ICh4rc2Xwjo3sEpqunDeTFYFg6N3yDRxujSFnKJyDUJz161G', '01915796511', 'umme1979@gmail.com', NULL, '2026-07-21 01:50:30', '2026-07-21 01:50:30', NULL, NULL),
(13, 'SHIKHA RANI SUTRADHAR', 'shikhashs', '1', '$2y$12$Id4jgg4WVD5hYBYkFKmgde8rQ0PJQ64rQe3GHUXnOXhznDvGalzjm', '01816666813', 'shikharanisutra672@gmail.com', NULL, '2026-07-21 01:52:41', '2026-07-21 01:52:41', NULL, NULL),
(14, 'UMMAY SALMA', 'ummesalmashs', '1', '$2y$12$7qQ/N0oJChvMFbgTSFiCXuV9ytBRAjWe.YEpYR44W7wjLoWo49N9m', '01789397298', 'shimaummaysalma@gmail.com', NULL, '2026-07-21 01:55:28', '2026-07-21 01:55:28', NULL, NULL),
(15, 'MD. RABIUL ALAM', 'rabiulshs', '1', '$2y$12$tUpj38EcsKxq.qw.49Hhq.wK7GgWKrOw3dtM5fDd/r66ZixX9TGp6', '01725310300', 'mdrabiula764@gmail.com', NULL, '2026-07-21 01:57:20', '2026-07-21 01:57:20', 4, 7),
(16, 'MD NASIR UDDIN', 'nasirshs', '1', '$2y$12$.L0JNyllUaB3AIGUjLYpo.NemmzFQSNGCm5A9xnux5DCwHmbCEmZu', '01725903331', 'nasiruddin938894@gmail.com', NULL, '2026-07-21 01:58:51', '2026-07-21 01:58:51', 5, 6),
(17, 'MOHAMMAD ZAHIRUL ISLAM', 'zahirulshs1597', '1', '$2y$12$svC9YmJlo0Ye9V3YPRrVrOBhvBi5CS60qcXac5hZRwa8TCFA0Q98K', '01727391597', 'zahirulislam441877@gmail.com', NULL, '2026-07-21 02:00:35', '2026-07-21 02:00:35', NULL, NULL),
(18, 'MOHAMMAD NURUL ALAM', 'nurulshs', '1', '$2y$12$4zWnic390/8uTf3y0f8gGuPIR8VjOZJkLXAA6/kspmLsSuWguVigW', '01710992786', 'nurulalam.mahin93@gmail.com', NULL, '2026-07-21 02:02:38', '2026-07-21 02:02:38', 3, 6),
(19, 'MD. YEASIN KHAN', 'yeasinshs', '1', '$2y$12$cFkIRoA0EZogN3icnxtoiudWSjr293UVOvKeuI95tZ/7TSbA368ZS', '01958217814', 'yeasin.khan12789@gmail.com', NULL, '2026-07-21 02:07:45', '2026-07-21 02:07:45', 5, 7),
(20, 'MD EASIN', 'easinshs', '1', '$2y$12$nscHaJLSY4kmdMS2z6RcHut5PqsMizlAhAGIhTC7AIS.jsou8k/Fa', '01817667310', 'mdeasin112@gmail.com', NULL, '2026-07-21 02:11:36', '2026-07-21 02:11:36', NULL, NULL),
(21, 'MOHAMMAD AL AMIN', 'alaminshs', '1', '$2y$12$MqvGdm4EtRVT2noCIhM9k..L7gZmHrVC8vwAEpk7INM6/.Ff7nc06', '01521233280', 'asmgazialamin@gmail.com', NULL, '2026-07-21 02:13:07', '2026-07-21 02:13:07', 4, 6),
(22, 'HABIBUR RAHMAN', 'habiburshs', '1', '$2y$12$f3a3mOjW3ORWICF907NXKe15/26arnUeqoShR1eUPwZxP0a7pqcpi', '01622493440', 'habibur3440@gmail.com', NULL, '2026-07-21 02:14:40', '2026-07-21 02:14:40', NULL, NULL),
(23, 'SAHARA BEGUM', 'saharashs', '1', '$2y$12$NULFCuffaK2GUTdM1ZFICerjmrpAKmtF9WHCD4aDhldpuJDZiRFka', '01726909017', 'saharabegum1978@gmail.com', NULL, '2026-07-21 02:16:13', '2026-07-21 02:16:13', 1, 6),
(24, 'MOSAMMAT KOHINOOR AKTER', 'kohinoorshs3863', '1', '$2y$12$vhZ5T/ye2nXGIyb7K2ghmewV9ipr9IqSVr16N/5e6s0HmwJ28hWZW', '01915383863', 'kohinoorakter3863@gmail.com', NULL, '2026-07-21 02:17:55', '2026-07-21 02:17:55', NULL, NULL),
(25, 'profileA_legacy_teacher', 'profileA_legacy_teacher', '1', '$2y$12$PSSGhtNf4zT75Sz7wA2DyuzURQIK3hEMh9wk1vb/7BQ9mGMRkdNKG', '01700000000', 'a@example.test', NULL, '2026-07-21 09:55:46', '2026-07-21 09:55:46', NULL, NULL),
(26, 'profileD_admin', 'profileD_admin', '3', '$2y$12$NGBGdzQsCmyjWgsk7VxA/.j4Is0HeKm0NnHr05OZLqeK66x4yX3Ra', '01700000000', 'd@example.test', NULL, '2026-07-21 09:58:44', '2026-07-21 09:58:44', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `section_manages`
--

CREATE TABLE `section_manages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `section` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `section_manages`
--

INSERT INTO `section_manages` (`id`, `section`, `created_at`, `updated_at`) VALUES
(6, 'B', '2026-07-03 08:48:49', '2026-07-03 08:48:49'),
(7, 'A', '2026-07-03 08:48:53', '2026-07-03 08:48:53'),
(8, 'Super', '2026-07-03 08:48:58', '2026-07-03 08:48:58');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `subjectName` varchar(255) DEFAULT NULL,
  `alias` varchar(255) DEFAULT NULL,
  `subjectType` varchar(255) DEFAULT NULL,
  `passingSystem` varchar(255) DEFAULT NULL,
  `assign_class` varchar(255) DEFAULT NULL,
  `isReligious` tinyint(1) NOT NULL DEFAULT 0,
  `CQ` varchar(255) DEFAULT NULL,
  `MCQ` varchar(255) DEFAULT NULL,
  `Practical` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `subjectName`, `alias`, `subjectType`, `passingSystem`, `assign_class`, `isReligious`, `CQ`, `MCQ`, `Practical`, `created_at`, `updated_at`) VALUES
(3, 'English 2nd Paper', 'english_2nd_paper', 'Main', NULL, '0', 0, '100', NULL, NULL, '2025-10-29 22:43:58', '2025-12-23 23:03:28'),
(4, 'English 1st Paper', 'english_1st_paper', 'Main', NULL, '0', 0, '100', NULL, NULL, '2025-10-29 22:44:26', '2025-12-23 23:03:23'),
(5, 'Bangla 1st Paper', 'bangla_1st_paper', 'Main', NULL, '0', 0, '70', '30', NULL, '2025-10-29 22:45:11', '2025-12-23 23:03:17'),
(6, 'Bangla 2nd Paper', 'bangla_2nd_paper', 'Main', NULL, '0', 0, '70', '30', NULL, '2025-10-29 22:45:54', '2025-12-23 23:03:08'),
(7, 'Math-109', 'math-109', 'Main', NULL, '0', 0, '70', '30', NULL, '2025-10-29 22:49:29', '2025-10-29 22:52:02'),
(8, 'Hinduism and Moral Education-112', 'hinduism_and_moral_education-112', 'Main', NULL, '0', 1, '70', '30', NULL, '2025-10-29 22:53:19', '2025-12-22 23:46:31'),
(9, 'Islam and moral education-111', 'islam_and_moral_education-111', 'Main', NULL, '0', 1, '70', '30', NULL, '2025-10-29 22:54:10', '2025-12-22 23:45:23'),
(11, 'Information and Comminucation Technology- 154', 'information_and_comminucation_technology-_154', 'Main', NULL, '0', 0, NULL, '25', '25', '2025-10-29 23:29:38', '2026-07-21 01:07:23'),
(12, 'Science-127', 'science-127', 'Main', NULL, '0', 0, '70', '30', NULL, '2025-10-29 23:31:11', '2026-07-02 03:24:15'),
(13, 'Bangladesh and Global Studies-150', 'bangladesh_and_global_studies-150', 'Main', NULL, '0', 0, '70', '30', NULL, '2025-10-29 23:31:55', '2025-10-29 23:31:55'),
(14, 'Agricultural Studies-134', 'agricultural_studies-134', 'Optional', NULL, '4', 0, '50', '25', '25', '2025-10-30 00:31:34', '2025-10-30 00:31:34'),
(15, 'Physics-136', 'physics-136', 'Main', NULL, '0', 0, '50', '25', '25', '2025-10-30 00:33:19', '2025-10-30 00:33:19'),
(16, 'Chemistry-137', 'chemistry-137', 'Main', NULL, '0', 0, '50', '25', '25', '2025-10-30 00:33:44', '2025-10-30 00:33:44'),
(17, 'Biology-138', 'biology-138', 'Main', NULL, '0', 0, '50', '25', '25', '2025-10-30 00:34:14', '2025-10-30 00:34:14'),
(18, 'Higher Math-126', 'higher_math-126', 'Optional', NULL, '0', 0, '50', '25', '25', '2025-10-30 00:34:53', '2025-10-30 00:34:53'),
(19, 'Accounting-146', 'accounting-146', 'Main', NULL, '0', 0, '70', '30', NULL, '2025-10-30 00:35:23', '2025-10-30 00:35:23'),
(20, 'Finance and Banking-152', 'finance_and_banking-152', 'Main', NULL, '0', 0, '70', '30', NULL, '2025-10-30 00:35:48', '2025-10-30 00:38:42'),
(21, 'Business Entrepreneurship-143', 'business_entrepreneurship-143', 'Main', NULL, '0', 0, '70', '30', NULL, '2025-10-30 00:36:23', '2025-10-30 00:36:23'),
(22, 'History of Bangladesh and World Civilization-153', 'history_of_bangladesh_and_world_civilization-153', 'Main', NULL, '0', 0, '70', '30', NULL, '2025-10-30 00:42:15', '2025-10-30 00:45:03'),
(23, 'Civics and Citizenship-140', 'civics_and_citizenship-140', 'Main', NULL, '0', 0, '70', '30', NULL, '2025-10-30 00:46:41', '2025-10-30 00:46:41'),
(24, 'Geography and Environment-110', 'geography_and_environment-110', 'Main', NULL, '0', 0, '70', '30', NULL, '2025-10-30 00:47:55', '2025-10-30 00:47:55'),
(25, 'Physical Education, Health and Sports-147', 'physical_education,_health_and_sports-147', 'Optional', NULL, '0', 0, NULL, NULL, '50', '2025-10-30 00:51:41', '2025-12-22 00:38:58'),
(29, 'LegacyMath', NULL, 'Theory', NULL, '3', 0, NULL, NULL, NULL, '2026-07-21 09:55:46', '2026-07-21 09:55:46'),
(30, 'LegacyScience', NULL, 'Theory', NULL, '3', 0, NULL, NULL, NULL, '2026-07-21 09:55:46', '2026-07-21 09:55:46');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_classes`
--

CREATE TABLE `teacher_classes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_classes`
--

INSERT INTO `teacher_classes` (`id`, `teacher_id`, `class_id`, `created_at`, `updated_at`) VALUES
(1, 3, 5, NULL, NULL),
(2, 3, 4, NULL, NULL),
(3, 3, 3, NULL, NULL),
(4, 3, 1, NULL, NULL),
(5, 4, 5, NULL, NULL),
(6, 6, 5, NULL, NULL),
(7, 8, 5, NULL, NULL),
(8, 10, 5, NULL, NULL),
(9, 13, 5, NULL, NULL),
(10, 15, 5, NULL, NULL),
(11, 16, 5, NULL, NULL),
(12, 17, 5, NULL, NULL),
(13, 22, 5, NULL, NULL),
(14, 21, 1, NULL, NULL),
(15, 21, 2, NULL, NULL),
(16, 21, 4, NULL, NULL),
(17, 21, 3, NULL, NULL),
(18, 25, 3, '2026-07-21 09:55:46', '2026-07-21 09:55:46');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_class_subjects`
--

CREATE TABLE `teacher_class_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED DEFAULT NULL,
  `section_id` bigint(20) UNSIGNED DEFAULT NULL,
  `group_id` bigint(20) UNSIGNED DEFAULT NULL,
  `assigned_days` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'JSON array of assigned days (e.g., ["Sunday", "Monday", "Wednesday"])' CHECK (json_valid(`assigned_days`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_class_subjects`
--

INSERT INTO `teacher_class_subjects` (`id`, `teacher_id`, `class_id`, `subject_id`, `section_id`, `group_id`, `assigned_days`, `created_at`, `updated_at`) VALUES
(1, 3, 5, 11, 2, NULL, NULL, '2026-07-02 03:16:19', '2026-07-02 03:16:19'),
(2, 3, 4, 15, 1, NULL, NULL, '2026-07-02 03:16:19', '2026-07-02 03:16:19'),
(3, 3, 4, 15, 2, NULL, NULL, '2026-07-02 03:16:19', '2026-07-02 03:16:19'),
(4, 3, 4, 15, 3, NULL, NULL, '2026-07-02 03:16:19', '2026-07-02 03:16:19'),
(5, 3, 3, 12, 1, NULL, NULL, '2026-07-02 03:16:19', '2026-07-02 03:16:19'),
(6, 3, 3, 12, 2, NULL, NULL, '2026-07-02 03:16:19', '2026-07-02 03:16:19'),
(7, 3, 3, 12, 3, NULL, NULL, '2026-07-02 03:16:19', '2026-07-02 03:16:19'),
(8, 3, 1, 11, 1, NULL, NULL, '2026-07-02 03:16:19', '2026-07-02 03:16:19'),
(9, 4, 5, 4, 6, NULL, NULL, '2026-07-21 01:00:25', '2026-07-21 01:00:25'),
(10, 4, 5, 4, 7, NULL, NULL, '2026-07-21 01:00:25', '2026-07-21 01:00:25'),
(11, 4, 5, 4, 8, NULL, NULL, '2026-07-21 01:00:25', '2026-07-21 01:00:25'),
(13, 6, 5, 5, 7, NULL, NULL, '2026-07-21 01:39:38', '2026-07-21 01:39:38'),
(14, 6, 5, 5, 8, NULL, NULL, '2026-07-21 01:39:38', '2026-07-21 01:39:38'),
(15, 6, 5, 6, 6, NULL, NULL, '2026-07-21 01:39:38', '2026-07-21 01:39:38'),
(17, 6, 5, 6, 8, NULL, NULL, '2026-07-21 01:39:38', '2026-07-21 01:39:38'),
(18, 8, 5, 8, 6, NULL, NULL, '2026-07-21 01:43:16', '2026-07-21 01:43:16'),
(19, 8, 5, 8, 7, NULL, NULL, '2026-07-21 01:43:16', '2026-07-21 01:43:16'),
(20, 8, 5, 8, 8, NULL, NULL, '2026-07-21 01:43:16', '2026-07-21 01:43:16'),
(21, 10, 5, 24, 6, NULL, NULL, '2026-07-21 01:47:00', '2026-07-21 01:47:00'),
(22, 10, 5, 24, 7, NULL, NULL, '2026-07-21 01:47:00', '2026-07-21 01:47:00'),
(23, 10, 5, 24, 8, NULL, NULL, '2026-07-21 01:47:00', '2026-07-21 01:47:00'),
(24, 10, 5, 12, 6, NULL, NULL, '2026-07-21 01:47:00', '2026-07-21 01:47:00'),
(25, 10, 5, 12, 7, NULL, NULL, '2026-07-21 01:47:00', '2026-07-21 01:47:00'),
(26, 10, 5, 12, 8, NULL, NULL, '2026-07-21 01:47:00', '2026-07-21 01:47:00'),
(27, 13, 5, 17, 6, NULL, NULL, '2026-07-21 01:52:41', '2026-07-21 01:52:41'),
(28, 13, 5, 17, 7, NULL, NULL, '2026-07-21 01:52:41', '2026-07-21 01:52:41'),
(29, 13, 5, 17, 8, NULL, NULL, '2026-07-21 01:52:41', '2026-07-21 01:52:41'),
(30, 15, 5, 3, 7, NULL, NULL, '2026-07-21 01:57:20', '2026-07-21 01:57:20'),
(31, 16, 5, 7, 6, NULL, NULL, '2026-07-21 01:58:51', '2026-07-21 01:58:51'),
(32, 16, 5, 7, 7, NULL, NULL, '2026-07-21 01:58:51', '2026-07-21 01:58:51'),
(33, 16, 5, 7, 8, NULL, NULL, '2026-07-21 01:58:51', '2026-07-21 01:58:51'),
(34, 17, 5, 4, 6, NULL, NULL, '2026-07-21 02:00:35', '2026-07-21 02:00:35'),
(35, 17, 5, 4, 7, NULL, NULL, '2026-07-21 02:00:35', '2026-07-21 02:00:35'),
(36, 17, 5, 4, 8, NULL, NULL, '2026-07-21 02:00:35', '2026-07-21 02:00:35'),
(37, 22, 5, 11, 6, NULL, NULL, '2026-07-21 02:14:40', '2026-07-21 02:14:40'),
(38, 21, 1, 7, 6, NULL, NULL, '2026-07-21 02:25:20', '2026-07-21 02:25:20'),
(39, 21, 2, 7, 7, NULL, NULL, '2026-07-21 02:25:20', '2026-07-21 02:25:20'),
(40, 21, 4, 7, 6, NULL, NULL, '2026-07-21 02:25:20', '2026-07-21 02:25:20'),
(41, 21, 4, 16, 6, 1, NULL, '2026-07-21 02:25:20', '2026-07-21 02:25:20'),
(42, 21, 4, 16, 7, 1, NULL, '2026-07-21 02:25:20', '2026-07-21 02:25:20'),
(43, 21, 4, 16, 8, 1, NULL, '2026-07-21 02:25:20', '2026-07-21 02:25:20'),
(44, 21, 3, 12, 7, NULL, NULL, '2026-07-21 02:25:20', '2026-07-21 02:25:20');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_sections`
--

CREATE TABLE `teacher_sections` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `class_id` bigint(20) UNSIGNED DEFAULT NULL,
  `section_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_sections`
--

INSERT INTO `teacher_sections` (`id`, `teacher_id`, `class_id`, `section_id`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, 2, NULL, NULL),
(2, 3, NULL, 1, NULL, NULL),
(3, 3, NULL, 3, NULL, NULL),
(4, 4, NULL, 6, NULL, NULL),
(5, 4, NULL, 7, NULL, NULL),
(6, 4, NULL, 8, NULL, NULL),
(7, 6, NULL, 6, NULL, NULL),
(8, 6, NULL, 7, NULL, NULL),
(9, 6, NULL, 8, NULL, NULL),
(10, 8, NULL, 6, NULL, NULL),
(11, 8, NULL, 7, NULL, NULL),
(12, 8, NULL, 8, NULL, NULL),
(13, 10, NULL, 6, NULL, NULL),
(14, 10, NULL, 7, NULL, NULL),
(15, 10, NULL, 8, NULL, NULL),
(16, 13, NULL, 6, NULL, NULL),
(17, 13, NULL, 7, NULL, NULL),
(18, 13, NULL, 8, NULL, NULL),
(19, 15, NULL, 7, NULL, NULL),
(20, 16, NULL, 6, NULL, NULL),
(21, 16, NULL, 7, NULL, NULL),
(22, 16, NULL, 8, NULL, NULL),
(23, 17, NULL, 6, NULL, NULL),
(24, 17, NULL, 7, NULL, NULL),
(25, 17, NULL, 8, NULL, NULL),
(26, 22, NULL, 6, NULL, NULL),
(27, 21, NULL, 6, NULL, NULL),
(28, 21, NULL, 7, NULL, NULL),
(29, 21, NULL, 8, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `subject_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`id`, `teacher_id`, `subject_id`, `created_at`, `updated_at`) VALUES
(1, 3, 11, NULL, NULL),
(2, 3, 15, NULL, NULL),
(3, 3, 12, NULL, NULL),
(4, 4, 4, NULL, NULL),
(5, 6, 5, NULL, NULL),
(6, 6, 6, NULL, NULL),
(7, 8, 8, NULL, NULL),
(8, 10, 24, NULL, NULL),
(9, 10, 12, NULL, NULL),
(10, 13, 17, NULL, NULL),
(11, 15, 3, NULL, NULL),
(12, 16, 7, NULL, NULL),
(13, 17, 4, NULL, NULL),
(14, 22, 11, NULL, NULL),
(15, 21, 7, NULL, NULL),
(16, 21, 16, NULL, NULL),
(17, 21, 12, NULL, NULL),
(18, 25, 29, '2026-07-21 09:55:46', '2026-07-21 09:55:46'),
(19, 25, 30, '2026-07-21 09:55:46', '2026-07-21 09:55:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_manages`
--
ALTER TABLE `class_manages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cultivation_admins`
--
ALTER TABLE `cultivation_admins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cultivation_admins_primary_class_id_index` (`primary_class_id`),
  ADD KEY `cultivation_admins_primary_section_id_index` (`primary_section_id`);

--
-- Indexes for table `section_manages`
--
ALTER TABLE `section_manages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_class_unique` (`teacher_id`,`class_id`);

--
-- Indexes for table `teacher_class_subjects`
--
ALTER TABLE `teacher_class_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_class_subject_unique` (`teacher_id`,`class_id`,`section_id`,`subject_id`),
  ADD KEY `teacher_class_subjects_group_id_index` (`group_id`);

--
-- Indexes for table `teacher_sections`
--
ALTER TABLE `teacher_sections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `teacher_subject_unique` (`teacher_id`,`subject_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `class_manages`
--
ALTER TABLE `class_manages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `cultivation_admins`
--
ALTER TABLE `cultivation_admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `section_manages`
--
ALTER TABLE `section_manages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `teacher_class_subjects`
--
ALTER TABLE `teacher_class_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `teacher_sections`
--
ALTER TABLE `teacher_sections`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
