<?php

return [
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
