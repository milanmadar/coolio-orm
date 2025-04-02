SET FOREIGN_KEY_CHECKS = 0;

UNLOCK TABLES;

DROP TABLE IF EXISTS `orm_third`;
CREATE TABLE `orm_third` (
     `id` int unsigned NOT NULL AUTO_INCREMENT,
     `fk_to_this` varchar(45) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
     PRIMARY KEY (`id`),
     KEY `idx_to_this` (`fk_to_this`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `orm_third` WRITE;
INSERT INTO `orm_third` VALUES (1,'third hali'),(2,'third bye');
UNLOCK TABLES;

-- -----------

DROP TABLE IF EXISTS `orm_other`;
CREATE TABLE `orm_other` (
     `id` int NOT NULL AUTO_INCREMENT,
     `fld_int` int NOT NULL,
     `title` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
     PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `orm_other` WRITE;
INSERT INTO `orm_other` VALUES (1,1,'first'),(2,2,'second');
UNLOCK TABLES;

-- ----------------------------------

DROP TABLE IF EXISTS `orm_test`;
CREATE TABLE `orm_test` (
    `id` int NOT NULL AUTO_INCREMENT,
    `fld_int` int DEFAULT NULL COMMENT 'an integer',
    `fld_tiny_int` tinyint(1) DEFAULT NULL COMMENT 'a tiny int',
    `fld_small_int` smallint DEFAULT NULL COMMENT 'a small int',
    `fld_medium_int` mediumint DEFAULT NULL COMMENT 'a medium int',
    `fld_float` float(8,2) DEFAULT NULL COMMENT 'a floating 8,2',
    `fld_double` double(8,2) DEFAULT NULL COMMENT 'a double 8,2',
    `fld_decimal` decimal(8,2) DEFAULT '1.23' COMMENT 'a decimal 8,2',
    `fld_char` char(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'a char 8',
    `fld_varchar` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'field''s "quoted" def val' COMMENT 'a varchar 25',
    `fld_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'a text',
    `fld_medium_text` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'a meidum text',
    `fld_json` json DEFAULT NULL COMMENT 'json data',
    `orm_other_id` int DEFAULT NULL,
    `orm_third_key` varchar(45) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_orm_other_id` (`orm_other_id`),
KEY `fk_orm_third_idx` (`orm_third_key`),
CONSTRAINT `fk_orm_other_id` FOREIGN KEY (`orm_other_id`) REFERENCES `orm_other` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
CONSTRAINT `fk_orm_third_key` FOREIGN KEY (`orm_third_key`) REFERENCES `orm_third` (`fk_to_this`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

LOCK TABLES `orm_test` WRITE;
INSERT INTO `orm_test` VALUES
   (1,1,2,3,4,1.10,1.10,1.10,'fgeabdhc','a varchar 0','a text 0','a mediumtext 0',NULL,NULL,NULL),
   (2,2,3,4,5,2.10,2.10,2.10,'hcbagfde','a varchar 1','a text 1','a mediumtext 1',NULL,NULL,'third hali'),
   (3,3,4,5,6,3.10,3.10,3.10,'cfahegdb','a varchar 2','a text 2','a mediumtext 2',NULL,NULL,NULL),
   (4,4,5,6,7,4.10,4.10,4.10,'agdbecfh','a varchar 3','a text 3','a mediumtext 3',NULL,NULL,NULL),
   (5,5,6,7,8,5.10,5.10,5.10,'fhdagcbe','a varchar 4','a text 4','a mediumtext 4',NULL,NULL,NULL),
   (6,6,7,8,9,6.10,6.10,6.10,'ecfhbadg','a varchar 5','a text 5','a mediumtext 5',NULL,NULL,NULL),
   (7,7,8,9,10,7.10,7.10,7.10,'ehcfbadg','a varchar 6','a text 6','a mediumtext 6',NULL,NULL,NULL),
   (8,8,9,10,11,8.10,8.10,8.10,'dfbagceh','a varchar 7','a text 7','a mediumtext 7',NULL,NULL,NULL),
   (9,9,10,11,12,9.10,9.10,9.10,'cbegdfha','a varchar 8','a text 8','a mediumtext 8',NULL,NULL,NULL),
   (10,10,11,12,13,10.10,10.10,10.10,'cagfehdb','a varchar 9','a text 9','a mediumtext 9',NULL,1,NULL);
UNLOCK TABLES;

SET FOREIGN_KEY_CHECKS = 1;
