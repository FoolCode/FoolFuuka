<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Admin_Controller extends MY_Controller
{
	public function __construct()
	{
		parent::__construct();

		if (!$this->tank_auth->is_logged_in())
		{
			$this->session->set_userdata('login_redirect', $this->uri->uri_string());
			redirect('/account/auth/login');
		}
		$this->tank_auth->is_allowed() or show_404();

		$this->viewdata["sidebar"] = $this->sidebar();

		// Check if the database is upgraded to the the latest available
		if ($this->tank_auth->is_admin() && $this->uri->uri_string() != 'admin/database/upgrade' && $this->uri->uri_string() != 'admin/database/do_upgrade')
		{
			$this->config->load('migration');
			$config_version = $this->config->item('migration_version');
			$db_version = $this->db->get('migrations')->row()->version;
			if ($db_version != $config_version)
			{
				redirect('/admin/database/upgrade/');
			}
			$this->cron();
		}
	}


	/*
	 * Non-dynamic sidebar array.
	 * Permissions are set inside
	 * 
	 * @author Woxxy
	 * @return sidebar array
	 */
	function sidebar_val()
	{

		$sidebar = array();

		if (get_setting('fs_balancer_master_url'))
		{
			$sidebar["members"] = array(
				"name" => _("Members"),
				"level" => "member",
				"default" => "members",
				"icon" => 258,
				"content" => array(
					"members" => array("level" => "mod", "name" => _("Member List"), "icon" => 382),
				)
			);
			$sidebar["preferences"] = array(
				"name" => _("Preferences"),
				"level" => "admin",
				"default" => "general",
				"icon" => 402,
				"content" => array(
					"registration" => array("level" => "admin", "name" => _("Registration"), "icon" => 360),
				)
			);
			$sidebar["balancer"] = array("name" => _("Load balancer"),
				"level" => "admin",
				"default" => "balancers",
				"icon" => 255,
				"content" => array(
					"client" => array("level" => "admin", "name" => _("Client"), "icon" => 120),
				)
			);

			return $sidebar;
		}

		$sidebar["series"] = array(
			"name" => _("Series"),
			"level" => "mod",
			"default" => "manage",
			"icon" => 146,
			"content" => array(
				"manage" => array("level" => "mod", "name" => _("Manage"), "icon" => 184),
				"add_new" => array("level" => "mod", "name" => _("Add New"), "icon" => 154),
				"add_new_chapter" => array("level" => "mod", "name" => _("Add Chapter"), "icon" => 274)
			)
		);
		$sidebar["members"] = array(
			"name" => _("Members"),
			"level" => "member",
			"default" => "members",
			"icon" => 258,
			"content" => array(
				"members" => array("level" => "mod", "name" => _("Member List"), "icon" => 382),
				"teams" => array("level" => "member", "name" => _("Team List"), "icon" => 357),
				"home_team" => array("level" => "member", "name" => _("Home Team"), "icon" => 210),
				"add_team" => array("level" => "mod", "name" => _("Add Team"), "icon" => 328)
			)
		);
		$sidebar["preferences"] = array(
			"name" => _("Preferences"),
			"level" => "admin",
			"default" => "general",
			"icon" => 402,
			"content" => array(
				"general" => array("level" => "admin", "name" => _("General"), "icon" => 147),
				"reader" => array("level" => "admin", "name" => _("Reader"), "icon" => 118),
				"theme" => array("level" => "admin", "name" => _("Theme"), "icon" => 176),
				"registration" => array("level" => "admin", "name" => _("Registration"), "icon" => 360),
				"advertising" => array("level" => "admin", "name" => _("Advertising"), "icon" => 285),
			)
		);
		$sidebar["balancer"] = array("name" => _("Load Balancer"),
			"level" => "admin",
			"default" => "balancers",
			"icon" => 255,
			"content" => array(
				"balancers" => array("level" => "admin", "name" => _("Master"), "icon" => 121),
				"client" => array("level" => "admin", "name" => _("Client"), "icon" => 120),
			)
		);
		$sidebar["system"] = array("name" => _("System"),
			"level" => "admin",
			"default" => "system",
			"icon" => 248,
			"content" => array(
				"information" => array("level" => "admin", "name" => _("Information"), "icon" => 150),
				"preferences" => array("level" => "admin", "name" => _("Preferences") . ' <span class="label notice">' . _('New') . '</span>', "icon" => 149),
				"tools" => array("level" => "admin", "name" => _("Tools") . ' <span class="label notice">' . _('New') . '</span>', "icon" => 351),
				"upgrade" => array("level" => "admin", "name" => _("Upgrade") . ((get_setting('fs_cron_autoupgrade_version') && version_compare(FOOLSLIDE_VERSION, get_setting('fs_cron_autoupgrade_version')) < 0) ? ' <span class="label success">' . _('New') . '</span>' : ''), "icon" => 353),
			)
		);

		$sidebar["meta"] = array("name" => "Meta", // no gettext because meta must be meta
			"level" => "member",
			"default" => "http://ask.foolrulez.com",
			"icon" => 423,
			"content" => array(
				"http://ask.foolrulez.com" => array("level" => "member", "name" => _("Ask FoOlRulez & FAQ"), "icon" => 356),
				"http://trac.foolrulez.com/foolslide" => array("level" => "member", "name" => _("Bug tracker"), "icon" => 312),
			)
		);

		return $sidebar;
	}


	/*
	 * Returns the sidebar code
	 * 
	 * @todo comment this
	 */
	public function sidebar()
	{
		// not logged in users don't need the sidebar
		if (!$this->tank_auth->is_logged_in())
			return false;

		$result = "";
		foreach ($this->sidebar_val() as $key => $item)
		{

			// segment 2 contains what's currently active so we can set it lighted up
			if ($this->uri->segment(2) == $key)
				$active = TRUE;
			else
				$active = FALSE;
			if (($this->tank_auth->is_admin() || $this->tank_auth->is_group($item["level"])) && !empty($item))
			{
				$result .= '<h5><a href="' . ((substr($item["default"], 0, 7) == 'http://') ? $item["default"] : site_url(array("admin", $key, $item["default"]))) . '" ' . ((substr($item["default"], 0, 7) == 'http://') ? 'target="_blank"' : '') . '><img src="' . icons($item['icon']) . '" class="icon">' . $item["name"] . '</a></h5>';
				$result .= '<ul class="sidebar">';
				foreach ($item["content"] as $subkey => $subitem)
				{
					if ($active && $this->uri->segment(3) == $subkey)
						$subactive = TRUE;
					else
						$subactive = FALSE;
					if (($this->tank_auth->is_admin() || $this->tank_auth->is_group($subitem["level"])))
					{
						//if($subitem["name"] == $_GET["location"]) $is_active = " active"; else $is_active = "";
						$is_active = "";
						$result .= '<li class="' . ($subactive ? 'active' : '') . '"><a href="' . ((substr($subkey, 0, 7) == 'http://') ? $subkey : site_url(array("admin", $key, $subkey))) . '"  ' . ((substr($subkey, 0, 7) == 'http://') ? 'target="_blank"' : '') . '><img src="' . icons($subitem['icon'], 16) . '" class="icon icon-small">' . $subitem["name"] . '</a></li>';
					}
				}
				$result .= '</ul>';
			}
		}
		return $result;
	}


	/*
	 * Controller for cron triggered by admin panel
	 * Currently defaulted crons:
	 * -check for updates
	 * -remove one week old logs
	 * 
	 * @author Woxxy
	 */
	public function cron()
	{
		if ($this->tank_auth->is_admin())
		{
			$last_check = get_setting('fs_cron_autoupgrade');

			// hourly cron
			if (time() - $last_check > 3600)
			{
				// update autoupgrade cron time
				$this->db->update('preferences', array('value' => time()), array('name' => 'fs_cron_autoupgrade'));

				// load model
				$this->load->model('upgrade_model');
				// check
				$versions = $this->upgrade_model->check_latest(TRUE);

				// if a version is outputted, save the new version number in database
				if ($versions[0])
				{
					$this->db->update('preferences', array('value' => $versions[0]->version . '.' . $versions[0]->subversion . '.' . $versions[0]->subsubversion), array('name' => 'fs_cron_autoupgrade_version'));
				}

				// remove one week old logs
				$files = glob($this->config->item('log_path') . 'log*.php');
				foreach ($files as $file)
				{
					if (filemtime($file) < strtotime('-7 days'))
					{
						unlink($file);
					}
				}

				// reload the settings
				load_settings();
			}
		}
	}


}