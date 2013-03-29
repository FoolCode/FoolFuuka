<?php
if ( ! defined('DOCROOT'))
{
	exit('No direct script access allowed');
}
?>

<table class="table table-hover">
	<thead>
		<tr>
			<th class="span6"><?= __('Poster') ?></th>
			<th class="span2"><?= __('Total Posts') ?></th>
			<th class="span2"><?= __('First Seen') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php $data_array = json_decode($data, true)?>
		<?php foreach ($data_array as $d) : ?>
		<tr>
			<td>
				<?php
				$params = [\Radix::getSelected()->shortname, 'search'];
				if ($d['name'])
				{
					array_push($params, 'username/' . urlencode($d['name']));
				}
				if ($d['trip'])
				{
					array_push($params, 'tripcode/' . urlencode($d['trip']));
				}
				$poster_link = Uri::create($params);
				?>
				<a href="<?= $poster_link ?>">
					<span class="poster_name"><?= $d['name'] ?></span> <span class="poster_trip"><?= $d['trip'] ?></span>
				</a>
			</td>
			<td><?= $d['postcount'] ?></td>
			<td><?= date('D M d H:i:s Y', $d['firstseen']) ?></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>