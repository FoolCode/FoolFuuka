<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>


<table class="bordered-table" style="width:600px; margin: 10px auto;">
	<thead>
		<tr>
			<th><?php echo _('Name') ?></th>
			<th><?php echo _('Trip') ?></th>
			<th><?php echo _('Last Seen') ?></th>
			<th><?php echo _('Latest post') ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		$data_array = json_decode($data, TRUE);
		foreach ($data_array as $d)
		{
			echo '<tr><td>'.$d['name'].'</td><td>'.$d['trip'].'</td><td style="width:350px">'.date('d-M-Y H:i:s',$d['max(timestamp)']).'</td><td><a href="'.site_url(array(get_selected_board()->shortname, 'post', $d['num'].(($d['subnum'])?'_'.($d['subnum']):''))).'">&gt;&gt;'.$d['num'].(($d['subnum'])?','.($d['subnum']):'').'</td></tr>';
		}
		?>
	</tbody>
</table>

