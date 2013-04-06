<?php

return [

	/**
	 * FoolFuuka base variables
	 */
	'main' => [
		/**
		 * Version for autoupgrades
		 */
		'version' => '1.7.0-dev-0',

		/**
		 * Display name for the module
		 */
		'name' => 'FoolFuuka',

		/**
		 * The name that can be used in classes names
		 */
		'class_name' => 'Foolfuuka',

		/**
		 *  URL to download a newer version
		 */
		'git_tags_url' => 'https://api.github.com/repos/FoolCode/foolfuuka/tags',

		/**
		 * URL to fetch the changelog
		 */
		'git_changelog_url' => 'https://raw.github.com/FoolCode/FoOlFuuka/master/CHANGELOG.md',

		/**
		 * Minimal PHP requirement
		 */
		'min_php_version' => '5.3.0'
	],


	/**
	 * Locations of the data out of the module folder
	 */
	'directories' => [
		'themes' => 'public/themes/',
		'plugins' => 'public/plugins/'
	],


	/**
	 * Preferences for when there's no default specified
	 */
	'preferences' => [

		'gen' => [
			'website_title' => 'FoolFuuka',
			'index_title' => 'FoolFuuka',
		],

		'lang' => [
			'default' => 'en_EN'
		],

		'sphinx' => [
			'listen' => '127.0.0.1:9306',
			'listen_mysql' => '127.0.0.1:9306',
			'dir' => '/usr/local/sphinx/var',
			'min_word' => 3,
			'memory' => 2047
		],

		'themes' => [],

		'radix' => [
			'threads_per_page' => 10,
			'thumbnail_op_width' => 250,
			'thumbnail_op_height' => 250,
			'thumbnail_reply_width' => 125,
			'thumbnail_reply_height' => 125,
			'max_image_size_kilobytes' => 3072,
			'max_image_size_width' => 5000,
			'max_image_size_height' => 5000,
			'max_posts_count' => 400,
			'max_images_count' => 250,
			'min_image_repost_time' => 0,
			'myisam_search' => false,
			'anonymous_default_name' => 'Anonymous',
		],

		'boards' => [
			'directory' => DOCROOT.'foolfuuka/boards/',
			'url' => \Uri::base().'foolfuuka/boards/'
		]
	]
];