<?php if (!defined('DOCROOT')) exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
		<meta name="robots" content="noarchive"/>
		<meta name="description" content=""/>
		<meta name="keywords" content=""/>
		<meta name="viewport" content="width=device-width,initial-scale=1"/>
		<!--
		<meta name="rating" content="RTA-5042-1996-1400-1577-RTA"/>
		<link rel="shortcut icon" href="//static.4chan.org/image/favicon-test.ico"/>
		<link rel="stylesheet" title="switch" href="//static.4chan.org/css/yotsubanew.104.css"/>
		<link rel="alternate stylesheet" style="text/css" href="//static.4chan.org/css/yotsubanew.104.css" title="Yotsuba New"/>
		<link rel="alternate stylesheet" style="text/css" href="//static.4chan.org/css/yotsubluenew.104.css" title="Yotsuba B New"/>
		<link rel="alternate stylesheet" style="text/css" href="//static.4chan.org/css/futabanew.104.css" title="Futaba New"/>
		<link rel="alternate stylesheet" style="text/css" href="//static.4chan.org/css/burichannew.104.css" title="Burichan New"/>
		<link rel="stylesheet" href="//static.4chan.org/css/yotsubamobile.104.css"/>
		<link rel="apple-touch-icon" href="//static.4chan.org/image/apple-touch-icon-iphone.png"/>
		<link rel="apple-touch-icon" sizes="72x72" href="//static.4chan.org/image/apple-touch-icon-ipad.png"/>
		<link rel="apple-touch-icon" sizes="114x114" href="//static.4chan.org/image/apple-touch-icon-iphone-retina.png"/>
		<link rel="apple-touch-icon" sizes="144x144" href="//static.4chan.org/image/apple-touch-icon-ipad-retina.png"/>
		<link rel="alternate" title="RSS feed" href="/htmlnew/index.rss" type="application/rss+xml"/>
		-->
		<title><?= $template['title'] ?></title>

		<?php
		foreach($this->fallback_override('style.css', $this->get_config('extends_css')) as $css)
		{
			echo link_tag($css);
		}
		?>

		<?php if (get_setting('fu.sphinx.global')) : ?>
			<link rel="search" type="application/opensearchdescription+xml" title="<?= get_setting('fs_gen_site_title', FOOL_PREF_GEN_WEBSITE_TITLE) ?> " href="<?= Uri::create('@system/functions/opensearch') ?>" />
		<?php endif; ?>
	</head>
	<body>

		<?php if ($disable_headers !== TRUE) : ?>
			<div id="boardNavDesktop" class="desktop">
				<?php
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
					$top_nav = Plugins::run_hook('fu_themes_yotsuba_top_nav_buttons', array($top_nav), 'simple');

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
				?>
			</div>

			<div id="boardNavMobile" class="mobile">
				<div class="boardSelect">
					<strong>Board:</strong>
					<select id="boardSelectMobile">
						<?php foreach(Radix::get_all() as $board) : ?>
							<option value="<?= $board->shortname ?>"<?= (Radix::get_selected() && $board->shortname == Radix::get_selected()->shortname ? ' selected="selected"' : '' ) ?>></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="boardBanner">
				<img src="" class="title" alt=""/>
				<?php if (Radix::get_selected()) : ?><div class="boardTitle"><?= Radix::get_selected()->formatted_title ?></div><?php endif; ?>
			</div>

			<?= $template['partials']['tools_reply_box'] ?>

			<hr/>

			<?php if (get_setting('fs_theme_header_text')) : ?>
			<div class="globalMessage"><?= get_setting('fs_theme_header_text') ?></div>

			<hr/>
			<?php endif; ?>

			<?= form_open_multipart(Radix::get_selected()->shortname . '/board', array('name' => 'delform', 'id' => 'delform')) ?>
		<?php endif; ?>


		<?= $template['body'] ?>

		<?php if ($disable_headers !== TRUE) : ?>

			<div style="float: right;">
				<div class="deleteform desktop" style="text-align: right">
					<input type="hidden" name="mode" value="usrdel"/>Delete Post [<input type="checkbox" name="onlyimgdel" value="on"/>File Only] Password <input type="password" name="pwd" value="<?= $this->fu_reply_password ?>" /> <input type="submit" name="mode" value="Delete" /><br/>
					Report Reason <input type="text" name="reason" /> <input type="submit" name="mode" value="Report" />
				</div>
				<div class="stylechanger desktop">
					Style
					<select onchange="setActiveStyleSheet(this.value); return false;">
						<?php
							foreach ($this->get_available_themes() as $theme)
							{
								if (($theme = $this->get_by_name($theme)))
								{
									echo '<option value="' . $theme['directory'] . '" >' . $theme['name'] . '</option>';
								}
							}
						?>
					</select>
				</div>
			</div>
			<?= form_close() ?>

			<?php if (isset($pagination) && !is_null($pagination['total']) && ($pagination['total'] >= 1)) : ?>
				<div class="pagelist desktop">
					<div class="prev">
						<span>
							<?php if ($pagination['current_page'] == 1) : ?>
								<?= __('Previous') ?>
							<?php else : ?>
								<a href="<?= $pagination['base_url'] . ($pagination['current_page'] - 1) ?>/"><?= __('Previous') ?></a>
							<?php endif; ?>
						</span>
					</div>

					<div class="pages">
						<?php
						if ($pagination['total'] <= 15) :
							for ($index = 1; $index <= $pagination['total']; $index++)
							{
								if (($pagination['current_page'] == $index))
									echo '[<strong>' . $index . '</strong>] ';
								else
									echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>] ';
							}
						else :
							if ($pagination['current_page'] < 15) :
								for ($index = 1; $index <= 15; $index++)
								{
									if (($pagination['current_page'] == $index))
										echo '[<strong>' . $index . '</strong>] ';
									else
										echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>] ';
								}
								echo '[...] ';
							else :
								for ($index = 1; $index < 10; $index++)
								{
									if (($pagination['current_page'] == $index))
										echo '[<strong>' . $index . '</strong>] ';
									else
										echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>] ';
								}
								echo '<li class="disabled"><span>...</span></li>';
								for ($index = ((($pagination['current_page'] + 2) > $pagination['total'])
									? ($pagination['current_page'] - 4) : ($pagination['current_page'] - 2)); $index <= ((($pagination['current_page'] + 2) > $pagination['total'])
									? $pagination['total'] : ($pagination['current_page'] + 2)); $index++)
								{
									if (($pagination['current_page'] == $index))
										echo '[<strong>' . $index . '</strong>] ';
									else
										echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>] ';
								}
								if (($pagination['current_page'] + 2) < $pagination['total'])
								{
									echo '[...] ';
								}
							endif;
						endif;
						?>
					</div>

					<div class="next">
						<span>
							<?php if ($pagination['total'] == $pagination['current_page']) : ?>
								<?= __('Next') ?>
							<?php else : ?>
								<a href="<?= $pagination['base_url'] . ($pagination['current_page'] + 1) ?>/"><?= __('Next') ?></a>
							<?php endif; ?>
						</span>
					</div>
				</div>

				<div class="mPagelist mobile">
					<div class="pages">
						<span><strong>0</strong></span>
						<span><a href="1">1</a></span>
						<span>2</span> <span>3</span> <span>4</span> <span>5</span> <span>6</span> <span>7</span> <span>8</span> <span>9</span> <span>10</span> <span>11</span> <span>12</span> <span>13</span> <span>14</span> <span>15</span>
					</div>
					<div class="prev">Previous</div>
					<div class="next"><a href="1" class="button">Next</a></div>
				</div>
			<?php endif; ?>

		<?php endif; ?>

		<div id="boardNavDesktopFoot" class="desktop">
			<?php
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
				$top_nav = Plugins::run_hook('fu_themes_yotsuba_top_nav_buttons', array($top_nav), 'simple');

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
			?>
		</div>

		<div id="absbot" class="absBotText">- <a href="http://www.2chan.net/" target="_top" rel="nofollow">futaba</a> + <a href="//www.4chan.org/" target="_top">yotsuba</a> -<br/>
			All trademarks and copyrights on this page are owned by their respective parties. Images uploaded are the responsibility of the Poster. Comments are owned by the Poster.
		</div>
	</body>
</html>