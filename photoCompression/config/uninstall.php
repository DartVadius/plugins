<?php

$db = new Zend_Db_Table();
$adapter = $db->getAdapter();

$adapter->query('DROP TABLE IF EXISTS photo_compression');

return 'success';