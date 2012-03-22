<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
			<a class="brand" href="<?php echo site_url('admin') ?>">
				<?php echo get_setting('fs_gen_site_title'); ?> - <?php echo _('Control Panel'); ?>
			</a>
			<ul class="nav">
				<li class="active">
					<a href="<?php echo site_url('admin') ?>">Home</a>
				</li>
			</ul>
			<ul class="nav pull-right">
				<li><a href="<?php echo site_url() ?>"><?php echo _('Boards') ?></a></li>
				<li class="divider-vertical"></li>
				<?php
				if ((isset($this->tank_auth) &&
					$this->tank_auth->is_allowed()) ||
					(isset($this->tank_auth) &&
					$this->tank_auth->is_logged_in())) :
					?>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">
							<?php echo $this->tank_auth->get_username(); ?>
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu">
							<li>
								<a href="<?php echo site_url('account'); ?>">
									<?php echo _("Your Profile") ?>
								</a>
							</li>
							<li>
								<a href="<?php echo site_url('/admin/auth/logout'); ?>">
									<?php echo _("Logout") ?>
								</a>
							</li>
						</ul>
					</li>
				<?php else : ?>
					<li><a href="<?php echo site_url('admin/auth/login') ?>"><?php echo _('Login') ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
</div>