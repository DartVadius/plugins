<?php

/**
 * Description of Calendar_Model_Orders
 *
 * @author DartVadius
 */
class Calendar_Model_Orders extends Zend_Db_Table_Abstract {

    protected $_name = 'shop_order';

    /**
     * get orders ID by period and manager id
     * if user id not initialized - get all orders by period
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $user_id
     * @return boolean | array
     */
    public function getByPeriodManager($from, $to, $user_id = NULL) {
        $session = new Zend_Session_Namespace('calendar_filter');

        if (empty($session->filter['order_status'])) {
            return FALSE;
        }

        $select = $this->select()->setIntegrityCheck(FALSE)
                ->from($this->_name, ['id', 'create_datetime'])
                ->where("create_datetime BETWEEN '$from' AND '$to'");

        if ($user_id) {
            $select->where('assigned_contact_id =?', $user_id);
        }
        $select->joinLeft('order_state_status', 'shop_order.id=order_state_status.order_id', ['state_id'])
                ->where('active=1');
        $count = 1;
        foreach ($session->filter['order_status'] as $key => $value) {
            if ($count === 1) {
                $select->where('order_state_status.state_id =?', $key);
            } else {
                $select->orWhere('order_state_status.state_id =?', $key);
            }
            $count++;
        }

        $select->group('shop_order.id')
                ->order("shop_order.create_datetime");
        $result = $this->fetchAll($select);
        if ($result->count()) {
            return $result->toArray();
        }
        return FALSE;
    }

    /**
     * get all client orders ID by period
     * 
     * @param type $from
     * @param type $to
     * @param type $user_id
     * @return boolean
     */
    public function getByPeriodClient($from, $to, $user_id) {

        $select = $this->select()->setIntegrityCheck(FALSE)
                ->from($this->_name, ['id', 'create_datetime'])
                ->where("create_datetime BETWEEN '$from' AND '$to'")
                ->where('contact_id =?', $user_id)
                ->order("id");
        $result = $this->fetchAll($select);
        if ($result->count()) {
            return $result->toArray();
        }
        return FALSE;
    }

}
