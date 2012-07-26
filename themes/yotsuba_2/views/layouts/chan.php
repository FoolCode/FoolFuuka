<?php if (!defined('DOCROOT')) exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="generator" content="<?= FOOL_NAME ?> <?= FOOL_VERSION ?>" />
		<meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale = 0.5,maximum-scale = 2.0">
		<?= $template['metadata'] ?>

		<title><?= $template['title'] ?></title>
		<link href='<?= Uri::base() ?>' rel='index' title='<?= get_setting('fs_gen_site_title') ?>' />
<?php if (Radix::get_selected()) : ?>
		<link href="<?= Uri::create(Radix::get_selected()->shortname) ?>rss_gallery_50.xml" rel="alternate" type="application/rss+xml" title="RSS" />
		<link href="<?= Uri::create(Radix::get_selected()->shortname) ?>atom_gallery_50.xml" rel="alternate" type="application/atom+xml" title="Atom" />
<?php endif; ?>
		<link href="<?= Uri::base() ?>assets/bootstrap2/css/bootstrap.min.css?v=<?= FOOL_VERSION ?>" rel="stylesheet" type="text/css" />
		<link href="<?= Uri::base() ?>assets/font-awesome/css/font-awesome.css?v=<?= FOOL_VERSION ?>" rel="stylesheet" type="text/css" />
		<!--[if lt IE 8]>
		<link href="<?= Uri::base() ?>assets/font-awesome/css/font-awesome-ie7.css?v=<?= FOOL_VERSION ?>" rel="stylesheet" type="text/css" />
		<![endif]-->
		<?php
		foreach($this->fallback_override('style.css', $this->get_config('extends_css')) as $css)
		{
			echo link_tag($css);
		}
		?>

		<!--[if lt IE 9]>
		<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
<?php if (get_setting('fu.sphinx.global')) : ?>
		<link rel="search" type="application/opensearchdescription+xml" title="<?= get_setting('fs_gen_site_title', FOOL_PREF_GEN_WEBSITE_TITLE); ?> " href="<?= Uri::create('@system/functions/opensearch') ?>" />
<?php endif; ?>
		<?= get_setting('fs_theme_header_code'); ?>

	</head>
	<body class="<?= $this->get_selected_theme_class(array('theme_default')) ?>">
<?php if (Radix::get_selected()) : ?>
		<div class="letters" style="display:none"><?php
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
									<?= (Radix::get_selected()) ? '/' . $board->shortname . '/' . ' - ' . $board->name :  get_setting('fs_gen_site_title', FOOL_PREF_GEN_WEBSITE_TITLE) ?> <b class="caret"></b>
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
							<?php if (Radix::get_selected()) : ?>
							<?php if (Radix::get_selected()->archive && Radix::get_selected()->board_url != "") : ?>
								<li>
									<a href="<?= Radix::get_selected()->board_url ?>" style="padding-right:4px;">4chan <i class="icon-share icon-white"></i></a>
								</li>
								<?php endif; ?>
							<li style="padding-right:0px;">
								<a href="<?= Uri::create(array($board->shortname)) ?>" style="padding-right:4px;"><?= __('Index') ?></a>
							</li>
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" style="padding-left:2px; padding-right:4px;">
									<b class="caret"></b>
								</a>
								<ul class="dropdown-menu" style="margin-left:-9px">
									<li>
										<a href="<?= Uri::create(array(Radix::get_selected()->shortname, 'by_post')) ?>">
											<?= __('By Post') ?>
											<?php if ($this->input->cookie('default_theme_by_thread' . (Radix::get_selected()->archive?'_archive':'_board')) != 1) echo ' <i class="icon-ok"></i>'; ?>
										</a>
									</li>
									<li>
										<a href="<?= Uri::create(array(Radix::get_selected()->shortname, 'by_thread')) ?>">
											<?= __('By Thread') ?>
											<?php if ($this->input->cookie('default_theme_by_thread' . (Radix::get_selected()->archive?'_archive':'_board')) == 1) echo ' <i class="icon-ok"></i>'; ?>
										</a>
									</li>
								</ul>
							</li>
							<?php endif; ?>
							<?php
							$top_nav = array();
							if (Radix::get_selected())
							{
								$top_nav[] = array('href' => Uri::create(array(Radix::get_selected()->shortname, 'ghost')), 'text' => __('Ghost'));
								$top_nav[] = array('href' => Uri::create(array(Radix::get_selected()->shortname, 'gallery')), 'text' => __('Gallery'));
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
				<?php if ($is_page) : ?>
				<?= $template['partials']['tools_reply_box'] ?>

				<div class="divider"></div>
				<?php endif; ?>

				<?php if (get_setting('fs_theme_header_text')) : ?>
				<div class="alert alert-block alert-error fade in">
					<button data-dismiss="alert" class="close" type="button">×</button>
					<h4 class="alert-heading"><?= __('Global Announcement') ?></h4>
					<p><?= get_setting('fs_theme_header_text') ?>Change this and that and try again. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit. Cras mattis consectetur purus sit amet fermentum.</p>
				</div>
				<?php endif; ?>

				<?= $template['body'] ?>

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
			<a href="http://github.com/FoOlRulez/FoOlFuuka"><?= FOOL_NAME ?> Imageboard <?= FOOL_VERSION ?></a>
			- <a href="http://github.com/eksopl/asagi" target="_blank">Asagi Fetcher</a>

			<div style="float:right">
				<div class="btn-group dropup pull-right">
					<a href="#" class="btn btn-inverse btn-mini dropdown-toggle" data-toggle="dropdown">
						<?= __('Change Style') ?> <span class="caret"></span>
					</a>
					<ul class="dropdown-menu">
						<?php
						foreach($this->get_available_styles('yotsuba_2') as $key => $style) :
						?>
							<li>
								<a href="<?= Uri::create(array('@system', 'functions', 'theme', $this->get_selected_theme(), $style)) ?>">
									<?= $key ?><?= ($style == $this->get_selected_theme_class()) ?' <i class="icon-ok"></i>':'' ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</div>

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
						<?php foreach(config_item('ff_available_languages') as $key => $lang) : ?>
						<li>
							<a href="<?= Uri::create(array('@system', 'functions', 'language', $key)) ?>">
								<?= $lang ?><?= ((!$this->input->cookie('language') && $key == 'en_EN') || $key == $this->input->cookie('language'))?' <i class="icon-ok"></i>':'' ?>
							</a>
						</li>
						<?php endforeach; ?>
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
				echo '<div class="pull-right" style="margin-right: 15px;">[ ';
				foreach ($bottom_nav as $key => $nav)
				{
					echo '<a href="' . $nav['href'] . '">' . $nav['text'] . '</a>';
					if ($key < count($bottom_nav) - 1)
					{
						echo ' / ';
					}
				}
				echo ' ]</div>';
			}

			if (get_setting('fs_theme_footer_text'))
			{
				echo '<section class="footer_text">' . get_setting('fs_theme_footer_text') . '</section>';
			}
			?>
		</footer>


		<script>
			var backend_vars = <?= json_encode($backend_vars) ?>;
		</script>

		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script>window.jQuery || document.write('<script src="<?= Uri::base() ?>assets/js/jquery.js"><\/script>')</script>
		<script defer src="<?= Uri::base() ?>assets/bootstrap2/js/bootstrap.min.js?v=<?= FOOL_VERSION ?>"></script>
		<script defer src="<?= Uri::base() . $this->fallback_asset('plugins.js') ?>"></script>
		<script defer src="<?= Uri::base() . $this->fallback_asset('board.js') ?>"></script>
<?php if (get_setting('fs_theme_google_analytics')) : ?>
		<script>
			var _gaq=[['_setAccount','<?= get_setting('fs_theme_google_analytics') ?>'],['_trackPageview'],['_trackPageLoadTime']];
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
		<?= get_setting('fs_theme_footer_code'); ?>
	</body>
</html>
