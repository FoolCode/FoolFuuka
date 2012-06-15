<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div class="navbar navbar-fixed-top">
	<div class="navbar-inner">
		<div class="container">
			<a class="brand" href="<?php echo site_url('admin') ?>">
				<?php echo get_setting('fs_gen_site_title'); ?> - <?php echo __('Control Panel'); ?>
			</a>
			<ul class="nav">
				<li class="active">
					<a href="<?php echo site_url('admin') ?>">Home</a>
				</li>
			</ul>
			<ul class="nav pull-right">
				<li><a href="<?php echo site_url('@default') ?>"><?php echo __('Boards') ?></a></li>
				<li class="divider-vertical"></li>
				<?php
				if ((isset($this->auth) &&
					$this->auth->is_mod_admin()) ||
					(isset($this->auth) &&
					$this->auth->is_logged_in())) :
					?>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown">
							<?php echo $this->auth->get_username(); ?>
							<b class="caret"></b>
						</a>
						<ul class="dropdown-menu">
							<li>
								<a href="<?php echo site_url('admin/auth/change_email'); ?>">
									<?php echo __("Your Profile") ?>
								</a>
							</li>
							<li>
								<a href="<?php echo site_url('/admin/auth/logout'); ?>">
									<?php echo __("Logout") ?>
								</a>
							</li>
						</ul>
					</li>
				<?php else : ?>
					<li><a href="<?php echo site_url('admin/auth/login') ?>"><?php echo __('Login') ?></a></li>
				<?php endif; ?>
			</ul>
		</div>
	</div>
</div>