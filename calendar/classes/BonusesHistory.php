<?php

class Calendar_BonusesHistory {
    
    protected $_contact_type;
    protected $_contact_id;
    protected $_day_from_str;
    protected $_day_to_str;
    
    public function __construct($contact_type = null, $contact_id = null, $day_from_str = null, $day_to_str = null) {
        
        if (!is_null($contact_type) && !is_null($contact_id) && !is_null($day_from_str) && !is_null($day_to_str)) {
            $this->_contact_type = $contact_type;
            $this->_contact_id = $contact_id;
            $this->_day_from_str = $day_from_str;
            $this->_day_to_str = $day_to_str;
        }
        
    }
    
    public function getBonusesHistory() {
        
        $bonusHistoryModel = new Shop_Model_BonusHistory();
        
        $contact_id = $this->_contact_id;
        
        $bonus_history = $bonusHistoryModel->getByManagerOrClientIds($this->_contact_type, $contact_id, $this->_day_from_str, $this->_day_to_str);
        
        if (!empty($bonus_history)) {
            foreach($bonus_history[$contact_id] as $id => $hist) {
                $create_date = explode(' ', $hist['create_date']);
                $date_ar = explode('-', $create_date[0]);
                $time_ar = explode(':', $create_date[1]);
                $bonus_history[$contact_id][$id]['year'] = $date_ar[0];
                $bonus_history[$contact_id][$id]['month'] = $date_ar[1];
                $bonus_history[$contact_id][$id]['day'] = $date_ar[2];
                $bonus_history[$contact_id][$id]['hour'] = $time_ar[0];
                $bonus_history[$contact_id][$id]['minute'] = $time_ar[1];
                $bonus_history[$contact_id][$id]['second'] = $time_ar[2];
            }
            return $bonus_history[$contact_id];
        } else {
            return null;
        }
        
    }
    
    public function createForDay($bonus_history) {
        
        if (!empty($bonus_history)) {
            foreach($bonus_history as $id => $value) {
                
                $date = new Zend_Date($value['create_date'], 'yyyy-MM-dd HH:mm:ss');
                $bonus_history[$id]['top'] = $date->toString('HH')*60 + $date->toString('mm');
            }
        }
        return $bonus_history;
    }
    
}