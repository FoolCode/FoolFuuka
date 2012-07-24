<?php

return array(

	'sidebar' => array(

		'boards' => array(
			"name" => __("Boards"),
			"level" => "admin",
			"default" => "manage",
			"content" => array(
				"manage" => array("alt_highlight" => array("board"),
					"level" => "admin", "name" => __("Manage"), "icon" => 'icon-th-list'),
				"add_new" => array("level" => "admin", "name" => __("Add board"), "icon" => 'icon-asterisk'),
				"sphinx" => array("level" => "admin", "name" => __("Sphinx Search"), "icon" => 'icon-search'),
				"preferences" => array("level" => "admin", "name" => __("Preferences"), "icon" => 'icon-check')
			)
		),
		'posts' => array(
			"name" => __("Posts"),
			"level" => "mod",
			"default" => "reports",
			"content" => array(
				"reports" => array("level" => "mod", "name" => __("Reports"), "icon" => 'icon-tag'),
			)
		)

	)
);