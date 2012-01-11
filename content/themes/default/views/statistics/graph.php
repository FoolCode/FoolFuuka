<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<img src="<?php echo site_url(array('content', 'reports', get_selected_board()->shortname)) . $info['location'] . '.png' ?>"/>