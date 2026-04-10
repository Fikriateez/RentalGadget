-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 09:18 AM
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
-- Database: `admin`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `gadget_id` int(11) NOT NULL,
  `rental_date` date NOT NULL,
  `return_deadline` date NOT NULL,
  `days` int(11) NOT NULL DEFAULT 1,
  `hours` int(11) NOT NULL DEFAULT 0,
  `status` varchar(50) DEFAULT 'pending',
  `total_price` decimal(10,2) DEFAULT NULL,
  `deposit_paid` decimal(10,2) DEFAULT 0.00,
  `deposit_status` varchar(50) DEFAULT 'Pending',
  `late_fee` decimal(10,2) DEFAULT 0.00,
  `refund_amount` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `gadget_id`, `rental_date`, `return_deadline`, `days`, `hours`, `status`, `total_price`, `deposit_paid`, `deposit_status`, `late_fee`, `refund_amount`) VALUES
(1, 1, 2, '2026-03-31', '2026-04-01', 1, 0, 'Completed', 200.00, 0.00, 'Paid', 0.00, 0.00),
(2, 1, 2, '2026-04-03', '2026-04-04', 1, 0, 'Completed', 200.00, 100.00, 'Paid', 0.00, 100.00),
(3, 1, 2, '2026-04-03', '2026-04-03', 0, 8, 'Completed', 240.00, 100.00, 'Paid', 390.00, 0.00),
(4, 1, 6, '2026-04-04', '2026-04-04', 0, 3, 'Completed', 30.00, 80.00, 'Paid', 60.00, 20.00),
(6, 1, 6, '2026-04-10', '2026-04-10', 0, 2, 'CANCEL', 20.00, 0.00, 'Unpaid', 0.00, 0.00),
(7, 1, 5, '2026-04-10', '2026-04-10', 0, 4, 'CANCEL', 80.00, 0.00, 'Unpaid', 0.00, 0.00),
(8, 1, 2, '2026-04-10', '2026-04-10', 0, 10, 'Completed', 300.00, 100.00, 'Paid', 210.00, 0.00),
(9, 1, 5, '2026-04-10', '2026-04-10', 0, 9, 'Completed', 180.00, 100.00, 'Paid', 140.00, 0.00),
(10, 1, 5, '2026-04-10', '2026-04-10', 0, 8, 'Completed', 160.00, 100.00, 'Paid', 160.00, 0.00),
(11, 1, 6, '2026-04-10', '2026-04-10', 0, 8, 'Completed', 80.00, 80.00, 'Paid', 80.00, 0.00),
(12, 1, 5, '2026-04-10', '2026-04-10', 0, 9, 'Completed', 180.00, 100.00, 'Paid', 0.00, 100.00),
(13, 1, 6, '2026-04-10', '2026-04-10', 0, 6, 'Completed', 60.00, 80.00, 'Paid', 0.00, 80.00),
(14, 1, 2, '2026-04-10', '2026-04-11', 1, 8, 'Completed', 440.00, 100.00, 'Paid', 0.00, 100.00),
(15, 1, 2, '2026-04-10', '2026-04-11', 1, 8, 'Completed', 440.00, 100.00, 'Paid', 0.00, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `register`
--

CREATE TABLE `register` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price_day` decimal(10,2) NOT NULL,
  `price_hour` decimal(10,2) DEFAULT 0.00,
  `deposit_price` decimal(10,2) DEFAULT 0.00,
  `image` longtext DEFAULT NULL,
  `description` text DEFAULT NULL,
  `specs` text DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `register`
--

INSERT INTO `register` (`id`, `name`, `price_day`, `price_hour`, `deposit_price`, `image`, `description`, `specs`, `stock`, `full_name`, `phone`, `address`, `user_id`) VALUES
(2, 'GO PRO', 200.00, 30.00, 100.00, 'uploads/69cf75dbcdee7.png', NULL, '20X ZOOM', 5, '', '', NULL, NULL),
(5, 'IPHONE 17', 200.00, 20.00, 100.00, 'uploads/69d07cb1685c9.png', NULL, '10X ZOOM', 5, '', '', NULL, NULL),
(6, 'SAMSUNG S23 ULTRA', 150.00, 10.00, 80.00, 'uploads/69d07ca25d3ed.png', NULL, '10X ZOOM', 10, '', '', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `site_name` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `footer_text` text DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `location_name` varchar(255) DEFAULT NULL,
  `map_link` text DEFAULT NULL,
  `special_notice` text DEFAULT NULL,
  `show_notice` tinyint(1) DEFAULT 0,
  `mon_hours` varchar(50) DEFAULT '09:00 - 18:00',
  `tue_hours` varchar(50) DEFAULT '09:00 - 18:00',
  `wed_hours` varchar(50) DEFAULT '09:00 - 18:00',
  `thu_hours` varchar(50) DEFAULT '09:00 - 18:00',
  `fri_hours` varchar(50) DEFAULT '09:00 - 18:00',
  `sat_hours` varchar(50) DEFAULT '09:00 - 18:00',
  `sun_hours` varchar(50) DEFAULT 'Closed'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `site_settings`
--

INSERT INTO `site_settings` (`id`, `site_name`, `email`, `contact_email`, `address`, `logo`, `phone`, `footer_text`, `whatsapp`, `location_name`, `map_link`, `special_notice`, `show_notice`, `mon_hours`, `tue_hours`, `wed_hours`, `thu_hours`, `fri_hours`, `sat_hours`, `sun_hours`) VALUES
(1, 'Rental Gadget', 'support@rentalgadget.com', 'admin@mail.com', NULL, NULL, NULL, NULL, '0123456789', 'Main Store', 'https://maps.app.goo.gl/N5RsqKtQoRcoJwAH8', 'emergency closed:friday', 0, '9am-6pm', '9am-6pm', '9am-6pm', '9am-6pm', '9am-6pm', '10am-4pm', 'Closed');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `password`, `role`, `created_at`) VALUES
(1, 'MUHAMMAD', 'fikries184@gmail.com', '0182671172', NULL, '$2y$10$KBo.kaoz38L09GSZevfU6e1v4uFzE7jh64hJmkoR/M2OCzGyhipPa', 'user', '2026-03-31 08:08:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `register`
--
ALTER TABLE `register`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `register`
--
ALTER TABLE `register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
