<?php

return array(

	/**
	 * FoolFuuka base variables
	 */
	'main' => array(

		/**
		 * Version for autoupgrades
		 */
		'version' => '1.5.0-dev-0',

		/**
		 * Display name for the module
		 */
		'name' => 'FoolFuuka',

		/**
		 * The two letter identifier
		 */
		'identifier' => 'fu',

		/**
		 * The name that can be used in classes names
		 */
		'class_name' => 'Foolfuuka',

		/**
		 *  URL to download a newer version
		 */
		'git_tags_url' => 'https://api.github.com/repos/foolrulez/foolfuuka/tags',

		/**
		 * URL to fetch the changelog
		 */
		'git_changelog_url' => 'https://raw.github.com/foolrulez/FoOlFuuka/master/CHANGELOG.md',

		/**
		 * Minimal PHP requirement
		 */
		'min_php_version' => '5.3.0'
	),

	/**
	 * Preferences for when there's no default specified
	 */
	'preferences' => array(

		'gen' => array(
			'website_title' => 'FoolFuuka',
			'index_title' => 'FoolFuuka',
		),

		'lang' => array(
			'default' => 'en_EN'
		),

		'sphinx' => array(
			'listen' => '127.0.0.1:9306',
			'listen_mysql' => '127.0.0.1:9306',
			'dir' => '/usr/local/sphinx/var',
			'min_word' => 3,
			'memory' => 2047
		),

		'themes' => array(
			'default' => 'default',
			'theme_default_enabled' => true,
			'theme_fuuka_enabled' => true,
			'theme_yotsuba_enabled' => false
		),

		'radix' => array(
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
		),

		'boards' => array(
			'directory' => 'content/boards'
		),

		'comment' => array(
			'secure_tripcode_salt' =>
				'FW6I5Es311r2JV6EJSnrR2+hw37jIfGI0FB0XU5+9lua9iCCrwgkZDVRZ+1PuClqC+78FiA6hhhX
				U1oq6OyFx/MWYx6tKsYeSA8cAs969NNMQ98SzdLFD7ZifHFreNdrfub3xNQBU21rknftdESFRTUr
				44nqCZ0wyzVVDySGUZkbtyHhnj+cknbZqDu/wjhX/HjSitRbtotpozhF4C9F+MoQCr3LgKg+CiYH
				s3Phd3xk6UC2BG2EU83PignJMOCfxzA02gpVHuwy3sx7hX4yvOYBvo0kCsk7B5DURBaNWH0srWz4
				MpXRcDletGGCeKOz9Hn1WXJu78ZdxC58VDl20UIT9er5QLnWiF1giIGQXQMqBB+Rd48/suEWAOH2
				H9WYimTJWTrK397HMWepK6LJaUB5GdIk56ZAULjgZB29qx8Cl+1K0JWQ0SI5LrdjgyZZUTX8LB/6
				Coix9e6+3c05Pk6Bi1GWsMWcJUf7rL9tpsxROtq0AAQBPQ0rTlstFEziwm3vRaTZvPRboQfREta0
				9VA+tRiWfN3XP+1bbMS9exKacGLMxR/bmO5A57AgQF+bPjhif5M/OOJ6J/76q0JDHA==',
		)

	)

);
