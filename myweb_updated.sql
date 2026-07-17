-- phpMyAdmin SQL Dump (updated with relay table)
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- Database: `myweb`

CREATE TABLE IF NOT EXISTS `tbl_user` (
  `id`       int(11)      NOT NULL AUTO_INCREMENT,
  `name`     varchar(50)  NOT NULL,
  `email`    varchar(50)  NOT NULL UNIQUE,
  `password` text         NOT NULL,
  `img`      varchar(100) NOT NULL DEFAULT '',
  `phone`    varchar(20)  NOT NULL DEFAULT '',
  `address`  text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbl_upload` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     int(11)      NOT NULL,
  `image_name`  varchar(255) NOT NULL,
  `uploaded_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `tbl_upload_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `tbl_files` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     int(11)      NOT NULL,
  `files_name`  varchar(255) NOT NULL,
  `uploaded_at` timestamp    NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- NEW: relay control table
CREATE TABLE IF NOT EXISTS `tbl_relay` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`     int(11)      NOT NULL,
  `relay_num`   tinyint      NOT NULL DEFAULT 1,
  `relay_name`  varchar(60)  NOT NULL DEFAULT 'Relay',
  `relay_icon`  varchar(10)  NOT NULL DEFAULT '💡',
  `status`      tinyint(1)   NOT NULL DEFAULT 0,
  `updated_at`  timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_relay` (`user_id`, `relay_num`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `tbl_relay_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample users (passwords: 'password123')
INSERT IGNORE INTO `tbl_user` (`id`,`name`,`email`,`password`,`img`,`phone`) VALUES
(1,'Somkiat Jaidee','eleclabs@gmail.com','$2y$10$nkbta.nmVRONBIs1LUTjc.dj/P2o30GDlY9Lfjp6DOjU.0HgELdLi','',''),
(2,'สมเกียรติ ใจดี','siamcodes@gmail.com','$2y$10$Cu9KAb891sYCUoOrBzX8Tul.Gukjts9YjBTq4K4n65kIiMQXD8Yuu','','');

COMMIT;
