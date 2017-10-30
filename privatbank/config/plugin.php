<?php

/**
 * Configure your plugin here
 */
return [
    'name' => 'privatbank',
    'description' => 'Privatbank payment',
    'version' => 1.0,
    'enabled' => true,
    'category' => 'shop',
    'image' => 'img/image.png',
    'routes' => [
        'privatbank_index' => [
            '/privatbank_index',
            [
                'module' => 'privatbank',
                'controller' => 'index',
                'action' => 'index'
            ],
        ],
    ],
    'resources' => [
        ['privatbank', null],
//        ['privatbank:privatbank', 'privatbank'],
        ['privatbank:index', 'privatbank'],
    ],
    'rules' => [
        'allow' => [
            [
                'roles' => 'admin',
                'resources' => ['privatbank'],
                'privileges' => null,
            ],
            [
                'roles' => 'guest',
                'resources' => ['privatbank:index'],
                'privileges' => ['status']
            ],
            [
                'roles' => 'guest',
                'resources' => ['privatbank:index'],
                'privileges' => ['payment']
            ],
        ],
        'deny' => [],
    ],
    'avaliable_methods' => [
        'frontend' => [
            'frontend',
        ]
    ],
    'route_default' => 'privatbank/index',
    'hooks' => [
        'frontend_checkout_confirmation_order_bottom' => ['setButtonPay'],
        'frontend_cabinet_order_view_bottom' => ['setButtonPayCabinet']
    ],
];
