<?php

/**
 * Description of Mail
 *
 * @author DartVadius
 */
class Calendar_Model_Mail extends Zend_Db_Table_Abstract {

    protected $_name = 'crm_statistics_mailer_send_log';

    const STATUS_OK = 4;
    const STATUS_ERROR = 6;

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
        $select = $this->select()->setIntegrityCheck(FALSE)
                
                ->from($this->_name, ['create_date', 'code'])
                ->joinLeft('wa_contact', 'wa_contact.id=crm_statistics_mailer_send_log.contact_id', ['name'])
                ->where("create_date BETWEEN '$from' AND '$to'")
                ->where('crm_statistics_mailer_send_log.status =?', self::STATUS_OK)
                ->where('NOT type =?', 'task_mail');
        if ($user_id) {
            $select->where('manager_id =?', $user_id);
        }
        $select->order("create_date");
        $result = $this->fetchAll($select);
        if ($result->count()) {
            return $result->toArray();
        }
        return FALSE;
    }
    
    /**
     * get archived mailer tasks data by period and client id
     * if user id not initialized - get all mailer tasks by period
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $user_id
     * @return boolean | array
     */
    public function getByPeriodClient($from, $to, $user_id = NULL) {
        $select = $this->select()->setIntegrityCheck(FALSE)
                
                ->from($this->_name, ['create_date', 'code'])
                ->joinLeft('wa_contact', 'wa_contact.id=crm_statistics_mailer_send_log.contact_id', ['name'])
                ->where("create_date BETWEEN '$from' AND '$to'")
                ->where('crm_statistics_mailer_send_log.status =?', self::STATUS_OK)
                ->where('NOT type =?', 'task_mail');
        if ($user_id) {
            $select->where('contact_id =?', $user_id);
        }
        $select->order("create_date");
        $result = $this->fetchAll($select);
        if ($result->count()) {
            return $result->toArray();
        }
        return FALSE;
    }

}
