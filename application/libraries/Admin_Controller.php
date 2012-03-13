<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Admin_Controller extends MY_Controller
{

	var $sidebar = NULL;
	var $sidebar_dynamic = NULL;


	public function __construct()
	{
		parent::__construct();

		if (!$this->tank_auth->is_allowed() && $this->uri->segment(2) != 'auth')
		{
			$this->session->set_userdata('login_redirect', $this->uri->uri_string());
			redirect('/admin/auth/login');
		}
		
		$this->load->library('datamapper');

		// a bit of looping to create the sidebar
		// 
		// returns the static sidebar array (can't use functions in )
		$this->sidebar = $this->get_sidebar_values();

		// merge if there were sidebar elements added dynamically
		if (!is_null($this->sidebar_dynamic))
		{
			$this->sidebar = $this->merge_sidebars($this->sidebar, $this->sidebar_dynamic);
		}
		// removes the sidebar elements for which user has no permissions
		// and adds some data (active, checks URLs...)
		$viewdata["sidebar"] = $this->get_sidebar($this->sidebar);
		$this->viewdata['sidebar'] = $this->load->view('admin/sidebar', $viewdata,
			TRUE);


		$this->viewdata['topbar'] = $this->load->view('admin/navbar', '', TRUE);


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


	/**
	 * Checks the form for and returns either a compiled array of values or
	 * the error
	 *  
	 */
	function form_validate($form)
	{
		// this gets a bit complex because we want to show all errors at the same
		// time, which means we have to run both CI validation and custom, then
		// merge the result.

		$this->load->library('form_validation');
		foreach ($form as $name => $item)
		{
			if (isset($item['validation']))
			{
				$this->form_validation->set_rules($name, $item['label'], $item['validation']);
			}
		}

		// we need to run both validation and closures
		$this->form_validation->run();
		$ci_validation_errors = $this->form_validation->get_errors_array();

		$validation_func = array();
		// we run this after form_validation in case form_validation edited the POST data
		foreach ($form as $name => $item)
		{
			// the "required" MUST be handled with the standard form_validation
			// or we'll never get in here
			if (isset($item['validation_func']) && $this->input->post($name))
			{
				// contains TRUE for success and in array with ['error'] in case
				$validation_func[$name] = $item['validation_func']($this->input->post(),
					$form);

				// critical errors don't allow the continuation of the validation.
				// this allows less checks for functions that come after the critical ones.
				// criticals are usually the IDs in the hidden fields.
				if (isset($validation_func[$name]['critical']) &&
					$validation_func[$name]['critical'] == TRUE)
				{
					break;
				}
			}
		}


		// filter results, since the closures return ['success'] = TRUE on success
		$validation_func_errors = array();
		$validation_func_warnings = array();
		foreach ($validation_func as $item)
		{
			// we want only the errors
			if (isset($item['success']))
			{
				continue;
			}

			if (isset($item['warning']))
			{
				// we want only the human readable error
				$validation_func_warnings[] = $item['warning'];
			}

			if (isset($item['error']))
			{
				// we want only the human readable error
				$validation_func_errors[] = $item['error'];
			}
		}

		if (count($ci_validation_errors) > 0 || count($validation_func_errors) > 0)
		{
			$errors = array_merge($ci_validation_errors, $validation_func_errors);
			return array('error' => implode(' ', $errors));
		}
		else
		{
			// get rid of all the uninteresting inputs and simplify
			$result = array();

			foreach ($form as $name => $item)
			{
				// not interested in data that is not related to database
				if ((!isset($item['database']) || $item['database'] !== TRUE) &&
					(!isset($item['preferences']) || $item['preferences'] !== TRUE))
				{
					continue;
				}

				if ($item['type'] == 'checkbox')
				{
					if ($this->input->post($name) == 1)
					{
						$result[$name] = 1;
					}
					else
					{
						$result[$name] = 0;
					}
				}
				else
				{
					if ($this->input->post($name) !== FALSE)
					{
						$result[$name] = $this->input->post($name);
					}
				}
			}

			if (count($validation_func_warnings) > 0)
			{
				return array('success' => $result, 'warning' => implode(' ',
						$validation_func_warnings));
			}

			// returning a form with the new values
			return array('success' => $result);
		}
	}


	function submit_preferences($data)
	{
		foreach ($data as $name => $value)
		{
			$this->db->where(array('name' => $name));
			if ($this->db->count_all_results('preferences') == 1)
			{
				$this->db->update('preferences', array('value' => $value),
					array('name' => $name));
			}
			else
			{
				$this->db->insert('preferences', array('name' => $name, 'value' => $value));
			}
		}

		// reload those preferences
		load_settings();
	}


	function submit_preferences_auto($form)
	{
		if ($this->input->post())
		{
			$result = $this->form_validate($form);
			if (isset($result['error']))
			{
				set_notice('warning', $result['error']);
			}
			else
			{
				if (isset($result['warning']))
				{
					set_notice('warning', $result['warning']);
				}
				
				set_notice('success', _('Preferences updated.'));
				$this->submit_preferences($result['success']);
			}
		}
	}


	/**
	 * Non-dynamic sidebar array.
	 * Permissions are set inside
	 *
	 * @author Woxxy
	 * @return sidebar array
	 */
	function get_sidebar_values()
	{

		$sidebar = array();

		$sidebar["boards"] = array(
			"name" => _("Boards"),
			"level" => "admin",
			"default" => "manage",
			"content" => array(
				"manage" => array("alt_highlight" => array("board"),
					"level" => "admin", "name" => _("Manage"), "icon" => 'icon-th-list'),
				"sphinx" => array("level" => "admin", "name" => _("Sphinx"), "icon" => 'icon-search'),
				"add_new" => array("level" => "admin", "name" => _("Add board"), "icon" => 'icon-asterisk')
			)
		);

		$sidebar["posts"] = array(
			"name" => _("Posts"),
			"level" => "mod",
			"default" => "reports",
			"content" => array(
				"reports" => array("level" => "mod", "name" => _("Reports"), "icon" => 'icon-tag'),
			//	"spam" => array("level" => "mod", "name" => _("Spam"), "icon" => 'icon-remove-circle')
			)
		);

		$sidebar["members"] = array(
			"name" => _("Members"),
			"level" => "member",
			"default" => "members",
			"content" => array(
				"members" => array("level" => "mod", "name" => _("Member List"), "icon" => 'icon-user'),
				"teams" => array("level" => "member", "name" => _("Team List"), "icon" => 'icon-star-empty'),
				"home_team" => array("level" => "member", "name" => _("Home Team"), "icon" => 'icon-star'),
				"add_team" => array("level" => "mod", "name" => _("Add Team"), "icon" => 'icon-asterisk')
			)
		);
		$sidebar["preferences"] = array(
			"name" => _("Preferences"),
			"level" => "admin",
			"default" => "general",
			"content" => array(
				"general" => array("level" => "admin", "name" => _("General"), "icon" => 'icon-cog'),
				"theme" => array("level" => "admin", "name" => _("Theme"), "icon" => 'icon-picture'),
				"registration" => array("level" => "admin", "name" => _("Registration"), "icon" => 'icon-book'),
				"advertising" => array("level" => "admin", "name" => _("Advertising"), "icon" => 'icon-lock'),
			)
		);
		$sidebar["system"] = array(
			"name" => _("System"),
			"level" => "admin",
			"default" => "system",
			"content" => array(
				"information" => array("level" => "admin", "name" => _("Information"), "icon" => 'icon-info-sign'),
				"preferences" => array("level" => "admin", "name" => _("Preferences"), "icon" => 'icon-check'),
				"tools" => array("level" => "admin", "name" => _("Tools"), "icon" => 'icon-fire'),
				"upgrade" => array("level" => "admin", "name" => _("Upgrade") . ((get_setting('fs_cron_autoupgrade_version') && version_compare(FOOL_VERSION,
						get_setting('fs_cron_autoupgrade_version')) < 0) ? ' <span class="label success">' . _('New') . '</span>'
							: ''), "icon" => 'icon-refresh'),
			)
		);

		$sidebar["plugins"] = array(
			"name" => _("Plugins"),
			"level" => "admin",
			"default" => "manage",
			"content" => array(
				"manage" => array("level" => "admin", "name" => _("Manage"), "icon" => 'icon-gift'),
			)
		);

		$sidebar["balancer"] = array(
			"name" => _("Load Balancer"),
			"level" => "admin",
			"default" => "balancers",
			"content" => array(
				"balancers" => array("level" => "admin", "name" => _("Master"), "icon" => 'icon-download'),
				"client" => array("level" => "admin", "name" => _("Client"), "icon" => 'icon-upload'),
			)
		);

		$sidebar["meta"] = array(
			"name" => "Meta", // no gettext because meta must be meta
			"level" => "member",
			"default" => "http://ask.foolrulez.com",
			"content" => array(
				"https://github.com/FoOlRulez/FoOlFuuka/issues" => array("level" => "member", "name" => _("Bug tracker"), "icon" => 'icon-exclamation-sign'),
			)
		);

		return $sidebar;
	}


	public function add_sidebar_element($array)
	{
		if (is_null($this->sidebar_dynamic))
		{
			$this->sidebar_dynamic = array();
		}

		$this->sidebar_dynamic[] = $array;
	}


	public function merge_sidebars($array1, $array2)
	{
		foreach ($array2 as $key => $item)
		{
			// are we inserting in an already existing method?
			if (isset($array1[$key]))
			{
				// overriding the name
				if (isset($item['name']))
				{
					$array1[$key]['name'] = $item['name'];
				}

				// overriding the permission level
				if (isset($item['level']))
				{
					$array1[$key]['level'] = $item['level'];
				}

				// overriding the default url to reach
				if (isset($item['default']))
				{
					$array1[$key]['default'] = $item['default'];
				}

				// overriding the default url to reach
				if (isset($item['icon']))
				{
					$array1[$key]['icon'] = $item['icon'];
				}

				// adding or overriding the inner elements
				if (isset($item['content']))
				{
					if (isset($array1[$key]['content']))
					{
						$array1[$key]['content'] = $this->merge_sidebars($array1[$key]['content'],
							$item['content']);
					}
					else
					{
						$array1[$key]['content'] = $this->merge_sidebars(array(), $item['content']);
					}
				}
			}
			else
			{
				// the element doesn't exist at all yet
				// let's trust the plugin creator in understanding the structure
				// extra control: allow him to put the plugin after or before any function
				if (isset($item['position']) && is_array($item['position']))
				{
					$before = $array2['position']['beforeafter'] == 'before' ? TRUE : FALSE;
					$element = $array2['position']['element'];

					$array_temp = $array1;
					$array1 = array();
					foreach ($array_temp as $subkey => $temp)
					{
						if ($subkey == $element)
						{
							if ($before)
							{
								$array1[$key] = $item;
								$array1[$subkey] = $temp;
							}
							else
							{
								$array1[$subkey] = $temp;
								$array1[$key] = $item;
							}

							unset($array_temp[$subkey]);

							// flush the rest
							foreach ($array_temp as $k => $t)
							{
								$array1[$k] = $t;
							}

							break;
						}
						else
						{
							$array1[$subkey] = $temp;
							unset($array_temp[$subkey]);
						}
					}
				}
			}
		}

		return $array1;
	}


	/**
	 * Returns the sidebar array
	 *
	 * @todo comment this
	 */
	public function get_sidebar($array)
	{
		// not logged in users don't need the sidebar
		if (!$this->tank_auth->is_logged_in())
			return array();

		$result = array();
		foreach ($array as $key => $item)
		{
			if (($this->tank_auth->is_admin() || $this->tank_auth->is_group($item["level"])) && !empty($item))
			{
				$subresult = $item;

				// segment 2 contains what's currently active so we can set it lighted up
				if ($this->uri->segment(2) == $key)
				{
					$subresult['active'] = TRUE;
				}
				else
				{
					$subresult['active'] = FALSE;
				}

				// we'll cherry-pick the content next
				unset($subresult['content']);

				// recognize plain URLs
				if ((substr($item['default'], 0, 7) == 'http://') ||
					(substr($item['default'], 0, 8) == 'https://'))
				{
					// nothing to do here, just copy the URL
					$subresult['href'] = $item['default'];
				}
				else
				{
					// else these are internal URIs
					// what if it uses more segments or is even an array?
					if (!is_array($item['default']))
					{
						$default_uri = explode('/', $item['default']);
					}
					else
					{
						$default_uri = $item['default'];
					}
					array_unshift($default_uri, 'admin', $key);
					$subresult['href'] = site_url($default_uri);
				}

				$subresult['content'] = array();

				// cherry-picking subfunctions
				foreach ($item['content'] as $subkey => $subitem)
				{
					$subsubresult = array();
					$subsubresult = $subitem;
					if (($this->tank_auth->is_admin() || $this->tank_auth->is_group($subitem['level'])))
					{
						if ($subresult['active'] && ($this->uri->segment(3) == $subkey ||
							(
							isset($subitem['alt_highlight']) &&
							in_array($this->uri->segment(3), $subitem['alt_highlight'])
							)
							))
						{
							$subsubresult['active'] = TRUE;
						}
						else
						{
							$subsubresult['active'] = FALSE;
						}

						// recognize plain URLs
						if ((substr($subkey, 0, 7) == 'http://') ||
							(substr($subkey, 0, 8) == 'https://'))
						{
							// nothing to do here, just copy the URL
							$subsubresult['href'] = $subkey;
						}
						else
						{
							// else these are internal URIs
							// what if it uses more segments or is even an array?
							if (!is_array($subkey))
							{
								$default_uri = explode('/', $subkey);
							}
							else
							{
								$default_uri = $subkey;
							}
							array_unshift($default_uri, 'admin', $key);
							$subsubresult['href'] = site_url($default_uri);
						}

						$subresult['content'][] = $subsubresult;
					}
				}

				$result[] = $subresult;
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
				$this->db->update('preferences', array('value' => time()),
					array('name' => 'fs_cron_autoupgrade'));

				// load model
				$this->load->model('upgrade_model');
				// check
				$versions = $this->upgrade_model->check_latest(TRUE);

				// if a version is outputted, save the new version number in database
				if ($versions[0])
				{
					$this->db->update('preferences', array('value' => $versions[0]->name),
						array('name' => 'fs_cron_autoupgrade_version'));
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