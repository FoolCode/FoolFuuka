<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Joint extends DataMapper {

	var $has_one = array();
	var $has_many = array();
	var $validation = array(
		'joint_id' => array(
			'rules' => array('required', 'max_length' => 256),
			'label' => 'Name'
		),
		'team_id' => array(
			'rules' => array('required', 'max_length' => 256),
			'label' => 'Stub'
		),
		'creator' => array(
			'rules' => array('max_length' => 256),
			'label' => 'URL'
		),
		'editor' => array(
			'rules' => array('max_length' => 256),
			'label' => 'Forum'
		)
	);

	function __construct($id = NULL) {
		parent::__construct($id);
	}

	function post_model_init($from_cache = FALSE) {
		
	}

	public function check_joint($teams) {
		$teams = array_unique($teams);
		$size = count($teams);
		$joints = new Joint();
		$joints->where('team_id', $teams[0])->get_iterated();
		if ($joints->result_count() < 1) {
			log_message('debug', 'check_joint: joint not found, result count zero');
			return false;
		}
		
		foreach ($joints as $joint) {
			$join = new Joint();
			$join->where('joint_id', $joint->joint_id)->get_iterated();
			if ($join->result_count() == $size) {
				$test = $teams;
				foreach ($join as $joi) {
					$key = array_search($joi->team_id, $teams);
					if ($key === FALSE) {
						break;
					}
					unset($test[$key]);
				}
				if (empty($test)) {
					return $joi->joint_id;
				}
			}
		}
		log_message('debug', 'check_joint: joint not found');
		return false;
	}

	// $teams is an array of names
	public function add_joint_via_name($teams) {
		$result = array();
		foreach ($teams as $team) {
			$tea = new Team();
			$tea->where('name', $team)->get();
			if ($tea->result_count() == 0) {
				set_notice('error', _('One of the named teams doesn\'t exist.'));
				log_message('error', 'add_joint_via_name: team does not exist');
				return false;
			}
			$result[] = $tea->id;
		}
		return $this->add_joint($result);
	}

	// $teams is an array of IDs
	public function add_joint($teams) {
		if (!$result = $this->check_joint($teams)) {
			$maxjoint = new Joint();
			/**
			 * @todo select_max returns an error:
			 * ERROR - 2011-05-31 19:58:16 --> Severity: Notice --> Undefined offset: 0 /var/www/manga/beta3/system/database/DB_active_rec.php 1719
			 */
			//$maxjoint->select_max('joint_id')->get();
			$maxjoint->order_by('joint_id', 'DESC')->limit(1)->get();
			$max = $maxjoint->joint_id + 1;

			foreach ($teams as $key => $team) {
				$joint = new Joint();
				$joint->joint_id = $max;
				$joint->team_id = $team;
				$joint->creator = $this->logged_id();
				$joint->editor = $this->logged_id();
				if (!$joint->save()) {
					if ($joint->valid) {
						set_notice('error', _('Check that you have inputted all the required fields.'));
						log_message('error', 'add_joint: validation failed');
					}
					else {
						set_notice('error', _('Couldn\'t save joint to database due to an unknown error.'));
						log_message('error', 'add_joint: saving failed');
					}
					return false;
				}
			}
			return $max;
		}
		return $result;
	}

	public function remove_joint() {
		if (!$this->delete_all()) {
			set_notice('error', _('The joint couldn\'t be removed.'));
			log_message('error', 'remove_joint: failed deleting');
			return false;
		}
		return true;
	}

	public function add_team($team_id) {
		$joint = new Joint();
		$joint->team_id = $team_id;
		$joint->joint_id = $this->joint_id;
		$joint->creator = $this->logged_id();
		$joint->editor = $this->logged_id();
		if (!$joint->save()) {
			if ($joint->valid) {
				set_notice('error', _('Check that you have inputted all the required fields.'));
				log_message('error', 'add_team (joint.php): validation failed');
			}
			else {
				set_notice('error', _('Couldn\'t add team to joint for unknown reasons.'));
				log_message('error', 'add_team (joint.php): saving failed');
			}
			return false;
		}
	}

	public function remove_team($team_id) {
		$this->where('team_id', $team_id)->get();
		if (!$this->delete()) {
			set_notice('error', _('Couldn\'t remove the team from the joint.'));
			log_message('error', 'remove_team (joint.php): removing failed');
			return false;
		}
	}

	public function remove_team_from_all($team_id) {
		$joints = new Joint();
		$joints->where('team_id', $team_id)->get();
		if (!$joints->delete_all()) {
			set_notice('error', _('Couldn\'t remove the team from all the joints.'));
			log_message('error', 'remove_team_from_all (joint.php): removing failed');
			return false;
		}
	}

}

/* End of file joint.php */
/* Location: ./application/models/joint.php */
