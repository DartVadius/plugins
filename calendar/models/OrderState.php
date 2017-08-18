<?php

/**
 * Description of Calendar_Model_OrderState
 *
 * @author DartVadius
 */
class Calendar_Model_OrderState extends Zend_Db_Table_Abstract {

    protected $_name = 'order_state';

    public function getActiveStatuses() {
        $select = $this->select()->setIntegrityCheck(FALSE)
                ->from($this->_name, ['id', 'name', 'system_name'])
                ->where('deleted =?', 0);
        $result = $this->fetchAll($select);
        if ($result->count() > 0) {
            return $result->toArray();
        }
        return FALSE;
    }

}
