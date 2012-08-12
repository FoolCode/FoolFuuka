<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

<table class="bordered-table table-striped" style="width:600px; margin: 10px auto;">
	<thead>
		<tr>
			<th><?php echo __('Poster') ?></th>
			<th><?php echo __('Total Posts') ?></th>
			<th><?php echo __('Availability') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php $data_array = json_decode($data); ?>
		<?php foreach ($data_array as $d) : ?>
		<tr>
			<td style="width:50%; max-width:50%">
				<?php
				$params = array(Radix::get_selected()->shortname, 'search');
				if ($d->name)
					array_push($params, 'username/' . urlencode($d->name));
				if ($d->trip)
					array_push($params, 'tripcode/' . urlencode($d->trip));

				$poster_link = Uri::create($params);

				$values = (($d->std2 < $d->std1) ? array($d->avg2 - $d->std2, $d->avg2 + $d->std2) : array($d->avg1 - $d->std1, $d->avg1 + $d->std1));
				$val_st = ($values[0] + 86400) % 86400;
				$val_ed = ($values[1] + 86400) % 86400;
				?>
				<a href="<?php echo $poster_link ?>">
					<span class="poster_name"><?php echo fuuka_htmlescape($d->name) ?></span> <span class="poster_trip"><?php echo fuuka_htmlescape($d->trip) ?></span>
				</a>
			</td>
			<td style="text-align:center"><?php echo $d->posts ?></td>
			<td style="text-align:center">
				<?php echo sprintf('%02d:%02d', floor($val_st / 3600), floor($val_st / 60) % 60) ?> - <?php echo sprintf('%02d:%02d', floor($val_ed / 3600), floor($val_ed / 60) % 60) ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<style type="text/css">
	.poster_name, .poster_trip {
		color: #117743;
	}
	.poster_name {
		font-weight: bold;
	}
</style>
