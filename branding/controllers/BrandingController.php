<?php

/**
 * Description of BrandingController
 *
 * @author DartVadius
 */
class Branding_BrandingController extends Zend_Controller_Action {

    /**
     * config view
     */
    public function indexAction() {
        $brand = new Branding_Model_Branding();
        $config = $brand->getConfig();

        $this->view->features = $brand->getFeatures();
        $this->view->brand = !empty($config['brand']) ? $config['brand'] : NULL;
        $this->view->manufacturer = !empty($config['manufacturer']) ? $config['manufacturer'] : NULL;
        $this->view->link = $brand->getUrl();
    }

    /**
     * save config
     */
    public function saveAction() {
        $brand = new Branding_Model_Branding();
        $r = new Zend_Controller_Action_Helper_Redirector;
        $post = $this->_request->getPost();
        $brand->saveConfig($post);

        $r->gotoUrl('/branding/branding/index')->redirectAndExit();
    }

    /**
     * brands view
     */
    public function brandAction() {
        $brand = new Branding_Model_Branding();
        $this->view->brands = $brand->getBrandList();
        $this->view->headTitle('Brands');
    }

    /**
     * manufacturers view
     */
    public function manufacturerAction() {
        $manufacturer = new Branding_Model_Branding();
        $this->view->manufacturers = $manufacturer->getManufacturerList();
        $this->view->headTitle('Manufacturers');
    }

    /**
     * products view by brand
     */
    public function productBrandAction() {
        $product = new Branding_Model_Branding();
        $params = $this->_request->getParams();

        $val_name = $this->_request->getParam('val');
        ($products = $product->getBrandProducts($val_name, $params)) ? $products : NULL;
        if ($this->getRequest()->isXmlHttpRequest()) {
            $ajax = TRUE;
        } else {
            $ajax = FALSE;
        }
        $this->product($products, $params, $val_name, $ajax);
    }

    /**
     * products view by manufacturer
     */
    public function productManufacturerAction() {
        $product = new Branding_Model_Branding();
        $params = $this->_request->getParams();
        $val_name = $this->_request->getParam('val');
        ($products = $product->getManufacturerProducts($val_name, $params)) ? $products : NULL;
        if ($this->getRequest()->isXmlHttpRequest()) {
            $ajax = TRUE;
        } else {
            $ajax = FALSE;
        }
        $this->product($products, $params, $val_name, $ajax);
    }

    /**
     * common part of productManufacturer and productBrand controllers
     *
     * @param array $products
     * @param array $params
     * @param boolean $ajax
     */
    private function product($products, $params, $val_name, $ajax) {
        $product = new Branding_Model_Branding();
        $mainCurrency = reset(Zend_Registry::get('maincurrency'))['value'];
        if ($products) {
            $minMaxPrice = $product->getMinMaxPrice($products);
            $minMaxPrice['minPrice'] = Frontend_Utils::sitePrice($mainCurrency, $minMaxPrice['minPrice']);
            $minMaxPrice['maxPrice'] = Frontend_Utils::sitePrice($mainCurrency, $minMaxPrice['maxPrice']);

            if (empty($_SESSION[$val_name]['category_filter'])) {
                $_SESSION[$val_name]['category_filter'] = $product->getCategory($products);
            }
        }

        $productsStorage = $this->pagination($params, $products);

        if ($productsStorage) {
            $products = $product->setProductValues($productsStorage);
        }

        if ($ajax) {
            $this->ajaxRequest($products, $params, $productsStorage);
        }

        $settingsModel = new Application_Model_DbTable_Settings();
        $settings = !is_null($settingsModel->getOne('quick_order_settings')) ? unserialize($settingsModel->getOne('quick_order_settings')) : null;

        $this->view->settings = $settings;
        $this->view->products = $products;
        $this->view->paginator = $productsStorage->getPages();
        $this->view->productsCount = $productsStorage->getTotalItemCount();
        $this->view->prices = $minMaxPrice;
        $this->view->brand = $val_name;
        $this->view->category = $_SESSION[$val_name]['category_filter'];
        $this->view->headTitle($val_name);
        $this->renderScript('branding/product.phtml');
    }

    /**
     * paginator
     *
     * @param array $params
     * @param array $products
     * @return \Zend_Paginator
     */
    private function pagination($params, $products) {
        $pageNumber = isset($params['page']) ? $params['page'] : 1;
        $countPerPage = isset($params['size']) ? $params['size'] : 25;
        $productsStorage = new Zend_Paginator(new Zend_Paginator_Adapter_Array($products));
        $productsStorage->setCurrentPageNumber($pageNumber);
        $productsStorage->setItemCountPerPage($countPerPage);
        $productsStorage->setPageRange(10);
        return $productsStorage;
    }

    /**
     * ajax request processing
     *
     * @param array $products
     * @param array $params
     * @param array $productsStorage
     */
    private function ajaxRequest($products, $params, $productsStorage) {
        $html = new Zend_View();
        $html->setScriptPath(PUBLIC_PATH . '/themes/' . Zend_Registry::get('theme'));
        $html->setHelperPath(APPLICATION_PATH . '/modules/frontend/views/helpers');
        $html->assign('products', $products);

        $html_paginator = new Zend_View();
        $html_paginator->setScriptPath(PUBLIC_PATH . '/themes/' . Zend_Registry::get('theme'));
        $html_paginator->assign('paginator', $productsStorage->getPages());
        $html_paginator->assign('params', $params);

        try {
            $productsHtml = $html->render('/partials/products_list.phtml');
            $paginatorHtml = $html_paginator->render('/partials/paginator.phtml');
        } catch (Exception $e) {
            echo $e->getMessage();
            die;
        }

        $this->_helper->json(array('products' => $productsHtml, 'paginator' => $paginatorHtml, 'productcount' => $productsStorage->getTotalItemCount()));
//        die;
    }

}
