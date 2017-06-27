<?php

/**
 * Description of EmailGateway
 *
 * @author DartVadius
 */
class Unisender_Model_EmailGateway extends Zend_Db_Table_Abstract {

    protected $_name = 'email_gateway';

    public function getBySystemName($systemName) {
        $select = $this->select()
                ->from($this->_name)
                ->where('system_name=?', $systemName);
        if ($result = $this->fetchAll($select)) {
            return $result->toArray();
        }
        return FALSE;
    }

    /**
     * get all data from email_gateway table
     * 
     * @return array | boolean
     */
    public function getAll() {
        if ($result = $this->fetchAll()) {
            return $result->toArray();
        }
        return FALSE;
    }

    /**
     * get all data from email_gateway table formatted for config view page
     * 
     * @return array | boolean
     */
    public function getAllToConfig() {
        $data = $this->getAll();
        if (!empty($data)) {
            $config = [];
            foreach ($data as $param) {
                $config[$param['system_name']][$param['id']][$param['param_name']] = $param['param_value'];
            }
            return $config;
        }
        return FALSE;
    }
    
    public function getBySystemNameToConfig($systemName) {
        $data = $this->getBySystemName($systemName);
        if (!empty($data)) {
            $config = [];
            foreach ($data as $param) {
                $config[$param['system_name']][$param['id']][$param['param_name']] = $param['param_value'];
            }
            return $config;
        }
        return FALSE;
    } 

    public function updateGateway($data, $id) {
        $where = $this->getDefaultAdapter()->quoteInto('id=?', $id);
        $this->update($data, $where);
    }

}
