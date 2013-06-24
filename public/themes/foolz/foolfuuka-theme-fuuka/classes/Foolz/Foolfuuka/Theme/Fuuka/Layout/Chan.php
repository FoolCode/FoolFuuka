<?php

namespace Foolz\Foolfuuka\Theme\Fuuka\Layout;

class Chan extends \Foolz\Theme\View
{
	public function toString()
	{
		header('X-UA-Compatible: IE=edge,chrome=1');
		header('imagetoolbar: false');

		$this->getHeader();
		$this->getNav();
		$this->getContent();
		$this->getFooter();
	}

	public function getSelectedThemeClass()
	{
		return 'theme_default';
	}

	public function getStyles()
	{
		?>
    <link href="<?= $this->getAssetManager()->getAssetLink('style.css') ?>" rel="stylesheet" type="text/css"/>
	<?php
	}

	public function getHeader()
	{
		?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="generator" content="<?= \Foolz\Config\Config::get('foolz/foolfuuka', 'package', 'main.name').' '.\Foolz\Config\Config::get('foolz/foolfuuka', 'package', 'main.version') ?>" />

		<title><?= $this->getBuilder()->getProps()->getTitle(); ?></title>
		<?php $this->getStyles(); ?>

		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script src="<?= $this->getAssetManager()->getAssetLink('board.js') ?>" type="text/javascript"></script>
		<?php if (\Preferences::get('foolfuuka.sphinx.global')) : ?>
			<link rel="search" type="application/opensearchdescription+xml" title="<?= \Preferences::get('foolframe.gen.website_title') ?> " href="<?= \Uri::create('_/opensearch') ?>" />
		<?php endif; ?>
		<?= \Preferences::get('foolframe.theme.header_code') ?>
	</head>
		<?php

	}

	public function getNav()
	{
		$radix = $this->getBuilderParamManager()->getParam('radix');
		$disable_headers = $this->getBuilderParamManager()->getParam('disable_headers', false);
		$section_title = $this->getBuilderParamManager()->getParam('section_title', false);

		?>
	<body>
	<?php if ($disable_headers !== true) : ?>
		<div><?php
			$board_urls = array();
			foreach (\Radix::getAll() as $key => $item)
			{
				$board_urls[] = '<a href="' . $item->getValue('href') . '">' . $item->shortname . '</a>';
			}

			if ( ! empty($board_urls))
			{
				echo '[ ' . implode(' / ', $board_urls) . ' ]';
			}
		?>

		<?php
			$board_urls = array();

			$board_urls[] = '<a href="' . \Uri::base() . '">' . strtolower(__('Index')) . '</a>';
			if ($radix)
			{
				$board_urls[] = '<a href="' . \Uri::create($radix->shortname) . '">' . strtolower(__('Top')) . '</a>';
				$board_urls[] = '<a href="' . \Uri::create(array($radix->shortname, 'statistics')) . '">' . strtolower(__('Statistics')) . '</a>';
			}
			$board_urls[] = '<a href="https://github.com/FoolCode/FoOlFuuka/issues">' . strtolower(__('Report Bug')) . '</a>';

			echo '[ ' . implode(' / ', $board_urls) . ' ]';
		?>

		<?php
			$top_nav = array();
			$top_nav = \Foolz\Plugin\Hook::forge('foolframe.themes.generic_top_nav_buttons')->setParam('nav', $top_nav)->execute()->get($top_nav);
			$top_nav = \Foolz\Plugin\Hook::forge('foolfuuka.themes.fuuka_top_nav_buttons')->setParam('nav', $top_nav)->execute()->get($top_nav);

			if ( ! empty($top_nav))
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
		?></div>
		<div style="min-height: 30px;">
	        <h1><?= ($radix) ? $radix->getValue('formatted_title') : '' ?></h1>
			<?php if ($section_title !== false) : ?>
	        <h2><?= $section_title ?></h2>
			<?php elseif (\Preferences::get('foolframe.theme.header_text')) : ?>
	        <div><?= \Preferences::get('foolframe.theme.header_text') ?></div>
			<?php endif; ?>

	        <hr />

			<?= $this->getBuilder()->isPartial('tools_search') ? $this->getBuilder()->getPartial('tools_search')->build() : ''; ?>

	        <hr />
			<?php if ( ! isset($thread_id)) : ?>
			<?= isset($template['partials']['tools_new_thread_box']) ? $template['partials']['tools_new_thread_box'] : ''; ?>
			<?php endif; ?>
	    </div>
		<?php endif; ?>
		<?php
	}

	public function getContent()
	{
		$pagination = $this->getBuilderParamManager()->getParam('pagination', false);
		?>

		<?= $this->getBuilder()->isPartial('tools_new_thread_box') ? $this->getBuilder()->getPartial('tools_new_thread_box')->build() : ''; ?>

		<?= $this->getBuilder()->getPartial('body')->build(); ?>

		<?php \Foolz\Plugin\Hook::forge('foolfuuka.themes.fuuka_after_body_template')->execute(); ?>

		<?php if ($pagination !== false && !is_null($pagination['total']) && ($pagination['total'] >= 1)) : ?>
			<table style="float: left;">
				<tbody>
					<tr>
						<td colspan="7" class="theader"><?= __('Navigation') ?></td>
					</tr>
					<tr>
						<td class="postblock"><?= __('View Posts') ?></td>
						<td>
							<?php if ($pagination['current_page'] == 1) : ?>
								[<?= __('Prev') ?>]
							<?php else : ?>
								[<a href="<?= $pagination['base_url'] . ($pagination['current_page'] - 1) ?>/"><?= __('Prev') ?></a>]
							<?php endif; ?>

							<?php
							if ($pagination['total'] <= 15) :
								for ($index = 1; $index <= $pagination['total']; $index++)
								{
									if (($pagination['current_page'] == $index))
										echo '[<b>' . $index . '</b>]';
									else
										echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>]';
								}
							else :
								if ($pagination['current_page'] < 15) :
									for ($index = 1; $index <= 15; $index++)
									{
										if (($pagination['current_page'] == $index))
											echo '[<b>' . $index . '</b>]';
										else
											echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>]';
									}
									echo '[...]';
								else :
									for ($index = 1; $index < 10; $index++)
									{
										if (($pagination['current_page'] == $index))
											echo '[<b>' . $index . '</b>]';
										else
											echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>]';
									}
									echo '<li class="disabled"><span>...</span></li>';
									for ($index = ((($pagination['current_page'] + 2) > $pagination['total'])
										? ($pagination['current_page'] - 4) : ($pagination['current_page'] - 2)); $index <= ((($pagination['current_page'] + 2) > $pagination['total'])
										? $pagination['total'] : ($pagination['current_page'] + 2)); $index++)
									{
										if (($pagination['current_page'] == $index))
											echo '[<b>' . $index . '</b>]';
										else
											echo '[<a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a>]';
									}
									if (($pagination['current_page'] + 2) < $pagination['total'])
									{
										echo '[...]';
									}
								endif;
							endif;
							?>

							<?php if ($pagination['total'] == $pagination['current_page']) : ?>
								[<?= __('Next') ?>]
							<?php else : ?>
								[<a href="<?= $pagination['base_url'] . ($pagination['current_page'] + 1) ?>/"><?= __('Next') ?></a>]
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		<?php endif;
	}

	public function getFooter()
	{
		$disable_headers = $this->getBuilderParamManager()->getParam('disable_headers', false);

		?>
		<?php if ($disable_headers !== true) : ?>
			<div style="float: right;">
				<?php
					$bottom_nav = array();
					$bottom_nav = \Foolz\Plugin\Hook::forge('foolframe.themes.generic_bottom_nav_buttons')->setParam('nav', $bottom_nav)->execute()->get($bottom_nav);
					$bottom_nav = \Foolz\Plugin\Hook::forge('foolfuuka.themes.fuuka_bottom_nav_buttons')->setParam('nav', $bottom_nav)->execute()->get($bottom_nav);

					if ( ! empty($bottom_nav))
					{
						echo '[ ';
						foreach ($bottom_nav as $key => $nav)
						{
							echo '<a href="' . $nav['href'] . '">' . $nav['text'] . '</a>';
							if ($key < count($bottom_nav) - 1)
							{
								echo ' / ';
							}
						}
						echo ' ]';
					}
				?>

				<?php
					$theme_links = array();
					foreach($this->getTheme()->getLoader()->getListWithStyles() as $key => $theme) :
						if (isset($theme['object']->enabled) && $theme['object']->enabled) :
							$theme_links[] = '<a href="' . \Uri::create(array('_', 'theme', $key)) . '">' . $theme['string'] . '</a>';
						endif;
					endforeach;

					echo 'Theme [ ' . implode(' / ', $theme_links) . ' ]';
				?>
			</div>
		<?php endif; ?>

		<?php
			if (\Preferences::get('foolframe.theme.footer_text'))
			{
				echo '<div style="clear: both;">' . \Preferences::get('foolframe.theme.footer_text') . '</div>';
			}
		?>

		<script>
			var backend_vars = <?= json_encode($this->getBuilderParamManager()->getParam('backend_vars')) ?>;
		</script>

		<?php if (\Preferences::get('foolframe.theme.google_analytics')) : ?>
			<script>
				var _gaq=[['_setAccount','<?= \Preferences::get('foolframe.theme.google_analytics') ?>'],['_trackPageview'],['_trackPageLoadTime']];
				(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
					g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
					s.parentNode.insertBefore(g,s)}(document,'script'));
			</script>
		<?php endif; ?>

		<?= \Preferences::get('foolframe.theme.footer_code') ?>
	</body>
</html>
	<?php
	}
}