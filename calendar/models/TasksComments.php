<?php

class Calendar_Model_TasksComments extends Zend_Db_Table_Abstract
{

    protected $_name = 'crm_calendar_tasks_comments';
    
    public function getByTaskId($task_id) {
        
        $res = $this->fetchAll("task_id = $task_id")->toArray();
        
        return $res;
        
    }
    
    
}