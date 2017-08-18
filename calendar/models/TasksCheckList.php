<?php

class Calendar_Model_TasksCheckList extends Zend_Db_Table_Abstract
{

    protected $_name = 'crm_calendar_tasks_check_list';

    public function getByTaskId($task_id)
    {
    	$sql = $this->select()->where("task_id = $task_id")->order("id ASC");
    	if ($r = $this->fetchAll($sql))
    		return $r->toArray();
    }
}