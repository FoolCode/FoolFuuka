<?php if (!defined('DOCROOT')) exit('No direct script access allowed'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="generator" content="<?= FOOL_NAME ?> <?= FOOL_VERSION ?>" />
		<?= $template['metadata'] ?>

		<title><?= $template['title'] ?></title>
		<?php
		foreach($this->fallback_override('style.css', $this->get_config('extends_css')) as $css)
		{
			echo link_tag($css);
		}
		?>

		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script src="<?= Uri::base() . $this->fallback_asset('plugins.js') ?>" type="text/javascript"></script>
		<?php if (get_setting('fu.sphinx.global')) : ?>
			<link rel="search" type="application/opensearchdescription+xml" title="<?= get_setting('fs_gen_site_title', FOOL_PREF_GEN_WEBSITE_TITLE) ?> " href="<?= Uri::create('@system/functions/opensearch') ?>" />
		<?php endif; ?>
		<?= get_setting('fs_theme_header_code') ?>
	</head>
	<body>
<?php if ($disable_headers !== TRUE) : ?>
		<div><?php
			$board_urls = array();
			foreach (Radix::get_all() as $key => $item)
			{
				$board_urls[] = '<a href="' . $item->href . '">' . $item->shortname . '</a>';
			}

			if (!empty($board_urls))
			{
				echo '[ ' . implode(' / ', $board_urls) . ' ]';
			}
		?>

		<?php
			$board_urls = array();

			$board_urls[] = '<a href="' . Uri::base() . '">' . strtolower(__('Index')) . '</a>';
			if (Radix::get_selected())
			{
				$board_urls[] = '<a href="' . Uri::create(Radix::get_selected()->shortname) . '">' . strtolower(__('Top')) . '</a>';
				$board_urls[] = '<a href="' . Uri::create(array(Radix::get_selected()->shortname, 'statistics')) . '">' . strtolower(__('Statistics')) . '</a>';
			}
			$board_urls[] = '<a href="https://github.com/FoOlRulez/FoOlFuuka/issues">' . strtolower(__('Report Bug')) . '</a>';

			echo '[ ' . implode(' / ', $board_urls) . ' ]';
		?>

		<?php
			$top_nav = array();
			$top_nav = Plugins::run_hook('fu_themes_generic_top_nav_buttons', array($top_nav), 'simple');
			$top_nav = Plugins::run_hook('fu_themes_fuuka_top_nav_buttons', array($top_nav), 'simple');

			if (!empty($top_nav))
			{
				echo '[ ';
				foreach ($top_nav as $key => $nav)
				{
					echo '<a href="' . $nav['href'] . '">' . strtolower($nav['text']) . '</a>';
					if ($key < count($top_nav) - 1)
					{
						echo ' / ';
					}
				}
				echo ' ]';
			}
		?></div>

		<div style="min-height: 30px;">
			<h1><?= (Radix::get_selected()) ? Radix::get_selected()->formatted_title : '' ?></h1>
			<?php if (isset($section_title)) : ?>
				<h2><?= $section_title ?></h2>
			<?php elseif (get_setting('fs_theme_header_text')) : ?>
				<div><?= get_setting('fs_theme_header_text') ?></div>
			<?php endif; ?>

			<hr />

			<?= $template['partials']['tools_search'] ?>
			<hr />
			<?php if ($is_page) echo $template['partials']['tools_reply_box']; ?>
		</div>
<?php endif; ?>

		<?= $template['body'] ?>

		<?php if ($disable_headers !== TRUE) : ?>
			<?php if (isset($pagination) && !is_null($pagination['total']) && ($pagination['total'] >= 1)) : ?>
				<table style="float: left;">
					<tbody>
						<tr>
							<td colspan="7" class="theader"><?= __('Navigation') ?></td>
						</tr>
						<tr>
							<td class="postblock"><?= __('View Posts') ?></td>
							<td>
								<?php if ($pagination['current_page'] == 1) : ?>
									[<?= __('Prev') ?>]
								<?php else : ?>
									[<a href="<?= $pagination['base_url'] . ($pagination['current_page'] - 1) ?>/"><?= __('Prev') ?></a>]
								<?php endif; ?>

								<?php
								if ($pagination['total'] <= 15) :
									for ($index = 1; $index <= $pagination['total']; $index++)
									{
										if (($pagination['current_page'] == $index))
											echo '[<b>' . $index . '</b>]';
										else
											echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>]';
									}
								else :
									if ($pagination['current_page'] < 15) :
										for ($index = 1; $index <= 15; $index++)
										{
											if (($pagination['current_page'] == $index))
												echo '[<b>' . $index . '</b>]';
											else
												echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>]';
										}
										echo '[...]';
									else :
										for ($index = 1; $index < 10; $index++)
										{
											if (($pagination['current_page'] == $index))
												echo '[<b>' . $index . '</b>]';
											else
												echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>]';
										}
										echo '<li class="disabled"><span>...</span></li>';
										for ($index = ((($pagination['current_page'] + 2) > $pagination['total'])
											? ($pagination['current_page'] - 4) : ($pagination['current_page'] - 2)); $index <= ((($pagination['current_page'] + 2) > $pagination['total'])
											? $pagination['total'] : ($pagination['current_page'] + 2)); $index++)
										{
											if (($pagination['current_page'] == $index))
												echo '[<b>' . $index . '</b>]';
											else
												echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>]';
										}
										if (($pagination['current_page'] + 2) < $pagination['total'])
										{
											echo '[...]';
										}
									endif;
								endif;
								?>

								<?php if ($pagination['total'] == $pagination['current_page']) : ?>
									[<?= __('Next') ?>]
								<?php else : ?>
									[<a href="<?= $pagination['base_url'] . ($pagination['current_page'] + 1) ?>/"><?= __('Next') ?></a>]
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
			<?php endif; ?>

			<div style="float: right;">
				<?php
					$bottom_nav = array();
					$bottom_nav = Plugins::run_hook('fu_themes_generic_bottom_nav_buttons', array($bottom_nav), 'simple');
					$bottom_nav = Plugins::run_hook('fu_themes_fuuka_bottom_nav_buttons', array($bottom_nav), 'simple');

					if (!empty($bottom_nav))
					{
						echo '[ ';
						foreach ($bottom_nav as $key => $nav)
						{
							echo '<a href="' . $nav['href'] . '">' . $nav['text'] . '</a>';
							if ($key < count($bottom_nav) - 1)
							{
								echo ' / ';
							}
						}
						echo ' ]';
					}
				?>

				<?php
					$theme_links = array();
					foreach ($this->get_available_themes() as $theme)
					{
						if (($theme = $this->get_by_name($theme)))
						{
							$theme_links[] = '<a href="' . Uri::create(array('@system', 'functions', 'theme', $theme['directory'])) . '" onclick="changeTheme(\'' . $theme['directory'] . '\'); return false;">' . $theme['name'] . '</a>';
						}
					}
					echo 'Theme [ ' . implode(' / ', $theme_links) . ' ]';
				?>
			</div>
		<?php endif; ?>

		<?php
			if (get_setting('fs_theme_footer_text'))
			{
				echo '<div style="clear: both;">' . get_setting('fs_theme_footer_text') . '</div>';
			}
		?>

		<script>
			var backend_vars = <?= json_encode($backend_vars) ?>;
		</script>

		<?php if (get_setting('fs_theme_google_analytics')) : ?>
			<script>
				var _gaq=[['_setAccount','<?= get_setting('fs_theme_google_analytics') ?>'],['_trackPageview'],['_trackPageLoadTime']];
				(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
					g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
					s.parentNode.insertBefore(g,s)}(document,'script'));
			</script>
		<?php endif; ?>

		<?= get_setting('fs_theme_footer_code') ?>
	</body>
</html>
