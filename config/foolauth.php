<?php

return array(

	'roles' => array(
		'user' => array(
			'access' => array('user', 'member'),
			'maccess' => array('user')
		),
		'mod' => array(
			'access' => array('mod'),
			'maccess' => array('user', 'mod'),
			'comment' => array('see_ip', 'passwordless_deletion', 'limitless_comment', 'reports', 'mod_capcode'),
			'users' => array('access')
		),
		'admin' => array(
			'access' => array('admin'),
			'maccess' => array('user', 'mod', 'admin'),
			'boards' => array('edit'),
			'comment' => array('see_ip', 'passwordless_deletion', 'limitless_comment', 'reports', 'mod_capcode', 'admin_capcode', 'dev_capcode'),
			'users' => array('access', 'change_credentials', 'change_group')
		),
	),
);
