<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

?>

<table class="bordered-table table-striped" style="width:600px; margin: 10px auto;">
	<thead>
	<tr>
		<th><?php echo __('Poster') ?></th>
		<th><?php echo __('Last Seen') ?></th>
		<th><?php echo __('Latest Post') ?></th>
	</tr>
	</thead>
	<tbody>
	<?php $data_array = json_decode($data); ?>
	<?php foreach ($data_array as $d) : ?>
	<tr>
		<td>
			<?php
			$params = array(get_selected_radix()->shortname, 'search');
			if ($d->name)
				array_push($params, 'username/' . urlencode($d->name));
			if ($d->trip)
				array_push($params, 'tripcode/' . urlencode($d->trip));

			$poster_link = site_url($params);
			?>
			<a href="<?php echo $poster_link ?>">
				<span class="poster_name"><?php echo $d->name ?></span> <span class="poster_trip"><?php echo $d->trip ?></span>
			</a>
		</td>
		<td style="width:350px; text-align:center;"><?php echo date('d-M-Y H:i:s', $d->{'MAX(timestamp)'}) ?></td>
		<td><a href="<?php echo site_url(array(get_selected_radix()->shortname, 'post', $d->num . ($d->subnum ? '_' . $d->subnum : ''))) ?>">
			&gt;&gt;<?php echo $d->num . ($d->subnum ? ',' . $d->subnum : '') ?>
		</a></td>
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
