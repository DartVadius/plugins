<?php

/**
 * Configure your plugin here
 */
return [
    'name' => 'Photo compression',
    'description' => 'Photo compression',
    'version' => 0.1,
    'enabled' => true,
    'category' => 'shop',
    'image' => 'img/image.png',
    'routes' => [
        'photo-compression_index' => [
            '/photo-compression_index',
            [
                'module' => 'photoCompression',
                'controller' => 'photo-compression',
                'action' => 'index'
            ],
        ],
    ],
    'resources' => [
        ['photoCompression', null],
        ['photoCompression:photo-compression', 'photoCompression'],
    ],
    'rules' => [
        'allow' => [
            [
                'roles' => 'admin',
                'resources' => ['photoCompression'],
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
    'route_default' => 'photo-compression/index',
    'hooks' => [
        'shop_products_edit_main_tab_right_column' => ['setButtonCompressProduct']
    ],
];
