<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');


class Install extends Install_Controller
{


	function __construct()
	{
		parent::__construct();

		// make sure people don't get here if FoOlSlide is already installed
		if (file_exists("config.php"))
			redirect('admin');
		$this->viewdata["controller_title"] = _("Installation");
	}

	/**
	 * This function shows and does everything on installation.
	 * The rest are private functions.
	 * 
	 * @author Woxxy
	 */
	function index()
	{
		if (!is_writable("content"))
			$form = array();

		if (!$this->_check())
		{
			$this->viewdata['main_content_view'] = "";
			$this->load->view("install/default", $this->viewdata);
			return FALSE;
		}

		$form['open'] = array(
			'type' => 'open'
		);
		
		$form['db_type'] = array(
			'label' => _('Database type'),
			'type' => 'input',
			'values' => array('mysql' => 'MySQL'),
			'value' => 'mysql',
			'disabled' => 'disabled',
			'help' => _('The type of database you\'re going to use. There\'s only one option currently.')
		);

		$form['db_hostname'] = array(
			'label' => _('Database hostname'),
			'type' => 'input',
			'placeholder' => 'required',
			'required' => 'required',
			'value' => 'localhost',
			'help' => _('The hostname of the server hosting the database. 99.8% of times is localhost')
		);

		$form['db_name'] = array(
			'label' => _('Database name'),
			'type' => 'input',
			'placeholder' => 'required',
			'required' => 'required',
			'help' => _('The name of the database')
		);

		$form['db_username'] = array(
			'label' => _('Database username'),
			'type' => 'input',
			'id' => 'db_username',
			'required' => 'required',
			'placeholder' => 'required',
			'help' => _('The username of the user with permissions to the database')
		);

		$form['db_password'] = array(
			'label' => _('Database password'),
			'type' => 'password',
			'required' => 'required',
			'placeholder' => 'required',
			'help' => _('The password of the user with permissions to the database')
		);

		$form['db_prefix'] = array(
			'label' => _('Database prefix'),
			'type' => 'input',
			'value' => 'fu_',
			'help' => _('Add a  database prefix to avoid collisions, in example if you have other ' . FOOL_NAME)
		);

		$form['spacer'] = array(
			'type' => 'separator'
		);

		$form['username'] = array(
			'label' => _('Administrator username'),
			'type' => 'input',
			'required' => 'required',
			'placeholder' => 'required',
			'help' => _('The username of the administrator\'s account')
		);

		$form['password'] = array(
			'label' => _('Administrator password'),
			'type' => 'password',
			'placeholder' => 'required',
			'required' => 'required',
			'help' => _('The password of the administrator\'s account')
		);

		$form['email'] = array(
			'label' => _('Administrator email'),
			'type' => 'input',
			'placeholder' => 'required',
			'required' => 'required',
			'help' => _('The email of the administrator\'s account')
		);
		
		$form['spacer2'] = array(
			'type' => 'separator',
		);
		
		$form['submit'] = array(
			'type' => 'submit',
			'value' => 'Submit',
			'class' => 'btn btn-primary'
		);
		
		$form['close'] = array(
			'type' => 'close'
		);

		// send to _submit that does all the rest of installation
		if ($post = $this->input->post())
		{
			if ($this->_submit($post) == 'stop')
			{
				return FALSE;
			}

			if (validation_errors())
				set_notice('error', validation_errors());
		}

		// print out
		$this->viewdata['main_content_view'] = $this->load->view("admin/form_creator",
			array('form' => $form), TRUE);
		$this->load->view("install/default", $this->viewdata);
	}

	/**
	 * Does the actual installation once data is submitted
	 * 
	 * @author Woxxy
	 */
	function _submit($post)
	{

		// validate the inputted data with the validation class
		$this->load->library('form_validation');
		$this->form_validation->set_rules('db_type', _('Database type'), '');
		$this->form_validation->set_rules('db_hostname', _('Database hostname'), '');
		$this->form_validation->set_rules('db_name', _('Database name'), '');
		$this->form_validation->set_rules('db_username', _('Database username'), '');
		$this->form_validation->set_rules('db_password', _('Database password'), '');
		$this->form_validation->set_rules('db_prefix', _('Database prefix'), '');
		$this->form_validation->set_rules('username', _('Administrator username'),
			'required|min_length[4]|max_length[20]');
		$this->form_validation->set_rules('password', _('Administrator password'),
			'required|min_length[5]|max_length[20]');
		$this->form_validation->set_rules('email', _('Administrator email'),
			'required|valid_email');

		if ($this->form_validation->run() == FALSE)
		{
			return FALSE;
		}

		// we need to check database connection, and to do that we need to connect in first place
		$config["hostname"] = $post["db_hostname"];
		$config["database"] = $post["db_name"];
		$config["username"] = $post["db_username"];
		$config["password"] = $post["db_password"];
		$config["dbprefix"] = $post["db_prefix"];
		$config['dbdriver'] = $post["db_type"];
		$config['pconnect'] = FALSE;
		$config['db_debug'] = FALSE;
		$config['cache_on'] = FALSE;
		$config['cachedir'] = "";
		$config['char_set'] = "utf8";
		$config['dbcollat'] = "utf8_general_ci";
		// load the co
		$this->db = $this->load->database($config, TRUE);

		// without conn_id we're not connected to the database
		if ($this->db->conn_id == "")
		{
			// unable to connect
			set_notice('error',
				_('Connection with database not enstabilished: check the database fields.'));
			return false;
		}

		// pick the sample config and replace entries so we can insert the database 
		$config = read_file('assets/config.sample.php');
		$config = str_replace("\$db['default']['dbdriver'] = ''",
			"\$db['default']['dbdriver'] = '" . addslashes($post["db_type"]) . "'",
			$config);
		$config = str_replace("\$db['default']['hostname'] = 'localhost'",
			"\$db['default']['hostname'] = '" . addslashes($post["db_hostname"]) . "'",
			$config);
		$config = str_replace("\$db['default']['username'] = ''",
			"\$db['default']['username'] = '" . addslashes($post["db_username"]) . "'",
			$config);
		$config = str_replace("\$db['default']['password'] = ''",
			"\$db['default']['password'] = '" . addslashes($post["db_password"]) . "'",
			$config);
		$config = str_replace("\$db['default']['database'] = ''",
			"\$db['default']['database'] = '" . addslashes($post["db_name"]) . "'",
			$config);
		$config = str_replace("\$db['default']['dbprefix'] = 'fs_'",
			"\$db['default']['dbprefix'] = '" . addslashes($post["db_prefix"]) . "'",
			$config);

		// create a random string of 20 letters and numbers for the encryption key
		$this->load->helper('string');
		$random_string = random_string('alnum', 20);
		$this->config->set_item('encryption_key', $random_string);
		$config = str_replace("\$config['encryption_key'] = ''",
			"\$config['encryption_key'] = '" . addslashes($random_string) . "'", $config);

		// check if a manual config file must be made manually (due to no permissions on FoOlSlide root)
		$manual_config = FALSE;
		if (!write_file('config.php', $config))
		{
			$manual_config = TRUE;
		}

		// load the necessary libraries
		// migrate to latest database
		$this->load->library('migration');
		$this->migration->latest();

		// load everything needed for a normal startup
		$this->load->library('session');
		$this->load->library('tank_auth');

		// load the settings from the now filled database
		load_settings();

		$user = $this->tank_auth->create_user($post["username"], $post["email"],
			$post["password"], FALSE);
		
		if ($user !== FALSE)
		{
			$this->db->where('user_id', $user['user_id'])->update('profiles', array('group_id' => 1));
		}

		// create the generic dirs that you can find in content folder
		if (!is_dir('content/cache'))
			mkdir('content/cache');
		if (!is_dir('content/logs'))
			mkdir('content/logs');
		if (!is_dir('content/boards'))
			mkdir('content/boards');

		// if install can't make that config.php file, tell the user to do it manually
		if ($manual_config)
		{
			$this->notices = array();
			$data["config"] = $config;
			$this->viewdata['main_content_view'] = $this->load->view("install/manual_config",
				$data, TRUE);
			$this->load->view("install/default", $this->viewdata);
			return 'stop';
		}

		// a nice notice to tell that FoOlSlide install was a success
		flash_notice('notice',
			_(FOOL_NAME . ' has installed successfully. Check the preferences and make sure you create a team for your own chapters.'));
		redirect('/admin/');
	}

	/**
	 * Checks that the necessary directories are writable and prepares suggestions to
	 * be able to deliver autoupgrades.
	 * 
	 * @author Woxxy
	 */
	function _check()
	{
		$prob = FALSE;

		if (version_compare(phpversion(), FOOL_REQUIREMENT_PHP) < 0)
		{
			set_notice('error',
				_('You need at least PHP version ' . FOOL_REQUIREMENT_PHP . ' to run ' . FOOL_NAME . '. This means you have a many years old version. It is suggested to upgrade to a more recent version of PHP to avoid security issues with your server in general.'));
			$prob = TRUE;
			return FALSE;
		}

		if (!file_exists('assets/config.sample.php'))
		{
			set_notice('error',
				sprintf(_('The file %s was removed. The installation can\'t continue without that file. You can find it in the ' . FOOL_NAME . ' download.'),
					FCPATH . 'config.sample.php'));
			$prob = TRUE;
			return FALSE;
		}

		if (!is_writable('content'))
		{
			set_notice('error',
				sprintf(_('The %s directory needs to be writable. Use this command in your shell if possible: %s or change its permissions recursively to 777 with your own FTP software. You won\'t be able to install or run ' . FOOL_NAME . ' without this.'),
					FCPATH . 'content/',
					'<br/><b><code>chmod -R 777 ' . FCPATH . 'content/</code></b><br/>'));
			$prob = TRUE;
			return FALSE;
		}

		if (!is_writable('content/themes'))
		{
			set_notice('error',
				sprintf(_('The %s directory needs to be writable as well. Use this command in your shell if possible: %s or change its permissions recursively to 777 with your own FTP software. You won\'t be able to install or run ' . FOOL_NAME . ' without this.'),
					FCPATH . 'content/themes',
					'<br/><b><code>chmod -R 777 ' . FCPATH . 'content/</code></b><br/>'));
			$prob = TRUE;
			return FALSE;
		}

		// check if base folder is writable
		if (!is_writable('.'))
		{
			$whoami = FALSE;

			// if exec is enable, just check with whoami function who's running php
			if ($this->_exec_enabled())
				$whoami = exec('whoami');

			// if exec is not enabled, write a file and check who has the permissions on it
			if (!$whoami && is_writable('content') && function_exists('posix_getpwid'))
			{
				write_file('content/testing_123.txt', 'testing_123');
				$whoami = posix_getpwuid(fileowner('content/testing_123.txt'));
				$whoami = $whoami['name'];
				unlink('content/testing_123.txt');
			}

			// if absolutely unable to tell who's the php user, just apologize
			// else, give a precise command for shell to enter
			if ($whoami != "")
				set_notice('warn',
					sprintf(_('The %s directory would be better if writable, in order to deliver automatic updates. Use this command in your shell if possible: %s'),
						FCPATH, '<br/><b><code>chown -R ' . $whoami . ' ' . FCPATH . '</code></b>'));
			else
				set_notice('warn',
					sprintf(_('The %s directory would be better if writable, in order to deliver automatic updates.<br/>It was impossible to determine the user running PHP. Use this command in your shell if possible: %s where www-data is an example (usually it\'s www-data or Apache)'),
						FCPATH, '<br/><b><code>chown -R www-data ' . FCPATH . '</code></b><br/>'));
			set_notice('warn',
				sprintf(_('If you can\'t do the above, after the installation you will be given a textfile to paste in config.php. More info after submitting.')));
			$prob = TRUE;
		}

		// there was an issue? suggest to refresh the page to check again
		if ($prob)
		{
			set_notice('notice',
				_('If you made any changes, just refresh this page to recheck the directory permissions.'));
		}

		// all good
		return TRUE;
	}

	/**
	 * checks if exex is enabled
	 * 
	 * @author Woxxy
	 */
	function _exec_enabled()
	{
		$disabled = explode(',', ini_get('disable_functions'));
		return !in_array('exec', $disabled);
	}

}