<?php

return [
    'sidebar' => [
        'boards' => [
            'name' => _i('Boards'),
            'level' => 'admin',
            'default' => 'manage',
            'content' => [
                'manage' => [
                    'alt_highlight' => ['board'],
                    'level' => 'admin',
                    'name' => _i('Manage'),
                    'icon' => 'icon-th-list'
                ],
                'search' => [
                    'level' => 'admin',
                    'name' => _i('Search'),
                    'icon' => 'icon-search'
                ],
                'preferences' => [
                    'level' => 'admin',
                    'name' => _i('Preferences'),
                    'icon' => 'icon-check'
                ]
            ]
        ],
        'moderation' => [
            'name' => _i('Moderation'),
            'level' => 'mod',
            'default' => 'reports',
            'content' => [
                'logs' => [
                    'level' => 'admin',
                    'name' => _i('Audit Log'),
                    'icon' => 'icon-file'
                ],
                'bans' => [
                    'level' => 'mod',
                    'name' => _i('Bans'),
                    'icon' => 'icon-truck'
                ],
                'appeals' => [
                    'level' => 'mod',
                    'name' => _i('Pending Appeals'),
                    'icon' => 'icon-heart-empty'
                ]
            ]
        ]
    ]
];
