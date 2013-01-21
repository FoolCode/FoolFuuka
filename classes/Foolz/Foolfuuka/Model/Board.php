<?php

namespace Foolz\Foolfuuka\Model;

use \Foolz\Foolframe\Model\DoctrineConnection as DC;
use \Foolz\Cache\Cache;

class BoardException extends \Exception {}
class BoardThreadNotFoundException extends BoardException {}
class BoardPostNotFoundException extends BoardException {}
class BoardMalformedInputException extends BoardException {}
class BoardNotCompatibleMethodException extends BoardException {}
class BoardMissingOptionsException extends BoardException {}

/**
 * Deals with all the data in the board tables
 */
class Board
{
	use \Foolz\Plugin\PlugSuit;

	/**
	 * Array of Comment sorted for output
	 *
	 * @var  \Foolz\Foolfuuka\Model\Comment[]
	 */
	protected $comments = null;

	/**
	 * Array of Comment in a plain array
	 *
	 * @var  \Foolz\Foolfuuka\Model\Comment[]
	 */
	protected $comments_unsorted = null;

	/**
	 * The count of the query without LIMIT
	 *
	 * @var  int
	 */
	protected $total_count = null;

	/**
	 * The method selected to retrieve comments
	 *
	 * @var  string
	 */
	protected $method_fetching = null;

	/**
	 * The method selected to retrieve the comment's count without LIMIT
	 *
	 * @var  string
	 */
	protected $method_counting = null;

	/**
	 * The options to give to the retrieving method
	 *
	 * @var  array
	 */
	protected $options = [];

	/**
	 * The options to give to the Comment class
	 *
	 * @var  array
	 */
	protected $comment_options = [];

	/**
	 * The selected Radix
	 *
	 * @var  \Foolz\Foolfuuka\Model\Radix
	 */
	protected $radix = null;

	/**
	 * The options for the API. If null it means we're not using API mode
	 *
	 * @var  array
	 */
	protected $api = null;

	/**
	 * Creates a new instance of Comment
	 *
	 * @return  \Foolz\Foolfuuka\Model\Comment
	 */
	public static function forge()
	{
		return new static();
	}

	/**
	 * Returns the comments, and executes the query if not already executed
	 *
	 * @return  \Foolz\Foolfuuka\Model\Comment[]  The array of comment objects
	 */
	protected function p_getComments()
	{
		if ($this->comments === null)
		{
			if (method_exists($this, 'p_'.$this->method_fetching))
			{
				\Profiler::mark('Start Board::getcomments() with method '.$this->method_fetching);
				\Profiler::mark_memory($this, 'Start Board::getcomments() with method '.$this->method_fetching);

				$this->{$this->method_fetching}();

				\Profiler::mark('End Board::getcomments() with method '.$this->method_fetching);
				\Profiler::mark_memory($this, 'End Board::getcomments() with method '.$this->method_fetching);
			}
			else
			{
				$this->comments = false;
			}
		}

		return $this->comments;
	}

	/**
	 * Returns the count without LIMIT, and executes the query if not already executed
	 *
	 * @return  int  The count
	 */
	protected function p_getCount()
	{
		if ($this->total_count === null)
		{
			if (method_exists($this, 'p_'.$this->method_counting))
			{
				\Profiler::mark('Start Board::getCount() with method '.$this->method_counting);
				\Profiler::mark_memory($this, 'Start Board::getCount() with method '.$this->method_counting);

				$this->{$this->method_counting}();

				\Profiler::mark('End Board::getCount() with method '.$this->method_counting);
				\Profiler::mark_memory($this, 'End Board::getCount() with method '.$this->method_counting);
			}
			else
			{
				$this->total_count = false;
			}
		}

		return $this->total_count;
	}

	/**
	 * Returns the number of pages of the result
	 *
	 * @return  int  The number of pages
	 */
	protected function p_getPages()
	{
		return floor($this->getCount() / $this->options['per_page']) + 1;
	}

	/**
	 * Returns the comment with the highest item
	 *
	 * @param  string  $key  The key (column) on which to calculate the "highest" Comment
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board
	 */
	protected function p_getHighest($key)
	{
		$temp = $this->comments_unsorted[0];

		foreach ($this->comments_unsorted as $comment)
		{
			if ($temp->$key < $comment->$key)
			{
				$temp = $comment;
			}
		}

		return $temp;
	}

	/**
	 * Sets the fetching method
	 *
	 * @param  string  $name  The method name of the fetching method, without p_
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_setMethodFetching($name)
	{
		$this->method_fetching = $name;

		return $this;
	}

	/**
	 * Sets the counting method
	 *
	 * @param  string  $name  The method name of the counting method, without p_
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_setMethodCounting($name)
	{
		$this->method_counting = $name;

		return $this;
	}

	/**
	 * Set the options to pass to the counting and fetching methods. These are usually exploded in the method.
	 *
	 * @param  string|array  $name   The name of the variable, if associative array it will be used instead
	 * @param  mixed         $value  The value of the variable
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	public function setOptions($name, $value = null)
	{
		if (is_array($name))
		{
			foreach ($name as $key => $item)
			{
				$this->setOptions($key, $item);
			}

			return $this;
		}

		$this->options[$name] = $value;

		return $this;
	}

	/**
	 * Set the options to pass to the Comment object
	 *
	 * @param  string|array  $name   The name of the variable, if associative array it will be used instead
	 * @param  mixed         $value  The value of the variable
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_setCommentOptions($name, $value = null)
	{
		if (is_array($name))
		{
			foreach ($name as $key => $item)
			{
				$this->setCommentOptions($key, $item);
			}

			return $this;
		}

		$this->comment_options[$name] = $value;

		return $this;
	}

	/**
	 * Sets the Radix object
	 *
	 * @param  \Foolz\Foolfuuka\Model\Radix  $radix  The Radix object pertaining this Board
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_setRadix(\Foolz\Foolfuuka\Model\Radix $radix)
	{
		$this->radix = $radix;

		return $this;
	}

	/**
	 * Set the API options
	 *
	 * @param  array|null  $enable  If array it will be used to set API options, if null it will disable API mode
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_setApi($enable = [])
	{
		$this->api = $enable;

		return $this;
	}

	/**
	 * Sets the page to fetch
	 *
	 * @param  int  $page  The page to fetch
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 * @throws  \Foolz\Foolfuuka\Model\BoardException  If the page number is not valid
	 */
	protected function p_setPage($page)
	{
		$page = intval($page);

		if($page < 1)
		{
			throw new BoardException(__('The page number is not valid.'));
		}

		$this->setoptions('page', $page);

		return $this;
	}

	/**
	 * Checks if the string is a valid post number
	 *
	 * @param  $str  The string to test on
	 *
	 * @return  boolean  True if the post number is valid, false otherwise
	 */
	public static function isValidPostNumber($str)
	{
		return ctype_digit((string) $str) || preg_match('/^[0-9]+(,|_)[0-9]+$/', $str);
	}

	/**
	 * Splits the post number in num and subnum, even if there's no subnum in the string.
	 *
	 * @param  string  $num  The string to split. Must be a "valid post number" (check with static::isValidPostNumber())
	 *
	 * @return  array  Two keys: num, subnum
	 */
	public static function splitPostNumber($num)
	{
		if (strpos($num, ',') !== false)
		{
			$arr = explode(',', $num);
		}
		elseif (strpos($num, '_') !== false)
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
	 * Sets the board to the "latest" mode, to create index pages with a couple of the last posts per thread
	 * Options: page, per_page, order[by_post, by_thread, ghost]
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_getLatest()
	{
		$this
			->setMethodFetching('getLatestComments')
			->setMethodCounting('getLatestCount')
			->setOptions([
				'per_page' => 20,
				'per_thread' => 5,
				'order' => 'by_post'
			]);

		return $this;
	}


	/**
	 * Returns the latest threads with a couple of the latest posts in each thread
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_getLatestComments()
	{
		\Profiler::mark('Board::getLatestComments Start');
		extract($this->options);

		switch ($order)
		{
			case 'by_post':
				$query = DC::qb()
					->select('*, thread_num AS unq_thread_num')
					->from($this->radix->getTable('_threads'), 'rt')
					->orderBy('rt.time_bump', 'DESC')
					->setMaxResults($per_page)
					->setFirstResult(($page * $per_page) - $per_page);
				break;

			case 'by_thread':
				$query = DC::qb()
					->select('*, thread_num AS unq_thread_num')
					->from($this->radix->getTable('_threads'), 'rt')
					->orderBy('rt.thread_num', 'DESC')
					->setMaxResults($per_page)
					->setFirstResult(($page * $per_page) - $per_page);
				break;

			case 'ghost':
				$query = DC::qb()
					->select('*, thread_num AS unq_thread_num')
					->from($this->radix->getTable('_threads'), 'rt')
					->where('rt.time_ghost_bump IS NOT NULL')
					->orderBy('rt.time_ghost_bump', 'DESC')
					->setMaxResults($per_page)
					->setFirstResult(($page * $per_page) - $per_page);
				break;
		}

		$threads = $query
			->execute()
			->fetchAll();

		if ( ! count($threads))
		{
			$this->comments = [];
			$this->comments_unsorted = [];

			\Profiler::mark_memory($this, 'Board $this');
			\Profiler::mark('Board::getLatestComments End Prematurely');
			return $this;
		}

		// populate arrays with posts
		$threads_arr = [];
		$sql_arr = [];

		foreach ($threads as $thread)
		{
			$threads_arr[$thread['unq_thread_num']] = [
				'replies' => $thread['nreplies'],
				'images' => $thread['nimages']
			];

			$temp = DC::qb()
				->select('*')
				->from($this->radix->getTable(), 'r')
				->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
				->leftJoin('r', $this->radix->getTable('_extra'), 'ex', 'ex.extra_id = r.doc_id')
				->where('r.thread_num = '.$thread['unq_thread_num'])
				->orderBy('op', 'DESC')
				->addOrderBy('num', 'DESC')
				->addOrderBy('subnum', 'DESC')
				->setMaxResults($per_thread + 1)
				->setFirstResult(0);

			$sql_arr[] = '('.$temp->getSQL().')';
		}

		$query_posts = DC::forge()
			->executeQuery(implode(' UNION ', $sql_arr))
			->fetchAll();

		// populate posts_arr array
		$results = [];
		$this->comments_unsorted = Comment::fromArrayDeep($query_posts, $this->radix, $this->comment_options);
		\Profiler::mark_memory($this->comments_unsorted, 'Board $this->comments_unsorted');

		foreach ($threads as $thread)
		{
			$results[$thread['thread_num']] = [
				'omitted' => ($thread['nreplies'] - ($per_thread + 1)),
				'images_omitted' => ($thread['nimages'] - 1)
			];
		}

		// populate results array and order posts
		foreach ($this->comments_unsorted as $post)
		{
			if ($post->op == 0)
			{
				if ($post->media !== null && $post->media->preview_orig)
				{
					$results[$post->thread_num]['images_omitted']--;
				}

				if ( ! isset($results[$post->thread_num]['posts']))
				{
					$results[$post->thread_num]['posts'] = [];
				}

				array_unshift($results[$post->thread_num]['posts'], $post);
			}
			else
			{
				$results[$post->thread_num]['op'] = $post;
			}
		}

		$this->comments = $results;

		\Profiler::mark_memory($this->comments, 'Board $this->comments');
		\Profiler::mark_memory($this, 'Board $this');
		\Profiler::mark('Board::getLatestComments End');
		return $this;
	}

	/**
	 * Returns the count of the threads available
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_getLatestCount()
	{
		\Profiler::mark('Board::getLatestCount Start');
		extract($this->options);

		$type_cache = 'thread_num';

		if ($order == 'ghost')
		{
			$type_cache = 'ghost_num';
		}

		try
		{
			$this->total_count = Cache::item('Foolz_Foolfuuka_Model_Board.getLatestCount.result.'.$type_cache)->get();
			return $this;
		}
		catch(\OutOfBoundsException $e)
		{
			switch ($order)
			{
				// these two are the same
				case 'by_post':
				case 'by_thread':
					$query_threads = DC::qb()
						->select('COUNT(thread_num) AS threads')
						->from($this->radix->getTable('_threads'), 'rt');
					break;

				case 'ghost':
					$query_threads = DC::qb()
						->select('COUNT(thread_num) AS threads')
						->from($this->radix->getTable('_threads'), 'rt')
						->where('rt.time_ghost_bump IS NOT NULL');
					break;
			}

			$result = $query_threads
				->execute()
				->fetch();

			$this->total_count = $result['threads'];
			Cache::item('Foolz_Foolfuuka_Model_Board.getLatestCount.result.'.$type_cache)->set($this->total_count, 300);
		}

		\Profiler::mark_memory($this, 'Board $this');
		\Profiler::mark('Board::getLatestCount End');
		return $this;
	}

	/**
	 * Sets the "thread" mode
	 * Options: page, per_page
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_getThreads()
	{
		$this
			->setMethodFetching('getThreadsComments')
			->setMethodCounting('getThreadsCount')
			->setOptions([
				'per_page' => 20,
				'order' => 'by_post'
			]);

		return $this;
	}

	/**
	 * Fetches a bunch of threads (in example for gallery)
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_getThreadsComments()
	{
		\Profiler::mark('Board::getThreadsComments Start');
		extract($this->options);

		$inner_query = DC::qb()
			->select('*, thread_num as unq_thread_num')
			->from($this->radix->getTable('_threads'), 'rt')
			->orderBy('rt.time_op', 'DESC')
			->setMaxResults($per_page)
			->setFirstResult(($page * $per_page) - $per_page)
			->getSQL();

		$result = DC::qb()
			->select('*')
			->from('('.$inner_query.')', 'g')
			->join('g', $this->radix->getTable(), 'r', 'r.num = g.unq_thread_num AND r.subnum = 0')
			->leftJoin('g', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
			->leftJoin('g', $this->radix->getTable('_extra'), 'ex', 'ex.extra_id = r.doc_id')
			->execute()
			->fetchAll();

		if( ! count($result))
		{
			$this->comments = [];
			$this->comments_unsorted = [];

			\Profiler::mark_memory($this, 'Board $this');
			\Profiler::mark('Board::get_threadscomments End Prematurely');
			return $this;
		}

		if ($this->api)
		{
			$this->comments_unsorted = Comment::fromArrayDeepApi($result, $this->radix, $this->api, $this->comment_options);
		}
		else
		{
			$this->comments_unsorted = Comment::fromArrayDeep($result, $this->radix, $this->comment_options);
		}

		$this->comments = $this->comments_unsorted;

		\Profiler::mark_memory($this->comments, 'Board $this->comments');
		\Profiler::mark_memory($this, 'Board $this');
		\Profiler::mark('Board::getThreadsComments End');

		return $this;
	}

	/**
	 * Counts the available threads
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_getThreadsCount()
	{
		extract($this->options);

		try
		{
			$this->total_count = Cache::item('Foolz_Foolfuuka_Model_Board.getThreadsCount.result')->get();
		}
		catch (\OutOfBoundsException $e)
		{
			$result = DC::qb()
				->select('COUNT(thread_num) AS threads')
				->from($this->radix->getTable('_threads'), 'rt')
				->execute()
				->fetch();

			$this->total_count = $result['threads'];
			Cache::item('Foolz_Foolfuuka_Model_Board.getThreadsCount.result')->set($this->total_count, 300);
		}

		return $this;
	}

	/**
	 * Sets the Board object to Thread mode
	 * Options: type=[from_doc_id, ghosts, last_x], (int)num(thread number)
	 * Options for "from_doc_id": (int)latest_doc_id
	 * Options for "last_x": (int)last_limit
	 *
	 * @param  int  $num  The number of the thread
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 * @throws  BoardMalformedInputException
	 */
	protected function p_getThread($num)
	{
		// default variables
		$this
			->setMethodFetching('getThreadComments')
			->setoptions(['type' => 'thread', 'realtime' => false]);

		if( ! ctype_digit((string) $num) || $num < 1)
		{
			throw new BoardMalformedInputException(__('The thread number is invalid.'));
		}

		$this->setOptions('num', $num);

		return $this;
	}

	/**
	 * Gets a thread
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 * @throws  BoardThreadNotFoundException  If the thread wasn't found
	 */
	protected function p_getThreadComments()
	{
		\Profiler::mark('Board::getThreadComments Start');

		$controller_method = 'thread';

		extract($this->options);

		// determine type
		switch ($type)
		{
			case 'from_doc_id':
				$query = DC::qb()
					->select('*')
					->from($this->radix->getTable(), 'r')
					->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
					->leftJoin('r', $this->radix->getTable('_extra'), 'ex', 'ex.extra_id = r.doc_id')
					->where('thread_num = :thread_num')
					->andWhere('doc_id > :latest_doc_id')
					->orderBy('num', 'ASC')
					->addOrderBy('subnum', 'ASC')
					->setParameter(':thread_num', $num)
					->setParameter(':latest_doc_id', $latest_doc_id);
				break;

			case 'ghosts':
				$query = DC::qb()
					->select('*')
					->from($this->radix->getTable(), 'r')
					->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
					->leftJoin('r', $this->radix->getTable('_extra'), 'ex', 'ex.extra_id = r.doc_id')
					->where('thread_num = :thread_num')
					->where('subnum <> 0')
					->orderBy('num', 'ASC')
					->addOrderBy('subnum', 'ASC')
					->setParameter(':thread_num', $num);
				break;

			case 'last_x':
				$subquery_first = DC::qb()
					->select('*')
					->from($this->radix->getTable(), 'xr')
					->where('num = '.DC::forge()->quote($num))
					->setMaxResults(1)
					->getSQL();
				$subquery_last = DC::qb()
					->select('*')
					->from($this->radix->getTable(), 'xrr')
					->where('thread_num = '.DC::forge()->quote($num))
					->orderBy('num', 'DESC')
					->addOrderBy('subnum', 'DESC')
					->setMaxResults($last_limit)
					->getSQL();
				$query = DC::qb()
					->select('*')
					->from('(('.$subquery_first.') UNION ('.$subquery_last.'))', 'r')
					->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
					->leftJoin('r', $this->radix->getTable('_extra'), 'ex', 'ex.extra_id = r.doc_id')
					->orderBy('num', 'ASC')
					->addOrderBy('subnum', 'ASC');

				$controller_method = 'last/'.$last_limit;
				break;

			case 'thread':
				$query = DC::qb()
					->select('*')
					->from($this->radix->getTable(), 'r')
					->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
					->leftJoin('r', $this->radix->getTable('_extra'), 'ex', 'ex.extra_id = r.doc_id')
					->where('thread_num = :thread_num')
					->orderBy('num', 'ASC')
					->addOrderBy('subnum', 'ASC')
					->setParameter(':thread_num', $num);
				break;
		}

		$query_result = $query
			->execute()
			->fetchAll();

		if ( ! count($query_result) && isset($latest_doc_id))
		{
			return $this->comments = $this->comments_unsorted = [];
		}

		if ( ! count($query_result))
		{
			throw new BoardThreadNotFoundException(__('There\'s no such a thread.'));
		}

		if ($this->api)
		{
			$this->comments_unsorted =
				Comment::fromArrayDeepApi($query_result, $this->radix, $this->api, [
					'realtime' => $realtime,
					'backlinks_hash_only_url' => true,
					'controller_method' => $controller_method
				] + $this->comment_options);

		}
		else
		{
			$this->comments_unsorted =
				Comment::fromArrayDeep($query_result, $this->radix, [
					'realtime' => $realtime,
					'backlinks_hash_only_url' => true,
					'prefetch_backlinks' => true,
					'controller_method' => $controller_method
				] + $this->comment_options);
		}

		// process entire thread and store in $result array
		$result = [];

		foreach ($this->comments_unsorted as $post)
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

		$this->comments = $result;

		\Profiler::mark_memory($this->comments, 'Board $this->comments');
		\Profiler::mark_memory($this, 'Board $this');
		\Profiler::mark('Board::getThreadComments End');

		return $this;
	}


	/**
	 * Returns an array specifying the thread statuses.
	 * Returned array keys: closed, dead, disable_image_upload
	 *
	 * @return  array  An associative array with boolean values
	 * @throws  BoardNotCompatibleMethodException  If the specified fetching method is not "getThreadComments"
	 * @throws  BoardThreadNotFoundException       If the thread was not found
	 */
	protected function p_checkThreadStatus()
	{
		if ($this->method_fetching !== 'getThreadComments')
		{
			throw new BoardNotCompatibleMethodException;
		}

		// define variables to override
		$thread_op_present = false;
		$ghost_post_present = false;
		$thread_last_bump = 0;
		$counter = ['posts' => 0, 'images' => 0];

		foreach ($this->comments_unsorted as $post)
		{
			// we need to find if there's the OP in the list
			// let's be strict, we want the $num to be the OP
			if ($post->op == 1)
			{
				$thread_op_present = true;
			}
			else
			{
				$counter['posts']++;
			}

			if ($post->op == 0 && $post->subnum == 0 && $post->media !== null)
			{
				$counter['images']++;
			}

			if ($post->subnum > 0)
			{
				$ghost_post_present = true;
			}

			if ($post->subnum == 0 && $thread_last_bump < $post->timestamp)
			{
				$thread_last_bump = $post->timestamp;
			}
		}

		// we didn't point to the thread OP, this is not a thread
		if ( ! $thread_op_present)
		{
			// this really should not happen here
			throw new BoardThreadNotFoundException(__('The thread you were looking for can\'t be found.'));
		}

		$result = [
			'closed' => false,
			'dead' => (bool) $this->radix->archive,
			'disable_image_upload' => (bool) $this->radix->archive,
		];

		// time check
		if (time() - $thread_last_bump > 432000 || $ghost_post_present)
		{
			$result['dead'] = true;
			$result['disable_image_upload'] = true;
		}

		if ($counter['posts'] >= $this->radix->max_posts_count)
		{
			$result['dead'] = true;
			$result['disable_image_upload'] = true;
		}
		elseif ($counter['images'] >= $this->radix->max_images_count)
		{
			$result['disable_image_upload'] = true;
		}

		if ($this->radix->disable_ghost && $result['dead'])
		{
			$result['closed'] = true;
		}

		return $result;
	}

	/**
	 * Sets the Board object to fetch a post
	 * Options available: num OR doc_id
	 *
	 * @param  string  $num  If specified, a valid post number
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 */
	protected function p_getPost($num = null)
	{
		// default variables
		$this->setMethodFetching('getPostComments');

		if ($num !== null)
		{
			$this->setOptions('num', $num);
		}

		return $this;
	}

	/**
	 * Gets a post by num or doc_d
	 *
	 * @return  \Foolz\Foolfuuka\Model\Board  The current object
	 * @throws  BoardMalformedInputException  If the $num is not a valid post number
	 * @throws  BoardMissingOptionsException  If doc_id or num has not been specified
	 * @throws  BoardPostNotFoundException    If the post has not been found
	 */
	protected function p_getPostComments()
	{
		extract($this->options);

		$query = DC::qb()
			->select('*')
			->from($this->radix->getTable(), 'r');

		if (isset($num))
		{
			if( ! static::isValidPostNumber($num))
			{
				throw new BoardMalformedInputException;
			}
			$num_arr = static::splitPostNumber($num);
			$query->where('num = :num')
				->andWhere('subnum = :subnum')
				->setParameter(':num', $num_arr['num'])
				->setParameter(':subnum', $num_arr['subnum']);
		}
		elseif (isset($doc_id))
		{
			$query->where('doc_id = :doc_id')
				->setParameter(':doc_id', $doc_id);
		}
		else
		{
			throw new BoardMissingOptionsException(__('No posts found with the submitted options.'));
		}

		$result = $query
			->leftJoin('r', $this->radix->getTable('_images'), 'mg', 'mg.media_id = r.media_id')
			->leftJoin('r', $this->radix->getTable('_extra'), 'ex', 'ex.extra_id = r.doc_id')
			->execute()
			->fetchAll();

		if( ! count($result))
		{
			throw new BoardPostNotFoundException(__('Post not found.'));
		}

		if ($this->api)
		{
			$this->comments_unsorted = Comment::fromArrayDeepApi($result, $this->radix, $this->api, $this->comment_options);
		}
		else
		{
			$this->comments_unsorted = Comment::fromArrayDeep($result, $this->radix, $this->comment_options);
		}

		foreach ($this->comments_unsorted as $comment)
		{
			$this->comments[$comment->num.($comment->subnum ? '_'.$comment->subnum : '')] = $comment;
		}

		return $this;
	}
}