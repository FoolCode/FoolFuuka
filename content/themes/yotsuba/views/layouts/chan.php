<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title><?php echo $template['title']; ?></title>
		<meta http-equiv="imagetoolbar" content="false" />
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale = 1.0">
		<?php
		if ($this->config->item('theme_extends') != '' &&
				$this->config->item('theme_extends') != get_setting('fs_theme_dir') &&
				$this->config->item('theme_extends_css') === TRUE &&
				file_exists('content/themes/' . $this->config->item('theme_extends') . '/style.css'))
		{
			echo link_tag('content/themes/' . $this->config->item('theme_extends') . '/style.css?v=' . FOOLSLIDE_VERSION);
		}
		if (file_exists('content/themes/' . get_setting('fs_theme_dir') . '/style.css'))
			echo link_tag('content/themes/' . get_setting('fs_theme_dir') . '/style.css?v=' . FOOLSLIDE_VERSION);
		?> 
		<link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo site_url() ?>sitemap.xml" />
		<link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo site_url() ?>rss.xml" />
		<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php echo site_url() ?>atom.xml" />
		<link rel='index' title='<?php echo get_setting('fs_gen_site_title') ?>' href='<?php echo site_url() ?>' />
		<meta name="generator" content="<?php echo FOOLSLIDE_NAME ?> <?php echo FOOLSLIDE_VERSION ?>" />
		<?php echo get_setting('fs_theme_header_code'); ?>
	</head>
	<body bgcolor="#FFFFEE" text="#800000" link="#0000EE" vlink="#0000EE">
		<div id="header">
			<span id="navtop">
			<?php if (!isset($disable_headers) || $disable_headers !== TRUE) : ?>
				[
				<?php
				$boards_urls = array();
				foreach ($boards as $key => $item)
				{
					$boards_urls[] = '<a href="' . $item->href() . '" title="' . $item->name . '">' . $item->shortname . '</a>';
				}
				echo implode(' / ', $boards_urls);
				?>
				]
			<?php endif; ?>
			</span>
			
			<span id="navtopr">
				[<a href="<?php echo site_url() ?>">index</a> / <a href="<?php echo site_url($this->fu_board) ?>">top</a> / reports / <a href="http://github.com/FoOlRulez/FoOlFuuka/issues">report a bug</a>]
			</span>
		</div>
		<br>
		<div class="logo">
			<font size="5">
				<b><span><?php echo $template['title']; ?></span></b>
			</font>
		</div>
		<?php echo $template['body']; ?>
	</body>
</html>