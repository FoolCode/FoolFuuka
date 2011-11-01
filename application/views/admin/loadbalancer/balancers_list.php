<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>

<div class="table">
	<h3><?php echo _('Balancers'); ?></h3>
	<span class="clearfix">
	<?php
	echo _('FoOlSlide load balancers are servers that serve images cloned from the master server. You can host a load balancer by either using an external service (like a Content Delivery Network), or use another FoOlSlide installation in "client mode" on another server.');

	echo '<br/><br/>';

	echo sprintf(_('The process of setting up a FoOlSlide Balancer is very simple: you just install a slide on the other server, provide it with the URL to this slide (%s), and add the URL for the other slide below. The rest is automatic.'), site_url());

	echo '<br/><br/>';
	echo _('The percentages are for ease and you can set each host to any percentage. For example, if you set two load balancers at 100%, they will actually operate at 50% each.');

	echo '<br/><br/>';

	echo _('Currently, the load balancer does not support serving downloadable compressed archives. These will be served by the master FoOlSlide installation instead. Please keep this in mind while you are distributing the percentages.');

	echo '<br/><br/>';
	?>
	</span>
	<div style="padding-right: 10px">
		<?php echo form_open(); ?>
		<table class="zebra-striped">
			<thead>
				<th><?php echo _('URL'); ?></th>
				<th><?php echo _('Priority'); ?></th>
			</thead>
			<tbody>
			<?php
				foreach ($balancers as $key => $item)
				{
					echo '<tr>
						<td>'.form_input('url[]', $item["url"]).'</td>
						<td width="140px">
							<div class="input-append">
								<input style="text-align: right; width: 100px" type="number" name="priority[]" min="0" max="100" value="'.form_prep($item["priority"]).'" />
								<span class="add-on">%</span>
							</div>
						</td>';
					echo '</tr>';
				}
				$url["value"] = "";
					echo '<tr>
						<td>'.form_input('url[]').'</td>
						<td width="140px">
							<div class="input-append">
								<input style="text-align: right; width: 100px" type="number" name="priority[]" min="0" max="100" value="0" />
								<span class="add-on">%</span>
							</div>
						</td>';
					echo '</tr>';
			?>
			</tbody>
		</table>
	<?php
		echo form_submit('submit', _("Add/Save"));
		echo form_close();
		
		$form = array();
		$form[] = array(
			_('IPs of Load Balancing Servers'),
			array(
				'type' => 'input',
				'value' => (isset($ips) && is_array($ips))?$ips:array(),
				'name' => 'fs_balancer_ips',
				'help' => _('Add the IPs of the servers used for balancing. This will prevent them from being limited via the nationality filter.')
			)
		);
		echo form_open('', array('class' => 'form-stacked'));
		echo tabler($form, FALSE, TRUE);
		echo form_close();
	?>
	</div>
</div>
