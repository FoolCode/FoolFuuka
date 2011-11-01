<div class="incontent login">
	<?php 
	if ($show_accept) echo _(sprintf("Accept or reject %s, who applied to the team:", $user_name)) . ' ' . $team_name;
	else echo _(sprintf("Reject your application to team:", $user_name)) . ' ' . $team_name;
	
	?>

	<br/><br/>
	<?php
	if ($show_accept)
	{
		echo form_open();
		echo form_hidden('action', 'accept');
		?>
		<div class="formgroup">
			<div><?php echo form_submit('submit', _('Accept')); ?></div>
		</div>
		<?php echo form_close();
	} ?>
	<?php
	echo form_open();
	echo form_hidden('action', 'reject');
	?>
	<div class="formgroup">
		<div><?php echo form_submit('submit', _('Reject')); ?></div>
	</div>
	<?php echo form_close(); ?>

	<a href="<? echo site_url('/account/teams') ?>" class="button yellow"><?php echo _("Back to your teams") ?></a>

</div>