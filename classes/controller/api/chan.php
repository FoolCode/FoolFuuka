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
	 * Commodity to check that the shortname is not wrong and return a coherent error
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
			return $this->response(array('error' => __("No board selected.")), 404);
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
			return $this->response(array('error' => __("No board selected.")), 404);
		}

		$num = \Input::get('num');

		if (!$num)
		{
			return $this->response(array('error' => __("You are missing the 'num' parameter.")), 404);
		}
		
		if (!\Board::is_valid_post_number($num))
		{
			return $this->response(array('error' => __("Invalid value for 'num'.")), 404);
		}
		
		try
		{
			$board = \Board::forge()
				->get_post($num)
				->set_radix($this->_radix)
				->set_api(array('formatted' => true, 'board' => false));

			// no index for the single post
			$this->response(current($board->get_comments()), 200);
		}
		catch(Model\BoardPostNotFoundException $e)
		{
			return $this->response(array('error' => __("Post not found.")), 200);
		}
		catch (Model\BoardException $e)
		{
			return $this->response(array('error' => $e->getMessage()), 404);
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

				$comment = current($comments);
				$comment->delete(\Input::post('password'));
			}
			catch (Model\BoardException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}
			
			return $this->response(array('success' => __("Post deleted.")), 200);
		}
	}
	
	
	function post_mod_actions()
	{
		if ( ! \Auth::has_access('comment.mod_capcode'))
		{
			return $this->response(array('error' => __("Forbidden.")), 403);
		}
		
		if ( ! $this->check_board())
		{
			return $this->response(array('error' => __("No board selected.")), 404);
		}
		
		if (\Input::post('action') === 'delete_report')
		{
			try
			{
				\Report::delete(\Input::post('id'));
			}
			catch (Model\Report\ReportException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}
			
			return $this->response(array('success' => __("Report deleted.")), 200);
		}
		
		if (\Input::post('action') === 'delete_post')
		{
			try
			{
				$comments = \Board::forge()
					->get_post()
					->set_options('doc_id', \Input::post('id'))
					->set_radix($this->_radix)
					->get_comments();

				$comment = current($comments);
				$comment->delete();
			}
			catch (Model\BoardException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}
			
			return $this->response(array('success' => __("Post deleted.")), 200);
		}
		
		if (\Input::post('action') === 'delete_image')
		{
			try
			{
				\Media::get_by_media_id($this->_radix, \Input::post('id'))->delete();
			}
			catch (Model\MediaNotFoundException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}
			
			return $this->response(array('success' => __("Image deleted.")), 200);
		}
		
		if (\Input::post('action') === 'ban_image')
		{
			try
			{
				\Media::get_by_media_id($this->_radix, \Input::post('id'))->ban();
			}
			catch (Model\MediaNotFoundException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}
			
			return $this->response(array('success' => __("Image banned.")), 200);
		}
		
		if (\Input::post('action') === 'ban_user')
		{
			try
			{
				\Ban::add(\Inet::ptod(\Input::post('ip')), 
					\Input::post('reason'),
					\Input::post('length'),
					\Input::post('board_ban') === 'global' ? array() : array($this->_radix->id)
				);
			}
			catch (Model\BanException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}
			
			return $this->response(array('success' => __("User banned.")), 200);
		}
	}

}