<?php

class Calendar_Model_TasksFiles extends Zend_Db_Table_Abstract
{
    protected $_name = 'crm_calendar_tasks_files';

    public function getByTaskId($task_id)
    {
    	if ($r = $this->fetchAll("task_id = $task_id")) {
    		$files = $r->toArray();

    		foreach ($files as &$f) {
    			$f['name'] = substr(strrchr($f['url'], '/'), 1);
    		}
    		unset($f);
    		return $files;
    	}
    }

}