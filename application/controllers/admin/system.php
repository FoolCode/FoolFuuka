<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class System extends Admin_Controller
{


	function __construct()
	{
		parent::__construct();

		// only admins should do this
		$this->tank_auth->is_admin() or redirect('admin');

		// we need the upgrade module's functions
		$this->load->model('upgrade_model', 'upgrade');

		// page title
		$this->viewdata['controller_title'] = '<a href="' . site_url("admin/system") . '">' . __("System") . '</a>';
	}

	/*
	 * A page telling if there's an ugrade available
	 *
	 * @author Woxxy
	 */


	function index()
	{
		redirect('/admin/system/information');
	}


	function information()
	{
		$this->viewdata["function_title"] = __("Information");

		// get current version from database
		$data["current_version"] = FOOL_VERSION;
		$data["form_title"] = __("Information");

		$this->viewdata["main_content_view"] = $this->load->view("admin/system/information",
			$data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}


	function preferences()
	{
		$this->viewdata["function_title"] = __("Preferences");

		$form = array();

		if (locate_imagemagick())
		{
			$imagick_status = '<span class="label label-success">' . __('Found and Working') . '</span>';
		}
		else
		{
			// @todo update the imagick statuses to match bootstrap 2.0
			if (!$this->ff_imagemagick->exec)
				$imagick_status = '<span class="label label-important">' . __('Not Available') . '</span><a rel="popover-right" href="#" data-content="' . htmlspecialchars(__('You must have Safe Mode turned off and the exec() function enabled to allow ImageMagick to process your images. Please check the information panel for more details.')) . '" data-original-title="' . htmlspecialchars(__('Disabled Functions')) . '"><i class="icon icon-info-sign"></i></a>';
			else if ($this->ff_imagemagick->path !== '')
				$imagick_status = '<span class="label label-important">' . __('Not Found') . '</span><a rel="popover-right" href="#" data-content="' . htmlspecialchars(__('You must provide the correct path to the "convert" binary on your system. This is typically located under /usr/bin (Linux), /opt/local/bin (Mac OSX) or the installation directory (Windows).')) . '" data-original-title="' . htmlspecialchars(__('Disabled Functions')) . '"><i class="icon icon-info-sign"></i></a>';
			else if (!$this->ff_imagemagick->available)
				$imagick_status = '<span class="label label-important">' . __('Not Working') . '</span><a rel="popover-right" href="#" data-content="' . htmlspecialchars(sprintf(__('There has been an error encountered when testing your ImageMagick installation. To manually check for errors, access your server via shell or command line and type: %s'),
							'<br/><code>' . $this->ff_imagemagick->path . ' -version</code>')) . '" data-original-title="' . htmlspecialchars(__('Disabled Functions')) . '"><i class="icon icon-info-sign"></i></a>';
		}

		$form['open'] = array(
			'type' => 'open'
		);

		$form['ff_path_imagemagick_bin'] = array(
			'type' => 'input',
			'label' => __('Path to ImageMagick') . ' ' . $imagick_status,
			'placeholder' => '/usr/bin',
			'preferences' => 'fs_gen',
			'help' => __('The location of your ImageMagick "convert" executable')
		);

		$form['separator-2'] = array(
			'type' => 'separator'
		);

		$form['submit'] = array(
			'type' => 'submit',
			'value' => __('Submit'),
			'class' => 'btn btn-primary'
		);

		$form['close'] = array(
			'type' => 'close'
		);

		$data["form_title"] = __("Preferences");

		$this->preferences->submit_auto($form);

		$data['form'] = $form;

		// create a form
		$this->viewdata["main_content_view"] = $this->load->view("admin/form_creator",
			$data, TRUE);
		$this->load->view("admin/default", $this->viewdata);
	}



	function upgrade()
	{
		if($this->input->post('upgrade'))
		{
			// triggers the upgrade
			if (!$this->upgrade->do_upgrade())
			{
				// clean the cache in case of failure
				$this->upgrade->clean();
				// show some kind of error
				log_message('error', 'system.php do_upgrade(): failed upgrade');
				flash_notice('error', __('Upgrade failed: check file permissions.'));
			}
			else
			{
				flash_notice('success', __('Upgrade successful'));
			}

			redirect($this->uri->uri_string());
		}

		$this->viewdata["function_title"] = __("Upgrade");

		// get current version from constant
		$data["current_version"] = FOOL_VERSION;

		// check if the user can upgrade by checking if files are writeable
		$data["can_upgrade"] = $this->upgrade->check_files();
		if (!$data["can_upgrade"])
		{
			// if there are not writeable files, suggest the actions to take
			$this->upgrade->permissions_suggest();
		}

		// look for the latest version available
		$data["new_versions"] = $this->upgrade->check_latest();

		// we're going to use markdown here
		$this->load->library('Markdown_Parser');
		$data["changelog"] = $this->upgrade->get_changelog();

		// print out
		$this->viewdata["main_content_view"] = $this->load->view("admin/system/upgrade",
			$data, TRUE);
		$this->load->view("admin/default.php", $this->viewdata);
	}

}
