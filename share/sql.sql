/*
SQLyog Ultimate v12.09 (64 bit)
MySQL - 5.5.53 : Database - xin_share
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`xin_share` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `xin_share`;

/*Table structure for table `system_auth` */

CREATE TABLE `system_auth` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(20) NOT NULL COMMENT '权限名称',
  `status` tinyint(1) unsigned DEFAULT '1' COMMENT '状态(1:禁用,2:启用)',
  `sort` smallint(6) unsigned DEFAULT '0' COMMENT '排序权重',
  `desc` varchar(255) DEFAULT NULL COMMENT '备注说明',
  `create_by` bigint(11) unsigned DEFAULT '0' COMMENT '创建人',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_system_auth_title` (`title`) USING BTREE,
  KEY `index_system_auth_status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统权限表';

/*Data for the table `system_auth` */

/*Table structure for table `system_auth_node` */

CREATE TABLE `system_auth_node` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `auth` bigint(20) unsigned DEFAULT NULL COMMENT '角色ID',
  `node` varchar(200) DEFAULT NULL COMMENT '节点路径',
  PRIMARY KEY (`id`),
  KEY `index_system_auth_auth` (`auth`) USING BTREE,
  KEY `index_system_auth_node` (`node`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统角色与节点绑定';

/*Data for the table `system_auth_node` */

/*Table structure for table `system_config` */

CREATE TABLE `system_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL COMMENT '配置编码',
  `value` varchar(500) DEFAULT NULL COMMENT '配置值',
  PRIMARY KEY (`id`),
  KEY `index_system_config_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='系统参数配置';

/*Data for the table `system_config` */

insert  into `system_config`(`id`,`name`,`value`) values (1,'app_name','Admin'),(2,'site_name','Admin'),(3,'app_version','1.0'),(4,'site_copy','&copy;版权所有 2016-2019 燊永杨科技'),(5,'browser_icon','http://admin.tp.dev/static/upload/f47b8fe06e38ae99/08e8398da45583b9.png'),(6,'tongji_baidu_key',''),(7,'miitbeian','粤ICP备16091920号-1'),(8,'storage_type','local'),(9,'storage_local_exts','png,jpg,rar,doc,icon,mp4'),(10,'storage_qiniu_bucket',''),(11,'storage_qiniu_domain',''),(12,'storage_qiniu_access_key',''),(13,'storage_qiniu_secret_key',''),(14,'storage_oss_bucket','cuci'),(15,'storage_oss_endpoint','oss-cn-beijing.aliyuncs.com'),(16,'storage_oss_domain','cuci.oss-cn-beijing.aliyuncs.com'),(17,'storage_oss_keyid','用你自己的吧'),(18,'storage_oss_secret','用你自己的吧'),(19,'wechat_type','api'),(20,'wechat_token','999'),(21,'wechat_appid','wx999klkl999999999'),(22,'wechat_appsecret','99999999999999999999999999999999'),(23,'wechat_encodingaeskey','4545'),(24,'wechat_thr_appid',''),(25,'wechat_thr_appkey','');

/*Table structure for table `system_log` */

CREATE TABLE `system_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `ip` char(15) NOT NULL DEFAULT '' COMMENT '操作者IP地址',
  `node` char(200) NOT NULL DEFAULT '' COMMENT '当前操作节点',
  `username` varchar(32) NOT NULL DEFAULT '' COMMENT '操作人用户名',
  `action` varchar(200) NOT NULL DEFAULT '' COMMENT '操作行为',
  `content` text NOT NULL COMMENT '操作内容描述',
  `create_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COMMENT='系统操作日志表';

/*Data for the table `system_log` */

insert  into `system_log`(`id`,`ip`,`node`,`username`,`action`,`content`,`create_at`) values (1,'113.106.73.138','admin/login/index','admin','系统管理','用户登录系统成功','2018-03-13 15:42:15'),(2,'115.229.46.40','admin/login/index','admin','系统管理','用户登录系统成功','2018-03-13 15:43:48'),(3,'127.0.0.1','admin/login/index','admin','系统管理','用户登录系统成功','2018-03-20 11:15:34'),(4,'127.0.0.1','admin/config/index','admin','系统管理','系统参数配置成功','2018-03-20 11:17:54'),(5,'127.0.0.1','admin/login/out','admin','系统管理','用户退出系统成功','2018-03-20 12:33:25'),(6,'127.0.0.1','admin/login/index','admin','系统管理','用户登录系统成功','2018-03-20 14:31:54'),(7,'127.0.0.1','admin/config/index','admin','系统管理','系统参数配置成功','2018-03-20 14:32:12'),(8,'127.0.0.1','admin/config/index','admin','系统管理','系统参数配置成功','2018-03-20 14:32:29'),(9,'127.0.0.1','admin/login/out','admin','系统管理','用户退出系统成功','2018-03-20 14:33:26'),(10,'127.0.0.1','admin/login/index','admin','系统管理','用户登录系统成功','2018-03-20 14:33:45'),(11,'127.0.0.1','admin/login/index','admin','系统管理','用户登录系统成功','2018-03-20 14:44:46'),(12,'127.0.0.1','admin/login/index','admin','系统管理','用户登录系统成功','2018-03-20 15:15:00'),(13,'127.0.0.1','admin/config/index','admin','系统管理','系统参数配置成功','2018-03-20 15:19:03'),(14,'127.0.0.1','admin/login/index','admin','系统管理','用户登录系统成功','2019-01-24 20:44:07'),(15,'127.0.0.1','wechat/config/index','admin','微信管理','修改微信接口参数成功','2019-01-24 22:24:28');

/*Table structure for table `system_menu` */

CREATE TABLE `system_menu` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pid` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '名称',
  `node` varchar(200) NOT NULL DEFAULT '' COMMENT '节点代码',
  `icon` varchar(100) NOT NULL DEFAULT '' COMMENT '菜单图标',
  `url` varchar(400) NOT NULL DEFAULT '' COMMENT '链接',
  `params` varchar(500) DEFAULT '' COMMENT '链接参数',
  `target` varchar(20) NOT NULL DEFAULT '_self' COMMENT '链接打开方式',
  `sort` int(11) unsigned DEFAULT '0' COMMENT '菜单排序',
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态(0:禁用,1:启用)',
  `create_by` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `index_system_menu_node` (`node`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8 COMMENT='系统菜单表';

/*Data for the table `system_menu` */

insert  into `system_menu`(`id`,`pid`,`title`,`node`,`icon`,`url`,`params`,`target`,`sort`,`status`,`create_by`,`create_at`) values (1,0,'系统设置','','','#','','_self',9000,1,10000,'2018-01-19 15:27:00'),(2,10,'后台菜单','','fa fa-leaf','admin/menu/index','','_self',10,1,10000,'2018-01-19 15:27:17'),(3,10,'系统参数','','fa fa-modx','admin/config/index','','_self',20,1,10000,'2018-01-19 15:27:57'),(4,11,'访问授权','','fa fa-group','admin/auth/index','','_self',20,1,10000,'2018-01-22 11:13:02'),(5,11,'用户管理','','fa fa-user','admin/user/index','','_self',10,1,0,'2018-01-23 12:15:12'),(6,11,'访问节点','','fa fa-fort-awesome','admin/node/index','','_self',30,1,0,'2018-01-23 12:36:54'),(7,0,'后台首页','','','admin/index/main','','_self',1000,1,0,'2018-01-23 13:42:30'),(8,16,'系统日志','','fa fa-code','admin/log/index','','_self',10,1,0,'2018-01-24 13:52:58'),(9,10,'文件存储','','fa fa-stop-circle','admin/config/file','','_self',30,1,0,'2018-01-25 10:54:28'),(10,1,'系统管理','','fa fa-scribd','#','','_self',200,1,0,'2018-01-25 18:14:28'),(11,1,'访问权限','','fa fa-anchor','#','','_self',300,1,0,'2018-01-25 18:15:08'),(16,1,'日志管理','','fa fa-hashtag','#','','_self',400,1,0,'2018-02-10 16:31:15'),(17,0,'微信管理','','','#','','_self',8000,1,0,'2018-03-06 14:42:49'),(18,17,'公众号配置','','fa fa-cogs','#','','_self',0,1,0,'2018-03-06 14:43:05'),(19,18,'微信授权绑定','','fa fa-cog','wechat/config/index','','_self',0,1,0,'2018-03-06 14:43:26'),(20,18,'关注默认回复','','fa fa-comment-o','wechat/keys/subscribe','','_self',0,1,0,'2018-03-06 14:44:45'),(21,18,'无反馈默认回复','','fa fa-commenting','wechat/keys/defaults','','_self',0,1,0,'2018-03-06 14:45:55'),(22,18,'微信关键字管理','','fa fa-hashtag','wechat/keys/index','','_self',0,1,0,'2018-03-06 14:46:23'),(23,17,'微信服务定制','','fa fa-cubes','#','','_self',0,1,0,'2018-03-06 14:47:11'),(24,23,'微信菜单管理','','fa fa-gg-circle','wechat/menu/index','','_self',0,1,0,'2018-03-06 14:47:39'),(25,23,'微信图文管理','','fa fa-map-o','wechat/news/index','','_self',0,1,0,'2018-03-06 14:48:14'),(26,17,'微信粉丝管理','','fa fa-user','#','','_self',0,1,0,'2018-03-06 14:48:33'),(27,26,'微信粉丝列表','','fa fa-users','wechat/fans/index','','_self',20,1,0,'2018-03-06 14:49:04'),(28,26,'微信黑名单管理','','fa fa-user-times','wechat/block/index','','_self',30,1,0,'2018-03-06 14:49:22'),(29,26,'微信标签管理','','fa fa-tags','wechat/tags/index','','_self',10,1,0,'2018-03-06 14:49:39'),(30,0,'任务管理','','','#','','_self',99999,1,0,'2019-01-24 21:17:01'),(31,30,'分享任务管理','','fa fa-credit-card-alt','#','','_self',0,1,0,'2019-01-24 21:17:23'),(32,31,'任务列表','','fa fa-automobile','admin/task/index','','_self',0,1,0,'2019-01-24 21:18:47');

/*Table structure for table `system_node` */

CREATE TABLE `system_node` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `node` varchar(100) DEFAULT NULL COMMENT '节点代码',
  `title` varchar(500) DEFAULT NULL COMMENT '节点标题',
  `is_menu` tinyint(1) unsigned DEFAULT '0' COMMENT '是否可设置为菜单',
  `is_auth` tinyint(1) unsigned DEFAULT '1' COMMENT '是否启动RBAC权限控制',
  `is_login` tinyint(1) unsigned DEFAULT '1' COMMENT '是否启动登录控制',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `index_system_node_node` (`node`)
) ENGINE=InnoDB AUTO_INCREMENT=241 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='系统节点表';

/*Data for the table `system_node` */

insert  into `system_node`(`id`,`node`,`title`,`is_menu`,`is_auth`,`is_login`,`create_at`) values (1,'admin','系统管理',0,1,1,'2018-01-23 12:39:13'),(2,'admin/auth','权限管理',0,1,1,'2018-01-23 12:39:14'),(3,'admin/auth/index','权限列表',1,1,1,'2018-01-23 12:39:14'),(4,'admin/auth/apply','访问授权',0,1,1,'2018-01-23 12:39:19'),(5,'admin/auth/add','添加权限',0,1,1,'2018-01-23 12:39:22'),(6,'admin/auth/edit','编辑权限',0,1,1,'2018-01-23 12:39:23'),(7,'admin/auth/forbid','禁用权限',0,1,1,'2018-01-23 12:39:23'),(8,'admin/auth/resume','启用权限',0,1,1,'2018-01-23 12:39:24'),(9,'admin/auth/del','删除权限',0,1,1,'2018-01-23 12:39:25'),(10,'admin/config/index','系统参数',1,1,1,'2018-01-23 12:39:25'),(11,'admin/config/file','文件存储',0,1,1,'2018-01-23 12:39:26'),(12,'admin/config/sms','短信接口',0,1,1,'2018-01-23 12:39:28'),(13,'admin/log/index','日志记录',1,1,1,'2018-01-23 12:39:28'),(14,'admin/log/sms','短信记录',0,1,1,'2018-01-23 12:39:29'),(15,'admin/log/del','日志删除',0,1,1,'2018-01-23 12:39:29'),(16,'admin/menu/index','系统菜单列表',1,1,1,'2018-01-23 12:39:31'),(17,'admin/menu/add','添加系统菜单',0,1,1,'2018-01-23 12:39:31'),(18,'admin/menu/edit','编辑系统菜单',0,1,1,'2018-01-23 12:39:32'),(19,'admin/menu/del','删除系统菜单',0,1,1,'2018-01-23 12:39:33'),(20,'admin/menu/forbid','禁用系统菜单',0,1,1,'2018-01-23 12:39:33'),(21,'admin/menu/resume','启用系统菜单',0,1,1,'2018-01-23 12:39:34'),(22,'admin/node/index','系统节点列表',1,1,1,'2018-01-23 12:39:36'),(23,'admin/node/save','保存节点信息',0,1,1,'2018-01-23 12:39:36'),(24,'admin/user/index','系统用户列表',1,1,1,'2018-01-23 12:39:37'),(25,'admin/user/auth','用户授权操作',0,1,1,'2018-01-23 12:39:38'),(26,'admin/user/add','添加系统用户',0,1,1,'2018-01-23 12:39:39'),(27,'admin/user/edit','编辑系统用户',0,1,1,'2018-01-23 12:39:39'),(28,'admin/user/pass','修改用户密码',0,1,1,'2018-01-23 12:39:40'),(29,'admin/user/del','删除系统用户',0,1,1,'2018-01-23 12:39:41'),(30,'admin/user/forbid','禁用系统用户',0,1,1,'2018-01-23 12:39:41'),(31,'admin/user/resume','启用系统用户',0,1,1,'2018-01-23 12:39:42'),(32,'admin/config','系统配置',0,1,1,'2018-01-23 13:34:37'),(33,'admin/log','日志管理',0,1,1,'2018-01-23 13:34:48'),(34,'admin/menu','系统菜单管理',0,1,1,'2018-01-23 13:34:58'),(35,'admin/node','系统节点管理',0,1,1,'2018-01-23 13:35:17'),(36,'admin/user','系统用户管理',0,1,1,'2018-01-23 13:35:26'),(37,'wechat','微信管理',0,1,1,'2018-02-06 11:53:21'),(38,'wechat/config','公众号对接',0,1,1,'2018-02-06 11:53:32'),(39,'wechat/config/index','公众号对接',1,1,1,'2018-02-06 11:53:32'),(45,'wechat/block','黑名单',0,1,1,'2018-03-06 14:37:37'),(46,'wechat/block/index','黑名单列表',1,1,1,'2018-03-06 14:37:47'),(47,'wechat/block/backdel','移出黑名单',0,1,1,'2018-03-06 14:37:49'),(48,'wechat/fans','粉丝管理',0,1,1,'2018-03-06 14:38:06'),(49,'wechat/fans/index','粉丝列表',1,1,1,'2018-03-06 14:38:25'),(50,'wechat/fans/backadd','移入黑名单',0,1,1,'2018-03-06 14:38:35'),(51,'wechat/fans/tagset','标签设置',0,1,1,'2018-03-06 14:38:36'),(52,'wechat/fans/tagadd','添加标签',0,1,1,'2018-03-06 14:38:37'),(53,'wechat/fans/tagdel','删除标签',0,1,1,'2018-03-06 14:38:38'),(54,'wechat/fans/sync','同步粉丝',0,1,1,'2018-03-06 14:38:38'),(55,'wechat/keys','关键字管理',0,1,1,'2018-03-06 14:39:21'),(56,'wechat/keys/index','关键字列表',1,1,1,'2018-03-06 14:39:25'),(57,'wechat/keys/add','添加关键字',0,1,1,'2018-03-06 14:39:27'),(58,'wechat/keys/edit','编辑关键字',0,1,1,'2018-03-06 14:39:28'),(59,'wechat/keys/del','删除关键字',0,1,1,'2018-03-06 14:39:42'),(60,'wechat/keys/forbid','禁用关键字',0,1,1,'2018-03-06 14:39:55'),(61,'wechat/keys/resume','启用关键字',0,1,1,'2018-03-06 14:40:01'),(62,'wechat/keys/subscribe','关注默认回复',1,1,1,'2018-03-06 14:40:12'),(63,'wechat/keys/defaults','无反馈默认回复',1,1,1,'2018-03-06 14:40:27'),(64,'wechat/menu','微信菜单管理',0,1,1,'2018-03-06 14:40:37'),(65,'wechat/menu/index','微信菜单管理',1,1,1,'2018-03-06 14:40:41'),(66,'wechat/menu/edit','编辑发布菜单',0,1,1,'2018-03-06 14:40:53'),(67,'wechat/menu/cancel','取消发布菜单',0,1,1,'2018-03-06 14:41:01'),(68,'wechat/news','图文内容管理',0,1,1,'2018-03-06 14:41:13'),(69,'wechat/news/index','图文内容管理',1,1,1,'2018-03-06 14:41:20'),(70,'wechat/news/select','图文选择器',0,1,1,'2018-03-06 14:41:22'),(71,'wechat/news/image','图文选择器',0,1,1,'2018-03-06 14:41:22'),(72,'wechat/news/add','添加图文',0,1,1,'2018-03-06 14:41:23'),(73,'wechat/news/del','删除图文',0,1,1,'2018-03-06 14:41:23'),(74,'wechat/news/push','推荐图文',0,1,1,'2018-03-06 14:41:24'),(75,'wechat/news/edit','编辑图文',0,1,1,'2018-03-06 14:41:25'),(76,'wechat/tags','标签管理',0,1,1,'2018-03-06 14:42:06'),(77,'wechat/tags/index','标签列表',1,1,1,'2018-03-06 14:42:09'),(78,'wechat/tags/add','添加标签',0,1,1,'2018-03-06 14:42:14'),(79,'wechat/tags/edit','编辑标签',0,1,1,'2018-03-06 14:42:17'),(80,'wechat/tags/del','删除标签',0,1,1,'2018-03-06 14:42:20'),(81,'wechat/tags/sync','同步标签',0,1,1,'2018-03-06 14:42:23'),(229,'admin/node/clear','清理无效记录',0,1,1,'2018-03-09 12:24:31'),(230,'admin/menu/ttt','',0,0,0,'2018-03-20 11:43:39'),(231,'admin/index/index','',1,1,1,'2019-01-02 20:06:38'),(232,'admin/index/main','',1,1,1,'2019-01-02 20:06:38'),(233,'admin/index/pass','',1,1,1,'2019-01-02 20:06:38'),(234,'admin/index/info','',1,1,1,'2019-01-02 20:06:38'),(235,'admin/index','',0,1,1,'2019-01-24 21:18:00'),(236,'admin/task','任务管理',0,1,1,'2019-01-24 21:18:04'),(237,'admin/task/index','任务列表',1,1,1,'2019-01-24 21:18:09'),(238,'admin/task/add','添加任务',0,1,1,'2019-01-24 21:18:14'),(239,'admin/task/edit','编辑任务',0,1,1,'2019-01-24 21:18:17'),(240,'admin/task/del','删除任务',0,1,1,'2019-01-24 21:18:21');

/*Table structure for table `system_sequence` */

CREATE TABLE `system_sequence` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) DEFAULT NULL COMMENT '序号类型',
  `sequence` char(50) NOT NULL COMMENT '序号值',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_system_sequence_unique` (`type`,`sequence`) USING BTREE,
  KEY `index_system_sequence_type` (`type`),
  KEY `index_system_sequence_number` (`sequence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='系统序号表';

/*Data for the table `system_sequence` */

/*Table structure for table `system_user` */

CREATE TABLE `system_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户登录名',
  `password` char(32) NOT NULL DEFAULT '' COMMENT '用户登录密码',
  `qq` varchar(16) DEFAULT NULL COMMENT '联系QQ',
  `mail` varchar(32) DEFAULT NULL COMMENT '联系邮箱',
  `phone` varchar(16) DEFAULT NULL COMMENT '联系手机号',
  `desc` varchar(255) DEFAULT '' COMMENT '备注说明',
  `login_num` bigint(20) unsigned DEFAULT '0' COMMENT '登录次数',
  `login_at` datetime DEFAULT NULL,
  `status` tinyint(1) unsigned NOT NULL DEFAULT '1' COMMENT '状态(0:禁用,1:启用)',
  `authorize` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) unsigned DEFAULT '0' COMMENT '删除状态(1:删除,0:未删)',
  `create_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建人',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_system_user_username` (`username`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=10001 DEFAULT CHARSET=utf8 COMMENT='系统用户表';

/*Data for the table `system_user` */

insert  into `system_user`(`id`,`username`,`password`,`qq`,`mail`,`phone`,`desc`,`login_num`,`login_at`,`status`,`authorize`,`is_deleted`,`create_by`,`create_at`) values (10000,'admin','21232f297a57a5a743894a0e4a801fc3','','','','',22415,'2019-01-24 20:44:07',1,'1',0,NULL,'2015-11-13 15:14:22');

/*Table structure for table `wechat_fans` */

CREATE TABLE `wechat_fans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appid` char(50) DEFAULT '' COMMENT '公众号Appid',
  `unionid` char(100) DEFAULT '' COMMENT 'unionid',
  `openid` char(100) DEFAULT '' COMMENT '用户的标识,对当前公众号唯一',
  `spread_openid` char(100) DEFAULT '' COMMENT '推荐人OPENID',
  `spread_at` datetime DEFAULT NULL COMMENT '推荐时间',
  `tagid_list` varchar(100) DEFAULT '' COMMENT '标签id',
  `is_black` tinyint(1) unsigned DEFAULT '0' COMMENT '是否为黑名单用户',
  `subscribe` tinyint(1) unsigned DEFAULT '0' COMMENT '用户是否关注该公众号(0:未关注, 1:已关注)',
  `nickname` varchar(200) DEFAULT '' COMMENT '用户的昵称',
  `sex` tinyint(1) unsigned DEFAULT NULL COMMENT '用户的性别,值为1时是男性,值为2时是女性,值为0时是未知',
  `country` varchar(50) DEFAULT '' COMMENT '用户所在国家',
  `province` varchar(50) DEFAULT '' COMMENT '用户所在省份',
  `city` varchar(50) DEFAULT '' COMMENT '用户所在城市',
  `language` varchar(50) DEFAULT '' COMMENT '用户的语言,简体中文为zh_CN',
  `headimgurl` varchar(500) DEFAULT '' COMMENT '用户头像',
  `subscribe_time` bigint(20) unsigned DEFAULT '0' COMMENT '用户关注时间',
  `subscribe_at` datetime DEFAULT NULL COMMENT '关注时间',
  `remark` varchar(50) DEFAULT '' COMMENT '备注',
  `expires_in` bigint(20) unsigned DEFAULT '0' COMMENT '有效时间',
  `refresh_token` varchar(200) DEFAULT '' COMMENT '刷新token',
  `access_token` varchar(200) DEFAULT '' COMMENT '访问token',
  `subscribe_scene` varchar(200) DEFAULT '' COMMENT '扫码关注场景',
  `qr_scene` varchar(100) DEFAULT '' COMMENT '二维码场景值',
  `qr_scene_str` varchar(200) DEFAULT '' COMMENT '二维码场景内容',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `index_wechat_fans_spread_openid` (`spread_openid`) USING BTREE,
  KEY `index_wechat_fans_openid` (`openid`) USING BTREE,
  KEY `index_wechat_fans_unionid` (`unionid`),
  KEY `index_wechat_fans_is_back` (`is_black`),
  KEY `index_wechat_fans_subscribe` (`subscribe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信粉丝';

/*Data for the table `wechat_fans` */

/*Table structure for table `wechat_fans_tags` */

CREATE TABLE `wechat_fans_tags` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT COMMENT '标签ID',
  `appid` char(50) DEFAULT NULL COMMENT '公众号APPID',
  `name` varchar(35) DEFAULT NULL COMMENT '标签名称',
  `count` int(11) unsigned DEFAULT NULL COMMENT '总数',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建日期',
  KEY `index_wechat_fans_tags_id` (`id`) USING BTREE,
  KEY `index_wechat_fans_tags_appid` (`appid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信会员标签';

/*Data for the table `wechat_fans_tags` */

/*Table structure for table `wechat_keys` */

CREATE TABLE `wechat_keys` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `appid` char(100) DEFAULT '' COMMENT '公众号APPID',
  `type` varchar(20) DEFAULT '' COMMENT '类型，text 文件消息，image 图片消息，news 图文消息',
  `keys` varchar(100) DEFAULT NULL COMMENT '关键字',
  `content` text COMMENT '文本内容',
  `image_url` varchar(255) DEFAULT '' COMMENT '图片链接',
  `voice_url` varchar(255) DEFAULT '' COMMENT '语音链接',
  `music_title` varchar(100) DEFAULT '' COMMENT '音乐标题',
  `music_url` varchar(255) DEFAULT '' COMMENT '音乐链接',
  `music_image` varchar(255) DEFAULT '' COMMENT '音乐缩略图链接',
  `music_desc` varchar(255) DEFAULT '' COMMENT '音乐描述',
  `video_title` varchar(100) DEFAULT '' COMMENT '视频标题',
  `video_url` varchar(255) DEFAULT '' COMMENT '视频URL',
  `video_desc` varchar(255) DEFAULT '' COMMENT '视频描述',
  `news_id` bigint(20) unsigned DEFAULT NULL COMMENT '图文ID',
  `sort` bigint(20) unsigned DEFAULT '0' COMMENT '排序字段',
  `status` tinyint(1) unsigned DEFAULT '1' COMMENT '0 禁用，1 启用',
  `create_by` bigint(20) unsigned DEFAULT NULL COMMENT '创建人',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `index_wechat_keys_appid` (`appid`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='微信关键字';

/*Data for the table `wechat_keys` */

insert  into `wechat_keys`(`id`,`appid`,`type`,`keys`,`content`,`image_url`,`voice_url`,`music_title`,`music_url`,`music_image`,`music_desc`,`video_title`,`video_url`,`video_desc`,`news_id`,`sort`,`status`,`create_by`,`create_at`) values (1,'','text','商品','啦啦啦','http://plugs.ctolog.com/theme/default/img/image.png','','音乐标题','','http://plugs.ctolog.com/theme/default/img/image.png','音乐描述','视频标题','','视频描述',0,0,1,NULL,'2018-03-20 11:59:20');

/*Table structure for table `wechat_menu` */

CREATE TABLE `wechat_menu` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `index` bigint(20) DEFAULT NULL,
  `pindex` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '父id',
  `type` varchar(24) NOT NULL DEFAULT '' COMMENT '菜单类型 null主菜单 link链接 keys关键字',
  `name` varchar(256) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `content` text NOT NULL COMMENT '文字内容',
  `sort` bigint(20) unsigned DEFAULT '0' COMMENT '排序',
  `status` tinyint(1) unsigned DEFAULT '1' COMMENT '状态(0禁用1启用)',
  `create_by` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '创建人',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `index_wechat_menu_pindex` (`pindex`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信菜单配置';

/*Data for the table `wechat_menu` */

/*Table structure for table `wechat_news` */

CREATE TABLE `wechat_news` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `media_id` varchar(100) DEFAULT '' COMMENT '永久素材MediaID',
  `local_url` varchar(300) DEFAULT '' COMMENT '永久素材显示URL',
  `article_id` varchar(60) DEFAULT '' COMMENT '关联图文ID，用，号做分割',
  `is_deleted` tinyint(1) unsigned DEFAULT '0' COMMENT '是否删除',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `create_by` bigint(20) DEFAULT NULL COMMENT '创建人',
  PRIMARY KEY (`id`),
  KEY `index_wechat_news_artcle_id` (`article_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信图文表';

/*Data for the table `wechat_news` */

/*Table structure for table `wechat_news_article` */

CREATE TABLE `wechat_news_article` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(50) DEFAULT '' COMMENT '素材标题',
  `local_url` varchar(300) DEFAULT '' COMMENT '永久素材显示URL',
  `show_cover_pic` tinyint(4) unsigned DEFAULT '0' COMMENT '是否显示封面 0不显示，1 显示',
  `author` varchar(20) DEFAULT '' COMMENT '作者',
  `digest` varchar(300) DEFAULT '' COMMENT '摘要内容',
  `content` longtext COMMENT '图文内容',
  `content_source_url` varchar(200) DEFAULT '' COMMENT '图文消息原文地址',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `create_by` bigint(20) DEFAULT NULL COMMENT '创建人',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信素材表';

/*Data for the table `wechat_news_article` */

/*Table structure for table `wechat_news_image` */

CREATE TABLE `wechat_news_image` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `md5` varchar(32) DEFAULT '' COMMENT '文件md5',
  `local_url` varchar(300) DEFAULT '' COMMENT '本地文件链接',
  `media_url` varchar(300) DEFAULT '' COMMENT '远程图片链接',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `index_wechat_news_image_md5` (`md5`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信服务器图片';

/*Data for the table `wechat_news_image` */

/*Table structure for table `wechat_news_media` */

CREATE TABLE `wechat_news_media` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `appid` varchar(100) DEFAULT '' COMMENT '公众号ID',
  `md5` varchar(32) DEFAULT '' COMMENT '文件md5',
  `type` varchar(20) DEFAULT '' COMMENT '媒体类型',
  `media_id` varchar(100) DEFAULT '' COMMENT '永久素材MediaID',
  `local_url` varchar(300) DEFAULT '' COMMENT '本地文件链接',
  `media_url` varchar(300) DEFAULT '' COMMENT '远程图片链接',
  `create_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='微信素材表';

/*Data for the table `wechat_news_media` */

/*Table structure for table `xin_share_pv_uv` */

CREATE TABLE `xin_share_pv_uv` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `t_id` int(11) NOT NULL DEFAULT '0',
  `create_time` int(10) NOT NULL DEFAULT '0',
  `ip` varchar(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

/*Data for the table `xin_share_pv_uv` */

insert  into `xin_share_pv_uv`(`id`,`t_id`,`create_time`,`ip`) values (1,4,1548337425,'127.0.0.1'),(2,4,1548337595,'127.0.0.1');

/*Table structure for table `xin_share_task` */

CREATE TABLE `xin_share_task` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL DEFAULT '' COMMENT '标题',
  `desc` varchar(255) NOT NULL DEFAULT '' COMMENT '描述',
  `img_url` varchar(255) NOT NULL DEFAULT '',
  `create_at` datetime DEFAULT NULL,
  `update_time` int(10) DEFAULT '0',
  `link` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

/*Data for the table `xin_share_task` */

insert  into `xin_share_task`(`id`,`title`,`desc`,`img_url`,`create_at`,`update_time`,`link`) values (4,'测试','111','http://x_share.dev/static/upload/a1bfb1c1fa3d4267/6ea98f580ea1a17c.png','2019-01-24 21:15:57',1548337527,'http://baidu.com');

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
