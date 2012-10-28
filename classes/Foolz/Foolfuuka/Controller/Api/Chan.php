<?php

namespace Foolz\Foolfuuka\Controller\Api;

class Controller_Api_Chan extends \Controller_Rest
{

	protected $_radix = null;
	protected $_theme = null;
	protected $format = 'json';

	public function before()
	{
		parent::before();

		header("Access-Control-Allow-Origin: *");
		header('Access-Control-Allow-Credentials: true');
		header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
		header('Access-Control-Max-Age: 604800');

		if ( ! \Input::get('board') && ! \Input::get('action') && ! \Input::post('board') && ! \Input::post('action'))
		{
			$segments = \Uri::segments();
			$uri = \Uri::base().'_'.
				'/'.array_shift($segments).'/'.array_shift($segments).'/'.array_shift($segments).'/?';

			echo count($segments);
			foreach ($segments as $key => $segment)
			{
				if ($key % 2 == 0)
				{
					$uri .= urlencode($segment).'=';
				}
				else
				{
					$uri .= urlencode($segment).'&';
				}
			}

			\Response::redirect($uri);
		}
	}


	/**
	 * Commodity to check that the shortname is not wrong and return a coherent error
	 */
	protected function check_board()
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

		if(!$this->_radix = \Radix::setSelectedByShortname($board))
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
	public function get_thread()
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

		if ( ! ctype_digit((string) $num))
		{
			return $this->response(array('error' => __("Invalid value for 'num'.")), 404);
		}

		$num = intval($num);

		try
		{
			// build an array if we have more specifications
			if ($latest_doc_id)
			{
				if ( ! ctype_digit((string) $latest_doc_id))
				{
					return $this->response(array('error' => __("The value for 'latest_doc_id' is malformed.")), 404);
				}

				$board = \Board::forge()
					->getThread($num)
					->setRadix($this->_radix)
					->setApi(array('theme' => \Input::get('theme'), 'board' => false))
					->setOptions(array(
						'type' => 'from_doc_id',
						'latest_doc_id' => $latest_doc_id,
						'realtime' => true,
						'controller_method' =>
							ctype_digit((string) \Input::get('last_limit')) ? 'last/'.\Input::get('last_limit') : 'thread'
				));

				return $this->response($board->getComments(), 200);
			}
			else
			{
				$board = \Board::forge()
					->getThread($num)
					->setRadix($this->_radix)
					->setApi(array('theme' => \Input::get('theme'), 'board' => false))
					->setOptions(array(
						'type' => 'thread',
				));

				return $this->response($board->getComments(), 200);
			}

		}
		catch(\Foolz\Foolfuuka\Model\BoardThreadNotFoundException $e)
		{
			return $this->response(array('error' => __("Thread not found.")), 200);
		}
		catch (\Foolz\Foolfuuka\Model\BoardException $e)
		{
			return $this->response(array('error' => __("Unknown error.")), 500);
		}
	}


	public function get_post()
	{
		if (!$this->check_board())
		{
			return $this->response(array('error' => __("No board selected.")), 404);
		}

		$num = \Input::get('num');
		$theme = \Input::get('theme');

		if (!$num)
		{
			return $this->response(array('error' => __("You are missing the 'num' parameter.")), 404);
		}

		if (!\Board::isValidPostNumber($num))
		{
			return $this->response(array('error' => __("Invalid value for 'num'.")), 404);
		}

		try
		{
			$board = \Board::forge()
				->getPost($num)
				->setRadix($this->_radix)
				->setApi(array('board' => false, 'theme' => $theme));

			// no index for the single post
			$this->response(current($board->getComments()), 200);
		}
		catch (\Foolz\Foolfuuka\Model\BoardPostNotFoundException $e)
		{
			return $this->response(array('error' => __("Post not found.")), 200);
		}
		catch (\Foolz\Foolfuuka\Model\BoardException $e)
		{
			return $this->response(array('error' => $e->getMessage()), 404);
		}
	}


	public function post_user_actions()
	{
		if ( ! \Security::check_token())
		{
			return $this->response(array('error' => __('The security token wasn\'t found. Try resubmitting.')));
		}

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
			catch (\Foolz\Foolfuuka\Model\ReportException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}

			return $this->response(array('success' => __("Post reported.")), 200);
		}

		if (\Input::post('action') === 'report_media')
		{
			try
			{
				\Report::add($this->_radix, \Input::post('media_id'), \Input::post('reason'), null, 'media_id');
			}
			catch (\Foolz\Foolfuuka\Model\ReportException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}

			return $this->response(array('success' => __("Media reported.")), 200);
		}

		if (\Input::post('action') === 'delete')
		{
			try
			{
				$comments = \Board::forge()
					->getPost()
					->setOptions('doc_id', \Input::post('doc_id'))
					->setCommentOptions('clean', false)
					->setRadix($this->_radix)
					->getComments();

				$comment = current($comments);
				$comment->delete(\Input::post('password'));
			}
			catch (\Foolz\Foolfuuka\Model\BoardException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 200);
			}
			catch (\Foolz\Foolfuuka\Model\CommentDeleteWrongPassException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 200);
			}

			return $this->response(array('success' => __("Post deleted.")), 200);
		}
	}


	public function post_mod_actions()
	{
		if ( ! \Security::check_token())
		{
			return $this->response(array('error' => __('The security token wasn\'t found. Try resubmitting.')));
		}

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
			catch (\Foolz\Foolfuuka\Model\ReportException $e)
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
					->getPost()
					->setOptions('doc_id', \Input::post('id'))
					->setRadix($this->_radix)
					->getComments();

				$comment = current($comments);
				$comment->delete();
			}
			catch (\Foolz\Foolfuuka\Model\BoardException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}

			return $this->response(array('success' => __("Post deleted.")), 200);
		}

		if (\Input::post('action') === 'delete_image')
		{
			try
			{
				\Media::getByMediaId($this->_radix, \Input::post('id'))->delete(true, true, true);
			}
			catch (\Foolz\Foolfuuka\Model\MediaNotFoundException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}

			return $this->response(array('success' => __("Image deleted.")), 200);
		}

		if (\Input::post('action') === 'ban_image_local' || \Input::post('action') === 'ban_image_global')
		{
			$global = false;
			if (\Input::post('action') === 'ban_image_global')
			{
				$global = true;
			}

			try
			{
				\Media::getByMediaId($this->_radix, \Input::post('id'))->ban($global);
			}
			catch (\Foolz\Foolfuuka\Model\MediaNotFoundException $e)
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
			catch (\Foolz\Foolfuuka\Model\BanException $e)
			{
				return $this->response(array('error' => $e->getMessage()), 404);
			}

			return $this->response(array('success' => __("User banned.")), 200);
		}
	}

}