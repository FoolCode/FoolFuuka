<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

<?php if (!empty($data)) : ?>
	<img src="<?php echo Uri::create(array('foolfuuka', 'statistics', \Radix::getSelected()->shortname)) . $info['location'] . '.png' ?>"/>
<?php endif; ?>
