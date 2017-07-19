<?php

/**
 * Description of HeybroProduct
 *
 * @author DartVadius
 */
class Heybro_Model_HeybroProduct extends Zend_Db_Table_Abstract {

    protected $_name = 'heybro_product';

    /**
     * 
     * @param string $name
     * @param array $checkbox
     * @return array
     */
    public static function searchProduct($name, $checkbox = NULL) {
        $productModel = new Application_Model_DbTable_Products();
        $sql = $productModel->select()->setIntegrityCheck(FALSE)
                ->from('shop_product')
                ->where('name LIKE ?', "%$name%");
        if (!empty($checkbox)) {
            $sql->orWhere("id IN ($checkbox)")->group('id');
        }

        return $productModel->fetchAll($sql)->toArray();
    }

    public function getProductByUrl($url) {
        $productModel = new Application_Model_DbTable_Products();
        $sql = $productModel->select()->setIntegrityCheck(FALSE)
                ->from('shop_product')
                ->where('url=?', $url);
        return $productModel->fetchRow($sql)->toArray();
    }

    /**
     * 
     * @param array $post
     * @return boolean
     */
    public function save($post) {
        $this->delete('');
        if (empty($post)) {
            return FALSE;
        }
        foreach ($post as $type => $value) {
            if (empty($value)) {
                continue;
            }
            foreach ($value as $name => $id) {
                $this->insert([
                    'product_id' => $id,
                    'product_name' => $name,
                    'product_type' => $type,
                ]);
            }
        }
        return TRUE;
    }

    /**
     * 
     * @param string $type
     * @return array
     */
    public function findByType($type) {
        $sql = $this->select()
                ->where('product_type = ?', $type);

        return $this->fetchAll($sql)->toArray();
    }

    /**
     * 
     * @param string $type
     * @return array
     */
    public function findProductIdByType($type) {
        $sql = $this->select()->from($this->_name, 'product_id')
                ->where('product_type = ?', $type);
        return $this->fetchAll($sql)->toArray();
    }

}
