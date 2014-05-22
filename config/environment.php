<?php

return [
    /*
     * TODO find a way to display this programmatically
     * 'software' => [
        [
            'title' => _i('FoolFuuka Version'),
            'value' => \Foolz\Foolframe\Model\Legacy\Config::get('foolz/foolfuuka', 'package', 'main.version'),
            'alert' => [
                'type' => 'info',
                'condition' => true,
                'title' => _i('New Update Available'),
                'string' => _i('There is a new version of the software available for download.')
            ]
        ]
    ], */

    'php-extensions' => [
        [
            'title' => 'BCMath',
            'value' => (extension_loaded('bcmath') ? _i('Installed') : _i('Unavailable')),
            'alert' => [
                'type' => 'important',
                'condition' => (bool) ! extension_loaded('bcmath'),
                'title' => _i('Critical'),
                'string' => _i('Your PHP environment shows that you do not have the "%s" extension installed. This will limit the functionality of the software.', 'BCMath')
            ]
        ],
        [
            'title' => 'EXIF',
            'value' => (extension_loaded('exif') ? _i('Installed') : _i('Unavailable')),
            'alert' => [
                'type' => 'warning',
                'condition' => (bool) ! extension_loaded('exif'),
                'title' => _i('Warning'),
                'string' => _i('Your PHP environment shows that you do not have the "%s" extension installed. This will limit the functionality of the software.', 'EXIF')
            ]
        ],
        [
            'title' => 'GD2',
            'value' => (extension_loaded('gd') ? _i('Installed') : _i('Unavailable')),
            'alert' => [
                'type' => 'warning',
                'condition' => (bool) ! extension_loaded('gd'),
                'title' => _i('Warning'),
                'string' => _i('Your PHP environment shows that you do not have the "%s" extension installed. This will limit the functionality of the software.', 'GD2')
            ]
        ]
    ]
];
