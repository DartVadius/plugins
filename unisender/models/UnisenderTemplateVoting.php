<?php

/**
 * Description of UnisenderTemplateVoting
 *
 * @author DartVadius
 */
class Unisender_Model_UnisenderTemplateVoting extends Zend_Db_Table_Abstract {

    protected $_name = 'crm_unisender_template_voting';

    public function getAvailable() {
        $identity = Zend_Auth::getInstance()->getStorage()->read();
        $contact_id = $identity['id'];
        if ($identity['role'] == 'admin') {
            $html = $this->fetchAll()->toArray();
        } else {
            $html = $this->fetchAll(["contact_id" => $contact_id])->toArray();
        }
        if (!empty($html)) {
            $newHtml = [];
            foreach ($html as $key => $value) {
                $newHtml[$key] = $value;
                $newHtml[$key]['action'] = 'voting';
                $newHtml[$key]['type'] = 'voting';
                $date = DateTime::createFromFormat('Y-m-d H:i:s', $value['modify_datetime']);
                $newHtml[$key]['modify_datetime'] = $date->format('d-m-Y H:i');
                if ($contact_id == $value['contact_id']) {
                    $newHtml[$key]['editable'] = 1;
                }
            }
            return $newHtml;
        }
        return FALSE;
    }
    
    public function getById($id) {
        return $this->fetchRow(["id" => $id])->toArray();
    }

}
