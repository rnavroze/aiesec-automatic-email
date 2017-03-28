-- phpMyAdmin SQL Dump
-- version 4.6.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 28, 2017 at 06:11 PM
-- Server version: 5.7.13
-- PHP Version: 5.6.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `aiesec2`
--

-- --------------------------------------------------------

--
-- Table structure for table `email_eplist`
--

CREATE TABLE `email_eplist` (
  `id` int(11) NOT NULL,
  `epid` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `status` varchar(16) NOT NULL,
  `email_sent` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `email_eplist`
--
ALTER TABLE `email_eplist`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `email_eplist`
--
ALTER TABLE `email_eplist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;