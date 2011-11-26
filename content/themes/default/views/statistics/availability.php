<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<table class="bordered-table" style="width:600px; margin: 10px auto;">
	<thead>
		<tr>
			<th><?php echo _('Name') ?></th>
			<th><?php echo _('Trip') ?></th>
			<th><?php echo _('Posts') ?></th>
			<th><?php echo _('Availability') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		$data_array = json_decode($data);
		foreach ($data_array as $d)
		{
			echo '<tr>';
			echo '<td>' . fuuka_htmlescape($d->name) . '</td>';

			echo '<td>' . fuuka_htmlescape($d->trip) . '</td>';
			echo '<td>' . $d->posts . '</td>';
			echo '<td style="width:100px">';
			$values = (($d->std2 < $d->std1) ? array($d->avg2 - $d->std2, $d->avg2 + $d->std2) : array($d->avg1 - $d->std1, $d->avg1 + $d->std1));
			$val1 = ($values[0] + 86400) % 86400;
			$val2 = ($values[1] + 86400) % 86400;
			echo sprintf('%02d:%02d', floor($val1 / 3600), floor($val1 / 60) % 60);
			echo ' - ';
			echo sprintf('%02d:%02d', floor($val2 / 3600), floor($val2 / 60) % 60);
			echo '</td>';
		}
		?>
	</tbody>
</table>