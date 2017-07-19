<?php

/**
 * Description of AjaxController
 *
 * @author DartVadius
 */
class Unisender_AjaxController extends Zend_Controller_Action {

    public function saveNewContactListAction() {
        $unisenderDeliveryListModel = new Unisender_Model_UnisenderDeliveryList();
        $post = $this->_request->getPost();
        
        $response = $unisenderDeliveryListModel->saveNewContactList($post['unisender_list_name']);

        $this->_helper->json(array('status' => $response['status'], 'url' => $response['url']));
    }
    
    public function filterCommonStatisticAction() {
        $post = $this->_request->getPost();
        $connector = new Unisender_Connector();
        $list = new Unisender_Model_UnisenderDeliveryList();
        
        $lists = $list->getContactListsForStatistic();
        $limit = '';
        
        if (!empty($post['from'])) {
            $from = $post['from'] . ' 00:00:00';
        }
        if (!empty($post['to'])) {
            $to = $post['to'] . ' 23:59:59';
        }
        
        if (empty($post['from']) && empty($post['to'])) {
            $limit = 25;
        }
        
        $campaigns = $connector->getCampaigns($from, $to, $limit);
        
        $data = Unisender_ServiceStatistic::setIndexStat($lists, $campaigns);
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/modules/unisender/views/scripts/partials/');
        $html->assign('data', $data);
        $page = $html->render('stat.phtml');
        $this->_helper->json(['data' => $page]);
    }

}
