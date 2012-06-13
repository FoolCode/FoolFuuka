<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<meta name="robots" content="noarchive"/>
	<meta name="description" content=""/>
	<meta name="keywords" content=""/>
	<meta name="viewport" content="width=device-width,initial-scale=1"/>
	<!-- 
	<meta name="rating" content="RTA-5042-1996-1400-1577-RTA"/>
	<link rel="shortcut icon" href="//static.4chan.org/image/favicon-test.ico"/>
	<link rel="stylesheet" title="switch" href="//static.4chan.org/css/yotsubanew.104.css"/>
	<link rel="alternate stylesheet" style="text/css" href="//static.4chan.org/css/yotsubanew.104.css" title="Yotsuba New"/>
	<link rel="alternate stylesheet" style="text/css" href="//static.4chan.org/css/yotsubluenew.104.css" title="Yotsuba B New"/>
	<link rel="alternate stylesheet" style="text/css" href="//static.4chan.org/css/futabanew.104.css" title="Futaba New"/>
	<link rel="alternate stylesheet" style="text/css" href="//static.4chan.org/css/burichannew.104.css" title="Burichan New"/>
	<link rel="stylesheet" href="//static.4chan.org/css/yotsubamobile.104.css"/>
	<link rel="apple-touch-icon" href="//static.4chan.org/image/apple-touch-icon-iphone.png"/>
	<link rel="apple-touch-icon" sizes="72x72" href="//static.4chan.org/image/apple-touch-icon-ipad.png"/>
	<link rel="apple-touch-icon" sizes="114x114" href="//static.4chan.org/image/apple-touch-icon-iphone-retina.png"/>
	<link rel="apple-touch-icon" sizes="144x144" href="//static.4chan.org/image/apple-touch-icon-ipad-retina.png"/>
	<link rel="alternate" title="RSS feed" href="/htmlnew/index.rss" type="application/rss+xml"/>
	-->
	<title><?= $template['title']; ?></title>
	<!-- <link rel="next" href="1">  -->
	<?php
		foreach($this->theme->fallback_override('style.css', $this->theme->get_config('extends_css')) as $css)
		{
			echo link_tag($css);
		}
	?>
</head>
<body>
	<div id="boardNavDesktop" class="desktop">
		
	<?php if ($disable_headers !== TRUE) : ?>
		<?php
			$board_urls = array();
			foreach ($this->radix->get_all() as $key => $item)
			{
				$board_urls[] = '<a href="' . $item->href . '">' . $item->shortname . '</a>';
			}

			if (!empty($board_urls))
			{
				echo '[ ' . implode(' / ', $board_urls) . ' ]';
			}
		?>
		
		<?php
			$board_urls = array();

			$board_urls[] = '<a href="' . site_url() . '">' . strtolower(__('Index')) . '</a>';
			if (get_selected_radix())
			{
				$board_urls[] = '<a href="' . site_url(get_selected_radix()->shortname) . '">' . strtolower(__('Top')) . '</a>';
				$board_urls[] = '<a href="' . site_url(array(get_selected_radix()->shortname, 'statistics')) . '">' . strtolower(__('Statistics')) . '</a>';
			}
			$board_urls[] = '<a href="https://github.com/FoOlRulez/FoOlFuuka/issues">' . strtolower(__('Report Bug')) . '</a>';

			echo '[ ' . implode(' / ', $board_urls) . ' ]';
		?>
		
		<?php
			$top_nav = array();
			$top_nav = $this->plugins->run_hook('fu_themes_generic_top_nav_buttons', array($top_nav), 'simple');
			$top_nav = $this->plugins->run_hook('fu_themes_yotsuba_top_nav_buttons', array($top_nav), 'simple');

			if (!empty($top_nav))
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
		?>
		
	<?php endif; ?>

	</div>
	
	<div id="boardNavMobile" class="mobile">
		<div class="boardSelect">
			<strong>Board:&nbsp;&nbsp;</strong>
			<select id="boardSelectMobile">
			<?php
				foreach($this->radix->get_all() as $board) 
				{
					echo '<option value="' . $board->shortname . '"' . 
						(get_selected_radix() && $board->shortname == get_selected_radix()->shortname?' selected="selected"':'') . '></option>';
				}
			?>
			</select>
		</div>
	</div>
	
	<div class="boardBanner">
	<img class="title" src="" alt="4chan"/>
	<div class="boardTitle"><?= get_selected_radix()->formatted_title ?></div>
	</div>
	
	<?php if ($is_page) echo $template['partials']['tools_reply_box']; ?>
	
	<hr/>
	
	<!-- AD GOES HERE -->
	
	<hr/>
	
	<div class="globalMessage">This is a global message!</div>
	
	<hr/>
	
	<form name="delform" id="delform" action="https://sys.4chan.org/htmlnew/imgboard.php" method="post">
		
		<?= $template['body'] ?>
		
		<!-- AD GOES HERE -->

		<hr/>


		<div style="float: right;">
			<div class="deleteform desktop"><input type="hidden" name="mode" value="usrdel"/>Delete Post [<input type="checkbox" name="onlyimgdel" value="on"/>File Only] Password <input type="password" name="pwd"/> <input type="submit" value="Delete"/><input type="button" value="Report"/></div>
			<div class="stylechanger desktop">
				Style
				<select onchange="setActiveStyleSheet(this.value); return false;">
					<?php
						foreach ($this->theme->get_available_themes() as $theme)
						{
							if (($theme = $this->theme->get_by_name($theme)))
							{
								echo '<option value="' . $theme['directory'] . '" >' . $theme['name'] . '</option>';
							}
						}
					?>
				</select>
			</div>
		</div>
	
	</form>
	
	<div class="pagelist desktop"><div class="prev"><span>Previous</span> </div><div class="pages">[<strong>0</strong>] [<a href="1">1</a>] [2] [3] [4] [5] [6] [7] [8] [9] [10] [11] [12] [13] [14] [15] </div><div class="next"><form action="1" onsubmit="location=this.action; return false;"><input type="submit" value="Next" accesskey="x"/></form></div></div><div class="mPagelist mobile"><div class="pages"><span><strong>0</strong></span> <span><a href="1">1</a></span> <span>2</span> <span>3</span> <span>4</span> <span>5</span> <span>6</span> <span>7</span> <span>8</span> <span>9</span> <span>10</span> <span>11</span> <span>12</span> <span>13</span> <span>14</span> <span>15</span> </div><div class="prev">Previous</div><div class="next"><a href="1" class="button">Next</a></div></div>
	
	<div id="boardNavDesktopFoot" class="desktop">
	<?php
		$board_urls = array();
		foreach ($this->radix->get_all() as $key => $item)
		{
			$board_urls[] = '<a href="' . $item->href . '">' . $item->shortname . '</a>';
		}

		if (!empty($board_urls))
		{
			echo '[ ' . implode(' / ', $board_urls) . ' ]';
		}
	?>
		
	<?php
		$board_urls = array();

		$board_urls[] = '<a href="' . site_url() . '">' . strtolower(__('Index')) . '</a>';
		if (get_selected_radix())
		{
			$board_urls[] = '<a href="' . site_url(get_selected_radix()->shortname) . '">' . strtolower(__('Top')) . '</a>';
			$board_urls[] = '<a href="' . site_url(array(get_selected_radix()->shortname, 'statistics')) . '">' . strtolower(__('Statistics')) . '</a>';
		}
		$board_urls[] = '<a href="https://github.com/FoOlRulez/FoOlFuuka/issues">' . strtolower(__('Report Bug')) . '</a>';

		echo '[ ' . implode(' / ', $board_urls) . ' ]';
	?>

	<?php
		$top_nav = array();
		$top_nav = $this->plugins->run_hook('fu_themes_generic_top_nav_buttons', array($top_nav), 'simple');
		$top_nav = $this->plugins->run_hook('fu_themes_yotsuba_top_nav_buttons', array($top_nav), 'simple');

		if (!empty($top_nav))
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
	?>
	</div>
	
	<div id="absbot" class="absBotText">- <a href="http://www.2chan.net/" target="_top" rel="nofollow">futaba</a> + <a href="//www.4chan.org/" target="_top">yotsuba</a> -<br/>
		All trademarks and copyrights on this page are owned by their respective parties. Images uploaded are the responsibility of the Poster. Comments are owned by the Poster.
	</div>
		
</body>
</html>