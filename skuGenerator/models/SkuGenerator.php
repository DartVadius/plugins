<?php

/**
 * SkuGenerator
 *
 * @author DartVadius
 */
class SkuGenerator_Model_SkuGenerator extends Zend_Db_Table_Abstract {

    protected $_name = 'sku_generator_conf';

    /**
     * select product 'color' type common features + features related to specific sku
     *
     * @param int $productId    product Id
     * @param int $skuId        sku Id
     * @return array
     */
    private function getColorByProductIdSkuId($productId, $skuId) {
        $features = new Application_Model_DbTable_Feature();
        $select = $features->select()->setIntegrityCheck(FALSE)->from('shop_product_features');
        $select->joinLeft('shop_feature', 'shop_product_features.feature_id = shop_feature.id');
        $select->joinLeft('shop_feature_values_color', 'shop_feature_values_color.feature_id = shop_feature.id', ['value_code']);
        $select->where('shop_product_features.feature_value_id = shop_feature_values_color.id');
        $select->where('shop_product_features.product_id=?', $productId);
        $select->where('shop_product_features.sku_id IS NULL OR shop_product_features.sku_id=?', $skuId);
        $select->where('shop_product_features.sku_id IS NOT NULL OR shop_feature.multiple = 0');

        return $this->fetchAll($select)->toArray();
    }

    /**
     * select product 'varchar' type common features + features related to specific sku
     *
     * @param int $productId    product Id
     * @param int $skuId sku    sku Id
     * @return array
     */
    private function getVarcharByProductIdSkuId($productId, $skuId) {
        $features = new Application_Model_DbTable_Feature();
        $select = $features->select()->setIntegrityCheck(FALSE)->from('shop_product_features');
        $select->joinLeft('shop_feature', 'shop_product_features.feature_id = shop_feature.id');
        $select->joinLeft('shop_feature_values_varchar', 'shop_feature_values_varchar.feature_id = shop_feature.id');
        $select->where('shop_product_features.feature_value_id = shop_feature_values_varchar.id');
        $select->where('shop_product_features.product_id=?', $productId);
        $select->where('shop_product_features.sku_id IS NULL OR shop_product_features.sku_id=?', $skuId);
        $select->where('shop_product_features.sku_id IS NOT NULL OR shop_feature.multiple = 0');

//        echo $select->assemble();
//        die();
        return $this->fetchAll($select)->toArray();
    }

    /**
     * select product 'dimension' type common features + features related to specific sku
     *
     * @param int $productId    product Id
     * @param int $skuId        sku Id
     * @return array
     */
    private function getDimensionByProductIdSkuId($productId, $skuId) {
        $features = new Application_Model_DbTable_Feature();
        $select = $features->select()->setIntegrityCheck(FALSE)->from('shop_product_features');
        $select->joinLeft('shop_feature', 'shop_product_features.feature_id = shop_feature.id');
        $select->joinLeft('shop_feature_values_dimension', 'shop_feature_values_dimension.feature_id = shop_feature.id');
        $select->where('shop_product_features.feature_value_id = shop_feature_values_dimension.id');
        $select->where('shop_product_features.product_id=?', $productId);
        $select->where('shop_product_features.sku_id IS NULL OR shop_product_features.sku_id=?', $skuId);
        $select->where('shop_product_features.sku_id IS NOT NULL OR shop_feature.multiple = 0');
        return $this->fetchAll($select)->toArray();
    }

    /**
     * select product 'double' type common features + features related to specific sku
     *
     * @param int $productId    product Id
     * @param int $skuId        sku Id
     * @return array
     */
    private function getDoubleByProductIdSkuId($productId, $skuId) {
        $features = new Application_Model_DbTable_Feature();
        $select = $features->select()->setIntegrityCheck(FALSE)->from('shop_product_features');
        $select->joinLeft('shop_feature', 'shop_product_features.feature_id = shop_feature.id');
        $select->joinLeft('shop_feature_values_double', 'shop_feature_values_double.feature_id = shop_feature.id');
        $select->where('shop_product_features.feature_value_id = shop_feature_values_double.id');
        $select->where('shop_product_features.product_id=?', $productId);
        $select->where('shop_product_features.sku_id IS NULL OR shop_product_features.sku_id=?', $skuId);
        $select->where('shop_product_features.sku_id IS NOT NULL OR shop_feature.multiple = 0');
        return $this->fetchAll($select)->toArray();
    }

    /**
     * select product 'range' type common features + features related to specific sku
     *
     * @param int $productId    product Id
     * @param int $skuId        sku Id
     * @return array
     */
    private function getRangeByProductIdSkuId($productId, $skuId) {
        $features = new Application_Model_DbTable_Feature();
        $select = $features->select()->setIntegrityCheck(FALSE)->from('shop_product_features');
        $select->joinLeft('shop_feature', 'shop_product_features.feature_id = shop_feature.id');
        $select->joinLeft('shop_feature_values_range', 'shop_feature_values_range.feature_id = shop_feature.id');
        $select->where('shop_product_features.feature_value_id = shop_feature_values_range.id');
        $select->where('shop_product_features.product_id=?', $productId);
        $select->where('shop_product_features.sku_id IS NULL OR shop_product_features.sku_id=?', $skuId);
        $select->where('shop_product_features.sku_id IS NOT NULL OR shop_feature.multiple = 0');
        return $this->fetchAll($select)->toArray();
    }

    /**
     * get all product common features and features related to specific sku
     *
     * @param int $productId    product Id
     * @param int $skuId        sku Id
     * @return array
     */
    public function getAllFeaturesByProductIdSkuId($productId, $skuId) {
        $features = [];
        $results = [];
        $results[] = $this->getColorByProductIdSkuId($productId, $skuId);
        $results[] = $this->getDimensionByProductIdSkuId($productId, $skuId);
        $results[] = $this->getDoubleByProductIdSkuId($productId, $skuId);
        $results[] = $this->getVarcharByProductIdSkuId($productId, $skuId);

        foreach ($results as $result) {
            foreach ($result as $res) {
                $features[] = $res;
            }
        }
        return $features;
    }

    public function getFeatureToType() {
        $features = new Shop_Model_TypeFeatures();

        $select = $features->select('shop_type_features.*, shop_feature.name')->setIntegrityCheck(FALSE);
        $select->joinLeft('shop_feature', 'shop_type_features.feature_id = shop_feature.id');
        $select->where('shop_feature.name IS NOT null');
//        echo $select->assemble();
//        die();
        if ($this->fetchAll($select)) {
            return $this->fetchAll($select)->toArray();
        }
    }

    /**
     * get full list of features attached to this product type
     *
     * @param int $typeId
     * @return array
     */
    public function getFeatureByTypeId($typeId) {
        $features = new Shop_Model_TypeFeatures();
        $select = $features->select('shop_type_features.*, shop_feature.name')->setIntegrityCheck(FALSE);
        $select->joinLeft('shop_feature', 'shop_type_features.feature_id = shop_feature.id');
        $select->where('shop_type_features.type_id=?', $typeId);
        if ($this->fetchAll($select)) {
            return $this->fetchAll($select)->toArray();
        }
    }

    /**
     * get list of all product types
     *
     * @return array
     */
    public function getTypes() {
        $types = new Application_Model_DbTable_Type();
        return $types->getTypes();
    }

    /**
     * get full sku generator config data from db
     *
     * @return array
     */
    public function getConfig() {
        $select = $this->select();
        if ($this->fetchAll()) {
            return $this->fetchAll($select)->toArray();
        }
    }

    /**
     *
     * @param array $post
     */
    public function saveConfig($post) {
        $this->delete('');
        foreach ($post['template'] as $key => $value) {
            if (!empty($value)) {
                $data = [
                    'sku_generator_conf_type_id' => trim($key),
                    'sku_generator_conf_template' => trim($value),
                ];
                $this->insert($data);
            }
        }
    }

    /**
     * generate sku for all products of this type
     *
     * @param id $typeId        id of product type
     * @param string $method    'all' if update all sku for this product type,
     *                          'empty' if update only empty sku values for this product type
     * @return boolean
     */
    public function setSkuByTypeAll($typeId, $method) {
        $template = $this->getConfigByTypeId($typeId)['sku_generator_conf_template'];
        if (empty($template)) {
            return FALSE;
        }

        $products = new Application_Model_DbTable_Products();
        $productsByType = $products->getProductByTypeId($typeId);

        $skus = new Application_Model_DbTable_ProductSku();
        foreach ($productsByType as $product) {
            $sku = $skus->getByProductId($product['id']);
            foreach ($sku as $value) {
                $skuOne = NULL;
                $features = $this->getAllFeaturesByProductIdSkuId($value['product_id'], $value['id']);
                if ($method === 'all') {
                    $skuOne = $this->createSku($features, $typeId, $value['id']);
                } elseif ($method === 'empty' && empty($value['sku'])) {
                    $skuOne = $this->createSku($features, $typeId, $value['id']);
                }
                if (!empty($skuOne)) {
                    $value['sku'] = $skuOne;
                    $skus->updateInsertSku($value, $value['id']);
                }
            }
        }

        return TRUE;
    }

    /**
     * generate sku for single product and concrete sku
     *
     * @param int $productId    product Id
     * @param int $skuId        sku Id
     * @return boolean
     */
    public function setSkuByProductId($productId, $skuId = NULL) {
        $type = new Application_Model_DbTable_Products();
        $typeId = $type->getById($productId)['type_id'];

        $template = $this->getConfigByTypeId($typeId)['sku_generator_conf_template'];
        if (empty($template)) {
            return FALSE;
        }
        $skus = new Application_Model_DbTable_ProductSku();
        $sku = $skus->getByProductId($productId);
        foreach ($sku as $value) {
            if ($skuId !== NULL) {
                if ($value['id'] == $skuId) {
                    $features = $this->getAllFeaturesByProductIdSkuId($productId, $value['id']);
                    $value['sku'] = $this->createSku($features, $typeId, $value['id']);
                    $skus->updateInsertSku($value, $value['id']);
                }
            } else {
                $features = $this->getAllFeaturesByProductIdSkuId($productId, $value['id']);
                $value['sku'] = $this->createSku($features, $typeId, $value['id']);
                $skus->updateInsertSku($value, $value['id']);
            }
        }
        return TRUE;
    }

    /**
     * get sku generator config data for concrete product type
     *
     * @param int $typeId   id of product type
     * @return array
     */
    private function getConfigByTypeId($typeId) {
        $select = $this->select()->where('sku_generator_conf_type_id =?', $typeId);
        if ($this->fetchRow($select)) {
            return $this->fetchRow($select)->toArray();
        }
    }

    /**
     * generate sku string by product Id and features values
     *
     * @param array $productFeatures    array of product features
     * @param int $typeId               product type Id
     * @param string $postFix           postfix added to result string
     * @return string
     */
    private function createSku($productFeatures, $typeId, $postFix = NULL) {
        $template = $this->getConfigByTypeId($typeId)['sku_generator_conf_template'];
        $features = $this->getFeatureByTypeId($typeId);
//        print_r($productFeatures);
//        die();
//replace template parts by real values
        foreach ($productFeatures as $feature) {
            $pattern = "/{{" . $feature['code'] . "}}/";
            $replacement = $feature['value_code'];
            $template = preg_replace($pattern, $replacement, $template);
        }

        $pattern = "/{{id}}/";
        $replacement = $postFix;
        $template = preg_replace($pattern, $replacement, $template);

//clear unchanged template parts
        foreach ($features as $feat) {
            $pattern = "/{{" . $feat['code'] . "}}/";
            $template = preg_replace($pattern, '', $template);
        }

//remove extra separators
        $pattern = [
            '/(\(\))/',
            '/(\[\])/',
        ];
        $template = preg_replace($pattern, '', $template);
        $template = preg_replace('/[-]{2,}+/', '-', $template);
        $template = preg_replace('/[\/]{2,}+/', '/', $template);
        $template = preg_replace('/[\\\]{2,}+/', '\\', $template);
        $first = $template[0];
        $last = $template[strlen($template) - 1];

        if ($first == '/' || $first == '\\' || $first == '-') {
            $template[0] = '';
        }

        if ($last == '/' || $last == '\\' || $last == '-') {
            $template[strlen($template) - 1] = '';
        }

        return trim($template);
    }
    
    /**
     * select from features only varchar type and color type
     * 
     * @param array $features
     * @return boolean|array
     */
    public function filterType($features) {
        if (empty($features)) {
            return FALSE;
        }
        $newFeatures = [];
        foreach ($features as $feature) {
            if ($feature['type'] == 'color' || $feature['type'] == 'varchar') {
                $newFeatures[] = $feature;
            }
        }
        return $newFeatures;
    }

}
