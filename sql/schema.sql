SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `tblAdmin` (
  `admin_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `admin_name` varchar(50) NOT NULL,
  `admin_city` varchar(50) NOT NULL,
  `admin_pass` varchar(50) NOT NULL,
  `admin_type` varchar(50) NOT NULL,
  `admin_email` varchar(50) NOT NULL,
  `admin_controls` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tblGame` (
  `game_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `round_id` bigint(20) NOT NULL,
  `court` bigint(20) NOT NULL DEFAULT '0',
  `game_time` datetime NOT NULL,
  PRIMARY KEY (`game_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tblGameTeams` (
  `score_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `game_id` bigint(20) NOT NULL,
  `team_id` bigint(20) NOT NULL,
  `score` smallint(6) NOT NULL DEFAULT '-1',
  PRIMARY KEY (`score_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tblPlayer` (
  `player_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `player_uid` bigint(20) NOT NULL,
  `player_name` varchar(40) NOT NULL,
  `player_info` varchar(40) NOT NULL,
  PRIMARY KEY (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tblRound` (
  `round_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `round_number` tinyint(4) NOT NULL,
  `tournament_id` bigint(20) NOT NULL,
  PRIMARY KEY (`round_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tblTeam` (
  `team_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `team_uid` int(10) unsigned NOT NULL,
  `team_name` varchar(50) NOT NULL,
  `team_init` bigint(20) NOT NULL,
  `tournament_id` bigint(20) NOT NULL,
  `team_text` varchar(255) NOT NULL,
  `is_disabled` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`team_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tblTeamPlayers` (
  `entry_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `team_id` bigint(20) NOT NULL,
  `player_id` bigint(20) NOT NULL,
  PRIMARY KEY (`entry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tblTournamentAdmins` (
  `tournament_id` bigint(20) NOT NULL,
  `admin_id` bigint(20) NOT NULL,
  KEY `tournament_id` (`tournament_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
