<?php

class Calendar_Bootstrap extends Zend_Application_Module_Bootstrap
{

    protected function _initView()
    {
        $router = new Zend_Controller_Router_Rewrite();
        $request = new Zend_Controller_Request_Http();
        $router->route($request);
        if ($request->getModuleName() == strtolower($this->getModuleName())) {
            $view = Zend_Layout::getMvcInstance()->getView();
            $view->headLink()->appendStylesheet('/modules/calendar/css/calendar.css');
            $view->headLink()->appendStylesheet('/modules/calendar/css/fullcalendar.css');
            $view->headScript()->appendFile('/modules/calendar/js/moment.min.js');
            $view->headScript()->appendFile('/modules/calendar/js/fullcalendar.min.js');
            $view->headScript()->appendFile('/modules/calendar/js/ru.js');
            $view->headScript()->appendFile('/modules/calendar/js/calendar.js');
            $view->headScript()->appendFile('/js/jquery.marcopolo.js');
        }
    }

    protected function _initAcl()
    {
        $acl = Zend_Registry::get('acl');

        $acl->addResource('calendar');
        $acl->addResource('calendar:index', 'calendar');
        $acl->addResource('calendar:task', 'calendar');
        $acl->addResource('calendar:ajax', 'calendar');

        $acl->allow('manager', 'calendar');
        $acl->allow('partner', 'calendar');
        $acl->allow('admin', 'calendar');

        Zend_Registry::set('acl',$acl);
    }
    
    // protected function _initAutoload()
    // {
    //     $autoloader = new Zend_Application_Module_Autoloader(array(
    //         'namespace' => 'Calendar',
    //         'basePath' => dirname(__FILE__)
    //     ));

    //     $loader = new Zend_Loader_Autoloader_Resource(array(
    //         'namespace' => '',
    //         'basePath' => dirname(__FILE__)
    //     ));

    //     $loader->addResourceType('classes_type', 'classes', 'Calendar');

    //     return $autoloader;
    // }

}