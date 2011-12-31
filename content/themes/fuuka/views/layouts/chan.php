<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title><?php echo htmlspecialchars($template['title']); ?></title>
		<meta name="generator" content="<?php echo FOOLSLIDE_NAME ?> <?php echo FOOLSLIDE_VERSION ?>" />
		<link rel='index' title='<?php echo get_setting('fs_gen_site_title') ?>' href='<?php echo site_url() ?>' />
		<?php if (!isset($disable_headers) || $disable_headers !== TRUE) : ?>
			<?php
			if (file_exists('content/themes/' . $this->fu_theme . '/style.css'))
				echo link_tag('content/themes/' . $this->fu_theme . '/style.css?v=' . FOOLSLIDE_VERSION);
			?>
		<?php else: ?>
			<?php
			if (file_exists('content/themes/' . $this->fu_theme . '/intro.css'))
				echo link_tag('content/themes/' . $this->fu_theme . '/intro.css?v=' . FOOLSLIDE_VERSION);
			?>
		<?php endif; ?>
		<script type="text/javascript" src="<?php echo site_url() ?>content/themes/<?php echo $this->fu_theme ? $this->fu_theme : 'default' ?>/plugins.js?v=<?php echo FOOLSLIDE_VERSION ?>"></script>
		<?php echo get_setting('fs_theme_header_code'); ?>

	</head>
	<body>
	<?php if (!isset($disable_headers) || $disable_headers !== TRUE) : ?>

		<div>
			[ <?php
			$board_urls = array();
			foreach ($boards as $key => $item)
			{
				$board_urls[] = '<a href="' . $item->href() . '">' . $item->shortname . '</a>';
			}
			echo implode(' / ', $board_urls)
			?> ] [ <a href="<?php echo site_url() ?>">index</a> / <a href="<?php echo site_url($this->fu_board) ?>">top</a> / <a href="<?php echo site_url(array($this->fu_board, 'statistics')) ?>">statistics</a> / <a href="http://github.com/FoOlRulez/FoOlFuuka/issues">report a bug</a> ]
		</div>

	<?php endif; ?>

	<?php if (!isset($disable_headers) || $disable_headers !== TRUE) : ?>

		<div>
			<h1>/<?php echo $board->shortname ?>/ - <?php echo htmlspecialchars($board->name) ?></h1>
		</div>

		<?php if(isset($section_title)): ?><h2><?php echo $section_title ?></h2><?php endif; ?>
		<hr />

		<?php echo $template['partials']['top_tools']; ?>

		<hr />

	<?php endif; ?>

		<?php echo $template['body']; ?>

		<?php if (isset($pages_links)) : ?>
			<?php if ($pages_links_current <= 15) : ?>
			<table class="pages" align="left" border="1">
				<tbody>
					<tr>
						<?php if ($pages_links_current == 1) : ?>
							<td>Previous</td>
						<?php else : ?>
							<td><input type="submit" value="Previous" onclick="location.href='<?php echo $pages_links[$pages_links_current-1]; ?>';return false;"/></td>
						<?php endif; ?>
							<td>
							<?php foreach ($pages_links as $key => $item) : ?>
								[<a href="<?php echo $item ?>"><?php if ($key == $pages_links_current) echo '<b>'; ?><?php echo $key ?><?php if ($key == $pages_links_current) echo '</b>'; ?></a>]
							<?php endforeach; ?>
							</td>
						<?php if ((count($pages_links) > 1) && ($pages_links_current >= 1 && $pages_links_current < 15)) : ?>
							<td><input type="submit" value="Next" onclick="location.href='<?php echo $pages_links[$pages_links_current+1]; ?>';return false;"/></td>
						<?php else : ?>
							<td>Next</td>
						<?php endif; ?>
					</tr>
				</tbody>
			</table>
			<?php else : ?>
			<?php endif; ?>
		<?php endif; ?>

		Theme [ <a href="<?php echo site_url(array(get_selected_board()->shortname, 'theme', 'default')) ?>" onclick="changeTheme('default'); return false;">Default</a> / <a href="<?php echo site_url(array(get_selected_board()->shortname, 'theme', 'fuuka')) ?>" onclick="changeTheme('fuuka'); return false;">Fuuka</a> / <a href="<?php echo site_url(array(get_selected_board()->shortname, 'theme', 'yotsuba')) ?>" onclick="changeTheme('yotsuba'); return false;">Yotsuba</a> ]
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