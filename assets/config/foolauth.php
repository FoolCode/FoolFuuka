<?php

return [
    'roles' => [
        'user' => [],
        'mod' => [
            'boards' => ['see_hidden'],
            'comment' => ['see_ip', 'passwordless_deletion', 'limitless_comment', 'reports', 'mod_capcode'],
            'media' => ['see_banned', 'see_hidden', 'limitless_media'],
        ],
        'admin' => [
            'boards' => ['edit', 'see_hidden'],
            'comment' => ['see_ip', 'passwordless_deletion', 'limitless_comment', 'reports', 'mod_capcode', 'admin_capcode', 'dev_capcode'],
            'poster' => ['manage_bans', 'manage_appeals'],
            'media' => ['see_banned', 'see_hidden', 'limitless_media'],
        ],
    ],
];
