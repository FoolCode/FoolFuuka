<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div class="alert" style="margin:15%;">
	<?php echo '<h4 class="alert-heading">' . (!isset($type)?__('Error!'):$type) . '</h4> ' . $error ?>
</div>
