-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 05, 2026 at 02:32 AM
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
-- Database: `persada_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` varchar(50) DEFAULT 'Admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `full_name`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$uO5sS0WNccY35cW3VmDXNeK.Rj7ZiYopz6l0qZ0Tk5K9YFw3JO0s.', 'PERSADA Administrator', 'Admin', '2026-06-02 04:14:05');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `scan_time` datetime DEFAULT current_timestamp(),
  `attendance_status` enum('Present','Absent') DEFAULT 'Present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `event_id`, `scan_time`, `attendance_status`) VALUES
(1, 1, 3, '2026-06-02 19:47:23', 'Present'),
(2, 1, 4, '2026-06-02 22:34:03', 'Present'),
(3, 1, 5, '2026-06-02 23:07:43', 'Present'),
(4, 3, 3, '2026-06-02 23:25:26', 'Present'),
(5, 3, 4, '2026-06-05 06:35:27', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(150) NOT NULL,
  `event_category` varchar(100) DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` varchar(50) NOT NULL,
  `venue` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `event_poster` varchar(255) DEFAULT NULL,
  `max_participants` int(11) DEFAULT 0,
  `registration_deadline` date DEFAULT NULL,
  `created_by` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
  `qr_token` varchar(255) DEFAULT NULL,
  `certificate_released` enum('Yes','No') DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_name`, `event_category`, `event_date`, `event_time`, `venue`, `description`, `event_poster`, `max_participants`, `registration_deadline`, `created_by`, `created_at`, `status`, `qr_token`, `certificate_released`) VALUES
(3, 'E-sports', 'Sports', '2026-06-17', '09:00', 'ASTAKA PADANG KAWAD UTHM', 'GAME', 'uploads/events/1780387068_Esports.png', 17, '2026-06-10', 'PERSADA Administrator', '2026-06-02 07:57:48', 'Upcoming', '30a05f58d131b10923de7b837504936d', 'Yes'),
(4, 'Run For Wellness', 'Sports', '2026-06-17', '07:00', 'Padang Kawad UTHM', 'run for fun', 'uploads/events/1780405429_Run_for_wellness.png', 19, '2026-06-10', 'PERSADA Administrator', '2026-06-02 13:03:49', 'Upcoming', 'c320d29d48155632c975d5d1280f69dc', 'No'),
(5, 'MULTIMEDIA WORKSHOP', 'Workshop', '2026-06-23', '20:30', 'FB Live PERSADA UTHM', 'elajar design guna apa ni ?\r\n🔥Semestinya Canva yang sering menjadi pilihan mahasiswa/i\r\n\r\nJadi, apa Kelebihan Program Multimedia Workshop❓❓\r\n✅ Dapat mengasaskan kemahiran diri menggunakan Canva\r\n✅ Dapat menambahkan ilmu pengetahuan dan kreativiti seseorang dalam membuat poster program', 'uploads/events/1780412640_Screenshot 2026-06-02 230203.png', 31, '2026-06-16', 'PERSADA Administrator', '2026-06-02 15:04:00', 'Upcoming', '44c8c70c90d33a8e07f67d1de95a7f22', 'No'),
(6, 'Volunteers for MIASA MALAYSIA', 'Volunteer', '2026-07-01', '07:00', 'MIASA  Johor Clubhouse', 'Volunteers for MIASA MALAYSIA - Launching Johor Clubhouse\r\nMIASA Malaysia’s Re - Launch 🌸  \r\nCalling all Johoreans Volunteers, we’re coming to you!\r\nWe’re excited to announce the official launch of MIASA Malaysia Johor! 🌿 As we expand our mission to promote mental health awareness and community wellbeing, we’re also opening up volunteering opportunities for passionate individuals who want to make a difference. This is your chance to be part of something meaningful — support our event, connect with the community, and help spread hope across Johor. Your time, your heart, your impact. 💚 Join us as volunteers in this journey of compassion and change!', 'uploads/events/1780615881_Volunteer.png', 29, '2026-06-30', 'PERSADA Administrator', '2026-06-04 23:31:21', 'Upcoming', NULL, 'No');

-- --------------------------------------------------------

--
-- Table structure for table `event_registration`
--

CREATE TABLE `event_registration` (
  `registration_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `attendance_status` enum('Absent','Present') DEFAULT 'Absent',
  `certificate_status` enum('Not Issued','Issued') DEFAULT 'Not Issued'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_registration`
--

INSERT INTO `event_registration` (`registration_id`, `student_id`, `event_id`, `registration_date`, `attendance_status`, `certificate_status`) VALUES
(1, 1, 3, '2026-06-02 09:10:05', 'Absent', 'Not Issued'),
(2, 1, 4, '2026-06-02 13:04:25', 'Absent', 'Not Issued'),
(3, 1, 5, '2026-06-02 15:04:40', 'Absent', 'Not Issued'),
(4, 3, 3, '2026-06-02 15:17:29', 'Absent', 'Not Issued'),
(5, 3, 4, '2026-06-02 15:17:39', 'Absent', 'Not Issued'),
(6, 3, 5, '2026-06-02 15:17:45', 'Absent', 'Not Issued'),
(7, 3, 6, '2026-06-04 23:32:13', 'Absent', 'Not Issued'),
(8, 1, 6, '2026-06-05 00:01:33', 'Absent', 'Not Issued');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `matric_number` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `faculty` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `gender` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `programme` varchar(100) DEFAULT NULL,
  `year_of_study` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `name`, `matric_number`, `email`, `phone_number`, `faculty`, `password`, `created_at`, `gender`, `date_of_birth`, `programme`, `year_of_study`, `profile_picture`, `status`) VALUES
(1, 'dina Iriesya', 'CI240059', 'nurirdinairiesya@gmail.com', '0166780186', 'FSKTM', '$2y$10$A34gD8MsoQThie6C51AW3e9GUUJQRLDRfiN9vI1ypTdj5wXq1meIi', '2026-05-31 06:11:53', 'Female', '2003-07-15', 'BIP - Bachelor of Computer Science (Software Engineering)', 'Year 2', 'uploads/profile/1780616636_6120576032189387334.jpg', 'Active'),
(3, 'SiapaTu', 'CI240060', 'SiapaTu@gmail.com', '0164567777', 'FKEE', '$2y$10$iYHQqdSDDXLjDAcL.GKON.GV7Qs4Sdz1A7ri1LO9fgunL8WjkXbUu', '2026-06-02 04:36:32', 'Female', '2026-06-01', 'BEV - Bachelor of Electrical Engineering', 'Year 2', 'uploads/profile/1780375067_young-housewife-apron-white-background-holds-her-beloved-pet-big-fluffy-cat-happy-family.jpg', 'Active');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `event_registration`
--
ALTER TABLE `event_registration`
  ADD PRIMARY KEY (`registration_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `event_id` (`event_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matric_number` (`matric_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `event_registration`
--
ALTER TABLE `event_registration`
  MODIFY `registration_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `event_registration`
--
ALTER TABLE `event_registration`
  ADD CONSTRAINT `event_registration_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`),
  ADD CONSTRAINT `event_registration_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
