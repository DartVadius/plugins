<?php

/**
 * Description of Heybro
 *
 * @author DartVadius
 */
class Heybro_Model_Heybro extends Zend_Db_Table_Abstract {
    protected $_name = 'heybro_plugin';
    protected $_sequence = false;
    
    /**
     * check existing of the code in db 
     * 
     * @param string $code
     * @return boolean | object
     */
    public function checkExist($code) {
        $sql = $this->select()->where("code = ?",  $code)->limit(1);
        return $this->fetchRow($sql);
    }
    
    /**
     * get data by order id
     * 
     * @param int $id
     * @return boolean | array
     */
    public function getProductsByOrderId($id) {
        $sql = $this->select()->where('order_id = ?', $id);
        if ($res = $this->fetchAll($sql)) {
            return $res->toArray();
        }
        return FALSE;
    }
    
    /**
     * get data with contact and product info
     * 
     * @return boolean | array
     */
    
    public function getAllFullInfo() {
        $sql = $this->select()->setIntegrityCheck(FALSE)->from($this->_name)
                ->joinLeft('wa_contact', 'wa_contact.id = heybro_plugin.user_id')
                ->joinLeft('shop_product', 'shop_product.id = heybro_plugin.product_id', 'shop_product.name as product_name');
        if ($res = $this->fetchAll($sql)) {
            return $res->toArray();
        }
        return FALSE;
    }
    
    /**
     * get array of cards by user id
     * 
     * @param int $id       user Id
     * @param int $ready    0 or 1 (if 1 -> get edited images, if 0 -> get not edited images)
     * @return boolean
     */
    public function getCardsByUserId($id, $ready = 1) {
        $sql = $this->select()->setIntegrityCheck(FALSE)->from($this->_name)
                ->where('user_id = ?', $id)
                ->where('ready_img = ?', $ready)
                ->joinLeft('shop_product', 'shop_product.id = heybro_plugin.product_id', 'shop_product.name as product_name');
        if ($res = $this->fetchAll($sql)) {
            return $res->toArray();
        }
        return FALSE;
    }
    
    /**
     * set 'ready' flag by card code
     * 
     * @param string $code
     */
    public function setEditTrueByCode($code) {
        $DB = Zend_Db_Table_Abstract::getDefaultAdapter();
        try {
            $DB->update($this->_name, ['ready_img' => 1], ['code = ?' => "$code"]);
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
        
    }
}
