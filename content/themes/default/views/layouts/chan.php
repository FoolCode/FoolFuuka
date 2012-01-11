<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title><?php echo $template['title']; ?></title>
		<meta http-equiv="imagetoolbar" content="false" />
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale = 1.0">
		<link rel="stylesheet" type="text/css" href="<?php echo base_url() ?>assets/bootstrap/style.css?v=<?php echo FOOLSLIDE_VERSION ?>" />
		<?php
		if ($this->config->item('theme_extends') != '' &&
				$this->config->item('theme_extends') != (($this->fu_theme) ? $this->fu_theme : 'default') &&
				$this->config->item('theme_extends_css') === TRUE &&
				file_exists('content/themes/' . $this->config->item('theme_extends') . '/style.css'))
		{
			echo link_tag('content/themes/' . $this->config->item('theme_extends') . '/style.css?v=' . FOOLSLIDE_VERSION);
		}
		if (file_exists('content/themes/' . (($this->fu_theme) ? $this->fu_theme : 'default') . '/style.css'))
			echo link_tag('content/themes/' . (($this->fu_theme) ? $this->fu_theme : 'default') . '/style.css?v=' . FOOLSLIDE_VERSION);
		?>

		<!--[if lt IE 9]>
			<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
		<?php if (!isset($disable_headers) || $disable_headers !== TRUE) : ?>
		<link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo site_url(get_selected_board()->shortname) ?>rss_gallery_50.xml" />
		<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php echo site_url(get_selected_board()->shortname) ?>atom_gallery_50.xml" /> 
		<?php endif; ?>
		<link rel='index' title='<?php echo get_setting('fs_gen_site_title') ?>' href='<?php echo site_url() ?>' />
		<meta name="generator" content="<?php echo FOOLSLIDE_NAME ?> <?php echo FOOLSLIDE_VERSION ?>" />
		<?php echo get_setting('fs_theme_header_code'); ?>
	</head>
	<body>
		<div class="container-fluid">
			<header id="header">
			<?php if (!isset($disable_headers) || $disable_headers !== TRUE) : ?>
				<aside id="top_tools">
					<?php echo $template['partials']['top_tools']; ?>
				</aside>
				[
				<?php
				$board_urls = array();
				foreach ($boards as $key => $item)
				{
					$board_urls[] = '<a href="' . $item->href() . '">' . $item->shortname . '</a> <a href="' . $item->href() . 'gallery/">+</a>';
				}
				echo implode(' / ', $board_urls)
				?>
				] [ <a href="<?php echo site_url() ?>">index</a> / <a href="<?php echo site_url($this->fu_board) ?>">top</a> / <a href="<?php echo site_url(array($this->fu_board, 'statistics')) ?>">statistics</a> / <a href="http://github.com/FoOlRulez/FoOlFuuka/issues">report a bug</a> ] [ Original archiver: <a href="http://oldarchive.foolz.us">http://oldarchive.foolz.us</a> ]
		<?php //		<br/>[ Select theme (will later be moved to footer): <a href="#" onClick="changeTheme('default')">Default</a> / <a href="#" onClick="changeTheme('fuuka')">Fuuka</a> ]
		?>			<h1 id="logo">/<?php echo $board->shortname ?>/ - <?php echo $board->name ?></h1>
				<?php endif; ?>
				<aside id="top_options">
					<?php echo $template['partials']['top_options']; ?>
				</aside>
			</header>

			<div role="main" id="main">
				<?php if(isset($section_title)): ?>
				<h3 class="section_title"><?php echo $section_title ?></h3>
				<?php else : ?>
				<?php endif; ?>


				<?php echo $template['body']; ?>

				<?php echo $template['partials']['post_tools']; ?>

				<?php if (isset($pagination)) : ?>
				<div class="paginate">
					<ul>
					<?php if ($pagination['current_page'] == 1) : ?>
						<li class="prev disabled"><a href="#">&larr; Previous</a></li>
					<?php else : ?>
						<li class="prev"><a href="<?php echo $pagination['base_url'] . ($pagination['current_page'] - 1); ?>/">&larr; Previous</a></li>
					<?php endif; ?>

					<?php
					if ($pagination['total'] == 0) :
						if ($pagination['current_page'] < 15) :
							for ($index = 1; $index <= 15; $index++)
							{
								echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"' : '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
							}
							echo '<li class="disabled"><span>...</span></li>';
						else :
							for ($index = 1; $index < 10; $index++)
							{
								echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"' : '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
							}
							echo '<li class="disabled"><span>...</span></li>';
							for ($index = ($pagination['current_page'] - 2); $index <= ($pagination['current_page'] + 2); $index++)
							{
								echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"' : '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
							}
							echo '<li class="disabled"><span>...</span></li>';
						endif;
					elseif ($pagination['total'] <= 15) :
						for ($index = 1; $index <= $pagination['total']; $index++)
						{
							echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"' : '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
						}
					else :
						if ($pagination['current_page'] < 15) :
							for ($index = 1; $index <= 15; $index++)
							{
								echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"' : '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
							}
							echo '<li class="disabled"><span>...</span></li>';
						else :
							for ($index = 1; $index < 10; $index++)
							{
								echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"' : '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
							}
							echo '<li class="disabled"><span>...</span></li>';
							for ($index = ((($pagination['current_page'] + 2) > $pagination['total']) ? ($pagination['current_page'] - 4) : ($pagination['current_page'] - 2)); $index <= ((($pagination['current_page'] + 2) > $pagination['total']) ? $pagination['total'] : ($pagination['current_page'] + 2)); $index++)
							{
								echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"' : '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
							}
							if (($pagination['current_page'] + 2) < $pagination['total'])
								echo '<li class="disabled"><span>...</span></li>';
						endif;
					endif;
					?>

					<?php if ($pagination['total'] == $pagination['current_page']) : ?>
						<li class="next disabled"><a href="#">Next &rarr;</a></li>
					<?php else : ?>
						<li class="next"><a href="<?php echo $pagination['base_url'] . ($pagination['current_page'] + 1); ?>/">Next &rarr;</a></li>
					<?php endif; ?>
					</ul>
				</div>
				<?php endif; ?>
			</div> <!-- end of #main -->

			<div id="push"></div>
		</div>
		<footer id="footer"><?php echo FOOLSLIDE_NAME ?> - Version <?php echo FOOLSLIDE_VERSION ?>
			<div style="float:right">
				Theme [ <a href="<?php echo site_url(array(get_selected_board()->shortname, 'theme', 'default')) ?>" onclick="changeTheme('default'); return false;">Default</a> / <a href="<?php echo site_url(array(get_selected_board()->shortname, 'theme', 'fuuka')) ?>" onclick="changeTheme('fuuka'); return false;">Fuuka</a> ]
			</div>
		</footer>


		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script>window.jQuery || document.write('<script src="<?php echo site_url() ?>assets/js/jquery.js"><\/script>')</script>
		<script defer src="<?php echo site_url() ?>content/themes/<?php echo $this->fu_theme ? $this->fu_theme : 'default' ?>/plugins.js?v=<?php echo FOOLSLIDE_VERSION ?>"></script>
		<script defer src="<?php echo site_url() ?>content/themes/<?php echo $this->fu_theme ? $this->fu_theme : 'default' ?>/board.js?v=<?php echo FOOLSLIDE_VERSION ?>"></script>
		<?php if(get_setting('fs_theme_google_analytics')) : ?>
		<script>
			var _gaq=[['_setAccount','<?php echo get_setting('fs_theme_google_analytics') ?>'],['_setDomainName', 'foolz.us'],['_trackPageview'],['_trackPageLoadTime']];
			(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
				g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
				s.parentNode.insertBefore(g,s)}(document,'script'));
		</script>
		<?php endif; ?>

		<!-- Prompt IE 6 users to install Chrome Frame. Remove this if you want to support IE 6.
			 chromium.org/developers/how-tos/chrome-frame-getting-started -->
		<!--[if lt IE 7 ]>
		  <script defer src="//ajax.googleapis.com/ajax/libs/chrome-frame/1.0.3/CFInstall.min.js"></script>
		  <script defer>window.attachEvent('onload',function(){CFInstall.check({mode:'overlay'})})</script>
		<![endif]-->

		<?php echo get_setting('fs_theme_footer_code'); ?>
	</body>
</html>
