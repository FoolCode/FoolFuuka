<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Preferences extends Admin_Controller
{
	function __construct()
	{
		parent::__construct();

		// preferences are settable only by admins!
		$this->tank_auth->is_admin() or redirect('admin');

		// set controller title
		$this->viewdata['controller_title'] = '<a href="'.site_url("admin/preferences").'">' . _("Preferences") . '</a>';
	}


	/*
	 * Just redirects to general
	 * 
	 * @author Woxxy
	 */
	function index()
	{
		redirect('/admin/preferences/general');
	}


	/*
	 * _submit is a private function that submits to the "preferences" table.
	 * entries that don't exist are created. the preferences table could get very large
	 * but it's not really an issue as long as the variables are kept all different.
	 * 
	 * @author Woxxy
	 */
	function _submit($post, $form)
	{
		// Support Checkbox Listing
		$former = array();
		foreach ($form as $key => $item)
		{
			if (isset($item[1]['value']) && is_array($item[1]['value'])) {
				foreach ($item[1]['value'] as $key => $item2) {
					$former[] = array('1', $item2);
				}
			}
			else
				$former[] = $form[$key];
		}

		foreach ($former as $key => $item)
		{
			if (isset($post[$item[1]['name']]))
				$value = $post[$item[1]['name']];
			else
				$value = NULL;

			$this->db->from('preferences');
			$this->db->where(array('name' => $item[1]['name']));
			if ($this->db->count_all_results() == 1)
			{
				$this->db->update('preferences', array('value' => $value), array('name' => $item[1]['name']));
			}
			else
			{
				$this->db->insert('preferences', array('name' => $item[1]['name'], 'value' => $value));
			}
		}

		$CI = & get_instance();
		$array = $CI->db->get('preferences')->result_array();
		$result = array();
		foreach ($array as $item)
		{
			$result[$item['name']] = $item['value'];
		}
		$CI->fs_options = $result;
		flash_notice('notice', _('Updated settings.'));
	}


	/*
	 * Generic info influcencing all of FoOlSlide
	 * 
	 * @author Woxxy
	 */
	function general()
	{
		$this->viewdata["function_title"] = _("General");

		$form = array();

		// build the array for the form
		$form[] = array(
			_('Slide Title'),
			array(
				'type' => 'input',
				'name' => 'fs_gen_site_title',
				'id' => 'site_title',
				'maxlength' => '200',
				'placeholder' => _('FoOlSlide'),
				'preferences' => 'fs_gen',
				'help' => _('Sets the title of your FoOlSlide. This appears in the title of every page.')
			)
		);

		$form[] = array(
			_('Back URL'),
			array(
				'type' => 'input',
				'name' => 'fs_gen_back_url',
				'id' => 'back_url',
				'maxlength' => '200',
				'placeholder' => 'http://',
				'preferences' => 'fs_gen',
				'help' => _('A small URL linked to another page. This is located next to the title at the top of every page.')
			)
		);

		$form[] = array(
			_('Default Team'),
			array(
				'type' => 'input',
				'name' => 'fs_gen_default_team',
				'id' => 'default_team',
				'maxlength' => '200',
				'placeholder' => 'Anonymous',
				'preferences' => 'fs_gen',
				'help' => _('Sets the default team used for widgets and releases.')
			)
		);


		$form[] = array(
			_('Default release language'),
			array(
				'type' => 'language',
				'name' => 'fs_gen_default_lang',
				'preferences' => 'fs_gen',
				'help' => _('Changes the default release language for all future releases.')
			)
		);

		$form[] = array(
			_('Default Software Language'),
			array(
				'type' => 'dropdowner',
				'name' => 'fs_gen_lang',
				'values' => array('' => 'English', 'cs_CZ.utf8' => 'Czech', 'fr_FR.utf8' => 'French', 'de_DE.utf8' => 'German', 'it_IT.utf8' => 'Italian', 'pl_PL.utf8' => 'Polish', 'pt_PT.utf8' => 'Portuguese', 'pt_BR.utf8' => 'Portuguese (Brazil)', 'ru_RU.utf8' => 'Russian', 'es_ES.utf8' => 'Spanish', 'tr_TR.utf8' => 'Turkish'),
				'preferences' => 'fs_gen',
				'help' => _('Changes the software language of FoOlSlide. Please contribute by translating this software on our <a href="https://www.transifex.net/projects/p/foolslide/resource/defaultpot/">Transifex project</a>.')
			)
		);

		if ($post = $this->input->post())
		{
			$this->_submit($post, $form);
			redirect('admin/preferences/general');
		}

		// create a form
		$table = tabler($form, FALSE);
		$data['form_title'] = _('General');
		$data['table'] = $table;

		// print out
		$this->viewdata["main_content_view"] = $this->load->view("admin/preferences/general.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	/*
	 * Allows setting basic variables for theme.
	 * Does not yet allow adding more variables from current theme.
	 * 
	 * @author Woxxy
	 */
	function theme()
	{
		$this->viewdata["function_title"] = _("Theme");

		$form = array();

		$form[] = array(
			_('Select Theme'),
			array(
				'type' => 'themes',
				'name' => 'fs_theme_dir',
				'placeholder' => '',
				'preferences' => 'fs_gen'
			)
		);
		
		$form[] = array(
			_('Pre-Header Text'),
			array(
				'type' => 'textarea',
				'name' => 'fs_theme_preheader_text',
				'placeholder' => '',
				'preferences' => 'fs_gen',
				'help' => _("This will insert HTML code above before the header navigation block.")
			)
		);
		
		$form[] = array(
			_('Header Text'),
			array(
				'type' => 'textarea',
				'name' => 'fs_theme_header_text',
				'placeholder' => '',
				'preferences' => 'fs_gen',
				'help' => _("Inserts the text above in the header where the nagivation linkes are located.")
			)
		);

		$form[] = array(
			_('Header Code'),
			array(
				'type' => 'textarea',
				'name' => 'fs_theme_header_code',
				'placeholder' => '',
				'preferences' => 'fs_gen',
				'help' => _("This will insert the HTML code above inside the &lt;HEAD&gt;.")
			)
		);

		$form[] = array(
			_('Footer Text'),
			array(
				'type' => 'textarea',
				'name' => 'fs_gen_footer_text',
				'placeholder' => '',
				'preferences' => 'fs_gen',
				'help' => _('Inserts the text above in the footer such as disclaimers. (Note: If the content uploaded does not belong to you, do not write things like "All Rights Reserived&copy;" above. However, if you\'re releasing your own works, please consider using <a href="http://creativecommons.org/">Creative Commons Licenses</a> to protect them.)')
			)
		);
		
		$form[] = array(
			_('Footer Code'),
			array(
				'type' => 'textarea',
				'name' => 'fs_theme_footer_code',
				'placeholder' => '',
				'preferences' => 'fs_gen',
				'help' => _("This will insert the HTML code above after the &lt;BODY&gt;.")
			)
		);

		if ($post = $this->input->post())
		{
			$this->_submit($post, $form);
			redirect('admin/preferences/theme');
		}

		// create the form
		$table = tabler($form, FALSE);
		$data['form_title'] = _('Theme');
		$data['table'] = $table;

		// print out
		$this->viewdata["main_content_view"] = $this->load->view("admin/preferences/general.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	/*
	 * Code boxes to add the ads' code, supporting top and bottom ads
	 * 
	 * @author Woxxy
	 */
	function advertising()
	{
		$this->viewdata["function_title"] = _("Advertising");

		$form = array();

		$form[] = array(
			_('Top banner'),
			array(
				'type' => 'textarea',
				'name' => 'fs_ads_top_banner',
				'help' => _('Insert the HTML code provided by your advertiser above.'),
				'preferences' => 'fs_ads'
			)
		);

		$form[] = array(
			_('Top Banner Options'),
			array(
				'type' => 'checkbox',
				'name' => 'fs_ads_top_options',
				'value' => array(
					array(
						'type' => 'checkbox',
						'name' => 'fs_ads_top_banner_active',
						'placeholder' => '',
						'preferences' => 'fs_ads',
						'text' => _('Enable')
					),
					array(
						'name' => 'fs_ads_top_banner_reload',
						'placeholder' => '',
						'preferences' => 'fs_ads',
						'text' => _('Reload on Every Pageview')
					)
				),
				'help' => _('')
			)
		);
		
		$form[] = array(
			_('Bottom banner'),
			array(
				'type' => 'textarea',
				'name' => 'fs_ads_bottom_banner',
				'help' => _('Insert the HTML code provided by your advertiser above.'),
				'preferences' => 'fs_ads'
			)
		);

		$form[] = array(
			_('Bottom Banner Options'),
			array(
				'type' => 'checkbox',
				'name' => 'fs_ads_bottom_options',
				'value' => array(
					array(
						'type' => 'checkbox',
						'name' => 'fs_ads_bottom_banner_active',
						'placeholder' => '',
						'preferences' => 'fs_ads',
						'text' => _('Enable')
					),
					array(
						'name' => 'fs_ads_bottom_banner_reload',
						'placeholder' => '',
						'preferences' => 'fs_ads',
						'text' => _('Reload on Every Pageview')
					)
					
				),
				'help' => _('')
			)
		);

		if ($post = $this->input->post())
		{
			$this->_submit($post, $form);

			// this code is necessary to keep the ad well centered inside iframes
			$ad_before = '<!DOCTYPE html>
						<html>
						  <head>
							<title>FoOlSlide ads</title>
							<style>body{margin:0; padding:0; overflow:hidden;}</style>
							<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
						  </head>
						  <body>';
			$ad_after = '</body>
						</html>';

			// available ads
			$ads = array('fs_ads_top_banner' => 'ads_top.html', 'fs_ads_bottom_banner' => 'ads_bottom.html');

			// write an HTML file, so calling it will use less processor power than calling the database via Codeigniter
			// this recreates the files every time one saves
			foreach ($ads as $ad => $adfile)
			{
				if (!write_file('./content/ads/' . $adfile, $ad_before . $this->input->post($ad) . $ad_after))
				{
					log_message('error', 'preferences.php/advertising: couldn\'t update HTML files');
					flash_notice('error', _('Couldn\'t save the advertising code in the HTML'));
				}
			}
			
			redirect('admin/preferences/advertising');
		}

		// create the form
		$table = tabler($form, FALSE);
		$data['form_title'] = _('Advertising');
		$data['table'] = $table;

		// print out
		$this->viewdata["main_content_view"] = $this->load->view("admin/preferences/general.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function registration()
	{
		$this->viewdata["function_title"] = _("Registration");

		$form = array();

		$form[] = array(
			_('Settings'),
			array(
				'type' => 'checkbox',
				'name' => 'fs_reg_options',
				'value' => array(
					array(
						'name' => 'fs_reg_disabled',
						'id' => 'disable_reg',
						'preferences' => 'fs_reg',
						'text' => _('Disable New User Registrations')
					),
					array(
						'name' => 'fs_reg_email_disabled',
						'id' => 'disable_reg',
						'preferences' => 'fs_reg',
						'text' => _('Disable Email Activation')
					)
				),
				'help' => _('Modify the settings for the registration system.')
			)
		);

		$form[] = array(
			_('reCaptcha&trade; Public Key'),
			array(
				'type' => 'input',
				'name' => 'fs_reg_recaptcha_public',
				'id' => 'captcha_public',
				'maxlength' => '200',
				'preferences' => 'fs_reg',
				'help' => _('Insert the public key provided by reCAPTCHA&trade;.')
			)
		);

		$form[] = array(
			_('reCaptcha&trade; Secret Key'),
			array(
				'type' => 'input',
				'name' => 'fs_reg_recaptcha_secret',
				'preferences' => 'fs_reg',
				'help' => _('Insert the private key provided by reCAPTCHA&trade;.')
			)
		);

		if ($post = $this->input->post())
		{
			$this->_submit($post, $form);
			redirect('admin/preferences/registration');
		}

		// prepare form
		$table = tabler($form, FALSE);
		$data['form_title'] = _('Registration');
		$data['form_description'] = _('In order to use reCAPTCHA&trade; you need to sign up for the service at <a href="http://www.google.com/recaptcha">reCAPTCHA&trade;</a>, which will provide you with a public and a private key.');
		$data['table'] = $table;

		// print out
		$this->viewdata["main_content_view"] = $this->load->view("admin/preferences/general.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	/*
	 * Reader configuration
	 * 
	 * @author Woxxy
	 */
	function reader()
	{
		$this->viewdata["function_title"] = _("Reading");

		$form = array();

		$form[] = array(
			_('Settings'),
			array(
				'type' => 'checkbox',
				'name' => 'fs_dl_enabled',
				'id' => 'enable_dl',
				'preferences' => 'fs_dl',
				'text' => _('Enable Direct Downloads'),
				'help' => _('Note: Direct downloads usually increase bandwidth usage by one-third. The issue is whether you have enough space to keep both images and ZIP archives. FoOlSlide tries to avoid this problem by using on-the-fly ZIP compression and caching.')
			)
		);

		$form[] = array(
			_('Maximum Direct Download Cache in Megabyte'),
			array(
				'type' => 'input',
				'name' => 'fs_dl_archive_max',
				'id' => 'max_dl',
				'preferences' => 'fs_dl',
				'help' => _('Once this limit has been reached, ZIP archives that have not been downloaded recently will be removed. If a very large number is set, the ZIP archives will be cached indefinitely.')
			)
		);

		if ($post = $this->input->post())
		{
			$this->_submit($post, $form);
			redirect('admin/preferences/reader');
		}

		// create form
		$table = tabler($form, FALSE);
		$data['form_title'] = _('Reading');
		$data['table'] = $table;

		// print out
		$this->viewdata["main_content_view"] = $this->load->view("admin/preferences/general.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}
	
	
	
	/*
	 * Generic info influcencing all of FoOlSlide
	 * 
	 * @author Woxxy
	 */
	function slideshow()
	{
		$this->viewdata["function_title"] = _("Slideshow");


		$form = array();

		// build the array for the form
		$form[] = array(
			_('1st Image URL'),
			array(
				'type' => 'input',
				'name' => 'fs_slsh_src_1',
				'placeholder' => _('Activate by inserting an URL'),
				'preferences' => 'fs_gen',
				'help' => _('Image URL (don\'t forget "http://"). Suggested size: 690px width, 280px height')
			)
		);
		
		$form[] = array(
			_('1st URL'),
			array(
				'type' => 'input',
				'name' => 'fs_slsh_url_1',
				'placeholder' => _('Not required'),
				'preferences' => 'fs_gen',
				'help' => _('Link URL (don\'t forget "http://")')
			)
		);

		$form[] = array(
			_('1st Display text'),
			array(
				'type' => 'textarea',
				'name' => 'fs_slsh_text_1',
				'preferences' => 'fs_gen',
				'help' => _('Text that appears on bottom of the image. HTML accepted')
			)
		);
		
		$form[] = array(
			_('2nd Image URL'),
			array(
				'type' => 'input',
				'name' => 'fs_slsh_src_2',
				'placeholder' => _('Activate by inserting an URL'),
				'preferences' => 'fs_gen',
				'help' => _('Image URL (don\'t forget "http://"). Suggested size: 690px width, 280px height')
			)
		);
		
		$form[] = array(
			_('2nd URL'),
			array(
				'type' => 'input',
				'name' => 'fs_slsh_url_2',
				'placeholder' => _('Not required'),
				'preferences' => 'fs_gen',
				'help' => _('Link URL (don\'t forget "http://")')
			)
		);

		$form[] = array(
			_('2nd Display text'),
			array(
				'type' => 'textarea',
				'name' => 'fs_slsh_text_2',
				'preferences' => 'fs_gen',
				'help' => _('Text that appears on bottom of the image. HTML accepted')
			)
		);
		
		$form[] = array(
			_('3rd Image URL'),
			array(
				'type' => 'input',
				'name' => 'fs_slsh_src_3',
				'placeholder' => _('Activate by inserting an URL'),
				'preferences' => 'fs_gen',
				'help' => _('Image URL (don\'t forget "http://"). Suggested size: 690px width, 280px height')
			)
		);
		
		$form[] = array(
			_('3rd URL'),
			array(
				'type' => 'input',
				'name' => 'fs_slsh_url_3',
				'placeholder' => _('Not required'),
				'preferences' => 'fs_gen',
				'help' => _('Link URL (don\'t forget "http://")')
			)
		);

		$form[] = array(
			_('3rd Display text'),
			array(
				'type' => 'textarea',
				'name' => 'fs_slsh_text_3',
				'preferences' => 'fs_gen',
				'help' => _('Text that appears on bottom of the image. HTML accepted')
			)
		);
		
		$form[] = array(
			_('4th Image URL'),
			array(
				'type' => 'input',
				'name' => 'fs_slsh_src_4',
				'placeholder' => _('Activate by inserting an URL'),
				'preferences' => 'fs_gen',
				'help' => _('Image URL (don\'t forget "http://"). Suggested size: 690px width, 280px height')
			)
		);
		
		$form[] = array(
			_('4th URL'),
			array(
				'type' => 'input',
				'name' => 'fs_slsh_url_4',
				'placeholder' => _('Not required'),
				'preferences' => 'fs_gen',
				'help' => _('Link URL (don\'t forget "http://")')
			)
		);

		$form[] = array(
			_('4th Display text'),
			array(
				'type' => 'textarea',
				'name' => 'fs_slsh_text_4',
				'preferences' => 'fs_gen',
				'help' => _('Text that appears on bottom of the image. HTML accepted')
			)
		);

		if ($post = $this->input->post())
		{
			$this->_submit($post, $form);
			redirect('admin/preferences/slideshow');
		}

		// create a form
		$table = tabler($form, FALSE);
		$data['table'] = $table;

		// print out
		$this->viewdata["main_content_view"] = $this->load->view("admin/preferences/general.php", $data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


}