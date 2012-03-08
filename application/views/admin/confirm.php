<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

?>

<div class="alert alert-block alert-<?php echo $alert_level ?> fade in">
	<p><?php echo $message ?></p>
	<p><?php echo form_open();
		echo form_submit(array(
			'name' => 'confirm', 
			'value' => _('Confirm'), 
			'class' => 'btn btn-danger',
			'style' => 'margin-right:6px;'));
		echo '<input type="button" onClick="history.back()" class="btn" value="'. _('Go back') . '" />';
		echo form_close();
	?></p>
</div>
