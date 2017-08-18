<?php

/**
 * Description of CalendarService
 *
 * @author DartVadius
 */
class Calendar_CalendarService {

    const DATA_TYPE_TASK = 'task';
    const DATA_TYPE_ORDER = 'order';
    const DATA_TYPE_MAIL = 'mail';
    const DATA_TYPE_MAILER = 'mailer';
    const CALENDAR_FILTER_ON = 1;
    const CALENDAR_FILTER_OFF = NULL;

    /**
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $id       user Id
     * @return json
     */
    public static function dataForCalendar($from, $to, $id) {
        $session = new Zend_Session_Namespace('calendar_filter');
        $userSession = new Zend_Session_Namespace('calendar_user_filter');
        $contactModel = new Application_Model_DbTable_Contact();
        $calendarData = [];
        $from = (empty($from)) ? date('Y-m-d') : $from;
        $to = (empty($to)) ? date('Y-m-d') : $to;
        $taskData = ($session->filter[self::DATA_TYPE_TASK] == self::CALENDAR_FILTER_ON) ? self::taskData($from, $to, $id) : [];
        $orderData = ($session->filter[self::DATA_TYPE_ORDER] == self::CALENDAR_FILTER_ON) ? self::orderData($from, $to, $id) : [];
        $mailerData = ($session->filter[self::DATA_TYPE_MAILER] == self::CALENDAR_FILTER_ON) ? self::mailerData($from, $to, $id) : [];
        $mailData = ($session->filter[self::DATA_TYPE_MAIL] == self::CALENDAR_FILTER_ON) ? self::mailData($from, $to, $id) : [];
        if (!empty($userSession->user_id) && $contactModel->isUser($userSession->user_id) === FALSE) {
            $vizitData = self::vizitData($from, $to, $userSession->user_id);
        } else {
            $vizitData = [];
        }


        $calendarData = array_merge($calendarData, $taskData, $orderData, $mailerData, $mailData, $vizitData);

        return(json_encode($calendarData));
    }

    /**
     * task data for FullCalendar
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $user_id
     * @return array
     */
    private static function taskData($from, $to, $user_id) {
        $taskModel = new Calendar_Model_Tasks();
        $identity = Zend_Auth::getInstance()->getStorage()->read();
        $contactModel = new Application_Model_DbTable_Contact();
        $session = new Zend_Session_Namespace('calendar_user_filter');

        $calendarData = [];
        if (!empty($session->user_id) && $contactModel->isUser($session->user_id) === FALSE) {
            return $calendarData;
        }
        $tasks = $taskModel->getByPeriod($from, $to, $user_id);
        if ($tasks) {
            foreach ($tasks as $task) {
                $oneTask = [];
                $oneTask['id'] = $task['id'];
                $oneTask['title'] = $task['title'];
                $oneTask['className'] = 'task';
                if ($identity['role'] == 'admin' || $identity['id'] == $task['contact_id']) {
                    $oneTask['startEditable'] = TRUE;
                    $oneTask['durationEditable'] = TRUE;
                } else {
                    $oneTask['startEditable'] = FALSE;
                    $oneTask['durationEditable'] = FALSE;
                }

//                $oneTask['url'] = '/calendar/task/view/task_id/' . $task['id'];
                $oneTask['url'] = '#';

                $task['start_date'] = str_replace(' 00:00:00', '', $task['start_date']);
                $task['end_date'] = str_replace(' 00:00:00', '', $task['end_date']);

                $oneTask['start'] = str_replace(' ', 'T', $task['start_date']);
                $oneTask['end'] = str_replace(' ', 'T', $task['end_date']);
                $calendarData[] = $oneTask;
            }
        }
        return $calendarData;
    }

    /**
     * order data for FullCalendar
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $user_id
     * @return array
     */
    private static function orderData($from, $to, $user_id) {
        $orderModel = new Calendar_Model_Orders();
        $contactModel = new Application_Model_DbTable_Contact();
        $session = new Zend_Session_Namespace('calendar_user_filter');

        if (!empty($session->user_id) && $contactModel->isUser($session->user_id) === FALSE) {
            $orders = $orderModel->getByPeriodClient($from, $to, $session->user_id);
        } else {
            $orders = $orderModel->getByPeriodManager($from, $to, $user_id);
        }

        $calendarData = [];
        if ($orders) {
            foreach ($orders as $order) {
                $oneOrder = [];
                $oneOrder['title'] = 'Заказ №' . $order['id'];
                $oneOrder['className'] = 'order';
                $oneOrder['start'] = str_replace(' ', 'T', $order['create_datetime']);
                $oneOrder['url'] = '/order/index/one/order_id/' . $order['id'];
                $oneOrder['startEditable'] = FALSE;
                $oneOrder['durationEditable'] = FALSE;
                $calendarData[] = $oneOrder;
            }
        }
        return $calendarData;
    }

    /**
     * mailer projects data for FullCalendar
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $user_id
     * @return array
     */
    private static function mailerData($from, $to, $user_id) {
        $mailerModel = new Calendar_Model_Mailer();
        $contactModel = new Application_Model_DbTable_Contact();
        $session = new Zend_Session_Namespace('calendar_user_filter');
        $calendarData = [];

        if (!empty($session->user_id) && $contactModel->isUser($session->user_id) === FALSE) {
            return $calendarData;
        }
        $mailer = $mailerModel->getByPeriodManager($from, $to, $user_id);
        if ($mailer) {
            foreach ($mailer as $mailerTask) {
                $oneMailerTask = [];
                $oneMailerTask['title'] = 'Рассылка: ' . $mailerTask['name'];
                $oneMailerTask['className'] = 'mailer';
                $oneMailerTask['start'] = str_replace(' ', 'T', $mailerTask['last_send']);
                $oneMailerTask['url'] = ' /mailer/index/view-archive/project_id/' . $mailerTask['id'];
                $oneMailerTask['startEditable'] = FALSE;
                $oneMailerTask['durationEditable'] = FALSE;
                $calendarData[] = $oneMailerTask;
            }
        }
        return $calendarData;
    }

    /**
     * mail data for FullCalendar
     * 
     * @param datetime $from
     * @param datetime $to
     * @param integer $user_id
     * @return array
     */
    private static function mailData($from, $to, $user_id) {
        $mailModel = new Calendar_Model_Mail();
        $contactModel = new Application_Model_DbTable_Contact();
        $session = new Zend_Session_Namespace('calendar_user_filter');

        if (!empty($session->user_id) && $contactModel->isUser($session->user_id) === FALSE) {
            $mail = $mailModel->getByPeriodClient($from, $to, $session->user_id);
        } else {
            $mail = $mailModel->getByPeriodManager($from, $to, $user_id);
        }
        $calendarData = [];
        if ($mail) {
            foreach ($mail as $mailTask) {
                $oneMailTask = [];
                $oneMailTask['title'] = 'Письмо: ' . $mailTask['name'];
                $oneMailTask['className'] = 'mail';
                $oneMailTask['id'] = $mailTask['code'];
                $oneMailTask['start'] = str_replace(' ', 'T', $mailTask['create_date']);
                $oneMailTask['url'] = '#';
                $oneMailTask['startEditable'] = FALSE;
                $oneMailTask['durationEditable'] = FALSE;
                $calendarData[] = $oneMailTask;
            }
        }
        return $calendarData;
    }

    public static function vizitData($from, $to, $user_id) {
        $vizitModel = new Calendar_Model_Vizits();
        $calendarData = [];

        $vizits = $vizitModel->getVizitsToCalendar($from, $to, $user_id);
        if ($vizits) {
            foreach ($vizits as $vizit) {
                $oneVizit = [];
                $oneVizit['title'] = 'Визит: ' . $vizit['name'];
                $oneVizit['id'] = $vizit['contact_id'];
                $oneVizit['className'] = 'vizit';
                $oneVizit['start'] = date('Y-m-d', strtotime($vizit['datetime_stamp']));
                $oneVizit['url'] = '#';
                $oneVizit['startEditable'] = FALSE;
                $oneVizit['durationEditable'] = FALSE;
                $calendarData[] = $oneVizit;
            }
        }
        return $calendarData;
    }

    /**
     * change task period by using FullCalendar methods
     * 
     * @param datetime $from
     * @param datetime $to
     * @param datetime $id      task id
     * @return boolean
     */
    public static function changeTaskPeriod($from, $to, $id) {
        $taskModel = new Calendar_Model_Tasks();
        $from = new DateTime($from);
        $to = new DateTime($to);
        if ($from == $to) {
            $to->add(new DateInterval('PT1H0S'));
        }
        $from = $from->format('Y-m-d H:i:s');
        $to = $to->format('Y-m-d H:i:s');
        $taskModel->updatePeriod($from, $to, $id);
        return TRUE;
    }

    /**
     * 
     * @param datetime $from
     * @param integer $user_id
     * @return array
     */
    public static function vizitDetails($from, $user_id) {
        $vizitsHistoryModel = new Calendar_Model_Vizits();
        $vizits = $vizitsHistoryModel->dayDetails($user_id, $from);
        return self::vizitFormatter($vizits);
    }

    /**
     * 
     * @param array $vizits
     * @return array
     */
    private static function vizitFormatter($vizits) {
        $types = [
            'product' => ['product/view', 'reviews/add', 'product/page'],
            'category' => ['category/view']
        ];
        $productModel = new Application_Model_DbTable_Products();
        $categoryModel = new Application_Model_DbTable_Categoryes();

        $products = [];
        $categories = [];
        $category_ids = $product_ids = [];

        foreach ($vizits as $vizit) {
            if (in_array($vizit['type'], $types['product'])) {
                $id_product = $productModel->getIdByUrl($vizit['value']);
                $products[$id_product['id']] = [
                    'id' => $id_product['id'],
                    'datetime_stamp' => $vizit['datetime_stamp']
                ];

                $product_ids[] = $id_product['id'];
            } elseif (in_array($vizit['type'], $types['category'])) {
                $id_category = $categoryModel->getIdByUrl($vizit['value']);
                $categories[$id_category['id']] = [
                    'id' => $id_category['id'],
                    'datetime_stamp' => $vizit['datetime_stamp']
                ];
                $category_ids[] = $id_category['id'];
            }
        }
        $unique_category_ids = array_unique($category_ids);
        $unique_product_ids = array_unique($product_ids);

        if (!empty($unique_product_ids)) {
            $products = self::vizitProductFormatter($productModel, $unique_product_ids, $products);
        }

        if (!empty($unique_category_ids)) {
            $categories = self::vizitCategoryFormatter($categoryModel, $unique_category_ids, $categories);
        }

        return [
            'products' => $products,
            'categories' => $categories
        ];
    }

    /**
     * 
     * @param Application_Model_DbTable_Products $productModel
     * @param array $unique_product_ids
     * @param array $products
     * @return array
     */
    private static function vizitProductFormatter(Application_Model_DbTable_Products $productModel, $unique_product_ids, $products) {
        $_products = $productModel->getProductsByIds($unique_product_ids);
        foreach ($products as &$p) {
            $p += [
                'name' => $_products[$p['id']]['name'],
                'url' => '/shop/products/edit/id/' . $p['id']
            ];
        }
        unset($p);
        return $products;
    }

    /**
     * 
     * @param Application_Model_DbTable_Categoryes $categoryModel
     * @param array $unique_category_ids
     * @param array $categories
     * @return array
     */
    private static function vizitCategoryFormatter(Application_Model_DbTable_Categoryes $categoryModel, $unique_category_ids, $categories) {
        $_categories = $categoryModel->getCategoriesByIds($unique_category_ids);
        foreach ($categories as &$c) {
            $c += array(
                'name' => $_categories[$c['id']]['name'],
                'url' => '/shop/products/view-products/category_id/' . $c['id'],
            );
        }
        unset($c);
        return $categories;
    }

    /**
     * 
     * @param string $code
     * @return boolean | array
     */
    public static function mailDetails($code) {
        $mailsModel = new Application_Model_DbTable_StatisticsMailerSendLog();
        if ($_mail = $mailsModel->fetchRow("code = '$code'")) {
            $file = new Crm_DataToFile();
            $mail = $file->readMail($_mail->code, new Zend_Date($_mail->create_date));
            if ($mail) {
                if ($report = Application_Model_DbTable_MailerReport::getByCode($_mail->code)) {
                    $mail['report'] = $report;
                }
                $mail['code'] = $_mail->code;
                return $mail;
            } else {
                return FALSE;
            }
        }
        return FALSE;
    }

}
