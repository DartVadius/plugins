<?php

/**
 * Description of UnisenderTemplateVotingVariants
 *
 * @author DartVadius
 */
class Unisender_Model_UnisenderTemplateVotingVariants extends Zend_Db_Table_Abstract {

    protected $_name = 'crm_unisender_template_voting_variants';

    public function getVariantsByTemplateId($id) {
        if ($result = $this->fetchAll("voting_id=$id")) {
            return $result->toArray();
        }
        return FALSE;
    }
    
}