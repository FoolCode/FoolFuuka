<?php
if (!defined('BASEPATH'))
	exit('No direct script access allowed');
?>

<div class="panel">
	<div class="topbar">
		<div>
			<div class="topbar_left">
				<h1 class="tbtitle dnone"><?php echo $comic->url() ?> :: <?php echo $chapter->url() ?></h1>
				<div class="tbtitle dropdown_parent"><div class="text"><?php echo $comic->url() ?> ⤵</div>
					<?php
					echo '<ul class="dropdown">';
					foreach ($comics->all as $co)
					{
						echo '<li>' . $co->url() . '</li>';
					}
					echo '</ul>'
					?>
				</div>	
				<div class="tbtitle dropdown_parent"><div class="text"><?php echo '<a href="' . $chapter->href() . '">' . ((strlen($chapter->title()) > 58) ? (substr($chapter->title(), 0, 50) . '...') : $chapter->title()) . '</a>' ?> ⤵</div>
					<?php
					echo '<ul class="dropdown">';
					foreach ($chapters->all as $ch)
					{
						echo '<li>' . $ch->url() . '</li>';
					}
					echo '</ul>'
					?>
				</div>
				<div class="tbtitle icon_wrapper dnone" ><img class="icon off" src="<?php echo glyphish(181); ?>" /><img class="icon on" src="<?php echo glyphish(181, TRUE); ?>" /></div>
				<?php echo $chapter->download_url(NULL, "fleft"); ?>
			</div>
			<div class="topbar_right">
				<div class="tbtitle dropdown_parent dropdown_right"><div class="text"><?php echo count($pages); ?> ⤵</div>
					<?php
					$url = $chapter->href();
					echo '<ul class="dropdown" style="width:90px;">';
					for ($i = 1; $i <= count($pages); $i++)
					{
						echo '<li><a href="' . $url . 'page/' . $i . '" onClick="changePage(' . ($i - 1) . '); return false;">' . _("Page") . ' ' . $i . '</a></li>';
					}
					echo '</ul>'
					?>
				</div>		

				<div class="divider"></div>
				<span class="numbers">
					<?php
					//for ($i = (($val = $current_page - 3) <= 0)?(1):$val; $i <= count($pages) && $i < $current_page + 3; $i++) {
					for ($i = (($val = $current_page + 2) >= count($pages)) ? (count($pages)) : $val; $i > 0 && $i > $current_page - 3; $i--)
					{
						$current = ((count($pages) / 100 > 1 && $i / 100 < 1) ? '0' : '') . ((count($pages) / 10 > 1 && $i / 10 < 1) ? '0' : '') . $i;
						echo '<div class="number number_' . $i . ' ' . (($i == $current_page) ? 'current_page' : '') . '"><a href="' . $chapter->href . 'page/' . $i . '">' . $current . '</a></div>';
					}
					?>
				</span>
			</div>
		</div>
		<div class="clearer"></div>
	</div>
</div>


<div id="page">

	<div class="inner">
		<a href="<?php echo $chapter->next_page($current_page); ?>" onClick="return nextPage();" >
			<img class="open" src="<?php echo $pages[$current_page - 1]['url'] ?>" />
		</a>
	</div>
</div>

<div class="clearer"></div>

<div id="bottombar">
    <div class="pagenumber">
		<?php echo _('Page') . ' ' . $current_page ?>
    </div>
    <div class="socialbuttons">
        <div class="tweet">
            <a href="http://twitter.com/share" class="twitter-share-button" data-url="<?php echo $chapter->href() ?>" data-count="horizontal" data-via="<?php echo get_setting_twitter(); ?>" data-related="<?php echo get_setting_twitter(); ?>">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
        </div>
		<div class="facebook">
			<iframe src="http://www.facebook.com/plugins/like.php?href=<?php echo urlencode($chapter->href()) ?>&amp;layout=button_count&amp;show_faces=false&amp;width=90&amp;action=like&amp;font=arial&amp;colorscheme=light&amp;height=21" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:95px; height:21px;" allowTransparency="true"></iframe>
		</div>
		<div class="googleplus">
			<g:plusone size="medium" href="<?php echo $chapter->href() ?>"></g:plusone>
		</div>
    </div>
</div>

<script type="text/javascript">
	
	

	var title = document.title;
	
	var pages = <?php echo json_encode($pages); ?>;

	var next_chapter = "<?php echo $next_chapter; ?>";
	
	var preload_next = 5;

	var preload_back = 2;

	var current_page = <?php echo $current_page - 1; ?>;
	
	var initialized = false;
	
	var baseurl = '<?php echo $chapter->href() ?>';
	
	var site_url = '<?php echo site_url() ?>';
	
	var gt_page = '<?php echo addslashes(_("Page")) ?>';
	
	var gt_key_suggestion = '<?php echo addslashes(_("Use W-A-S-D or the arrow keys to navigate")) ?>';

	var gt_key_tap = '<?php echo addslashes(_("Double-tap to change page")) ?>';

	function changePage(id, noscroll, nohash)
	{
		id = parseInt(id);
		if (initialized && id == current_page)
			return false;
		
		if(!initialized) {
			create_message('key_suggestion', 4000, gt_key_suggestion);
		}
		
		initialized = true;
		if(id > pages.length-1) 
		{
			location.href = next_chapter;
			return false;
		}
		if(id < 0){
			current_page = 0;
			id = 0;
		} 
		
		preload(id);
		current_page = id;
		next = parseInt(id+1);
		jQuery("html, body").stop(true,true);
		if(!noscroll) jQuery.scrollTo('.panel', 430, {'offset':{'top':-6}});
		
		
		if(pages[id].loaded !== true) {
			jQuery('#page .inner img.open').css({'opacity':'0'});
			jQuery('#page .inner .preview img').attr('src', pages[id].thumb_url);
			jQuery('#page .inner img.open').attr('src', pages[id].thumb_url);
		}
		else {
			jQuery('#page .inner img.open').css({'opacity':'1'});
			jQuery('#page .inner .preview img').attr('src', pages[id].thumb_url);
			jQuery('#page .inner img.open').attr('src', pages[id].url);
		}
		
		resizePage(id);
		
		if(!nohash) History.pushState(null, null, baseurl+'page/' + (current_page + 1));
		document.title = gt_page+' ' + (current_page+1) + ' :: ' + title;
		update_numberPanel();
		jQuery('#pagelist .images').scrollTo(jQuery('#thumb_' + id).parent(), 400);
		jQuery('#pagelist .current').removeClass('current');
		jQuery('#thumb_' + id).addClass('current');
		
		jQuery("#ads_top_banner.iframe iframe").attr("src", site_url + "content/ads/ads_top.html");
		jQuery("#ads_bottom_banner.iframe iframe").attr("src", site_url + "content/ads/ads_bottom.html");
		
		return false;
	}


	function resizePage(id) {
		var doc_width = jQuery(document).width();
		var page_width = parseInt(pages[id].width);
		var page_height = parseInt(pages[id].height);
		var nice_width = 980;
		var perfect_width = 980;
		
		if(doc_width > 1200) {
			nice_width = 1120;
			perfect_width = 1000;
		}
		if(doc_width > 1600) {
			nice_width = 1400;
			perfect_width = 1300;
		}
		if(doc_width > 1800) {
			nice_width = 1600;
			perfect_width = 1500;
		}
		
		
		if (page_width > nice_width && (page_width/page_height) > 1.2) {
			if(page_height < 1610) {
				width = page_width;
				height = page_height;
			}
			else { 
				height = 1600;
				width = page_width;
				width = (height*width)/(page_height);
			}
			jQuery("#page").css({'max-width': 'none', 'overflow':'auto'});
			jQuery("#page").animate({scrollLeft:9000},400);
			jQuery('#page .inner .preview img').attr({width:width, height:height});
			jQuery("#page .inner img.open").css({'max-width':'99999px'});
			jQuery('#page .inner img.open').attr({width:width, height:height});
			if(jQuery("#page").width() < jQuery("#page .inner img.open").width()) {
				isSpread = true;
				create_message('is_spread', 3000, 'Tap the arrows twice to change page');
			}
			else {
				jQuery("#page").css({'max-width': width+10, 'overflow':'hidden'});
				isSpread = false;
				delete_message('is_spread');
			}
		}
		else{
			if(page_width < nice_width && doc_width > page_width + 10) {
				width = page_width;
				height = page_height;
			}
			else { 
				width = (doc_width > perfect_width) ? perfect_width : doc_width - 10;
				height = page_height; 
				height = (height*width)/page_width;
			}
			jQuery('#page .inner .preview img').attr({width:width, height:height});
			jQuery('#page .inner img.open').attr({width:width, height:height});
			jQuery("#page").css({'max-width':(width + 10) + 'px','overflow':'hidden'});
			jQuery("#page .inner img.open").css({'max-width':'100%'});
			isSpread = false;
			delete_message('is_spread');
		}
	}

	function nextPage()
	{
		changePage(current_page+1);
		return false;
	}
	
	function prevPage()
	{
		changePage(current_page-1);
		return false;
	}
	
	function preload(id)
	{
		var array = [];
		var arraythumb = [];
		var arraydata = [];
		for(i = -preload_back; i < preload_next; i++)
		{
			if(id+i >= 0 && id+i < pages.length)
			{
				array.push(pages[(id+i)].url);
				arraydata.push(id+i);
			}
		}
		
		jQuery.preload(array, {
			threshold: 40,
			enforceCache: true,
			onComplete:function(data)
			{
				var idx = data.index;
				if(data.index == page)
					return false;
				var page = arraydata[idx];
				pages[page].loaded = true;
				jQuery('#thumb_'+ page).addClass('loaded');
				jQuery('.numbers .number_'+ (page+1)).addClass('loaded');
				if(current_page == page)
				{
					jQuery('#page .inner img.open').animate({'opacity':'1.0'}, 800);
					jQuery('#page .inner img.open').attr('src', pages[current_page].url);
				}
			}
	
		});
	}
	
	function create_numberPanel()
	{
		result = "";
		for (j = pages.length+1; j > 0; j--) {
			nextnumber = ((j/1000 < 1 && pages.length >= 1000)?'0':'') + ((j/100 < 1 && pages.length >= 100)?'0':'') + ((j/10 < 1 && pages.length >= 10)?'0':'') + j;
			result += "<div class='number number_"+ j +" dnone'><a href='" + baseurl + "page/" + j + "' onClick='changePage("+(j-1)+"); return false;'>"+nextnumber+"</a></div>"; 
		}
		jQuery(".topbar_right .numbers").html(result);
	}
	
	function update_numberPanel()
	{
		jQuery('.topbar_right .number.current_page').removeClass('current_page');
		jQuery('.topbar_right .number_'+(current_page+1)).addClass('current_page');
		jQuery('.topbar_right .number').addClass('dnone');
		for (i = ((val = current_page - 1) <= 0)?(1):val; i <= pages.length && i < current_page + 4; i++) {
			jQuery('.number_'+i).removeClass('dnone');
		}
                
		jQuery('.pagenumber').html(gt_page + ' ' + (current_page+1));
	}
	
	function chapters_dropdown()
	{
		location.href = jQuery('#chapters_dropdown').val();
	}
	
	function togglePagelist()
	{
		jQuery('#pagelist').slideToggle();
		jQuery.scrollTo('#pagelist', 300);
		jQuery('#panel').scrollTo('#thumb_' + current_page, 400);
	}
	
	
	var isSpread = false;
	var button_down = false;
	var button_down_code;
	
	jQuery(document).ready(function() {
		jQuery(document).keydown(function(e){
			
			if(!button_down && !jQuery("input").is(":focus"))
			{
				button_down = true;
				code = e.keyCode || e.which;
				
				if(e.keyCode==37 || e.keyCode==65)
				{
					if(!isSpread) prevPage();
					else if(e.timeStamp - timeStamp37 < 400 && e.timeStamp - timeStamp37 > 150) prevPage();
					timeStamp37 = e.timeStamp;
				
					button_down = true;
					e.preventDefault();
					button_down_code = setInterval(function() { 
						if (button_down) {
							jQuery('#page').scrollTo("-=13",{axis:"x"});
						} 
					}, 20);
				}
				if(e.keyCode==39 || e.keyCode==68) 
				{
					if(!isSpread) nextPage();
					else if(e.timeStamp - timeStamp39 < 400 && e.timeStamp - timeStamp39 > 150) nextPage();
					timeStamp39 = e.timeStamp;
					
					button_down = true;
					e.preventDefault();
					button_down_code = setInterval(function() { 
						if (button_down) {
							jQuery('#page').scrollTo("+=13",{axis:"x"});
						} 
					}, 20);
				}
				
			
				if(code == 40 || code == 83) 
				{
					e.preventDefault();
					button_down_code = setInterval(function() { 
						if (button_down) {
							jQuery.scrollTo("+=13"); 
						} 
					}, 20);
				}
			
				if(code == 38 || code == 87) 
				{
					e.preventDefault();
					button_down_code = setInterval(function() {
						if (button_down) {
							jQuery.scrollTo("-=13"); 
						} 
					}, 20);
					
				}
			}

		});
		
		jQuery(document).keyup(function(e){
			button_down_code = window.clearInterval(button_down_code);
			button_down = false;
		});
		
		timeStamp37 = 0;
		timeStamp39 = 0;
		
		jQuery(window).bind('statechange',function(){
			var State = History.getState();
			url = parseInt(State.url.substr(State.url.lastIndexOf('/')+1));
			changePage(url-1, false, true);
			document.title = gt_page+' ' + (current_page+1) + ' :: ' + title;
		});
		
		
		
		State = History.getState();
		url = State.url.substr(State.url.lastIndexOf('/')+1);
		if(url < 1)
			url = 1;
		current_page = url-1;
		History.pushState(null, null, baseurl+'page/' + (current_page+1));
		changePage(current_page, false, true);
		create_numberPanel();
		update_numberPanel();
		document.title = gt_page+' ' + (current_page+1) + ' :: ' + title;	
		
		jQuery(window).resize(function() {
			resizePage(current_page);
		});
	});
</script>

<script type="text/javascript">
	(function() {
		var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
		po.src = 'https://apis.google.com/js/plusone.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
	})();
</script>