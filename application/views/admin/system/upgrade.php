<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php
echo __('Current Version') . ': ' . $current_version . '<br/>';
echo ($new_versions ? __('Latest Version Available') . ': ' . (end($new_versions)->name) : __('You have the latest version of {{FOOL_NAME}}.')) . '<br/><br/>';
?>

<?php
echo form_open();
echo form_submit(array(
	'type' => 'submit',
	'name' => 'upgrade',
	'value' => __('Upgrade or reinstall'),
	'class' => 'btn btn-large'
));
echo form_close();
?>




<br/>
<div class="well">
	<?php
	echo '<div class="table" style="padding-bottom: 10px; margin-right:10px;">';
	echo '<h3>' . __('Changelog') . '</h3><div class="changelog">';
	$changelog = Markdown($changelog);
	$changelog = str_replace('{{ENHANCEMENT}}', '<span class="label label-warning">Enhancement</span>', $changelog);
	$changelog = str_replace('{{BUGFIX}}', '<span class="label label-info">Bugfix</span>', $changelog);
	$changelog = str_replace('{{NEW}}', '<span class="label label-success">New</span>', $changelog);
	$changelog = str_replace('{{ATTENTION}}', '<span class="label label-important">Attention</span>', $changelog);
	$changelog = str_replace('{{NEWS}}', '<span class="label label-inverse">News</span>', $changelog);
	echo $changelog;
	echo '</div></div>';
	?>
</div>
