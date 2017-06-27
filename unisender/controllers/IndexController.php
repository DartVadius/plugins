<?php

class Unisender_IndexController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */

        $this->view->headTitle($this->view->translate('Unisender'), 'PREPEND');

        $smtpModel = new Application_Model_DbTable_ContactSmtp();
        $identity = Zend_Auth::getInstance()->getStorage()->read();
        if (is_null($smtpModel->fetchRow('contact_id=' . $identity['id']))) {
            $this->_helper->redirector('contact-mailer', 'settings', 'crm');
        }
    }

    public function indexAction() {
        $gateway = new Unisender_Model_EmailGateway();
        $config = $gateway->getBySystemName('Unisender')[0];
        if (empty($config['param_value'])) {
            $this->_helper->redirector->gotoUrl("/unisender/index/config");
        }
        $identity = Zend_Auth::getInstance()->getStorage()->read();
        $unisenderDeliveryListModel = new Unisender_Model_UnisenderDeliveryList();
        $templateHtmlModel = new Unisender_Model_UnisenderTemplateHtml();
        $templateVotingModel = new Unisender_Model_UnisenderTemplateVoting();

        $contacts_lists = $unisenderDeliveryListModel->getContactLists();

        $html = $templateHtmlModel->getAvailable();
        $voting = $templateVotingModel->getAvailable();
        $subjects = Unisender_Service::getMergedArray($html, $voting);

        $this->view->subjects = $subjects;
        $this->view->contact_lists = $contacts_lists;
        $this->view->identity = $identity;
    }

    /**
     * index config page
     */
    public function configAction() {
        $gateway = new Unisender_Model_EmailGateway();
        $config = $gateway->getAllToConfig();
        $this->view->config = $config;
    }

    /**
     * update config
     */
    public function updateAction() {
        $gateway = new Unisender_Model_EmailGateway();
        $connector = new Unisender_Connector();
        $connector->createField('user_id', 'ID пользователя', 'string');
        if ($this->getRequest()->isXmlHttpRequest() && $post = $this->_request->getPost()) {
            Zend_Controller_Action_HelperBroker::removeHelper('viewRenderer');
            foreach ($post as $id => $param) {
                $data = ['param_value' => $param];
                $gateway->updateGateway($data, $id);
            }
        }
    }

    /**
     * project create page
     */
    public function projectAction() {
        $unisenderDeliveryListModel = new Unisender_Model_UnisenderDeliveryList();
        $templateHtmlModel = new Unisender_Model_UnisenderTemplateHtml();
        $templateVotingModel = new Unisender_Model_UnisenderTemplateVoting();

        $params = $this->_request->getParams();

        list($template['id'], $template['type']) = explode('&', $params['template']);
        list($list['type'], $list['id']) = explode('&', $params['recipients']);

        $contacts = $unisenderDeliveryListModel->getListById($list['id']);

        if ($template['type'] == 'html') {
            $html = $templateHtmlModel->getById($template['id']);
        } else {
            $html = $templateVotingModel->getById($template['id']);
            $html['html'] = $html['text'];
        }

        $this->view->project = $html;
        $this->view->template_id = $template['id'];
        $this->view->template_type = $template['type'];
        $this->view->contact_list_id = $list['id'];
        $this->view->contact_list = $contacts['name'];
    }

    /**
     * run project
     */
    public function sendAction() {
        $contactModel = new Application_Model_DbTable_Contact();
        $connector = new Unisender_Connector();

        if ($this->_request->isPost()) {
            $params = $this->_request->getPost();
            $project_id = Unisender_Service::createProject($params);
            $contactList = Unisender_Service::setUpContactList($connector, $params);
            $localContactList = Unisender_Service::synchronizeContacts($connector, $params, $project_id);
            $body = Unisender_Service::createEmailBody($contactList['contact_id'], $params['template_type'], $params['template_id'], $project_id);
            $manager_data = $contactModel->getContact($contactList['contact_id']);
            $company = Unisender_Service::getCompanyData();
            $manager = Unisender_Service::createManager($manager_data, $company);
            $mail = Unisender_Service::createMail($manager, $body, $params);
            $unisender_message_id = $connector->createEmail($mail)->getMessageId();
            $unisender_campaign_id = $connector->sendEmail($mail)->getCampaignId();
            if (is_array($unisender_message_id) || is_array($unisender_campaign_id)) {
                $this->_helper->FlashMessenger($this->view->translate(serialize([$unisender_campaign_id, $unisender_message_id])));
                $this->_helper->redirector->goToRoute(['module' => 'unisender', 'controller' => 'index', 'action' => 'project'], 'default', true);
            } else {
                if($params['template_type'] == 'voting') {
                    Unisender_ServiceStatistic::saveVoting($project_id, $localContactList);
                }
                Unisender_Service::updateProject($project_id, $unisender_message_id, $unisender_campaign_id);
                $this->_helper->FlashMessenger($this->view->translate('The project has been created'));
                $this->_helper->redirector->goToRoute(['module' => 'unisender', 'controller' => 'index', 'action' => 'index'], 'default', true);
            }
        }
    }

}
