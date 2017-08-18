<?php

/**
 * Configure your plugin here
 */
return [
    'name' => 'heybro',
    'description' => 'парспорта игрушек',
    'version' => 1.0,
    'enabled' => true,
    'category' => 'frontend',
    'routes' => [
        'heybro' => [
            '/heybro',
            [
                'module' => 'heybro',
                'controller' => 'heybro',
                'action' => 'index'
            ],
        ],
        'cabinet-set_img' => [
            'cabinet/heybro',
            [
                'module' => 'heybro',
                'controller' => 'heybro',
                'action' => 'set-img'
            ],
        ],
        'cabinet-edit' => [
            '/heybro/ajax/edit',
            [
                'module' => 'heybro',
                'controller' => 'ajax',
                'action' => 'edit'
            ],
        ],
        'chkdir-toy' => [
            '/heybro/ajax/check-product',
            [
                'module' => 'heybro',
                'controller' => 'ajax',
                'action' => 'check-product'
            ],
        ],
        'get-sku' => [
            '/heybro/ajax/get-sku',
            [
                'module' => 'heybro',
                'controller' => 'ajax',
                'action' => 'get-sku'
            ],
        ],
        'heybro-toy' => [
            'toy-construct',
            [
                'module' => 'heybro',
                'controller' => 'heybro',
                'action' => 'toy'
            ],
        ],
    ],
    'resources' => [
        ['heybro', null],
        ['heybro:heybro', 'heybro'],
        ['heybro:ajax', 'heybro'],
    ],
    'rules' => [
        'allow' => [
            [
                'roles' => 'admin',
                'resources' => ['heybro'],
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
    'route_default' => 'heybro',
    'hooks' => [
        'order_state_change_action' => ['setCard'],
        'frontend_cabinet_profile_menu_item' => ['setLink']
    ]
];
