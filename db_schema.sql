-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               5.5.50-MariaDB - MariaDB Server
-- Server OS:                    Linux
-- HeidiSQL Version:             9.3.0.4984
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table iq_test.balance
DROP TABLE IF EXISTS `balance`;
CREATE TABLE IF NOT EXISTS `balance` (
  `user` bigint(20) unsigned NOT NULL,
  `balance` decimal(11,2) unsigned NOT NULL,
  PRIMARY KEY (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Current balance of users';

-- Data exporting was unselected.


-- Dumping structure for table iq_test.lock
DROP TABLE IF EXISTS `lock`;
CREATE TABLE IF NOT EXISTS `lock` (
  `operation_uuid` binary(16) NOT NULL,
  `user` bigint(20) unsigned NOT NULL,
  `amount` decimal(11,2) unsigned NOT NULL,
  PRIMARY KEY (`operation_uuid`),
  KEY `FK_lock_balance` (`user`),
  CONSTRAINT `FK_lock_balance` FOREIGN KEY (`user`) REFERENCES `balance` (`user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='List of locked amounts';

-- Data exporting was unselected.


-- Dumping structure for table iq_test.operation
DROP TABLE IF EXISTS `operation`;
CREATE TABLE IF NOT EXISTS `operation` (
  `uuid` binary(16) NOT NULL,
  `input_dup_num` int(10) unsigned NOT NULL,
  `input_md5` binary(16) NOT NULL,
  `output_dup_num` int(10) unsigned NOT NULL,
  `completed` datetime NOT NULL,
  `raw_body` blob NOT NULL,
  PRIMARY KEY (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci COMMENT='Log of successful operations\r\nOld operations can be purged or moved to a backup';

-- Data exporting was unselected.
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
