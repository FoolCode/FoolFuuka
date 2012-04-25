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
		$this->viewdata['controller_title'] = '<a href="' . site_url("admin/preferences") . '">' . _("Preferences") . '</a>';
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
			if (isset($item[1]['value']) && is_array($item[1]['value']))
			{
				foreach ($item[1]['value'] as $key => $item2)
				{
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
				$this->db->update('preferences', array('value' => $value),
					array('name' => $item[1]['name']));
			}
			else
			{
				$this->db->insert('preferences',
					array('name' => $item[1]['name'], 'value' => $value));
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


	/**
	 * Allows setting basic variables for theme.
	 * Does not yet allow adding more variables from current theme.
	 * 
	 * @author Woxxy
	 */
	function theme()
	{
		$this->viewdata["function_title"] = _("Theme");

		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);
		
		// build the array for the form
		$form['fs_gen_site_title'] = array(
			'type' => 'input',
			'label' => 'Title',
			'class' => 'span3',
			'placeholder' => 'FoOlFuuka',
			'preferences' => TRUE,
			'validate' => 'trim|max_length[32]',
			'help' => _('Sets the title of your site.')
		);

		$form['separator-2'] = array(
			'type' => 'separator'
		);
		
		$form['fs_theme_active_themes'] = array(
			'type' => 'checkbox_array',
			'label' => _('Active themes'),
			'help' => _('Choose the themes to make available to the users. Admins are able to access any of them even if disabled.'),
			'checkboxes' => array(
				array(
					'type' => 'checkbox',
					'label' => 'Default theme',
					'help' => _('Enable Default theme'),
					'array_key' => 'default',
					'preferences' => TRUE,
					'checked' => FOOL_PREF_THEMES_THEME_DEFAULT_ENABLED
				),
				array(
					'type' => 'checkbox',
					'label' => 'Fuuka theme',
					'help' => _('Enable Fuuka theme'),
					'array_key' => 'fuuka',
					'preferences' => TRUE,
					'checked' => FOOL_PREF_THEMES_THEME_FUUKA_ENABLED
				),
				array(
					'type' => 'checkbox',
					'label' => 'Yotsuba theme',
					'help' => _('Enable Yotsuba theme'),
					'array_key' => 'yotsuba',
					'preferences' => TRUE,
					'checked' => FOOL_PREF_THEMES_THEME_YOTSUBA_ENABLED
				)
			)
		);
		
		$form['fs_theme_default'] = array(
			'type' => 'dropdown',
			'label' => _('Default theme'),
			'help' => _('The theme the users will see as they reach your site.'),
			'options' => array(
				'default' => 'Default',
				'fuuka' => 'Fuuka',
				'Yotsuba' => 'Yotsuba'
			),
			'selected' => 'default'
		);

		$form['fs_theme_google_analytics'] = array(
			'type' => 'input',
			'label' => _('Google Analytics code'),
			'placeholder' => 'UX-XXXXXXX-X',
			'preferences' => TRUE,
			'help' => _("Insert your Google Analytics code to get statistics."),
			'class' => 'span2'
		);

		$form['separator-3'] = array(
			'type' => 'separator'
		);
		
		$form['fs_theme_preheader_text'] = array(
			'type' => 'textarea',
			'label' => _('Pre-Header Text'),
			'preferences' => TRUE,
			'help' => _("This will insert HTML code above before the header navigation block."),
			'class' => 'span5'
		);

		$form['fs_theme_header_text'] = array(
			'type' => 'textarea',
			'label' => _('Header Text'),
			'preferences' => TRUE,
			'help' => _("Inserts the text above in the header, below the nagivation links."),
			'class' => 'span5'
		);

		$form['fs_theme_header_code'] = array(
			'type' => 'textarea',
			'label' => _('Header Code'),
			'preferences' => TRUE,
			'help' => _("This will insert the HTML code above inside the &lt;HEAD&gt;."),
			'class' => 'span5'
		);

		$form['fs_gen_footer_text'] = array(
			'type' => 'textarea',
			'label' => _('Footer Text'),
			'preferences' => TRUE,
			'help' => _('Credits in the footer and similar.'),
			'class' => 'span5'
		);

		$form['fs_theme_footer_code'] = array(
			'type' => 'textarea',
			'label' => _('Footer Code'),
			'preferences' => TRUE,
			'help' => _("This will insert the HTML code above after the &lt;BODY&gt;."),
			'class' => 'span5'
		);

		$form['separator'] = array(
			'type' => 'separator'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'value' => _('Submit'),
			'class' => 'btn btn-primary'
		);

		$form['close'] = array(
			'type' => 'close'
		);

		$this->submit_preferences_auto($form);

		$data['form'] = $form;

		// create a form
		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator",
			$data, TRUE);
		$this->load->view("admin/default", $this->viewdata);
	}


	/**
	 * Code boxes to add the ads' code, supporting top and bottom ads
	 * 
	 * @author Woxxy
	 */
	function advertising()
	{
		$this->viewdata["function_title"] = _("Advertising");

		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);

		$form['fs_ads_top_banner'] = array(
			'type' => 'textarea',
			'label' => _('Top banner'),
			'help' => _('Insert the HTML code provided by your advertiser.'),
			'preferences' => TRUE,
			'validation' => 'trim',
			'class' => 'span5'
		);

		$form['fs_ads_top_banner_active'] = array(
			'type' => 'checkbox',
			'preferences' => TRUE,
			'help' => _('Enable top banner')
		);

		$form['fs_ads_bottom_banner'] = array(
			'type' => 'textarea',
			'label' => _('Bottom banner'),
			'help' => _('Insert the HTML code provided by your advertiser.'),
			'preferences' => TRUE,
			'validation' => 'trim',
			'class' => 'span5'
		);

		$form['fs_ads_bottom_banner_active'] = array(
			'type' => 'checkbox',
			'preferences' => TRUE,
			'help' => _('Enable bottom banner')
		);

		$form['separator'] = array(
			'type' => 'separator'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'value' => _('Submit'),
			'class' => 'btn btn-primary'
		);

		$form['close'] = array(
			'type' => 'close'
		);

		$this->submit_preferences_auto($form);

		$data['form'] = $form;

		// create a form
		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator",
			$data, TRUE);
		$this->load->view("admin/default", $this->viewdata);
	}


	function registration()
	{
		$this->viewdata["function_title"] = _("Registration");

		$form = array();

		$form['open'] = array(
			'type' => 'open'
		);

		$form['fs_reg_disabled'] = array(
			'type' => 'checkbox',
			'preferences' => TRUE,
			'help' => _('Disable New User Registrations')
		);
		$form['fs_reg_email_disabled'] = array(
			'type' => 'checkbox',
			'preferences' => TRUE,
			'help' => _('Disable Email Activation')
		);

		$form['separator'] = array(
			'type' => 'separator'
		);

		$form['paragraph'] = array(
			'type' => 'paragraph',
			'help' => _('In order to use reCAPTCHA&trade; you need to sign up for the service at <a href="http://www.google.com/recaptcha">reCAPTCHA&trade;</a>, which will provide you with a public and a private key.')
		);

		$form['fs_reg_recaptcha_public'] = array(
			'type' => 'input',
			'label' => _('reCaptcha&trade; Public Key'),
			'preferences' => TRUE,
			'help' => _('Insert the public key provided by reCAPTCHA&trade;.'),
			'validation' => 'trim',
			'class' => 'span4'
		);

		$form['fs_reg_recaptcha_secret'] = array(
			'type' => 'input',
			'label' => _('reCaptcha&trade; Secret Key'),
			'preferences' => TRUE,
			'help' => _('Insert the private key provided by reCAPTCHA&trade;.'),
			'validation' => 'trim',
			'class' => 'span4'
		);

		$form['separator-2'] = array(
			'type' => 'separator'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'value' => _('Submit'),
			'class' => 'btn btn-primary'
		);

		$form['close'] = array(
			'type' => 'close'
		);

		$this->submit_preferences_auto($form);

		$data['form'] = $form;

		// create a form
		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator",
			$data, TRUE);
		$this->load->view("admin/default", $this->viewdata);
	}

}