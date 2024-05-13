-- ---------------------------------------------------------------
--                  _         _       _        _
--    ___ _ __ ___ | |_ _ __ | | __ _| |_ _ __(_)_ __   ___
--   / __| '_ ` _ \| __| '_ \| |/ _` | __| '__| | '_ \ / _ \
--   \__ \ | | | | | |_| |_) | | (_| | |_| |  | | | | |  __/
--   |___/_| |_| |_|\__| .__/|_|\__,_|\__|_|  |_|_| |_|\___|
--                     |_|               _____
--                                 ||    |   D
--   A custom SMTP Honeypot        ||    |   |
--   written in PHP with focus     ||    |   |
--   on gathering intel on threat  ||    \___|             _
--   actors and for doing spam     ||      | |  _______  -( (-
--   forensic work                 ||      |__'(-------)  '-'
--                                 ||          |       /
--  (c) 2024 - THUGSred            ||     ___,-\__..__|__
-- ---------------------------------------------------------------
-- SQL Dump for the database smtplatrine
-- PLEASE NOTE THIS IS DESTRUCTIVE !!!! USING DROP TABLES !!
-- ONLY USE THIS FILE IF YOU WANT TO RECREATE THE DATABASE
-- ---------------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for smtplatrine
DROP DATABASE IF EXISTS `smtplatrine`;
CREATE DATABASE IF NOT EXISTS `smtplatrine` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci */;
USE `smtplatrine`;

-- Dumping structure for table smtplatrine.acl_blacklist_geo
DROP TABLE IF EXISTS `acl_blacklist_geo`;
CREATE TABLE IF NOT EXISTS `acl_blacklist_geo` (
  `geo_code` varchar(2) NOT NULL,
  `geo_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `geo_lastseen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `geo_conn_tries` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`geo_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Any Country code added to this list will be blacklisted from using the service. Country code is 2 chars (CC)';

-- Data exporting was unselected.

-- Dumping structure for table smtplatrine.acl_blacklist_ip
DROP TABLE IF EXISTS `acl_blacklist_ip`;
CREATE TABLE IF NOT EXISTS `acl_blacklist_ip` (
  `ip_addr` varchar(32) NOT NULL,
  `ip_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_lastseen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `ip_conn_tries` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ip_addr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Any IP added to this list will be blacklisted from using the service';

-- Data exporting was unselected.

-- Dumping structure for table smtplatrine.honeypot_attachments
DROP TABLE IF EXISTS `honeypot_attachments`;
CREATE TABLE IF NOT EXISTS `honeypot_attachments` (
  `attachment_email` int(11) NOT NULL,
  `attachment_uuid` uuid NOT NULL,
  `attachment_filename` varchar(128) NOT NULL,
  `attachment_size` int(11) NOT NULL DEFAULT 0,
  `attachment_mimetype` varchar(50) NOT NULL,
  `attachment_stored_path` varchar(256) DEFAULT NULL,
  `attachment_stored` enum('Yes','No') NOT NULL DEFAULT 'No',
  `attachment_hash_md5` varchar(32) DEFAULT NULL,
  `attachment_hash_sha1` varchar(40) DEFAULT NULL,
  `attachment_hash_sha256` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`attachment_email`) USING BTREE,
  KEY `attachment_uuid` (`attachment_uuid`),
  CONSTRAINT `fk_attachment_email` FOREIGN KEY (`attachment_email`) REFERENCES `honeypot_emails` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='This table will hold all attachments seen in the emails';

-- Data exporting was unselected.

-- Dumping structure for table smtplatrine.honeypot_clients
DROP TABLE IF EXISTS `honeypot_clients`;
CREATE TABLE IF NOT EXISTS `honeypot_clients` (
  `smtp_client_ip` varchar(32) NOT NULL,
  `smtp_client_network` varchar(45) NOT NULL,
  `smtp_client_hostname` varchar(256) DEFAULT NULL,
  `smtp_client_as_number` int(11) DEFAULT NULL,
  `smtp_client_as_name` varchar(256) DEFAULT NULL,
  `smtp_client_seen_first` timestamp NOT NULL DEFAULT current_timestamp(),
  `smtp_client_seen_last` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `smtp_client_seen` int(11) NOT NULL DEFAULT 0,
  `smtp_client_geo_country_code` varchar(2) DEFAULT NULL,
  `smtp_client_geo_country_name` varchar(70) DEFAULT NULL,
  `smtp_client_geo_continent` varchar(70) DEFAULT NULL,
  `smtp_client_geo_eu_union` enum('Unknown','Yes','No') DEFAULT 'Unknown',
  `smtp_client_geo_city_name` varchar(70) DEFAULT NULL,
  `smtp_client_geo_city_postalcode` varchar(20) DEFAULT NULL,
  `smtp_client_geo_latitude` float NOT NULL,
  `smtp_client_geo_longitude` float NOT NULL,
  PRIMARY KEY (`smtp_client_ip`),
  KEY `smtp_client_hostname` (`smtp_client_hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='This table will hold all IP realted info for the client connecting to the honeypot';

-- Data exporting was unselected.

-- Dumping structure for table smtplatrine.honeypot_credentials
DROP TABLE IF EXISTS `honeypot_credentials`;
CREATE TABLE IF NOT EXISTS `honeypot_credentials` (
  `auth_email` int(11) NOT NULL,
  `auth_type` enum('NONE','UNKNOWN','LOGIN','PLAIN','CRAM-MD5','DIGEST-MD5','NTLM','GSSAPI','XOAUTH','XOAUTH2') DEFAULT 'NONE',
  `auth_username` varchar(60) DEFAULT NULL,
  `auth_password` varchar(60) DEFAULT NULL,
  PRIMARY KEY (`auth_email`) USING BTREE,
  CONSTRAINT `fk_auth_email` FOREIGN KEY (`auth_email`) REFERENCES `honeypot_emails` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Any AUTH credentials seen is listed here';

-- Data exporting was unselected.

-- Dumping structure for table smtplatrine.honeypot_emails
DROP TABLE IF EXISTS `honeypot_emails`;
CREATE TABLE IF NOT EXISTS `honeypot_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Main uniqe ID to identify a email',
  `smtp_client_ip` varchar(32) NOT NULL,
  `smtp_client_port` int(11) NOT NULL DEFAULT 0,
  `smtp_delivered_to` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`smtp_delivered_to`)),
  `smtp_queue_id` varchar(12) NOT NULL DEFAULT '0',
  `smtp_server_hostname` varchar(128) NOT NULL DEFAULT '0',
  `smtp_server_listning` varchar(36) NOT NULL DEFAULT '0',
  `smtp_server_port` int(11) NOT NULL DEFAULT 0,
  `smtp_server_system` varchar(128) NOT NULL DEFAULT '0',
  `smtp_header_received` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' COMMENT 'Must be JSON',
  `smtp_header_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `smtp_header_to` varchar(512) NOT NULL DEFAULT '0',
  `smtp_header_from` varchar(128) NOT NULL DEFAULT '0',
  `smtp_header_cc` varchar(512) NOT NULL DEFAULT '0',
  `smtp_header_reply_to` varchar(512) NOT NULL DEFAULT '0',
  `smtp_header_subject` varchar(265) NOT NULL DEFAULT '0',
  `smtp_header_message_id` varchar(128) NOT NULL DEFAULT '0',
  `smtp_header_xmailer` varchar(128) NOT NULL DEFAULT '0',
  `smtp_header_useragent` varchar(128) NOT NULL DEFAULT '0',
  `smtp_header_content_type` varchar(128) NOT NULL DEFAULT '0',
  `smtp_header_content_transfer_encoding` varchar(25) NOT NULL DEFAULT '0',
  `smtp_attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' COMMENT 'Must be JSON' CHECK (json_valid(`smtp_attachments`)),
  `smtp_body_text` longtext NOT NULL DEFAULT '',
  `smtp_body_html` longtext NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `fk_smtp_client_ip` (`smtp_client_ip`),
  CONSTRAINT `fk_smtp_client_ip` FOREIGN KEY (`smtp_client_ip`) REFERENCES `honeypot_clients` (`smtp_client_ip`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='This table will consist of all mails recieved via the SMTP honeypot';

-- Data exporting was unselected.

-- Dumping structure for table smtplatrine.honeypot_recipients
DROP TABLE IF EXISTS `honeypot_recipients`;
CREATE TABLE IF NOT EXISTS `honeypot_recipients` (
  `recipients_email` int(11) NOT NULL,
  `recipients_address` varchar(256) NOT NULL DEFAULT '',
  `recipients_name` varchar(256) NOT NULL DEFAULT '',
  `recipients_username` varchar(128) NOT NULL,
  `recipients_tags` varchar(128) NOT NULL,
  `recipients_domain` varchar(50) NOT NULL,
  `recipients_seen` int(11) NOT NULL DEFAULT 0,
  `recipients_seen_first` timestamp NOT NULL DEFAULT current_timestamp(),
  `recipients_seen_last` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`recipients_email`),
  UNIQUE KEY `recipients_address` (`recipients_address`),
  KEY `recipients_domain` (`recipients_domain`),
  CONSTRAINT `fk_recipients_email` FOREIGN KEY (`recipients_email`) REFERENCES `honeypot_emails` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='This table holds all seen recipients from the SMTP sessions, we don''t have duplicates however we update a seen count';

-- Data exporting was unselected.

-- Dumping structure for table smtplatrine.meta_abuseipdb
DROP TABLE IF EXISTS `meta_abuseipdb`;
CREATE TABLE IF NOT EXISTS `meta_abuseipdb` (
  `abuseipdb_client_ip` varchar(36) NOT NULL,
  `abuseipdb_ip_addr` varchar(36) NOT NULL,
  `abuseipdb_ip_version` enum('IPv4','IPv6') NOT NULL DEFAULT 'IPv4',
  `abuseipdb_domain` varchar(128) NOT NULL,
  `abuseipdb_hostnames` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' COMMENT 'Must be JSON',
  `abuseipdb_is_public` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `abuseipdb_is_tor` enum('Yes','No') NOT NULL DEFAULT 'No',
  `abuseipdb_is_whitelisted` enum('Yes','No') NOT NULL DEFAULT 'No',
  `abuseipdb_abuse_confidence_score` int(11) NOT NULL DEFAULT 0,
  `abuseipdb_country_code` varchar(4) NOT NULL,
  `abuseipdb_country_name` varchar(50) NOT NULL,
  `abuseipdb_usage_type` varchar(128) NOT NULL,
  `abuseipdb_isp` varchar(128) NOT NULL,
  `abuseipdb_total_reports` int(11) NOT NULL DEFAULT 0,
  `abuseipdb_num_distinct_users` int(11) NOT NULL DEFAULT 0,
  `abuseipdb_last_reported_at` timestamp NOT NULL,
  PRIMARY KEY (`abuseipdb_client_ip`),
  CONSTRAINT `fk_abuseipdb_client_ip` FOREIGN KEY (`abuseipdb_client_ip`) REFERENCES `honeypot_clients` (`smtp_client_ip`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='All data pulled from AbuseIPDB is kept here just so we dont clutter up the big emails table with even more data';

-- Data exporting was unselected.

-- Dumping structure for table smtplatrine.meta_otx
DROP TABLE IF EXISTS `meta_otx`;
CREATE TABLE IF NOT EXISTS `meta_otx` (
  `otx_client_ip` varchar(36) NOT NULL,
  `otx_namespace` varchar(20) NOT NULL,
  `otx_predicate` varchar(20) NOT NULL,
  `otx_pulse_count` int(11) DEFAULT 0,
  `otx_pulses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' COMMENT 'Must be JSON',
  PRIMARY KEY (`otx_client_ip`),
  CONSTRAINT `fk_otx_client_ip` FOREIGN KEY (`otx_client_ip`) REFERENCES `honeypot_clients` (`smtp_client_ip`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='All data pulled from AlienVault OTX is kept here just so we dont clutter up the big emails table with even more data';

-- Data exporting was unselected.

-- Dumping structure for table smtplatrine.meta_virustotal
DROP TABLE IF EXISTS `meta_virustotal`;
CREATE TABLE IF NOT EXISTS `meta_virustotal` (
  `vt_attachment` uuid NOT NULL,
  `vt_scan_date` datetime NOT NULL,
  `vt_positives` int(11) NOT NULL DEFAULT 0,
  `vt_total` int(11) NOT NULL DEFAULT 0,
  `vt_permalink` varchar(128) NOT NULL DEFAULT '',
  `vt_scans` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' COMMENT 'Must be JSON',
  PRIMARY KEY (`vt_attachment`),
  CONSTRAINT `fk_vt_attachment` FOREIGN KEY (`vt_attachment`) REFERENCES `honeypot_attachments` (`attachment_uuid`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='All data pulled from Virustotal is kept here just so we dont clutter up the big emails table with even more data';

-- Data exporting was unselected.

-- Dumping structure for table smtplatrine.stats
DROP TABLE IF EXISTS `stats`;
CREATE TABLE IF NOT EXISTS `stats` (
  `total_connections` int(11) DEFAULT NULL,
  `total_smtp_commands` int(11) DEFAULT NULL,
  `total_emails` int(11) DEFAULT NULL,
  `total_attachments` int(11) DEFAULT NULL,
  `total_data_processed` int(11) DEFAULT NULL,
  `total_clients` int(11) DEFAULT NULL,
  `uniqe_email_adresses` int(11) DEFAULT NULL,
  `list_xmailers_seen` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`list_xmailers_seen`)),
  `list_useragent_seen` longtext DEFAULT NULL,
  `list_mimetypes_seen` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Table that holds some fun stats, so we dont have to make big queries cross many tables';

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
