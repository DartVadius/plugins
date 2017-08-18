<?php

/**
 * Description of Calendar_SessionService
 *
 * @author DartVadius
 */
class Calendar_SessionService {

    /**
     * change session filters to rendering data in FullCalendar
     * 
     * @param array $filter
     * @return boolean
     */
    public static function setFilterSession($filter) {
        $session = new Zend_Session_Namespace('calendar_filter');
        $session->unsetAll();
        $session->init = TRUE;
        $session->filter = [];
        if (!empty($filter)) {
            foreach ($filter as $type) {
                if(is_numeric($type)) {
                    $session->filter['order_status'][$type] = TRUE;
                } else {
                    $session->filter[$type] = TRUE;
                }
                
            }
        }
        return TRUE;
    }

    /**
     * 
     * @param integer $user_id
     * @param string $user_name
     * @return boolean
     */
    public static function setIdFilterSession($user_id, $user_name) {
        $session = new Zend_Session_Namespace('calendar_user_filter');
        $session->user_id = $user_id;
        $session->user_name = $user_name;
        return TRUE;
    }

    public static function clearIdFilterSession() {
        $session = new Zend_Session_Namespace('calendar_user_filter');
        $session->unsetAll();
        return TRUE;
    }

    /**
     * set up session filters if it not initializing
     * 
     * @return boolean
     */
    public static function initFilterSession($order_statuses) {
        $session = new Zend_Session_Namespace('calendar_filter');
        if (!$session->init) {
            $session->init = TRUE;
            $session->filter['task'] = TRUE;
            $session->filter['order'] = TRUE;
            $session->filter['mail'] = TRUE;
            $session->filter['mailer'] = TRUE;
            if (!$order_statuses) {
                return TRUE;
            }
            foreach ($order_statuses as $status) {
                $session->filter['order_status'][$status['id']] = TRUE;
            }
        }
//        print_r($session->filter);
//        die;
        return TRUE;
    }

}
