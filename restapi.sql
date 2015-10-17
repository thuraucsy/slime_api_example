/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 50538
 Source Host           : localhost
 Source Database       : restapi

 Target Server Type    : MySQL
 Target Server Version : 50538
 File Encoding         : utf-8

 Date: 10/17/2015 22:21:28 PM
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `api_auth`
-- ----------------------------
DROP TABLE IF EXISTS `api_auth`;
CREATE TABLE `api_auth` (
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `id_auth` int(11) NOT NULL AUTO_INCREMENT,
  `token` int(11) NOT NULL,
  PRIMARY KEY (`id_auth`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- ----------------------------
--  Records of `api_auth`
-- ----------------------------
BEGIN;
INSERT INTO `api_auth` VALUES ('lemke', 'ca56f22ec999ab153ba0e94cc3cd083fe786257e', '1', '0');
COMMIT;

-- ----------------------------
--  Table structure for `articles`
-- ----------------------------
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `url` text NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Records of `articles`
-- ----------------------------
BEGIN;
INSERT INTO `articles` VALUES ('2', 'thura aung Title', 'thura url', '2015-10-17'), ('3', 'thura aung Title', 'thura url', '2015-10-17');
COMMIT;

-- ----------------------------
--  Table structure for `users`
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` text NOT NULL,
  `api_key` varchar(32) NOT NULL,
  `status` int(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1;

-- ----------------------------
--  Records of `users`
-- ----------------------------
BEGIN;
INSERT INTO `users` VALUES ('1', 'heyhey', 'thura.aung@example.com', '$2a$10$4957ec2d9940ea26ea516uTyvpFbwVs2gzE3eWMs1FIVZ5LjwYvrC', '24d136bfe0981f258999a6361dc3936c', '1', '2015-10-17 21:05:36'), ('2', 'thura', 'thura.ucsy@example.com', '$2a$10$352c6e1b15ac0ecbf329euD9q2N1wZJoMo.0SjmBiiiAaoyN5vqz2', 'bedbc4a977a15e28bcaa3b146aa74a85', '1', '2015-10-17 21:42:20');
COMMIT;

SET FOREIGN_KEY_CHECKS = 1;
