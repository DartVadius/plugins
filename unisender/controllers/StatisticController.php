<?php

/**
 * Description of StatisticController
 *
 * @author DartVadius
 */
class Unisender_StatisticController extends Zend_Controller_Action {

    public function indexAction() {
        $connector = new Unisender_Connector();
        $list = new Unisender_Model_UnisenderDeliveryList();

        $lists = $list->getContactListsForStatistic();
        $campaigns = $connector->getCampaigns();

        $data = Unisender_ServiceStatistic::setIndexStat($lists, $campaigns);
        $this->view->data = $data;
        $html = new Zend_View();
        $html->setScriptPath(APPLICATION_PATH . '/modules/unisender/views/scripts/partials/');
        $html->assign('data', $data);
        $stat = $html->render('stat.phtml');
        $this->view->stat = $stat;
    }

    public function projectStatisticAction() {
        $connector = new Unisender_Connector();
        $projectModel = new Unisender_Model_UnisenderProject();
        $recipientsModel = new Unisender_Model_UnisenderDeliveryContacts();
        $post = $this->_request->getParams();
        $project_id = $post['id'];
        if (!empty($project_id)) {
            $commonStats = $connector->getCommonStats($project_id);
            $project = $projectModel->getProjectByCampaignId($project_id);
            $contacts = $recipientsModel->getContactsByListId($project['recipients_list_id']);

            $deliveryStats = $connector->getDeliveryStats($project_id);
            $deliveryStats = Unisender_ServiceStatistic::setProjectContacts($deliveryStats, $contacts);

            $links = $connector->getVisitedLinksStatistic($project_id);
            $links = Unisender_ServiceStatistic::setLinks($links, $contacts);
            $voting = NULL;
            if ($project['template_type'] == 'voting') {
                $voting = Unisender_ServiceStatistic::getVotingResult($project);
            }
            $linksCount = Unisender_ServiceStatistic::getLinksCount($links);
            $this->view->project = $project;
            $this->view->delivery_stats = $deliveryStats;
            $this->view->common_stats = $commonStats;
            $this->view->links = $links;
            $this->view->voting = array_reverse($voting);
            $this->view->links_count = $linksCount;
        } else {
            $this->_helper->redirector->gotoUrl("/unisender/index");
        }
    }

    public function countVotingAction() {

        $params = $this->_request->getParams();
        Unisender_ServiceStatistic::updateVoting($params);

        $this->view->setScriptPath(APPLICATION_PATH . '/../public/themes/' . Zend_Registry::get('theme'));
        $this->_helper->layout->disableLayout();
    }

}
