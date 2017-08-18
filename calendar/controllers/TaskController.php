<?php

class Calendar_TaskController extends Zend_Controller_Action {

    public function init() {
        $this->_identity = Zend_Auth::getInstance()->getStorage()->read();
    }

    public function editAction() {

        $this->view->headTitle('Добавить новую задачу', 'PREPEND');

        $contactModel = new Application_Model_DbTable_Contact();
        $calendarTasksModel = new Calendar_Model_Tasks();
        $calendarTask = new Calendar_Task();

        $manager_list = $contactModel->getAllUsers('1, 2');

        $post = $this->_request->getPost();
        
        $date = $this->_request->getParam('date');
        if (!empty($date)) {
            $this->view->date = date( "d-m-Y H:i", strtotime($date) );
        }

        $task_id = $this->_request->getParam('task_id');
        $identity = Zend_Auth::getInstance()->getStorage()->read();

        $task_list = $calendarTasksModel->getAllByContactId($this->_identity['id']);

        if (!is_null($task_id)) {
            unset($task_list[$task_id]);
        }

        if (!empty($post)) {
            if (isset($post['create_task']) || isset($post['create_all_tasks'])) {
                $post['contact_id'] = $this->_identity['id'];
                $post['title'] = str_replace(array('"', "'"), array('&quot;', '&#39;'), $post['title']);
                if ($post['set_time_interval_type'] == 'some_tasks' && isset($post['end_period'])) {

                    if (isset($post['start_period'])) {
                        $start_period = $post['start_period'];
                    } else {
                        $start_period = date('Y-m-d');
                    }

                    $start_period_seconds = strtotime($start_period);
                    $end_period = $post['end_period'];
                    $end_period_seconds = strtotime($end_period);

                    $period_type = $post['period_type'];
                    $by_day_or_month = $post['by_day_or_month'];
                    $change_day_or_month = $post['change_day_or_month'];

                    $days_qty = ($end_period_seconds - $start_period_seconds) / 24 / 3600 + 1;

                    $days_in_period = array();
                    for ($i = 0; $i < $days_qty; $i++) {
                        $days_in_period[] = date('d.m.Y', $start_period_seconds + $i * 24 * 3600);
                    }

                    if ($period_type == 'day_month') {
                        if ($change_day_or_month == 'change_day') {

                            $j = $by_day_or_month;
                            foreach ($days_in_period as $id => $day) {
                                if ($j != $by_day_or_month) {
                                    unset($days_in_period[$id]);
                                }
                                $j--;
                                if ($j == 0) {
                                    $j = $by_day_or_month;
                                }
                            }
                        } elseif ($change_day_or_month == 'change_month') {

                            $day_of_month = date('d', $start_period_seconds);
                            $month_from = date('m', $start_period_seconds);
                            $year_from = date('Y', $start_period_seconds);
                            $month_to = date('m', $end_period_seconds);
                            $year_to = date('Y', $end_period_seconds);

                            $month_in_period = array();
                            for ($i = $year_from; $i <= $year_to; $i++) {
                                for ($j = $month_from; $j <= $month_to; $j++) {
                                    $month_in_period[] = $day_of_month . '.' . $j . '.' . $i;
                                }
                            }

                            $j = $by_day_or_month;
                            foreach ($month_in_period as $id => $month) {
                                if ($j != $by_day_or_month || strtotime($month) > $end_period_seconds) {
                                    unset($month_in_period[$id]);
                                }
                                $j--;
                                if ($j == 0) {
                                    $j = $by_day_or_month;
                                }
                            }

                            $days_in_period = $month_in_period;
                        }
                    } elseif ($period_type == 'day_of_week') {
                        $days_of_week = array();
                        foreach ($post as $field => $value) {
                            if (stristr($field, 'period1_')) {
                                $days_of_week[] = substr(stristr($field, '_'), 1);
                            }
                        }

                        foreach ($days_in_period as $id => $day) {
                            if (!in_array(strtolower(date('D', strtotime($day))), $days_of_week)) {
                                unset($days_in_period[$id]);
                            }
                        }
                    }

                    $period_for_every_task = array();
                    foreach ($days_in_period as $id => $day) {
                        $period_for_every_task[$id] = array(
                            'from' => date('Y-m-d H:i:s', strtotime($day . ' ' . str_replace(array(';'), ':', $post['hours_from']))),
                            'to' => date('Y-m-d H:i:s', strtotime($day . ' ' . str_replace(array(';'), ':', $post['hours_to'])) + 24 * 3600 * ($post['during_time'] - 1))
                        );
                    }
                }

                unset($post['start_period'], $post['end_period'], $post['by_day_or_month'], $post['change_day_or_month'], $post['period_type'], $post['hours_from'], $post['hours_to'], $post['during_time'], $post['set_time_interval_type']);

                if (!isset($post['curator'])) {
                    $post['curator'] = 0;
                }

                foreach ($post as $id => $field) {
                    if (stristr($id, 'period1_')) {
                        unset($post[$id]);
                    }
                }

                $task_ids = array();
                if (is_null($task_id)) {

                    if (isset($period_for_every_task)) {
                        // создание цепочки задач
                        foreach ($period_for_every_task as $id => $time_interval) {
                            $post['start_date'] = $time_interval['from'];
                            $post['end_date'] = $time_interval['to'];
                            $task_ids[] = $calendarTasksModel->save($post);
                        }
                    } else {
                        // создание одной задачи
                        if ($post['start_date'] == '') {
                            $post['start_date'] = date('Y-m-d H:i:s');
                        }
                        $task_ids[] = $calendarTasksModel->save($post);
                    }

                    $task_id = $task_ids[min(array_keys($task_ids))];
                    $message_start_text = 'Вам назначена задача';
                } else {

                    if (isset($post['create_task'])) {
                        $calendarTasksModel->save($post, $task_id);
                        $task_ids[] = $task_id;
                    } elseif (isset($post['create_all_tasks'])) {

                        $task_row = $calendarTasksModel->fetchRow("id = $task_id");
                        if (!empty($task_row)) {
                            $task_row = $task_row->toArray();
                            $tasks = $calendarTasksModel->fetchAll("title = '" . $task_row['title'] . "' AND contact_id = " . $task_row['contact_id'])->toArray();
                            if (!empty($tasks)) {

                                $start_date = $post['start_date'];
                                $end_date = $post['end_date'];

                                foreach ($tasks as $id => $value) {
                                    if ($value['id'] == $task_id) {
                                        $post['start_date'] = $start_date;
                                        $post['end_date'] = $end_date;
                                    } else {
                                        unset($post['start_date'], $post['end_date']);
                                    }

                                    $calendarTasksModel->save($post, $value['id']);
                                    $task_ids[] = $value['id'];
                                }
                            }
                        }
                    }

                    if (isset($period_for_every_task)) {

                        $task_row = $calendarTasksModel->fetchRow("id = $task_id");

                        if (!empty($task_row)) {
                            $task_row = $task_row->toArray();
                            foreach ($period_for_every_task as $id => $time_interval) {
                                $post['start_date'] = $time_interval['from'];
                                $post['end_date'] = $time_interval['to'];
                                $post['contact_id'] = $task_row['contact_id'];
                                $task_ids[] = $calendarTasksModel->save($post);
                            }
                        }
                    }

                    $message_start_text = 'Задача изменена';
                }

                // создаем уведомление для пользователя
                if (!empty($task_ids)) {
                    foreach ($task_ids as $id) {
                        $calendarTask->saveNotifications($post, $id);
                    }
                }

                $task_data = $calendarTasksModel->getFull($task_id);

                // отправляем письмо
                $contactEmailsModel = new Application_Model_DbTable_ContactEmails();
                $auth_model = new Application_Model_DbTable_ContactSmtp();
                $config_auth = $auth_model->get($identity['id']);

                $config = array(
                    'auth' => 'login',
                    'username' => $config_auth['username'],
                    'password' => $config_auth['password'],
                    'ssl' => $config_auth['ssl'],
                    'port' => $config_auth['port']
                );

                $contactModel = new Application_Model_DbTable_Contact();
                $statisticsMailerErrorLogModel = new Application_Model_DbTable_StatisticsMailerErrorLog();
                $statistics_mail_model = new Application_Model_DbTable_StatisticsMailerSendLog;

                $name_from = $contactModel->getUserById($identity['id']);
                $manager_data = $contactModel->getContact($identity['id']);

                $mail_recipients = array($post['responsible'] => 'responsible');

                if (isset($post['curator']) && !empty($post['curator'])) {
                    $mail_recipients[$post['curator']] = 'curator';
                }

                if (isset($post['assistants']) && !empty($post['assistants'])) {
                    foreach ($post['assistants'] as $id => $contact_id) {
                        $mail_recipients[$contact_id] = 'assistant';
                    }
                }

                if (!empty($mail_recipients)) {
                    foreach ($mail_recipients as $contact_id => $value) {
                        $name_to = $contactModel->getUserById($contact_id);

                        if (is_numeric($contact_id)) {
                            $contact_data = $contactEmailsModel->fetchAll("contact_id = " . $contact_id, 'sort asc');
                            if (!empty($contact_data)) {
                                $contact_data = $contact_data->toArray();
                            }
                        }

                        if (!empty($contact_data)) {
                            $contact_data_email = $contact_data[min(array_keys($contact_data))]['email'];

                            // генерируем уникальный code для письма
                            $code = md5(time() . $contact_id);

                            $bodyHtml = '<div>' . $message_start_text . ': ' . $post['title'] . '</div>';
                            if ($post['description'] != '') {
                                $bodyHtml .= '<div style="margin: 10px 0;">' . $post['description'] . '</div>';
                            }
                            $bodyHtml .= '<div><a href="http://' . Zend_Registry::get('crmDomain') . '/calendar/task/view/task_id/' . $task_id . '">Перейти на задачу</a></div>';

                            $connection = new Zend_Mail_Transport_Smtp($config_auth['server'], $config);
                            $mail = new Zend_Mail('utf-8');
                            $mail->setBodyHtml($bodyHtml);
                            $mail->setFrom($config_auth['username'], $manager_data['name']);
                            $mail->addTo($contact_data_email, $name_to['name']);
                            $mail->setSubject('Новая задача');

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

                                $statisticsMailerErrorLogModel->insert($error_data);
                            }
                            $statistics_mail = array(
                                'contact_id' => $contact_id,
                                'manager_id' => $identity['id'],
                                'type' => 'task_mail',
                                'status' => $mail_status,
                                'code' => $code
                            );

                            $id_statistics = $statistics_mail_model->insert($statistics_mail);
                        }
                    }
                }
            } elseif (isset($post['del_current_task'])) {
                $calendarTasksModel->delete("id = $task_id");
                $this->_helper->FlashMessenger('Задача успешно удалена');
            } elseif (isset($post['del_all_periodical_tasks'])) {
                $task = $calendarTasksModel->getFull($task_id);
                
                $calendarTasksModel->delete("title = '" . $task['title'] . "' AND contact_id = " . $task['contact_id']);
                $this->_helper->FlashMessenger('Цепочка задач успешно удалена');
            }
            $this->_helper->redirector('index', 'index', 'calendar');
        }
        if (empty($task_id)) {
            $title = 'Новая задача';
            $send_button_caption = 'Добавить задачу';
        } else {
            $task = $calendarTasksModel->getFull($task_id);

            if (is_null($task)) {
                $this->_helper->redirector->goToRoute(array('module' => 'calendar', 'controller' => 'index', 'action' => 'index'), 'default', true);
            }

            $task_qty = $calendarTasksModel->getCountPeriodicalTasks($task['title'], $task['contact_id']);
            $task['task_qty'] = $task_qty;

            $this->view->task = $task;
            $title = 'Редактирование задачи';

            if ($task_qty > 1) {
                $send_button_caption = 'Сохранить текущую задачу';
            } else {
                $send_button_caption = 'Сохранить задачу';
            }
        }

        $this->view->title = $title;
        $this->view->send_button_caption = $send_button_caption;

        $this->view->my_tasks = $task_list;
        $this->view->manager_list = $manager_list;
    }

    public function viewAction() {

        $task_id = $this->_request->getParam('task_id');
        $calendarTasksModel = new Calendar_Model_Tasks();

        $stringOperations = new Crm_StringOperations();

        $translate = Zend_Registry::get('Zend_Translate');

        $periodical_tasks = $calendarTasksModel->getPeriodicalTasks($task_id);

        $task = $periodical_tasks[$task_id];

        unset($periodical_tasks[$task_id]);

        $task_statuses = array(
            0 => 'task in process',
            1 => 'task complete',
            2 => 'task canceled'
        );

        $task['status_name'] = $stringOperations->ucfirst_utf8($translate->_($task_statuses[$task['status']]));
        $task['status_class'] = str_replace(' ', '-', $task_statuses[$task['status']]);

        $this->view->edit = ($this->_identity['role'] == 'admin' || $this->_identity['id'] == $task['contact_id']) ? TRUE : FALSE;
        $this->view->task = $task;
        $this->view->periodical_tasks = $periodical_tasks;
    }

}
