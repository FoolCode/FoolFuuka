<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?><!DOCTYPE html>
<html>
	<head>
		<title><?php echo get_setting('fs_gen_site_title'); ?> <?php echo _('Control Panel') ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<link rel="stylesheet" type="text/css" href="<?php echo base_url() ?>assets/bootstrap2/css/bootstrap.min.css?v=<?php echo FOOL_VERSION ?>" />
		<link rel="stylesheet" type="text/css" href="<?php echo base_url() ?>assets/admin/admin.css?v=<?php echo FOOL_VERSION ?>" />
		<script type="text/javascript" src="<?php echo site_url() ?>assets/js/jquery.js?v=<?php echo FOOL_VERSION ?>"></script>
		<script type="text/javascript" src="<?php echo site_url() ?>assets/bootstrap2/js/bootstrap.js?v=<?php echo FOOL_VERSION ?>"></script>
		<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/font-awesome/css/font-awesome.css?v=<?php echo FOOL_VERSION ?>" />
		<script type="text/javascript" src="<?php echo site_url() ?>assets/admin/admin.js?v=<?php echo FOOL_VERSION ?>"></script>
		<script type="text/javascript">jQuery().alert();</script>
	</head>

	<body>

		<?php echo $topbar; ?>

		<div class="container-fluid">
			<div class="row-fluid">
				<div class="span2">
					<?php echo $sidebar ?>
				</div>


				<div class="span10">
					<ul class="breadcrumb">
						<?php
						echo '<li>' . $controller_title . '</li>';
						if (isset($function_title))
							echo ' <span class="divider">/</span> <li>' . $function_title . '</li>';
						if (isset($extra_title) && !empty($extra_title))
						{
							$breadcrumbs = count($extra_title);
							$count = 1;
							foreach ($extra_title as $item)
							{
								echo ' <span class="divider">/</span> ';
								if ($count == $breadcrumbs)
									echo '<li class="active">' . $item . '</li>';
								else
									echo '<li>' . $item . '</li>';
							}
						}
						?>
					</ul>

					<?php
					if (isset($function_title))
						echo '<h3>' . $function_title . '</h3>';
					?>
					
					<div class="alerts">
						<?php
						echo get_notices();
						?>
					</div>

					<?php
					echo $main_content_view;
					?>


					<footer class="footer">
						<p style="padding-left: 20px;">FoOlSlide Version <?php
					if (isset($this->tank_auth))
					{
						echo FOOL_VERSION;
						if ($this->tank_auth->is_admin() && (FOOL_VERSION != get_setting('fs_cron_autoupgrade_version') && (get_setting('fs_cron_autoupgrade_version'))))
							echo ' – <a href="' . site_url('admin/system/upgrade/') . '">' . _('New upgrade available:') . ' ' . get_setting('fs_cron_autoupgrade_version') . '</a>';
					}
					?></p>
					</footer>
				</div>
				<div style="clear:both"></div>
			</div>
		</div>


		<!-- Modal Container for Admin Panel -->
		<div id="modal-container" class="modal hide fade">
			<div class="modal-header">
				<a href="#" class="close">&times;</a>
				<h3 id="modal-text-head">Warning!</h3>
			</div>
			<div class="modal-body">
				<p id="modal-text-desc"></p>
				<div id="modal-loading" class="loading"><img src="<?php echo site_url() ?>assets/js/images/loader-18.gif"/></div>
			</div>
			<div class="modal-footer">
				<a href="#" id="modal-btn-no" class="btn primary"><?php echo _('No'); ?></a>
				<a href="#" id="modal-btn-yes" class="btn secondary"><?php echo _('Yes'); ?></a>
			</div>
		</div>
	</body>
</html>