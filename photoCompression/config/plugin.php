<?php

/**
 * Configure your plugin here
 */
return [
    'name' => 'photoCompression',
    'description' => 'photo compression',
    'version' => 0.1,
    'enabled' => true,
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
        'product_edit_tab_main_right_column' => ['setButtonCompressProduct']
    ],
];
