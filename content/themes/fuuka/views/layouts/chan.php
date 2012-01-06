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

		<div style="min-height:30px;">
			<h1>/<?php echo $board->shortname ?>/ - <?php echo htmlspecialchars($board->name) ?></h1>

			<?php if(isset($section_title)): ?><h2><?php echo $section_title ?></h2><?php endif; ?>
			<hr />

			<?php echo $template['partials']['top_tools']; ?>

			<hr />

	<?php endif; ?>

		<?php echo $template['body']; ?>

		<?php if (isset($pages_links)) : ?>
			<table style="float:left">
				<tbody>
					<tr>
						<td colspan="6" class="theader">Navigation</td>
					</tr>
					<tr>
						<td class="postblock">View posts</td>
						<?php if ($pages_links_current > 5) : ?>
						<td>[<a href="<?php echo $pages_links . ($pages_links_current - 4) ?>/">-96</a>]</td>
						<?php endif; ?>
						<?php if ($pages_links_current > 2) : ?>
						<td>[<a href="<?php echo $pages_links . ($pages_links_current - 2) ?>/">-48</a>]</td>
						<?php endif; ?>
						<?php if ($pages_links_current > 1) : ?>
						<td>[<a href="<?php echo $pages_links . ($pages_links_current - 1) ?>/">-24</a>]</td>
						<?php endif; ?>
						<td>[<a href="<?php echo $pages_links . ($pages_links_current + 1) ?>/">+24</a>]</td>
						<td>[<a href="<?php echo $pages_links . ($pages_links_current + 2) ?>/">+48</a>]</td>
						<td>[<a href="<?php echo $pages_links . ($pages_links_current + 4) ?>/">+96</a>]</td>
					</tr>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if (!isset($disable_headers) || $disable_headers !== TRUE) : ?>
			<div style="float:right">
				Theme [ <a href="<?php echo site_url(array(get_selected_board()->shortname, 'theme', 'default')) ?>" onclick="changeTheme('default'); return false;">Default</a> / <a href="<?php echo site_url(array(get_selected_board()->shortname, 'theme', 'fuuka')) ?>" onclick="changeTheme('fuuka'); return false;">Fuuka</a> / <a href="<?php echo site_url(array(get_selected_board()->shortname, 'theme', 'yotsuba')) ?>" onclick="changeTheme('yotsuba'); return false;">Yotsuba</a> ]
			</div>

		</div>
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