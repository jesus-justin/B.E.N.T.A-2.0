-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 01, 2025 at 04:00 AM
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
-- Database: `benta_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetFinancialHealthScore` (IN `p_user_id` INT, IN `p_period_months` INT)   BEGIN
    DECLARE income_total DECIMAL(15,2) DEFAULT 0;
    DECLARE expense_total DECIMAL(15,2) DEFAULT 0;
    DECLARE savings_rate DECIMAL(5,2) DEFAULT 0;
    DECLARE score INT DEFAULT 0;
    
    SELECT COALESCE(SUM(amount), 0) INTO income_total
    FROM transactions 
    WHERE user_id = p_user_id 
    AND status = 'completed'
    AND trx_date >= DATE_SUB(CURDATE(), INTERVAL p_period_months MONTH);
    
    SELECT COALESCE(SUM(amount), 0) INTO expense_total
    FROM expenses 
    WHERE user_id = p_user_id 
    AND status = 'paid'
    AND expense_date >= DATE_SUB(CURDATE(), INTERVAL p_period_months MONTH);
    
    IF income_total > 0 THEN
        SET savings_rate = ((income_total - expense_total) / income_total) * 100;
        
        CASE 
            WHEN savings_rate >= 20 THEN SET score = 100;
            WHEN savings_rate >= 15 THEN SET score = 90;
            WHEN savings_rate >= 10 THEN SET score = 80;
            WHEN savings_rate >= 5 THEN SET score = 70;
            WHEN savings_rate >= 0 THEN SET score = 60;
            ELSE SET score = 40;
        END CASE;
    END IF;
    
    SELECT income_total, expense_total, savings_rate, score;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `GetUserDashboardData` (IN `p_user_id` INT)   BEGIN
    SELECT 
        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = p_user_id AND status = 'completed') as total_income,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE user_id = p_user_id AND status = 'paid') as total_expenses,
        (SELECT COUNT(*) FROM transactions WHERE user_id = p_user_id) as total_transactions,
        (SELECT COUNT(*) FROM expenses WHERE user_id = p_user_id) as total_expense_records,
        (SELECT COUNT(*) FROM notifications WHERE user_id = p_user_id AND is_read = 0) as unread_notifications;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','moderator') DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `full_name`, `role`, `is_active`, `created_at`, `updated_at`, `last_login`, `created_by`) VALUES
(1, 'admin', 'admin@benta.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 1, '2025-10-01 00:59:28', '2025-10-01 00:59:28', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `spent_amount` decimal(15,2) DEFAULT 0.00,
  `period_type` enum('daily','weekly','monthly','quarterly','yearly') DEFAULT 'monthly',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `alert_percentage` decimal(5,2) DEFAULT 80.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#007bff',
  `icon` varchar(50) DEFAULT 'fas fa-circle',
  `is_active` tinyint(1) DEFAULT 1,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `type`, `description`, `color`, `icon`, `is_active`, `user_id`, `created_at`, `updated_at`) VALUES
(1, 'Salary', 'income', 'Regular employment income', '#28a745', 'fas fa-briefcase', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(2, 'Freelance Work', 'income', 'Freelance and contract work', '#17a2b8', 'fas fa-laptop-code', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(3, 'Business Income', 'income', 'Revenue from business operations', '#ffc107', 'fas fa-building', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(4, 'Investment Returns', 'income', 'Dividends, interest, and capital gains', '#6f42c1', 'fas fa-chart-line', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(5, 'Rental Income', 'income', 'Income from property rentals', '#fd7e14', 'fas fa-home', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(6, 'Side Hustle', 'income', 'Additional income streams', '#20c997', 'fas fa-coins', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(7, 'Bonus', 'income', 'Performance bonuses and incentives', '#e83e8c', 'fas fa-gift', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(8, 'Commission', 'income', 'Sales commissions and referrals', '#6610f2', 'fas fa-handshake', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(9, 'Other Income', 'income', 'Miscellaneous income sources', '#6c757d', 'fas fa-plus-circle', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(10, 'Food & Dining', 'expense', 'Meals, groceries, and dining out', '#dc3545', 'fas fa-utensils', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(11, 'Transportation', 'expense', 'Gas, public transport, vehicle maintenance', '#007bff', 'fas fa-car', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(12, 'Utilities', 'expense', 'Electricity, water, internet, phone bills', '#28a745', 'fas fa-plug', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(13, 'Entertainment', 'expense', 'Movies, games, subscriptions, hobbies', '#ffc107', 'fas fa-gamepad', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(14, 'Shopping', 'expense', 'Clothing, electronics, personal items', '#e83e8c', 'fas fa-shopping-bag', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(15, 'Healthcare', 'expense', 'Medical expenses, insurance, pharmacy', '#17a2b8', 'fas fa-user-md', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(16, 'Education', 'expense', 'Tuition, books, courses, training', '#6f42c1', 'fas fa-graduation-cap', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(17, 'Insurance', 'expense', 'Life, health, auto, property insurance', '#fd7e14', 'fas fa-shield-alt', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(18, 'Rent/Mortgage', 'expense', 'Housing payments and property costs', '#20c997', 'fas fa-home', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(19, 'Personal Care', 'expense', 'Salon, spa, beauty, grooming', '#6610f2', 'fas fa-cut', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(20, 'Travel', 'expense', 'Vacation, business trips, accommodation', '#dc3545', 'fas fa-plane', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(21, 'Fitness', 'expense', 'Gym, sports, health and wellness', '#28a745', 'fas fa-dumbbell', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(22, 'Subscriptions', 'expense', 'Software, streaming, memberships', '#007bff', 'fas fa-sync', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(23, 'Business Expenses', 'expense', 'Office supplies, equipment, services', '#ffc107', 'fas fa-briefcase', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(24, 'Miscellaneous', 'expense', 'Other uncategorized expenses', '#6c757d', 'fas fa-ellipsis-h', 1, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(25, 'Printing Services', 'income', 'Business cards, flyers, banners, invitations', '#007bff', 'fas fa-print', 1, NULL, '2025-10-01 01:09:29', '2025-10-01 01:09:29'),
(26, 'Custom Tailoring', 'income', 'Suits, dresses, alterations, repairs', '#28a745', 'fas fa-cut', 1, NULL, '2025-10-01 01:09:29', '2025-10-01 01:09:29'),
(27, 'Design Services', 'income', 'Logo design, layouts, creative work', '#6f42c1', 'fas fa-palette', 1, NULL, '2025-10-01 01:09:29', '2025-10-01 01:09:29'),
(28, 'Embroidery Work', 'income', 'Company logos, patches, personalization', '#fd7e14', 'fas fa-star', 1, NULL, '2025-10-01 01:09:29', '2025-10-01 01:09:29'),
(29, 'Bulk Orders', 'income', 'Uniforms, corporate orders, events', '#ffc107', 'fas fa-boxes', 1, NULL, '2025-10-01 01:09:29', '2025-10-01 01:09:29'),
(30, 'Raw Materials', 'expense', 'Papers, fabrics, inks, threads, supplies', '#dc3545', 'fas fa-box', 1, NULL, '2025-10-01 01:09:29', '2025-10-01 01:09:29'),
(31, 'Equipment Maintenance', 'expense', 'Printer servicing, sewing machine repairs', '#17a2b8', 'fas fa-tools', 1, NULL, '2025-10-01 01:09:29', '2025-10-01 01:09:29'),
(32, 'Staff Salaries', 'expense', 'Employee wages and benefits', '#20c997', 'fas fa-users', 1, NULL, '2025-10-01 01:09:29', '2025-10-01 01:09:29'),
(33, 'Business Rent', 'expense', 'Shop space rental and property costs', '#6610f2', 'fas fa-building', 1, NULL, '2025-10-01 01:09:29', '2025-10-01 01:09:29'),
(34, 'Marketing/Advertising', 'expense', 'Social media ads, flyers, promotions', '#e83e8c', 'fas fa-bullhorn', 1, NULL, '2025-10-01 01:09:29', '2025-10-01 01:09:29');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `vendor` varchar(150) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `payment_method` enum('cash','bank_transfer','credit_card','debit_card','check','digital_wallet','other') DEFAULT 'cash',
  `status` enum('pending','paid','overdue','cancelled') DEFAULT 'paid',
  `expense_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurring_frequency` enum('daily','weekly','monthly','quarterly','yearly') DEFAULT NULL,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `user_id`, `category_id`, `amount`, `vendor`, `note`, `receipt_number`, `payment_method`, `status`, `expense_date`, `due_date`, `attachment`, `is_recurring`, `recurring_frequency`, `tax_amount`, `created_at`, `updated_at`) VALUES
(1, 3, 30, 25000.00, 'Paper Plus Trading', 'Various paper stocks - bond, cardstock, photo paper', 'REC-001', 'bank_transfer', 'paid', '2024-01-02', NULL, NULL, 0, NULL, 0.00, '2024-01-02 01:00:00', '2025-10-01 01:09:29'),
(2, 3, 30, 18500.00, 'Fabric World Manila', 'Cotton, polyester, lining fabrics for tailoring', 'REC-002', 'cash', 'paid', '2024-01-02', NULL, NULL, 0, NULL, 0.00, '2024-01-02 02:30:00', '2025-10-01 01:09:29'),
(3, 3, 30, 8200.00, 'Ink Supply Co.', 'Printer inks - CMYK cartridges, specialty inks', 'REC-003', 'bank_transfer', 'paid', '2024-01-03', NULL, NULL, 0, NULL, 0.00, '2024-01-03 06:20:00', '2025-10-01 01:09:29'),
(4, 3, 30, 3500.00, 'Notions & Threads', 'Threads, buttons, zippers, interfacing', 'REC-004', 'cash', 'paid', '2024-01-05', NULL, NULL, 0, NULL, 0.00, '2024-01-05 03:15:00', '2025-10-01 01:09:29'),
(5, 3, 30, 6800.00, 'Vinyl Graphics Supply', 'Vinyl rolls for banners and signage', 'REC-005', 'bank_transfer', 'paid', '2024-01-08', NULL, NULL, 0, NULL, 0.00, '2024-01-08 07:30:00', '2025-10-01 01:09:29'),
(6, 3, 30, 4200.00, 'Office Depot', 'Laminating sheets, binding materials', 'REC-006', 'credit_card', 'paid', '2024-01-12', NULL, NULL, 0, NULL, 0.00, '2024-01-12 05:45:00', '2025-10-01 01:09:29'),
(7, 3, 30, 7500.00, 'Embroidery Supplies Inc.', 'Embroidery threads, stabilizers, hoops', 'REC-007', 'bank_transfer', 'paid', '2024-01-15', NULL, NULL, 0, NULL, 0.00, '2024-01-15 08:20:00', '2025-10-01 01:09:29'),
(8, 3, 30, 3800.00, 'Paper Plus Trading', 'Emergency stock - premium cardstock', 'REC-008', 'cash', 'paid', '2024-01-22', NULL, NULL, 0, NULL, 0.00, '2024-01-22 02:45:00', '2025-10-01 01:09:29'),
(9, 3, 31, 15000.00, 'Canon Service Center', 'Large format printer maintenance and parts', 'REC-009', 'bank_transfer', 'paid', '2024-01-04', NULL, NULL, 0, NULL, 0.00, '2024-01-04 03:30:00', '2025-10-01 01:09:29'),
(10, 3, 31, 3500.00, 'Brother Sewing Machine', 'Industrial sewing machine servicing', 'REC-010', 'cash', 'paid', '2024-01-09', NULL, NULL, 0, NULL, 0.00, '2024-01-09 06:15:00', '2025-10-01 01:09:29'),
(11, 3, 31, 2800.00, 'Tech Solutions', 'Computer maintenance and software updates', 'REC-011', 'bank_transfer', 'paid', '2024-01-16', NULL, NULL, 0, NULL, 0.00, '2024-01-16 01:20:00', '2025-10-01 01:09:29'),
(12, 3, 31, 4200.00, 'Equipment Repairs Plus', 'Cutting plotter blade replacement and calibration', 'REC-012', 'cash', 'paid', '2024-01-23', NULL, NULL, 0, NULL, 0.00, '2024-01-23 07:45:00', '2025-10-01 01:09:29'),
(13, 3, 32, 18000.00, 'Maria Santos - Tailor', 'Monthly salary for head tailor', 'PAY-001', 'bank_transfer', 'paid', '2024-01-31', NULL, NULL, 0, NULL, 0.00, '2024-01-31 08:00:00', '2025-10-01 01:09:29'),
(14, 3, 32, 15000.00, 'Juan Dela Cruz - Printer Operator', 'Monthly salary for print operator', 'PAY-002', 'bank_transfer', 'paid', '2024-01-31', NULL, NULL, 0, NULL, 0.00, '2024-01-31 08:00:00', '2025-10-01 01:09:29'),
(15, 3, 32, 12000.00, 'Ana Garcia - Assistant', 'Monthly salary for shop assistant', 'PAY-003', 'bank_transfer', 'paid', '2024-01-31', NULL, NULL, 0, NULL, 0.00, '2024-01-31 08:00:00', '2025-10-01 01:09:29'),
(16, 3, 33, 25000.00, 'SM Mall Property Management', 'Monthly rent for shop space', 'RENT-001', 'bank_transfer', 'paid', '2024-01-01', NULL, NULL, 0, NULL, 0.00, '2024-01-01 01:00:00', '2025-10-01 01:09:29'),
(17, 3, 12, 8500.00, 'Meralco/Maynilad/PLDT', 'Electricity, water, internet for shop', 'UTIL-001', 'bank_transfer', 'paid', '2024-01-05', NULL, NULL, 0, NULL, 0.00, '2024-01-05 02:30:00', '2025-10-01 01:09:29'),
(18, 3, 34, 5000.00, 'Facebook Ads Manager', 'Social media advertising for January', 'AD-001', 'credit_card', 'paid', '2024-01-10', NULL, NULL, 0, NULL, 0.00, '2024-01-10 04:20:00', '2025-10-01 01:09:29'),
(19, 3, 34, 2500.00, 'Local Newspaper', 'Print advertisement in community paper', 'AD-002', 'cash', 'paid', '2024-01-15', NULL, NULL, 0, NULL, 0.00, '2024-01-15 06:30:00', '2025-10-01 01:09:29'),
(20, 3, 34, 3200.00, 'Flyer Distribution', 'Door-to-door marketing campaign', 'AD-003', 'cash', 'paid', '2024-01-20', NULL, NULL, 0, NULL, 0.00, '2024-01-20 03:45:00', '2025-10-01 01:09:29'),
(21, 3, 17, 8500.00, 'BIR/SSS/PhilHealth', 'Business taxes and employee benefits', 'TAX-001', 'bank_transfer', 'paid', '2024-01-25', NULL, NULL, 0, NULL, 0.00, '2024-01-25 05:15:00', '2025-10-01 01:09:29'),
(22, 3, 11, 2200.00, 'Various Suppliers', 'Delivery and transportation costs', 'TRANS-001', 'cash', 'paid', '2024-01-12', NULL, NULL, 0, NULL, 0.00, '2024-01-12 07:30:00', '2025-10-01 01:09:29'),
(23, 3, 11, 1800.00, 'Customer Deliveries', 'Gas and taxi for customer deliveries', 'TRANS-002', 'cash', 'paid', '2024-01-18', NULL, NULL, 0, NULL, 0.00, '2024-01-18 09:20:00', '2025-10-01 01:09:29'),
(24, 3, 23, 1500.00, 'Office supplies', 'Receipt books, pens, measuring tools', 'OFF-001', 'cash', 'paid', '2024-01-08', NULL, NULL, 0, NULL, 0.00, '2024-01-08 02:15:00', '2025-10-01 01:09:29'),
(25, 3, 30, 32000.00, 'Paper Plus Trading', 'Valentine themed papers, premium cardstock', 'REC-201', 'bank_transfer', 'paid', '2024-02-01', NULL, NULL, 0, NULL, 0.00, '2024-02-01 01:15:00', '2025-10-01 01:09:29'),
(26, 3, 30, 22000.00, 'Fabric World Manila', 'Formal fabrics for Valentine alterations', 'REC-202', 'bank_transfer', 'paid', '2024-02-02', NULL, NULL, 0, NULL, 0.00, '2024-02-02 02:30:00', '2025-10-01 01:09:29'),
(27, 3, 30, 12000.00, 'Ink Supply Co.', 'Extra ink cartridges for high volume printing', 'REC-203', 'bank_transfer', 'paid', '2024-02-03', NULL, NULL, 0, NULL, 0.00, '2024-02-03 06:45:00', '2025-10-01 01:09:29'),
(28, 3, 30, 8500.00, 'Specialty Papers Inc.', 'Metallic and textured papers for Valentine cards', 'REC-204', 'credit_card', 'paid', '2024-02-05', NULL, NULL, 0, NULL, 0.00, '2024-02-05 03:20:00', '2025-10-01 01:09:29'),
(29, 3, 33, 25000.00, 'SM Mall Property Management', 'Monthly rent', 'RENT-002', 'bank_transfer', 'paid', '2024-02-01', NULL, NULL, 0, NULL, 0.00, '2024-02-01 01:00:00', '2025-10-01 01:09:29'),
(30, 3, 32, 20000.00, 'Maria Santos - Tailor', 'Monthly salary + Valentine overtime', 'PAY-004', 'bank_transfer', 'paid', '2024-02-29', NULL, NULL, 0, NULL, 0.00, '2024-02-29 08:00:00', '2025-10-01 01:09:29'),
(31, 3, 32, 17000.00, 'Juan Dela Cruz - Printer Operator', 'Monthly salary + Valentine overtime', 'PAY-005', 'bank_transfer', 'paid', '2024-02-29', NULL, NULL, 0, NULL, 0.00, '2024-02-29 08:00:00', '2025-10-01 01:09:29'),
(32, 3, 32, 12000.00, 'Ana Garcia - Assistant', 'Monthly salary', 'PAY-006', 'bank_transfer', 'paid', '2024-02-29', NULL, NULL, 0, NULL, 0.00, '2024-02-29 08:00:00', '2025-10-01 01:09:29'),
(33, 3, 12, 12000.00, 'Meralco/Maynilad/PLDT', 'Higher electricity due to overtime work', 'UTIL-002', 'bank_transfer', 'paid', '2024-02-05', NULL, NULL, 0, NULL, 0.00, '2024-02-05 07:30:00', '2025-10-01 01:09:29'),
(34, 3, 34, 8000.00, 'Facebook/Instagram Ads', 'Valentine marketing campaign', 'AD-004', 'credit_card', 'paid', '2024-02-01', NULL, NULL, 0, NULL, 0.00, '2024-02-01 04:00:00', '2025-10-01 01:09:29'),
(35, 3, 31, 5500.00, 'Emergency Equipment Repair', 'Printer breakdown during Valentine rush', 'REC-205', 'cash', 'paid', '2024-02-11', NULL, NULL, 0, NULL, 0.00, '2024-02-11 12:30:00', '2025-10-01 01:09:29'),
(36, 3, 30, 35000.00, 'Paper Plus Trading', 'Premium papers for graduation materials', 'REC-301', 'bank_transfer', 'paid', '2024-03-01', NULL, NULL, 0, NULL, 0.00, '2024-03-01 01:00:00', '2025-10-01 01:09:30'),
(37, 3, 30, 28000.00, 'Fabric World Manila', 'Formal fabrics for graduation alterations', 'REC-302', 'bank_transfer', 'paid', '2024-03-02', NULL, NULL, 0, NULL, 0.00, '2024-03-02 02:15:00', '2025-10-01 01:09:30'),
(38, 3, 30, 15000.00, 'Gold Foil Supplies', 'Specialty foiling for graduation certificates', 'REC-303', 'credit_card', 'paid', '2024-03-05', NULL, NULL, 0, NULL, 0.00, '2024-03-05 06:30:00', '2025-10-01 01:09:30'),
(39, 3, 30, 12000.00, 'Embroidery Supplies Inc.', 'Gold and school color threads', 'REC-304', 'bank_transfer', 'paid', '2024-03-08', NULL, NULL, 0, NULL, 0.00, '2024-03-08 03:45:00', '2025-10-01 01:09:30'),
(40, 3, 33, 25000.00, 'SM Mall Property Management', 'Monthly rent', 'RENT-003', 'bank_transfer', 'paid', '2024-03-01', NULL, NULL, 0, NULL, 0.00, '2024-03-01 01:00:00', '2025-10-01 01:09:30'),
(41, 3, 32, 21000.00, 'Maria Santos - Tailor', 'Salary + graduation season peepee', 'PAY-007', 'bank_transfer', 'paid', '2024-03-31', NULL, NULL, 0, NULL, 0.00, '2024-03-31 08:00:00', '2025-10-01 01:19:27'),
(42, 3, 32, 19000.00, 'Juan Dela Cruz - Printer Operator', 'Salary + graduation season bonus', 'PAY-008', 'bank_transfer', 'paid', '2024-03-31', NULL, NULL, 0, NULL, 0.00, '2024-03-31 08:00:00', '2025-10-01 01:09:30'),
(43, 3, 32, 14000.00, 'Ana Garcia - Assistant', 'Salary + graduation season bonus', 'PAY-009', 'bank_transfer', 'paid', '2024-03-31', NULL, NULL, 0, NULL, 0.00, '2024-03-31 08:00:00', '2025-10-01 01:09:30'),
(44, 3, 12, 10500.00, 'Meralco/Maynilad/PLDT', 'Utilities', 'UTIL-003', 'bank_transfer', 'paid', '2024-03-05', NULL, NULL, 0, NULL, 0.00, '2024-03-05 07:30:00', '2025-10-01 01:09:30'),
(45, 3, 34, 6000.00, 'Social Media Marketing', 'Graduation season advertising', 'AD-005', 'credit_card', 'paid', '2024-03-01', NULL, NULL, 0, NULL, 0.00, '2024-03-01 04:00:00', '2025-10-01 01:09:30'),
(46, 3, 31, 8500.00, 'Equipment Upgrades', 'New cutting tools for increased volume', 'REC-305', 'bank_transfer', 'paid', '2024-03-15', NULL, NULL, 0, NULL, 0.00, '2024-03-15 05:20:00', '2025-10-01 01:09:30');

-- --------------------------------------------------------

--
-- Stand-in structure for view `monthly_summary`
-- (See below for the actual view)
--
CREATE TABLE `monthly_summary` (
`user_id` int(11)
,`year` int(4)
,`month` int(2)
,`type` varchar(7)
,`total_amount` decimal(37,2)
,`record_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error','budget_alert','system') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `action_url`, `metadata`, `created_at`, `read_at`) VALUES
(1, 3, 'Welcome to BENTA!', 'Your PrintCraft & Stitch Co. account has been set up successfully.', 'success', 0, '/dashboard', NULL, '2025-10-01 01:09:30', NULL),
(2, 3, 'High Income Month', 'March 2024 was your best performing month with ₱215,000 in revenue!', 'success', 0, '/reports', NULL, '2024-04-01 01:00:00', NULL),
(3, 3, 'Material Cost Alert', 'Raw material expenses increased by 45% this month. Consider reviewing suppliers.', 'warning', 0, '/expenses', NULL, '2024-03-31 09:30:00', NULL),
(4, 3, 'Seasonal Trend Detected', 'Graduation season showing strong performance. Plan inventory accordingly.', 'info', 0, '/analytics', NULL, '2024-03-15 06:20:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_method` enum('cash','bank_transfer','credit_card','debit_card','check','digital_wallet','other') DEFAULT 'cash',
  `status` enum('pending','completed','cancelled','failed') DEFAULT 'completed',
  `trx_date` date NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `category_id`, `amount`, `description`, `reference_number`, `payment_method`, `status`, `trx_date`, `attachment`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 25, 15500.00, 'Wedding invitation printing - 500pcs premium cards', 'INV-2024-001', 'bank_transfer', 'completed', '2024-01-03', NULL, NULL, '2024-01-03 02:30:00', '2025-10-01 01:09:29'),
(2, 3, 26, 8500.00, 'Custom suit tailoring - Mr. Santos', 'INV-2024-002', 'cash', 'completed', '2024-01-03', NULL, NULL, '2024-01-03 06:20:00', '2025-10-01 01:09:29'),
(3, 3, 25, 3200.00, 'Business card printing - Law firm (1000pcs)', 'INV-2024-003', 'bank_transfer', 'completed', '2024-01-04', NULL, NULL, '2024-01-04 01:15:00', '2025-10-01 01:09:29'),
(4, 3, 27, 2500.00, 'Logo design for local restaurant', 'INV-2024-004', 'digital_wallet', 'completed', '2024-01-05', NULL, NULL, '2024-01-05 08:45:00', '2025-10-01 01:09:29'),
(5, 3, 26, 12000.00, 'Barong Tagalog alterations - 3 pieces', 'INV-2024-005', 'cash', 'completed', '2024-01-08', NULL, NULL, '2024-01-08 03:30:00', '2025-10-01 01:09:29'),
(6, 3, 25, 6800.00, 'Company flyers - 2000pcs full color', 'INV-2024-006', 'bank_transfer', 'completed', '2024-01-09', NULL, NULL, '2024-01-09 05:20:00', '2025-10-01 01:09:29'),
(7, 3, 28, 4500.00, 'School uniform embroidery - 50 pieces', 'INV-2024-007', 'cash', 'completed', '2024-01-10', NULL, NULL, '2024-01-10 07:30:00', '2025-10-01 01:09:29'),
(8, 3, 25, 2800.00, 'Graduation certificates printing - 200pcs', 'INV-2024-008', 'bank_transfer', 'completed', '2024-01-11', NULL, NULL, '2024-01-11 01:45:00', '2025-10-01 01:09:29'),
(9, 3, 29, 25000.00, 'Corporate uniform order - 100 polo shirts', 'INV-2024-009', 'bank_transfer', 'completed', '2024-01-15', NULL, NULL, '2024-01-15 06:00:00', '2025-10-01 01:09:29'),
(10, 3, 25, 8900.00, 'Event banners - 10 pieces large format', 'INV-2024-010', 'cash', 'completed', '2024-01-16', NULL, NULL, '2024-01-16 02:20:00', '2025-10-01 01:09:29'),
(11, 3, 26, 6500.00, 'Dress alterations - 4 formal dresses', 'INV-2024-011', 'cash', 'completed', '2024-01-17', NULL, NULL, '2024-01-17 08:15:00', '2025-10-01 01:09:29'),
(12, 3, 27, 3500.00, 'Brochure design and printing - Medical clinic', 'INV-2024-012', 'digital_wallet', 'completed', '2024-01-18', NULL, NULL, '2024-01-18 03:45:00', '2025-10-01 01:09:29'),
(13, 3, 25, 12500.00, 'Birthday party materials - banners, invites, stickers', 'INV-2024-013', 'bank_transfer', 'completed', '2024-01-22', NULL, NULL, '2024-01-22 05:30:00', '2025-10-01 01:09:29'),
(14, 3, 26, 9500.00, 'Pants hemming and suit adjustments - 6 customers', 'INV-2024-014', 'cash', 'completed', '2024-01-23', NULL, NULL, '2024-01-23 07:45:00', '2025-10-01 01:09:29'),
(15, 3, 28, 7200.00, 'Company logo embroidery - 80 items', 'INV-2024-015', 'bank_transfer', 'completed', '2024-01-24', NULL, NULL, '2024-01-24 04:20:00', '2025-10-01 01:09:29'),
(16, 3, 25, 4200.00, 'Menu printing - Restaurant (laminated, 100pcs)', 'INV-2024-016', 'cash', 'completed', '2024-01-25', NULL, NULL, '2024-01-25 06:50:00', '2025-10-01 01:09:29'),
(17, 3, 29, 18000.00, 'Church choir robes - 15 pieces', 'INV-2024-017', 'bank_transfer', 'completed', '2024-01-29', NULL, NULL, '2024-01-29 08:30:00', '2025-10-01 01:09:29'),
(18, 3, 25, 5500.00, 'Real estate signage - 8 pieces outdoor vinyl', 'INV-2024-018', 'cash', 'completed', '2024-01-30', NULL, NULL, '2024-01-30 02:15:00', '2025-10-01 01:09:29'),
(19, 3, 25, 28000.00, 'Valentine\'s Day printing rush - cards, banners, photobooks', 'INV-2024-019', 'bank_transfer', 'completed', '2024-02-12', NULL, NULL, '2024-02-12 10:30:00', '2025-10-01 01:09:29'),
(20, 3, 26, 15500.00, 'Formal dress alterations for Valentine dates - 8 customers', 'INV-2024-020', 'cash', 'completed', '2024-02-13', NULL, NULL, '2024-02-13 11:45:00', '2025-10-01 01:09:29'),
(21, 3, 27, 8500.00, 'Valentine themed design packages - 5 clients', 'INV-2024-021', 'digital_wallet', 'completed', '2024-02-10', NULL, NULL, '2024-02-10 06:20:00', '2025-10-01 01:09:29'),
(22, 3, 29, 32000.00, 'School uniform orders - new academic year prep', 'INV-2024-022', 'bank_transfer', 'completed', '2024-02-05', NULL, NULL, '2024-02-05 03:30:00', '2025-10-01 01:09:29'),
(23, 3, 25, 18500.00, 'Corporate ID printing and lanyards - 300 pieces', 'INV-2024-023', 'bank_transfer', 'completed', '2024-02-08', NULL, NULL, '2024-02-08 05:45:00', '2025-10-01 01:09:29'),
(24, 3, 26, 22000.00, 'Wedding entourage outfit alterations - complete wedding party', 'INV-2024-024', 'bank_transfer', 'completed', '2024-02-15', NULL, NULL, '2024-02-15 08:20:00', '2025-10-01 01:09:29'),
(25, 3, 28, 12000.00, 'Company anniversary embroidery - 120 shirts', 'INV-2024-025', 'cash', 'completed', '2024-02-20', NULL, NULL, '2024-02-20 06:15:00', '2025-10-01 01:09:29'),
(26, 3, 25, 14500.00, 'Birthday party package - banners, invites, giveaways', 'INV-2024-026', 'bank_transfer', 'completed', '2024-02-22', NULL, NULL, '2024-02-22 07:30:00', '2025-10-01 01:09:29'),
(27, 3, 26, 9500.00, 'Pants and shirt alterations - regular customers', 'INV-2024-027', 'cash', 'completed', '2024-02-25', NULL, NULL, '2024-02-25 09:45:00', '2025-10-01 01:09:29'),
(28, 3, 25, 11200.00, 'Restaurant new menu printing - premium finish', 'INV-2024-028', 'bank_transfer', 'completed', '2024-02-28', NULL, NULL, '2024-02-28 04:20:00', '2025-10-01 01:09:29'),
(29, 3, 25, 45000.00, 'Graduation invitations - 5 schools, 2000+ pieces', 'INV-2024-029', 'bank_transfer', 'completed', '2024-03-15', NULL, NULL, '2024-03-15 06:30:00', '2025-10-01 01:09:30'),
(30, 3, 26, 35000.00, 'Toga and graduation dress alterations - 25 graduates', 'INV-2024-030', 'bank_transfer', 'completed', '2024-03-18', NULL, NULL, '2024-03-18 09:20:00', '2025-10-01 01:09:30'),
(31, 3, 25, 28000.00, 'Graduation programs and certificates - premium printing', 'INV-2024-031', 'bank_transfer', 'completed', '2024-03-20', NULL, NULL, '2024-03-20 03:45:00', '2025-10-01 01:09:30'),
(32, 3, 28, 18000.00, 'School logo embroidery on graduation sashes', 'INV-2024-032', 'cash', 'completed', '2024-03-22', NULL, NULL, '2024-03-22 07:30:00', '2025-10-01 01:09:30'),
(33, 3, 27, 12000.00, 'Graduation backdrop design - 3 schools', 'INV-2024-033', 'digital_wallet', 'completed', '2024-03-10', NULL, NULL, '2024-03-10 05:15:00', '2025-10-01 01:09:30'),
(34, 3, 29, 25000.00, 'Summer corporate uniforms - light fabric orders', 'INV-2024-034', 'bank_transfer', 'completed', '2024-03-05', NULL, NULL, '2024-03-05 02:20:00', '2025-10-01 01:09:30'),
(35, 3, 25, 15500.00, 'Real estate marketing materials - summer promo', 'INV-2024-035', 'bank_transfer', 'completed', '2024-03-08', NULL, NULL, '2024-03-08 08:45:00', '2025-10-01 01:09:30'),
(36, 3, 26, 18500.00, 'Summer dress alterations and repairs', 'INV-2024-036', 'cash', 'completed', '2024-03-12', NULL, NULL, '2024-03-12 06:30:00', '2025-10-01 01:09:30'),
(37, 3, 25, 9800.00, 'Birthday party printing - March celebrations', 'INV-2024-037', 'cash', 'completed', '2024-03-25', NULL, NULL, '2024-03-25 04:20:00', '2025-10-01 01:09:30'),
(38, 3, 26, 8200.00, 'Regular alterations and repairs', 'INV-2024-038', 'cash', 'completed', '2024-03-28', NULL, NULL, '2024-03-28 08:15:00', '2025-10-01 01:09:30'),
(39, 3, 25, 22000.00, 'Summer event printing - beach resort materials', 'INV-2024-039', 'bank_transfer', 'completed', '2024-04-10', NULL, NULL, '2024-04-10 03:30:00', '2025-10-01 01:09:30'),
(40, 3, 26, 28000.00, 'Summer clothing alterations - lighter fabrics', 'INV-2024-040', 'cash', 'completed', '2024-04-15', NULL, NULL, '2024-04-15 08:20:00', '2025-10-01 01:09:30'),
(41, 3, 29, 18000.00, 'Beach resort staff uniforms - 60 pieces', 'INV-2024-041', 'bank_transfer', 'completed', '2024-04-05', NULL, NULL, '2024-04-05 06:45:00', '2025-10-01 01:09:30'),
(42, 3, 25, 16500.00, 'Summer camp materials - t-shirts, banners, IDs', 'INV-2024-042', 'bank_transfer', 'completed', '2024-04-18', NULL, NULL, '2024-04-18 05:15:00', '2025-10-01 01:09:30'),
(43, 3, 27, 8500.00, 'Summer vacation packages design', 'INV-2024-043', 'digital_wallet', 'completed', '2024-04-08', NULL, NULL, '2024-04-08 07:30:00', '2025-10-01 01:09:30'),
(44, 3, 25, 35000.00, 'Election campaign materials - multiple candidates', 'INV-2024-044', 'bank_transfer', 'completed', '2024-05-08', NULL, NULL, '2024-05-08 08:30:00', '2025-10-01 01:09:30'),
(45, 3, 25, 18000.00, 'Mother\'s Day cards and gift wrapping', 'INV-2024-045', 'cash', 'completed', '2024-05-10', NULL, NULL, '2024-05-10 06:20:00', '2025-10-01 01:09:30'),
(46, 3, 26, 12000.00, 'Mother\'s Day dress alterations', 'INV-2024-046', 'cash', 'completed', '2024-05-11', NULL, NULL, '2024-05-11 09:45:00', '2025-10-01 01:09:30'),
(47, 3, 29, 28000.00, 'Election staff uniforms and accessories', 'INV-2024-047', 'bank_transfer', 'completed', '2024-05-05', NULL, NULL, '2024-05-05 03:30:00', '2025-10-01 01:09:30'),
(48, 3, 27, 9500.00, 'Election campaign design services', 'INV-2024-048', 'digital_wallet', 'completed', '2024-05-02', NULL, NULL, '2024-05-02 02:45:00', '2025-10-01 01:09:30'),
(49, 3, 25, 18000.00, 'Halloween party materials - spooky designs', 'INV-2024-049', 'bank_transfer', 'completed', '2024-10-01', NULL, NULL, '2024-10-01 06:30:00', '2025-10-01 01:09:30'),
(50, 3, 26, 8500.00, 'Costume alterations for Halloween', 'INV-2024-050', 'cash', 'completed', '2024-10-01', NULL, NULL, '2024-10-01 08:45:00', '2025-10-01 01:09:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_admin` tinyint(1) DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `is_active`, `is_admin`, `profile_image`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'john_doe', 'john.doe@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', '+63-917-123-4567', 1, 0, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28', NULL),
(2, 'jane_smith', 'jane.smith@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane Smith', '+63-918-234-5678', 1, 0, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28', NULL),
(3, 'printcraft_owner', 'owner@printcraft.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PrintCraft & Stitch Co.', '+63-919-345-6789', 1, 0, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `user_financial_summary`
-- (See below for the actual view)
--
CREATE TABLE `user_financial_summary` (
`user_id` int(11)
,`username` varchar(50)
,`full_name` varchar(100)
,`total_income` decimal(37,2)
,`total_expenses` decimal(37,2)
,`net_balance` decimal(38,2)
,`transaction_count` bigint(21)
,`expense_count` bigint(21)
,`user_since` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `currency` varchar(3) DEFAULT 'PHP',
  `date_format` varchar(20) DEFAULT 'Y-m-d',
  `timezone` varchar(50) DEFAULT 'Asia/Manila',
  `language` varchar(5) DEFAULT 'en',
  `theme` enum('light','dark','auto') DEFAULT 'light',
  `notifications_enabled` tinyint(1) DEFAULT 1,
  `email_notifications` tinyint(1) DEFAULT 1,
  `budget_alerts` tinyint(1) DEFAULT 1,
  `dashboard_layout` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dashboard_layout`)),
  `export_preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`export_preferences`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_settings`
--

INSERT INTO `user_settings` (`id`, `user_id`, `currency`, `date_format`, `timezone`, `language`, `theme`, `notifications_enabled`, `email_notifications`, `budget_alerts`, `dashboard_layout`, `export_preferences`, `created_at`, `updated_at`) VALUES
(1, 1, 'PHP', 'Y-m-d', 'Asia/Manila', 'en', 'light', 1, 1, 1, NULL, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(2, 2, 'PHP', 'Y-m-d', 'Asia/Manila', 'en', 'light', 1, 1, 1, NULL, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28'),
(3, 3, 'PHP', 'Y-m-d', 'Asia/Manila', 'en', 'light', 1, 1, 1, NULL, NULL, '2025-10-01 00:59:28', '2025-10-01 00:59:28');

-- --------------------------------------------------------

--
-- Structure for view `monthly_summary`
--
DROP TABLE IF EXISTS `monthly_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `monthly_summary`  AS SELECT `transactions`.`user_id` AS `user_id`, year(`transactions`.`trx_date`) AS `year`, month(`transactions`.`trx_date`) AS `month`, 'income' AS `type`, sum(`transactions`.`amount`) AS `total_amount`, count(0) AS `record_count` FROM `transactions` WHERE `transactions`.`status` = 'completed' GROUP BY `transactions`.`user_id`, year(`transactions`.`trx_date`), month(`transactions`.`trx_date`)union all select `expenses`.`user_id` AS `user_id`,year(`expenses`.`expense_date`) AS `year`,month(`expenses`.`expense_date`) AS `month`,'expense' AS `type`,sum(`expenses`.`amount`) AS `total_amount`,count(0) AS `record_count` from `expenses` where `expenses`.`status` = 'paid' group by `expenses`.`user_id`,year(`expenses`.`expense_date`),month(`expenses`.`expense_date`)  ;

-- --------------------------------------------------------

--
-- Structure for view `user_financial_summary`
--
DROP TABLE IF EXISTS `user_financial_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `user_financial_summary`  AS SELECT `u`.`id` AS `user_id`, `u`.`username` AS `username`, `u`.`full_name` AS `full_name`, coalesce(`income`.`total_income`,0) AS `total_income`, coalesce(`expenses`.`total_expenses`,0) AS `total_expenses`, coalesce(`income`.`total_income`,0) - coalesce(`expenses`.`total_expenses`,0) AS `net_balance`, `income`.`transaction_count` AS `transaction_count`, `expenses`.`expense_count` AS `expense_count`, `u`.`created_at` AS `user_since` FROM ((`users` `u` left join (select `transactions`.`user_id` AS `user_id`,sum(`transactions`.`amount`) AS `total_income`,count(0) AS `transaction_count` from `transactions` where `transactions`.`status` = 'completed' group by `transactions`.`user_id`) `income` on(`u`.`id` = `income`.`user_id`)) left join (select `expenses`.`user_id` AS `user_id`,sum(`expenses`.`amount`) AS `total_expenses`,count(0) AS `expense_count` from `expenses` where `expenses`.`status` = 'paid' group by `expenses`.`user_id`) `expenses` on(`u`.`id` = `expenses`.`user_id`)) WHERE `u`.`is_active` = 1 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table` (`table_name`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_period` (`start_date`,`end_date`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_name` (`name`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_expense_date` (`expense_date`),
  ADD KEY `idx_amount` (`amount`),
  ADD KEY `idx_vendor` (`vendor`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_recurring` (`is_recurring`),
  ADD KEY `idx_payment_method` (`payment_method`),
  ADD KEY `idx_user_date_expenses` (`user_id`,`expense_date`),
  ADD KEY `idx_user_category_expenses` (`user_id`,`category_id`),
  ADD KEY `idx_amount_date_expenses` (`amount`,`expense_date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_category_id` (`category_id`),
  ADD KEY `idx_trx_date` (`trx_date`),
  ADD KEY `idx_amount` (`amount`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_payment_method` (`payment_method`),
  ADD KEY `idx_user_date_transactions` (`user_id`,`trx_date`),
  ADD KEY `idx_user_category_transactions` (`user_id`,`category_id`),
  ADD KEY `idx_amount_date_transactions` (`amount`,`trx_date`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
