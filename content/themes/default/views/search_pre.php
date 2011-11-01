<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>
<div class="large">
	<div class="center">
		<?php echo _('Search series'); ?>:<br/>
		<?php
		echo form_open("/reader/search/");
		echo form_input(array('name' => 'search', 'placeholder' => _('To search series, type and hit enter'), 'id' => 'searchbox'));
		echo form_close();
		?>
	</div>
</div>