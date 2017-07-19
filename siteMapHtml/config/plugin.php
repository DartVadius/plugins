<?php

/**
 * Configure your plugin here
 */
$plugConf = new Application_Model_DbTable_PluginsSettings();
$url = $plugConf->getSettingBySystemName('siteMapHtml', 'urlmap')[0]['params'];
if (!$url) {
    $url = '/site-map_index';
}


return [
    'name' => 'Карта сайта',
    'description' => 'Плагин карта сайта',
    'version' => 0.9,
    'enabled' => true,
    'category' => 'frontend',
    'image' => 'img/image.png',
    'routes' => [
        'site-map_index' => [
            $url,
            [
                'module' => 'siteMapHtml',
                'controller' => 'site-map',
                'action' => 'index'
            ]
        ],
    ],
    'resources' => [
        ['siteMapHtml', null],
        ['siteMapHtml:site-map', 'siteMapHtml'],
    ],
    'rules' => [
        'allow' => [
            [
                'roles' => 'admin',
                'resources' => ['siteMapHtml'],
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
    'route_default' => 'site-map/config',
];
?>