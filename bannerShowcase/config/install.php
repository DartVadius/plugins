<?php

// Get db adapter
$db = new Zend_Db_Table();
$adapter = $db->getAdapter();

// Start a transaction explicitly
$adapter->beginTransaction();

try {
    $adapter->query("CREATE TABLE IF NOT EXISTS `banner` (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(255) NOT NULL,
                    `url` VARCHAR(255) NOT NULL,
                    `image_name` VARCHAR(255) NOT NULL,
                    `category` VARCHAR(255) NULL DEFAULT NULL,
                    `product` VARCHAR(255) NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `id_UNIQUE` (`id` ASC),
                    UNIQUE INDEX `name` (`name` ASC))
                  ENGINE = MyISAM
                  DEFAULT CHARACTER SET = utf8;");
    $adapter->commit();
    return true;
} catch (Exception $e) {
    $adapter->rollBack();
    $adapter->query('DROP TABLE IF EXISTS banner');
    return $e->getMessage();
}