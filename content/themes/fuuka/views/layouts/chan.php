<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title><?php echo $template['title']; ?></title>
		<meta name="generator" content="<?php echo FOOLSLIDE_NAME ?> <?php echo FOOLSLIDE_VERSION ?>" />
		<link rel='index' title='<?php echo get_setting('fs_gen_site_title') ?>' href='<?php echo site_url() ?>' />
		<?php
		if (file_exists('content/themes/' . $this->fu_theme . '/style.css'))
			echo link_tag('content/themes/' . $this->fu_theme . '/style.css?v=' . FOOLSLIDE_VERSION);
		?>
		<script type="text/javascript" src="<?php echo site_url() ?>content/themes/<?php echo $this->fu_theme ? $this->fu_theme : 'default' ?>/plugins.js?v=<?php echo FOOLSLIDE_VERSION ?>"></script>
		<?php echo get_setting('fs_theme_header_code'); ?>
	</head>
	<body>
		<div>
			[
			<?php
			$board_urls = array();
			foreach ($boards as $key => $item)
			{
				$board_urls[] = '<a href="' . $item->href() . '">' . $item->shortname . '</a>';
			}
			echo implode(' / ', $board_urls)
			?>
			] [ <a href="<?php echo site_url() ?>">index</a> / <a href="<?php echo site_url($this->fu_board) ?>">top</a> / <a href="<?php echo site_url(array($this->fu_board, 'statistics')) ?>">statistics</a> / <a href="http://github.com/FoOlRulez/FoOlFuuka/issues">report a bug</a> ]
			[ Select theme (will later be moved to footer): <a href="#" onClick="changeTheme('default')">Default</a> / <a href="#" onClick="changeTheme('fuuka')">Fuuka</a> ]
		</div>

		<div style="min-height:30px;">
			<h1>/<?php echo $board->shortname ?>/ - <?php echo $board->name ?></h1>

			<hr>

			<?php echo $template['partials']['top_tools']; ?>

			<hr>

			<?php echo $template['body']; ?>
		</div>

	</body>
</html>