<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="generator" content="<?php echo FOOL_NAME ?> <?php echo FOOL_VERSION ?>" />
		<title><?php echo $template['title']; ?></title>
		<?php
		if ($disable_headers !== TRUE)
		{
			if (file_exists('content/themes/' . $this->fu_theme . '/style.css'))
				echo link_tag('content/themes/' . $this->fu_theme . '/style.css?v=' . FOOL_VERSION);
		}
		else
		{
			if (file_exists('content/themes/' . $this->fu_theme . '/intro.css'))
				echo link_tag('content/themes/' . $this->fu_theme . '/intro.css?v=' . FOOL_VERSION);
		}
		?>
		<script type="text/javascript" src="<?php echo site_url() ?>content/themes/<?php echo $this->fu_theme ? $this->fu_theme : 'default' ?>/plugins.js?v=<?php echo FOOL_VERSION ?>"></script>
		<?php echo get_setting('fs_theme_header_code'); ?>
	</head>
	<body>
		<?php if ($disable_headers !== TRUE) : ?>
		<div>
			<?php
			$parenthesis_open = FALSE;
			$board_urls = array();
			foreach ($this->radix->get_all() as $key => $item)
			{
				if (!$parenthesis_open)
				{
					echo '[ ';
					$parenthesis_open = TRUE;
				}

				array_push($board_urls, '<a href="' . $item->href . '">' . $item->shortname . '</a>');
			}
			echo implode(' / ', $board_urls);
			if ($parenthesis_open)
			{
				echo ' ]';
				$parenthesis_open = FALSE;
			}
			?>
			[ <a href="<?php echo site_url() ?>">index</a><?php if (get_selected_radix()) : ?> / <a href="<?php echo site_url(get_selected_radix()->shortname) ?>">top</a> / <a href="<?php echo site_url(array(get_selected_radix()->shortname, 'statistics')) ?>">statistics</a><?php endif; ?> / <a href="http://github.com/FoOlRulez/FoOlFuuka/issues">report a bug</a> ]
		
			<?php if($top_articles = $this->plugins->FS_Articles->get_top()) : ?>
			Articles [
			<?php 
			$top_articles_count = count($top_articles);
			foreach($top_articles as $key => $article) : ?>
			<a href="<?php echo site_url('articles/' . $article->slug) ?>"><?php echo fuuka_htmlescape($article->title) ?></a>
			<?php 
			if($top_articles_count - 1 > $key)
				echo ' / ';
			endforeach; ?>
			]
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ($disable_headers !== TRUE) : ?>
		<div style="min-height:30px;">
			<h1><?php echo (get_selected_radix()) ? get_selected_radix()->formatted_title : '' ?></h1>
			<?php if (isset($section_title)) : echo '<h2>' . $section_title . '</h2>'; endif ?>
			<hr />
			<?php echo $template['partials']['tools_view']; ?>
			<hr />
			<?php if ($is_page) : echo $template['partials']['post_thread']; endif ?>
		<?php endif; ?>

			<?php echo $template['body']; ?>

		<?php if ($disable_headers !== TRUE) : ?>
			<?php if (isset($pagination) && !is_null($pagination['total']) && ($pagination['total'] >= 1)) : ?>
			<table style="float:left">
				<tbody>
					<tr>
						<td colspan="7" class="theader">Navigation</td>
					</tr>
					<tr>
						<td class="postblock">View posts</td>
						<td>
						<?php
							if ($pagination['current_page'] == 1) :
								echo '[Prev] ';
							else :
								echo '[<a href="' . $pagination['base_url'] . ($pagination['current_page'] - 1) . '/">Prev</a>] ';
							endif;

							if ($pagination['total'] <= 15) :
								for ($index = 1; $index <= $pagination['total']; $index++)
								{
									if ($pagination['current_page'] == $index)
										echo '[<b>' . $index  . '</b>]';
									else
										echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>] ';
								}
							else :
								if ($pagination['current_page'] < 15) :
									for ($index = 1; $index <= 15; $index++)
									{
										if ($pagination['current_page'] == $index)
											echo '[<b>' . $index  . '</b>]';
										else
											echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>] ';
									}
									echo '[<span>...</span>] ';
								else :
									for ($index = 1; $index < 10; $index++)
									{
										echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>] ';
									}
									echo '[<span>...</span>] ';
									for ($index = ((($pagination['current_page'] + 2) > $pagination['total']) ? ($pagination['current_page'] - 4) : ($pagination['current_page'] - 2)); $index <= ((($pagination['current_page'] + 2) > $pagination['total']) ? $pagination['total'] : ($pagination['current_page'] + 2)); $index++)
									{
										if ($pagination['current_page'] == $index)
											echo '[<b>' . $index  . '</b>]';
										else
											echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>] ';
									}
									if (($pagination['current_page'] + 2) < $pagination['total'])
										echo '[<span>...</span>] ';
								endif;
							endif;

							if ($pagination['total'] == $pagination['current_page']) :
								echo '[Next] ';
							else :
								echo '[<a href="' . $pagination['base_url'] . ($pagination['current_page'] + 1) . '/">Next</a>] ';
							endif;
						?>
						</td>
					</tr>
				</tbody>
			</table>
			<?php endif; ?>

			<div style="float:right">
				
				<?php if($bottom_articles = $this->plugins->FS_Articles->get_bottom()) : ?>
				Articles [
				<?php 
				$bottom_articles_count = count($bottom_articles);
				foreach($bottom_articles as $key => $article) : ?>
				<a href="<?php echo site_url('articles/' . $article->slug) ?>"><?php echo fuuka_htmlescape($article->title) ?></a>
				<?php 
				if($bottom_articles_count - 1 > $key)
					echo ' / ';
				endforeach; ?>
				]
				<?php endif; ?>
				
				Theme [ <a href="<?php echo site_url(array('functions', 'theme', 'default')) ?>" onclick="changeTheme('default'); return false;">Default</a> / <a href="<?php echo site_url(array('functions', 'theme', 'fuuka')) ?>" onclick="changeTheme('fuuka'); return false;">Fuuka</a> ]
			</div>
		</div>
		<?php endif; ?>

		<?php if (get_setting('fs_theme_google_analytics')) : ?>
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
