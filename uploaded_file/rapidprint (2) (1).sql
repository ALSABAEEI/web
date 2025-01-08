-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 07, 2025 at 01:32 AM
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
-- Database: `rapidprint`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `adminID` int(11) NOT NULL,
  `UserName` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`adminID`, `UserName`, `Password`, `PhoneNumber`) VALUES
(1, 'amro', '$2y$10$L4qMKPNI1R21m2E4V9ztIuOwN/kdxJlx5OfQ2abb6RWMy.lVOrAte', '11111'),
(4, 'admin_unique', '$2y$10$UNIQUEPASSWORDHASH1', '0123456789');

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `BranchID` int(11) NOT NULL,
  `adminID` int(11) DEFAULT NULL,
  `BranchName` varchar(50) DEFAULT NULL,
  `Location` varchar(255) DEFAULT NULL,
  `ContactInfo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branch`
--

INSERT INTO `branch` (`BranchID`, `adminID`, `BranchName`, `Location`, `ContactInfo`) VALUES
(4, 4, 'Pekan', 'Lorong tegia', 'uniquebranch@example.com'),
(5, 1, 'Gambang', 'dasdsa', '21321312'),
(6, 4, 'Johor Bahru', '21321dasdas', 'dasd');

-- --------------------------------------------------------

--
-- Table structure for table `membershipcard`
--

CREATE TABLE `membershipcard` (
  `CardID` int(11) NOT NULL,
  `studID` int(11) DEFAULT NULL,
  `QRCode` varchar(255) DEFAULT NULL,
  `Balance` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `membershipcard`
--

INSERT INTO `membershipcard` (`CardID`, `studID`, `QRCode`, `Balance`) VALUES
(4, 6, 'QR456DEF', 188.16),
(12, 34, 'qrcodes/card_34.png', 59.00);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `OrderID` int(11) NOT NULL,
  `studID` int(11) DEFAULT NULL,
  `Date` date DEFAULT NULL,
  `OrderStatus` varchar(50) DEFAULT NULL,
  `OrderTotal` decimal(10,2) DEFAULT NULL,
  `staffID` int(11) DEFAULT NULL,
  `Upload_File` varchar(255) DEFAULT NULL,
  `CardID` int(11) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL,
  `QRCodePath` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`OrderID`, `studID`, `Date`, `OrderStatus`, `OrderTotal`, `staffID`, `Upload_File`, `CardID`, `BranchID`, `QRCodePath`) VALUES
(4, 5, '2024-12-22', 'Completed', 50.00, 4, 'unique_file1.pdf', 4, 4, NULL),
(5, 5, '2024-12-23', 'Pending', 35.00, 4, 'unique_file2.pdf', 4, 5, NULL),
(7, NULL, '2024-12-23', 'Pending', 190.00, NULL, 'uploaded_file/ضريبة التصرفات العقاريه.pdf', NULL, 6, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_line`
--

CREATE TABLE `order_line` (
  `OL_ID` int(11) NOT NULL,
  `Order_ID` int(11) DEFAULT NULL,
  `PackageID` int(11) DEFAULT NULL,
  `SubPrice` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_line`
--

INSERT INTO `order_line` (`OL_ID`, `Order_ID`, `PackageID`, `SubPrice`) VALUES
(4, 4, 4, 20.00),
(5, 4, 5, 30.00),
(6, 7, 4, 40.00),
(7, 7, 5, 150.00);

-- --------------------------------------------------------

--
-- Table structure for table `package`
--

CREATE TABLE `package` (
  `PackageID` int(11) NOT NULL,
  `PackageName` varchar(100) DEFAULT NULL,
  `Description` text DEFAULT NULL,
  `Price` decimal(10,2) DEFAULT NULL,
  `BranchID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package`
--

INSERT INTO `package` (`PackageID`, `PackageName`, `Description`, `Price`, `BranchID`) VALUES
(4, 'Black and white', 'High-Quality Printing Package', 20.00, 4),
(5, 'Coloured', 'Fast Service Printing Package', 30.00, 4),
(6, 'A4', 'aaaaaa', 20.00, 6),
(7, 'A3', 'aaaa', 11.00, 5);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `PaymentID` int(11) NOT NULL,
  `OrderID` int(11) DEFAULT NULL,
  `PaymentStatus` varchar(50) DEFAULT NULL,
  `PaymentMethod` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`PaymentID`, `OrderID`, `PaymentStatus`, `PaymentMethod`) VALUES
(4, 4, 'Completed', 'Credit Card'),
(5, 5, 'Pending', 'Cash');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `staffID` int(11) NOT NULL,
  `UserName` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`staffID`, `UserName`, `Password`, `PhoneNumber`) VALUES
(1, 'boja', '$2y$10$s3KxYU7uAaMTHO9dcU6OGeAv5f14/tu95LurQLL.7pgRSbfjSEaea', '1111'),
(4, 'unique_staff', '$2y$10$UNIQUEPASSWORDHASH3', '0145678901');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `studID` int(11) NOT NULL,
  `UserName` varchar(50) NOT NULL,
  `Password` varchar(255) NOT NULL,
  `PhoneNumber` varchar(20) DEFAULT NULL,
  `StudentCard` varchar(20) DEFAULT NULL,
  `address` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `IsVerified` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`studID`, `UserName`, `Password`, `PhoneNumber`, `StudentCard`, `address`, `email`, `IsVerified`) VALUES
(4, 'sdsa', '$2y$10$wAJEPpPsVVaooGV/UnwfBeG5OyJDMqVlJfkM2b8m3jOdRZogL616K', '321321', '', '', '', 0),
(5, 'unique_student', '$2y$10$UNIQUEPASSWORDHASH2', '0198765432', 'SC54321', '', '', 0),
(6, 'aa', '$2y$10$Ho1Ixmn/zRLP953IsfdwBu5OLHgQD1qmToM/B//OLOgLyl9Ol1edm', '123', NULL, '', '', 0),
(34, 'rah', '$2y$10$GmzQySuJqGTDGELz/X2Xxu5aABWXC2WI04.dS68UyhtXZR85BIM3i', '0116694176', 'Tony_Soprano_2.jpg', 'lorong tegak aman 2', 'savage@gmail.com', 0);

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `TransactionID` int(11) NOT NULL,
  `CardID` int(11) DEFAULT NULL,
  `Type` varchar(50) DEFAULT NULL,
  `Amount` int(11) DEFAULT NULL,
  `Date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`TransactionID`, `CardID`, `Type`, `Amount`, `Date`) VALUES
(5, 4, 'Redeem', NULL, '2024-12-22'),
(6, 4, 'Redeem', NULL, '2025-01-05'),
(7, 4, 'Redeem', NULL, '2025-01-05'),
(8, 4, 'Redeem', NULL, '2025-01-05'),
(9, 4, 'Redeem', 120, '2025-01-06'),
(11, 12, 'Add Funds', 59, '2025-01-06'),
(12, 4, 'Redeem', 300, '2025-01-07'),
(13, 4, 'Redeem', 100, '2025-01-07');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`adminID`);

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`BranchID`),
  ADD KEY `adminID` (`adminID`);

--
-- Indexes for table `membershipcard`
--
ALTER TABLE `membershipcard`
  ADD PRIMARY KEY (`CardID`),
  ADD KEY `studID` (`studID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`OrderID`),
  ADD KEY `studID` (`studID`),
  ADD KEY `staffID` (`staffID`),
  ADD KEY `orders_ibfk_3` (`CardID`);

--
-- Indexes for table `order_line`
--
ALTER TABLE `order_line`
  ADD PRIMARY KEY (`OL_ID`),
  ADD KEY `Order_ID` (`Order_ID`),
  ADD KEY `PackageID` (`PackageID`);

--
-- Indexes for table `package`
--
ALTER TABLE `package`
  ADD PRIMARY KEY (`PackageID`),
  ADD KEY `BranchID` (`BranchID`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`PaymentID`),
  ADD KEY `OrderID` (`OrderID`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`staffID`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`studID`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`TransactionID`),
  ADD KEY `CardID` (`CardID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `adminID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `BranchID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `membershipcard`
--
ALTER TABLE `membershipcard`
  MODIFY `CardID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `OrderID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `order_line`
--
ALTER TABLE `order_line`
  MODIFY `OL_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=211;

--
-- AUTO_INCREMENT for table `package`
--
ALTER TABLE `package`
  MODIFY `PackageID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `PaymentID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `staffID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `studID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `TransactionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `branch`
--
ALTER TABLE `branch`
  ADD CONSTRAINT `branch_ibfk_1` FOREIGN KEY (`adminID`) REFERENCES `admin` (`adminID`);

--
-- Constraints for table `membershipcard`
--
ALTER TABLE `membershipcard`
  ADD CONSTRAINT `membershipcard_ibfk_1` FOREIGN KEY (`studID`) REFERENCES `student` (`studID`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`studID`) REFERENCES `student` (`studID`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`staffID`) REFERENCES `staff` (`staffID`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`CardID`) REFERENCES `membershipcard` (`CardID`);

--
-- Constraints for table `order_line`
--
ALTER TABLE `order_line`
  ADD CONSTRAINT `order_line_ibfk_1` FOREIGN KEY (`Order_ID`) REFERENCES `orders` (`OrderID`),
  ADD CONSTRAINT `order_line_ibfk_2` FOREIGN KEY (`PackageID`) REFERENCES `package` (`PackageID`);

--
-- Constraints for table `package`
--
ALTER TABLE `package`
  ADD CONSTRAINT `package_ibfk_1` FOREIGN KEY (`BranchID`) REFERENCES `branch` (`BranchID`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`OrderID`) REFERENCES `orders` (`OrderID`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`CardID`) REFERENCES `membershipcard` (`CardID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
