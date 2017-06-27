<?php

/**
 * Description of UnisenderVotingAnswer
 *
 * @author DartVadius
 */
class Unisender_Model_UnisenderVotingAnswer extends Zend_Db_Table_Abstract {
    protected $_name = 'crm_unisender_voting_answer';
    
    public function getVoteByProjectId($id) {
        if ($result = $this->fetchAll("project_id=$id")) {
            return $result->toArray();
        }
        return FALSE;
    }
}
