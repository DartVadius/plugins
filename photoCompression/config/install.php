<?php

// Get db adapter
$db = new Zend_Db_Table();
$adapter = $db->getAdapter();

// Start a transaction explicitly
$adapter->beginTransaction();

try {
    $adapter->query("CREATE TABLE IF NOT EXISTS `photo_compression` (
                     `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                     `path` VARCHAR(255) NOT NULL,
                     UNIQUE INDEX `path_UNIQUE` (`path` ASC),
                     PRIMARY KEY (`id`),
                     UNIQUE INDEX `id_UNIQUE` (`id` ASC))
                     ENGINE = InnoDB;");
    $adapter->commit();
    return true;
} catch (Exception $e) {
    $adapter->rollBack();
    $adapter->query('DROP TABLE IF EXISTS photo_compression');
    return $e->getMessage();
}