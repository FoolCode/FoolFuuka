<?php

namespace Foolfuuka\Model;

class BoardException extends \FuelException {}
class BoardThreadNotFoundException extends BoardException {}
class BoardPostNotFoundException extends BoardException {}
class BoardMalformedInputException extends BoardException {}
class BoardNotCompatibleMethodException extends BoardException {}
class BoardMissingOptionsException extends BoardException {}


/**
 * FoOlFuuka Post Model
 *
 * The Post Model deals with all the data in the board tables and
 * the media folders. It also processes the post for display.
 *
 * @package        	FoOlFrame
 * @subpackage    	FoOlFuuka
 * @category    	Models
 * @author        	FoOlRulez
 * @license         http://www.apache.org/licenses/LICENSE-2.0.html
 */
class Board extends \Model\Model_Base
{

	/**
	 * Array of Comment sorted for output
	 *
	 * @var array
	 */
	private $_comments = null;

	/**
	 * Array of Comment in a plain array
	 *
	 * @var array
	 */
	private $_comments_unsorted = null;

	/**
	 * The count of the query without LIMIT
	 *
	 * @var int
	 */
	private $_total_count = 0;

	/**
	 * The method selected to retrieve comments
	 *
	 * @var string
	 */
	private $_method_fetching = null;

	/**
	 * The method selected to retrieve the comment's count without LIMIT
	 *
	 * @var string
	 */
	private $_method_counting = null;

	/**
	 * The options to give to the retrieving method
	 *
	 * @var array
	 */
	private $_options = array();

	/**
	 * The selected Radix
	 *
	 * @var array
	 */
	private $_radix = null;


	public static function forge()
	{
		return new Board();
	}

	/**
	 * Returns the comments, and executes the query if not already executed
	 *
	 * @return array
	 */
	protected function p_get_comments()
	{
		if (is_null($this->_comments))
		{
			$this->{$this->_method_fetching}();
		}

		return $this->_comments;
	}


	/**
	 * Returns the count without LIMIT, and executes the query if not already executed
	 *
	 * @return array
	 */
	protected function p_get_count()
	{
		if (is_null($this->_total_count))
		{
			if (method_exists($this, $this->_method_counting))
			{
				$this->{$this->_method_counting}();
			}
			else
			{
				$this->_total_count = false;
			}
		}

		return $this->_total_count;
	}


	protected function p_get_pages()
	{
		return floor($this->get_count() / $this->_options['per_page']) + 1;
	}


	protected function p_get_highest($item)
	{
		$temp = $this->_comments_unsorted[0];

		foreach ($this->_comments_unsorted as $post)
		{
			if ($temp->$item < $post->$item)
			{
				$temp = $post;
			}
		}

		return $post;
	}


	protected function p_set_method_fetching($name)
	{
		$this->_method_fetching = $name;

		return $this;
	}


	protected function p_set_method_counting($name)
	{
		$this->_method_counting = $name;

		return $this;
	}


	protected function p_set_options($name, $value = null)
	{
		if (is_array($name))
		{
			foreach ($name as $key => $item)
			{
				$this->set_options($key, $item);
			}

			return $this;
		}

		$this->_options[$name] = $value;

		return $this;
	}


	protected function p_set_radix(&$radix)
	{
		$this->_radix = $radix;

		return $this;
	}


	protected function p_set_page($page)
	{
		$page = intval($page);

		if($page < 1)
		{
			throw new BoardException(__('The page number is not valid.'));
		}

		$this->set_options('page', $page);

		return $this;
	}


	public static function is_natural($str)
	{
		return ctype_digit((string) $str);
	}

	public static function is_valid_post_number($str)
	{
		if(static::is_natural($str))
		{
			return true;
		}

		return (bool) preg_match('/^[0-9]+(,|_)[0-9]+$/', $str);
	}

	public static function split_post_number($num)
	{
		if (strpos($num, ',') !== FALSE)
		{
			$arr = explode(',', $num);
		}
		else if (strpos($num, '_') !== FALSE)
		{
			$arr = explode('_', $num);
		}
		else
		{
			$result['num'] = $num;
			$result['subnum'] = 0;
			return $result;
		}

		$result['num'] = $arr[0];
		$result['subnum'] = isset($arr[1]) ? $arr[1] : 0;
		return $result;
	}


	/**
	 * Returns the SQL string to append to queries to be able to
	 * get the filenames required to create the path to media
	 *
	 * @param object $board
	 * @param bool|string $join_on alternative join table name
	 * @return string SQL to append to retrieve image filenames
	 */
	protected function p_sql_media_join($query, &$board = null, $join_on = false)
	{
		if (is_null($board))
		{
			$board = $this->_radix;
		}

		$query->join(\DB::expr(Radix::get_table($board, '_images').' AS `mg`'), 'LEFT')
			->on(\DB::expr(($join_on ? '`'.$join_on.'`' : Radix::get_table($board)).'.`media_id`'),
				'=', \DB::expr('`mg`.`media_id`'));
	}


	/**
	 * If the user is an admin, this will return SQL to add reports to the
	 * query output
	 *
	 * @param object $board
	 * @param bool|string $join_on alternative join table name
	 * @return string SQL to append reports to the rows
	 */
	protected function p_sql_report_join($query, $board = null, $join_on = false)
	{
		if (is_null($board))
		{
			$board = $this->_radix;
		}

		// only show report notifications to certain users
		if (\Auth::has_access('comment.reports'))
		{
			$query->join(\DB::expr('
					(SELECT
						id AS report_id, doc_id AS report_doc_id, reason AS report_reason, ip_reporter as report_ip_reporter,
						status AS report_status, created AS report_created
					FROM `fu_reports`
					WHERE `board_id` = '.$board->id.') AS r'),
				'LEFT'
			);
			$query->on(\DB::expr(($join_on ? '`'.$join_on.'`' : Radix::get_table($board)).'.`doc_id`'), '=', \DB::expr('`r`.`report_doc_id`'));
		}
	}


	protected function p_get_latest()
	{
		// prepare
		$this->set_method_fetching('get_latest_comments')
			->set_method_counting('get_latest_count')
			->set_options(array(
				'per_page' => 20,
				'per_thread' => 5,
				'order' => 'by_post'
			));

		return $this;
	}


	/**
	 * Get the latest
	 *
	 * @param object $board
	 * @param int $page the page to determine the offset
	 * @param array $options modifiers
	 * @return array|bool FALSE on error (likely from faulty $options), or the list of threads with 5 replies attached
	 */
	protected function p_get_latest_comments()
	{
		\Profiler::mark('Board::get_latest_comments Start');
		extract($this->_options);

		switch ($order)
		{
			case 'by_post':

				$query = \DB::select('*', \DB::expr('thread_num as unq_thread_num'))
						->from(\DB::expr(Radix::get_table($this->_radix, '_threads')))
						->order_by('time_bump', 'desc')
						->limit($per_page)->offset(($page * $per_page) - $per_page);
				break;

			case 'by_thread':

				$query = \DB::select('*', \DB::expr('thread_num as unq_thread_num'))
						->from(\DB::expr(Radix::get_table($this->_radix, '_threads')))
						->order_by('thread_num', 'desc')
						->limit($per_page)->offset(($page * $per_page) - $per_page);
				break;

			case 'ghost':

				$query = \DB::select('*', \DB::expr('thread_num as unq_thread_num'))
						->from(\DB::expr(Radix::get_table($this->_radix, '_threads')))
						->where('time_ghost_bump', '', \DB::expr('IS NOT NULL'))
						->order_by('time_ghost_bump', 'desc')
						->limit($per_page)->offset(($page * $per_page) - $per_page);
				break;
		}

		$threads = $query->as_object()->execute()->as_array();

		if (!count($threads))
		{
			$this->_comments = array();
			$this->_comments_unsorted = array();

			\Profiler::mark_memory($this, 'Board $this');
			\Profiler::mark('Board::get_latest_comments End Prematurely');
			return $this;
		}

		// populate arrays with posts
		$threads_arr = array();
		$sql_arr = array();

		foreach ($threads as $thread)
		{
			$threads_arr[$thread->unq_thread_num] = array('replies' => $thread->nreplies, 'images' => $thread->nimages);

			$temp = \DB::select()->from(\DB::expr(Radix::get_table($this->_radix)));
			static::sql_media_join($temp);
			static::sql_report_join($temp);
			$temp->where('thread_num', $thread->unq_thread_num)
				->order_by('op', 'desc')->order_by('num', 'desc')->order_by('subnum', 'desc')
				->limit($per_thread + 1)->offset(0);

			$sql_arr[] = '('.$temp.')';
		}

		$query_posts = \DB::query(implode(' UNION ', $sql_arr), \DB::SELECT)->as_object()->execute()->as_array();
		// populate posts_arr array
		$this->_comments_unsorted = Comment::forge($query_posts, $this->_radix);
		\Profiler::mark_memory($this->_comments_unsorted, 'Board $this->_comments_unsorted');
		$results = array();

		foreach ($threads as $thread)
		{
			$results[$thread->thread_num] = array(
				'omitted' => ($thread->nreplies - ($per_thread + 1)),
				'images_omitted' => ($thread->nimages - 1)
			);
		}

		// populate results array and order posts
		foreach ($this->_comments_unsorted as $post)
		{
			if ($post->op == 0)
			{
				if ($post->preview_orig)
				{
					$results[$post->thread_num]['images_omitted']--;
				}

				if (!isset($results[$post->thread_num]['posts']))
					$results[$post->thread_num]['posts'] = array();

				array_unshift($results[$post->thread_num]['posts'], $post);
			}
			else
			{
				$results[$post->thread_num]['op'] = $post;
			}
		}

		$this->_comments = $results;

		\Profiler::mark_memory($this->_comments, 'Board $this->_comments');
		\Profiler::mark_memory($this, 'Board $this');
		\Profiler::mark('Board::get_latest_comments End');
		return $this;
	}


	protected function p_get_latest_count()
	{
		\Profiler::mark('Board::get_latest_count Start');
		extract($this->_options);

		$type_cache = 'thread_num';

		if ($order == 'ghost')
		{
			$type_cache = 'ghost_num';
		}

		switch ($type)
		{
			// these two are the same
			case 'by_post':
			case 'by_thread':
				$query_threads = \DB::select(\DB::expr('COUNT(thread_num) AS threads'))
						->from(\DB::expr(Radix::get_table($this->_radix, '_threads')))->cached(300);
				break;

			case 'ghost':
				$query_threads = \DB::select(\DB::expr('COUNT(thread_num) AS threads'))
						->from(\DB::expr(Radix::get_table($this->_radix, '_threads')))
						->where('time_ghost_bump', \DB::expr('IS NOT NULL'))->cached(300);
				break;
		}

		$this->_total_count = $query_threads->as_object()->execute()->current()->threads;

		\Profiler::mark_memory($this, 'Board $this');
		\Profiler::mark('Board::get_latest_count End');
		return $this;
	}


	protected function p_get_threads()
	{
		// prepare
		$this->set_method_fetching('get_threads_comments')
			->set_method_counting('get_threads_count')
			->set_options(array(
				'per_page' => 20,
				'order' => 'by_post'
			));

		return $this;
	}


	protected function p_get_threads_comments()
	{
		\Profiler::mark('Board::get_threads_comments Start');
		extract($this->_options);

		$inner_query =  \DB::select('*', \DB::expr('thread_num as unq_thread_num'))
			->from(\DB::expr(Radix::get_table($this->_radix, '_threads')))
			->order_by('time_op', 'desc')->limit($per_page)->offset(($page * $per_page) - $per_page);

		$query = \DB::select()->from(\DB::expr('('.$inner_query.') AS t'))
			->join(\DB::expr(Radix::get_table($this->_radix).' AS g'), 'LEFT')
			->on(\DB::expr('g.num'), '=', \DB::expr('t.unq_thread_num AND g.subnum = 0'));

		static::sql_media_join($query, null, 'g');
		static::sql_report_join($query, null, 'g');

		$result = $query->as_object()->execute()->as_array();

		if(!count($result))
		{
			\Profiler::mark_memory($this, 'Board $this');
			\Profiler::mark('Board::get_threads_comments End Prematurely');
			return array();
		}

		$this->_comments_unsorted = Comment::forge($result, $this->_radix);
		$this->_comments = $this->_comments_unsorted;

		\Profiler::mark_memory($this->_comments, 'Board $this->_comments');
		\Profiler::mark_memory($this, 'Board $this');
		\Profiler::mark('Board::get_threads_comments End');
		return $this;
	}


	protected function p_get_threads_count()
	{
		extract($this->_options);

		$query = \DB::select(\DB::expr('COUNT(thread_num) AS threads'))
			->from(\DB::expr(Radix::get_table($this->_radix, '_threads')))->cached(300);

		$this->_total_count = $query_threads->as_object()->execute()->current()->threads;

		return $this;
	}


	/**
	 * Get the thread
	 * Deals also with "last_x", and "from_doc_id" for realtime updates
	 *
	 * @param object $board
	 * @param int $num thread number
	 * @param array $options modifiers
	 * @return array|bool FALSE on failure (probably caused by faulty $options) or the thread array
	 */
	protected function p_get_thread($num)
	{
		// default variables
		$this->set_method_fetching('get_thread_comments')
			->set_options(array('type' => 'thread', 'realtime' => false));

		if(!static::is_natural($num) || $num < 1)
		{
			throw new BoardMalformedInputException(__('The thread number is invalid.'));
		}

		$this->set_options('num', $num);

		return $this;
	}

	protected function p_get_thread_comments()
	{
		\Profiler::mark('Board::get_thread_comments Start');
		extract($this->_options);

		// determine type
		switch ($type)
		{
			case 'from_doc_id':
				$query = \DB::select()->from(\DB::expr(Radix::get_table($this->_radix)));
				static::sql_media_join($query);
				static::sql_report_join($query);
				$query->where('thread_num', $num)->where('doc_id', '>', $latest_doc_id)
					->order_by('num', 'asc')->order_by('subnum', 'asc');
				break;

			case 'ghosts':
				$query = \DB::select()->from(\DB::expr(Radix::get_table($this->_radix)));
				static::sql_media_join($query);
				static::sql_report_join($query);
				$query->where('thread_num', $num)->where('subnum', '<>', 0)
					->order_by('num', 'asc')->order_by('subnum', 'asc');
				break;

			case 'last_x':
				$query = \DB::select()->from(\DB::expr('
					(
						('.\DB::select()->from(\DB::expr(Radix::get_table($this->_radix)))->where('num',
							$num)->limit(1).')
						UNION
						('.\DB::select()->from(\DB::expr(Radix::get_table($this->_radix)))->where('thread_num',
								$num)
							->order_by('num', 'desc')->order_by('subnum', 'desc')->limit($last_limit).')
					) AS x
				'));
				static::sql_media_join($query,null, 'x');
				static::sql_report_join($query, null, 'x');
				$query->order_by('num', 'asc')->order_by('subnum', 'asc');
				break;

			case 'thread':
				$query = \DB::select()->from(\DB::expr(Radix::get_table($this->_radix)));
				static::sql_media_join($query);
				static::sql_report_join($query);
				$query->where('thread_num', $num)->order_by('num', 'asc')->order_by('subnum', 'asc');
				break;
		}

		$query_result = $query->as_object()->execute()->as_array();

		if (!count($query_result))
		{
			throw new BoardThreadNotFoundException(__('There\'s no such a thread.'));
		}

		$this->_comments_unsorted =
			Comment::forge($query_result, $this->_radix, array('realtime' => $realtime, 'backlinks_hash_only_url' => true));

		// process entire thread and store in $result array
		$result = array();

		foreach ($this->_comments_unsorted as $post)
		{
			if ($post->op == 0)
			{
				$result[$post->thread_num]['posts'][$post->num.(($post->subnum == 0) ? '' : '_'.$post->subnum)] = $post;
			}
			else
			{
				$result[$post->num]['op'] = $post;
			}
		}

		/*

		  // populate results with backlinks
		  foreach ($this->backlinks as $key => $backlinks)
		  {
		  if (isset($result[$num]['op']) && $result[$num]['op']->num == $key)
		  {
		  $result[$num]['op']->backlinks = array_unique($backlinks);
		  }
		  else if (isset($result[$num]['posts'][$key]))
		  {
		  $result[$num]['posts'][$key]->backlinks = array_unique($backlinks);
		  }
		  }
		 *
		 *
		 */

		$this->_comments = $result;

		\Profiler::mark_memory($this->_comments, 'Board $this->_comments');
		\Profiler::mark_memory($this, 'Board $this');
		\Profiler::mark('Board::get_thread_comments End');
		return $this;
	}


	/**
	 * Return the status of the thread to determine if it can be posted in, or if images can be posted
	 * or if it's a ghost thread...
	 *
	 * @param object $board
	 * @param mixed $num if you send a $query->result() of a thread it will avoid another query
	 * @return array statuses of the thread
	 */
	protected function p_check_thread_status()
	{
		if ($this->_method_fetching != 'get_thread_comments')
		{
			throw new BoardNotCompatibleMethodException;
		}

		// define variables to override
		$thread_op_present = false;
		$ghost_post_present = false;
		$thread_last_bump = 0;
		$counter = array('posts' => 0, 'images' => 0);

		foreach ($this->_comments_unsorted as $post)
		{
			// we need to find if there's the OP in the list
			// let's be strict, we want the $num to be the OP
			if ($post->op == 1)
			{
				$thread_op_present = true;
			}

			if ($post->subnum > 0)
			{
				$ghost_post_present = true;
			}

			if ($post->subnum == 0 && $thread_last_bump < $post->timestamp)
			{
				$thread_last_bump = $post->timestamp;
			}

			if ($post->media_filename)
			{
				$counter['images']++;
			}

			$counter['posts']++;
		}

		// we didn't point to the thread OP, this is not a thread
		if (!$thread_op_present)
		{
			// this really should not happen here
			throw new BoardThreadNotFoundException;
		}

		$result = array(
			'dead' => (bool) $this->_radix->archive,
			'disable_image_upload' => (bool) $this->_radix->archive,
		);

		// time check
		if (time() - $thread_last_bump > 432000 || $ghost_post_present)
		{
			$result['dead'] = true;
			$result['disable_image_upload'] = true;
		}

		if ($counter['posts'] > $this->_radix->max_posts_count)
		{
			if ($counter['images'] > $this->_radix->max_images_count)
			{
				$result['dead'] = true;
				$result['disable_image_upload'] = true;
			}
			else
			{
				$result['dead'] = true;
			}
		}
		else if ($counter['images'] > $this->_radix->max_images_count)
		{
			$result['disable_image_upload'] = true;
		}

		return $result;
	}


	protected function p_get_post()
	{
		// default variables
		$this->set_method_fetching('get_post_comments');

		return $this;
	}


	protected function p_get_post_comments()
	{
		extract($this->_options);

		$query = \DB::select()->from(\DB::expr(Radix::get_table($this->_radix)));

		if (isset($this->_options['num']))
		{
			if(!static::is_valid_post_number($this->_options['num']))
			{
				throw new BoardMalformedInputException;
			}
			$num_arr = static::split_post_number($this->_options['num']);
			$query->where('num' , $num_arr['num'])->where('subnum', $num_arr['subnum']);
		}
		else if (isset($this->_options['doc_id']))
		{
			$query->where('doc_id', $this->_options['doc_id']);
			break;
		}
		else
		{
			throw new BoardMissingOptionsException;
		}

		$result = $query->as_object()->execute()->as_array();

		if(!count($result))
		{
			throw new BoardPostNotFoundException;
		}

		$this->_comments_unsorted = Comment::forge($result, $this->_radix);
		$this->_comments = $this->_comments_unsorted;

		return $this;
	}


}