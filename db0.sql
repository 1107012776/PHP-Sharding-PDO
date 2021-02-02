/*
 Navicat Premium Data Transfer

 Source Server         : 本地3306
 Source Server Type    : MySQL
 Source Server Version : 50714
 Source Host           : localhost:3306
 Source Schema         : db0

 Target Server Type    : MySQL
 Target Server Version : 50714
 File Encoding         : 65001

 Date: 25/07/2019 16:20:02
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for t_order_0
-- ----------------------------
DROP TABLE IF EXISTS `t_order_0`;
CREATE TABLE `t_order_0`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `order_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `create_time` datetime(0) NOT NULL DEFAULT '1970-01-01 08:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 21 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of t_order_0
-- ----------------------------
INSERT INTO `t_order_0` VALUES (1, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (2, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (3, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (4, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (5, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (6, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (7, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (8, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (9, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (10, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (11, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (12, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (13, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (14, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (15, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (16, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (17, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (18, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (19, 2, 2, '1970-01-01 08:00:00');
INSERT INTO `t_order_0` VALUES (20, 2, 2, '1970-01-01 08:00:00');

-- ----------------------------
-- Table structure for t_order_1
-- ----------------------------
DROP TABLE IF EXISTS `t_order_1`;
CREATE TABLE `t_order_1`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `order_id` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `create_time` datetime(0) NOT NULL DEFAULT '1970-01-01 08:00:00',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 59 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of t_order_1
-- ----------------------------
INSERT INTO `t_order_1` VALUES (1, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (3, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (4, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (5, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (6, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (7, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (8, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (9, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (10, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (11, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (12, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (13, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (14, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (15, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (16, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (17, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (18, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (19, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (20, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (21, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (22, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (23, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (24, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (25, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (26, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (27, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (28, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (29, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (30, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (31, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (32, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (33, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (34, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (35, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (36, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (37, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (38, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (39, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (40, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (41, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (42, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (43, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (44, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (45, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (46, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (47, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (48, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (49, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (50, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (51, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (52, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (53, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (54, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (55, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (56, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (57, 2, 1, '1970-01-01 08:00:00');
INSERT INTO `t_order_1` VALUES (58, 2, 1, '1970-01-01 08:00:00');

SET FOREIGN_KEY_CHECKS = 1;
