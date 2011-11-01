<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Team extends DataMapper
{

	static $cached = array();
	var $has_one = array();
	var $has_many = array('chapter');
	var $validation = array(
		'name' => array(
			'rules' => array('required', 'max_length' => 256),
			'label' => 'Name',
			'type' => 'input'
		),
		'stub' => array(
			'rules' => array('required', 'stub', 'unique', 'max_length' => 256),
			'label' => 'Stub'
		),
		'url' => array(
			'rules' => array('max_length' => 256),
			'label' => 'URL',
			'type' => 'input'
		),
		'forum' => array(
			'rules' => array('max_length' => 256),
			'label' => 'Forum',
			'type' => 'input'
		),
		'irc' => array(
			'rules' => array('max_length' => 256),
			'label' => 'IRC',
			'type' => 'input'
		),
		'twitter' => array(
			'rules' => array(),
			'label' => 'Twitter username',
			'type' => 'input'
		),
		'facebook' => array(
			'rules' => array(),
			'label' => 'Facebook',
			'type' => 'input'
		),
		'facebookid' => array(
			'rules' => array('max_length' => 512),
			'label' => 'Facebook page ID',
			'type' => 'input'
		),
		'lastseen' => array(
			'rules' => array(),
			'label' => 'Lastseen'
		),
		'creator' => array(
			'rules' => array('required'),
			'label' => 'Creator'
		),
		'editor' => array(
			'rules' => array('required'),
			'label' => 'Editor'
		)
	);

	function __construct($id = NULL)
	{
		if (!is_null($id) && $team = $this->get_cached($id)) {
			parent::__construct();
			foreach($team->to_array() as $key => $t) {
				$this->$key = $t;
				
				// fill also the all array so result_count() is correctly 1
				$this->all[0]->$key = $t;
			}
			return TRUE;
		}
		parent::__construct($id);
	}


	function post_model_init($from_cache = FALSE)
	{
		
	}


	/**
	 * Overwrite of the get() function to add filters to the search.
	 * Refer to DataMapper ORM for get() function details.
	 *
	 * @author	Woxxy
	 * @param	integer|NULL $limit Limit the number of results.
	 * @param	integer|NULL $offset Offset the results when limiting.
	 * @return	DataMapper Returns self for method chaining.
	 */
	public function get($limit = NULL, $offset = NULL)
	{
		$result = parent::get($limit, $offset);
		// let's put the result in a small cache, since teams are always the same
		if ($this->result_count() > 0)
		{
			foreach ($this->all as $team)
			{
				// if it's not yet cached, let's cache it
				if (!$this->get_cached($team->id))
				{
					if(count(self::$cached) > 10)
					array_shift(self::$cached);
					self::$cached[] = $team->get_clone();
				}
			}
		}

		return $result;
	}


	/**
	 * Returns the teams that have been already called before
	 * 
	 * @author Woxxy
	 * @param int $id team_id
	 */
	public function get_cached($id)
	{
		foreach (self::$cached as $cache)
		{
			if ($cache->id == $id)
			{
				return $cache;
			}
		}
		return FALSE;
	}


	/**
	 * Leaving this here to make sure I can restore it later in case somewhere it was used
	 *
	 * @deprecated
	 */
	public function add_team($name, $url = "", $forum = "", $irc = "", $twitter = "", $facebook = "", $facebookid = "")
	{
		$this->name = $name;
		$this->url = $url;
		$this->forum = $forum;
		$this->irc = $irc;
		$this->twitter = $twitter;
		$this->facebook = $facebook;
		$this->facebookid = $facebookid;

		if (!$this->update_team())
		{
			log_message('error', 'add_team: failed adding team');
			return false;
		}

		return true;
	}


	public function update_team($data = array())
	{

		// Check if we're updating or creating a new entry by looking at $data["id"].
		// False is pushed if the ID was not found.
		if (isset($data["id"]) && $data['id'] != '')
		{
			$this->where("id", $data["id"])->get();
			if ($this->result_count() == 0)
			{
				set_notice('error', _('Failed to find the selected team\'s ID.'));
				log_message('error', 'update_team_db: failed to find requested id');
				return false;
			}
			// Save the stub in a variable in case it gets changed, so we can change folder name
			$old_stub = $this->stub;
			$old_name = $this->name;
		}
		else
		{ // let's set the creator name if it's a new entry
			$this->creator = $this->logged_id();
		}

		// always set the editor name
		$this->editor = $this->logged_id();


		// Loop over the array and assign values to the variables.
		foreach ($data as $key => $value)
		{
			$this->$key = $value;
		}

		// Unset sensible variables
		unset($data["creator"]);
		unset($data["editor"]);
		unset($data["stub"]);

		// Allow only admins and mods to arbitrarily change the release date
		$CI = & get_instance();
		if (!$CI->tank_auth->is_allowed())
			unset($data["created"]);
		if (!$CI->tank_auth->is_allowed())
			unset($data["edited"]);

		// Double check that we have all the necessary automated variables
		if (!isset($this->uniqid))
			$this->uniqid = uniqid();
		if (!isset($this->stub))
			$this->stub = $this->stub();

		// Create a new stub if the name has changed
		if (isset($old_name) && isset($old_stub) && ($old_name != $this->name))
		{
			// Prepare a new stub.
			$this->stub = $this->name;
			// stub() is also able to restub the $this->stub. Already stubbed values won't change.
			$this->stub = $this->stub();
		}


		// Make so there's no intersecting stubs, and make a stub with a number in case of duplicates
		// In case this chapter already has a stub and it wasn't changed, don't change it!
		if ((!isset($this->id) || $this->id == '') || (isset($old_stub) && $old_stub != $this->stub))
		{
			$i = 1;
			$found = FALSE;

			$team = new Team();
			$team->where('stub', $this->stub)->get();
			if ($team->result_count() == 0)
			{
				$found = TRUE;
			}

			while (!$found)
			{
				$i++;
				$pre_stub = $this->stub . '_' . $i;
				$team = new Team();
				$team->where('stub', $pre_stub)->get();
				if ($team->result_count() == 0)
				{
					$this->stub = $pre_stub;
					$found = TRUE;
				}
			}
		}

		// let's save and give some error check. Push false if fail, true if good.
		if (!$this->save())
		{
			if (!$this->valid)
			{
				set_notice('error', _('Check that you have inputted all the required fields.'));
				log_message('error', 'update_team: failed validation');
			}
			else
			{
				set_notice('error', _('Failed to update the team in the database for unknown reasons.'));
				log_message('error', 'update_team: failed to save');
			}
			return false;
		}
		else
		{
			return true;
		}
	}


	public function remove_team($also_chapters = FALSE)
	{
		if ($this->result_count() != 1)
		{
			set_notice('error', _('Failed to remove the chapter directory. Please, check file permissions.'));
			log_message('error', 'remove_team: id not found');
			return false;
		}

		if ($also_chapters)
		{
			$chapters = new Chapter();
			$chapters->where("team_id", $this->id)->get();
			foreach ($chapters->all as $chapter)
			{
				if (!$chapter->remove())
				{
					set_notice('error', _('Failed removing the chapters while removing the team.'));
					log_message('error', 'remove_team: failed removing chapter');
					return false;
				}
			}
		}

		$joint = new Joint();
		if (!$joint->remove_team_from_all($this->id))
		{
			log_message('error', 'remove_team: failed removing traces of team in joints');
			return false;
		}

		if (!$this->delete())
		{
			set_notice('error', _('Failed to delete the team for unknown reasons.'));
			log_message('error', 'remove_team: failed removing team');
			return false;
		}

		return true;
	}


	// this works by inputting an array of names (not stubs)
	public function get_teams_id($array, $create_joint = FALSE)
	{
		if (count($array) < 1)
		{
			set_notice('error', _('There were no groups selected.'));
			log_message('error', 'get_groups: input array empty');
			return false;
		}

		if (count($array) == 1)
		{
			$team = new Team();
			$team->where("name", $array[0])->get();
			if ($team->result_count() < 1)
			{
				set_notice('error', _('There\'s no team under this ID.'));
				log_message('error', 'get_groups: team not found');
				return false;
			}
			$result = array("team_id" => $team->id, "joint_id" => 0);
			return $result;
		}

		if (count($array) > 1)
		{
			$id_array = array();
			foreach ($array as $key => $arra)
			{
				$team = new Team();
				$team->where('name', $arra[$key])->get();
				if ($team->result_count() < 1)
				{
					set_notice('error', _('There\'s no teams under this ID.'));
					log_message('error', 'get_groups: team not found');
					return false;
				}
				$id_array[$key] = $team->id;
			}
			$joint = new Joint();
			if (!$joint->check_joint($id_array) && $create_joint)
			{
				if (!$joint->add_joint($id_array))
				{
					log_message('error', 'get_groups: could not create new joint');
					return false;
				}
			}
			return array("team_id" => 0, "joint_id" => $joint->joint_id);
		}

		set_notice('error', _('There\'s no group found with this ID.'));
		log_message('error', 'get_groups: no case matched');
		return false;
	}


	//////// UNFINISHED! // Or is it finished...? @_@

	public function get_teams($team_id, $joint_id = 0)
	{
		// if it's a joint, let's deal it as a joing
		if ($joint_id > 0)
		{
			// get all the joint entries so we have all the teams
			$joint = new Joint();
			$joint->where("joint_id", $joint_id)->get();

			// not an existing joint?
			if ($joint->result_count() < 1)
			{
				log_message('error', 'get_teams: joint -> joint not found');
				return false;
			}

			// result array
			$teamarray = array();
			foreach ($joint->all as $key => $join)
			{
				if (!$team = $this->get_cached($join->team_id))
				{
					$team = new Team();
					$team->where('id', $join->team_id);
					$team->get();
				}
				$teamarray[] = $team->get_clone();
			}

			if (empty($teamarray))
			{
				log_message('error', 'get_teams: joint -> no teams found');
				return false;
			}

			return $teamarray;
		}

		// if we're here, it means it's a simple team
		if (!$team = $this->get_cached($team_id))
			$team = new Team($team_id);
		return array($team);
	}
	
	
	/**
	 * Returns the href to the reader. This will create the shortest possible URL.
	 *
	 * @author	Woxxy
	 * @returns string href to reader.
	 */
	public function href()
	{
		return site_url('/reader/team/' . $this->stub);
	}
	

	/**
	 * Overwrites the original DataMapper to_array() to add some elements
	 * 
	 * @param array $fields
	 * @return array
	 */
	public function to_array($fields = '')
	{
		$result = parent::to_array($fields = '');
		$result["href"] = $this->href();
		return $result;
	}

}

/* End of file team.php */
/* Location: ./application/models/team.php */