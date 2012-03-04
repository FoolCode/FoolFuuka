<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

$data_array = json_decode($data);
?>

<table class="bordered-table" style="width:600px; margin: 10px auto;">
	<thead>
	<tr>
		<th><?php echo _('Poster') ?></th>
		<th><?php echo _('Last Seen') ?></th>
		<th><?php echo _('Latest post') ?></th>
	</tr>
	</thead>
	<tbody>
	<?php foreach ($data_array as $d) : ?>
	<tr>
		<td>
			<?php
			$params = array(get_selected_radix()->shortname, 'search');
			if ($d['name'])
				array_push($params, 'username/' . urlencode($d['name']));
			if ($d['trip'])
				array_push($params, 'tripcode/' . urlencode($d['trip']));

			$poster_link = site_url($params);
			?>
			<a href="<?php echo $poster_link ?>">
				<span class="poster_name"><?php echo $d['name'] ?></span> <span class="poster_trip"><?php echo $d['trip'] ?></span>
			</a>
		</td>
		<td style="width:350px"><?php date('d-M-Y H:i:s',$d['MAX(timestamp)']) ?></td>
		<td><?php echo $d['COUNT(*)'] ?></td>
	</tr><?php endforeach; ?>
	</tbody>
</table>

<style type="text/css">
	.poster_name {
		color: #117743;
		font-weight: bold;
	}
</style>