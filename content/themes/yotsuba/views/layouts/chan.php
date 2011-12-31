<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<html>
	<head>

		<title><?php echo $template['title']; ?></title>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<meta http-equiv="imagetoolbar" content="false" />
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale = 1.0" />
		<?php
		if (file_exists('content/themes/' . $this->fu_theme . '/style.css'))
			echo link_tag('content/themes/' . $this->fu_theme . '/style.css?v=' . FOOLSLIDE_VERSION);
		?>
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
				[<a href="<?php echo site_url() ?>">index</a> / <a href="<?php echo site_url($this->fu_board) ?>">top</a> / statistics / <a href="http://github.com/FoOlRulez/FoOlFuuka/issues">report a bug</a>]
			</span>
		</div>

		<div class="logo">
			<font size="5">
				<b><span><?php echo $template['title']; ?></span></b>
			</font>
		</div>

		<hr>

		<div id="archive_tools">
			<?php echo $template['partials']['top_tools'] ?>
		</div>

		<hr>

		<?php echo $template['body']; ?>

		<?php if (isset($pages_links)) : ?>
			<table class="pages" align="left" border="1">
				<tbody>
					<tr>
						<?php if ($pages_links_current == 1) : ?>
							<td>Previous</td>
						<?php else : ?>
							<td><input type="submit" value="Previous" onclick="location.href='<?php echo $pages_links[$pages_links_current-1]; ?>';return false;"></td>
						<?php endif; ?>
							<td>
							<?php foreach ($pages_links as $key => $item) : ?>
								[<a href="<?php echo $item ?>"><?php if ($key == $pages_links_current) echo '<b>'; ?><?php echo $key ?><?php if ($key == $pages_links_current) echo '</b>'; ?></a>]
							<?php endforeach; ?>
							</td>
						<?php if ((count($pages_links) > 1) && ($pages_links_current >= 1 && $pages_links_current < 15)) : ?>
							<td><input type="submit" value="Next" onclick="location.href='<?php echo $pages_links[$pages_links_current+1]; ?>';return false;"></td>
						<?php else : ?>
							<td>Next</td>
						<?php endif; ?>
					</tr>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if(get_setting('fs_theme_google_analytics')) : ?>
		<script>
			var _gaq=[['_setAccount','<?php echo get_setting('fs_theme_google_analytics') ?>'],['_setDomainName', 'foolz.us']['_trackPageview'],['_trackPageLoadTime']];
			(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
				g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
				s.parentNode.insertBefore(g,s)}(document,'script'));
		</script>
		<?php endif; ?>

		<?php echo get_setting('fs_theme_footer_code'); ?>
	</body>
</html>