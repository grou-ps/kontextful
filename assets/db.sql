-- phpMyAdmin SQL Dump
-- version 4.0.10deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 17, 2014 at 07:02 AM
-- Server version: 5.5.35-1ubuntu1
-- PHP Version: 5.5.9-1ubuntu4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `kontextful`
--

-- --------------------------------------------------------

--
-- Table structure for table `analyzed_urls`
--

CREATE TABLE IF NOT EXISTS `analyzed_urls` (
  `url` text CHARACTER SET latin1 NOT NULL,
  `url_md5` char(32) CHARACTER SET latin1 NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `added_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `content_scores`
--

CREATE TABLE IF NOT EXISTS `content_scores` (
  `content_id` char(32) NOT NULL,
  `word` varchar(255) NOT NULL,
  `count` float unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  KEY `word` (`word`),
  KEY `content_id` (`content_id`)
) ENGINE=MEMORY DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `contexts`
--

CREATE TABLE IF NOT EXISTS `contexts` (
  `context_id` varbinary(16) NOT NULL,
  `title` varchar(255) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `service` enum('facebook') NOT NULL DEFAULT 'facebook',
  `service_type` enum('groups') NOT NULL DEFAULT 'groups',
  `service_id` bigint(20) unsigned NOT NULL,
  `added_at` datetime NOT NULL,
  UNIQUE KEY `context_id` (`context_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `context_scores`
--

CREATE TABLE IF NOT EXISTS `context_scores` (
  `context_id` varbinary(16) NOT NULL,
  `word` varchar(255) NOT NULL,
  `count` float unsigned NOT NULL,
  `weight` float unsigned NOT NULL,
  KEY `word` (`word`),
  KEY `context_id` (`context_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE IF NOT EXISTS `services` (
  `user_id` bigint(20) unsigned NOT NULL,
  `service` enum('facebook') NOT NULL,
  `service_id` bigint(20) unsigned NOT NULL,
  `added_at` datetime NOT NULL,
  UNIQUE KEY `service` (`service_id`,`service`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `added_at` datetime NOT NULL,
  `invite_code` varchar(20) NOT NULL,
  `long_term_access_token` text NOT NULL,
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
