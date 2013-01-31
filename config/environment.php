<?php

return [
	'software' => [
		[
			'title' => __('FoolFuuka Version'),
			'value' => \Foolz\Config\Config::get('foolz/foolfuuka', 'package', 'main.version'),
			'alert' => [
				'type' => 'info',
				'condition' => true,
				'title' => __('New Update Available'),
				'string' => __('There is a new version of the software available for download.')
			]
		]
	],

	'php-extensions' => [
		[
			'title' => 'BCMath',
			'value' => (extension_loaded('bcmath') ? __('Installed') : __('Unavailable')),
			'alert' => [
				'type' => 'important',
				'condition' => (bool) ! extension_loaded('bcmath'),
				'title' => __('Critical'),
				'string' => \Str::tr(
					__('Your PHP environment shows that you do not have the :ext extension installed. This will limit the functionality of the software.'),
					[':ext' => 'BCMath']
				)
			]
		],
		[
			'title' => 'EXIF',
			'value' => (extension_loaded('exif') ? __('Installed') : __('Unavailable')),
			'alert' => [
				'type' => 'warning',
				'condition' => (bool) ! extension_loaded('exif'),
				'title' => __('Warning'),
				'string' => \Str::tr(
					__('Your PHP environment shows that you do not have the :ext extension installed. This may limit the functionality of the software.'),
					[':ext' => 'EXIF']
				)
			]
		],
		[
			'title' => 'GD2',
			'value' => (extension_loaded('gd') ? __('Installed') : __('Unavailable')),
			'alert' => [
				'type' => 'warning',
				'condition' => (bool) ! extension_loaded('gd'),
				'title' => __('Warning'),
				'string' => \Str::tr(
					__('Your PHP environment shows that you do not have the :ext extension installed. This may limit the functionality of the software.'),
					[':ext' => 'GD2']
				)
			]
		]
	]
];