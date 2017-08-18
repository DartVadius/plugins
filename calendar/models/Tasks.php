<?php

class Calendar_Model_Tasks extends Zend_Db_Table_Abstract {

    protected $_name = 'crm_calendar_tasks';
    protected $_qty;
   
    public function getTaskOrder($contact_id) {
        $sql = $this->select()
                ->from(array('calendar' => $this->_name))
                ->setIntegrityCheck(false)
                ->join(array('oc' => 'order_calendar'), 'oc.calendar_id=calendar.id')
                ->where("calendar.contact_id = " . $contact_id)
                ->where("calendar.status = '0'")
                ->where("oc.views = '0'")
                ->where("UNIX_TIMESTAMP(calendar.start_date) < " . time())
                ->order("calendar.start_date");
        $result = $this->addStrFormatDate($this->getDefaultAdapter()->fetchAssoc($sql));

        return $result;
    }

    public function getTaskByOrderId($order_id) {
        $sql = $this->select()
                ->from(array('calendar' => $this->_name))
                ->setIntegrityCheck(false)
                ->joinRight(array('oc' => 'order_calendar'), 'oc.calendar_id=calendar.id')
                ->where("calendar.status = '0'")
                ->where("oc.views = '0'")
                ->where("oc.order_id = $order_id")
                //->where("UNIX_TIMESTAMP(calendar.start_date) < ".time())
                ->order("calendar.start_date");
        $result = $this->addStrFormatDate($this->getDefaultAdapter()->fetchAssoc($sql));

        return $result;
    }

    public function getTaskByTime($from, $to, $contact_id) {
        $sql = $this->select()
                ->where("responsible = $contact_id OR contact_id = " . $contact_id)
                ->where("status = '0'")
                ->where("start_date BETWEEN STR_TO_DATE('" . $from . ":00', '%Y-%m-%d %H:%i:%s')
									AND STR_TO_DATE('" . $to . ":59', '%Y-%m-%d %H:%i:%s')")
                ->order("start_date");
        $result = $this->addStrFormatDate($this->getDefaultAdapter()->fetchAssoc($sql));

        if (count($result) > 0) {
            return $result;
        } else {
            return null;
        }
    }

    /**
     * Add srting view to dates
     */
    protected function addStrFormatDate($tasks) {
        if (count($tasks) > 0) {
            foreach ($tasks as $id => &$value) {
                $start_date = new Zend_Date($value['start_date'], 'yyy-MM-dd HH:mm:ss');
                $end_date = new Zend_Date($value['end_date'], 'yyy-MM-dd HH:mm:ss');

                if ($end_date->compareDay($start_date) == 1 || $end_date->compareMonth($start_date) == 1)
                    $value['date_str'] = $start_date->toString('EE dd MMM') . " - " . $end_date->toString('EE dd MMM');
                else
                    $value['date_str'] = $start_date->toString('EE dd MMM');
                $value['time_str'] = $start_date->toString('HH:mm') . " - " . $end_date->toString('HH:mm');
            }
            unset($value);
            return $tasks;
        }
    }

    public function getTask($task_id = null) {

        $sql = $this->select()
                ->from(array('ct' => $this->_name));

        if (!is_null($task_id)) {
            $sql->where("id = $task_id");
        }

        $task = $this->getDefaultAdapter()->fetchAssoc($sql);

        return $task;
    }

    public function getAllByContactId($contact_id) {
        $sql = $this->select()
                ->from(array('ct' => $this->_name))
                ->where("contact_id = $contact_id");
        $result = $this->getDefaultAdapter()->fetchAssoc($sql);

        $this->_qty = count($result);

        return $result;
    }

    public function getFull($task_id) {
        $calendarTasksAssistantsModel = new Calendar_Model_TasksAssistants();
        $calendarTasksCheckListModel = new Calendar_Model_TasksCheckList();
        $calendarTasksFilesModel = new Calendar_Model_TasksFiles();

        $task = $this->fetchRow("id = $task_id");
        if (!empty($task)) {
            $task = $task->toArray();
        } else {
            return null;
        }

        $task['responsible'] = Application_Model_DbTable_Contact::getById($task['responsible']);
        $task['assistants'] = $calendarTasksAssistantsModel->getByTaskId($task_id);
        $task['check_list'] = $calendarTasksCheckListModel->getByTaskId($task_id);
        $task['files'] = $calendarTasksFilesModel->getByTaskId($task_id);

        if (!is_null($task['curator']))
            $task['curator'] = Application_Model_DbTable_Contact::getById($task['curator']);

        if ($children_tasks = $this->getDefaultAdapter()->fetchAssoc(
                $this->select()->where("parent_task_id = $task_id"))) {
            $task['children_tasks'] = $children_tasks;
        }

        $date_format = 'dd-MM-yyyy HH:mm';
        $_date = new Zend_Date($task['start_date']);
        $task['start_date'] = $_date->toString($date_format);
        $_date->set($task['end_date']);
        $task['end_date'] = $_date->toString($date_format);
        return $task;
    }

    public function checkChilds($task_id) {
        return $this->getDefaultAdapter()->fetchAssoc(
                        $this->select()->where("parent_task_id = $task_id")->where("status = '0'")
        );
    }

    public function save($task, $task_id = null) {
        $calendarTasksAssistantsModel = new Calendar_Model_TasksAssistants();
        $calendarTasksCheckListModel = new Calendar_Model_TasksCheckList();
        $calendarTasksFilesModel = new Calendar_Model_TasksFiles();

        $data = $task;
        unset($data['assistants'], $data['check_list'], $data['files'], $data['create_task'], $data['create_all_tasks']);

        if (isset($data['start_date'])) {
            $_date = new Zend_Date($data['start_date']);
            $data['start_date'] = $_date->getSql();
        }

        if (isset($data['end_date'])) {
            $_date->set($data['end_date']);
            $data['end_date'] = $_date->getSql();
        }

        if (is_null($task_id)) {
            $task_id = $this->insert($data);
            Log::getInstance('crm_calendar_tasks')->startLog('create', $task_id);
            Log::getInstance('crm_calendar_tasks')->endLog($this->getFull($task_id));
        } else {
            unset($data['contact_id']);
            Log::getInstance('crm_calendar_tasks')->startLog('update', $task_id, $this->getFull($task_id));
            $this->update($data, "id = $task_id");
            Log::getInstance('crm_calendar_tasks')->endLog($this->getFull($task_id));
        }

        $calendarTasksAssistantsModel->delete("task_id = $task_id");
        if (isset($task['assistants'])) {
            foreach ($task['assistants'] as $a_id) {
                $calendarTasksAssistantsModel->insert(array(
                    'task_id' => $task_id,
                    'contact_id' => $a_id
                ));
            }
        }

        $calendarTasksCheckListModel->delete("task_id = $task_id");
        if (isset($task['check_list'])) {
            foreach ($task['check_list'] as $cl) {
                $cl = trim($cl);
                if (!empty($cl)) {
                    $calendarTasksCheckListModel->insert(array(
                        'task_id' => $task_id,
                        'title' => str_replace(array('"', "'"), array('&quot;', '&#39;'), $cl)
                    ));
                }
            }
        }

        $calendarTasksFilesModel->delete("task_id = $task_id");
        if (isset($task['files'])) {
            foreach ($task['files'] as $f) {
                $calendarTasksFilesModel->insert(array(
                    'task_id' => $task_id,
                    'file_url' => $f['file_url']
                ));
            }
        }
        return $task_id;
    }

    public function getCount() {
        return $this->_qty;
    }

    public function getCountPeriodicalTasks($task_title, $create_contact_id) {
        $task_title = str_replace("'", "\'", $task_title);
        $sql = $this->select()
                ->from(array($this->_name), array('count(*) as task_count'))
                ->where("title = '$task_title' AND contact_id = $create_contact_id");
        $task_qty = $this->fetchRow($sql);
        if (!is_null($task_qty)) {
            $task_qty = $task_qty->toArray();
            return $task_qty['task_count'];
        } else {
            return null;
        }
    }

    public function getPeriodicalTasks($values, $type = 'id') {

        $taskAssistantsModel = new Calendar_Model_TasksAssistants();
        $taskCheckListModel = new Calendar_Model_TasksCheckList();
        $taskCommentsModel = new Calendar_Model_TasksComments();
        $taskFilesModel = new Calendar_Model_TasksFiles();
        $contactModel = new Application_Model_DbTable_Contact();

        if ($type == 'id') {
            $task_data = $this->fetchRow("id = $values");
            if (!empty($task_data)) {
                $task_data = $task_data->toArray();
                $title = $task_data['title'];
                $contact_id = $task_data['contact_id'];
            } else {
                return null;
            }
        } else {
            $title = $values['title'];
            $contact_id = $values['contact_id'];
        }

        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('t' => $this->_name), array('id', 'parent_task_id', 'contact_id',
                    'responsible', 'curator', 'title', 'description', 'start_date',
                    'end_date', 'create_date', 'priority', 'status'))
                ->where("t.title = '$title' AND t.contact_id = $contact_id")
                ->order("start_date asc");

        $tasks = $this->getDefaultAdapter()->fetchAssoc($sql);

        foreach ($tasks as $task_id => $task) {

            $tasks[$task_id]['assistants'] = $taskAssistantsModel->getByTaskId($task_id);

            $tasks[$task_id]['check_list'] = $taskCheckListModel->getByTaskId($task_id);

            $tasks[$task_id]['comments'] = $taskCommentsModel->getByTaskId($task_id);

            $tasks[$task_id]['files'] = $taskFilesModel->getByTaskId($task_id);

            $tasks[$task_id]['create_contact'] = $contactModel->getById($task['contact_id']);

            $tasks[$task_id]['responsible'] = $contactModel->getById($task['responsible']);

            $tasks[$task_id]['curator'] = !empty($task['curator']) ? $contactModel->getById($task['curator']) : null;
        }

        return $tasks;
    }

    /**
     * get tasks by period and user id
     * if user id not initialized - get all tasks by period
     * 
     * @param datetime $from        start period
     * @param datetime $to          end period
     * @param integer $user_id      user id
     * @return boolean | array      false | array of tasks
     */
    public function getByPeriod($from, $to, $user_id = NULL) {
        $session = new Zend_Session_Namespace('calendar_user_filter');
        if (!empty($session->user_id)) {
            $user_id = $session->user_id;
        }
        
        $select = $this->select()->setIntegrityCheck(FALSE)
                ->from('crm_calendar_tasks')
                ->where("'{$from}' BETWEEN crm_calendar_tasks.start_date AND crm_calendar_tasks.end_date")
                ->orWhere("crm_calendar_tasks.start_date BETWEEN '{$from}' AND '{$to}'");

        if ($user_id) {
            $select->joinLeft('crm_calendar_tasks_assistants', 'crm_calendar_tasks.id=crm_calendar_tasks_assistants.task_id', 'contact_id AS assistant_contact')
                    ->where('crm_calendar_tasks.contact_id =?', $user_id)
                    ->orWhere('crm_calendar_tasks.responsible =?', $user_id)
                    ->orWhere('crm_calendar_tasks.curator =?', $user_id)
                    ->orWhere('crm_calendar_tasks_assistants.contact_id =?', $user_id)
                    ->group('crm_calendar_tasks.id');
        }
        $select->order("start_date");

        $result = $this->fetchAll($select);
        if ($result->count()) {
            return $result->toArray();
        }
        return FALSE;
    }

    /**
     * change task period value
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $id
     */
    public function updatePeriod($from, $to, $id) {
        $this->update(['start_date' => $from, 'end_date' => $to], "id={$id}");
    }

}
