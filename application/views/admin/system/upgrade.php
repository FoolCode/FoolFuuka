<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

	<?php
	echo _('Current Version') . ': ' . $current_version . '<br/>';
	echo ($new_versions ? _('Latest Version Available') . ': ' . ($new_versions[0]->name) : _('You have the latest version of FoOlFuuka.')) . '<br/><br/>';
	?>
	

<br/>

<?php
if ($new_versions)
{
	echo '<div class="table" style="padding-bottom: 10px; margin-right:10px;">';
	echo '<h3>' . _('Changelog') . '</h3><div class="changelog">';
	echo Markdown($changelog);
	echo '</div></div>';
}
