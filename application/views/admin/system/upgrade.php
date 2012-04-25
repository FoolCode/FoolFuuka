<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<?php
echo _('Current Version') . ': ' . $current_version . '<br/>';
echo ($new_versions ? _('Latest Version Available') . ': ' . ($new_versions[0]->name) : _('You have the latest version of FoOlFuuka.')) . '<br/><br/>';
?>

<?php 
echo form_open(); 
echo form_submit(array(
	'type' => 'submit',
	'name' => 'upgrade', 
	'value' => _('Upgrade or reinstall'),
	'class' => 'btn btn-large'
));
echo form_close();
?>




<br/>
<div class="well">
<?php
if ($new_versions)
{
	echo '<div class="table" style="padding-bottom: 10px; margin-right:10px;">';
	echo '<h3>' . _('Changelog') . '</h3><div class="changelog">';
	$changelog = Markdown($changelog);
	$changelog = str_replace('{{ENHANCEMENT}}', '<span class="badge badge-warning">Enhancement</span>', $changelog);
	$changelog = str_replace('{{BUGFIX}}', '<span class="badge badge-warning">Bugfix</span>', $changelog);
	$changelog = str_replace('{{NEW}}', '<span class="badge badge-success">New</span>', $changelog);
	$changelog = str_replace('{{ATTENTION}}', '<span class="badge badge-warning">Attention</span>', $changelog);
	$changelog = str_replace('{{NEWS}}', '<span class="badge badge-inverse">News</span>', $changelog);
	echo $changelog;
	echo '</div></div>';
}
?>
</div>
