<?php

/**
 * Description of ContactListController
 *
 * @author DartVadius
 */
class Unisender_ContactListController extends Zend_Controller_Action {

    public function deleteAction() {
        $list_id = $this->_request->getParam('id');

        $unisenderDeliveryListModel = new Unisender_Model_UnisenderDeliveryList();
        $unisenderDeliveryContactsModel = new Unisender_Model_UnisenderDeliveryContacts();

        $unisenderDeliveryListModel->delete("id=$list_id");
        $unisenderDeliveryContactsModel->delete("list_id=$list_id");

        $this->_helper->redirector('index', 'index', 'unisender');
    }

    public function viewAction() {
        $mailerDeliveryListModel = new Unisender_Model_UnisenderDeliveryList();
        $mailerDeliveryContactsModel = new Unisender_Model_UnisenderDeliveryContacts();
        $identity = Zend_Auth::getInstance()->getStorage()->read();

        $list_id = $this->_request->getParam('id', null);

        if ($this->_request->isPost()) {
            $post = $this->_request->getPost();

            $name = trim($post['name']);
            $data = [
                'contact_id' => $identity['id'],
                'name' => $name,
                'description' => $post['description'],
            ];

            $mailerDeliveryListModel->update($data, "id=$list_id");

            $this->_helper->FlashMessenger($this->view->translate('The list has been saved'));
            $this->_helper->redirector->goToRoute(['module' => 'unisender', 'controller' => 'index', 'action' => 'index'], 'default', true);
        }
        $list = $mailerDeliveryListModel->fetchRow("id=$list_id")->toArray();
        $contacts = $mailerDeliveryContactsModel->getContactsByListId($list_id);
        $this->view->contacts = $contacts;
        $this->view->list = $list;
        $this->view->list_id = $list_id;
    }

}
