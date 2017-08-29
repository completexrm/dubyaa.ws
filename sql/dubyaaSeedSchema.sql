-- phpMyAdmin SQL Dump
-- version 4.2.10.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: May 12, 2015 at 04:04 PM
-- Server version: 5.6.21
-- PHP Version: 5.5.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `dubaaSeed`
--

-- --------------------------------------------------------

--
-- Table structure for table `account`
--

CREATE TABLE IF NOT EXISTS `account` (
`id` int(11) NOT NULL,
  `displayName` varchar(255) NOT NULL,
  `subscriptionId` int(11) NOT NULL DEFAULT '1',
  `registrationKey` char(16) NOT NULL,
  `lastBillDate` datetime DEFAULT NULL,
  `primaryEmail` varchar(255) DEFAULT NULL,
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdBy` int(11) NOT NULL DEFAULT '0',
  `updatedOn` datetime DEFAULT NULL,
  `updatedBy` int(11) NOT NULL DEFAULT '0',
  `isTrial` tinyint(1) NOT NULL DEFAULT '1',
  `trialDuration` tinyint(4) NOT NULL,
  `isActive` tinyint(1) NOT NULL DEFAULT '0',
  `isDeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `account`
--

INSERT INTO `account` (`id`, `displayName`, `subscriptionId`, `registrationKey`, `lastBillDate`, `primaryEmail`, `createdOn`, `createdBy`, `updatedOn`, `updatedBy`, `isTrial`, `trialDuration`, `isActive`, `isDeleted`) VALUES
(1, 'Seed Account', 1, 'P0mj856GTvd421lM', NULL, 'support@dubyaa.com', '2015-05-12 15:59:37', 0, NULL, 0, 0, 7, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `account_billing_history`
--

CREATE TABLE IF NOT EXISTS `account_billing_history` (
`id` int(11) NOT NULL,
  `accountId` int(11) NOT NULL,
  `referenceNumber` varchar(255) NOT NULL,
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `account_payment_method`
--

CREATE TABLE IF NOT EXISTS `account_payment_method` (
`id` int(11) NOT NULL,
  `accountId` int(11) NOT NULL,
  `cFirstName` varchar(128) NOT NULL,
  `cLastName` varchar(128) NOT NULL,
  `cType` varchar(24) NOT NULL,
  `cLastFour` int(11) NOT NULL,
  `cExpMo` int(11) NOT NULL,
  `cExpYr` int(11) NOT NULL,
  `cPostal` varchar(24) NOT NULL,
  `cCode` int(11) NOT NULL,
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdBy` int(11) NOT NULL,
  `updatedOn` datetime NOT NULL,
  `updatedBy` int(11) NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `account_pref`
--

CREATE TABLE IF NOT EXISTS `account_pref` (
`id` int(11) NOT NULL,
  `accountId` int(11) NOT NULL,
  `dubyaaMultiplier` decimal(5,2) DEFAULT NULL,
  `maxOpen` tinyint(4) DEFAULT NULL,
  `maxOpenDay` tinyint(4) DEFAULT NULL,
  `billingEmails` tinyint(1) NOT NULL DEFAULT '1',
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedOn` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `account_pref`
--

INSERT INTO `account_pref` (`id`, `accountId`, `dubyaaMultiplier`, `maxOpen`, `maxOpenDay`, `billingEmails`, `createdOn`, `updatedOn`) VALUES
(1, 1, '1.50', 3, 10, 1, '2015-05-12 16:03:20', '2015-05-12 10:03:20');

-- --------------------------------------------------------

--
-- Table structure for table `team`
--

CREATE TABLE IF NOT EXISTS `team` (
`id` int(11) NOT NULL,
  `accountId` int(11) NOT NULL,
  `teamLeadId` int(11) NOT NULL,
  `displayName` varchar(128) NOT NULL,
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdBy` int(11) NOT NULL DEFAULT '0',
  `updatedOn` datetime DEFAULT NULL,
  `updatedBy` int(11) NOT NULL DEFAULT '0',
  `isDeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `team`
--

INSERT INTO `team` (`id`, `accountId`, `teamLeadId`, `displayName`, `createdOn`, `createdBy`, `updatedOn`, `updatedBy`, `isDeleted`) VALUES
(1, 1, 1, 'Seed Team', '2015-05-12 16:00:00', 1, '2015-05-12 10:00:00', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `todo`
--

CREATE TABLE IF NOT EXISTS `todo` (
`id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `label` varchar(128) NOT NULL,
  `dateDue` datetime DEFAULT NULL,
  `points` decimal(4,2) NOT NULL DEFAULT '0.00',
  `pointsMultiple` decimal(4,2) NOT NULL DEFAULT '1.00',
  `pointsAwarded` decimal(4,2) NOT NULL DEFAULT '0.00',
  `isDubyaa` tinyint(1) NOT NULL DEFAULT '0',
  `dubyaaDate` datetime DEFAULT NULL,
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdBy` int(11) NOT NULL DEFAULT '0',
  `updatedOn` datetime DEFAULT NULL,
  `updatedBy` int(11) NOT NULL DEFAULT '0',
  `isDeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `todo_tag`
--

CREATE TABLE IF NOT EXISTS `todo_tag` (
`id` int(11) NOT NULL,
  `todoId` int(11) NOT NULL,
  `tagId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
`id` int(11) NOT NULL,
  `accountId` int(11) NOT NULL,
  `isAccountAdmin` tinyint(1) NOT NULL DEFAULT '0',
  `displayName` varchar(128) NOT NULL,
  `firstName` varchar(128) NOT NULL,
  `lastName` varchar(128) NOT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `userHash` varchar(128) NOT NULL,
  `isFirstLogin` tinyint(1) NOT NULL DEFAULT '1',
  `isPWReset` tinyint(1) NOT NULL DEFAULT '0',
  `autoLoginKey` char(24) DEFAULT NULL,
  `autoLoginExpires` datetime DEFAULT NULL,
  `lastLogin` datetime DEFAULT NULL,
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdBy` int(11) NOT NULL DEFAULT '0',
  `updatedOn` datetime DEFAULT NULL,
  `updatedBy` int(11) NOT NULL DEFAULT '0',
  `isDeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `accountId`, `isAccountAdmin`, `displayName`, `firstName`, `lastName`, `emailAddress`, `userHash`, `isFirstLogin`, `isPWReset`, `autoLoginKey`, `autoLoginExpires`, `lastLogin`, `createdOn`, `createdBy`, `updatedOn`, `updatedBy`, `isDeleted`) VALUES
(1, 1, 1, 'Seed Admin', 'Seed', 'Admin', 'support@dubyaa.com', '$2y$10$bfgmMuVMDxVEfNk5kyiotOaM4ZzW5oUntWAcQ3.Dd/pB9Sqwwyfzi', 0, 0, NULL, NULL, NULL, '2015-05-12 16:01:55', 0, '2015-05-12 10:01:55', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_goal`
--

CREATE TABLE IF NOT EXISTS `user_goal` (
`id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `goalType` char(1) NOT NULL,
  `goalMonth` tinyint(4) DEFAULT NULL,
  `goalQuarter` tinyint(4) DEFAULT NULL,
  `goalYear` smallint(6) DEFAULT NULL,
  `goalPts` int(11) NOT NULL DEFAULT '0',
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedOn` datetime NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_pref`
--

CREATE TABLE IF NOT EXISTS `user_pref` (
`id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `tzOffset` smallint(6) NOT NULL DEFAULT '0',
  `hasReminders` tinyint(1) NOT NULL DEFAULT '1',
  `amRemindTime` time DEFAULT NULL,
  `pmRemindTime` time DEFAULT NULL,
  `appHints` tinyint(1) NOT NULL DEFAULT '1',
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedOn` datetime DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user_pref`
--

INSERT INTO `user_pref` (`id`, `userId`, `tzOffset`, `hasReminders`, `amRemindTime`, `pmRemindTime`, `appHints`, `createdOn`, `updatedOn`) VALUES
(1, 1, 0, 1, '06:00:00', '16:00:00', 1, '2015-05-12 16:02:21', '2015-05-12 10:02:21');

-- --------------------------------------------------------

--
-- Table structure for table `user_session`
--

CREATE TABLE IF NOT EXISTS `user_session` (
`id` int(11) NOT NULL,
  `userId` int(11) NOT NULL,
  `accountId` int(11) NOT NULL,
  `sessionId` varchar(48) DEFAULT NULL,
  `ipAddress` varchar(16) DEFAULT NULL,
  `uaType` varchar(48) DEFAULT NULL,
  `uaMobile` tinyint(1) NOT NULL DEFAULT '0',
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `isActive` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_team`
--

CREATE TABLE IF NOT EXISTS `user_team` (
  `userId` int(11) NOT NULL,
  `teamId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `user_team`
--

INSERT INTO `user_team` (`userId`, `teamId`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `_registration`
--

CREATE TABLE IF NOT EXISTS `_registration` (
`id` int(11) NOT NULL,
  `emailAddress` varchar(255) NOT NULL,
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `_subscription`
--

CREATE TABLE IF NOT EXISTS `_subscription` (
`id` int(11) NOT NULL,
  `label` varchar(255) NOT NULL,
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdBy` int(11) NOT NULL,
  `updatedOn` datetime NOT NULL,
  `updatedBy` int(11) NOT NULL,
  `isDeleted` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `_subscription`
--

INSERT INTO `_subscription` (`id`, `label`, `createdOn`, `createdBy`, `updatedOn`, `updatedBy`, `isDeleted`) VALUES
(1, 'Base Trial', '2015-05-12 16:02:50', 1, '2015-05-12 10:02:50', 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `_tag`
--

CREATE TABLE IF NOT EXISTS `_tag` (
`id` int(11) NOT NULL,
  `tag` varchar(48) NOT NULL,
  `createdOn` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdBy` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `_tz`
--

CREATE TABLE IF NOT EXISTS `_tz` (
`id` int(11) NOT NULL,
  `label` varchar(64) NOT NULL,
  `offset` smallint(6) NOT NULL,
  `dst` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

--
-- Dumping data for table `_tz`
--

INSERT INTO `_tz` (`id`, `label`, `offset`, `dst`) VALUES
(1, 'America/Pacific', -180, 1),
(2, 'America/Mountain', -120, 1),
(3, 'America/Central', -60, 1),
(4, 'America/Eastern', 0, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account`
--
ALTER TABLE `account`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `account_billing_history`
--
ALTER TABLE `account_billing_history`
 ADD PRIMARY KEY (`id`), ADD KEY `accountId` (`accountId`), ADD KEY `reference_number` (`referenceNumber`);

--
-- Indexes for table `account_payment_method`
--
ALTER TABLE `account_payment_method`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `account_pref`
--
ALTER TABLE `account_pref`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `team`
--
ALTER TABLE `team`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `todo`
--
ALTER TABLE `todo`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `todo_tag`
--
ALTER TABLE `todo_tag`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `emailAddressUnique` (`emailAddress`);

--
-- Indexes for table `user_goal`
--
ALTER TABLE `user_goal`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_pref`
--
ALTER TABLE `user_pref`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_session`
--
ALTER TABLE `user_session`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `_registration`
--
ALTER TABLE `_registration`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `_subscription`
--
ALTER TABLE `_subscription`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `_tag`
--
ALTER TABLE `_tag`
 ADD PRIMARY KEY (`id`);

--
-- Indexes for table `_tz`
--
ALTER TABLE `_tz`
 ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account`
--
ALTER TABLE `account`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `account_billing_history`
--
ALTER TABLE `account_billing_history`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `account_payment_method`
--
ALTER TABLE `account_payment_method`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `account_pref`
--
ALTER TABLE `account_pref`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `team`
--
ALTER TABLE `team`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `todo`
--
ALTER TABLE `todo`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `todo_tag`
--
ALTER TABLE `todo_tag`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `user_goal`
--
ALTER TABLE `user_goal`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `user_pref`
--
ALTER TABLE `user_pref`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `user_session`
--
ALTER TABLE `user_session`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `_registration`
--
ALTER TABLE `_registration`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `_subscription`
--
ALTER TABLE `_subscription`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `_tag`
--
ALTER TABLE `_tag`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `_tz`
--
ALTER TABLE `_tz`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
