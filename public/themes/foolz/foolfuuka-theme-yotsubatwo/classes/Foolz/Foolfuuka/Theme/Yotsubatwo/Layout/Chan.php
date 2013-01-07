<?php

namespace Foolz\Foolfuuka\Theme\Yotsubatwo\Layout;

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
    <link href="<?= $this->getAssetManager()->getAssetLink('style.css') ?>" rel="stylesheet"
          type="text/css"/>
		<?php
	}

	public function getHeader()
	{
		$radix = $this->getBuilderParamManager()->getParam('radix');

		?><!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="generator"
              content="<?= \Foolz\Config\Config::get('foolz/foolfuuka', 'package', 'main.name').' '.\Config::get('foolz/foolfuuka', 'package', 'main.version') ?>"/>
        <meta name="viewport" content="width=device-width,initial-scale=1,minimum-scale = 0.5,maximum-scale = 2.0">

        <title><?= $this->getBuilder()->getProps()->getTitle(); ?></title>
        <link href='<?= \Uri::base() ?>' rel='index' title='<?= \Preferences::get('ff.gen.website_title') ?>'/>
	    <?php if ($radix) : ?>
        <link href="<?= \Uri::create($radix->shortname) ?>rss_gallery_50.xml" rel="alternate" type="application/rss+xml"
              title="RSS"/>
        <link href="<?= \Uri::create($radix->shortname) ?>atom_gallery_50.xml" rel="alternate"
              type="application/atom+xml" title="Atom"/>
	    <?php endif; ?>
        <link href="<?= \Uri::base().'assets/bootstrap2/css/bootstrap.min.css' ?>" rel="stylesheet" type="text/css"/>
        <link href="<?= \Uri::base().'assets/font-awesome/css/font-awesome.css' ?>" rel="stylesheet" type="text/css"/>
        <!--[if lt IE 8]>
	    <link href="<?= \Uri::base().'assets/font-awesome/css/font-awesome-ie7.css' ?>" rel="stylesheet"
	          type="text/css"/>
        <![endif]-->

	    <?php $this->getStyles(); ?>

        <!--[if lt IE 9]>
	    <script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
	    <?php if (\Preferences::get('fu.sphinx.global')) : ?>
        <link rel="search" type="application/opensearchdescription+xml"
              title="<?= \Preferences::get('ff.gen.website_title'); ?> " href="<?= \Uri::create('_/opensearch') ?>"/>
	    <?php endif; ?>

	    <?= \Preferences::get('ff.theme.header_code'); ?>

    </head>
		<?php
	}

	public function getNav()
	{
		$radix = $this->getBuilderParamManager()->getParam('radix');
		$disable_headers = $this->getBuilderParamManager()->getParam('disable_headers', false);

		?>
	<body class="<?= $this->getSelectedThemeClass(); ?>">
	<?php if ($disable_headers !== true) : ?>
    <div class="letters"><?php
		$board_urls = [];
		foreach (\Radix::getArchives() as $key => $item)
		{
			$board_urls[] = '<a href="'.$item->href.'">'.$item->shortname.'</a>';
		}

		if (! empty($board_urls))
		{
			echo sprintf(__('Archives: [ %s ]'), implode(' / ', $board_urls));
		}

		if (\Radix::getArchives() && \Radix::getBoards())
		{
			echo ' ';
		}

		$board_urls = [];
		foreach (\Radix::getBoards() as $key => $item)
		{
			$board_urls[] = '<a href="'.$item->href.'">'.$item->shortname.'</a>';
		}

		if (! empty($board_urls))
		{
			echo sprintf(__('Boards: [ %s ]'), implode(' / ', $board_urls));
		}
		?></div>
		<?php endif; ?>
		<div class="container-fluid">
            <div class="navbar navbar-fixed-top navbar-inverse">
                <div class="navbar-inner">
                    <div class="container">
                        <ul class="nav">
                            <li class="dropdown">
                                <a href="<?= \Uri::base() ?>" id="brand" class="brand dropdown-toggle"
                                   data-toggle="dropdown">
						            <?= ($radix) ? '/'.$radix->shortname.'/'.' - '.$radix->name : \Preferences::get('ff.gen.website_title') ?>
                                    <b class="caret"></b>
                                </a>
                                <ul class="dropdown-menu">
						            <?= '<li><a href="'.\Uri::base().'">'.__('Index').'</a></li>'; ?>
						            <?= (\Auth::has_access('maccess.mod')) ? '<li><a href="'.\Uri::create('admin').'">'.__('Control Panel').'</a></li>' : '' ?>
                                    <li class="divider"></li>
						            <?php
						            if (\Radix::getArchives())
						            {
							            echo '<li class="nav-header">'.__('Archives').'</li>';
							            foreach (\Radix::getArchives() as $key => $item)
							            {
								            echo '<li><a href="'.$item->href.'">/'.$item->shortname.'/ - '.$item->name.'</a></li>';
							            }
						            }

						            if (\Radix::getBoards())
						            {
							            if (\Radix::getArchives())
							            {
								            echo '<li class="divider"></li>';
							            }

							            echo '<li class="nav-header">'.__('Boards').'</li>';
							            foreach (\Radix::getBoards() as $key => $item)
							            {
								            echo '<li><a href="'.$item->href.'">/'.$item->shortname.'/ - '.$item->name.'</a></li>';
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
                                    <a href="<?= $radix->board_url ?>" style="padding-right:4px;">4chan <i
                                            class="icon-share icon-white"></i></a>
                                </li>
					            <?php endif; ?>
                            <li style="padding-right:0px;">
                                <a href="<?= \Uri::create(array($radix->shortname)) ?>"
                                   style="padding-right:4px;"><?= __('Index') ?></a>
                            </li>
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown"
                                   style="padding-left:2px; padding-right:4px;">
                                    <b class="caret"></b>
                                </a>
                                <ul class="dropdown-menu" style="margin-left:-9px">
                                    <li>
                                        <a href="<?= \Uri::create(array($radix->shortname, 'page_mode', 'by_post')) ?>">
								            <?= __('By Post') ?>
								            <?php if (\Cookie::get('default_theme_page_mode_'.($radix->archive ? 'archive' : 'board')) !== 'by_thread') {
								            echo ' <i class="icon-ok"></i>';
							            } ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="<?= \Uri::create(array($radix->shortname, 'page_mode', 'by_thread')) ?>">
								            <?= __('By Thread') ?>
								            <?php if (\Cookie::get('default_theme_page_mode_'.($radix->archive ? 'archive' : 'board')) === 'by_thread') {
								            echo ' <i class="icon-ok"></i>';
							            } ?>
                                        </a>
                                    </li>
                                </ul>
                            </li>
				            <?php endif; ?>
				            <?php
				            $top_nav = array();
				            if ($radix)
				            {
					            $top_nav[] = array('href' => \Uri::create(array($radix->shortname, 'ghost')), 'text' => __('Ghost'));
					            $top_nav[] = array('href' => \Uri::create(array($radix->shortname, 'gallery')), 'text' => __('Gallery'));
				            }

				            if (\Auth::has_access('comment.reports'))
				            {
					            $top_nav[] = array('href' => \Uri::create(array('admin', 'posts', 'reports')), 'text' => __('Reports').(\Report::count() ? ' <span style="font-family:Verdana;text-shadow:none; font-size:11px; color:#ddd;" class="label label-inverse">'.\Report::count().'</span>' : ''));
				            }

				            $top_nav = \Foolz\Plugin\Hook::forge('ff.themes.generic_top_nav_buttons')->setParam('nav', $top_nav)->execute()->get($top_nav);
				            $top_nav = \Foolz\Plugin\Hook::forge('fu.themes.default_top_nav_buttons')->setParam('nav', $top_nav)->execute()->get($top_nav);

				            foreach ($top_nav as $nav)
				            {
					            echo '<li><a href="'.$nav['href'].'">'.$nav['text'].'</a></li>';
				            }
				            ?>
                        </ul>

			            <?= $this->getBuilder()->getPartial('tools_search')->build(); ?>
                    </div>
                </div>
            </div>
		<?php
	}

	public function getContent()
	{
		$pagination = $this->getBuilderParamManager()->getParam('pagination', false);
		$section_title = $this->getBuilderParamManager()->getParam('section_title', false);

		?>
        <div role="main" id="main">
			<?= $this->getBuilder()->isPartial('tools_new_thread_box') ? $this->getBuilder()->getPartial('tools_new_thread_box')->build() : ''; ?>

			<?php if ($section_title) : ?>
            <h3 class="section_title"><?= $section_title ?></h3>
			<?php elseif (\Preferences::get('ff.theme.header_text')) : ?>
            <section class="section_title"><?= \Preferences::get('ff.theme.header_text') ?></section>
			<?php endif; ?>

            <div class="search_box">
				<?= $this->getBuilder()->isPartial('tools_advanced_search') ? $this->getBuilder()->getPartial('tools_advanced_search')->build() : ''; ?>
            </div>

			<?= $this->getBuilder()->getPartial('body')->build(); ?>

			<?php \Foolz\Plugin\Hook::forge('fu.themes.default_after_body_template')->execute(); ?>

			<?= $this->getBuilder()->getPartial('tools_modal')->build(); ?>

			<?php if ($pagination !== false && ! is_null($pagination['total']) && ($pagination['total'] >= 1)) : ?>
            <div class="paginate">
                <ul>
					<?php if ($pagination['current_page'] == 1) : ?>
                    <li class="prev disabled"><a href="#">&larr;  <?= __('Previous') ?></a></li>
					<?php else : ?>
                    <li class="prev"><a
                            href="<?= $pagination['base_url'].($pagination['current_page'] - 1); ?>/">&larr; <?= __('Previous') ?></a>
                    </li>
					<?php endif; ?>

					<?php
					if ($pagination['total'] <= 15) :
						for ($index = 1; $index <= $pagination['total']; $index ++)
						{
							echo '<li'.(($pagination['current_page'] == $index) ? ' class="active"'
								: '').'><a href="'.$pagination['base_url'].$index.'/">'.$index.'</a></li>';
						}
					else :
						if ($pagination['current_page'] < 15) :
							for ($index = 1; $index <= 15; $index ++)
							{
								echo '<li'.(($pagination['current_page'] == $index) ? ' class="active"'
									: '').'><a href="'.$pagination['base_url'].$index.'/">'.$index.'</a></li>';
							}
							echo '<li class="disabled"><span>...</span></li>';
						else :
							for ($index = 1; $index < 10; $index ++)
							{
								echo '<li'.(($pagination['current_page'] == $index) ? ' class="active"'
									: '').'><a href="'.$pagination['base_url'].$index.'/">'.$index.'</a></li>';
							}
							echo '<li class="disabled"><span>...</span></li>';
							for ($index = ((($pagination['current_page'] + 2) > $pagination['total'])
								? ($pagination['current_page'] - 4) : ($pagination['current_page'] - 2)); $index <= ((($pagination['current_page'] + 2) > $pagination['total'])
								? $pagination['total'] : ($pagination['current_page'] + 2)); $index ++)
							{
								echo '<li'.(($pagination['current_page'] == $index) ? ' class="active"'
									: '').'><a href="'.$pagination['base_url'].$index.'/">'.$index.'</a></li>';
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
                    <li class="next"><a
                            href="<?= $pagination['base_url'].($pagination['current_page'] + 1); ?>/"><?= __('Next') ?> &rarr;</a>
                    </li>
					<?php endif; ?>
                </ul>
            </div>
			<?php endif; ?>
        </div> <!-- end of #main -->

        <div id="push"></div>
		</div>
		<?php
	}

	public function getFooter()
	{
		?>
    <footer id="footer">
        <a href="http://github.com/FoOlRulez/FoOlFuuka"><?= \Foolz\Config\Config::get('foolz/foolfuuka', 'package', 'main.name') ?>
            Imageboard <?= \Foolz\Config\Config::get('foolz/foolfuuka', 'package', 'main.version') ?></a>
        - <a href="http://github.com/eksopl/asagi" target="_blank">Asagi Fetcher</a>

        <div style="float:right">
            <div class="btn-group dropup pull-right">
                <a href="#" class="btn btn-inverse btn-mini dropdown-toggle" data-toggle="dropdown">
					<?= __('Change Theme') ?> <span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
					<?php foreach($this->getTheme()->getLoader()->getAll() as $dir) :
					foreach ($dir as $theme) :
						if (isset($theme->enabled) && $theme->enabled) :
							?>
                            <li>
                                <a href="<?= \Uri::create(array('theme', $theme->getConfig('name'))) ?>">
									<?= $theme->getConfig('name') ?><?= ($theme === $this->getTheme())?' <i class="icon-ok"></i>':'' ?>
                                </a>
                            </li>
							<?php endif;
					endforeach;
				endforeach; ?>
                </ul>
            </div>
        </div>

        <div style="float:right">
            <div class="btn-group dropup pull-right">
                <a href="#" class="btn btn-inverse btn-mini dropdown-toggle" data-toggle="dropdown">
					<?= __('Change Language') ?> <span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
					<?php foreach (\Foolz\Config\Config::get('foolz/foolframe', 'package', 'preferences.lang.available') as $key => $lang) : ?>
                    <li>
                        <a href="<?= \Uri::create(array('_', 'language', $key)) ?>">
							<?= $lang ?><?= ((! \Cookie::get('language') && $key == 'en_EN') || $key == \Cookie::get('language')) ? ' <i class="icon-ok"></i>' : '' ?>
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
		$bottom_nav = \Foolz\Plugin\Hook::forge('ff.themes.generic_bottom_nav_buttons')->setParam('nav', $bottom_nav)->execute()->get($bottom_nav);
		$bottom_nav = \Foolz\Plugin\Hook::forge('fu.themes.default_bottom_nav_buttons')->setParam('nav', $bottom_nav)->execute()->get($bottom_nav);

		if (! empty($bottom_nav))
		{
			echo '<div class="pull-right" style="margin-right: 15px;">';
			foreach ($bottom_nav as $key => $nav)
			{
				echo '<a href="'.$nav['href'].'">'.$nav['text'].'</a>';
				if ($key < count($bottom_nav) - 1)
				{
					echo ' - ';
				}
			}
			echo '</div>';
		}

		if (\Preferences::get('ff.theme.footer_text'))
		{
			echo '<section class="footer_text">'.\Preferences::get('ff.theme.footer_text').'</section>';
		}
		?>
    </footer>


    <script>
        var backend_vars = <?= json_encode($this->getBuilderParamManager()->getParam('backend_vars')) ?>;
    </script>

    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <script>window.jQuery || document.write('<script src="<?= \Uri::base().'assets/js/jquery.js' ?>"><\/script>')</script>
    <script defer src="<?= \Uri::base().'assets/bootstrap2/js/bootstrap.min.js?v=' ?>"></script>
    <script defer src="<?= $this->getAssetManager()->getAssetLink('plugins.js') ?>"></script>
    <script defer src="<?= $this->getAssetManager()->getAssetLink('board.js') ?>"></script>

		<?php if (\Preferences::get('ff.theme.google_analytics')) : ?>
    <script>
        var _gaq = [
            ['_setAccount', '<?= \Preferences::get('ff.theme.google_analytics') ?>'],
            ['_trackPageview'],
            ['_trackPageLoadTime']
        ];
        (function (d, t) {
            var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
            g.src = ('https:' == location.protocol ? '//ssl' : '//www') + '.google-analytics.com/ga.js';
            s.parentNode.insertBefore(g, s)
        }(document, 'script'));
    </script>
		<?php endif; ?>

    <!-- Prompt IE 6 users to install Chrome Frame. Remove this if you want to support IE 6.
	 chromium.org/developers/how-tos/chrome-frame-getting-started -->
    <!--[if lt IE 7 ]>
	<script defer src="//ajax.googleapis.com/ajax/libs/chrome-frame/1.0.3/CFInstall.min.js"></script>
	<script defer>window.attachEvent('onload', function () {
		CFInstall.check({mode:'overlay'})
	})</script>
    <![endif]-->
		<?= \Preferences::get('ff.theme.footer_code'); ?>
	</body>
	</html>
	<?php
	}
}