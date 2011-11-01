<?php if (!defined('BASEPATH'))
	exit('No direct script access allowed'); ?>

<div class="table">
	<div style="margin:0 10px 15px 0;">
		<h3><?php echo _('Sitemap for search engines') ?></h3>
		<p><?php echo _('You can submit your sitemap to the search engines to index your FoOlSlide better and faster. We suggest adding this to your Google Webmaster Tools admin panel.') ?></p>
		<p><code><?php echo site_url() ?>sitemap.xml</code></p>
	</div>

	<?php if ($imagick_optimize): ?>
		<div style="margin:0 10px 15px 0;">
			<h3><?php echo _('Optimize Thumbnails') ?></h3>
			<p><span class="label important"><?php echo _('Important') ?></span> <?php echo _('FoOlSlide has detected that your server is able to use a better compression algorithm for thumbnail generation. This optimization will create small thumbnails and reduce up to 10% bandwidth usage. However, regardless of this action, all thumbnails will be generated with this new algorithmn.') ?></p>
			<span><a href="#" class="btn" data-keyboard="true" data-backdrop="true" data-controls-modal="modal-for-thumbnail-optimization" onClick="return getThumbNumber();"><?php echo _('Optimize Thumbnails'); ?></a></span>

			<div id="modal-for-thumbnail-optimization" class="modal hide fade" style="display: none">
				<div class="modal-header">
					<a class="close" href="#">&times;</a>
					<h3><?php echo _('Optimize Thumbnails'); ?></h3>
				</div>
				<div class="modal-body" style="text-align: center">
					<div id="modal-optimize-thumbnails-errors" style="margin-bottom:10px;"></div>
					<div id="modal-optimize-thumbnails-count" style="margin-bottom:10px;"><?php echo _('Pictures left to be processed:') ?> <span id="modal-optimize-thumbnails-current-count">0</span></div>
					<div id="modal-loading-optimize-thumbnails" class="loading" style="display:block;"><img src="<?php echo site_url() ?>assets/js/images/loader-18.gif"/></div>
				</div>
				<div class="modal-footer">
					<a href="#" class="btn primary" onClick="return optimizeThumbnails(true)"><?php echo _('Optimize') ?></a>
					<a href="#" class="btn secondary" onClick="return stopOptimizeThumbnails()"><?php echo _('Stop Optimization') ?></a>
				</div>

				<script type="text/javascript">
																						
																						
					var stop = false;
																						
					var stopOptimizeThumbnails = function() {
						stop = true;
					}
																						
					var optimizeThumbnails = function(manual){
						if(manual === true)
						{
							stop = false;
						}
																							
						if(!stop)
						{
							jQuery('#modal-loading-optimize-thumbnails').show();
							stop = false;
							jQuery.post('<?php echo site_url('admin/system/tools_optimize_thumbnails/10') ?>', function(data){
								if(data.error instanceof Array)
								{
									jQuery('#modal-loading-optimize-thumbnails').hide();
									jQuery.each(data.error, function(i,v){
										jQuery('#modal-optimize-thumbnails-errors').append('<div class="alert-message error fade in" data-alert="alert"><p>' + v.message + '</p></div>');
									});
									return false;
								}
																		
								if(data.warning instanceof Array)
								{
									jQuery.each(data.error, function(i,v){
										jQuery('#modal-optimize-thumbnails-errors').append('<div class="alert-message warning fade in" data-alert="alert"><p>' + v.message + '</p></div>');
									});
								}
																									
								if(data.status == "done")
								{
									jQuery('#modal-optimize-thumbnails-count').html('<?php echo _('Done.') ?>');
									jQuery('#modal-loading-optimize-thumbnails').hide();
									return false;
								}
																									
								var activeCount = jQuery('#modal-optimize-thumbnails-current-count');
								activeCount.text((parseInt(activeCount.html()) < 10)?0:parseInt(activeCount.html()) - 10);
								optimizeThumbnails();
							}, 'json');
						}
						else
						{
							jQuery('#modal-loading-optimize-thumbnails').hide();
						}
					}
																						
					jQuery(document).ready(function(){
						jQuery('#modal-for-thumbnail-optimization').bind('show', function () {
							jQuery.post('<?php echo site_url('admin/system/tools_optimize_thumbnails') ?>', function(data){
								jQuery('#modal-loading-optimize-thumbnails').hide();
								jQuery('#modal-optimize-thumbnails-errors').empty();
								jQuery('#modal-optimize-thumbnails-current-count').text(data.count);
							}, 'json');
						});
																							
						jQuery('#modal-for-thumbnail-optimization').bind('hide', function () {
							stop = true;
						});
					});
				</script>
			</div>
		</div>
	<?php endif; ?>

	<?php if ($database_backup): ?>
		<div style="margin:0 10px 15px 0;">
			<h3><?php echo _('Download Database Backup') ?></h3>
			<p><?php echo _('This will allow you to routinely download a copy of your FoOlSlide database. Furthermore, routine backups of the FoOlSlide directory is also required in case of a complete server failure for this file to be useful.') ?></p>
			<span><a href="<?php echo site_url('admin/system/tools_database_backup') ?>" class="btn" data-keyboard="true" data-backdrop="true"><?php echo _('Download Database Backup'); ?></a></span>
		</div>
	<?php endif; ?>

	<?php if ($database_optimize): ?>
		<div style="margin:0 10px 15px 0;">
			<h3><?php echo _('Optimize Database') ?></h3>
			<p><?php echo _('Performing database optimization from time to time will cause your FoOlSlide installation to be slightly faster.') ?></p>
			<span style=""><?php
	$CI = & get_instance();
	$CI->buttoner[] = array(
		'text' => _('Optimize Database'),
		'href' => site_url('admin/system/tools_database_optimize'),
		'plug' => _('Are you sure you want to optimize your FoOlSlide database?')
	);
	echo buttoner();
	$CI->buttoner = array();
		?></span>
		</div>
	<?php endif; ?>


	<div style="margin:0 10px 15px 0;">
		<h3><?php echo _('FoOlSlide Logs') ?></h3>
		<p><?php echo _('Daily logs are generated by FoOlSlide and contains information that will help developers debug your problems. If any actual errors are found, please report any serious errors to the FoOlSlide developers. However, do not report any 404 and missing cookie notices.') ?></p>
		<span><a href="#" class="btn" data-keyboard="true" data-backdrop="true" data-controls-modal="modal-for-log-display" onClick="return getLog();"><?php echo _('View Logs'); ?></a></span>
		<span style=""><?php
	$CI = & get_instance();
	$CI->buttoner[] = array(
		'text' => _('Prune Logs'),
		'href' => site_url('admin/system/tools_logs_prune'),
		'plug' => _('Are you sure you want to prune all FoOlSlide logs?'),
		'rel' => 'popover-right',
		'title' => _('Prune Logs'),
		'data-content' => _('FoOlSlide logs can often use a lot of space. This function will allow you to remove all logs to save space.') . '<br/><br/>' . _('Current Size') . ': ' . $logs_space . 'kb'
	);
	echo buttoner();
	?></span>

		<div id="modal-for-log-display" class="modal hide fade" style="display: none">
			<div class="modal-header">
				<a class="close" href="#">&times;</a>
				<h3><?php echo _('View Logs'); ?></h3>
			</div>
			<div class="modal-body" style="text-align: center">
				<div id="modal-loading-log-display" class="loading" style="display:block;"><img src="<?php echo site_url() ?>assets/js/images/loader-18.gif"/></div>
				<select id="modal-select-log" style="display:none; margin-bottom:10px;" onchange="getLog(this.value)"></select>
				<textarea id="log-display-output" style="min-height: 300px; font-family: Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace !important" readonly="readonly">
				</textarea>
				<div id="modal-log-display-errors" style="margin-top:10px;"></div>
			</div>
			<div class="modal-footer">
				<?php
				if (function_exists('curl_init'))
				{
					echo '<center><a class="btn" style="float: none" href="#" onclick="return pastebinLog()">' . _('Pastebin It!') . '</a></center>';
				}
				?>
			</div>

			<script type="text/javascript">
				var getLog = function(date){
					jQuery('#modal-loading-log-display').show();
					
					if(date == undefined)
					{
						date = "";
					}
					
					jQuery.post('<?php echo site_url('admin/system/tools_logs_get/') ?>' + date, function(data){
						var log_select = jQuery('#modal-select-log');
						if(data.error != undefined)
						{
							if(log_select.text().length < 3)
							{
								jQuery('#log-display-output').val(data.error);
								jQuery('#modal-loading-log-display').hide();
								jQuery("#modal-for-log-display").find(".modal-footer").html('')
								return false;
							}
							else
							{
								jQuery('#modal-loading-log-display').hide();
								jQuery('#modal-log-display-errors').append('<div class="alert-message error fade in" data-alert="alert"><p>' + data.error + '</p></div>');
								return false;
							}
						}
						
						
						if(log_select.text().length < 3)
						{
							var options = '';
							jQuery.each(data.dates, function(i,v){
								options = '<option value="' + v + '">' + v + '</option>' + options;
							});
							log_select.empty().html(options).show();
						}
						
						jQuery('#modal-loading-log-display').hide();
						jQuery('#log-display-output').val(data.log);
						jQuery("#modal-for-log-display").find(".modal-footer").html('<center><a class="btn" style="float: none" href="#" onclick="return pastebinLog()"><?php echo _('Pastebin It!') ?></a></center>');
					}, 'json');
				}
				
				var pastebinLog = function() {
					var modalInfoOutput = jQuery("#modal-for-log-display");
					jQuery.post('<?php echo site_url("admin/system/pastebin") ?>', { output: modalInfoOutput.find("#log-display-output").val() }, function(result) {
						if (result.href != "") {
							modalInfoOutput.find(".modal-footer").html('<center><input value="' + result.href + '" style="text-align: center" onclick="select(this);" readonly="readonly" /><br/><?php echo _('Note: This paste expires in 1 hour.'); ?></center>');
						}
					}, 'json');
					return false;
				}
			</script>
		</div>

	</div>

	<div style="margin:0 10px 15px 0;">
		<h3><?php echo _('Check and repair the library') ?></h3>
		<p><span class="label important">Danger Zone™</span> <?php echo _('The repair function brutally edits your files and database. While it should be safe to use, you might lose data you actually want to save.') ?></p>
		<p><span class="label success"><?php echo _('Suggestion') ?></span> <?php echo _('Have a backup of your database and of your FoOlSlide directory before proceeding. Perform a check before clicking on repair.') ?></p>
		<p><?php echo _('It can happen that you or some server error mess with the FoOlSlide library. This will allow you to find broken database entries and missing files. The repair function will rebuild the missing thumbnails, remove the database entries for missing files and remove the unidentified files.') ?></p>
		<p><?php echo sprintf(_('You can also use this function via command line. Use the following line: %s'), '<br/><code>php ' . FCPATH . 'index.php admin system tools_check_comics</code>') ?></p>
		<span>
			<a href="#" class="btn success" data-keyboard="true" data-backdrop="true" data-controls-modal="modal-for-library-check" onClick="checkLibrary(true)"><?php echo _('Check library'); ?></a>
			<a href="#" class="btn danger" rel="popover-right" data-original-title="Repair library" data-content="<?php echo htmlentities(_('This is actually a dangerous operation. Always perform a check and make sure you have a backup of your data.')) ?>" data-keyboard="true" data-backdrop="true" data-controls-modal="modal-for-library-check" onClick="checkLibrary(true, true)"><?php echo _('Repair library'); ?></a>
		</span>



		<div id="modal-for-library-check" class="modal hide fade" style="display: none;">
			<div class="modal-header">
				<a class="close" href="#">&times;</a>
				<h3><?php echo _('Check and repair the library'); ?></h3>
			</div>
			<div class="modal-body" style="text-align: center">
				<div id="modal-check-status" style="margin-bottom:10px;"></div>
				<div id="modal-loading-check-display" class="loading" style="display:block;"><img src="<?php echo site_url() ?>assets/js/images/loader-18.gif"/></div>
				<textarea id="check-display-output" style="min-height: 300px; font-family: Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace !important" readonly="readonly"></textarea>
				<div id="modal-check-display-errors" style="margin-top:10px;"></div>
			</div>
			<div class="modal-footer">
				<?php
				if (function_exists('curl_init'))
				{
					echo '<center><a class="btn" style="float: none" href="#" onclick="return pastebinCheck()">' . _('Pastebin It!') . '</a></center>';
				}
				?>
			</div>

			<script type="text/javascript">
				var stop_check = false;
					
				var check_library = false;
				var items_page = 1;
				var items_left = 0;
				var check_pages = false;
							
				var repairLibrary = function(){
					var thisModal = jQuery('#modal-for-library-check');
					thisModal.modal('show');
					checkLibrary(true, true);
				};
							
				var checkLibrary = function(manual, repair){
					jQuery('#modal-loading-check-display').show();
					
					if(repair !== true)
					{
						jQuery('#modal-for-library-check').find('h3').text('<?php echo htmlentities(_('Checking the library')) ?>');	
					}
					else
					{
						jQuery('#modal-for-library-check').find('h3').text('<?php echo htmlentities(_('Repairing the library')) ?>');						
					}
					
					if(manual === true)
					{
						items_page = 1;
						items_left = 0;
						check_library = false;
						check_pages = false;
						jQuery('#check-display-output').val("");
						stop_check = false;
					}
													
					if(!stop_check)
					{
						if(check_library !== true)
						{
							jQuery('#modal-check-status').text('<?php echo htmlentities(_('Checking comics folder.')) ?>')
							jQuery.post('<?php echo site_url('admin/system/tools_check_comics/') ?>',
							{
								repair: (repair === true)?'repair':'false'
							},
							function(data){
								if(data.status == 'error')
								{
									jQuery('#modal-loading-check-display').hide();
									jQuery('#modal-check-display-errors').append('<div class="alert-message error fade in" data-alert="alert"><p>' + data.error + '</p></div>');
									jQuery('#modal-check-display-errors').append('<div class="alert-message error fade in" data-alert="alert"><p><?php echo htmlentities(_('You must fix the errors before you can proceed.')) ?></p></div>');
									return false;
								}
						
								if(data.status == 'warning')
								{
									var messages = "";
									jQuery.each(data.messages, function(index, value){
										messages += "["+((repair === true)?'repairing':'warning')+"] " + value + "\n";
									});
									jQuery('#check-display-output').val(messages);
								}
								
								items_left = data.count;
								jQuery('#modal-check-status').text('<?php echo htmlentities(_('Chapters left to check:')) ?> ' + items_left);

								check_library = true;
								setTimeout(checkLibrary, 0, false, repair);
								
							}, 'json');
						}
						else
						{
							jQuery.post('<?php echo site_url('admin/system/tools_check_library/') ?>',
							{
								repair: (repair === true)?'repair':'false',
								page: items_page,
								type: (check_pages === true)?'page':'chapter'
							},
							function(data){
								items_page++;
								if(data.status == 'error')
								{
									jQuery('#modal-loading-check-display').hide();
									jQuery('#modal-check-display-errors').append('<div class="alert-message error fade in" data-alert="alert"><p>' + data.error + '</p></div>');
									jQuery('#modal-check-display-errors').append('<div class="alert-message error fade in" data-alert="alert"><p><?php echo htmlentities(_('You must fix the errors before you can proceed.')) ?></p></div>');
									return false;
								}
						
								if(data.status == 'warning')
								{
									var messages = "";
									jQuery.each(data.messages, function(index, value){
										messages += "["+((repair === true)?'repairing':'warning')+"] " + value + "\n";
									});
									jQuery('#check-display-output').val(jQuery('#check-display-output').val() + messages);
								}
								
								if(data.status == 'done')
								{
									if(check_pages === false)
									{
										check_pages = true;
										items_page = 1;
										items_left = data.pages_count;
										setTimeout(checkLibrary, 0, false, repair);
										return true;
									}
									jQuery('#modal-loading-check-display').hide();
									jQuery('#modal-check-status').text('<?php echo htmlentities(_('Done.')) ?>');
									return true;
								}
								
								items_left -= data.processed;
								jQuery('#modal-check-status').text(((!check_pages)?'<?php echo htmlentities(_('Chapters left to check:')) ?> ':'<?php echo htmlentities(_('Pages left to check:')) ?> ') + items_left);
								
								setTimeout(checkLibrary, 0, false, repair);
								
							}, 'json');
						}
					}
				}
				
				
				var pastebinCheck = function() {
					var modalInfoOutput = jQuery("#modal-for-library-check");
					jQuery.post('<?php echo site_url("admin/system/pastebin") ?>', { output: modalInfoOutput.find("#check-display-output").val() }, function(result) {
						if (result.href != "") {
							modalInfoOutput.find(".modal-footer").html('<center><input value="' + result.href + '" style="text-align: center" onclick="select(this);" readonly="readonly" /><br/><?php echo _('Note: This paste expires in 1 hour.'); ?></center>');
						}
					}, 'json');
					return false;
				}
				
				jQuery(document).ready(function(){
					jQuery('#modal-for-library-check').bind('hide', function () {
						stop_check = true;
					});
				});
			</script>
		</div>
	</div>
</div>
