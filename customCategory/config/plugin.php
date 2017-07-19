<?php

$router = Zend_Controller_Front::getInstance()->getRouter();

$urls = require_once 'url.php';
if (!empty($urls)) {
    $category_routes = [];
    $plugin = new Application_Model_DbTable_Plugins();
    $pluginId = $plugin->getBySystemName('customCategory')['id'];
    $settings = new Application_Model_DbTable_PluginsSettings();
    
    foreach ((array)$urls as $url) {
        $route_value = $settings->getSettingBySystemName('customCategory', $url);
        if (empty($route_value) && $url != 1) {
            $settings->insert([
                'plugins_id' => $pluginId,
                'name' => $url,
                'params' => ''
            ]);
        }
        $router->addRoute($url, new Zend_Controller_Router_Route_Static($url, [
            'module' => 'customCategory',
            'controller' => 'custom-category',
            'action' => 'view',
        ]));
        
    }
}

return [
    'name' => 'customCategory',
    'description' => 'Плагин группировки категорий',
    'version' => 1,
    'enabled' => true,
    'category' => 'frontend',
    'image' => 'img/image.png',
    'routes' => [
        'custom-category_index' => [
            'custom-category',
            [
                'module' => 'customCategory',
                'controller' => 'custom-category',
                'action' => 'index',
            ],
        ],
    ],
    'resources' => [
        ['customCategory', null],
        ['customCategory:custom-category', 'customCategory'],
    ],
    'rules' => [
        'allow' => [
            [
                'roles' => 'admin',
                'resources' => ['customCategory'],
                'privileges' => null,
            ]
        ],
        'deny' => [],
    ],
    'avaliable_methods' => [
        'frontend' => [
            'frontend',
        ]
    ],
    'route_default' => 'custom-category/config',
];