-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:4306
-- Generation Time: Jul 13, 2026 at 03:44 PM
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
-- Database: `dd`
--

-- --------------------------------------------------------

--
-- Table structure for table `careers`
--

CREATE TABLE `careers` (
  `career_id` int(11) NOT NULL,
  `job_family` varchar(255) NOT NULL,
  `job_title` varchar(255) NOT NULL,
  `holland_code` varchar(10) DEFAULT NULL,
  `beginner_level` text DEFAULT NULL,
  `intermediate_level` text DEFAULT NULL,
  `beginner_tools` text DEFAULT NULL,
  `intermediate_tools` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `careers`
--

INSERT INTO `careers` (`career_id`, `job_family`, `job_title`, `holland_code`, `beginner_level`, `intermediate_level`, `beginner_tools`, `intermediate_tools`) VALUES
(1, 'Infrastructure', 'Network Engineering', 'CRI', 'Python - Routing/Switching - OSI model - Hardware basic - TCP/IP', 'Firewall Configuration - VPN Setup - Network Monitoring - Full CCNA - Networking basic', 'VS Code - GitHub - TeamViewer - Cisco Packet Network - PuTTy', 'Wireshark - SolarWinds - PRTG'),
(2, 'Infrastructure', 'Broadcast Engineering', 'CR', 'Python - TCP/IP - JavaScript - CompTIA - Networking Basics', 'Full CCNA - Networking basic - Infrastructure as Code - Cloud Security - Network protocols', 'VS Code - GitHub - PuTTy - Bash/Python', 'Terraform - Docker'),
(3, 'R&D and product development', 'Electronics Engineers', 'RIC', 'C++ - Python - Data analysis - Oracle database', 'Deep learning - Usability Testing - CNN & RNN - NLP Basics - Computer Vision', 'VS Code - GitHub - oracle', 'TransorFlow - InVision - PyTorch'),
(4, 'Software', 'Software Developers', 'ICR', 'Machine Learning - Linux Basics - Git - JavaScript - CI/CD', 'Linux system - Agile - Scrum - Deep learning - Jenkins - GitHub Actions', 'Scikit learn - Linux - Git - Bash/Python', 'Linux - Docker - TransorFlow - Jenkins'),
(5, 'Software', 'Computer Systems Analysts', 'IC', 'ETL Concepts - PowerShell - Python - ITIL Basics - Test Case Writing', 'SQL - API Testing - REST APIs - Active Directory - CI/CD', 'VS Code - GitHub - TestRail', 'PostgreSQL - Postman - Hardhat - Express - Vmware - Ansible'),
(6, 'Data and AI', 'Database Analysts', 'CI', 'SQL - Python - ETL Concepts - Databases - AWS - Azure', 'Data warehousing - Apache Spark - Airflow - Data Modeling - Unit Testing', 'Python - PostgreSQL - dbt - AWS Free Tier/Azure Portal', 'Spark - Airflow - Snowflake - dbt'),
(7, 'Data and AI', 'Business Intelligence Analysts', 'CIE', 'Python - Microsoft Excel - Git - SQL - ETL Concepts', 'SQL Server - Power BI - Tableau - Data Visualization - Statistical Analysis', 'Jupyter - GitHub - Pandas - Matplotlib', 'MySQL - Power BI - Tableau - Scikit learn'),
(8, 'Data protection and cybersecurity', 'Cloud Engineer', 'CIR', 'Cloud Basics - AWS - Azure - Linux Basics - Networking Basics', 'Cloud security - Container - CI/CD - Docker - Infrastructure as Code', 'AWS Free Tier - Azure Portal - Linux', 'Docker - Terraform - Ansible - Jenkins'),
(9, 'Sales & marketing', 'Sales Managers', 'EC', 'Microsoft Excel - Basic Statistics - Processes & Threads Basics - CRM concept - Sales methodologies', 'Power BI - Tableau - Analytics - Networking basic - Data Visualization', 'Microsoft Excel', 'Power BI - Tableau - Wireshark'),
(10, 'Hardware', 'Electromechanical Equipment Assemblers', 'RCI', 'Networking Basics - Microcontrollers - Actuators - CompTIA - Digital Electronics Basics', 'Communication Protocols - Quantum Algorithms - Quantum Gates - Full CCNA - Networking basic', 'TeamViewer - Arduino - Proteus', 'Cirq - PennyLane - Wireshark'),
(11, 'Infrastructure', 'Web Administrators', 'CIE', 'TCP/IP - AWS - Azure - Git - Backup', 'Operating system - Windows Server - PostgreSQL - Apache - DNS', 'Azure Portal - AWS Free Tie', 'Linux - VMware - Docker - PostgreSQL - Spark'),
(12, 'Software', 'Web Developers', 'CI', 'HTML - CSS - JavaScript - Responsive design - Git', 'Node.js - SQL database - RESTful APIs - React - Vue', 'Figma - Chrome DevTools - VS code', 'PostgreSQL - Express - React - Postman'),
(13, 'Design and animation', 'Video Game Designers', 'AI', 'C++ - C# - Unity - Unreal Engine - Git', '3D Development - physics - collision - game AI - Audio integration', 'Aseprite - VS code - Unity', 'Blender - Unreal Engine - FMOD'),
(14, 'Software', 'Computer and Information Systems Managers', 'CEI', 'TCP/IP - Routing/Switching - Cybersecurity Basics - Operating System - Oracle database', 'SQL Server - PostgreSQL - Firewall Configuration - Agile - Scrum', 'Oracle - Cisco Packet Network', 'MySQL - PostgreSQL - Vmware'),
(15, 'Data protection and cybersecurity', 'IT Security Analysts', 'CI', 'Linux Basics - AWS - Azure - Risk management basics - Splunk', 'OWASP - Firewall Configuration - Active Directory - Cloud Security - Web App Security', 'Kali Linux - Azure Portal - AWS Free Tier - Splunk', 'Nmap - Metasploit'),
(16, 'Project management', 'Information Technology Project Managers', 'ECI', 'Cloud Basics - AWS - Azure - Risk management basics - Cybersecurity basics', 'Project Management - Data Analysis - Agile - Scrum - Tableau', 'Azure Portal - AWS Free Tier', 'MS Project - Power BI - Tableau'),
(17, 'Quality', 'Software Quality Engineer', 'ICR', 'Manual testing - Test Case Writing - Bug reporting - Agile - Scrum', 'API Testing - CI Integration - Python for testing - Java - Selenium WebDriver', 'Jira - Postman - TestRail', 'Postman - Jenkins - Selenium'),
(18, 'Hardware', 'Computer Hardware Engineers', 'RIC', 'C - C++ - Digital Electronics - Microprocessor - Microcontroller', 'FPGA programming - VHDL - Verilog - Simulink - ASIC', 'Multisim - Arduino - Proteus', 'MATLAB - Keil - ModelSim'),
(19, 'Infrastructure', 'IT Support', 'CRI', 'CompTIA - Machine Learning - IT Basics - Security concepts - Operating system', 'Computer vision - Ethical hacking - Machine learning - OWASP - Deep learning', 'Scikit learn - Packet tracer - Linux', 'Metasploit - TransorFlow'),
(20, 'Software', 'Search Marketing Strategists', 'ECI', 'Microsoft Excel - HTML - CSS - Reporting - Keyword Research tool', 'Data Visualization - A/B Testing - UX principles - Website Analytics - Google Analytics', 'Microsoft Excel - VS code - Bing Webmaster - Google Keyword Planner', 'Tableau - Figma');

-- --------------------------------------------------------

--
-- Table structure for table `hollandresult`
--

CREATE TABLE `hollandresult` (
  `result_id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `test_version` varchar(10) NOT NULL DEFAULT 'v1',
  `top3_code` char(3) NOT NULL,
  `scores_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`scores_json`)),
  `percentages_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`percentages_json`)),
  `summary_text` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

CREATE TABLE `plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `skill` varchar(255) DEFAULT NULL,
  `weeks` int(11) DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_days`
--

CREATE TABLE `plan_days` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `day_number` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `task` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `progress`
--

CREATE TABLE `progress` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `plan_day_id` int(11) DEFAULT NULL,
  `completed` tinyint(1) DEFAULT 1,
  `completed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skill` varchar(255) DEFAULT 'General',
  `quiz_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`quiz_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `plan_id` int(11) DEFAULT NULL,
  `score` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `userlogininformation`
--

CREATE TABLE `userlogininformation` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `log_failed_attempt` int(11) NOT NULL DEFAULT 0,
  `verified` tinyint(1) NOT NULL DEFAULT 0,
  `OTP` varchar(6) DEFAULT NULL,
  `otp_expires` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_attempt` datetime DEFAULT NULL,
  `academic_status` varchar(50) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `userpersonalinfo`
--

CREATE TABLE `userpersonalinfo` (
  `user_skills` text DEFAULT NULL,
  `other_skills` text DEFAULT NULL,
  `cv_file_name` varchar(255) DEFAULT NULL,
  `cv_file_path` varchar(255) DEFAULT NULL,
  `cv_status` varchar(100) DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_career`
--

CREATE TABLE `user_career` (
  `career_user_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `career_id` int(11) NOT NULL,
  `career_title` varchar(255) NOT NULL,
  `overall_match` tinyint(3) UNSIGNED NOT NULL,
  `holland_match` tinyint(3) UNSIGNED NOT NULL,
  `skills_match` tinyint(3) UNSIGNED NOT NULL,
  `strengths` text DEFAULT NULL,
  `gaps` text DEFAULT NULL,
  `selected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completion_email_sent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_plans`
--

CREATE TABLE `user_plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `skill` varchar(255) DEFAULT NULL,
  `weeks` int(11) DEFAULT NULL,
  `days` int(11) DEFAULT NULL,
  `plan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`plan`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `careers`
--
ALTER TABLE `careers`
  ADD PRIMARY KEY (`career_id`);

--
-- Indexes for table `hollandresult`
--
ALTER TABLE `hollandresult`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `idx_result_user_created` (`user_id`,`created_at`);

--
-- Indexes for table `plans`
--
ALTER TABLE `plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_plans` (`user_id`);

--
-- Indexes for table `plan_days`
--
ALTER TABLE `plan_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_plan_days_plan` (`plan_id`);

--
-- Indexes for table `progress`
--
ALTER TABLE `progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_progress_user` (`user_id`),
  ADD KEY `fk_progress_plan_day` (`plan_day_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_plan` (`plan_id`);

--
-- Indexes for table `userlogininformation`
--
ALTER TABLE `userlogininformation`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_career`
--
ALTER TABLE `user_career`
  ADD PRIMARY KEY (`career_user_id`),
  ADD UNIQUE KEY `unique_user_career` (`user_id`),
  ADD KEY `fk_user_career_career` (`career_id`);

--
-- Indexes for table `user_plans`
--
ALTER TABLE `user_plans`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `careers`
--
ALTER TABLE `careers`
  MODIFY `career_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `hollandresult`
--
ALTER TABLE `hollandresult`
  MODIFY `result_id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `plans`
--
ALTER TABLE `plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `plan_days`
--
ALTER TABLE `plan_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=444;

--
-- AUTO_INCREMENT for table `progress`
--
ALTER TABLE `progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `userlogininformation`
--
ALTER TABLE `userlogininformation`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_career`
--
ALTER TABLE `user_career`
  MODIFY `career_user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `user_plans`
--
ALTER TABLE `user_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `plans`
--
ALTER TABLE `plans`
  ADD CONSTRAINT `fk_user_plans` FOREIGN KEY (`user_id`) REFERENCES `userlogininformation` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `plan_days`
--
ALTER TABLE `plan_days`
  ADD CONSTRAINT `fk_plan_days_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `progress`
--
ALTER TABLE `progress`
  ADD CONSTRAINT `fk_progress_plan_day` FOREIGN KEY (`plan_day_id`) REFERENCES `plan_days` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_progress_user` FOREIGN KEY (`user_id`) REFERENCES `userlogininformation` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `fk_plan` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `delete_unverified_accounts_after_72_hours` ON SCHEDULE EVERY 1 HOUR STARTS '2026-05-06 02:58:26' ON COMPLETION NOT PRESERVE ENABLE DO DELETE FROM userlogininformation
WHERE verified = 0
AND created_at < (NOW() - INTERVAL 72 HOUR)$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
