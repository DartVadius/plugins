<?php

class Calendar_AjaxController extends Zend_Controller_Action {

    protected $_identity;
    protected $_contact_id;
    protected $_contact_role = array();
    protected $_calendar_view;
    protected $_calendar_nav;
    protected $_date;
    protected $_calendar_date;
    protected $_events_options;

    public function init() {
        if ($this->getRequest()->isXmlHttpRequest()) {
            Zend_Controller_Action_HelperBroker::removeHelper('viewRenderer');
        } else {
            $this->_helper->redirector->gotoUrl($this->getRequest()->getServer('HTTP_REFERER'));
        }

        $this->_identity = Zend_Auth::getInstance()->getIdentity();
        $this->_contact_id = $this->_identity['id'];
        $this->_calendar_view = $this->_request->getPost('param');
        $this->_calendar_nav = $this->_request->getPost('nav');

        if ($contact_id = $this->_request->getPost('contact_id')) {
            if (!Application_Model_DbTable_Contact::isUser($contact_id)) {
                $this->_contact_role = array(
                    'code' => 'client',
                    'name' => 'контакт'
                );
            } else {
                $role = Application_Model_DbTable_ContactRole::getRoleNameByContactId($contact_id);
                $this->_contact_role = array(
                    'code' => $role['code'],
                    'name' => $role['name']
                );
            }
            $this->_contact_id = $contact_id;
        }
        $this->_events_options['orders_select'] = $this->_identity['role'] . '-' . $this->_contact_role['code'];
    }

    public function uploadAction() {
        if (!is_null($this->_request->getParam('uploadfiles'))) {
            try {
                $files = array();
                $path = 'userfiles/contacts/files/' . $this->_identity['id'] . '/downloads/';
                if (!is_dir($path)) {
                    mkdir('userfiles/contacts/files/' . $this->_identity['id'] . '/', 0777);
                    mkdir($path, 0777);
                }

                foreach ($_FILES as $file) {
                    if (move_uploaded_file($file['tmp_name'], $path . basename($file['name']))) {
                        $files[] = array(
                            'name' => $file['name'],
                            'url' => $path
                        );
                    }
                }
                $this->_helper->json($files);
            } catch (Exception $e) {
                echo $e->getMaeesge();
            }
        }
    }

    /**
     * status:
     *      0 - in progress(new)
     *      1 - done
     *      2 - abort
     *      3 - pause
     */
    public function setStatusAction() {
        $operation = $this->_request->getParam('operation');
        $task_id = $this->_request->getParam('task_id');
        $done = true;
        $noComplite = array();

        $calendarTasksModel = new Calendar_Model_Tasks();
        Log::getInstance('crm_calendar_tasks')->startLog('status', $task_id, $calendarTasksModel->getFull($task_id));
        switch ($operation) {
            case 'btn done':

                if ($noComplite = $calendarTasksModel->checkChilds($task_id)) {
                    $noComplite = array_keys($noComplite);
                    $done = false;
                } else {
                    $calendarTasksModel->update(array('status' => 1), "id = $task_id");
                    $done = 'выполнено';

                    //send message to create_contact
                    $contactEmailsModel = new Application_Model_DbTable_ContactEmails();

                    $identity = Zend_Auth::getInstance()->getIdentity();
                    $auth_model = new Application_Model_DbTable_ContactSmtp();
                    $config_auth = $auth_model->get($identity['id']);

                    $config = array(
                        'auth' => 'login',
                        'username' => $config_auth['username'],
                        'password' => $config_auth['password'],
                        'ssl' => $config_auth['ssl'],
                        'port' => $config_auth['port']
                    );

                    $task = $calendarTasksModel->getFull($task_id);


                    $contactModel = new Application_Model_DbTable_Contact();
                    $name_from = $contactModel->getUserById($identity['id']);
                    $name_to = $contactModel->getUserById($task['contact_id']);
                    $contact_data = $contactEmailsModel->fetchAll("contact_id = " . $task['contact_id'], 'sort asc')->toArray();

                    $contact_data_email = $contact_data[min(array_keys($contact_data))]['email'];

                    $manager_data = $contactModel->getContact($identity['id']);

                    // генерируем уникальный code для письма
                    $code = md5(time() . $message_data['to_contact_id']);

                    $bodyHtml = '<div>Задача выполнена: ' . $task['title'] . '</div>';
                    if ($task['description'] != '') {
                        $bodyHtml .= '<div style="margin: 10px 0;">' . $task['description'] . '</div>';
                    }
                    $bodyHtml .= '<div><a href="http://' . Zend_Registry::get('crmDomain') . '/calendar/task/view/task_id/' . $task_id . '">Перейти на задачу</a></div>';

                    $connection = new Zend_Mail_Transport_Smtp($config_auth['server'], $config);
                    $mail = new Zend_Mail('utf-8');
                    $mail->setBodyHtml($bodyHtml);
                    $mail->setFrom($config_auth['username'], $manager_data['name']);
                    $mail->addTo($contact_data_email, $name_to['name']);
                    $mail->setSubject('Выполненная задача');

                    try {
                        $mail->send($connection);
                        $mail_status = 4;
                    } catch (Exception $e) {
                        $mail_status = 6;
                        $error_data = array(
                            'code' => $code,
                            'type' => 'task_mail',
                            'text_error' => serialize(array('error' => $e->getMessage(), 'text' => 'Не удалось отправить письмо пользователю', 'trace' => $e->getTrace()))
                        );
                        $statisticsMailerErrorLogModel = new Application_Model_DbTable_StatisticsMailerErrorLog();
                        $statisticsMailerErrorLogModel->insert($error_data);
                    }

                    $statistics_mail = array(
                        'contact_id' => $task['contact_id'],
                        'manager_id' => $identity['id'],
                        'type' => 'task_mail',
                        'status' => $mail_status,
                        'code' => $code
                    );

                    $statistics_mail_model = new Application_Model_DbTable_StatisticsMailerSendLog;
                    $id_statistics = $statistics_mail_model->insert($statistics_mail);
                }
                break;

            case 'pause':
                // $done = false;
                // $calendarTasksModel->update(array('status' => 3),"id = $task_id");
                break;
            case 'btn abort':
                $calendarTasksModel->update(array('status' => 2), "id = $task_id");
                $done = 'отменено';
                break;
        }

        Log::getInstance('crm_calendar_tasks')->endLog($calendarTasksModel->getFull($task_id));

        $this->_helper->json(array(
            'result' => $done,
            'noComplite' => $noComplite
        ));
    }

    public function updateCheckListAction() {
        $check_id = $this->_request->getParam('check_id');
        $is_checked = $this->_request->getParam('is_checked');

        $taskCheckListModel = new Calendar_Model_TasksCheckList();
        $taskCheckListModel->update(array(
            'is_checked' => $is_checked == 'true' ? 1 : 0
                ), "id = $check_id"
        );
    }

    public function getMiniTaskViewAction() {
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/modules/calendar/views/scripts/partials/');
        $html->assign('date', $this->_request->getPost('data'));
        $json = $html->render('add-task-mini.phtml');
        $this->_helper->json($json);
    }

    public function setMiniTaskAction() {

        $post = $this->_request->getPost('data');
        $tasksModel = new Calendar_Model_Tasks();
        $task = array(
            'contact_id' => $this->_identity['id'],
            'responsible' => $this->_identity['id'],
            'title' => $post['title']
        );
        if ($this->_request->getCookie('calendar-view') == 'week' || $this->_request->getCookie('calendar-view') == 'day') {
            $task['start_date'] = $post['date'] . ' ' . implode(':', $post['times']);
            $end_date = new Zend_Date($post['date'] . ' ' . implode(':', $post['times']));
            $task['end_date'] = $end_date->addHour(1)->getSql();
        } else {
            $task['start_date'] = $post['date'] . ' 00:00';
            $task['end_date'] = $post['date'] . ' 23:59';
        }

        try {
            $task_id = $tasksModel->save($task);
            $this->_helper->json('success');
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }
    }

    public function calendarAction() {
        $id = ($this->_identity['role'] == 'admin') ? NULL : $this->_identity['id'];
        $post = $this->_request->getParams();

        echo Calendar_CalendarService::dataForCalendar($post['start'], $post['end'], $id);
        die;
    }

    public function updateEventAction() {
        $post = $this->_request->getParams();
        Calendar_CalendarService::changeTaskPeriod($post['from'], $post['to'], $post['id']);
        die;
    }

    public function setFilterAction() {
        $post = $this->_request->getParams();
        Calendar_SessionService::setFilterSession($post['filter']);
        die;
    }

    public function setIdFilterAction() {
        $user_id = $this->_request->getParam('user_id');
        $user_name = $this->_request->getParam('user_name');
        Calendar_SessionService::setIdFilterSession($user_id, $user_name);
        die;
    }

    public function clearIdFilterAction() {
        Calendar_SessionService::clearIdFilterSession();
        die;
    }

    public function vizitAction() {
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/modules/calendar/views/scripts/events/');
        $post = $this->_request->getPost();

        $vizits = Calendar_CalendarService::vizitDetails($post['from'], $post['user_id']);
        $html->assign('vizits', $vizits);
        $json['html'] = $html->render('vizits.phtml');
        $this->_helper->json($json);
    }

    public function mailAction() {
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/modules/calendar/views/scripts/events/');
        $post = $this->_request->getPost();
        
        $mail = Calendar_CalendarService::mailDetails($post['id']);
       
        if ($mail) {
            $html->assign('mail', $mail);
            $html->assign('mail_id', $mail['code']);
        } else {
            $json['error'] = true;
        }

        $json['html'] = $html->render('mail.phtml');
        $this->_helper->json($json);
    }
    
    public function taskAction() {
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/modules/calendar/views/scripts/partials/');
        $calendarTasksModel = new Calendar_Model_Tasks();
        $post = $this->_request->getPost();
        
        $task = $calendarTasksModel->getFull($post['task_id']);
        
        $html->assign('task', $task);
        $json['html'] = $html->render('task.phtml');
        $this->_helper->json($json);
    }

}
