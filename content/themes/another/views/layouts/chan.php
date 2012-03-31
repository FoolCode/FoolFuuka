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
	<body class="<?php if (get_selected_radix()) echo 'board_' . get_selected_radix()->shortname; ?>">
		<ul class="dropdown-menu-bla">
						<?php if ($this->radix->get_all()) : ?>
							<?php
							foreach ($this->radix->get_all() as $key => $item)
							{
								echo '<li><a href="' . $item->href . '">/' . $item->shortname . '/ - ' . $item->name . '</a></li>';
							}
						endif;
						?>
					</ul>
		<header>
			<div class="top">
				<span class="dropdown">
					<span data-function="toggleDropdown">My nymphits <span class="caret"></span></span>
					
				</span>

				<span class="trending">
					<a href="<?php echo site_url('foolz') ?>">FoOlz</a> -
						<a href="<?php echo site_url() ?>">LOLSORANDUMBXD</a> | 
						<a href="<?php echo site_url('mlp') ?>">pony</a> -
						<a href="<?php echo site_url('a') ?>">animu</a> -
						<a href="<?php echo site_url('v') ?>">vidya</a> -
						<a href="<?php echo site_url('jp') ?>">shitposting</a> -
						<a href="<?php echo site_url('vg') ?>">vidya2</a> -
						<a href="<?php echo site_url('tv') ?>">terevi</a> -
						<a href="<?php echo site_url('u') ?>">humanRights</a> -
						<a href="<?php echo site_url('m') ?>">bigRobots</a> -
						<a href="<?php echo site_url('tg') ?>">dices</a>
				</span>
			</div>
			<div class="header">
				<div class="sections">
					<a class="title" href="<?php echo site_url(get_selected_radix()->shortname) ?>"><?php echo get_selected_radix()->formatted_title ?></a>
					<?php if($is_thread || $is_last50) : ?>
							<a class="active" href="<?php echo site_url(get_selected_radix()->shortname.'/hot/') ?>">comments</a>
					<?php else : ?>
					<a class="active" href="<?php echo site_url(get_selected_radix()->shortname.'/hot/') ?>">what's hot</a>
					<a href="<?php echo site_url(get_selected_radix()->shortname.'/newest/') ?>">new</a>
					<a href="<?php echo site_url(get_selected_radix()->shortname.'/search/text/faggot/') ?>">controversial</a>
					<?php endif; ?>
				</div>

				<div class="join_us">
					want to join? <a href="http://twitter.com">login or register</a> in seconds | Moonrune
				</div>
			</div>
		</header>
		
		<div id="main" role="main">
			<section class="sidebar">
				<div class="search search-dropdown">
					<?php 
						echo form_open_multipart(get_selected_radix()->shortname .'/search');
						echo form_input(array(
							'name' => 'text', 
							'placeholder' => 'search nymphit',
							'class' => 'search-query',
							'data-function' => 'searchShow'
						)); 
						?>
						
						<ul class="search-dropdown-menu">
							<li>
								<?php
								echo form_submit(array(
									'class' => 'btn btn-success btn-mini',
									'value' => _('Undefined'),
									'name' => 'submit_undefined',
									'style' => 'display:none;'
								))
								?>

								<?php
								echo form_submit(array(
									'class' => 'btn btn-success btn-mini',
									'value' => _('Search'),
									'name' => 'submit_search'
								))
								?>

								<?php
								if($board->shortname)
								{
									echo form_submit(array(
										'class' => 'btn btn-success btn-mini',
										'value' => _('Go to post'),
										'name' => 'submit_post'
									));
								}
								?>

								<?php
								echo form_button(array(
									'data-function' => 'searchHide',
									'class' => 'btn btn-danger btn-mini pull-right',
									'content' => _('Close'),
								))
								?>

							</li>

							<li class="divider"></li>

							<li class="input-prepend"><span class="add-on">Subject</span><?php echo form_input(array('name' => 'subject', 'id' => 'subject', 'value' => (isset($search["subject"]))
											? rawurldecode($search["subject"]) : ''))
								?></li>
							<li class="input-prepend"><span class="add-on">Username</span><?php echo form_input(array('name' => 'username', 'id' => 'username', 'value' => (isset($search["username"]))
											? rawurldecode($search["username"]) : ''))
								?></li>
							<li class="input-prepend"><span class="add-on">Tripcode</span><?php echo form_input(array('name' => 'tripcode', 'id' => 'tripcode', 'value' => (isset($search["tripcode"]))
											? rawurldecode($search["tripcode"]) : ''))
								?></li>
							<li class="input-prepend"><span class="add-on">Date start</span><?php
								$date_array = array(
										'placeholder' => 'yyyy-mm-dd',
										'type' => 'date',
										'name' => 'start',
										'id' => 'date_start'
									);
								if(isset($search["date_start"]))
								{
									$date_array['value'] = rawurldecode($search["date_start"]);
								}

								echo form_input($date_array);
								?></li>

							<li class="input-prepend"><span class="add-on">Date end</span><?php
								$date_array = array(
										'placeholder' => 'yyyy-mm-dd',
										'type' => 'date',
										'name' => 'end',
										'id' => 'date_end',
									);

								if(isset($search["date_end"]))
								{
									$date_array['value'] = rawurldecode($search["date_end"]);
								}
								echo form_input($date_array);

								?></li>
							<li><?php echo _('Filters:') ?></li>
							<li>
								<label>
				<?php echo form_radio(array('name' => 'deleted', 'value' => '', 'checked' => (empty($search["deleted"]))
							? TRUE : FALSE));
				?>
									All
								</label>
							</li>
							<li>
								<label>
				<?php echo form_radio(array('name' => 'deleted', 'value' => 'deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'deleted')
							? TRUE : FALSE));
				?>
									Only Deleted
								</label>
							</li>
							<li>
								<label>
				<?php echo form_radio(array('name' => 'deleted', 'value' => 'not-deleted', 'checked' => (!empty($search["deleted"]) && $search["deleted"] == 'not-deleted')
							? TRUE : FALSE));
				?>
									Only Non-Deleted
								</label>
							</li>

							<li class="divider"></li>

							<li>
								<label>
									<?php echo form_radio(array('name' => 'ghost', 'value' => '', 'checked' => (empty($search["ghost"]))
												? TRUE : FALSE));
									?>
									<span>All</span>
								</label>
							</li>
							<li>
								<label>
									<?php echo form_radio(array('name' => 'ghost', 'value' => 'only', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'only')
												? TRUE : FALSE));
									?>
									<span>Only Ghost</span>
								</label>
							</li>
							<li>
								<label>
				<?php echo form_radio(array('name' => 'ghost', 'value' => 'none', 'checked' => (!empty($search["ghost"]) && $search["ghost"] == 'none')
							? TRUE : FALSE));
				?>
									<span>Only Original</span>
								</label>
							</li>

							<li class="divider"></li>

							<li>
								<label>
				<?php echo form_radio(array('name' => 'filter', 'value' => '', 'checked' => (empty($search["filter"]))
							? TRUE : FALSE));
				?>
									<span>All</span>
								</label>
							</li>
							<li>
								<label>
				<?php echo form_radio(array('name' => 'filter', 'value' => 'text', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'text')
							? TRUE : FALSE));
				?>
									<span>Only with image</span>
								</label>
							</li>
							<li>
								<label>
									<?php echo form_radio(array('name' => 'filter', 'value' => 'image', 'checked' => (!empty($search["filter"]) && $search["filter"] == 'image')
												? TRUE : FALSE));
									?>
									<span>Only without image</span>
								</label>
							</li>

							<li class="divider"></li>

							<li>
								<label>
				<?php echo form_radio(array('name' => 'type', 'value' => '', 'checked' => (empty($search["type"]))
							? TRUE : FALSE));
				?>
									<span>All</span>
								</label>
							</li>
							<li>
								<label>
				<?php echo form_radio(array('name' => 'type', 'value' => 'op', 'checked' => (!empty($search["type"]) && $search["type"] == 'op')
							? TRUE : FALSE));
				?>
									<span>Thread OPs Only</span>
								</label>
							</li>
							<li>
								<label>
				<?php echo form_radio(array('name' => 'type', 'value' => 'posts', 'checked' => (!empty($search["type"]) && $search["type"] == 'posts')
							? TRUE : FALSE));
				?>
									<span>Replies Only</span>
								</label>
							</li>


							<li class="divider"></li>

							<li>
								<label>
				<?php echo form_radio(array('name' => 'capcode', 'value' => '', 'checked' => (empty($search["capcode"]))
							? TRUE : FALSE));
				?>
									<span>All</span>
								</label>
							</li>

							<li>
								<label>
				<?php echo form_radio(array('name' => 'capcode', 'value' => 'user', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'user')
							? TRUE : FALSE));
				?>
									<span>Only by users</span>
								</label>
							</li>
							<li>
								<label>
				<?php echo form_radio(array('name' => 'capcode', 'value' => 'mod', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'mod')
							? TRUE : FALSE));
				?>
									<span>Only by mods</span>
								</label>
							</li>
							<li>
								<label>
									<?php echo form_radio(array('name' => 'capcode', 'value' => 'admin', 'checked' => (!empty($search["capcode"]) && $search["capcode"] == 'admin')
												? TRUE : FALSE));
									?>
									<span>Only by admins</span>
								</label>
							</li>



							<li class="divider"></li>


							<li>
								<label>
						<?php echo form_radio(array('name' => 'order', 'value' => 'desc', 'checked' => (empty($search["order"]) || (!empty($search["order"]) && $search["order"] == 'desc'))
									? TRUE : FALSE));
						?>
									<span>New First</span>
								</label>
							</li>
							<li>
								<label>
				<?php echo form_radio(array('name' => 'order', 'value' => 'asc', 'checked' => (!empty($search["order"]) && $search["order"] == 'asc')
							? TRUE : FALSE));
				?>
									<span>Old First</span>
								</label>
							</li>

							<?php if($board->shortname) : ?>
							<li class="divider"></li>
							<li class="input-prepend"><span class="add-on">Image</span><input type="file" name="image" />
								</li><li><?php
								echo form_submit(array(
									'class' => 'btn btn-success btn-mini',
									'value' => _('Search image'),
									'name' => 'submit_image',
									'title' => _('On most browsers you can also drop the file on the search bar.'),
									'style' => 'margin-top:0;'
								))
								?></li>
							<?php endif; ?>

						</ul>
					<?php	
						echo form_close();
					?>
				</div>
				
				<div class="box">
					<div class="follow">
						<a href="https://twitter.com/foolrulez" class="twitter-follow-button" data-show-count="true">Follow @foolrulez</a>
						<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
					</div>
					
					<h4>News</h4>
					<div class="item">
						<strong>No news at the moment</strong>
					</div>
					
					<h4>Conventions</h4>
					<ul class="item">
						<li><a href="http://archive.foolz.us/jp/search/text/meetup*">/jp/ meetups</a></li>
					</ul>
					
					<h4>Rules</h4>
					<ul class="item">
						<li>&gt;Quotes should quote</li>
						<li>Don't spoonfeed</li>
						<li>We are not lehjuun</li>
						<li>OP, you are a faggot</li>
						<li>Love Boku</li>
						<li>The only allowed smilie is ;_;</li>
						<li>Worship Nymph</li>
					</ul>
					
					<h4>Spoiler</h4>
					<div class="item">
						<strong>YOU MUST POST ALL SPOILERS USING THIS FORMAT</strong>
						<br/>
						<code>[spoiler]Spike dies[/spoiler]</code>
						It will show up like this:
						<br/>
						<span class="spoiler">Kamina dies too</span>
						<br/><br/>
						Do not post spoilers in the submission title.
						<br/><br/>
						Links to external articles/images with spoilers should have [SPOILERS] in the title of the submission as well as the name of the show.
						<br/><br/>
						If you repeatedly fail to properly use spoiler tags you will be <span class="spoiler"><a href="http://www.youtube.com/watch?v=VVmbhYKDKfU">LOVED</a></span>.
					</div>
					
					<h4>Help</h4>
					<ul class="item">
						<li>
							<a href="http://github.com/foolrulez/foolfuuka">If you find bugs in this thing</a>
						</li>
						<li>
							<a href="<?php echo site_url('functions/theme/default')?>">Back to default theme</a>
						</li>
						<li>
							<a href="#" onClick="alert('LOL XD'); return false">Why do we love reddit</a>
						</li>
						<li>
							<a href="http://www.youtube.com/watch?v=mu8G5fVzsyc">You actually need help?</a>
						</li>
					</ul>
					<div class="separator"></div>
					<div class="item">
						<strong>Please, use the report button in case you find any kind 
						of illegal or questionable content on the site. 
						The report button is only available on the default theme.</strong>
					</div>
					<div class="separator"></div>
					<div class="item">
						<strong>In case you can't find an image, use the default theme and click on the links near the image.</strong>
					</div>
					
				</div>
			</section>
			
			
			<div class="content">
				<div class="silly_notice">Remember: the default theme will be phased out as soon as we enable the reporting on this theme.</div>
				<?php echo $template['body']; ?>
				
				<div style="clear:left; margin:5px; font-size:12px; color:#808080">
				<?php if (isset($pagination) && !is_null($pagination['total']) && ($pagination['total'] >= 1)) : ?>
					more:
							<?php if ($pagination['current_page'] == 1) : ?>
							<?php else : ?>
								<a href="<?php echo $pagination['base_url'] . ($pagination['current_page'] - 1); ?>/">previous</a>
							<?php endif; ?>

							<?php if ($pagination['current_page'] != 1 && $pagination['total'] != $pagination['current_page']) : ?>
							|
							<?php endif; ?>
							
							<?php if ($pagination['total'] == $pagination['current_page']) : ?>
							<?php else : ?>
								<a href="<?php echo $pagination['base_url'] . ($pagination['current_page'] + 1); ?>/">next</a></li>
							<?php endif; ?>
				<?php endif; ?>
				</div>
			</div>
			
			
			
			
			
			
			
			
		</div>
		
					<footer>You might be interested in leather jackets. <br/>We're selling these fine leather jackets.
						<div class="big">{{$footer}}</div>
					</footer>
					<div class="subfooter">This theme is inspired by reddit\anime and sure as hell I will get rid of this tomorrow.<br/>
					Mue Nymph is my waifu don't touch;</div>
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script>window.jQuery || document.write('<script src="<?php echo site_url() ?>assets/js/jquery.js"><\/script>')</script>
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