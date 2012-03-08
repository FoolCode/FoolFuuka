<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

?>

<div class="alert alert-block alert-<?php echo $alert_level ?> fade in">
	<p><?php echo $message ?></p>
	<p><?php echo form_open();
		echo '<div class="btn-group">';
		echo form_submit(array('name' => 'confirm', 'value' => _('Confirm'), 'class' => 'btn btn-danger'));
		echo '<a href="' . $this->agent->referrer() . '" onClick="history.back()" class="btn">'. _('Go back') . '</a>';
		echo '</div>';
		echo form_close();
	?></p>
</div>
