<?php

class Calendar_Model_TasksAssistants extends Zend_Db_Table_Abstract
{
    protected $_name = 'crm_calendar_tasks_assistants';

    public function getByTaskId($task_id)
    {
    	if ($r = $this->fetchAll("task_id = $task_id")) {
    		$assit = $r->toArray();
    		$ids = array();

    		foreach ($assit as $a) {
    			$ids[] = $a['contact_id'];
    		}

    		$contactModel = new Application_Model_DbTable_Contact();
        	return $contactModel->find($ids)->toArray();
    	}
    }
    
    public function getByTasksIds($ids = null) {
        $sql = $this->select()
                    ->setIntegrityCheck(false)
                    ->from(array('ta' => $this->_name))
                    ->joinLeft(array('c' => 'wa_contact'), 'ta.contact_id = c.id', array('name'));
        if (!is_null($ids)) {
            if (is_array($ids)) {
                $ids = implode(',', $ids);
            }
            $sql->where("task_id IN ($ids)");
        }
        $res = $this->fetchAll($sql)->toArray();
        return $res;
    }
}