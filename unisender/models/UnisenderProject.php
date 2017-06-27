<?php

/**
 * Description of UnisenderProject
 *
 * @author DartVadius
 */
class Unisender_Model_UnisenderProject extends Zend_Db_Table_Abstract {

    protected $_name = 'crm_unisender_project';

    public function getProjects() {
        $identity = Zend_Auth::getInstance()->getStorage()->read();
        if ($identity['role'] == 'admin') {
            $projects = $this->fetchAll()->toArray();
        } else {
            $identity_id = $identity['id'];
            $projects = $this->fetchAll("contact_id = '$identity_id'")->toArray();
        }
        return $projects;
    }
    
    /**
     * 
     * @param integer $id   Unisender campaign Id
     * @return array
     */
    public function getProjectByCampaignId($id) {
        return $this->fetchRow("unisender_campaign_id = $id")->toArray();
    }
    
}