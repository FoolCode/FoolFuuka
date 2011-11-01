<?php if (!defined('BASEPATH'))
	exit('No direct script access allowed'); ?>

<div class="table">

<?php
	if (isset($form_title)) echo '<h3 style="float: left">' . $form_title . '</h3>';
?>
	<span style="float: right; padding: 5px"><?php echo buttoner(); ?></span>
	<hr class="clear"/>
<?php
	if (isset($form_description)) echo '<span class="clearfix">' . $form_description . '</span>';
	echo form_open('', array('class' => 'form-stacked'));
	echo $table;
	echo form_close();
?>
</div>