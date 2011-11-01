<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');

$CI = & get_instance();

$CI->buttoner = array();

if ($CI->tank_auth->get_user_id() == $user->id)
	$CI->buttoner[] = array(
		'href' => site_url('/account/auth/change_password/'),
		'text' => _('Reset password'),
	);

if ($CI->tank_auth->get_user_id() == $user->id)
	$CI->buttoner[] = array(
		'href' => site_url('/account/auth/change_email/'),
		'text' => _('Change email'),
	);

if ($CI->tank_auth->is_admin() && !$CI->tank_auth->is_admin($user->id))
	$CI->buttoner[] = array(
		'href' => site_url('/admin/members/make_admin/' . $user->id),
		'text' => _('Make administrator'),
		'plug' => _('Are you sure you want to make this user an administrator?')
	);

if ($CI->tank_auth->is_admin() && $CI->tank_auth->is_admin($user->id))
	$CI->buttoner[] = array(
		'href' => site_url('/admin/members/remove_admin/' . $user->id),
		'text' => _('Remove administrator'),
		'plug' => _('Are you sure you want to remove this user from the administrator group?')
	);

if ($CI->tank_auth->is_admin() && !$CI->tank_auth->is_admin($user->id) && !$CI->tank_auth->is_mod($user->id))
	$CI->buttoner[] = array(
		'href' => site_url('/admin/members/make_mod/' . $user->id),
		'text' => _('Make moderator'),
		'plug' => _('Are you sure you want to make this user a moderator?')
	);

if ($CI->tank_auth->is_admin() && $CI->tank_auth->is_mod($user->id))
	$CI->buttoner[] = array(
		'href' => site_url('/admin/members/remove_mod/' . $user->id),
		'text' => _('Remove moderator'),
		'plug' => _('Are you sure you want to remove this user from the moderator group?')
	);
if ($CI->tank_auth->get_user_id() == $user->id)
	$CI->buttoner[] = array(
		'text' => _('Edit'),
		'href' => '',
		'onclick' => "slideToggle('.plain'); slideToggle('.edit'); return false;"
	);

echo $table;
?>

<div class="table">
	<h3 style="float: left"><?php echo _('Member Profile'); ?></h3>
	<span style="float: right; padding: 5px"><?php echo buttoner(); ?></span>
	<hr class="clear"/>
	<div style="padding-right: 10px">
		<ul class="media-grid" style="float: left; margin: 10px 10px -10px -10px">
			<li>
				<a href="#">
					<img src="<?php echo get_gravatar($user->email, 150); ?>" class="thumbnail"/>
				</a>
			</li>
		</ul>
	<?php
		echo form_open('', array('class' => 'form-stacked'));
		echo $profile;
		echo form_close();
	?>
	</div>
	
</div>