<?php

namespace Foolfuuka;

class Controller_Api_Chan extends \Controller_Rest
{
	
	protected $_radix = null;
	protected $format = 'json';
	
	public function before()
	{
		parent::before();
		if(isset($_SERVER['https']) && $_SERVER['https'] == 'on')
			header("Access-Control-Allow-Origin: https://boards.4chan.org");
		else
			header("Access-Control-Allow-Origin: http://boards.4chan.org");
		
		header('Access-Control-Allow-Credentials: true');	
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Max-Age: 604800');
	}
	
	
	/**
	 * Commodity to check that the ID is not wrong and return a coherent error
	 * 
	 * @author Woxxy
	 */
	function check_board()
	{
		$board = \Input::get('board');

		if (!$board) 
		{
			$board = \Input::post('board');
		}
		
		if (!$board)
		{
			//$this->response(array('error' => __('You didn\'t select a board')), 404);
			return false;
		}
			
		if(!$this->_radix = \Radix::set_selected_by_shortname($board))
		{
			//$this->response(array('error' => __('The board you selected doesn\'t exist')), 404);
			return false;
		}
		
		return true;
	}
	
	
	/**
	 * Returns a thread
	 *
	 * Available filters: num (required)
	 *
	 * @author Woxxy
	 */
	function get_thread()
	{
		if (!$this->check_board())
		{
			return $this->response(array('error' => __("No board selected.")), 404);;
		}

		$num = \Input::get('num');
		$latest_doc_id = \Input::get('latest_doc_id');
		
		if (!$num)
		{
			return $this->response(array('error' => __("You are missing the 'num' parameter.")), 404);
		}
		
		if (!\Board::is_natural($num))
		{
			return $this->response(array('error' => __("Invalid value for 'num'.")), 404);
		}

		$num = intval($num);

		try
		{
			// build an array if we have more specifications
			if ($latest_doc_id)
			{
				if (!\Board::is_natural($latest_doc_id))
				{
					return $this->response(array('error' => __("The value for 'latest_doc_id' is malformed.")), 404);
				}

				$board = \Board::forge()
					->get_thread($num)
					->set_radix($this->_radix)
					->set_api(array('formatted' => true, 'board' => false))
					->set_options(array(
						'type' => 'from_doc_id',
						'latest_doc_id' => $latest_doc_id,
						'realtime' => true
				));

				return $this->response($board->get_comments(), 200);
			}
			else
			{
				$board = \Board::forge()
					->get_thread($num)
					->set_radix($this->_radix)
					->set_api(array('formatted' => true, 'board' => false))
					->set_options(array(
						'type' => 'thread',
				));

				return $this->response($board->get_comments(), 200);
			}
				
		}
		catch(Model\BoardThreadNotFoundException $e)
		{
			return $this->response(array('error' => __("Thread not found.")), 404);
		}
		catch (Model\BoardException $e)
		{
			return $this->response(array('error' => __("Unknown error.")), 500);
		}
	}


	function get_post()
	{
		if (!$this->check_board())
		{
			return $this->response(array('error' => __("No board selected.")), 404);;
		}

		$num = \Input::get('num');

		if (!$num)
		{
			return $this->response(array('error' => __("You are missing the 'num' parameter.")), 404);
		}
		
		if (!\Board::is_natural($num))
		{
			return $this->response(array('error' => __("Invalid value for 'num'.")), 404);
		}
		
		try
		{
			$board = \Board::forge()
				->get_post($num)
				->set_radix($this->_radix)
				->set_api(array('formatted' => true, 'board' => false));

			$this->response($board->get_comments(), 200);
		}
		catch(Model\BoardThreadNotFoundException $e)
		{
			return $this->response(array('error' => __("Thread not found.")), 404);
		}
		catch (Model\BoardException $e)
		{
			return $this->response(array('error' => __("Unknown error.")), 500);
		}
	}
	
	
	function post_user_actions()
	{
		if ( ! $this->check_board())
		{
			return $this->response(array('error' => __("No board selected.")), 404);
		}
		
		if (\Input::post('action') === 'report')
		{
			try
			{
				\Report::add($this->_radix, \Input::post('doc_id'), \Input::post('reason'));
			}
			catch (Model\ReportException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}
			
			return $this->response(array('success' => __("Post reported.")), 200);;
		}
		
		if (\Input::post('action') === 'delete')
		{
			try
			{
				$comments = \Board::forge()
					->get_post()
					->set_options('doc_id', \Input::post('doc_id'))
					->set_radix($this->_radix)
					->get_comments();

				$comment = $comments[0];
			}
			catch (Model\BoardException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}
			
			try
			{
				$comment->delete(\Input::post('password'));
			}
			catch (Model\BoardException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}
			
			return $this->response(array('success' => __("Post deleted.")), 200);;
		}
	}

}