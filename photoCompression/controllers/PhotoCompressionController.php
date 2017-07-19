<?php

/**
 * Description of PhotoCompressionController
 *
 * @author DartVadius
 */
class PhotoCompression_PhotoCompressionController extends Zend_Controller_Action {

    public function indexAction() {
        $plugConf = new Application_Model_DbTable_PluginsSettings();

        $this->view->api_key = $plugConf->getSettingBySystemName('photoCompression', 'api_key')[0]['params'];
        $this->view->proxy_set = $plugConf->getSettingBySystemName('photoCompression', 'proxy_set')[0]['params'];
    }

    /**
     * save config
     */
    public function saveAction() {
        $photo = new PhotoCompression_Model_PhotoCompression();
        $r = new Zend_Controller_Action_Helper_Redirector;

        $conf = $this->_request->getPost();
        $photo->saveConfig($conf);

        $r->gotoUrl('/photoCompression/photo-compression')->redirectAndExit();
    }

    /**
     * compress all uncompressed products photos on website
     */
    public function compressAllProductAction() {
        if ($this->getRequest()->isXmlHttpRequest()) {

            $photo = new PhotoCompression_Model_PhotoCompression();

            Zend_Controller_Action_HelperBroker::removeHelper('viewRenderer');
            $post = $this->_request->getPost();

            if (isset($post['prod_img'])) {
                $photo->saveImg($post['prod_img']);
                $this->_helper->json($post['prod_img']);
            } else {
                $data = $photo->getProductImgs();
                $this->_helper->json($data);
            }
        }
        return FALSE;
    }

    /**
     * compress uncompressed photos of current product
     */
    public function compressProductAction() {
        $photo = new PhotoCompression_Model_PhotoCompression();
        $r = new Zend_Controller_Action_Helper_Redirector;

        $id = $this->getParam('id');
        $imgs = $photo->getOneProductImgs($id);
        if (!empty($imgs)) {
            foreach ($imgs as $img) {
                $photo->saveImg($img);
            }
        }
        $r->gotoUrl('/shop/products/edit/id/' . $id)->redirectAndExit();
    }

    /**
     * compress all uncompressed categories photos on website
     */
    public function compressAllCategoryAction() {
        if ($this->getRequest()->isXmlHttpRequest()) {

            $photo = new PhotoCompression_Model_PhotoCompression();

            Zend_Controller_Action_HelperBroker::removeHelper('viewRenderer');
            $post = $this->_request->getPost();

            if (isset($post['cat_img'])) {
                $photo->saveImg($post['cat_img']);
            } else {
                $data = $photo->getCategoriesImgs();
                $this->_helper->json($data);
            }
        }
        return FALSE;
    }

    /**
     * compress uncompressed photos of current category
     */
    public function compressCategoryAction() {
        $photo = new PhotoCompression_Model_PhotoCompression();
        $r = new Zend_Controller_Action_Helper_Redirector;

        $id = $this->getParam('id');
        $imgs = $photo->getOneCategoryImgs($id);
        if (!empty($imgs)) {
            foreach ($imgs as $img) {
                $photo->saveImg($img);
            }
        }
        $r->gotoUrl('/shop/category/add-edit/product_id/' . $id)->redirectAndExit();
    }

    /**
     * check out API key
     */
    public function checkApiKeyAction() {
        if ($this->getRequest()->isXmlHttpRequest()) {
            Zend_Controller_Action_HelperBroker::removeHelper('viewRenderer');

            $photo = new PhotoCompression_Model_PhotoCompression();

            if ($photo->checkApiKey()) {
                $data = 1;
            } else {
                $data = 0;
            }

            $this->_helper->json($data);
        }
    }

    public function removeAction() {
        $photo = new PhotoCompression_Model_PhotoCompression();
        $r = new Zend_Controller_Action_Helper_Redirector;
        
        $photo->removeDeadLinks();

        $r->gotoUrl('/photoCompression/photo-compression')->redirectAndExit();
    }

}
