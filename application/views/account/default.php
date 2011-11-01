<!DOCTYPE html>
<html>
	<head>
		<title><?php echo get_setting('fs_gen_site_title'); ?> <?php echo _('Control panel') ?></title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <link href="<?php echo base_url() ?>assets/admin/account.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript" src="<?php echo site_url() ?>assets/js/jquery.js"></script>
        <script type="text/javascript">
            function slideDown(item) { jQuery(item).slideDown(); }
            function slideUp(item) { jQuery(item).slideUp(); }
            function slideToggle(item) { jQuery(item).slideToggle(); }
            function confirmPlug(href, text, item)
            {
                if(text != "") var plug = confirm(text);
				else plug = true;
				
                if (plug)
                {
					jQuery(item).addClass('loading');
                    jQuery.post(href, function(result){
						jQuery(item).removeClass('loading');
						if(location.href == result.href) window.location.reload(true);
						location.href = result.href;
					}, 'json');
                }
            }
			
            function addField(e)
            {
				if(jQuery(e).val().length > 0)
				{
					jQuery(e).clone().val('').insertAfter(e);
					jQuery(e).attr('onKeyUp', '');
					jQuery(e).attr('onChange', '');
				}
            }
			
			jQuery(document).ready(function(){
<?php
$CI = & get_instance();
if ($CI->agent->is_browser('MSIE'))
{
	?>

				// Let's make placeholders work on IE and old browsers too
				jQuery('[placeholder]').focus(function() {
					var input = jQuery(this);
					if (input.val() == input.attr('placeholder')) {
						input.val('');
						input.removeClass('placeholder');
					}
				}).blur(function() {
					var input = jQuery(this);
					if (input.val() == '' || input.val() == input.attr('placeholder')) {
						input.addClass('placeholder');
						input.val(input.attr('placeholder'));
					}
				}).blur().parents('form').submit(function() {
					jQuery(this).find('[placeholder]').each(function() {
						var input =jQuery(this);
						if (input.val() == input.attr('placeholder')) {
							input.val('');
						}
					})
				}); <?php } ?>
		});
        </script>

	</head>



	<body>

		<div class="wrapper">

			<div id="background">
				<img src="<?php echo base_url() ?>assets/admin/images/admin_background.png" />
			</div>



			<div id="nav">
				<div class="element">
					<a href="<?php echo site_url(); ?>">
						<img class="icon off" src="<?php echo glyphish(158) ?>" />
						<img class="icon on" src="<?php echo glyphish(158, TRUE) ?>" />
						<?php echo _("Reader") ?></a>
				</div>
				<?php if (isset($this->tank_auth) && $this->tank_auth->is_allowed())
				{ ?><div class="element">
						<a href="<?php echo site_url('admin'); ?>">
							<img class="icon off" src="<?php echo glyphish(116) ?>" />
							<img class="icon on" src="<?php echo glyphish(116, TRUE) ?>" />
							<?php echo _("Admin panel") ?></a>
					</div>
				<?php } ?>
				<?php if (isset($this->tank_auth) && $this->tank_auth->is_logged_in())
				{ ?><div class="element">
						<a href="<?php echo site_url('/account/auth/logout'); ?>">
							<img class="icon off" src="<?php echo glyphish(73) ?>" />
							<img class="icon on" src="<?php echo glyphish(73, TRUE) ?>" />
							<?php echo _("Logout") ?> <?php echo $this->tank_auth->get_username(); ?></a>
					</div>
				<?php } ?>
			</div>

			<div id="header">

				<div class="title"><?php if (isset($this->tank_auth))
					echo get_setting('fs_gen_site_title'); ?> - <?php echo _('Account'); ?></div>

				<div class="subtitle"><?php
					if ($this->tank_auth->is_logged_in())
					{
						echo '<img src="'.get_gravatar($user_email, 16).'" /> ';
						echo $this->tank_auth->get_username() . ': ';
					}
					echo $function_title
				?></div>

				<?php
				if (isset($navbar))
				{
					echo '<div id="navbar">';
					echo $navbar;
					echo '</div>';
				}
				?>
			</div>

			<div id="content_wrap">


				<div class="spacer"></div>


				<div id="center">

					<div class="content">
						<div class="errors">
							<?php
							if (isset($this->notices))
								foreach ($this->notices as $key => $value)
								{
									if ($value["type"] == 'error')
										$color = 'red';
									if ($value["type"] == 'warn')
										$color = 'yellow';
									if ($value["type"] == 'notice')
										$color = 'green';
									if ($value["message"])
										echo '<div class="alert ' . $color . '">' . $value["message"] . '</div>';
								}
							if (isset($this->tank_auth))
							{

								$flashdata = $this->session->flashdata('notices');
								if (!empty($flashdata))
									foreach ($flashdata as $key => $value)
									{
										if ($value["type"] == 'error')
											$color = 'red';
										if ($value["type"] == 'warn')
											$color = 'yellow';
										if ($value["type"] == 'notice')
											$color = 'green';
										if ($value["message"])
											echo '<div class="alert ' . $color . '">' . $value["message"] . '</div>';
									}
							}
							?>
						</div>

<?php echo $main_content_view; ?>

					</div></div>
				<div class="clearer"></div>
			</div>

		</div>

		<div id="footer"><div class="text"></div></div>
	</body>

</html>