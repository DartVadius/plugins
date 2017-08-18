<?php

class Calendar_IndexController extends Zend_Controller_Action {

    protected $_identity;

    public function init() {
        $this->_identity = Zend_Auth::getInstance()->getStorage()->read();
        $this->view->headTitle('Календарь', 'PREPEND');
    }

    public function indexAction() {
        $orderStatusModel = new Calendar_Model_OrderState();
        if (empty($this->_request->getCookie('CalendarView'))) {
            setcookie("CalendarView", 'agendaDay', time()+36000);
        }
        $statuses = $orderStatusModel->getActiveStatuses();
        Calendar_SessionService::initFilterSession($statuses);
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/modules/calendar/views/scripts/partials/');
        $this->view->calendar = $html->render('calendar.phtml');
        $this->view->statuses = $statuses;
    }

    public function getFileFromMailArchiveAction() {
        $data = $this->_request->getParams();
        $mailsModel = new Application_Model_DbTable_StatisticsMailerSendLog();

        if ($_mail = $mailsModel->fetchRow("code = '$data[mail_id]'")) {

            $file = new Crm_DataToFile();
            $mail = $file->readMail($_mail->code, new Zend_Date($_mail->create_date));

            $file = $mail['attachment'][$data['file_key']];
            $filename = $mail['filename'][$data['file_key']];
            if ($file) {
                header('Pragma: public');
                header('Expires: 0');
                header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
                header('Cache-Control: private', false);
                header('Content-Type: application/octet-stream', false);
                header('Content-Disposition: attachment; filename=' . $filename);
                header('Content-Transfer-Encoding: binary');
                echo $file;
            } else {
                echo 'Error';
            }
        }
        die;
    }

}
