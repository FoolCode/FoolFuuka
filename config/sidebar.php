<?php

return [
	'sidebar' => [
		'boards' => [
			'name' => __('Boards'),
			'level' => 'admin',
			'default' => 'manage',
			'content' => [
				'manage' => [
					'alt_highlight' => ['board'],
					'level' => 'admin',
					'name' => __('Manage'),
					'icon' => 'icon-th-list'
				],
				'search' => [
					'level' => 'admin',
					'name' => __('Search'),
					'icon' => 'icon-search'
				],
				'preferences' => [
					'level' => 'admin',
					'name' => __('Preferences'),
					'icon' => 'icon-check'
				]
			]
		],
		'moderation' => [
			'name' => __('Moderation'),
			'level' => 'mod',
			'default' => 'reports',
			'content' => [
				'reports' => [
					'level' => 'mod',
					'name' => __('Reports'),
					'icon' => 'icon-tag',
					'notification' => \Report::count()
				],
				'bans' => [
					'level' => 'mod',
					'name' => __('Bans'),
					'icon' => 'icon-truck'
				],
				'appeals' => [
					'level' => 'mod',
					'name' => __('Pending Appeals'),
					'icon' => 'icon-heart-empty'
				]
			]
		]
	]
];