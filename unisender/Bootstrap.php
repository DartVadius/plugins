<?php

class Unisender_Bootstrap extends Zend_Application_Module_Bootstrap {

    protected function _initView() {
        $router = new Zend_Controller_Router_Rewrite();
        $request = new Zend_Controller_Request_Http();
        $router->route($request);
        if ($request->getModuleName() == strtolower($this->getModuleName())) {
            $view = Zend_Layout::getMvcInstance()->getView();
            $view->headLink()->appendStylesheet('/modules/unisender/css/unisender.css');
            $view->headScript()->appendFile('/modules/unisender/js/unisender.js');
        }
    }

    protected function _initAcl() {
        $acl = Zend_Registry::get('acl');

        $acl->addResource('unisender');
        $acl->addResource('unisender:index', 'unisender');
        $acl->addResource('unisender:ajax', 'unisender');
        $acl->addResource('unisender:contact-list', 'unisender');
        $acl->addResource('unisender:template', 'unisender');
        $acl->addResource('unisender:statistic', 'unisender');

        $acl->allow('manager', 'unisender');
        $acl->allow('admin', 'unisender');
        $acl->allow('guest', 'unisender:statistic', ['count-voting']);

        Zend_Registry::set('acl', $acl);
    }

    protected function _initAutoload() {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Unisender',
            'basePath' => dirname(__FILE__)
        ));

        $loader = new Zend_Loader_Autoloader_Resource(array(
            'namespace' => '',
            'basePath' => dirname(__FILE__)
        ));

        $loader->addResourceType('classes_type', 'classes', 'Unisender');

        return $autoloader;
    }

}
