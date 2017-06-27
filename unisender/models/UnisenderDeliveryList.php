<?php

/**
 * Description of UnisenderDeliveryList
 *
 * @author DartVadius
 */
class Unisender_Model_UnisenderDeliveryList extends Zend_Db_Table_Abstract {

    protected $_name = 'crm_unisender_delivery_list';

    /**
     * 
     * @param string $listName
     * @return array
     */
    public function saveNewContactList($listName) {
        $identity = Zend_Auth::getInstance()->getStorage()->read();
        $response = [];
        try {
            $list_id = $this->insert(
                    [
                        'contact_id' => $identity['id'],
                        'name' => $listName
                    ]
            );
            if ($list_id) {
                $response['status'] = 'contact_list_saved';
                $response['url'] = 'http://' . Zend_Registry::get('crmDomain') . '/unisender/contact-list/view/id/' . $list_id;
            }
        } catch (Exception $exc) {
            $response['status'] = $exc->getMessage();
        }

        return $response;
    }

    /**
     * get contact lists
     * if admin, then return all lists, else only lists created by current user
     * 
     * @return boolean | array
     */
    public function getContactLists() {
        $identity = Zend_Auth::getInstance()->getStorage()->read();
        $translate = Zend_Registry::get('Zend_Translate');

        if ($identity['role'] == 'admin') {
            $contacts_lists = $this->fetchAll()->toArray();
        } else {
            $contacts_lists = $this->fetchAll('contact_id=' . $identity['id'])->toArray();
        }
        if (!empty($contacts_lists)) {
            foreach ($contacts_lists as $id => $value) {
                $contacts_lists[$id]['type'] = 'contact_list';
                $contacts_lists[$id]['type_name'] = $translate->_('Contact list');
            }
            return $contacts_lists;
        }
        return FALSE;
    }

    public function getContactListsForStatistic($contactId = NULL) {
        $select = $this->select()
                ->setIntegrityCheck(FALSE)
                ->from($this->_name)
                ->joinLeft('wa_contact', 'wa_contact.id=crm_unisender_delivery_list.contact_id', 'wa_contact.name as list_owner');
        if ($contactId) {
            $select->where("contact_id=$contactId");
        }
        $lists = [];
        if ($contacts_lists = $this->fetchAll($select)) {
            $lists = $contacts_lists->toArray();
        }
        if (!empty($lists)) {
            $result = [];
            foreach ($lists as $list) {
                $result[$list['unis_contact_list_id']] = $list;
            }
            return $result;
        }
        return FALSE;
    }

    public function getListById($id) {
        return $this->fetchRow("id=$id")->toArray();
    }

    public function getContactList($id) {
        $sql = $this->select()
                ->where("id = $id");

        $contact_list = $this->getDefaultAdapter()->fetchAssoc($sql);
        if (!empty($contact_list)) {
            $mailerDeliveryContactsModel = new Unisender_Model_UnisenderDeliveryContacts();
            $contact_list_keys = implode(',', array_keys($contact_list));
            $sql = $mailerDeliveryContactsModel->select()
                    ->setIntegrityCheck(false)
                    ->from(array('mdc' => 'crm_unisender_delivery_contacts'))
                    ->joinLeft(array('c' => 'wa_contact'), 'mdc.contact_id = c.id', array('name'))
                    ->joinLeft(array('ce' => 'wa_contact_emails'), 'mdc.contact_id = ce.contact_id AND sort=0', array('email'))
                    ->where("mdc.list_id IN ($contact_list_keys)");
            $contacts = $mailerDeliveryContactsModel->fetchAll($sql)->toArray();
            foreach ($contacts as $value) {
                if (!empty($value['name'])) {
                    $contact_list[$value['list_id']]['contacts'][$value['contact_id']] = array('contact_id' => $value['contact_id'], 'name' => $value['name'], 'email' => $value['email']);
                }
            }
        }
        $sql = $mailerDeliveryContactsModel->select()
                ->setIntegrityCheck(false)
                ->from('crm_mailer_contact_settings')
                ->where('news_general=0')
                ->orWhere('news_company=0');
        $contactSettings = $mailerDeliveryContactsModel->fetchAll($sql)->toArray();
        $keys = array_keys($contact_list[$id]['contacts']);

        foreach ($contactSettings as $contact) {
            if (in_array($contact['contact_id'], $keys)) {
                unset($contact_list[$id]['contacts'][$contact['contact_id']]);
            }
        }
        return $contact_list;
    }

    /**
     * 
     * @param int $id   mail list ID
     * @return array
     */
    public function getContactListForGateway($id) {
        return $this->getContactList($id)[$id];
    }

}
