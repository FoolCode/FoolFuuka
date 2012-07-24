<?php if (!defined('DOCROOT')) exit('No direct script access allowed');
header('X-UA-Compatible: IE=edge,chrome=1');
header('imagetoolbar: false');
?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="generator" content="<?= \Config::get('foolfuuka.main.name') ?> <?= \Config::get('foolfuuka.main.version') ?>" />
		<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale = 0.5,maximum-scale = 2.0">
		<?= $template['metadata'] ?>

		<title><?= $template['title'] ?></title>
		<link href='<?= Uri::base() ?>' rel='index' title='<?= Preferences::get('ff.gen.site_title') ?>' />
		<?php if ($radix) : ?>
		<link href="<?= Uri::create($radix->shortname) ?>rss_gallery_50.xml" rel="alternate" type="application/rss+xml" title="RSS" />
		<link href="<?= Uri::create($radix->shortname) ?>atom_gallery_50.xml" rel="alternate" type="application/atom+xml" title="Atom" />
		<?php endif; ?>
		<link href="<?= Uri::base() ?>assets/bootstrap2/css/bootstrap.min.css?v=<?= \Config::get('foolfuuka.main.version') ?>" rel="stylesheet" type="text/css" />
		<link href="<?= Uri::base() ?>assets/font-awesome/css/font-awesome.css?v=<?= \Config::get('foolfuuka.main.version') ?>" rel="stylesheet" type="text/css" />
		<!--[if lt IE 8]>
			<link href="<?= Uri::base() ?>assets/font-awesome/css/font-awesome-ie7.css?v=<?= \Config::get('foolfuuka.main.version') ?>" rel="stylesheet" type="text/css" />
		<![endif]-->
		<?php
			foreach($this->fallback_override('style.css', $this->get_config('extends_css')) as $css)
				echo '<link href="'.Uri::base().$css.'"rel="stylesheet" type="text/css" />';
		?>

		<!--[if lt IE 9]>
			<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
		<?php if (Preferences::get('fu.sphinx.global')) : ?>
			<link rel="search" type="application/opensearchdescription+xml" title="<?= Preferences::get('ff.gen.site_title'); ?> " href="<?= Uri::create('@system/functions/opensearch') ?>" />
		<?php endif; ?>
		<?= Preferences::get('ff.theme.header_code'); ?>

	</head>
	<body class="<?= $this->get_selected_theme_class(array('theme_default')) ?>">
	<?php if ($disable_headers !== TRUE) : ?>
		<div class="letters"><?php
			$board_urls = array();
			foreach (Radix::get_archives() as $key => $item)
			{
				$board_urls[] = '<a href="' . $item->href . '">' . $item->shortname . '</a>';
			}

			if (!empty($board_urls))
			{
				echo sprintf(__('Archives: [ %s ]'), implode(' / ', $board_urls));
			}

			if (Radix::get_archives() && Radix::get_boards())
			{
				echo ' ';
			}

			$board_urls = array();
			foreach (Radix::get_boards() as $key => $item)
			{
				$board_urls[] = '<a href="' . $item->href . '">' . $item->shortname . '</a>';
			}

			if (!empty($board_urls))
			{
				echo sprintf(__('Boards: [ %s ]'), implode(' / ', $board_urls));
			}
		?></div>
	<?php endif; ?>
		<div class="container-fluid">
			<div class="navbar navbar-fixed-top">
				<div class="navbar-inner">
					<div class="container">
						<ul class="nav">
							<li class="dropdown">
								<a href="<?= Uri::base() ?>" id="brand" class="brand dropdown-toggle" data-toggle="dropdown">
									<?= ($radix) ? '/' . $radix->shortname . '/' . ' - ' . $radix->name :  Preferences::get('ff.gen.website_title') ?> <b class="caret"></b>
								</a>
								<ul class="dropdown-menu">
									<?= '<li><a href="' . Uri::create('@default') . '">' . __('Index') . '</a></li>'; ?>
									<?= (Auth::has_access('maccess.mod')) ? '<li><a href="' . Uri::create('@system/admin') . '">' . __('Control Panel') . '</a></li>' : '' ?>
									<li class="divider"></li>
									<?php
										if (Radix::get_archives())
										{
											echo '<li class="nav-header">' . __('Archives') . '</li>';
											foreach (Radix::get_archives() as $key => $item)
											{
												echo '<li><a href="' . $item->href . '">/' . $item->shortname . '/ - ' . $item->name . '</a></li>';
											}
										}

										if (Radix::get_boards())
										{
											if (Radix::get_archives())
											{
												echo '<li class="divider"></li>';
											}

											echo '<li class="nav-header">' . __('Boards') . '</li>';
											foreach (Radix::get_boards() as $key => $item)
											{
												echo '<li><a href="' . $item->href . '">/' . $item->shortname . '/ - ' . $item->name . '</a></li>';
											}
										}
									?>
								</ul>
							</li>
						</ul>

						<ul class="nav">
						<?php if ($radix) : ?>
							<?php if ($radix->archive && $radix->board_url != "") : ?>
							<li>
								<a href="<?= $radix->board_url ?>" style="padding-right:4px;">4chan <i class="icon-share icon-white"></i></a>
							</li>
							<?php endif; ?>
							<li style="padding-right:0px;">
								<a href="<?= Uri::create(array($radix->shortname)) ?>" style="padding-right:4px;"><?= __('Index') ?></a>
							</li>
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" style="padding-left:2px; padding-right:4px;">
									<b class="caret"></b>
								</a>
								<ul class="dropdown-menu" style="margin-left:-9px">
									<li>
										<a href="<?= Uri::create(array($radix->shortname, 'page_mode', 'by_post')) ?>">
											<?= __('By Post') ?>
											<?php if (\Cookie::get('default_theme_page_mode_' . ($radix->archive?'archive':'board')) !== 'by_thread') echo ' <i class="icon-ok"></i>'; ?>
										</a>
									</li>
									<li>
										<a href="<?= Uri::create(array($radix->shortname, 'page_mode', 'by_thread')) ?>">
											<?= __('By Thread') ?>
											<?php if (\Cookie::get('default_theme_page_mode_' . ($radix->archive?'archive':'board')) === 'by_thread') echo ' <i class="icon-ok"></i>'; ?>
										</a>
									</li>
								</ul>
							</li>
						<?php endif; ?>
						<?php
							$top_nav = array();
							if ($radix)
							{
								$top_nav[] = array('href' => Uri::create(array($radix->shortname, 'ghost')), 'text' => __('Ghost'));
								$top_nav[] = array('href' => Uri::create(array($radix->shortname, 'gallery')), 'text' => __('Gallery'));
							}

							$top_nav = Plugins::run_hook('fu_themes_generic_top_nav_buttons', array($top_nav), 'simple');
							$top_nav = Plugins::run_hook('fu_themes_default_top_nav_buttons', array($top_nav), 'simple');

							foreach ($top_nav as $nav)
							{
								echo '<li><a href="' . $nav['href'] . '">' . $nav['text'] . '</a></li>';
							}
						?>
						</ul>

						<?= $template['partials']['tools_search']; ?>
					</div>
				</div>
			</div>

			<div role="main" id="main">
				<?php if (isset($section_title)) : ?>
					<h3 class="section_title"><?= $section_title ?></h3>
				<?php elseif (Preferences::get('ff.theme.header_text')) : ?>
					<section class="section_title"><?= Preferences::get('ff.theme.header_text') ?></section>
				<?php endif; ?>

				<?= isset($template['partials']['tools_new_thread_box']) ? $template['partials']['tools_new_thread_box'] : ''; ?>

				<?= $template['body'] ?>

				<?php
				if ($disable_headers !== TRUE && $radix) :
					echo $template['partials']['tools_modal'];
				endif;
				?>

				<?php if (isset($pagination) && !is_null($pagination['total']) && ($pagination['total'] >= 1)) : ?>
					<div class="paginate">
						<ul>
							<?php if ($pagination['current_page'] == 1) : ?>
								<li class="prev disabled"><a href="#">&larr;  <?= __('Previous') ?></a></li>
							<?php else : ?>
								<li class="prev"><a href="<?= $pagination['base_url'] . ($pagination['current_page'] - 1); ?>/">&larr; <?= __('Previous') ?></a></li>
							<?php endif; ?>

							<?php
							if ($pagination['total'] <= 15) :
								for ($index = 1; $index <= $pagination['total']; $index++)
								{
									echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"'
											: '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
								}
							else :
								if ($pagination['current_page'] < 15) :
									for ($index = 1; $index <= 15; $index++)
									{
										echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"'
												: '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
									}
									echo '<li class="disabled"><span>...</span></li>';
								else :
									for ($index = 1; $index < 10; $index++)
									{
										echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"'
												: '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
									}
									echo '<li class="disabled"><span>...</span></li>';
									for ($index = ((($pagination['current_page'] + 2) > $pagination['total'])
											? ($pagination['current_page'] - 4) : ($pagination['current_page'] - 2)); $index <= ((($pagination['current_page'] + 2) > $pagination['total'])
												? $pagination['total'] : ($pagination['current_page'] + 2)); $index++)
									{
										echo '<li' . (($pagination['current_page'] == $index) ? ' class="active"'
												: '') . '><a href="' . $pagination['base_url'] . $index . '/">' . $index . '</a></li>';
									}
									if (($pagination['current_page'] + 2) < $pagination['total'])
									{
										echo '<li class="disabled"><span>...</span></li>';
									}
								endif;
							endif;
							?>

							<?php if ($pagination['total'] == $pagination['current_page']) : ?>
								<li class="next disabled"><a href="#"><?= __('Next') ?> &rarr;</a></li>
							<?php else : ?>
								<li class="next"><a href="<?= $pagination['base_url'] . ($pagination['current_page'] + 1); ?>/"><?= __('Next') ?> &rarr;</a></li>
							<?php endif; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div> <!-- end of #main -->

			<div id="push"></div>
		</div>
		<footer id="footer">
			<a href="http://github.com/FoOlRulez/FoOlFuuka"><?= \Config::get('foolfuuka.main.name') ?> Imageboard <?= \Config::get('foolfuuka.main.version') ?></a>
			- <a href="http://github.com/eksopl/asagi" target="_blank">Asagi Fetcher</a>

			<div style="float:right">
				<div class="btn-group dropup pull-right">
					<a href="#" class="btn btn-inverse btn-mini dropdown-toggle" data-toggle="dropdown">
						<?= __('Change Theme') ?> <span class="caret"></span>
					</a>
					<ul class="dropdown-menu">
					<?php foreach($this->get_available_themes() as $theme) :
						if (($theme = $this->get_by_name($theme))) :
					?>
						 <li>
							 <a href="<?= Uri::create(array('@system', 'functions', 'theme', $theme['directory'])) ?>">
								 <?= $theme['name'] ?><?= ($theme['directory'] == $this->get_selected_theme())?' <i class="icon-ok"></i>':'' ?>
							 </a>
						 </li>
					<?php endif; ?>
					<?php endforeach; ?>
					</ul>
				</div>
			</div>

			<div style="float:right">
				<div class="btn-group dropup pull-right">
					<a href="#" class="btn btn-inverse btn-mini dropdown-toggle" data-toggle="dropdown">
						<?= __('Change Language') ?> <span class="caret"></span>
					</a>
					<ul class="dropdown-menu">
					<?php /*foreach(config_item('ff_available_languages') as $key => $lang) : ?>
						 <li>
							 <a href="<?= Uri::create(array('@system', 'functions', 'language', $key)) ?>">
								 <?= $lang ?><?= ((!$this->input->cookie('language') && $key == 'en_EN') || $key == $this->input->cookie('language'))?' <i class="icon-ok"></i>':'' ?>
							 </a>
						 </li>
					<?php endforeach; */?>
						 <li class="divider"></li>
						 <li><a href="http://archive.foolz.us/articles/translate/"><?= __('Add a Translation') ?></a></li>
					</ul>
				</div>
			</div>

			<?php
			$bottom_nav = array();
			$bottom_nav = Plugins::run_hook('fu_themes_generic_bottom_nav_buttons', array($bottom_nav), 'simple');
			$bottom_nav = Plugins::run_hook('fu_themes_default_bottom_nav_buttons', array($bottom_nav), 'simple');

			if (!empty($bottom_nav))
			{
				echo '<div class="pull-right" style="margin-right: 15px;">';
				foreach ($bottom_nav as $key => $nav)
				{
					echo '<a href="' . $nav['href'] . '">' . $nav['text'] . '</a>';
					if ($key < count($bottom_nav) - 1)
					{
						echo ' - ';
					}
				}
				echo '</div>';
			}

			if (Preferences::get('fu.theme.footer_text'))
			{
				echo '<section class="footer_text">' . Preferences::get('fu.theme.footer_text') . '</section>';
			}
			?>
		</footer>


		<script>
			var backend_vars = <?= json_encode($backend_vars) ?>;
		</script>

		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script>window.jQuery || document.write('<script src="<?= Uri::base() ?>assets/js/jquery.js"><\/script>')</script>
		<script defer src="<?= Uri::base() ?>assets/bootstrap2/js/bootstrap.min.js?v=<?= \Config::get('foolfuuka.main.version') ?>"></script>
		<script defer src="<?= Uri::base() . $this->fallback_asset('plugins.js') ?>"></script>
		<script defer src="<?= Uri::base() . $this->fallback_asset('board.js') ?>"></script>
<?php if (Preferences::get('ff.theme.google_analytics')) : ?>
		<script>
			var _gaq=[['_setAccount','<?= Preferences::get('fs_theme_google_analytics') ?>'],['_trackPageview'],['_trackPageLoadTime']];
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
		<?= Preferences::get('fu.theme.footer_code'); ?>
	</body>
</html>
