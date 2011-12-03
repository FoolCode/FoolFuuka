<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title><?php echo $template['title']; ?></title>
		<meta http-equiv="imagetoolbar" content="false" />
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale = 1.0">
		<script src="<?php echo site_url() ?>/assets/js/modernizr-2.0.6.min.js"></script>
		<link rel="stylesheet" type="text/css" href="<?php echo base_url() ?>assets/bootstrap/style.css?v=<?php echo FOOLSLIDE_VERSION ?>" />
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
	<body>
		<div class="container-fluid">
			<header id="header">
			<?php if (!isset($disable_headers) || $disable_headers !== TRUE) : ?>
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
				
					<h1 id="logo">/<?php echo $board->shortname ?>/ - <?php echo $board->name ?></h1>
				<?php endif; ?>
				<h6>This archiver does have Super Loli Powers.</h6>
			</header>

			<?php if (!isset($disable_headers) || $disable_headers !== TRUE) : ?>
				<aside id="top_tools">
					<?php echo $template['partials']['top_tools']; ?>
				</aside>
			<?php endif; ?>

			<div role="main">
				<?php if(isset($section_title)): ?>
				<h3 class="section_title"><?php echo $section_title ?></h3>
				<?php endif; ?>
				<?php echo $template['body']; ?>
				
				<?php echo $template['partials']['post_tools']; ?>
			</div> <!-- end of #main -->


			<footer><?php echo FOOLSLIDE_NAME ?> - Version <?php echo FOOLSLIDE_VERSION ?></footer>
		</div>

		<!-- JavaScript at the bottom for fast page loading -->

		<!-- Grab Google CDN's jQuery, with a protocol relative URL; fall back to local if offline -->
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js"></script>
		<script>window.jQuery || document.write('<script src="<?php echo site_url() ?>/js/libs/jquery-1.6.4.min.js"><\/script>')</script>

		<!-- scripts concatenated and minified via build script -->
		<script defer type="text/javascript" src="<?php echo site_url() ?>assets/bootstrap/bootstrap.js?v=<?php echo FOOLSLIDE_VERSION ?>"></script>
		<script defer src="<?php echo site_url() ?>content/themes/<?php echo get_setting('fs_theme_dir') ? get_setting('fs_theme_dir') : 'default' ?>/plugins.js?v=<?php echo FOOLSLIDE_VERSION ?>"></script>

		<!-- end scripts -->


		<!-- Asynchronous Google Analytics snippet. Change UA-XXXXX-X to be your site's ID.
			 mathiasbynens.be/notes/async-analytics-snippet -->
		<script>
			var _gaq=[['_setAccount','UX-XXXXXXX-X'],['_trackPageview'],['_trackPageLoadTime']];
			(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
				g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
				s.parentNode.insertBefore(g,s)}(document,'script'));
		</script>

		<!-- Prompt IE 6 users to install Chrome Frame. Remove this if you want to support IE 6.
			 chromium.org/developers/how-tos/chrome-frame-getting-started -->
		<!--[if lt IE 7 ]>
		  <script defer src="//ajax.googleapis.com/ajax/libs/chrome-frame/1.0.3/CFInstall.min.js"></script>
		  <script defer>window.attachEvent('onload',function(){CFInstall.check({mode:'overlay'})})</script>
		<![endif]-->



		<?php echo get_setting('fs_theme_footer_code'); ?>
	</body>
</html>
