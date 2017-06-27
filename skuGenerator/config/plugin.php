<?php

/**
 * Configure your plugin here
 */
return [
    'name' => 'skuGenerator',
    'description' => 'vendor code generator ',
    'version' => 1.0,
    'enabled' => true,
    'routes' => [
        'sku-Generator_index' => [
            '/sku-generator_index',
            [
                'module' => 'skuGenerator',
                'controller' => 'sku-generator',
                'action' => 'index'
            ],
        ],
    ],
    'resources' => [
        ['skuGenerator', null],
        ['skuGenerator:sku-generator', 'skuGenerator'],
    ],
    'rules' => [
        'allow' => [
            [
                'roles' => 'admin',
                'resources' => ['skuGenerator'],
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
    'route_default' => 'sku-generator/index',
    'hooks' => [
        'product_edit_tab_main_right_column' => ['setButton']
    ]
];
