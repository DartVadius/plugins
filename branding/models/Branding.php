<?php

/**
 * Description of Branding
 *
 * @author DartVadius
 */
class Branding_Model_Branding {

    private $router;
    private $request;

    public function __construct() {
        $this->router = new Zend_Controller_Router_Rewrite();
        $this->request = new Zend_Controller_Request_Http();
        $this->router->route($this->request);
    }

    /**
     * get array of all selectable product features with varchar type and color type
     *
     * @return boolean|array
     */
    public function getFeatures() {
        $features = new Application_Model_DbTable_Feature();
        $select = $features->select()->setIntegrityCheck(FALSE)->from('shop_feature');
        $select->where('selectable = "1" AND multiple = "0" AND (type = "varchar" OR type = "color")');
        $result = $features->fetchAll($select);
        if ($result) {
            return $result->toArray();
        }
        return FALSE;
    }

    /**
     * get config from crm_plugins_settings table
     *
     * @return array
     */
    public function getConfig() {
        $plugin = new Application_Model_DbTable_Plugins();
        return $plugin->getBySettingsBySystemName('branding');
    }

    /**
     *
     * @param array $post
     * @return boolean
     */
    public function saveConfig($post) {
        $plugin = new Application_Model_DbTable_Plugins();
        $plugConf = new Application_Model_DbTable_PluginsSettings();

        $pluginId = $plugin->getBySystemName('branding')['id'];

        if (isset($post['brand'])) {
            $plugConf->saveSettings($pluginId, 'brand', $post['brand']);
        }
        if (isset($post['manufacturer'])) {
            $plugConf->saveSettings($pluginId, 'manufacturer', $post['manufacturer']);
        }
        return TRUE;
    }

    public function getUrl() {
        return $this->request->getScheme() . '://' . Zend_Registry::get('rootDomain');
    }

    /**
     * get list of brand feature values
     *
     * @return array|boolean
     */
    public function getBrandList() {
        return $this->getList($this->getConfig()['brand']);
    }

    /**
     * get list of manufacturer feature values
     *
     * @return array
     */
    public function getManufacturerList() {
        return $this->getList($this->getConfig()['manufacturer']);
    }

    /**
     * get list of feature values by feature id
     *
     * @param int $id
     * @return boolean|array
     */
    private function getList($id) {

        $features = new Application_Model_DbTable_Feature();
        $select = $features->select()->setIntegrityCheck(FALSE)->from('shop_feature');
        $select->joinLeft('shop_feature_values_color', 'shop_feature.id = shop_feature_values_color.feature_id', ['color_id' => 'id', 'picture', 'color_value' => 'value']);
        $select->joinLeft('shop_feature_values_varchar', 'shop_feature.id = shop_feature_values_varchar.feature_id', ['varchar_id' => 'id', 'varchar_value' => 'value']);
        $select->where('shop_feature.id = ?', $id);

        if ($result = $features->fetchAll($select)) {
            $values = $result->toArray();
        } else {
            return FALSE;
        }
        $list = [];
        foreach ($values as $key => $value) {
            $list[$key]['id'] = $value['id'];
            $list[$key]['name'] = $value['name'];
            if ($value['type'] == 'color') {
                $list[$key]['feature_id'] = $value['id'];
                $list[$key]['feature_value_id'] = $value['color_id'];
                $list[$key]['feature_value'] = $value['color_value'];
                $list[$key]['feature_picture'] = $value['picture'] . '/img_200x0.jpg';
            } else {
                $list[$key]['feature_id'] = $value['id'];
                $list[$key]['feature_value_id'] = $value['varchar_id'];
                $list[$key]['feature_value'] = $value['varchar_value'];
            }
        }

        return $list;
    }

    /**
     * get list of products selected by brand value
     *
     * @param int $val_id
     * @return array|boolean
     */
    public function getBrandProducts($val_name, $params = NULL) {
        return $this->getProducts($val_name, $this->getConfig()['brand'], $params);
    }

    /**
     * get list of products selected by manufacturer value
     *
     * @param int $val_id
     * @return array|boolean
     */
    public function getManufacturerProducts($val_name, $params = NULL) {
        return $this->getProducts($val_name, $this->getConfig()['manufacturer'], $params);
    }

    /**
     * get list of products selected by feature value name
     *
     * @param int $val_name
     * @param int $feature_id
     * @return boolean|array
     */
    private function getProducts($val_name, $feature_id, $params) {
        $product = new Application_Model_DbTable_ProductFeatures();
        $siteCurrency = Frontend_Utils::getSiteCurrency();
        if (!empty($val_name) && !empty($feature_id)) {

//get feature to find out its type 
            $select = $product->select()->setIntegrityCheck(FALSE)->from('shop_feature');
            $select->where('id =?', $feature_id);
            $feature = $product->fetchRow($select)->toArray();

//by feature type set up table name
            $table = (($feature['type'] == 'varchar') ? 'shop_feature_values_varchar' : 'shop_feature_values_color');

//find feature by name
            $select = $product->select()->setIntegrityCheck(FALSE)->from($table);
            $select->where('value =?', $val_name)->limit(1);
            $value = $product->fetchRow($select)->toArray();


// find products by feature id and feature value id
            $select = $product->select()->setIntegrityCheck(FALSE)->from('shop_product');
            $select->join('shop_product_features', 'shop_product.id = shop_product_features.product_id', ['feature_id', 'feature_value_id', 'product_id']);

//<--add filters from _get parameters 
            if (!empty($params['price_max'])) {
                $price_max = Frontend_Utils::priceUA($params['price_max'], $siteCurrency);
                $select->where('shop_product.price <= ?', $price_max);
            }

            if (!empty($params['price_min'])) {
                $price_min = Frontend_Utils::priceUA($params['price_min'], $siteCurrency);
                $select->where('shop_product.price >= ?', $price_min);
            }

            if (!empty($params['category'])) {
                $categories = implode(',', $params['category']);
                $select->where('shop_product.category_id IN (' . $categories . ')');
            }
//<--end


            $select->where('shop_product_features.feature_value_id = ?', $value['id']);
            $select->where('shop_product_features.feature_id = ?', $feature_id);
            $select->where('shop_product.status = ?', 1);
            
//<-- add sorting filters
            if (isset($params['sort'])) {
                switch ($params['sort']) {
                    case 'shop_product.name':
                        $select->order('shop_product.name');
                        break;
                    case 'price_asc':
                        $select->order('shop_product.price');
                        break;
                    case 'price_desc':
                        $select->order('shop_product.price DESC');
                        break;
                    case 'rating':
                        $select->order('shop_product.rating DESC');
                        break;
                    case 'date':
                        $select->order('shop_product.create_datetime DESC');
                        break;
                    default:
                        break;
                }
            }
//<--end
            
            if ($result = $product->fetchAll($select)) {
                $category = new Application_Model_DbTable_Categoryes();
                $products = new Application_Model_DbTable_Products();

                $newResult = [];
                $result = $result->toArray();
                foreach ($result as $key => $value) {
                    $newResult[$key] = $value;
                    $newResult[$key]['category_name'] = $category->getCategoryById($value['category_id'])['name'];

                    $select = $product->select()->setIntegrityCheck(FALSE)->from('shop_product_skus');
                    $select->where('shop_product_skus.product_id = ?', $value['id']);
                    $skus = $product->fetchAll($select)->toArray();
                    foreach ($skus as $sku) {
                        $newResult[$key]['skus'][$sku['id']] = $sku;
                    }
                }
                return $newResult;
            }
        }
        return FALSE;
    }

    /**
     * set up additional products parameters for correct displaying in views
     * 
     * @param array $products
     * @return array
     */
    public function setProductValues($products) {
        $productObj = new Crm_Product();
        $productFeaturesModel = new Frontend_Model_ProductFeatures();
        $product_reviews_model = new Frontend_Model_ProductReviews();
        $identity = Zend_Auth::getInstance()->getStorage()->read();
        $contact_id = $identity ? $identity['id'] : null;
        $personalPricesModel = new Crm_PersonalPrice($contact_id);
        $newProducts = [];
        foreach ($products as $key => $product) {
            $newProducts[$key] = $product;

            $img = $productObj->getImgSrc($product['id'], $product['image_id']) . $product['image_id'] . '.96x96.' . $product['ext'];
            $img_200 = $productObj->getImgSrc($product['id'], $product['image_id']) . $product['image_id'] . '.200x0.' . $product['ext'];
            $dumy_Image = "/img/empty.jpg";

            $newProducts[$key]['img'] = ($product['image_id'] && file_exists(PUBLIC_PATH . $img)) ? $img : $dumy_Image;
            $newProducts[$key]['img_200'] = ($product['image_id'] && file_exists(PUBLIC_PATH . $img_200)) ? $img_200 : $dumy_Image;
            $newProducts[$key]['features'] = $productFeaturesModel->getFeaturesValuesByParams(['product_id' => $product['id']]);
            $newProducts[$key]['review_count'] = $product_reviews_model->getCountReviews($product['id']);
            if ($contact_id) {
                $productOne = $personalPricesModel->add_personal_price(array($newProducts[$key]));
                $productOne = reset($productOne);
                $newProducts[$key]['personal_price'] = Frontend_Utils::priceUA($productOne['skus'][$productOne['sku_id']]['personal_price'], $productOne['currency']);
            }
        }
        return $newProducts;
    }

    /**
     * get min/max price from product list for price filter
     * 
     * @param array $products
     * @return array
     */
    public function getMinMaxPrice($products) {
        $r = new Application_Model_DbTable_ProductFeatures();
        $ids = [];
        foreach ($products as $product) {
            $ids[] = $product['id'];
        }
        $sql = $r->select()
                ->setIntegrityCheck(false)
                ->from('shop_product', array(new Zend_Db_Expr('MIN(price) AS minPrice'), new Zend_Db_Expr('MAX(price) AS maxPrice')))
                ->where('id IN ' . '(' . implode(',', array_unique($ids)) . ')');
        $sql->where('status = ?', 1);

        $productsPrice = $r->fetchRow($sql)->toArray();
        return $productsPrice;
    }

    /**
     * get categories list for category filter
     * 
     * @param array $products
     * @return boolean|array
     */
    public function getCategory($products) {
        $category = [];
        foreach ($products as $product) {
            if (!empty($product['category_id'])) {
                $category[$product['category_id']] = $product['category_name'];
            }
        }
        if (!empty($category)) {
            return $category;
        }
        return FALSE;
    }

}
