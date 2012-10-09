<?php

return array(

	'roles' => array(
		'user' => array(),
		'mod' => array(
			'boards' => array('see_hidden'),
			'comment' => array('see_ip', 'passwordless_deletion', 'limitless_comment', 'reports', 'mod_capcode'),
			'media' => array('see_banned'),
		),
		'admin' => array(
			'boards' => array('edit', 'see_hidden'),
			'comment' => array('see_ip', 'passwordless_deletion', 'limitless_comment', 'reports', 'mod_capcode', 'admin_capcode', 'dev_capcode'),
			'media' => array('see_banned', 'see_hidden', 'limitless_media'),
		),
	),
);
