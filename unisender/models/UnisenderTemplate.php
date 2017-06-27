<?php

/**
 * Description of UnisenderTemplate
 *
 * @author DartVadius
 */
class Unisender_Model_UnisenderTemplate extends Zend_Db_Table_Abstract {

    protected $_name = 'crm_unisender_template';

    public function getAll() {

//        $sql = $this->select();
        if ($result = $this->fetchAll()->toArray()) {
            return $result;
        }
        return FALSE;
    }

    public function add($data) {
        if ($result = $this->insert($data)) {
            return $result;
        }
        return FALSE;
    }

    public function getById($template_id) {
        if ($result = $this->fetchRow(['id=?' => $template_id])->toArray()) {
            return $result;
        }
        return FALSE;
    }

    public function editTemplate($data, $template_id) {
        if ($result = $this->update($data, ['id=?' => $template_id])) {
            return $result;
        }
        return FALSE;
    }

    public function removeTemplate($template_id) {
        return $this->delete("id=$template_id");
    }

}
