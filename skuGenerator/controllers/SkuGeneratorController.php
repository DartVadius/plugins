<?php

/**
 * SkuGeneratorController
 *
 * @author DartVadius
 */
class SkuGenerator_SkuGeneratorController extends Zend_Controller_Action {

    public function indexAction() {
        $sku = new SkuGenerator_Model_SkuGenerator();
        $features = $sku->getFeatureToType();
        $features = $sku->filterType($features);        
        $types = $sku->getTypes();
        $conf = $sku->getConfig();
        $templ = [];
        foreach ($conf as $template) {
            $templ[$template['sku_generator_conf_type_id']] = $template['sku_generator_conf_template'];
        }
        $this->view->types = $types;
        $this->view->features = $features;
        $this->view->config = $templ;
    }

    /**
     * save template for sku creating
     */
    public function saveAction() {
        $sku = new SkuGenerator_Model_SkuGenerator();
        $post = $this->_request->getPost();
        $sku->saveConfig($post);
        $this->_helper->redirector()->goTourl('/skuGenerator/sku-generator/index');
    }

    /**
     * create sku by products categories
     */
    public function createAction() {
        $sku = new SkuGenerator_Model_SkuGenerator();
        $post = $this->_request->getPost();
        if (!empty($post)) {
            $sku->setSkuByTypeAll(key($post), $post[key($post)]);
        }
        $types = $sku->getTypes();
        $this->view->types = $types;
    }

    /**
     * create sku for single product
     */
    public function productAction() {
        $r = new Zend_Controller_Action_Helper_Redirector;
        $sku = new SkuGenerator_Model_SkuGenerator();
        $id = $this->getParam('id');
        $skus = new SkuGenerator_Model_SkuGenerator();
        $skus->setSkuByProductId($id);
        $r->gotoUrl('/shop/products/edit/id/' . $id)->redirectAndExit();
    }

}