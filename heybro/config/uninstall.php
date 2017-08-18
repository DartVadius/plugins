<?php

$db = new Zend_Db_Table();
$adapter = $db->getAdapter();

$adapter->query('DROP TABLE IF EXISTS heybro_plugin');
$adapter->query('DROP TABLE IF EXISTS heybro_product');

return 'success';
