<?php

// Get db adapter
$db = new Zend_Db_Table();
$adapter = $db->getAdapter();

// Start a transaction explicitly
$adapter->beginTransaction();
try {
    $adapter->query("CREATE TABLE IF NOT EXISTS `heybro_plugin` (
                    `code` VARCHAR(12) NOT NULL,
                    `order_id` INT(12) UNSIGNED NOT NULL,
                    `user_id` INT(12) UNSIGNED NOT NULL,
                    `product_id` INT(12) UNSIGNED NOT NULL,
                    `date` DATETIME NOT NULL,
                    `ready_img` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0',
                    PRIMARY KEY (`code`),
                    UNIQUE INDEX `id_UNIQUE` (`code` ASC))
                  ENGINE = MyISAM
                  DEFAULT CHARACTER SET = utf8;");
    $adapter->query("CREATE TABLE IF NOT EXISTS `heybro_product` (
                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `product_id` INT(10) UNSIGNED NOT NULL,
                    `product_name` VARCHAR(255) NOT NULL,
                    `product_type` VARCHAR(255) NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE INDEX `id_UNIQUE` (`id` ASC))
                  ENGINE = MyISAM
                  DEFAULT CHARACTER SET = utf8;");
    $adapter->commit();
    return true;
} catch (Exception $e) {
    $adapter->rollBack();
    $adapter->query('DROP TABLE IF EXISTS heybro_plugin');
    $adapter->query('DROP TABLE IF EXISTS heybro_product');
    return $e->getMessage();
}
if (!is_dir('cards')) {
    mkdir('cards', 0777);
}
copy(APPLICATION_PATH . '/plugins/frontend/heybro/template/start.png', "cards/start.png");