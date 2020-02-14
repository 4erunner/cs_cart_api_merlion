<?php 
$schema = array();
$schema['op'] = array(
    "install" => array(
        "CREATE TABLE IF NOT EXISTS `?:backup_<table>` LIKE `?:<table>`",
        "TRUNCATE `?:<table>`",
        "INSERT `?:<table>` SELECT * FROM `?:backup_<table>`",
        "DROP TABLE IF EXISTS `?:backup_<table>`",
    ),
    "uninstall" => array(
        "CREATE TABLE IF NOT EXISTS `?:backup_<table>` LIKE `?:<table>`",
        "TRUNCATE `?:backup_<table>`",
        "INSERT `?:backup_<table>` SELECT * FROM `?:<table>`",
        "DROP TABLE IF EXISTS `?:<table>`",
    ),
);
$schema['table'] = array();
$schema['table']['api_merlion_groups'] = "
            CREATE TABLE IF NOT EXISTS `?:api_merlion_groups` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `group_id` VARCHAR(12) NULL DEFAULT NULL,
                `group_pid` VARCHAR(12) NULL DEFAULT NULL,
                `name` VARCHAR(255) NULL DEFAULT NULL,
                `category_id` INT(11) NULL DEFAULT '0',
                `status` VARCHAR(2) NULL DEFAULT 'A',
                `comparison` TINYINT(4) NULL DEFAULT '0',
                `list_price` TINYINT(4) NULL DEFAULT '0',
                `partnumber_name` TINYINT(4) NULL DEFAULT '0',
                `check` TINYINT(4) NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                UNIQUE INDEX `group_id` (`group_id`),
                INDEX `group_pid` (`group_pid`),
                INDEX `catalog_id` (`category_id`),
                INDEX `status` (`status`),
                INDEX `list_price` (`list_price`),
                INDEX `partnumber_name` (`list_price`),
                INDEX `check` (`check`)
            )
            ENGINE=MyISAM DEFAULT CHARSET=utf8
";
$schema['table']['api_merlion_products'] = "
            CREATE TABLE IF NOT EXISTS `?:api_merlion_products` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `No` VARCHAR(50) NULL,
                `Source` VARCHAR(2) NULL DEFAULT 'M',
                `Name` VARCHAR(255) NULL DEFAULT '',
                `Brand` VARCHAR(255) NULL DEFAULT '',
                `Vendor_part` VARCHAR(255) NULL DEFAULT '',
                `Size` VARCHAR(255) NULL DEFAULT '',
                `EOL` TINYINT(1) NULL DEFAULT '0' COMMENT 'EOL � End of life',
                `Warranty` INT NULL DEFAULT '0' COMMENT '� �������',
                `Weight` DOUBLE NULL DEFAULT '0',
                `Volume` DOUBLE NULL DEFAULT '0',
                `Min_Packaged` INT NULL DEFAULT '0',
                `GroupName1` VARCHAR(50) NULL DEFAULT '',
                `GroupName2` VARCHAR(50) NULL DEFAULT '',
                `GroupName3` VARCHAR(50) NULL DEFAULT '',
                `GroupCode1` VARCHAR(50) NULL DEFAULT '',
                `GroupCode2` VARCHAR(50) NULL DEFAULT '',
                `GroupCode3` VARCHAR(50) NULL DEFAULT '',
                `IsBundle` TINYINT(1) NULL DEFAULT '0' COMMENT '����� � ��������',
                `ActionDesc` VARCHAR(255) NULL DEFAULT '',
                `ActionWWW` VARCHAR(255) NULL DEFAULT '',
                `ActionNumber` INT NULL DEFAULT '0',
                `Last_time_modified` DATETIME NULL ,
                `PriceClient` DOUBLE NULL DEFAULT '0' COMMENT 'USD �� �������� ������ ��������',
                `PriceClient_RG` DOUBLE NULL DEFAULT '0' COMMENT 'USD',
                `PriceClient_MSK` DOUBLE NULL DEFAULT '0' COMMENT 'USD',
                `AvailableClient` INT NULL DEFAULT '0' COMMENT '�� �������� ������ ��������',
                `AvailableClient_RG` INT NULL DEFAULT '0',
                `AvailableClient_MSK` INT NULL DEFAULT '0',
                `AvailableExpected` INT NULL DEFAULT '0',
                `AvailableExpectedNext` INT NULL DEFAULT '0',
                `DateExpectedNext` DATETIME NULL ,
                `RRP` DOUBLE NULL DEFAULT '0' COMMENT 'RUB',
                `RRP_Date` DATETIME NULL ,
                `PriceClientRUB` DOUBLE NULL DEFAULT '0' COMMENT 'RUB �� �������� ������ ��������',
                `PriceClientRUB_RG` DOUBLE NULL DEFAULT '0',
                `PriceClientRUB_MSK` DOUBLE NULL DEFAULT '0',
                `Online_Reserve` TINYINT(2) NULL DEFAULT '0',
                `ReserveCost` DOUBLE NULL DEFAULT '0',
                `Category` VARCHAR(255) NULL DEFAULT '',
                `Comparison` VARCHAR(1) NULL DEFAULT '',
                `PartnumberName` VARCHAR(1) NULL DEFAULT '',
                `Language` VARCHAR(4) NULL DEFAULT '',
                `Short description` TEXT NULL,
                `check` INT NULL DEFAULT '0',
                UNIQUE INDEX `No` (`No`),
                INDEX `check` (`check`),
                PRIMARY KEY (`id`)
            )
            COLLATE='utf8_general_ci' ENGINE=MyISAM DEFAULT CHARSET=utf8
";
$schema['table']['api_merlion_features'] = "
            CREATE TABLE IF NOT EXISTS `?:api_merlion_features` (
                `hash` VARCHAR(50)  NOT NULL,
                `No` VARCHAR(10) NULL DEFAULT NULL,
                `PropertyID` INT NULL DEFAULT '0',
                `PropertyName` VARCHAR(255) NULL DEFAULT NULL,
                `Value` VARCHAR(255) NULL DEFAULT NULL,
                `Sorting` INT NULL DEFAULT '0',
                `Last_time_modified` DATETIME NULL DEFAULT NULL,
                `check` INT NULL DEFAULT '0',
                PRIMARY KEY (`hash`),
                UNIQUE INDEX `hash` (`hash`),
                INDEX `No` (`No`),
                INDEX `Sorting` (`Sorting`),
                INDEX `check` (`check`)
            )
            COLLATE='utf8_general_ci' ENGINE=MyISAM DEFAULT CHARSET=utf8
";
$schema['table']['api_merlion_images'] = "
            CREATE TABLE IF NOT EXISTS `?:api_merlion_images` (
                `hash` VARCHAR(50) NOT NULL,
                `No` VARCHAR(10) NULL,
                `ViewType` VARCHAR(2) NULL,
                `SizeType` VARCHAR(2) NULL,
                `FileName` VARCHAR(255) NULL,
                `Created` DATETIME NULL,
                `Size` INT NULL DEFAULT '0',
                `Width` SMALLINT NULL DEFAULT '0',
                `Height` SMALLINT NULL DEFAULT '0',
                `check` INT NULL DEFAULT '0',
                PRIMARY KEY (`hash`),
                INDEX `No` (`No`),
                INDEX `SizeType` (`SizeType`),
                INDEX `Created` (`Created`),
                INDEX `check` (`check`)
            )
            COLLATE='utf8_general_ci' ENGINE=MyISAM DEFAULT CHARSET=utf8
";
$schema['table']['api_merlion_features_description'] = "
            CREATE TABLE IF NOT EXISTS `?:api_merlion_features_description` (
                `feature_id` INT NOT NULL AUTO_INCREMENT,
                `description` VARCHAR(255) NOT NULL,
                PRIMARY KEY (`feature_id`)
            )
            COLLATE='utf8_general_ci' ENGINE=MyISAM DEFAULT CHARSET=utf8
";

$schema['table']['api_merlion_features_group_description'] = "
            CREATE TABLE IF NOT EXISTS `?:api_merlion_features_group_description` (
                `group_id` INT NOT NULL AUTO_INCREMENT,
                `description` VARCHAR(255) NOT NULL,
                PRIMARY KEY (`group_id`)
            )
            COLLATE='utf8_general_ci' ENGINE=MyISAM DEFAULT CHARSET=utf8
";

$schema['table']['api_merlion_features_attached_group'] = "
            CREATE TABLE IF NOT EXISTS `?:api_merlion_features_attached_group` (
                `group_id` INT NOT NULL,
                `feature_id` INT NOT NULL,
                INDEX `group_id` (`group_id`),
                INDEX `feature_id` (`feature_id`)
            )
            COLLATE='utf8_general_ci' ENGINE=MyISAM DEFAULT CHARSET=utf8
";

$schema['table']['api_merlion_order_product'] = "
            CREATE TABLE IF NOT EXISTS `?:api_merlion_order_product` (
                `order_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
                `product_id` MEDIUMINT(8) UNSIGNED NOT NULL DEFAULT '0',
                `product_code` VARCHAR(32) NOT NULL DEFAULT '',
                `amount` SMALLINT(5) UNSIGNED NOT NULL DEFAULT '0',
                `api_merlion_order` VARCHAR(32) NOT NULL DEFAULT '',
                `order_date` DATETIME NULL DEFAULT NULL,
                `order_price` INT(11) NOT NULL DEFAULT '0',
                `order_available` INT(11) NOT NULL DEFAULT '0',
                `message` VARCHAR(255) NOT NULL DEFAULT '',
                `status` VARCHAR(2) NULL DEFAULT 'Z',
                PRIMARY KEY (`order_id`, `product_id`),
                INDEX `product_code` (`product_code`),
                INDEX `order_id` (`order_id`),
                INDEX `product_id` (`product_id`),
                INDEX `api_merlion_order` (`api_merlion_order`)
            )   
            COLLATE='utf8_general_ci' ENGINE=MyISAM DEFAULT CHARSET=utf8
";

$schema['table']['api_merlion_order_product'] = "
            CREATE TABLE IF NOT EXISTS `?:api_merlion_akkom_product` (
                `id` INT  AUTO_INCREMENT,
                `product_code` VARCHAR(32) NOT NULL DEFAULT '',
                PRIMARY KEY (`id`),
                INDEX `product_code` (`product_code`)
            )   
            COLLATE='utf8_general_ci' ENGINE=MyISAM DEFAULT CHARSET=utf8
";

return $schema;