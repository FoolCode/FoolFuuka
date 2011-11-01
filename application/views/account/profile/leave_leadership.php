<div class="incontent login">
	<?php echo _("You're leaving the team leadership:") . ' ' . $team_name ?>

<br/><br/>
	<?php echo form_open(); ?>
	<div class="formgroup">
		<div><?php echo form_submit('submit', _('Leave')); ?></div>
	</div>
	<?php echo form_close(); ?>

<a href="<? echo site_url('/account/teams') ?>" class="button yellow"><?php echo _("Back to your teams") ?></a>

</div>