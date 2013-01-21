<?php

return [

	'sidebar' => [

		'boards' => [
			"name" => __("Boards"),
			"level" => "admin",
			"default" => "manage",
			"content" => [
				"manage" => ["alt_highlight" => ["board"],
					"level" => "admin", "name" => __("Manage"), "icon" => 'icon-th-list'],
				"add_new" => ["level" => "admin", "name" => __("Add board"), "icon" => 'icon-asterisk'],
				"sphinx" => ["level" => "admin", "name" => __("Sphinx Search"), "icon" => 'icon-search'],
				"preferences" => ["level" => "admin", "name" => __("Preferences"), "icon" => 'icon-check']
			]
		],
		'posts' => [
			"name" => __("Posts"),
			"level" => "mod",
			"default" => "reports",
			"content" => [
				"reports" => ["level" => "mod", "name" => __("Reports"), "icon" => 'icon-tag'],
				"bans" => ["level" => "mod", "name" => __("Bans"), "icon" => 'icon-truck'],
				"appeals" => ["level" => "mod", "name" => __("Pending Appeals"), "icon" => 'icon-heart-empty'],
			]
		]
	]
];