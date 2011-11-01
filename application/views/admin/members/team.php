<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div class="table">
	<h3 style="float: left"><?php echo _('Team Information'); ?></h3>
	<span style="float: right; padding: 5px"><?php echo buttoner(); ?></span>
	<hr class="clear"/>
	<?php
		echo form_open("", array('class' => 'form-stacked'));
		echo $table;
		echo form_close();
	?>
</div>

<br/>

<div class="table">
	<h3><?php echo _('Members'); ?></h3>
<?php
if ($no_leader) {
	echo form_open("/admin/members/make_team_leader_username/".$team->id, array('class' => 'form-stacked'));
	echo '<fieldset><div class="clearfix">
		<label>'._("Make an user a leader by submitting his username:").'</label>
		<div class="input">';
	echo form_input(array('name' => 'username', 'placeholder' => 'Username', 'style' => 'float: left'));
	echo '	</div>';
	echo form_submit('save', 'Add');
	echo '</fieldset>';
	echo form_close();
}
?>
	<div style="padding-right: 10px; margin-bottom: -5px">
		<?php echo $members; ?>
	</div>
</div>