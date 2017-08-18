<?php

class Calendar_Task {
    
    public function saveNotifications($post, $task_id) {
        
        $notificationsModel = new Application_Model_DbTable_Notifications();

        $translate = Zend_Registry::get('Zend_Translate');

        $notif_description = ($post['description'] != '') ? (': ' . $post['description']) : '';
        
        $notificationsModel->insert(
            array(
                'icon' => 'fa-calendar-plus-o',
                'title' => $translate->_('New task'),
                'description' => $post['title'] . $notif_description,
                'contact_id' => $post['responsible'],
                'important' => 1,
                'link_url' => serialize(array('module' => 'calendar', 'controller' => 'task', 'action' => 'view', 'task_id' => $task_id))
            )
        );
        
    }
    
}
