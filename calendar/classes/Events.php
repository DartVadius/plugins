<?php

class Calendar_Events
{
    protected $_contact_id;
    protected $_identity;
    protected $_from;
    protected $_to;
    protected $_options;
    protected $_db;
    protected $_events;
    protected $_translate;
    protected $_qty;

    // models
    protected $_mailerStatus;
    protected $_vizits;
    protected $_orderLog;

	public function __construct($contact_id, $from, $to, $options = null)
    {
        if (!is_null($contact_id) && !is_null($from) && !is_null($to)) {
            $this->_contact_id = $contact_id;
            $this->_from = $from;
            $this->_to = $to;
            $this->_options = $options;
            $this->_db = Zend_Db_Table::getDefaultAdapter();
            $this->_identity = Zend_Auth::getInstance()->getIdentity();
        } else {
            throw new Exception("Error. Check values: contact_id:$contact_id, from:$from, to:$to", 1);
        }
        $this->_translate = Zend_Registry::get('Zend_Translate');

        $this->_mailerStatus = new Application_Model_DbTable_MailerStatus();
        $this->_vizits = new Application_Model_DbTable_StatisticsContactVisitHistory();
        $this->_orderLog = new Shop_Model_OrderLog();
    }

    public function getMailerProjects()
	{
        $sql = $this->_db->select()
        		  ->union(array(
        		  		$this->_db->select()
        		  			->from(array('t1' => 'crm_mailer_project'), array('id', 'name', "CONCAT_WS(' ',`send_on_schedule`,`schedule_time`) as date"))
        		  			->where("contact_id = $this->_contact_id")
        		  			->where("send_on_schedule BETWEEN STR_TO_DATE('" . $this->_from . "', '%Y-%m-%d') 
                                           AND STR_TO_DATE('" . $this->_to . "', '%Y-%m-%d')"),
        		  		$this->_db->select()
        		  			->from(array('t2' => 'crm_mailer_archive_projects'), array('id', 'name', 'last_send as date'))
        		  			->where("contact_id = $this->_contact_id")
        		  			->where("last_send BETWEEN STR_TO_DATE('" . $this->_from . " 00:00:00', '%Y-%m-%d %H:%i:%s') 
                                           AND STR_TO_DATE('" . $this->_to . " 23:59:59', '%Y-%m-%d %H:%i:%s')")
        		  	));

		if ($p = $this->_db->fetchAll($sql)) {
            $this->_qty['mailer_project'] = count($p);
            return $p;
		}
	}

    /**
     * @param $contact_type (manager || contact)
     * @param $mail_types array of mail types
     */
    public function getMails($contact_type, $mail_types = array())
    {
        $sql = $this->_db->select()
                  ->from(array('log' => 'crm_statistics_mailer_send_log'))
                  ->joinLeft(array('report' => 'crm_mailer_report'), 'log.code = report.code', array('create_date AS report_date'))
                  ->where("log." . $contact_type . "_id = $this->_contact_id")
                  ->where("log.create_date BETWEEN STR_TO_DATE('" . $this->_from . " 00:00:00', '%Y-%m-%d %H:%i:%s') 
                                           AND STR_TO_DATE('" . $this->_to . " 23:59:59', '%Y-%m-%d %H:%i:%s')");
        if (!empty($mail_types)) {
            $sql->where("log.type IN ('" . implode("','", $mail_types) . "')");
        }

        if ($msl = $this->_db->fetchAll($sql)) {
            $this->_qty['mails'] = count($msl);
            return $msl;
        }
    }

    public function getPerMonth()
    {
        $projects = $this->getMailerProjects();
        if (count($projects) > 0) {
            foreach ($projects as $item) {
                $date = new Zend_Date($item['date'], 'yyy-MM-dd HH:mm:ss');
                $item['event_type'] = 'mailer_project';
                $item['hint'] = isset($item['status']) ? $this->_mailerStatus->getStatusText($item['status']) . ' ' . $date->toString('HH:mm dd/MM/y') : $this->_translate->_('done') . ' ' . $date->toString('HH:mm dd/MM/y');
                $this->_events[$date->toString('d')][] = $item;
            }
        }

        $mails = $this->getMails('manager', array('invoice','commercial','mail'));
        if (count($mails) > 0) {
            foreach ($mails as $item) {
                $item['event_type'] = 'mail';
                $item['name'] = $this->_translate->_($item['type']);
                $item['id'] = $item['code'];
                if (isset($item['report_date'])) {
                    $report_date = new Zend_Date($item['report_date'], 'yyy-MM-dd HH:mm:ss');
                    $item['hint'] = $this->_translate->_('delivered') . ' ' . $report_date->toString('HH:mm dd/MM/y');
                }
                $date = new Zend_Date($item['create_date'], 'yyy-MM-dd HH:mm:ss');
                $this->_events[$date->toString('d')][] = $item;
            }
        }

        $this->getOrders('month');
        
        if (count($this->_events) > 0) {
            return $this->_events;
        }
    }

    public function getPerWeek()
    {
        $projects = $this->getMailerProjects();
        if (count($projects) > 0) {
            foreach ($projects as $item) {
                $date = new Zend_Date($item['date'], 'yyy-MM-dd HH:mm:ss');
                $item['top'] = $this->findTopByDate($item['date']);
                $item['event_type'] = 'mailer_project';
                $item['hint'] = isset($item['status']) ? $this->_mailerStatus->getStatusText($item['status']) . ' ' . $date->toString('HH:mm dd/MM/y') : $this->_translate->_('done') . ' ' . $date->toString('HH:mm dd/MM/y');
                $this->_events[$date->toString('eee')][] = $item;
            }
        }

        $mails = $this->getMails('manager', array('invoice','commercial','mail'));
        if (count($mails) > 0) {
            foreach ($mails as $item) {
                $item['event_type'] = 'mail';
                $item['name'] = $this->_translate->_($item['type']);
                $item['id'] = $item['code'];
                if (isset($item['report_date'])) {
                    $report_date = new Zend_Date($item['report_date'], 'yyy-MM-dd HH:mm:ss');
                    $item['hint'] = $this->_translate->_('delivered') . ' ' . $report_date->toString('HH:mm dd/MM/y');
                }
                $date = new Zend_Date($item['create_date'], 'yyy-MM-dd HH:mm:ss');
                $item['top'] = $this->findTopByDate($item['create_date']);
                $this->_events[$date->toString('eee')][] = $item;
            }
        }

        $this->getOrders('week');
        
        if (count($this->_events) > 0) {
            return $this->_events;
        }
    }

    public function getForDay()
    {
        $projects = $this->getMailerProjects();

        if (count($projects) > 0) {
            foreach ($projects as $item) {
                $date = new Zend_Date($item['date'], 'yyy-MM-dd HH:mm:ss');
                $item['top'] = $this->findTopByDate($item['date']);
                $item['event_type'] = 'mailer_project';
                $item['hint'] = isset($item['status']) ? $this->_mailerStatus->getStatusText($item['status']) . ' ' . $date->toString('HH:mm dd/MM/y') : $this->_translate->_('done') . ' ' . $date->toString('HH:mm dd/MM/y');
                $this->_events[] = $item;
            }
        }

        $mails = $this->getMails('manager', array('invoice','commercial','mail'));
        if (count($mails) > 0) {
            foreach ($mails as $item) {
                $item['event_type'] = 'mail';
                $item['name'] = $this->_translate->_($item['type']);
                $item['id'] = $item['code'];
                if (isset($item['report_date'])) {
                    $report_date = new Zend_Date($item['report_date'], 'yyy-MM-dd HH:mm:ss');
                    $item['hint'] = $this->_translate->_('delivered') . ' ' . $report_date->toString('HH:mm dd/MM/y');
                }
                $item['top'] = $this->findTopByDate($item['create_date']);
                $this->_events[] = $item;
            }
        }

        $this->getOrders('day');

        if (count($this->_events) > 0) {
            return $this->_events;
        }
    }

    protected function findTopByDate($date)
    {
        $date = new Zend_Date($date, 'yyy-MM-dd HH:mm:ss');
        return $date->toString('HH') * 60 + $date->toString('mm');
    }

    public function getForClientPerMonth()
    {
        $mails = $this->getMails('contact');
        if (count($mails) > 0) {
            foreach ($mails as $item) {
                $item['event_type'] = 'mail';
                $item['name'] = $this->_translate->_($item['type']);
                $item['id'] = $item['code'];
                if (isset($item['report_date'])) {
                    $report_date = new Zend_Date($item['report_date'], 'yyy-MM-dd HH:mm:ss');
                    $item['hint'] = $this->_translate->_('delivered') . ' ' . $report_date->toString('HH:mm dd/MM/y');
                }
                $date = new Zend_Date($item['create_date'], 'yyy-MM-dd HH:mm:ss');
                $this->_events[$date->toString('d')][] = $item;
            }
        }

        $vizits = $this->_vizits->visits($this->_contact_id, $this->_from, $this->_to);

        $this->_qty['vizits'] = count($vizits);

        if (!empty($vizits)) {
            foreach ($vizits as $item) {
                $date = new Zend_Date($item['datetime'], 'yyy-MM-dd HH:mm:ss');
                $item['id'] = $date->toString('y-MM-dd');
                $item['event_type'] = 'vizit';
                $item['name'] = $this->_translate->_('vizit on frontend');
                $this->_events[$date->toString('d')][] = $item;
            }
        }

        $this->getOrders('month');

        if (count($this->_events) > 0) {
            return $this->_events;
        }
    }

    public function getForClientPerWeek()
    {
        $mails = $this->getMails('contact');
        if (count($mails)) {
            foreach ($mails as $item) {
                $item['event_type'] = 'mail';
                $item['name'] = $this->_translate->_($item['type']);
                $item['id'] = $item['code'];
                if (isset($item['report_date'])) {
                    $report_date = new Zend_Date($item['report_date'], 'yyy-MM-dd HH:mm:ss');
                    $item['hint'] = $this->_translate->_('delivered') . ' ' . $report_date->toString('HH:mm dd/MM/y');
                }
                $item['top'] = $this->findTopByDate($item['create_date']);
                $date = new Zend_Date($item['create_date'], 'yyy-MM-dd HH:mm:ss');
                $this->_events[$date->toString('eee')][] = $item;
            }
        }

        $vizits = $this->_vizits->visits($this->_contact_id, $this->_from, $this->_to);

        $this->_qty['vizits'] = count($vizits);

        if (!empty($vizits)) {
            foreach ($vizits as $item) {
                $date = new Zend_Date($item['datetime'], 'yyy-MM-dd HH:mm:ss');
                $item['event_type'] = 'vizit';
                $item['name'] = $this->_translate->_('vizit on frontend');
                $item['top'] = $this->findTopByDate($item['create_date']);
                $item['id'] = $date->toString('y-MM-dd');
                $this->_events[$date->toString('eee')][] = $item;
            }
        }

        $this->getOrders('week');

        if (count($this->_events) > 0) {
            return $this->_events;
        }
    }

    public function getForClientByDay()
    {
        $mails = $this->getMails('contact');
        if (count($mails)) {
            foreach ($mails as $item) {
                $item['event_type'] = 'mail';
                $item['name'] = $this->_translate->_($item['type']);
                $item['id'] = $item['code'];
                if (isset($item['report_date'])) {
                    $report_date = new Zend_Date($item['report_date'], 'yyy-MM-dd HH:mm:ss');
                    $item['hint'] = $this->_translate->_('delivered') . ' ' . $report_date->toString('HH:mm dd/MM/y');
                }
                $date = new Zend_Date($item['create_date'], 'yyy-MM-dd HH:mm:ss');
                $item['top'] = $this->findTopByDate($item['create_date']);
                $this->_events[] = $item;
            }
        }

        $vizits = $this->_vizits->visits($this->_contact_id, $this->_from, $this->_to);

        $this->_qty['vizits'] = count($vizits);

        if (!empty($vizits)) {
            foreach ($vizits as $item) {
                $date = new Zend_Date($item['datetime'], 'yyy-MM-dd HH:mm:ss');
                $item['event_type'] = 'vizit';
                $item['name'] = $this->_translate->_('vizit on frontend');
                $item['id'] = $date->toString('y-MM-dd');
                $item['top'] = $this->findTopByDate($item['create_date']);
                $this->_events[] = $item;
            }
        }

        $this->getOrders('day');

        if (count($this->_events) > 0) {
            return $this->_events;
        }
    }

    protected function getOrders($calendar_view)
    {
        switch ($this->_options['orders_select']) {
            case 'admin-client':
                $orders = $this->_orderLog->getByPeriod($this->_from, $this->_to, null, $this->_contact_id);
                break;

            case 'admin-manager':
                $orders = $this->_orderLog->getByPeriod($this->_from, $this->_to, $this->_contact_id, null);
                break;

            case 'manager-client':
                $orders = $this->_orderLog->getByPeriod($this->_from, $this->_to, $this->_identity['id'], $this->_contact_id);
                break;

            case 'manager-':
                $orders = $this->_orderLog->getByPeriod($this->_from, $this->_to, $this->_identity['id'], null);
                break;
            
            case 'admin-':
                $orders = $this->_orderLog->getByPeriod($this->_from, $this->_to, null, null);
                break;
        }

        $this->_qty['orders'] = count($orders);
        
        if ($this->_qty['orders'] > 0) {
            foreach ($orders as $item) {
                $item['id'] = $item['order_id'];
                $item['event_type'] = 'order';
                $item['name'] = "# $item[order_id] " . $this->_translate->_($item['action_id']);
                $date = new Zend_Date($item['datetime'], 'yyy-MM-dd HH:mm:ss');
                if ($calendar_view == 'month') {
                    $this->_events[$date->toString('d')][] = $item;
                } elseif($calendar_view == 'week') {
                    $item['top'] = $this->findTopByDate($item['datetime']);
                    $this->_events[$date->toString('eee')][] = $item;
                } else {
                    $item['top'] = $this->findTopByDate($item['datetime']);
                    $this->_events[] = $item;
                }
            }
        }
    }

    public function getCount()
    {
        return $this->_qty;
    }
    
}