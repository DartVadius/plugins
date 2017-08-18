<?php

/**
 * Description of Calendar_Model_Mailer
 *
 * @author DartVadius
 */
class Calendar_Model_Mailer extends Zend_Db_Table_Abstract {

    protected $_name = 'crm_mailer_archive_projects';

    /**
     * get archived mailer tasks data by period and manager id
     * if user id not initialized - get all mailer tasks by period
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $user_id
     * @return boolean | array
     */
    public function getByPeriodManager($from, $to, $user_id = NULL) {
        $session = new Zend_Session_Namespace('calendar_user_filter');
        if (!empty($session->user_id)) {
            $user_id = $session->user_id;
        }
        
        $select = $this->select()->setIntegrityCheck(FALSE)
                ->from($this->_name, ['id', 'last_send', 'name'])
                ->where("last_send BETWEEN '$from' AND '$to'");
        if ($user_id) {
            $select->where('contact_id =?', $user_id);
        }
        $select->order("last_send");
        $result = $this->fetchAll($select);
        if ($result->count()) {
            return $result->toArray();
        }
        return FALSE;
    }

}
