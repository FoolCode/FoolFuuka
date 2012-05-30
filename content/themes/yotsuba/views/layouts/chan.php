<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<html>
	<head>

		<title><?php echo $template['title']; ?></title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<meta http-equiv="imagetoolbar" content="false" />
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale = 1.0" />
		<?php
		if (file_exists('content/themes/' . $this->theme->get_selected_theme() . '/style.css'))
			echo link_tag('content/themes/' . $this->theme->get_selected_theme() . '/style.css?v=' . FOOL_VERSION);
		?>
		<script type="text/javascript" src="<?php echo site_url() ?>content/themes/<?php echo $this->theme->get_selected_theme() ? $this->theme->get_selected_theme() : 'default' ?>/plugins.js?v=<?php echo FOOL_VERSION ?>"></script>
		<meta name="generator" content="<?php echo FOOL_NAME ?> <?php echo FOOL_VERSION ?>" />
		<?php echo get_setting('fs_theme_header_code'); ?>
	</head>
	<body bgcolor="#FFFFEE" text="#800000" link="#0000EE" vlink="#0000EE">
		<?php if (!isset($disable_headers) || $disable_headers !== TRUE) : ?>

			<div id="header">
				<span id="navtop">
					[<?php
					$boards_urls = array();
					foreach ($this->radix->get_all() as $key => $item)
					{
						$boards_urls[] = '<a href="' . $item->href . '" title="' . $item->name . '">' . $item->shortname . '</a>';
					}
					echo implode(' / ', $boards_urls);
					?>]
				</span>

				<span id="navtopr">
					[<a href="<?php echo site_url() ?>">index</a> / <a href="<?php echo site_url(get_selected_radix()->shortname) ?>">top</a> / <a href="<?php echo site_url(array(get_selected_radix()->shortname, 'statistics')) ?>">statistics</a> / <a href="http://github.com/FoOlRulez/FoOlFuuka/issues">report a bug</a>]
				</span>
			</div>
			<br/>
			<div class="logo">
				<img width="300" height="100" src="<?php echo get_banner() ?>"/>
				<br>
				<font size="5">
					<b><span>/<?php echo $board->shortname ?>/ - <?php echo htmlspecialchars($board->name) ?></span></b>
				</font>
				<br>
				<font size="1"><?php if(isset($section_title)): ?><?php echo $section_title ?><?php endif; ?></font>
			</div>

			<hr width="90%" size="1">
			<!-- Start J-List Affiliate Code -->
			<div style="text-align: center; text-side:12px;">
				<a href="http://pocky.jlist.com/click/3953/111" target="_blank" onmouseover="window.status='Hentai dating-sim games in English - click to see'; return true;" onmouseout="window.status=''; return true;" title="Hentai dating-sim games in English - click to see">
					<img src="http://pocky.jlist.com/media/3953/111" width="728" height="90" alt="Hentai dating-sim games in English - click to see" border="0">
				</a>
			</div>
			<!-- End J-List Affiliate Code -->
			<hr>

			<?php //echo $template['partials']['tools_view']<hr> <hr width="90%" size="1">?>

			<?php if ($is_page) : echo $template['partials']['post_thread']; endif ?>

		<?php endif; ?>

		<?php echo $template['body']; ?>

		<br clear="all">

		<div id="footer">
			<span id="navbot">
				[<?php
				$boards_urls = array();
				foreach ($this->radix->get_all() as $key => $item)
				{
					$boards_urls[] = '<a href="' . $item->href . '" title="' . $item->name . '">' . $item->shortname . '</a>';
				}
				echo implode(' / ', $boards_urls);
				?>]
			</span>
			<span id="navbotr">
				[<a href="<?php echo site_url() ?>">index</a><?php if ($disable_headers !== TRUE) : ?> / <a href="<?php echo site_url(get_selected_radix()->shortname) ?>">top</a> / <a href="<?php echo site_url(array(get_selected_radix()->shortname, 'statistics')) ?>">statistics</a><?php endif; ?> / <a href="http://github.com/FoOlRulez/FoOlFuuka/issues">report a bug</a>]
			</span>
			<br>
			<br>
			<center>
				<font size="2">
					- fuuka + foolfuuka -
					<br>
					All trademarks and copyrights on this page are owned by their respective parties. Images uploaded are the responsibility of the Poster. Comments are owned by the Poster.
				</font>
			</center>
		</div>

		<?php if(get_setting('fs_theme_google_analytics')) : ?>
		<script>
			var _gaq=[['_setAccount','<?php echo get_setting('fs_theme_google_analytics') ?>'],['_setDomainName', 'foolz.us'],['_trackPageview'],['_trackPageLoadTime']];
			(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
				g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
				s.parentNode.insertBefore(g,s)}(document,'script'));
		</script>
		<?php endif; ?>

		<?php echo get_setting('fs_theme_footer_code'); ?>
	</body>
</html>