<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Reader extends REST_Controller
{
	/**
	 * Returns 100 comics from selected page
	 * 
	 * Available filters: page, per_page (default:30, max:100), orderby
	 * 
	 * @author Woxxy
	 */
	function comics_get()
	{
		$comics = new Comic();

		// filter with orderby
		$this->_orderby($comics);
		// use page_to_offset function
		$this->_page_to_offset($comics);


		$comics->get();

		if ($comics->result_count() > 0)
		{
			$result = array();
			foreach ($comics->all as $key => $comic)
			{
				$result['comics'][$key] = $comic->to_array();
			}
			$this->response($result, 200); // 200 being the HTTP response code
		}
		else
		{
			// no comics
			$this->response(array('error' => _('Comics could not be found')), 404);
		}
	}


	/**
	 * Returns the comic
	 * 
	 * Available filters: id (required)
	 * 
	 * @author Woxxy
	 */
	function comic_get()
	{
		if ($this->get('id'))
		{
			//// check that the id is at least a valid number
			$this->_check_id();

			// get the comic
			$comic = new Comic();
			$comic->where('id', $this->get('id'))->limit(1)->get();
		}
		else if ($this->get('stub'))
		{ // mostly used for load balancer
			$comic = new Comic();
			$comic->where('stub', $this->get('stub'));
			// back compatibility with version 0.7.6, though stub is already an unique key
			if ($this->get('uniqid'))
				$comic->where('uniqid', $this->get('uniqid'));
			$comic->limit(1)->get();
		}
		else
		{
			$this->response(array('error' => _('You didn\'t use the necessary parameters')), 404);
		}

		if ($comic->result_count() == 1)
		{
			$chapters = new Chapter();
			$chapters->where('comic_id', $comic->id)->get();
			$chapters->get_teams();
			$result = array();

			$result["comic"] = $comic->to_array();

			// order in the beautiful [comic][chapter][teams][page]
			$result["chapters"] = array();
			foreach ($chapters->all as $key => $chapter)
			{
				$result['chapters'][$key]['comic'] = $result["comic"];
				$result['chapters'][$key]['chapter'] = $chapter->to_array();

				// if it's requested, throw in also the pages (for load balancer)
				if ($this->get('chapter_stub') == $chapter->stub
						&& $this->get('chapter_uniqid') == $chapter->uniqid)
				{
					$pages = new Page();
					$pages->where('chapter_id', $chapter->id)->get();
					$result["chapters"][$key]["chapter"]["pages"] = $pages->all_to_array();
				}



				// teams is a normal array, can't use $team->all_to_array()
				foreach ($chapter->teams as $team)
				{
					$result['chapters'][$key]['teams'][] = $team->to_array();
				}
			}

			// all good
			$this->response($result, 200); // 200 being the HTTP response code
		}
		else
		{
			// there's no comic with that id
			$this->response(array('error' => _('Comic could not be found')), 404);
		}
	}


	/**
	 * Returns chapters from selected page
	 * 
	 * Available filters: page, per_page (default:30, max:100), orderby
	 * 
	 * @author Woxxy
	 */
	function chapters_get()
	{
		$chapters = new Chapter();

		// filter with orderby
		$this->_orderby($chapters);
		// use page_to_offset function
		$this->_page_to_offset($chapters);


		// get the generic chapters and the comic coming with them
		$chapters->get();
		$chapters->get_comic();

		if ($chapters->result_count() > 0)
		{

			// let's create a pretty array of chapters [comic][chapter][teams]
			$result['chapters'] = array();
			foreach ($chapters->all as $key => $chapter)
			{
				$result['chapters'][$key]['comic'] = $chapter->comic->to_array();
				$result['chapters'][$key]['chapter'] = $chapter->to_array();
				$chapter->get_teams();
				foreach ($chapter->teams as $item)
				{
					$result['chapters'][$key]['teams'][] = $item->to_array();
				}
			}

			// all good
			$this->response($result, 200); // 200 being the HTTP response code
		}
		else
		{
			// no comics
			$this->response(array('error' => _('Comics could not be found')), 404);
		}
	}


	/**
	 * Returns the chapter
	 * 
	 * Available filters: id (required)
	 *
	 * @author Woxxy
	 */
	function chapter_get()
	{
		if (	($this->get('comic_stub'))
				|| is_numeric($this->get('comic_id')) 
				|| is_numeric($this->get('volume')) 
				|| is_numeric($this->get('chapter')) 
				|| is_numeric($this->get('subchapter'))
				|| is_numeric($this->get('team_id'))
				|| is_numeric($this->get('joint_id'))
		)
		{
			$chapter = new Chapter();

			if(($this->get('comic_stub')))
			{
				$chapter->where_related('comic', 'stub', $this->get('comic_stub'));
			}
			
			// this mess is a complete search system through integers!
			if (is_numeric($this->get('comic_id')))
				$chapter->where('comic_id', $this->get('comic_id'));
			if (is_numeric($this->get('volume')))
				$chapter->where('volume', $this->get('volume'));
			if (is_numeric($this->get('chapter')))
				$chapter->where('chapter', $this->get('chapter'));
			if (is_numeric($this->get('subchapter')))
				$chapter->where('subchapter', $this->get('subchapter'));
			if (is_numeric($this->get('team_id')))
				$chapter->where('team_id', $this->get('team_id'));
			if (is_numeric($this->get('joint_id')))
				$chapter->where('joint_id', $this->get('joint_id'));
			
			// and we'll still give only one result
			$chapter->limit(1)->get();
		}
		else
		{
			// check that the id is at least a valid number
			$this->_check_id();

			$chapter = new Chapter();
			// get the single chapter by id
			$chapter->where('id', $this->get('id'))->limit(1)->get();
		}


		if ($chapter->result_count() == 1)
		{
			$chapter->get_comic();
			$chapter->get_teams();

			// the pretty array gets pages too: [comic][chapter][teams][pages]
			$result = array();
			$result['comic'] = $chapter->comic->to_array();
			$result['chapter'] = $chapter->to_array();
			$result['teams'] = array();
			foreach ($chapter->teams as $team)
			{
				$result['teams'][] = $team->to_array();
			}

			// this time we get the pages
			$result['pages'] = $chapter->get_pages();

			// all good
			$this->response($result, 200); // 200 being the HTTP response code
		}
		else
		{
			// the chapter with that id doesn't exist
			$this->response(array('error' => _('Chapter could not be found')), 404);
		}
	}


	/**
	 * Returns chapters per page from team ID
	 * Includes releases from joints too
	 * 
	 * This is NOT a method light enough to lookup teams. use api/members/team for that
	 * 
	 * Available filters: id (required), page, per_page (default:30, max:100), orderby
	 * 
	 * @author Woxxy
	 */
	function team_get()
	{
		// get the single team by id or stub
		if ($this->get('stub'))
		{
			$team = new Team();
			$team->where('stub', $this->get('stub'))->limit(1)->get();
		}
		else
		{
			// check that the id is at least a valid number
			$this->_check_id();
			$team = new Team();
			$team->where('id', $this->get('id'))->limit(1)->get();
		}

		// team found?
		if ($team->result_count() == 1)
		{
			$result = array();
			$result['team'] = $team->to_array();

			// get joints to get also the chapters from joints
			$joints = new Joint();
			$joints->where('team_id', $team->id)->get();


			$chapters = new Chapter();
			// get all chapters with the team ID
			$chapters->where('team_id', $team->id);
			foreach ($joints->all as $joint)
			{
				// get also all chapters with the joints by the team
				$chapters->or_where('joint_id', $joint->joint_id);
			}

			// filter for the page and the order
			$this->_orderby($chapters);
			$this->_page_to_offset($chapters);
			$chapters->get();
			$chapters->get_comic();

			// let's save some power by reusing the variables we already have for team
			// and put everything in the usual clean [comic][chapter][teams]
			$result['chapters'] = array();
			foreach ($chapters->all as $key => $chapter)
			{
				if (!$chapter->team_id)
				{
					$chapter->get_teams();
					foreach ($chapter->teams as $item)
					{
						$result['chapters'][$key]['teams'][] = $item->to_array();
					}
				}
				else
				{
					$result['chapters'][$key]['teams'][] = $team->to_array();
				}
				$result['chapters'][$key]['comic'] = $chapter->comic->to_array();
				$result['chapters'][$key]['chapter'] = $chapter->to_array();
			}

			// all good
			$this->response($result, 200); // 200 being the HTTP response code
		}
		else
		{
			// that single team id wasn't found
			$this->response(array('error' => _('Team could not be found')), 404);
		}
	}


	/**
	 * Returns chapters per page by joint ID
	 * Also returns the teams
	 * 
	 * This is not a method light enough to lookup teams. use api/members/joint for that
	 * 
	 * Available filters: id (required), page, per_page (default:30, max:100), orderby
	 * 
	 * @author Woxxy
	 */
	function joint_get()
	{
		// check that the id is at least a valid number
		$this->_check_id();

		// grab by joint_id, id for joints means nothing much
		$joint = new Joint();
		$joint->where('joint_id', $this->get('id'))->limit(1)->get();

		if ($joint->result_count() == 1)
		{
			// good old get_teams() will give us all Team objects in an array
			$team = new Team();
			$teams = $team->get_teams(0, $this->get('id'));

			// $teams is a normal array, so we have to do a loop
			$result = array();
			foreach ($teams as $item)
			{
				$result['teams'][] = $item->to_array();
			}

			// grab all the chapters from the same joint
			$chapters = new Chapter();
			$chapters->where('joint_id', $joint->joint_id);

			// apply the limit and orderby filters
			$this->_orderby($chapters);
			$this->_page_to_offset($chapters);
			$chapters->get();
			$chapters->get_comic();

			// let's put the chapters in a nice [comic][chapter][teams] list
			$result['chapters'] = array();
			foreach ($chapters->all as $key => $chapter)
			{
				$result['chapters'][$key]['comic'] = $chapter->comic->to_array();
				$result['chapters'][$key]['chapter'] = $chapter->to_array();
				$result['chapters'][$key]['teams'] = $result['teams'];
			}

			// all good
			$this->response($result, 200); // 200 being the HTTP response code
		}
		else
		{
			// nothing for this joint or page
			$this->response(array('error' => _('Team could not be found')), 404);
		}
	}


}