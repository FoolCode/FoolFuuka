<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Members extends REST_Controller {

	/*
	 * Returns the teams, paged by 100
	 * 
	 * Available filters: page, per_page (default:30, max:100), orderby
	 *
	 * @author Woxxy
	 */
	function teams_get() {
		$teams = new Team();
		// filter by page and orderby
		$this->_orderby($teams);
		$this->_page_to_offset($teams);
		$teams->get();

		// any team was found?
		if ($teams->result_count() > 0) {
			$result = array();
			foreach($teams->all as $team){
				$result[] = $team->to_array();
			}
			
			// all good
			$this->response($result, 200); // 200 being the HTTP response code
		} else {
			// no team found
			$this->response(array('error' => _('Teams could not be found')), 404);
		}
	}
	
	/*
	 * Returns the team	
	 *
 	 * Available filters: id (required)
	 * 
	 * @author Woxxy
	 */
	function team_get() {
		// check that the id is at least a valid number
		$this->_check_id();

		// get the single team by id
		$team = new Team();
		$team->where('id', $this->get('id'))->limit(1)->get();

		if ($team->result_count() == 1) {
			$result = $team->to_array();
			$members = new Membership();
			
			// get members gives an actual object with ->profile_othervariable
			$memb = $members->get_members($team->id);
			
			// we have to select the user array manually because... we don't want to expose password hashes
			foreach($memb->all as $key => $mem) {
				$result['members'][$key] = $mem->to_array(array('id','username'));
				$result['members'][$key]['display_name'] = $mem->profile_display_name;
				$result['members'][$key]['twitter'] = $mem->profile_twitter;
				$result['members'][$key]['bio'] = $mem->profile_bio;
			}
			
			// all good
			$this->response($result, 200); // 200 being the HTTP response code
		} else {
			// no team for this id
			$this->response(array('error' => _('Team could not be found')), 404);
		}
	}
	
	/*
	 * Returns the teams related to the joint
	 * 
	 * Available filters: id (required)
	 * 
	 * @author Woxxy
	 */
	function joint_get() {
		// check that the id is at least a valid number
		$this->_check_id();

		// get the single team by id
		$team = new Team();
		$teams = $team->get_teams(0, $this->get('id'));

		if (count($teams) > 0) {
			$result = array();
			// teams is a normal array, can't use all_to_array()
			foreach($teams as $item) {
				$result[] = $item->to_array();
			}
			$this->response($result, 200); // 200 being the HTTP response code
		} else {
			// no team found
			$this->response(array('error' => _('Team could not be found')), 404);
		}
	}

}