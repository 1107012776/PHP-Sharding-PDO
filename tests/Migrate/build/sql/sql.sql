SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for account_0
-- ----------------------------
DROP TABLE IF EXISTS `account_0`;
CREATE TABLE `account_0`  (
  `id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'id主键',
  `username` varchar(255)  NOT NULL DEFAULT ''  COMMENT '账号',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `username`(`username`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '账户拓展表，分布式用户快速登录查找';


-- ----------------------------
-- Table structure for article_0
-- ----------------------------
DROP TABLE IF EXISTS `article_0`;
CREATE TABLE `article_0`  (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `article_title` varchar(255)  NOT NULL DEFAULT '' COMMENT '文章标题',
  `content` mediumtext  COMMENT '文章html代码',
  `content_md` mediumtext  COMMENT 'markdown原始代码',
  `article_keyword` varchar(255)  NOT NULL DEFAULT '' COMMENT '文章关键词',
  `article_descript` varchar(255)  NOT NULL DEFAULT '' COMMENT '文章描述',
  `article_img` varchar(255)  NOT NULL DEFAULT '',
  `author` varchar(255)  NOT NULL DEFAULT '',
  `create_time` datetime(0) DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) DEFAULT NULL COMMENT '更新时间',
  `is_choice` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否精选 1为否 2为是',
  `is_push` int(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否上架: 1为上架 2为定时',
  `timing_time` datetime(0) DEFAULT NULL COMMENT '定时更新上架时间',
  `del_flag` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否删除: 1为删除 0为正常',
  `cate_id` tinyint(4) UNSIGNED NOT NULL DEFAULT 3 COMMENT '分类id',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `cate_id`(`cate_id`) USING BTREE
) ENGINE = InnoDB  CHARACTER SET = utf8mb4 COMMENT = '文章表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for article_1
-- ----------------------------
DROP TABLE IF EXISTS `article_1`;
CREATE TABLE `article_1`  (
  `id` bigint(11) NOT NULL AUTO_INCREMENT,
  `article_title` varchar(255)  NOT NULL DEFAULT '' COMMENT '文章标题',
  `content` mediumtext  COMMENT '文章html代码',
  `content_md` mediumtext  COMMENT 'markdown原始代码',
  `article_keyword` varchar(255)  NOT NULL DEFAULT '' COMMENT '文章关键词',
  `article_descript` varchar(255)  NOT NULL DEFAULT '' COMMENT '文章描述',
  `article_img` varchar(255)  NOT NULL DEFAULT '',
  `author` varchar(255)  NOT NULL DEFAULT '',
  `create_time` datetime(0) DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) DEFAULT NULL COMMENT '更新时间',
  `is_choice` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否精选 1为否 2为是',
  `is_push` tinyint(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '是否上架: 1为上架 2为定时',
  `timing_time` datetime(0) DEFAULT NULL COMMENT '定时更新上架时间',
  `del_flag` tinyint(4) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否删除: 1为删除 0为正常',
  `cate_id` tinyint(4) UNSIGNED NOT NULL DEFAULT 3 COMMENT '分类id',
  `user_id` bigint(20) UNSIGNED NOT NULL DEFAULT 0 COMMENT '用户id',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `cate_id`(`cate_id`) USING BTREE
) ENGINE = InnoDB  CHARACTER SET = utf8mb4 COMMENT = '文章表' ROW_FORMAT = Dynamic;


-- ----------------------------
-- Table structure for auto_distributed
-- ----------------------------
DROP TABLE IF EXISTS `auto_distributed`;
CREATE TABLE `auto_distributed`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `stub` char(1)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `stub`(`stub`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4;

-- ----------------------------
-- Table structure for category
-- ----------------------------
DROP TABLE IF EXISTS `category`;
CREATE TABLE `category`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id主键',
  `name` varchar(255)  NOT NULL DEFAULT '' COMMENT '分类名称',
  `create_time` datetime(0) DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '分类列表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of category
-- ----------------------------
INSERT INTO `category` VALUES (1, '慢生活', '2021-11-10 17:24:16',  '2021-11-10 17:24:16');
INSERT INTO `category` VALUES (2, '美文欣赏',  '2021-11-10 17:24:16',  '2021-11-10 17:24:16');
INSERT INTO `category` VALUES (3, '学无止境',  '2021-11-10 17:24:16',  '2021-11-10 17:24:16');

-- ----------------------------
-- Table structure for user_0
-- ----------------------------
DROP TABLE IF EXISTS `user_0`;
CREATE TABLE `user_0`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'id主键',
  `username` varchar(255)   NOT NULL DEFAULT '' COMMENT '账号' ,
  `password` varchar(255)  NOT NULL DEFAULT ''  COMMENT '密码',
  `nickname` varchar(255)  NOT NULL DEFAULT ''  COMMENT '昵称',
  `sex` tinyint(4) DEFAULT 0  COMMENT '性别：0为未知 1为男 2为女',
  `create_time` datetime(0) DEFAULT NULL COMMENT '创建时间',
  `update_time` datetime(0) DEFAULT NULL COMMENT '更新时间',
  `email` varchar(255)  NOT NULL DEFAULT ''  COMMENT '邮箱',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `username`(`username`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COMMENT = '账户表';



SET FOREIGN_KEY_CHECKS = 1;
