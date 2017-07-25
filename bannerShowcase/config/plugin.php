<?php

/**
 * Configure your plugin here
 */
return [
    'name' => 'bannerShowcase',
    'description' => 'Баннер на витрину',
    'version' => 1.0,
    'enabled' => true,
    'routes' => [
        'bannerShowcase' => [
            '/banner-showcase',
            [
                'module' => 'bannerShowcase',
                'controller' => 'banner-showcase',
                'action' => 'index'
            ],
        ],
        'show-banner' => [
            'banner-display',
            [
                'module' => 'bannerShowcase',
                'controller' => 'banner-showcase',
                'action' => 'display',
            ]
        ]
    ],
    'resources' => [
        ['bannerShowcase', null],
        ['bannerShowcase:banner-showcase', 'bannerShowcase'],
    ],
    'rules' => [
        'allow' => [
            [
                'roles' => 'admin',
                'resources' => ['bannerShowcase'],
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
    'route_default' => 'banner-showcase',
    'hooks' => [
    ]
];
