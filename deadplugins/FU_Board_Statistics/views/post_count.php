<?php
if (!defined('DOCROOT'))
	exit('No direct script access allowed');
?>

<table class="bordered-table table-striped" style="width:600px; margin: 10px auto;">
	<thead>
	<tr>
		<th><?php echo __('Poster') ?></th>
		<th><?php echo __('Total Posts') ?></th>
	</tr>
	</thead>
	<tbody>
	<?php $data_array = json_decode($data, TRUE); ?>
	<?php foreach ($data_array as $d) : ?>
	<tr>
		<td>
			<?php
			$params = array(Radix::get_selected()->shortname, 'search');
			if ($d['name'])
				array_push($params, 'username/' . urlencode($d['name']));
			if ($d['trip'])
				array_push($params, 'tripcode/' . urlencode($d['trip']));

			$poster_link = Uri::create($params);
			?>
			<a href="<?php echo $poster_link ?>">
				<span class="poster_name"><?php echo $d['name'] ?></span> <span class="poster_trip"><?php echo $d['trip'] ?></span>
			</a>
		</td>
		<td><?php echo $d['postcount'] ?></td>
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
