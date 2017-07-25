<?php

/**
 * Description of Banner
 *
 * @author DartVadius
 */
class BannerShowcase_Model_Banner extends Zend_Db_Table_Abstract {

    protected $_name = 'banner';

    /**
     * upload file and save data in db
     * 
     * @param array $post
     * @return boolean
     */
    public function save($post) {
        $folder = APPLICATION_PATH . '/plugins/frontend/bannerShowcase/banners/';
        $file = $folder . basename($_FILES['file']['name']);

        if (!empty($post['banner_id']) && !empty($post['url']) && move_uploaded_file($_FILES['file']['tmp_name'], $file)) {
            $product = $category = NULL;
            if (!empty($post['shop_category'])) {
                $category = json_encode($post['shop_category']);
            }
            if (!empty($post['shop_product'])) {
                $product = json_encode($post['shop_product']);
            }
            try {
                $this->insert([
                    'name' => $post['banner_id'],
                    'url' => $post['url'],
                    'image_name' => basename($_FILES['file']['name']),
                    'category' => $category,
                    'product' => $product,
                ]);
                return TRUE;
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }
        }
        return FALSE;
    }
    
    public function edit($post) {
        $folder = APPLICATION_PATH . '/plugins/frontend/bannerShowcase/banners/';
        if (!empty($_FILES['file']['name'])) {
            $file = $folder . basename($_FILES['file']['name']);
            move_uploaded_file($_FILES['file']['tmp_name'], $file);
            $name = basename($_FILES['file']['name']);
        } else {
            $name = $post['file_name'];
        }

        if (!empty($post['banner_id']) && !empty($post['url'])) {
            $product = $category = NULL;
            if (!empty($post['shop_category'])) {
                $category = json_encode($post['shop_category']);
            }
            if (!empty($post['shop_product'])) {
                $product = json_encode($post['shop_product']);
            }
            try {
                $this->update([
                    'name' => $post['banner_id'],
                    'url' => $post['url'],
                    'image_name' => $name,
                    'category' => $category,
                    'product' => $product,
                ], "id={$post['id']}");
                return TRUE;
            } catch (Exception $ex) {
                echo $ex->getMessage();
            }
        }
        return FALSE;
    }

    /**
     * 
     * @return boolean | array
     */
    public function getAll() {
        try {
            return $this->fetchAll()->toArray();
        } catch (Exception $ex) {
            return FALSE;
        }
    }
    
    /**
     * 
     * @param string $name          name of the banner
     * @return boolean | array
     */
    public function getByName($name) {
        $sql = $this->select()->where('name=?', $name);
        if (empty($this->fetchRow($sql))) {
            return FALSE;
        }
        return $this->fetchRow($sql)->toArray();
    }
    
    /**
     * 
     * @param integer $id
     * @return boolean
     */
    public function deleteById($id) {
        if (!empty($id)) {
            $this->delete("id=$id");
            return TRUE;
        }
        return FALSE;
    }

    /**
     * forming html string for view
     * 
     * @param string $name              name of the banner
     * @param integer | NULL $width     setup image width
     * @return boolean | string         html string for view
     */
    public function showBanner($name, $width = NULL) {

        $sql = $this->select()->where('name=?', $name);
        if (empty($this->fetchRow($sql))) {
            return FALSE;
        }
        $banner = $this->fetchRow($sql)->toArray();
        $size = '';
        if (!empty($width)) {
            $size = "width='{$width}'";
        }
        return "<a href='{$banner['url']}' id='{$banner['name']}'><img src='/banner-display?name={$banner['image_name']}' $size /></a>";
    }

    /**
     * 
     * @param string $name          string for searching
     * @param string $table         name of the table, where we searching
     * @param array $checkbox       array of ID`s, which included in result of request
     * @return array
     */
    public static function search($name, $table, $checkbox = NULL) {
        $productModel = new Application_Model_DbTable_Products();
        $sql = $productModel->select()->setIntegrityCheck(FALSE)
                ->from($table)
                ->where('name LIKE ?', "%$name%");
        if (!empty($checkbox)) {
            $sql->orWhere("id IN ($checkbox)")->group('id');
        }

        return $productModel->fetchAll($sql)->toArray();
    }
    
    public function getCategoriesById($id) {
        $sql = $this->select()->where('id=?', $id);
        if (empty($this->fetchRow($sql))) {
            return FALSE;
        }
        $data = $this->fetchRow($sql)->toArray();
        if (empty($data['category'])) {
            return FALSE;
        }
        return json_decode($data['category'], TRUE);
    }
    
    public function getProductsById($id) {
        $sql = $this->select()->where('id=?', $id);
        if (empty($this->fetchRow($sql))) {
            return FALSE;
        }
        $data = $this->fetchRow($sql)->toArray();
        if (empty($data['product'])) {
            return FALSE;
        }
        return json_decode($data['product'], TRUE);
    }
    
    /**
     * 
     * @param string $id          name of the banner
     * @return boolean | array
     */
    public function getById($id) {
        $sql = $this->select()->where('id=?', $id);
        if (empty($this->fetchRow($sql))) {
            return FALSE;
        }
        return $this->fetchRow($sql)->toArray();
    }

}
