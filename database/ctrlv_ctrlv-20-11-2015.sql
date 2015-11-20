# ************************************************************
# Sequel Pro SQL dump
# Version 4499
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.5.41-MariaDB-1ubuntu0.14.04.1-log)
# Database: ctrlv_ctrlv
# Generation Time: 2015-11-20 22:36:03 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table email_bounces
# ------------------------------------------------------------

DROP TABLE IF EXISTS `email_bounces`;

CREATE TABLE `email_bounces` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `type` varchar(1) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table favs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `favs`;

CREATE TABLE `favs` (
  `userID` int(11) NOT NULL,
  `imageID` int(11) NOT NULL,
  PRIMARY KEY (`userID`,`imageID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table files
# ------------------------------------------------------------

DROP TABLE IF EXISTS `files`;

CREATE TABLE `files` (
  `fileID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `filename` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `IP` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table image_tags
# ------------------------------------------------------------

DROP TABLE IF EXISTS `image_tags`;

CREATE TABLE `image_tags` (
  `imageID` int(11) NOT NULL,
  `tagID` int(11) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`imageID`,`tagID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table images
# ------------------------------------------------------------

DROP TABLE IF EXISTS `images`;

CREATE TABLE `images` (
  `imageID` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `via` varchar(255) NOT NULL,
  `IP` varchar(255) NOT NULL,
  `userID` int(11) DEFAULT NULL,
  `key` varchar(255) NOT NULL,
  `uncroppedfilename` varchar(255) NOT NULL,
  `caption` varchar(255) NOT NULL,
  `privacy` int(1) NOT NULL,
  `password` varchar(255) NOT NULL,
  `annotation` varchar(255) NOT NULL,
  `notes` longtext NOT NULL,
  `thumb` tinyint(1) NOT NULL,
  `w` int(11) NOT NULL,
  `h` int(11) NOT NULL,
  `ocr` tinyint(1) NOT NULL,
  `ocrskip` tinyint(1) NOT NULL,
  `ocrtext` longtext NOT NULL,
  `ocrinprogress` tinyint(1) NOT NULL,
  `tagged` tinyint(1) NOT NULL,
  `views` int(11) NOT NULL,
  `filesize` int(11) NOT NULL,
  `batchID` varchar(255) NOT NULL,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`imageID`),
  KEY `userID` (`userID`),
  KEY `uploadBatch` (`batchID`),
  KEY `expires_at` (`expires_at`),
  FULLTEXT KEY `ocrtext` (`ocrtext`),
  FULLTEXT KEY `caption` (`caption`),
  FULLTEXT KEY `caption_2` (`caption`,`ocrtext`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table tags
# ------------------------------------------------------------

DROP TABLE IF EXISTS `tags`;

CREATE TABLE `tags` (
  `tagID` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `userID` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `images` int(11) NOT NULL,
  `lastAdded` datetime NOT NULL,
  `privacy` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`tagID`),
  UNIQUE KEY `name` (`name`,`userID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table testtable
# ------------------------------------------------------------

DROP TABLE IF EXISTS `testtable`;

CREATE TABLE `testtable` (
  `penis` varchar(255) NOT NULL,
  `sex` varchar(255) NOT NULL,
  `boobs` varchar(255) NOT NULL,
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `butts` int(11) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table user_sessions
# ------------------------------------------------------------

DROP TABLE IF EXISTS `user_sessions`;

CREATE TABLE `user_sessions` (
  `sessionKey` varchar(255) NOT NULL,
  `userID` int(11) NOT NULL,
  `IP` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`sessionKey`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `userID` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `fbID` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `signupdate` datetime NOT NULL,
  `moderator` tinyint(1) NOT NULL,
  `defaultPrivacy` int(11) NOT NULL,
  `defaultPassword` varchar(255) NOT NULL,
  PRIMARY KEY (`userID`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;




/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
