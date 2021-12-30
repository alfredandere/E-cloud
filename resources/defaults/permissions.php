<?php

return [
    'roles' => [
        [
            'default' => true,
            'extends' => 'users',
            'name' => 'users',
            'permissions' => [
                'api.access',
                'links.view',
                'links.create',
                'workspaces.create',
                'notifications.subscribe',
            ]
        ],
        [
            'guests' => true,
            'extends' => 'guests',
            'name' => 'guests',
            'permissions' => [
                'links.view',
            ]
        ]
    ],
    'all' => [
        'links' => [
            'links.view',
            'links.create',
            'links.update',
            'links.delete',
        ],
        'api' => [
            [
                'name' => 'api.access',
                'description' => 'Required in order for users to be able to use the API.',
            ],
        ],
        'notifications' => [
            ['name' => 'notifications.subscribe', 'description' => 'Allows agents to subscribe to various conversation notifications.'],
        ]
    ]
];

