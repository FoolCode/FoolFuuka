<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<meta http-equiv="imagetoolbar" content="false" />
		<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale = 1.0">
		<title><?php echo $template['title']; ?></title>
		<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>assets/bootstrap2/css/bootstrap.min.css?v=<?php echo FOOL_VERSION ?>" />
		<?php
		if ($this->config->item('theme_extends') != ''
			&& $this->config->item('theme_extends') != (($this->fu_theme) ? $this->fu_theme
					: 'default')
			&& $this->config->item('theme_extends_css') === TRUE
			&& file_exists('content/themes/' . $this->config->item('theme_extends') . '/style.css'))
			echo link_tag('content/themes/' . $this->config->item('theme_extends') . '/style.css?v=' . FOOL_VERSION);

		if (file_exists('content/themes/' . (($this->fu_theme) ? $this->fu_theme : 'default') . '/style.css'))
			echo link_tag('content/themes/' . (($this->fu_theme) ? $this->fu_theme : 'default') . '/style.css?v=' . FOOL_VERSION);
		?>

		<!--[if lt IE 9]>
			<script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
		<![endif]-->
		<?php if (get_selected_radix()) : ?>
			<link rel="alternate" type="application/rss+xml" title="RSS" href="<?php echo site_url(get_selected_radix()->shortname) ?>rss_gallery_50.xml" />
			<link rel="alternate" type="application/atom+xml" title="Atom" href="<?php echo site_url(get_selected_radix()->shortname) ?>atom_gallery_50.xml" />
		<?php endif; ?>
		<link rel='index' title='<?php echo get_setting('fs_gen_site_title') ?>' href='<?php echo site_url() ?>' />
		<meta name="generator" content="<?php echo FOOL_NAME ?> <?php echo FOOL_VERSION ?>" />
		<?php echo get_setting('fs_theme_header_code'); ?>
	</head>
	<body>
		<div class="container-fluid">
			<?php if (get_selected_radix()) : ?>
			<div class="navbar navbar-fixed-top">
				<div class="navbar-inner">
					<div class="container">
						<ul class="nav">
							<li class="dropdown">
								<a href="<?php echo site_url() ?>" class="brand dropdown-toggle" data-toggle="dropdown">
									/<?php echo $board->shortname ?>/ - <?php echo $board->name ?>
									<b class="caret"></b>
								</a>
								<ul class="dropdown-menu">
									<?php
									foreach ($this->radix->get_archives() as $key => $item)
									{
										echo '<li><a href="' . $item->href . '">/' . $item->shortname . '/ - ' . $item->name . ' (archive)</a></li>';
									}
									?>
									<li class="divider"></li>
									<?php
									foreach ($this->radix->get_boards() as $key => $item)
									{
										echo '<li><a href="' . $item->href . '">/' . $item->shortname . '/ - ' . $item->name . '</a></li>';
									}
									?>
								</ul>
							</li>
						</ul>
						<ul class="nav">
							<li>
								<a href="<?php echo site_url(array($board->shortname)) ?>"><?php echo _('Index') ?></a>
							</li>
							<li><a href="<?php echo site_url(array($board->shortname, 'ghost')) ?>"><?php echo _('Ghost') ?></a>
							</li>
							<li><a href="<?php echo site_url(array($board->shortname, 'gallery')) ?>"><?php echo _('Gallery') ?></a>
							</li>
							<li><a href="<?php echo site_url(array($board->shortname, 'statistics')) ?>"><?php echo _('Stats') ?></a>
							</li>
							<li>
								<a href="<?php echo site_url(array($board->shortname, 'by_thread')) ?>"><?php echo _('By thread') ?></a>
							</li>



						</ul>

						<?php echo $template['partials']['tools_view']; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
			<div role="main" id="main">
				<?php if (isset($section_title)): ?>
					<h3 class="section_title"><?php echo $section_title ?></h3>
				<?php else : ?>

				<?php endif; ?>

				<?php
				if ($is_page)
					echo $template['partials']['post_thread'];
				?>

				<?php echo $template['body']; ?>

				<?php
				if ($disable_headers !== TRUE && !$is_statistics && get_selected_radix())
					echo $template['partials']['tools_post'];
				?>

				<?php if (isset($pagination) && !is_null($pagination['total']) && ($pagination['total'] >= 1)) : ?>
					<div class="paginate">
						<ul>
							<?php if ($pagination['current_page'] == 1) : ?>
								<li class="prev disabled"><a href="#">&larr; Previous</a></li>
							<?php else : ?>
								<li class="prev"><a href="<?php echo $pagination['base_url'] . ($pagination['current_page'] - 1); ?>/">&larr; Previous</a></li>
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
		<footer id="footer"><?php echo FOOL_NAME ?> - Version <?php echo FOOL_VERSION ?>, <a href="http://code.google.com/p/fuuka/" target="_blank">Fuuka Fetcher</a> - Version r95
			<div style="float:right">
				Theme [ <a href="<?php echo site_url(array('functions', 'theme', 'default')) ?>" onclick="changeTheme('default'); return false;">Default</a> / <a href="<?php echo site_url(array('functions', 'theme', 'fuuka')) ?>" onclick="changeTheme('fuuka'); return false;">Fuuka</a> ]
			</div>
		</footer>


		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script>window.jQuery || document.write('<script src="<?php echo site_url() ?>assets/js/jquery.js"><\/script>')</script>
		<script defer src="<?php echo site_url() ?>assets/bootstrap2/js/bootstrap.js?v=<?php echo FOOL_VERSION ?>"></script>
		<script defer src="<?php echo site_url() ?>content/themes/<?php
				echo $this->fu_theme ? $this->fu_theme : 'default'
				?>/plugins.js?v=<?php echo FOOL_VERSION ?>"></script>
		<script defer src="<?php echo site_url() ?>content/themes/<?php
				echo $this->fu_theme ? $this->fu_theme : 'default'
				?>/board.js?v=<?php echo FOOL_VERSION ?>"></script>
				<?php if (get_setting('fs_theme_google_analytics')) : ?>
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
		
		<script>
			var backend_vars = <?php echo json_encode($backend_vars) ?>;
		</script>

		<?php echo get_setting('fs_theme_footer_code'); ?>
	</body>
</html>
