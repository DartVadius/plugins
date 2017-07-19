<?php

/**
 * Description of UnisenderDeliveryContacts
 *
 * @author DartVadius
 */
class Unisender_Model_UnisenderDeliveryContacts extends Zend_Db_Table_Abstract {

    protected $_name = 'crm_unisender_delivery_contacts';

    public function getContactsByListId($id) {
        $contactsModel = new Application_Model_DbTable_Contact();
        $statuses = $contactsModel->getStatuses('not_show');

        $where = $this->getDefaultAdapter()->quoteInto('dc.list_id=?', $id);
        $sql = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('dc' => $this->_name))
                ->join(array('c' => 'wa_contact'), 'dc.contact_id = c.id', array('name'))
                ->joinLeft(array('ce' => 'wa_contact_emails'), 'dc.contact_id = ce.contact_id AND sort = 0', array('email'))
                ->joinLeft(array('cs' => 'crm_mailer_contact_settings'), 'dc.contact_id = cs.contact_id', array('news_general', 'news_company', 'interest', 'interest_set', 'time_period', 'interest_rand'))
                ->where($where)
                ->where("c.status NOT IN ('" . implode("','", $statuses) . "')");

        //echo $sql->assemble();
        return $this->getDefaultAdapter()->fetchAssoc($sql);
    }

}
