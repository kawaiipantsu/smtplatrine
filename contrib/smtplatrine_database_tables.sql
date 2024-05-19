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
-- NON DESTRUCTIVE SQL DUMP - You need to supply a database name
-- It will however drop the tables if they exist
-- ---------------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

DROP TABLE IF EXISTS `acl_blacklist_geo`;
CREATE TABLE IF NOT EXISTS `acl_blacklist_geo` (
  `geo_code` varchar(2) NOT NULL,
  `geo_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `geo_lastseen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `geo_conn_tries` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`geo_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Any Country code added to this list will be blacklisted from using the service. Country code is 2 chars (CC)';

DROP TABLE IF EXISTS `acl_blacklist_ip`;
CREATE TABLE IF NOT EXISTS `acl_blacklist_ip` (
  `ip_addr` varchar(32) NOT NULL,
  `ip_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_lastseen` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `ip_conn_tries` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`ip_addr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Any IP added to this list will be blacklisted from using the service';

DROP TABLE IF EXISTS `honeypot_attachments`;
CREATE TABLE IF NOT EXISTS `honeypot_attachments` (
  `attachments_email` int(11) NOT NULL,
  `attachments_uuid` varchar(36) NOT NULL DEFAULT '',
  `attachments_filename` varchar(128) NOT NULL,
  `attachments_size` int(11) NOT NULL DEFAULT 0,
  `attachments_mimetype` varchar(50) NOT NULL,
  `attachments_stored_path` varchar(256) DEFAULT NULL,
  `attachments_stored` enum('Yes','No') NOT NULL DEFAULT 'No',
  `attachments_hash_md5` varchar(32) DEFAULT NULL,
  `attachments_hash_sha1` varchar(40) DEFAULT NULL,
  `attachments_hash_sha256` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`attachments_email`) USING BTREE,
  KEY `attachment_uuid` (`attachments_uuid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='This table will hold all attachments seen in the emails';

DROP TABLE IF EXISTS `honeypot_clients`;
CREATE TABLE IF NOT EXISTS `honeypot_clients` (
  `clients_ip` varchar(32) NOT NULL,
  `clients_hostname` varchar(256) DEFAULT NULL,
  `clients_as_number` int(11) DEFAULT NULL,
  `clients_as_name` varchar(256) DEFAULT NULL,
  `clients_seen_first` timestamp NOT NULL DEFAULT current_timestamp(),
  `clients_seen_last` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  `clients_seen` int(11) NOT NULL DEFAULT 0,
  `clients_geo_country_code` varchar(2) DEFAULT NULL,
  `clients_geo_country_name` varchar(70) DEFAULT NULL,
  `clients_geo_continent` varchar(70) DEFAULT NULL,
  `clients_geo_eu_union` enum('Unknown','Yes','No') DEFAULT NULL,
  `clients_geo_city_name` varchar(70) DEFAULT NULL,
  `clients_geo_city_postalcode` varchar(20) DEFAULT NULL,
  `clients_geo_subdivisionname` varchar(70) DEFAULT NULL,
  `clients_geo_latitude` float DEFAULT NULL,
  `clients_geo_longitude` float DEFAULT NULL,
  `client_location_accuracy_radius` int(11) DEFAULT NULL,
  `clients_timezone` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`clients_ip`) USING BTREE,
  KEY `smtp_client_hostname` (`clients_hostname`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='This table will hold all IP realted info for the client connecting to the honeypot';

DROP TABLE IF EXISTS `honeypot_credentials`;
CREATE TABLE IF NOT EXISTS `honeypot_credentials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `credentials_type` enum('NONE','UNKNOWN','LOGIN','PLAIN','CRAM-MD5','DIGEST-MD5','NTLM','GSSAPI','XOAUTH','XOAUTH2') DEFAULT 'NONE',
  `credentials_username` varchar(60) DEFAULT NULL COMMENT 'These will be converted to UTF8, might corrupt them',
  `credentials_password` varchar(60) DEFAULT NULL COMMENT 'These will be converted to UTF8, might corrupt them',
  `credentials_serialized_original` varchar(512) DEFAULT NULL COMMENT 'Serialized array with original encoding',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Any AUTH credentials seen is listed here';

DROP TABLE IF EXISTS `honeypot_emails`;
CREATE TABLE IF NOT EXISTS `honeypot_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Main uniqe ID to identify a email',
  `emails_client_ip` varchar(32) NOT NULL,
  `emails_client_port` int(11) NOT NULL DEFAULT 0,
  `emails_recipients` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Must be JSON',
  `emails_queue_id` varchar(12) NOT NULL DEFAULT '0',
  `emails_server_hostname` varchar(128) NOT NULL DEFAULT '0',
  `emails_server_listening` varchar(36) NOT NULL DEFAULT '0',
  `emails_server_port` int(11) NOT NULL DEFAULT 0,
  `emails_server_system` varchar(128) NOT NULL DEFAULT '0',
  `emails_header_received` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Must be JSON',
  `emails_header_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `emails_header_to` varchar(512) NOT NULL DEFAULT '0',
  `emails_header_from` varchar(128) NOT NULL DEFAULT '0',
  `emails_header_cc` varchar(512) NOT NULL DEFAULT '0',
  `emails_header_reply_to` varchar(512) NOT NULL DEFAULT '0',
  `emails_header_subject` varchar(265) NOT NULL DEFAULT '0',
  `emails_header_message_id` varchar(128) NOT NULL DEFAULT '0',
  `emails_header_xmailer` varchar(128) NOT NULL DEFAULT '0',
  `emails_header_useragent` varchar(128) NOT NULL DEFAULT '0',
  `emails_header_organization` varchar(128) NOT NULL DEFAULT '0',
  `emails_header_content_type` varchar(128) NOT NULL DEFAULT '0',
  `emails_header_content_transfer_encoding` varchar(25) NOT NULL DEFAULT '0',
  `emails_attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Must be JSON',
  `emails_body_text` longtext NOT NULL DEFAULT '',
  `emails_body_html` longtext NOT NULL DEFAULT '',
  `emails_rawemails_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_emails_client_ip` (`emails_client_ip`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='This table will consist of all mails recieved via the SMTP honeypot';

DROP TABLE IF EXISTS `honeypot_rawemails`;
CREATE TABLE IF NOT EXISTS `honeypot_rawemails` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rawemails_data` longtext NOT NULL,
  `rawemails_received` timestamp NULL DEFAULT current_timestamp(),
  `rawemails_keep` enum('Keep','Unseen','Seen','Delete') NOT NULL DEFAULT 'Unseen',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='This table will hold a backup of the complete raw email recieved by the client, please note this can take up space and should be cleaned up regularly see contrib';

DROP TABLE IF EXISTS `honeypot_recipients`;
CREATE TABLE IF NOT EXISTS `honeypot_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipients_address` varchar(256) NOT NULL DEFAULT '',
  `recipients_username` varchar(128) NOT NULL,
  `recipients_tags` varchar(128) NOT NULL,
  `recipients_domain` varchar(50) NOT NULL,
  `recipients_seen` int(11) NOT NULL DEFAULT 0,
  `recipients_seen_first` timestamp NOT NULL DEFAULT current_timestamp(),
  `recipients_seen_last` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `recipients_address` (`recipients_address`),
  KEY `recipients_domain` (`recipients_domain`)
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='This table holds all seen recipients from the SMTP sessions, we don''t have duplicates however we update a seen count';

DROP TABLE IF EXISTS `meta_abuseipdb`;
CREATE TABLE IF NOT EXISTS `meta_abuseipdb` (
  `abuseipdb_client_ip` varchar(36) NOT NULL,
  `abuseipdb_ip_addr` varchar(36) NOT NULL,
  `abuseipdb_ip_version` enum('IPv4','IPv6') NOT NULL DEFAULT 'IPv4',
  `abuseipdb_domain` varchar(128) NOT NULL,
  `abuseipdb_hostnames` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Must be JSON',
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
  PRIMARY KEY (`abuseipdb_client_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='All data pulled from AbuseIPDB is kept here just so we dont clutter up the big emails table with even more data';

DROP TABLE IF EXISTS `meta_otx`;
CREATE TABLE IF NOT EXISTS `meta_otx` (
  `otx_client_ip` varchar(36) NOT NULL,
  `otx_namespace` varchar(20) NOT NULL,
  `otx_predicate` varchar(20) NOT NULL,
  `otx_pulse_count` int(11) DEFAULT 0,
  `otx_pulses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Must be JSON',
  PRIMARY KEY (`otx_client_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='All data pulled from AlienVault OTX is kept here just so we dont clutter up the big emails table with even more data';

DROP TABLE IF EXISTS `meta_virustotal`;
CREATE TABLE IF NOT EXISTS `meta_virustotal` (
  `vt_attachment` varchar(36) NOT NULL,
  `vt_scan_date` datetime NOT NULL,
  `vt_positives` int(11) NOT NULL DEFAULT 0,
  `vt_total` int(11) NOT NULL DEFAULT 0,
  `vt_permalink` varchar(128) NOT NULL DEFAULT '',
  `vt_scans` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Must be JSON',
  PRIMARY KEY (`vt_attachment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='All data pulled from Virustotal is kept here just so we dont clutter up the big emails table with even more data';

DROP TABLE IF EXISTS `www_stats`;
CREATE TABLE IF NOT EXISTS `www_stats` (
  `stats_total_connections` int(11) NOT NULL DEFAULT 0,
  `stats_total_smtp_commands` int(11) NOT NULL DEFAULT 0,
  `stats_total_emails` int(11) NOT NULL DEFAULT 0,
  `stats_total_attachments` int(11) NOT NULL DEFAULT 0,
  `stats_total_data_processed` int(11) NOT NULL DEFAULT 0,
  `stats_total_clients` int(11) NOT NULL DEFAULT 0,
  `stats_unique_email_addresses` int(11) NOT NULL DEFAULT 0,
  `stats_list_xmailers_seen` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Must be JSON',
  `stats_list_useragent_seen` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Must be JSON',
  `stats_list_mimetypes_seen` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Must be JSON'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Table that holds some fun stats, so we dont have to make big queries cross many tables';

DROP TABLE IF EXISTS `www_users`;
CREATE TABLE IF NOT EXISTS `www_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users_username` varchar(128) DEFAULT NULL,
  `users_password` varchar(255) DEFAULT NULL,
  `users_fullname` varchar(128) DEFAULT NULL,
  `users_email` varchar(128) DEFAULT NULL COMMENT 'Will try and use it for gravatar as well',
  `users_role` enum('Statistical analyst','Forensic analyst','Admin') DEFAULT 'Statistical analyst',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='This table is just credentials that can login on the web page for stats and forensic work';

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
