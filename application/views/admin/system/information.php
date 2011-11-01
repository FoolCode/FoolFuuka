<?php if (!defined('BASEPATH'))
	exit('No direct script access allowed'); ?>

<div class="table">
	<?php if (isset($form_title))
		echo '<h3 style="float: left">' . $form_title . '</h3>'; ?>
	<span style="float: right; padding: 5px"><a href="#" class="btn" data-keyboard="true" data-backdrop="true" data-controls-modal="modal-for-information"><?php echo _('Output Server Information'); ?></a></span>
	<hr class="clear"/>
	<div style="margin-right: 10px; padding-bottom: 10px">
		<?php
		// name = non-localized for developers
		// title = localized for users
		$information['server'] = array(
			'name' => 'Server Information',
			'title' => _('Server Information'),
			'data' => array(
				array(
					'name' => 'Web Server Software',
					'title' => _('Web Server Software'),
					'value' => $_SERVER["SERVER_SOFTWARE"],
					'text' => _('The web server that is currently running to serve your content.')
				),
				array(
					'name' => 'PHP Version',
					'title' => _('PHP Version'),
					'value' => phpversion(),
					'text' => _('The version of the currently running PHP parser.'),
					'alert' => array(
						'type' => 'important',
						'title' => _('Old PHP version'),
						'text' => _('To run FoOlSlide, you need at least PHP  version 5.2.0.') . '<p class="vartext">' . _('Suggested') . ': 5.3.0+</p>',
						'if' => version_compare(phpversion(), '5.2.0') < 0
					)
				)
			)
		);

		if (preg_match('/nginx/i', $_SERVER["SERVER_SOFTWARE"]))
		{
			$information['server']['data'][] = array(
				'name' => 'Nginx Upload Size',
				'title' => _('Nginx Upload Size'),
				'value' => _('This value cannot be checked via PHP.'),
				'text' => _('The Nginx web server has its own internal upload limit variable. If you are receiving upload errors, and your PHP configuration looks fine, check this variable in your Nginx configuration file.') . '</p><p class="vartext">' . _('Variable') . ': client_max_body_size (in nginx.conf)</p>'
			);
		}

		$information['software'] = array(
			'name' => 'Software Information',
			'title' => _('Software Information'),
			'data' => array(
				array(
					'name' => 'FoOlSlide Version',
					'title' => _('FoOlSlide Version'),
					'value' => FOOLSLIDE_VERSION,
					'text' => _('The version of FoOlSlide that you are currently running on your server.'),
					'alert' => array(
						'type' => 'success',
						'type_text' => _('New Version Available'),
						'title' => _('New Version Available'),
						'text' => _('Upgrading FoOlSlide ensures that you have the most secure, stable and feature enhanced release.') . '<p class="vartext">' . _('Suggested') . ': ' . get_setting('fs_cron_autoupgrade_version') . '</p>',
						'if' => get_setting('fs_cron_autoupgrade_version') && version_compare(FOOLSLIDE_VERSION, get_setting('fs_cron_autoupgrade_version')) < 0
					)
				),
				array(
					'name' => 'Environment',
					'title' => _('Environment'),
					'value' => ucfirst(ENVIRONMENT),
					'text' => _('The environment FoOlSlide is current running as on the server.'),
				)
			)
		);

		$information['configuration'] = array(
			'name' => 'PHP Configuration',
			'title' => _('PHP Configuration'),
			'text' => _('PHP settings can be easily changed by editing your php.ini file.'),
			'data' => array(
				array(
					'name' => 'php.ini Location',
					'title' => _('php.ini Location'),
					'value' => php_ini_loaded_file(),
					'text' => _('This is the location of the file to edit to change the following variables.')
				),
				array(
					'name' => 'Safe Mode',
					'title' => _('Safe Mode'),
					'value' => (ini_get('safe_mode')) ? _('On') : _('Off'),
					'text' => _('The PHP safe mode is an attempt to solve the shared-server security problems and disables many important PHP functions. This is mainly used by shared hosting services to avoid implementing correct security fixes for shared-server environments.') . '</p><p class="vartext">' . _('Variable') . ': safe_mode</p>',
					'alert' => array(
						'type' => 'important',
						'title' => _('Safe Mode'),
						'text' => _('Safe Mode has been enabled on your PHP installation. This setting has nothing to do with security, and it\'s used by shared server hosts to limit your actions. This variable should be turned off for FoOlSlide to function correctly.') . '<p class="vartext">' . _('Suggested') . ': Off</p>',
						'if' => ini_get('safe_mode')
					)
				),
				array(
					'name' => 'Allow URL fopen',
					'title' => _('Allow URL fopen'),
					'value' => (ini_get('allow_url_fopen')) ? _('On') : _('Off'),
					'text' => _('This function allows PHP to use URL-aware fopen wrappers to access remote files via FTP or HTTP protocol.') . '</p><p class="vartext">' . _('Variable') . ': allow_url_fopen</p>',
					'alert' => array(
						'type' => 'important',
						'title' => _('Disabled'),
						'text' => _('Your PHP configuration currently has URL-aware fopen wrappers disabled. This affects FoOlSlide functions that require accessing remote files in case cURL is not installed on the system. If it is possible, this variable should be enabled with cURL installed as well.') . '<p class="vartext">' . _('Suggested') . ': On</p>',
						'if' => !ini_get('allow_url_fopen')
					)
				),
				array(
					'name' => 'Max Execution Time',
					'title' => _('Max Execution Time'),
					'value' => ini_get('max_execution_time'),
					'text' => _('This is the maximum time in seconds a script is allowed to run before it is terminated by the parser.') . '<p class="vartext">' . _('Variable') . ': max_execution_time</p>',
					'alert' => array(
						'type' => 'notice',
						'title' => _('Low Value'),
						'text' => _('Your current value for max execution time is low. This option affects functions that require large amounts of processing time, such as processing images. If your server doesn\'t have a powerful processor or you\'re processing large amounts of images, this value must be set as high as the total processing time taken to complete the function.') . '<p class="vartext">' . _('Suggested') . ': 120+</p>',
						'if' => intval(ini_get('max_execution_time')) < 110
					)
				),
				array(
					'name' => 'File Uploads',
					'title' => _('File Uploads'),
					'value' => (ini_get('file_uploads')) ? _('Enabled') : _('Disabled'),
					'text' => _('This states whether or not to allow HTTP file uploads.') . '<p class="vartext">' . _('Variable') . ': file_uploads</p>',
					'alert' => array(
						'type' => 'important',
						'title' => _('Disabled'),
						'text' => _('Your PHP configuration currently has file uploads disabled. This variable must be enabled or FoOlSlide will not operate correctly.') . '<p class="vartext">' . _('Suggested') . ': On</p>',
						'if' => !ini_get('file_uploads')
					)
				),
				array(
					'name' => 'Max POST Size',
					'title' => _('Max POST Size'),
					'value' => ini_get('post_max_size'),
					'text' => _('This is max size of post data allowed.') . '<p class="vartext">' . _('Variable') . ': post_max_size</p>',
					'alert' => array(
						'type' => 'notice',
						'title' => _('Low Value'),
						'text' => _('Your current value for POST size is low. This variable should generally be set at a higher value to accomodate and ensure that all of your chapters will be uploaded.') . '<p class="vartext">' . _('Suggested') . ': 16M+</p>',
						'if' => (intval(substr(ini_get('post_max_size'), 0, -1)) < 16)
					)
				),
				array(
					'name' => 'Max Upload Size',
					'title' => _('Max Upload Size'),
					'value' => ini_get('upload_max_filesize'),
					'text' => _('This is the maximum size allowed for an uploaded file.') . '<p class="vartext">' . _('Variable') . ': upload_max_filesize</p>',
					'alert' => array(
						'type' => 'notice',
						'title' => _('Low Value'),
						'text' => _('Your current value for max upload size is low. This variable should generally be set at a higher value to accommodate and allow your largest chapter to be uploaded.') . '<p class="vartext">' . _('Suggested') . ': 16M+</p>',
						'if' => (intval(substr(ini_get('upload_max_filesize'), 0, -1)) < 16)
					)
				),
				array(
					'name' => 'Max File Uploads',
					'title' => _('Max File Uploads'),
					'value' => ini_get('max_file_uploads'),
					'text' => _('This is the maximum number of files allowed to be uploaded simultaneously.') . '<p class="vartext">' . _('Variable') . ': max_file_uploads</p>',
					'alert' => array(
						'type' => 'notice',
						'title' => _('Low Value'),
						'text' => _('Your current value for max file uploads is low. This variable should generally be set at a higher value than the number of pages your chapters may have.') . '<p class="vartext">' . _('Suggested') . ': 54+</p>',
						'if' => (intval(ini_get('max_file_uploads')) < 54)
					)
				)
			)
		);


		$information['extensions'] = array(
			'name' => 'Extensions',
			'title' => _('Extensions'),
			'data' => array(
				array(
					'name' => 'cURL',
					'title' => 'cURL',
					'value' => (extension_loaded('curl')) ? _('Installed') : _('Not Installed'),
					'text' => _('This is a library used to communicate with different types of servers with many types of protocols.')
				),array(
					'name' => 'GD2',
					'title' => 'GD2',
					'value' => (extension_loaded('gd')) ? _('Installed') : _('Missing'),
					'text' => _('This is a library used to dynamically create images and thumbnails.')
				),
				array(
					'name' => 'ImageMagick',
					'title' => 'ImageMagick',
					'value' => (find_imagick()) ? _('Installed') : _('Not Installed'),
					'text' => _('This is a library used to dynamically create, edit, compose or convert images.') . '<p class="vartext">' . _('Optional') . '</p>'
				)
			)
		);

		// Output Tables
		foreach ($information as $key => $item)
		{
			echo '<h4>' . $item['title'] . '</h4>';
			if (isset($item['text']))
				echo '<p>' . $item['text'] . '</p>';
			echo '<table class="zebra-striped fixed-table"><tbody>';
			foreach ($item['data'] as $subkey => $subitem)
			{
				$tooltip = (isset($subitem['text']) && $subitem['text'] != "") ? '<a rel="popover-right" href="#" data-content="' . htmlspecialchars($subitem['text']) . '" data-original-title="' . htmlspecialchars($subitem['title']) . '"><img src="' . icons(219, 16) . '" class="icon icon-small"></a>' : '';
				$tooltip2 = (isset($subitem['alert']) && $subitem['alert']['text'] != "" && $subitem['alert']['if']) ? '<span class="label ' . $subitem['alert']['type'] . '">' . _(isset($subitem['alert']['type_text'])?$subitem['alert']['type_text']:$subitem['alert']['type']) . '</span><a rel="popover-right" href="#" data-content="' . htmlspecialchars($subitem['alert']['text']) . '" data-original-title="' . htmlspecialchars($subitem['alert']['title']) . '"><img src="' . icons(388, 16) . '" class="icon icon-small"></a>' : '';
				echo '<tr><td>' . $subitem['title'] . ' ' . $tooltip . '</td><td>' . $subitem['value'] . ' ' . $tooltip2 . '</td></tr>';
			}
			echo '</tbody></table>';
		}
		?>

		<?php echo _('If you are asked to provide an output of your server information, please click the "Output Server Information" button at the top right and provide it to us via <a href="http://pastebin.com">Pastebin</a> or some similar service.'); ?>
	</div>
</div>

<div id="modal-for-information" class="modal hide fade" style="display: none">
	<div class="modal-header">
		<a class="close" href="#">&times;</a>
		<h3><?php echo _('System Information'); ?></h3>
	</div>
	<div class="modal-body" style="text-align: center">
		<textarea id="server-information-output" style="min-height: 300px; font-family: Consolas,Monaco,Lucida Console,Liberation Mono,DejaVu Sans Mono,Bitstream Vera Sans Mono,Courier New, monospace !important" readonly="readonly"><?php
		foreach ($information as $key => $item)
		{
			echo $item['name'] . "\n";
			echo "------------------------------\n";
			foreach ($item['data'] as $subkey => $subitem)
			{
				echo $subitem['name'] . ' = ' . $subitem['value'] . "\n";
			}
			echo "\n";
		}
		echo 'Report Generated: ' . date(DATE_RFC822) . "\n";
		?></textarea>
	</div>
	<div class="modal-footer">
		<?php
		if (function_exists('curl_init'))
		{
			echo '<center><a class="btn" style="float: none" href="#" onclick="return pasteSystemInfo();">' . _('Pastebin It!') . '</a></center>';
		}
		?>
	</div>
</div>

<script type="text/javascript">
	
	var pasteSystemInfo = function() {
		var modalInfoOutput = jQuery("#modal-for-information");
		jQuery.post('<?php echo site_url("admin/system/pastebin") ?>', { output: modalInfoOutput.find("#server-information-output").val() }, function(result) {
			if (result.href != "") {
				modalInfoOutput.find(".modal-footer").html('<center><input value="' + result.href + '" style="text-align: center" onclick="select(this);" readonly="readonly" /><br/><?php echo _('Note: This paste expires in 1 hour.'); ?></center>');
			}
		}, 'json');
	}
			
	jQuery(document).ready(function() {
		var modalInfoContainer = jQuery("#modal-for-information").find("#server-information-output");
		modalInfoContainer.click(function() {
			modalInfoContainer.select();
			// Chrome Fix
			modalInfoContainer.mouseup(function() { modalInfoContainer.unbind('mouseup'); return false; });
		});
		return false;
	});
</script>