-- phpMyAdmin SQL Dump
-- version 3.3.10.4
-- http://www.phpmyadmin.net
--
-- Host: mysql.seattleswiss.com
-- Generation Time: May 08, 2012 at 10:59 AM
-- Server version: 5.1.53
-- PHP Version: 5.3.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `seattleswiss_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `tblAdmin`
--

CREATE TABLE IF NOT EXISTS `tblAdmin` (
  `admin_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(50) NOT NULL,
  `admin_city` varchar(50) NOT NULL,
  `admin_pass` varchar(50) NOT NULL,
  `admin_type` varchar(50) NOT NULL,
  `admin_email` varchar(50) NOT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=12 ;

-- --------------------------------------------------------

--
-- Table structure for table `tblGame`
--

CREATE TABLE IF NOT EXISTS `tblGame` (
  `game_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `round_id` bigint(20) NOT NULL,
  `court` bigint(20) NOT NULL DEFAULT '0',
  `game_time` datetime NOT NULL,
  PRIMARY KEY (`game_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1530 ;

-- --------------------------------------------------------

--
-- Table structure for table `tblGameTeams`
--

CREATE TABLE IF NOT EXISTS `tblGameTeams` (
  `score_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `game_id` bigint(20) NOT NULL,
  `team_id` bigint(20) NOT NULL,
  `score` smallint(6) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`score_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=2929 ;

-- --------------------------------------------------------

--
-- Table structure for table `tblPlayer`
--

CREATE TABLE IF NOT EXISTS `tblPlayer` (
  `player_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_uid` bigint(20) NOT NULL,
  `player_name` varchar(40) NOT NULL,
  `player_info` varchar(40) NOT NULL,
  PRIMARY KEY (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tblRound`
--

CREATE TABLE IF NOT EXISTS `tblRound` (
  `round_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `round_number` tinyint(4) NOT NULL,
  `tournament_id` bigint(20) NOT NULL,
  PRIMARY KEY (`round_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=230 ;

-- --------------------------------------------------------

--
-- Table structure for table `tblTeam`
--

CREATE TABLE IF NOT EXISTS `tblTeam` (
  `team_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `team_uid` bigint(20) unsigned NOT NULL,
  `team_name` varchar(50) NOT NULL,
  `team_init` tinyint(4) NOT NULL,
  `tournament_id` bigint(20) NOT NULL,
  `team_text` varchar(255) NOT NULL,
  `is_disabled` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=473 ;

-- --------------------------------------------------------

--
-- Table structure for table `tblTeamPlayers`
--

CREATE TABLE IF NOT EXISTS `tblTeamPlayers` (
  `entry_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) NOT NULL,
  `player_id` bigint(20) NOT NULL,
  PRIMARY KEY (`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `tblTournament`
--

CREATE TABLE IF NOT EXISTS `tblTournament` (
  `tournament_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `tournament_name` varchar(50) NOT NULL,
  `tournament_city` varchar(50) NOT NULL,
  `tournament_date` datetime NOT NULL,
  `tournament_rounds` smallint(2) NOT NULL,
  `tournament_mode` tinyint(4) NOT NULL,
  `tournament_owner` bigint(20) NOT NULL,
  `is_public` tinyint(4) NOT NULL,
  `is_over` tinyint(4) NOT NULL,
  PRIMARY KEY (`tournament_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=28 ;

-- --------------------------------------------------------

--
-- Table structure for table `tblTournamentAdmins`
--

CREATE TABLE IF NOT EXISTS `tblTournamentAdmins` (
  `tournament_id` bigint(20) NOT NULL,
  `admin_id` bigint(20) NOT NULL,
  KEY `tournament_id` (`tournament_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

