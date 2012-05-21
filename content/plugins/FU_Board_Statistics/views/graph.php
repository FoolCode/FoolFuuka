<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php if (!empty($data)) : ?>
	<img src="<?php echo site_url(array('content', 'statistics', get_selected_radix()->shortname)) . $info['location'] . '.png' ?>"/>
<?php endif; ?>
