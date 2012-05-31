<?php if (!defined('BASEPATH'))
	exit('No direct script access allowed'); ?>

<div class="table">
	<div style="margin-right: 10px; padding-bottom: 10px">
		<?php
		// name = non-localized for developers
		// title = localized for users
		$information['server'] = array(
			'name' => 'Server Information',
			'title' => __('Server Information'),
			'data' => array(
				array(
					'name' => 'Web Server Software',
					'title' => __('Web Server Software'),
					'value' => $_SERVER["SERVER_SOFTWARE"],
					'text' => __('The web server that is currently running to serve your content.')
				),
				array(
					'name' => 'PHP Version',
					'title' => __('PHP Version'),
					'value' => phpversion(),
					'text' => __('The version of the currently running PHP parser.'),
					'alert' => array(
						'type' => 'important',
						'title' => __('Old PHP version'),
						'text' => __('To run {{FOOL_NAME}}, you need at least PHP  version 5.2.0.') . '<p class="vartext">' . __('Suggested') . ': 5.3.0+</p>',
						'if' => version_compare(phpversion(), '5.2.0') < 0
					)
				)
			)
		);

		if (preg_match('/nginx/i', $_SERVER["SERVER_SOFTWARE"]))
		{
			$information['server']['data'][] = array(
				'name' => 'Nginx Upload Size',
				'title' => __('Nginx Upload Size'),
				'value' => __('This value cannot be checked via PHP.'),
				'text' => __('The Nginx web server has its own internal upload limit variable. If you are receiving upload errors, and your PHP configuration looks fine, check this variable in your Nginx configuration file.') . '</p><p class="vartext">' . __('Variable') . ': client_max_body_size (in nginx.conf)</p>'
			);
		}

		$information['software'] = array(
			'name' => 'Software Information',
			'title' => __('Software Information'),
			'data' => array(
				array(
					'name' => 'FoOlFuuka Version',
					'title' => __('{{FOOL_NAME}} Version'),
					'value' => FOOL_VERSION,
					'text' => __('The version of {{FOOL_NAME}} that you are currently running on your server.'),
					'alert' => array(
						'type' => 'success',
						'type_text' => __('New Version Available'),
						'title' => __('New Version Available'),
						'text' => __('Upgrading {{FOOL_NAME}} ensures that you have the most secure, stable and feature enhanced release.') . '<p class="vartext">' . __('Suggested') . ': ' . get_setting('fs_cron_autoupgrade_version') . '</p>',
						'if' => get_setting('fs_cron_autoupgrade_version') && version_compare(FOOL_VERSION, get_setting('fs_cron_autoupgrade_version')) < 0
					)
				),
				array(
					'name' => 'Environment',
					'title' => __('Environment'),
					'value' => ucfirst(ENVIRONMENT),
					'text' => __('The environment {{FOOL_NAME}} is currently running as on the server.'),
				)
			)
		);

		$information['configuration'] = array(
			'name' => 'PHP Configuration',
			'title' => __('PHP Configuration'),
			'text' => __('PHP settings can be easily changed by editing your php.ini file.'),
			'data' => array(
				array(
					'name' => 'php.ini Location',
					'title' => __('php.ini Location'),
					'value' => php_ini_loaded_file(),
					'text' => __('This is the location of the file to edit to change the following variables.')
				),
				array(
					'name' => 'Safe Mode',
					'title' => __('Safe Mode'),
					'value' => (ini_get('safe_mode')) ? __('On') : __('Off'),
					'text' => __('The PHP safe mode is an attempt to solve the shared-server security problems and disables many important PHP functions. This is mainly used by shared hosting services to avoid implementing correct security fixes for shared-server environments.') . '</p><p class="vartext">' . __('Variable') . ': safe_mode</p>',
					'alert' => array(
						'type' => 'important',
						'title' => __('Safe Mode'),
						'text' => __('Safe Mode has been enabled on your PHP installation. This setting has nothing to do with security, and it\'s used by shared server hosts to limit your actions. This variable should be turned off for {{FOOL_NAME}} to function correctly.') . '<p class="vartext">' . __('Suggested') . ': Off</p>',
						'if' => ini_get('safe_mode')
					)
				),
				array(
					'name' => 'Allow URL fopen',
					'title' => __('Allow URL fopen'),
					'value' => (ini_get('allow_url_fopen')) ? __('On') : __('Off'),
					'text' => __('This function allows PHP to use URL-aware fopen wrappers to access remote files via FTP or HTTP protocol.') . '</p><p class="vartext">' . __('Variable') . ': allow_url_fopen</p>',
					'alert' => array(
						'type' => 'important',
						'title' => __('Disabled'),
						'text' => __('Your PHP configuration currently has URL-aware fopen wrappers disabled. This affects {{FOOL_NAME}} functions that require accessing remote files in case cURL is not installed on the system. If it is possible, this variable should be enabled with cURL installed as well.') . '<p class="vartext">' . __('Suggested') . ': On</p>',
						'if' => !ini_get('allow_url_fopen')
					)
				),
				array(
					'name' => 'Max Execution Time',
					'title' => __('Max Execution Time'),
					'value' => ini_get('max_execution_time'),
					'text' => __('This is the maximum time in seconds a script is allowed to run before it is terminated by the parser.') . '<p class="vartext">' . __('Variable') . ': max_execution_time</p>',
					'alert' => array(
						'type' => 'notice',
						'title' => __('Low Value'),
						'text' => __('Your current value for max execution time is low. This option affects functions that require large amounts of processing time, such as processing images. If your server doesn\'t have a powerful processor or you\'re processing large amounts of images, this value must be set as high as the total processing time taken to complete the function.') . '<p class="vartext">' . __('Suggested') . ': 120+</p>',
						'if' => intval(ini_get('max_execution_time')) < 110
					)
				),
				array(
					'name' => 'File Uploads',
					'title' => __('File Uploads'),
					'value' => (ini_get('file_uploads')) ? __('Enabled') : __('Disabled'),
					'text' => __('This states whether or not to allow HTTP file uploads.') . '<p class="vartext">' . __('Variable') . ': file_uploads</p>',
					'alert' => array(
						'type' => 'important',
						'title' => __('Disabled'),
						'text' => __('Your PHP configuration currently has file uploads disabled. This variable must be enabled or {{FOOL_NAME}} will not operate correctly.') . '<p class="vartext">' . __('Suggested') . ': On</p>',
						'if' => !ini_get('file_uploads')
					)
				),
				array(
					'name' => 'Max POST Size',
					'title' => __('Max POST Size'),
					'value' => ini_get('post_max_size'),
					'text' => __('This is max size of post data allowed.') . '<p class="vartext">' . __('Variable') . ': post_max_size</p>',
					'alert' => array(
						'type' => 'notice',
						'title' => __('Low Value'),
						'text' => __('Your current value for POST size is low. This variable should generally be set at a higher value to accomodate and ensure that all of your chapters will be uploaded.') . '<p class="vartext">' . __('Suggested') . ': 16M+</p>',
						'if' => (intval(substr(ini_get('post_max_size'), 0, -1)) < 16)
					)
				),
				array(
					'name' => 'Max Upload Size',
					'title' => __('Max Upload Size'),
					'value' => ini_get('upload_max_filesize'),
					'text' => __('This is the maximum size allowed for an uploaded file.') . '<p class="vartext">' . __('Variable') . ': upload_max_filesize</p>',
					'alert' => array(
						'type' => 'notice',
						'title' => __('Low Value'),
						'text' => __('Your current value for max upload size is low. This variable should generally be set at a higher value to accommodate and allow your largest chapter to be uploaded.') . '<p class="vartext">' . __('Suggested') . ': 16M+</p>',
						'if' => (intval(substr(ini_get('upload_max_filesize'), 0, -1)) < 16)
					)
				),
				array(
					'name' => 'Max File Uploads',
					'title' => __('Max File Uploads'),
					'value' => ini_get('max_file_uploads'),
					'text' => __('This is the maximum number of files allowed to be uploaded simultaneously.') . '<p class="vartext">' . __('Variable') . ': max_file_uploads</p>',
					'alert' => array(
						'type' => 'notice',
						'title' => __('Low Value'),
						'text' => __('Your current value for max file uploads is low. This variable should generally be set at a higher value than the number of pages your chapters may have.') . '<p class="vartext">' . __('Suggested') . ': 54+</p>',
						'if' => (intval(ini_get('max_file_uploads')) < 54)
					)
				)
			)
		);


		$information['extensions'] = array(
			'name' => 'Extensions',
			'title' => __('Extensions'),
			'data' => array(
				array(
					'name' => 'cURL',
					'title' => 'cURL',
					'value' => (extension_loaded('curl')) ? __('Installed') : __('Not Installed'),
					'text' => __('This is a library used to communicate with different types of servers with many types of protocols.')
				),array(
					'name' => 'GD2',
					'title' => 'GD2',
					'value' => (extension_loaded('gd')) ? __('Installed') : __('Missing'),
					'text' => __('This is a library used to dynamically create images and thumbnails.')
				),
				array(
					'name' => 'ImageMagick',
					'title' => 'ImageMagick',
					'value' => (locate_imagemagick()) ? __('Installed') : __('Not Installed'),
					'text' => __('This is a library used to dynamically create, edit, compose or convert images.') . '<p class="vartext">' . __('Optional') . '</p>'
				)
			)
		);

		// Output Tables
		foreach ($information as $key => $item)
		{
			echo '<h4>' . $item['title'] . '</h4>';
			if (isset($item['text']))
				echo '<p>' . $item['text'] . '</p>';
			echo '<table class="table table-striped fixed-table"><tbody>';
			foreach ($item['data'] as $subkey => $subitem)
			{
				$tooltip = (isset($subitem['text']) && $subitem['text'] != "") ? '<a rel="popover-right" href="#" data-content="' . htmlspecialchars($subitem['text']) . '" data-original-title="' . htmlspecialchars($subitem['title']) . '"><i class="icon-info-sign"></i></a>' : '';
				$tooltip2 = (isset($subitem['alert']) && $subitem['alert']['text'] != "" && $subitem['alert']['if']) ? '<span class="label label-' . $subitem['alert']['type'] . '">' . __(isset($subitem['alert']['type_text'])?$subitem['alert']['type_text']:$subitem['alert']['type']) . '</span> <a rel="popover-right" href="#" data-content="' . htmlspecialchars($subitem['alert']['text']) . '" data-original-title="' . htmlspecialchars($subitem['alert']['title']) . '"><i class="icon-info-sign"></i></a>' : '';
				echo '<tr>
					<td style="width:50%;">' . $subitem['title'] . ' ' . $tooltip . '</td>
					<td style="width:50%;">' . $subitem['value'] . ' ' . $tooltip2 . '</td></tr>';
			}
			echo '</tbody></table>';
		}
		?>

	</div>
</div>
