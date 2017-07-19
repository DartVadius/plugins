<?php

/**
 * Configure your plugin here
 */
return [
    'name' => 'branding',
    'description' => 'setup brand and manufacturer for products',
    'version' => 1.0,
    'enabled' => true,
    'routes' => [
        'branding_index' => [
            '/branding_index',
            [
                'module' => 'branding',
                'controller' => 'branding',
                'action' => 'index'
            ],
        ],
        'brand' => [
            '/brand',
            [
                'module' => 'branding',
                'controller' => 'branding',
                'action' => 'brand'
            ],
        ],
        'manufacturer' => [
            '/manufacturer',
            [
                'module' => 'branding',
                'controller' => 'branding',
                'action' => 'manufacturer'
            ],
        ],
        'brand_product' => [
            '/brand_product/:val',
            [
                'module' => 'branding',
                'controller' => 'branding',
                'action' => 'product-brand'
            ],
        ],
        'manufacturer_product' => [
            '/manufacturer_product/:val',
            [
                'module' => 'branding',
                'controller' => 'branding',
                'action' => 'product-manufacturer'
            ],
        ],
        
    ],
    'resources' => [
        ['branding', null],
        ['branding:branding', 'branding'],
    ],
    'rules' => [
        'allow' => [
            [
                'roles' => 'admin',
                'resources' => ['branding'],
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
    'route_default' => 'branding/index',
//    'hooks' => [
//        'product_edit_tab_main_right_column' => ['setButton']
//    ]
];
