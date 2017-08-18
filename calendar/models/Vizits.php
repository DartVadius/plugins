<?php

/**
 * Description of Calendar_Model_Vizits
 *
 * @author DartVadius
 */
class Calendar_Model_Vizits extends Zend_Db_Table_Abstract {

    protected $_name = 'crm_statistics_contact_visit_history';
    protected $_types = [
        'product' => ['product/view', 'reviews/add', 'product/page'],
        'category' => ['category/view']
    ];

    public function getVizitsToCalendar($from, $to, $user_id) {
        $select = $this->select()->setIntegrityCheck(FALSE)->from($this->_name, ['contact_id', 'datetime_stamp'])
                ->joinLeft('wa_contact', 'wa_contact.id=crm_statistics_contact_visit_history.contact_id', ['name'])
                ->where("datetime_stamp BETWEEN '$from' AND '$to'")
                ->where('contact_id =?', $user_id)
                ->group('convert (`datetime_stamp`, date)');
        $result = $this->fetchAll($select);

        if ($result->count()) {
            return $result->toArray();
        }
        return FALSE;
    }

    public function dayDetails($contact_id, $date) {
        $sql = $this->select()
                ->from($this->_name)
                ->where("contact_id=$contact_id")
                ->where("datetime_stamp BETWEEN 
                        STR_TO_DATE('$date 00:00:00', '%Y-%m-%d %H:%i:%s') 
                        AND STR_TO_DATE('$date 23:59:59', '%Y-%m-%d %H:%i:%s')")
                ->order('id');

        $vizits = $this->fetchAll($sql);
        if($vizits->count() > 0) {
            return $vizits->toArray();
        }
        return FALSE;
    }

}

//SELECT crm_statistics_contact_visit_history.*, wa_contact.name FROM crm_statistics_contact_visit_history
//LEFT JOIN wa_contact 
//ON crm_statistics_contact_visit_history.contact_id = wa_contact.id
//WHERE contact_id = 10 
//OR contact_id = 9
//GROUP BY convert (`datetime_stamp`, date), contact_id