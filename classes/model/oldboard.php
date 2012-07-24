<?php

namespace Model;

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
class Board extends \Model
{
	// store all relavent data regarding posts displayed

	/**
	 * Sets the callbacks so they return URLs good for realtime updates
	 * Notice: this is global because it's used in a PHP callback
	 *
	 * @var type
	 */
	private $realtime = FALSE;

	/**
	 * The functions with 'p_' prefix will respond to plugins before and after
	 *
	 * @param string $name
	 * @param array $parameters
	 */
	public function __call($name, $parameters)
	{
		$before = Plugins::run_hook('model/board/call/before/'.$name, $parameters);

		if (is_array($before))
		{
			// if the value returned is an Array, a plugin was active
			$parameters = $before['parameters'];
		}

		// if the replace is anything else than NULL for all the functions ran here, the
		// replaced function wont' be run
		$replace = Plugins::run_hook('model/board/call/replace/'.$name, $parameters, array($parameters));

		if($replace['return'] !== NULL)
		{
			$return = $replace['return'];
		}
		else
		{
			switch (count($parameters)) {
				case 0:
					$return = $this->{'p_' . $name}();
					break;
				case 1:
					$return = $this->{'p_' . $name}($parameters[0]);
					break;
				case 2:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1]);
					break;
				case 3:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2]);
					break;
				case 4:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
					break;
				case 5:
					$return = $this->{'p_' . $name}($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
					break;
				default:
					$return = call_user_func_array(array(&$this, 'p_' . $name), $parameters);
				break;
			}
		}

		// in the after, the last parameter passed will be the result
		array_push($parameters, $return);
		$after = Plugins::run_hook('model/board/call/after/'.$name, $parameters);

		if (is_array($after))
		{
			return $after['return'];
		}

		return $return;
	}


	/**
	 * If the user is an admin, this will return SQL to add reports to the
	 * query output
	 *
	 * @param object $board
	 * @param bool|string $join_on alternative join table name
	 * @return string SQL to append reports to the rows
	 */
	private function p_sql_report_join($board, $query, $join_on = FALSE)
	{
		// only show report notifications to certain users
		if (!Auth::has_access('board.reports'))
		{
			return '';
		}

		$query->join(DB::expr('
			SELECT
				id AS report_id, doc_id AS report_doc_id, reason AS report_reason, ip_reporter as report_ip_reporter,
				status AS report_status, created AS report_created
			FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
			WHERE `board_id` = ' . $board->id), 'LEFT'
		)->on(DB::expr($this->radix->get_table($board) . '.`doc_id`'),
			'=',
			DB::expr($this->db->protect_identifiers('r') . '.`report_doc_id`')
		);
	}


	/**
	 * Returns the SQL string to append to queries to be able to
	 * get the filenames required to create the path to media
	 *
	 * @param object $board
	 * @param bool|string $join_on alternative join table name
	 * @return string SQL to append to retrieve image filenames
	 */
	private function p_sql_media_join($board, $query, $join_on = FALSE)
	{
		$query->join(DB::expr($this->radix->get_table($board, '_images') . ' AS `mg`'), 'LEFT')
			->on(DB::expr($this->radix->get_table($board)) . '.`media_id`',
			'=',
			DB::expr($this->db->protect_identifiers('mg') . '.`media_id`')
		);
	}


	/**
	 * Puts in the $posts_arr class variable the number of the posts that
	 * we for sure know exist since we've fetched them once during processing
	 *
	 * @param array|object $posts
	 */
	private function p_populate_posts_arr($post)
	{
		if (is_array($post))
		{
			foreach ($post as $p)
			{
				$this->populate_posts_arr($p);
			}
		}

		if (is_object($post))
		{
			if ($post->op == 1)
			{
				$this->posts_arr[$post->num][] = $post->num;
			}
			else
			{
				if ($post->subnum == 0)
					$this->posts_arr[$post->thread_num][] = $post->num;
				else
					$this->posts_arr[$post->thread_num][] = $post->num . ',' . $post->subnum;
			}
		}
	}


	/**
	 * Return the status of the thread to determine if it can be posted in, or if images can be posted
	 * or if it's a ghost thread...
	 *
	 * @param object $board
	 * @param mixed $num if you send a $query->result() of a thread it will avoid another query
	 * @return array statuses of the thread
	 */
	private function p_check_thread($board, $num)
	{
		if ($num == 0)
		{
			return array('invalid_thread' => TRUE);
		}

		// of $num is an array it means we've sent a $query->result()
		if (!is_array($num))
		{
			// grab the entire thread
			$query = $this->db->query('
				SELECT * FROM ' . $this->radix->get_table($board) . '
				WHERE thread_num = ?
			',
				array($num, $num)
			);

			// thread was not found
			if ($query->num_rows() == 0)
			{
				return array('invalid_thread' => TRUE);
			}

			$query_result = $query->result();

			// free up result
			$query->free_result();
		}
		else
		{
			$query_result = $num;
		}

		// define variables
		$thread_op_present = FALSE;
		$ghost_post_present = FALSE;
		$thread_last_bump = 0;
		$counter = array('posts' => 0, 'images' => 0);

		foreach ($query_result as $post)
		{
			// we need to find if there's the OP in the list
			// let's be strict, we want the $num to be the OP
			if ($post->op == 1)
			{
				$thread_op_present = TRUE;
			}

			if($post->subnum > 0)
			{
				$ghost_post_present = TRUE;
			}

			if($post->subnum == 0 && $thread_last_bump < $post->timestamp)
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
			return array('invalid_thread' => TRUE);
		}

		// time check
		if(time() - $thread_last_bump > 432000 || $ghost_post_present)
		{
			return array('thread_dead' => TRUE, 'disable_image_upload' => TRUE, 'ghost_disabled' => $board->disable_ghost);
		}

		if ($counter['posts'] > $board->max_posts_count)
		{
			if ($counter['images'] > $board->max_images_count)
			{
				return array('thread_dead' => TRUE, 'disable_image_upload' => TRUE, 'ghost_disabled' => $board->disable_ghost);
			}
			else
			{
				return array('thread_dead' => TRUE, 'ghost_disabled' => $board->disable_ghost);
			}
		}
		else if ($counter['images'] > $board->max_images_count)
		{
			return array('disable_image_upload' => TRUE);
		}

		return array('valid_thread' => TRUE);
	}


	/**
	 * Query the selected search engine to get an array of matching posts
	 *
	 * @param object $board
	 * @param array $args search arguments
	 * @param array $options modifiers
	 * @return array rows in form of database objects
	 */
	private function p_get_search($board, $args, $options = array())
	{
		// default variables
		$process = TRUE;
		$clean = TRUE;
		$limit = 25;

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// set a valid value for $search['page']
		if ($args['page'])
		{
			if (!is_numeric($args['page']))
			{
				log_message('error', 'post.php/get_search: invalid page argument');
				show_404();
			}

			$args['page'] = intval($args['page']);
		}
		else
		{
			$args['page'] = 1;
		}

		// if image is set, get either media_hash or media_id
		if ($args['image'] && !is_natural($args['image']))
		{
			// this is urlsafe, let's convert it else decode it
			if (mb_strlen($args['image']) < 23)
			{
				$args['image'] = $this->get_media_hash($args['image']);
			}
			else
			{
				$args['image'] = rawurldecode($args['image']);
			}

			if(substr($args['image'], -2) != '==')
			{
				$args['image'] .= '==';
			}

			// if board set, grab media_id
			if ($board !== FALSE)
			{
				$image_query = $this->db->query('
					SELECT media_id
					FROM ' . $this->radix->get_table($board, '_images') . '
					WHERE media_hash = ?
				', array($args['image']));

				// if there's no images matching, the result is certainly empty
				if($image_query->num_rows() == 0)
				{
					return array('posts' => array(), 'total_found' => 0);
				}

				$args['image'] = $image_query->row()->media_id;
			}
		}

		// if global or board => use sphinx, else mysql for board only
		// global search requires sphinx
		if (($board === FALSE && get_setting('fu.sphinx.global', 0) == 0))
		{
			return array('error' => __('Sorry, global search requires SphinxSearch.'));
		}
		elseif (($board === FALSE && get_setting('fu.sphinx.global', 0)) || (is_object($board) && $board->sphinx))
		{
			$this->load->library('SphinxQL');

			// establish connection to sphinx
			$sphinx_server = explode(':', get_setting('fu_sphinx_listen', FOOL_PREF_SPHINX_LISTEN));

			if (!$this->sphinxql->set_server($sphinx_server[0], $sphinx_server[1]))
				return array('error' => __('The search backend is currently not online. Try later or contact us in case it\'s offline for too long.'));

			// determine if all boards will be used for search or not
			if ($board === FALSE)
			{
				$this->radix->preload(TRUE);
				$indexes = array();

				foreach ($this->radix->get_all() as $radix)
				{
					// ignore boards that don't have sphinx enabled
					if (!$radix->sphinx)
					{
						continue;
					}

					$indexes[] = $radix->shortname . '_ancient';
					$indexes[] = $radix->shortname . '_main';
					$indexes[] = $radix->shortname . '_delta';
				}
			}
			else
			{
				$indexes = array(
					$board->shortname . '_ancient',
					$board->shortname . '_main',
					$board->shortname . '_delta'
				);
			}

			// set db->from with indexes loaded
			$this->db->from($indexes, FALSE, FALSE);

			// begin filtering search params
			if ($args['text'])
			{
				if (mb_strlen($args['text']) < 1)
				{
					return array();
				}

				$this->db->sphinx_match('comment', $args['text'], 'half', TRUE);
			}
			if ($args['subject'])
			{
				$this->db->sphinx_match('title', $args['subject'], 'full', TRUE);
			}
			if ($args['username'])
			{
				$this->db->sphinx_match('name', $args['username'], 'full', TRUE);
			}
			if ($args['tripcode'])
			{
				$this->db->sphinx_match('trip', $args['tripcode'], 'full', TRUE, TRUE);
			}
			if ($args['email'])
			{
				$this->db->sphinx_match('email', $args['email'], 'full', TRUE);
			}
			if ($args['filename'])
			{
				$this->db->sphinx_match('media_filename', $args['filename'], 'full', TRUE);
			}
			if ($this->auth->is_mod_admin() && $args['poster_ip'])
			{
				$this->db->where('pip', (int) inet_ptod($args['poster_ip']));
			}
			if ($args['image'])
			{
				if($board !== FALSE)
				{
					$this->db->where('mid', (int) $args['image']);
				}
				else
				{
					$this->db->sphinx_match('media_hash', $args['image'], 'full', TRUE, TRUE);
				}
			}
			if ($args['capcode'] == 'admin')
			{
				$this->db->where('cap', 3);
			}
			if ($args['capcode'] == 'mod')
			{
				$this->db->where('cap', 2);
			}
			if ($args['capcode'] == 'user')
			{
				$this->db->where('cap', 1);
			}
			if ($args['deleted'] == 'deleted')
			{
				$this->db->where('is_deleted', 1);
			}
			if ($args['deleted'] == 'not-deleted')
			{
				$this->db->where('is_deleted', 0);
			}
			if ($args['ghost'] == 'only')
			{
				$this->db->where('is_internal', 1);
			}
			if ($args['ghost'] == 'none')
			{
				$this->db->where('is_internal', 0);
			}
			if ($args['type'] == 'op')
			{
				$this->db->where('is_op', 1);
			}
			if ($args['type'] == 'posts')
			{
				$this->db->where('is_op', 0);
			}
			if ($args['filter'] == 'image')
			{
				$this->db->where('has_image', 0);
			}
			if ($args['filter'] == 'text')
			{
				$this->db->where('has_image', 1);
			}
			if ($args['start'])
			{
				$this->db->where('timestamp >=', intval(strtotime($args['start'])));
			}
			if ($args['end'])
			{
				$this->db->where('timestamp <=', intval(strtotime($args['end'])));
			}
			if ($args['order'] == 'asc')
			{
				$this->db->order_by('timestamp', 'ASC');
			}
			else
			{
				$this->db->order_by('timestamp', 'DESC');
			}

			// set sphinx options
			$this->db->limit($limit, ($args['page'] * $limit) - $limit)
				->sphinx_option('max_matches', get_setting('fu_sphinx_max_matches', 5000))
				->sphinx_option('reverse_scan', ($args['order'] == 'asc') ? 0 : 1);

			// send sphinxql to searchd
			$search = $this->sphinxql->query($this->db->statement());

			if (empty($search['matches']))
			{
				return array('posts' => array(), 'total_found' => 0);
			}

			// populate array to query for full records
			$sql = array();

			foreach ($search['matches'] as $post => $result)
			{
				$sql[] = '
					(
						SELECT *, ' . $result['board'] . ' AS board
						FROM ' . $this->radix->get_table($this->radix->get_by_id($result['board'])) . '
						' . $this->sql_media_join($this->radix->get_by_id($result['board'])) . '
						' . $this->sql_report_join($this->radix->get_by_id($result['board'])) . '
						WHERE num = ' . $result['num'] . ' AND subnum = ' . $result['subnum'] . '
					)
				';
			}

			// query mysql for full records
			$query = $this->db->query(implode('UNION', $sql) . '
				ORDER BY timestamp ' . (($args['order'] == 'asc') ? 'ASC' : 'DESC') .',
				num ' . (($args['order'] == 'asc') ? 'ASC' : 'DESC'));
			$total = $search['total_found'];
		}
		else /* use mysql as fallback for non-sphinx indexed boards */
		{
			// begin filtering search params
			if ($args['text'] || $args['filename'])
			{
				if (mb_strlen($args['text']) < 1)
				{
					return array();
				}

				// we're using fulltext fields, we better start from this
				$this->db->from($this->radix->get_table($board, '_search'), FALSE, FALSE);

				// select that we'll use for the final statement
				$select = 'SELECT ' . $this->radix->get_table($board, '_search') . '.`doc_id`';

				if($args['text'])
				{
					$this->db->where(
						'MATCH (' . $this->radix->get_table($board, '_search') . '.`comment`) AGAINST (' . $this->db->escape(rawurldecode($args['text'])) . ' IN BOOLEAN MODE)',
						NULL,
						FALSE
					);
				}

				if($args['filename'])
				{
					$this->db->where(
						'MATCH (' . $this->radix->get_table($board, '_search') . '.`media_filename`) AGAINST (' . $this->db->escape(rawurldecode($args['filename'])) . ' IN BOOLEAN MODE)',
						NULL,
						FALSE
					);
				}

				$query = $this->db->query($this->db->statement('', NULL, NULL, 'SELECT doc_id'));
				if ($query->num_rows == 0)
				{
					return array('posts' => array(), 'total_found' => 0);
				}

				$docs = array();
				foreach ($query->result() as $rec)
				{
					$docs[] = $rec->doc_id;
				}
			}

			$this->db->start_cache();

			// no need for the fulltext fields
			$this->db->from($this->radix->get_table($board), FALSE, FALSE);

			// select that we'll use for the final statement
			$select = 'SELECT ' . $this->radix->get_table($board) . '.`doc_id`';

			if (isset($docs))
			{
				$this->db->where_in('doc_id', $docs);
			}

			if ($args['subject'])
			{
				$this->db->like('title', rawurldecode($args['subject']));
			}
			if ($args['username'])
			{
				$this->db->like('name', rawurldecode($args['username']));
				$this->db->use_index('name_trip_index');
			}
			if ($args['tripcode'])
			{
				$this->db->like('trip', rawurldecode($args['tripcode']));
				$this->db->use_index('trip_index');
			}
			if ($args['email'])
			{
				$this->db->like('email', rawurldecode($args['email']));
				$this->db->use_index('email_index');
			}
			if ($args['image'])
			{
				$this->db->where('media_id', $args['image']);
				$this->db->use_index('media_id_index');
			}
			if ($this->auth->is_mod_admin() && $args['poster_ip'])
			{
				$this->db->where('poster_ip', (int) inet_ptod($args['poster_ip']));
			}
			if ($args['capcode'] == 'admin')
			{
				$this->db->where('capcode', 'A');
			}
			if ($args['capcode'] == 'mod')
			{
				$this->db->where('capcode', 'M');
			}
			if ($args['capcode'] == 'user')
			{
				$this->db->where('capcode !=', 'A');
				$this->db->where('capcode !=', 'M');
			}
			if ($args['deleted'] == 'deleted')
			{
				$this->db->where('deleted', 1);
			}
			if ($args['deleted'] == 'not-deleted')
			{
				$this->db->where('deleted', 0);
			}
			if ($args['ghost'] == 'only')
			{
				$this->db->where('subnum <>', 0);
				$this->db->use_index('subnum_index');
			}
			if ($args['ghost'] == 'none')
			{
				$this->db->where('subnum', 0);
				$this->db->use_index('subnum_index');
			}
			if ($args['type'] == 'op')
			{
				$this->db->where('op', 1);
				$this->db->use_index('op_index');
			}
			if ($args['type'] == 'posts')
			{
				$this->db->where('op', 0);
				$this->db->use_index('op_index');
			}
			if ($args['filter'] == 'image')
			{
				$this->db->where('media_id', 0);
				$this->db->use_index('media_id_index');
			}
			if ($args['filter'] == 'text')
			{
				$this->db->where('media_id <>', 0);
				$this->db->use_index('media_id_index');
			}
			if ($args['start'])
			{
				$this->db->where('timestamp >=', intval(strtotime($args['start'])));
				$this->db->use_index('timestamp_index');
			}
			if ($args['end'])
			{
				$this->db->where('timestamp <=', intval(strtotime($args['end'])));
				$this->db->use_index('timestamp_index');
			}

			$this->db->stop_cache();

			// fetch initial total first...
			$this->db->limit(5000);

			// get directly the count for speed
			$count_res = $this->db->query($this->db->statement('', NULL, NULL, 'SELECT COUNT(*) AS count'));
			$total = $count_res->row()->count;

			if (!$total)
			{
				return array('posts' => array(), 'total_found' => 0);
			}

			// now grab those results in order
			$this->db->limit($limit, ($args['page'] * $limit) - $limit);

			$this->db->order_by('timestamp', ($args['order'] == 'asc'?'ASC':'DESC'));

			// get doc_ids, last parameter is the select
			$doc_ids_res = $this->db->query($this->db->statement('', NULL, NULL, $select));

			$doc_ids = array();
			$doc_ids_res_arr = $doc_ids_res->result();
			foreach($doc_ids_res_arr as $doc_id)
			{
				// juuust to be extra sure, make force it to be an int
				$doc_ids[] = intval($doc_id->doc_id);
			}

			$this->db->flush_cache();

			$query = $this->db->query('
				SELECT *
				FROM ' . $this->radix->get_table($board) . '
				' . $this->sql_media_join($board) . '
				' . $this->sql_report_join($board) . '
				WHERE doc_id IN (' . implode(', ', $doc_ids) . ')
				ORDER BY timestamp ' . ($args['order'] == 'asc'?'ASC':'DESC') . ',
					num ' . (($args['order'] == 'asc') ? 'ASC' : 'DESC') . '
				LIMIT ?, ?
			', array(($args['page'] * $limit) - $limit, $limit));

			// query mysql for full records
			//$query = $this->db->query($this->db->statement());
			$total = $doc_ids_res->num_rows();
		}

		// process all results to be displayed
		$results = array();

		$this->populate_posts_arr($query->result());

		foreach ($query->result() as $post)
		{
			// override board with full board information
			if (isset($post->board))
			{
				$post->board = $this->radix->get_by_id($post->board);
				$board = $post->board;
			}

			// populate posts_arr array
			$this->populate_posts_arr($post);

			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}

			$results[0]['posts'][] = $post;
		}

		return array('posts' => $results, 'total_found' => $total);
	}


	/**
	 * Get the latest
	 *
	 * @param object $board
	 * @param int $page the page to determine the offset
	 * @param array $options modifiers
	 * @return array|bool FALSE on error (likely from faulty $options), or the list of threads with 5 replies attached
	 */
	private function p_get_latest($board, $page = 1, $options = array())
	{
		// default variables
		$per_page = 20;
		$process = TRUE;
		$clean = TRUE;
		$type = 'by_post';

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// determine type
		switch ($type)
		{
			case 'by_post':

				$query = $this->db->query('
					SELECT *, thread_num as unq_thread_num
					FROM ' . $this->radix->get_table($board, '_threads') . '
					ORDER BY time_bump DESC LIMIT ?, ?
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);

				break;

			case 'by_thread':

				$query = $this->db->query('
					SELECT *, thread_num as unq_thread_num
					FROM ' . $this->radix->get_table($board, '_threads') . '
					ORDER BY thread_num DESC LIMIT ?, ?
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);

				break;

			case 'ghost':

				$query = $this->db->query('
					SELECT *
					FROM
					(
						SELECT *, thread_num as unq_thread_num
						FROM ' . $this->radix->get_table($board, '_threads') . '
						WHERE time_ghost_bump IS NOT NULL
						ORDER BY time_ghost_bump DESC LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_thread_num AND g.subnum = 0
					' . $this->sql_media_join($board, 'g') . '
					' . $this->sql_report_join($board, 'g') . '
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);

				break;

			default:
				log_message('error', 'post.php/get_latest: invalid or missing type argument');
				return FALSE;
		}

		// cache the count or get the cached count
		if($type == 'ghost')
		{
			$type_cache = 'ghost_num';
		}
		else
		{
			$type_cache = 'thread_num';
		}

		if(!$threads = $this->cache->get('foolfuuka_' . config_item('random_id') . '_board_' . $board->id . '_get_latest_threads_count_' . $type_cache))
		{
			switch ($type)
			{
				// these two are the same
				case 'by_post':
				case 'by_thread':
					$query_threads = $this->db->query('
						SELECT COUNT(thread_num) AS threads
						FROM ' . $this->radix->get_table($board, '_threads') . '
					');
					break;

				case 'ghost':
					$query_threads = $this->db->query('
						SELECT COUNT(thread_num) AS threads
						FROM ' . $this->radix->get_table($board, '_threads') . '
						WHERE time_ghost_bump IS NOT NULL;
					');
					break;
			}

			$threads = $query_threads->row()->threads;
			$query_threads->free_result();

			// start caching only over 2500 threads so we can keep boards with little number of threads dynamic
			if($threads > 2500)
			{
				$this->cache->save(
					'foolfuuka_' . config_item('random_id') . '_board_' . $board->id . '_get_latest_threads_count_' . $type_cache,
					$threads,
					1800
				);
			}
		}

		if ($query->num_rows() == 0)
		{
			return array(
				'result' => array('op' => array(), 'posts' => array()),
				'pages' => NULL
			);
		}


		// set total pages found
		if ($threads <= $per_page)
		{
			$pages = NULL;
		}
		else
		{
			$pages = floor($threads/$per_page)+1;
		}

		// populate arrays with posts
		$threads = array();
		$results = array();
		$sql_arr = array();

		foreach ($query->result() as $thread)
		{
			$threads[$thread->unq_thread_num] = array('replies' => $thread->nreplies, 'images' => $thread->nimages);

			$sql_arr[] = '
				(
					SELECT *
					FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE thread_num = ' . $thread->unq_thread_num . '
					ORDER BY op DESC, num DESC, subnum DESC
					LIMIT 0, 6
				)
			';
		}

		$query_posts = $this->db->query(implode('UNION', $sql_arr));

		// populate posts_arr array
		$this->populate_posts_arr($query_posts->result());

		// populate results array and order posts
		foreach ($query_posts->result() as $post)
		{
			$post_num = ($post->op == 0) ? $post->thread_num : $post->num;

			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}

			if (!isset($results[$post_num]['omitted']))
			{
				foreach ($threads as $thread_num => $counter)
				{
					if ($thread_num == $post_num)
					{
						$results[$post_num] = array(
							'omitted' => ($counter['replies'] - 6),
							'images_omitted' => ($counter['images'] - 1)
						);
					}
				}
			}

			if ($post->op == 0)
			{
				if ($post->preview_orig)
				{
					$results[$post->thread_num]['images_omitted']--;
				}

				if(!isset($results[$post->thread_num]['posts']))
					$results[$post->thread_num]['posts'] = array();

				array_unshift($results[$post->thread_num]['posts'], $post);
			}
			else
			{
				$results[$post->num]['op'] = $post;
			}
		}

		return array('result' => $results, 'pages' => $pages);
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
	private function p_get_thread($board, $num, $options = array())
	{
		// default variables
		$process = TRUE;
		$clean = TRUE;
		$type = 'thread';
		$type_extra = array();
		$realtime = FALSE;

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// determine type
		switch ($type)
		{
			case 'from_doc_id':

				if (!isset($type_extra['latest_doc_id']) || !is_natural($type_extra['latest_doc_id']))
				{
					log_message('error', 'post.php/get_thread: invalid last_doc_id argument');
					return FALSE;
				}

				$query = $this->db->query('
					SELECT *
					FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE thread_num = ? AND doc_id > ?
					ORDER BY num, subnum ASC
				',
					array($num, $type_extra['latest_doc_id'])
				);

				break;

			case 'ghosts':

				$query = $this->db->query('
					SELECT *
					FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE thread_num = ? AND subnum <> 0
					ORDER BY num, subnum ASC
				',
					array($num)
				);

				break;

			case 'last_x':

				if (!isset($type_extra['last_limit']) || !is_natural($type_extra['last_limit']))
				{
					log_message('error', 'post.php/get_thread: invalid last_limit argument');
					return FALSE;
				}

				// TODO reduce this query since thread_num catches all
				$query = $this->db->query('
					SELECT *
					FROM
					(
						(
							SELECT * FROM ' . $this->radix->get_table($board) . '
							WHERE num = ? LIMIT 0, 1
						)
						UNION
						(
							SELECT * FROM ' . $this->radix->get_table($board) . '
							WHERE thread_num = ?
							ORDER BY num DESC, subnum DESC
							LIMIT ?
						)
					) AS x
					' . $this->sql_media_join($board, 'x') . '
					' . $this->sql_report_join($board, 'x') . '
					ORDER BY num, subnum ASC
				',
					array(
						$num, $num, intval($type_extra['last_limit'])
					)
				);

				break;

			case 'thread':

				$query = $this->db->query('
					SELECT * FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE thread_num = ?
					ORDER BY num, subnum ASC
				',
					array($num, $num)
				);

				break;

			default:
				log_message('error', 'post.php/show_thread: invalid or missing type argument');
				return FALSE;
		}

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		// set global variables for special usage
		if ($realtime === TRUE)
		{
			$this->realtime = TRUE;
		}

		$this->backlinks_hash_only_url = TRUE;

		// populate posts_arr array
		$this->populate_posts_arr($query->result());
		$thread_check = $this->check_thread($board, $query->result());

		// process entire thread and store in $result array
		$result = array();

		foreach ($query->result() as $post)
		{
			if ($process === TRUE)
			{
				if ($post->op == 0)
				{
					$this->process_post($board, $post, $clean, $realtime);
				}
				else
				{
					$this->process_post($board, $post, TRUE, TRUE);
				}
			}

			if ($post->op == 0)
			{
				$result[$post->thread_num]['posts'][$post->num . (($post->subnum == 0) ? '' : '_' . $post->subnum)] = $post;
			}
			else
			{
				$result[$post->num]['op'] = $post;
			}
		}

		// free up memory
		$query->free_result();

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

		// reset module settings
		$this->backlinks_hash_only_url = FALSE;
		$this->realtime = FALSE;

		return array('result' => $result, 'thread_check' => $thread_check);
	}


	/**
	 * Get the gallery
	 *
	 * @param object $board
	 * @param int $page page to determine offset
	 * @param array $options modifiers
	 * @return array|bool FALSE on failure (probably caused by faulty $options) or the gallery array
	 */
	private function p_get_gallery($board, $page = 1, $options = array())
	{
		// default variables
		$per_page = 200;
		$process = TRUE;
		$clean = TRUE;
		$type = 'by_thread';

		// override defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// determine type
		switch ($type)
		{
			case 'by_image':

				$query = $this->db->query('
					SELECT * FROM ' . $this->radix->get_table($board) . '
					' . $this->sql_media_join($board) . '
					' . $this->sql_report_join($board) . '
					WHERE ' . $this->radix->get_table($board) . '.`media_id` <> 0
					ORDER BY timestamp DESC LIMIT ?, ?
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);
				break;

			case 'by_thread':

				$query = $this->db->query('
					SELECT *
					FROM
					(
						SELECT *, thread_num as unq_thread_num
						FROM ' . $this->radix->get_table($board, '_threads') . '
						ORDER BY time_op DESC LIMIT ?, ?
					) AS t
					LEFT JOIN ' . $this->radix->get_table($board) . ' AS g
						ON g.num = t.unq_thread_num AND g.subnum = 0
					' . $this->sql_media_join($board, 'g') . '
					' . $this->sql_report_join($board, 'g') . '
				',
					array(
						intval(($page * $per_page) - $per_page),
						intval($per_page)
					)
				);
				break;

			default:
				log_message('error', 'post.php/get_gallery: invalid or missing type argument');
				return FALSE;
		}



		// cache the count or get the cached count
		if(!$threads = $this->cache->get('foolfuuka_' . config_item('random_id') . '_board_' . $board->id . '_get_gallery_threads_count_' . $type))
		{
			switch ($type)
			{
				case 'by_image':
					$query_threads = $this->db->query('
						SELECT SUM(total) AS threads
						FROM ' . $this->radix->get_table($board, '_images') . '
					');
					break;

				case 'by_thread':
					$query_threads = $this->db->query('
						SELECT COUNT(thread_num) AS threads
						FROM ' . $this->radix->get_table($board, '_threads') . '
					');
					break;
			}

			$threads = $query_threads->row()->threads;
			$query_threads->free_result();

			// start caching only over 2500 threads so we can keep boards with little number of threads dynamic
			if($threads > 2500)
			{
				$this->cache->save(
					'foolfuuka_' . config_item('random_id') . '_board_' . $board->id . '_get_gallery_threads_count_' . $type,
					$threads,
					1800
				);
			}
		}

		// populate result array
		$results = array();

		foreach ($query->result() as $key => $post)
		{
			if ($post->preview_orig)
			{
				$this->process_post($board, $post, $clean, $process);
				$results[$post->num] = $post;
			}
		}

		return array('threads' => $results, 'total_found' => $threads);
	}


	/**
	 * Get reported posts
	 *
	 * @param int $page page to determine offset
	 * @return array the reported posts
	 */
	private function p_get_reports($page = 1)
	{
		$this->load->model('report_model', 'report');

		// populate multi_posts array to fetch
		$multi_posts = array();

		foreach ($this->report->get_reports($page) as $post)
		{
			$multi_posts[] = array(
				'board_id' => $post->board_id,
				'doc_id'   => array($post->doc_id)
			);
		}

		return array('posts' => $this->get_multi_posts($multi_posts), 'total_found' => $this->report->get_count());
	}


	/**
	 * Get multiple posts from multiple boards
	 *
	 * @param array $multi_posts array of array('board_id'=> , 'doc_id' => )
	 * @param null|string $order_by the entire "ORDER BY ***" string
	 * @return array|bool
	 */
	private function p_get_multi_posts($multi_posts = array(), $order_by = NULL)
	{
		// populate sql array
		$sql = array();

		foreach ($multi_posts as $posts)
		{
			// posts => [board_id, doc_id => [1, 2, 3]]
			if (isset($posts['board_id']) && isset($posts['doc_id']))
			{
				$board = $this->radix->get_by_id($posts['board_id']);
				$sql[] = '
					(
						SELECT *, CONCAT(' . $this->db->escape($posts['board_id']) . ') AS board_id
						FROM ' . $this->radix->get_table($board) . ' AS g
						' . $this->sql_media_join($board, 'g') . '
						' . $this->sql_report_join($board, 'g') . '
						WHERE g.`doc_id` = ' . implode(' OR g.`doc_id` = ', $posts['doc_id']) . '
					)
				';
			}
		}

		if (empty($sql))
		{
			return array();
		}

		// order results properly with string argument
		$query = $this->db->query(implode('UNION', $sql) . ($order_by ? $order_by : ''));

		if ($query->num_rows() == 0)
		{
			return array();
		}

		// populate results array
		$results = array();

		foreach ($query->result() as $post)
		{
			$board = $this->radix->get_by_id($post->board_id);
			$post->board = $board;

			$this->process_post($board, $post);

			array_push($results, $post);
		}

		return $results;
	}


	/**
	 * Get the post to determine the thread number
	 *
	 * @param object $board
	 * @param int $num the post number
	 * @param int $subnum the post subnum
	 * @return bool|object FALSE if not found, the row if found
	 */
	private function p_get_post_thread($board, $num, $subnum = 0)
	{
		$query = $this->db->query('
			SELECT num, thread_num, subnum
			FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE num = ? AND subnum = ? LIMIT 0, 1
		',
			array($num, $subnum)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		return $query->row();
	}


	/**
	 * Get a single post by num/subnum with _processed data
	 *
	 * @param object $board
	 * @param int|string $num post number
	 * @param int $subnum post subnumber
	 * @param bool $build build the HTML for the post box
	 * @return bool|object FALSE if not found, or the row with processed data appended
	 */
	private function p_get_post_by_num($board, $num, $subnum = 0, $build = FALSE)
	{
		if (strpos($num, '_') !== FALSE && $subnum == 0)
		{
			$num_array = explode('_', $num);

			if (count($num_array) != 2)
			{
				return FALSE;
			}

			$num = $num_array[0];
			$subnum = $num_array[1];
		}

		$num = intval($num);
		$subnum = intval($subnum);

		$query = $this->db->query('
			SELECT *
			FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE num = ? AND subnum = ? LIMIT 0, 1
		',
			array($num, $subnum)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		// process results
		$post = $query->row();
		$this->process_post($board, $post, TRUE, $build);

		return $post;
	}


	/**
	 * @param object $board
	 * @param int $doc_id
	 * @return bool|object
	 */
	private function p_get_post_by_doc_id($board, $doc_id)
	{
		$query = $this->db->query('
			SELECT * ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE doc_id = ? LIMIT 0, 1
		',
			array($doc_id)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		return $query->row();
	}


	/**
	 * Get a post by doc_id
	 *
	 * @param object $board
	 * @param int $doc_id the post do_id
	 * @return bool|object FALSE if not found, the row if found
	 */
	private function p_get_by_doc_id($board, $doc_id)
	{
		$query = $this->db->query('
			SELECT * FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			' . $this->sql_report_join($board) . '
			WHERE doc_id = ? LIMIT 0, 1;
		',
			array($doc_id)
		);

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		return $query->row();
	}


	/**
	 * Returns an URL to the full media from the original filename
	 *
	 * @param object $board
	 * @param string $media_filename the original filename
	 * @return array the media link
	 */
	private function p_get_full_media($board, $media_filename)
	{
		$query = $this->db->query('
			SELECT * FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE media_orig = ?
			ORDER BY num DESC LIMIT 0, 1
		',
			array($media_filename)
		);

		if ($query->num_rows() == 0)
		{
			return array('error_type' => 'no_record', 'error_code' => 404);
		}

		$result = $query->row();
		$media_link = $this->get_media_link($board, $result);

		if ($media_link === FALSE)
		{
			$this->process_post($board, $result, TRUE);
			return array('error_type' => 'not_on_server', 'error_code' => 404, 'result' => $result);
		}

		return array('media_link' => $media_link);
	}


	


	/**
	 * Use the search system to delete lots of messages at once
	 *
	 * @param type $board
	 * @param type $data the data otherwise sent to the search system
	 * @param type $options modifiers
	 */
	function p_delete_by_search($board, $data, $options = array())
	{
		// for safety don't let deleting more than 1000 entries at once
		if(!isset($options['limit']))
			$options['limit'] = 1000;

		$result = $this->get_search($board, $data, $options);

		// send back the error
		if(isset($result['error']))
			return $result;


		if(isset($result['posts'][0]['posts']))
		{
			$results = $result['posts'][0]['posts'];
		}
		else
		{
			return FALSE;
		}

		foreach($results as $post)
		{
			$this->delete(isset($post->board)?$post->board:$board, array('doc_id' => $post->doc_id, 'password' => ''));
		}
	}


	/**
	 * Delete the post and eventually the entire thread if it's OP
	 * Also deletes the images when it's the only post with that image
	 *
	 * @param object $board
	 * @param array $post the post data necessary for deletion (password, doc_id)
	 * @return array|bool
	 */
	private function p_delete($board, $post)
	{
		// $post => [doc_id, password, type]
		$query = $this->db->query('
			SELECT * FROM ' . $this->radix->get_table($board) . '
			' . $this->sql_media_join($board) . '
			WHERE doc_id = ? LIMIT 0, 1
		',
			array($post['doc_id'])
		);

		if ($query->num_rows() == 0)
		{
			log_message('debug', 'post.php/delete: invalid doc_id for post or thread');
			return array('error' => __('There\'s no such a post to be deleted.'));
		}

		// store query results
		$row = $query->row();

		$phpass = new PasswordHash(
			$this->config->item('phpass_hash_strength', 'tank_auth'),
			$this->config->item('phpass_hash_portable', 'tank_auth')
		);

		// validate password
		if ($phpass->CheckPassword($post['password'], $row->delpass) !== TRUE && !$this->auth->is_mod_admin())
		{
			log_message('debug', 'post.php/delete: invalid password');
			return array('error' => __('The password you inserted did not match the post\'s deletion password.'));
		}

		// delete media file for post
		if ($row->total == 1 && !$this->delete_media($board, $row))
		{
			log_message('error', 'post.php/delete: unable to delete media from post');
			return array('error' => __('Unable to delete thumbnail for post.'));
		}

		// remove the thread
		$this->db->query('
				DELETE
				FROM ' . $this->radix->get_table($board) . '
				WHERE doc_id = ?
			',
			array($row->doc_id)
		);

		// get rid of the entry from the myisam _search table
		if($board->myisam_search)
		{
			$this->db->query("
				DELETE
				FROM " . $this->radix->get_table($board, '_search') . "
				WHERE doc_id = ?
			", array($row->doc_id));
		}

		// purge existing reports for post
		$this->db->delete('reports', array('board_id' => $board->id, 'doc_id' => $row->doc_id));

		// purge thread replies if thread_num
		if ($row->op == 1) // delete: thread
		{
			$thread = $this->db->query('
				SELECT * FROM ' . $this->radix->get_table($board) . '
				' . $this->sql_media_join($board) . '
				WHERE thread_num = ?
			',array($row->num));

			// thread replies found
			if ($thread->num_rows() > 0)
			{
				// remove all media files
				foreach ($thread->result() as $p)
				{
					if (!$this->delete_media($board, $p))
					{
						log_message('error', 'post.php/delete: unable to delete media from thread op');
						return array('error' => __('Unable to delete thumbnail for thread replies.'));
					}

					// purge associated reports
					$this->db->delete('reports', array('board_id' => $board->id, 'doc_id' => $p->doc_id));
				}

				// remove all replies
				$this->db->query('
					DELETE FROM ' . $this->radix->get_table($board) . '
					WHERE thread_num = ?
				', array($row->num));

				// get rid of the replies from the myisam _search table
				if($board->myisam_search)
				{
					$this->db->query("
						DELETE
						FROM " . $this->radix->get_table($board, '_search') . "
						WHERE thread_num = ?
					", array($row->num));
				}
			}
		}

		return TRUE;
	}


	/**
	 * Delete media for the selected post
	 *
	 * @param object $board
	 * @param object $post the post choosen
	 * @param bool $media if full media should be deleted
	 * @param bool $thumb if thumbnail should be deleted
	 * @return bool TRUE on success or if it didn't exist in first place, FALSE on failure
	 */
	private function p_delete_media($board, $post, $media = TRUE, $thumb = TRUE)
	{
		if (!$post->media_hash)
		{
			// if there's no media, it's all OK
			return TRUE;
		}

		// delete media file only if there is only one image OR the image is banned
		if ($post->total == 1 || $post->banned == 1 || $this->auth->is_mod_admin())
		{
			if ($media === TRUE)
			{
				$media_file = $this->get_media_dir($board, $post);
				if (file_exists($media_file))
				{
					if (!unlink($media_file))
					{
						log_message('error', 'post.php/delete_media: unable to remove ' . $media_file);
						return FALSE;
					}
				}
			}

			if ($thumb === TRUE)
			{
				// remove OP thumbnail
				$post->op = 1;
				$thumb_file = $this->get_media_dir($board, $post, TRUE);
				if (file_exists($thumb_file))
				{
					if (!unlink($thumb_file))
					{
						log_message('error', 'post.php/delete_media: unable to remove ' . $thumb_file);
						return FALSE;
					}
				}

				// remove reply thumbnail
				$post->op = 0;
				$thumb_file = $this->get_media_dir($board, $post, TRUE);
				if (file_exists($thumb_file))
				{
					if (!unlink($thumb_file))
					{
						log_message('error', 'post.php/delete_media: unable to remove ' . $thumb_file);
						return FALSE;
					}
				}
			}
		}

		return TRUE;
	}


	/**
	 * Sets the media hash to banned through all boards
	 *
	 * @param string $hash the hash to ban
	 * @param bool $delete if it should delete the media through all the boards
	 * @return bool
	 */
	private function p_ban_media($media_hash, $delete = FALSE)
	{
		// insert into global banned media hash
		$this->db->query('
			INSERT IGNORE INTO ' . $this->db->protect_identifiers('banned_md5', TRUE) . '
			(
				md5
			)
			VALUES
			(
				?
			)
		',
			array($media_hash)
		);

		// update all local _images table
		foreach ($this->radix->get_all() as $board)
		{
			$this->db->query('
				INSERT INTO ' . $this->radix->get_table($board, '_images') . '
				(
					media_hash, media, preview_op, preview_reply, total, banned
				)
				VALUES
				(
					?, ?, ?, ?, ?, ?
				)
				ON DUPLICATE KEY UPDATE banned = 1
			',
				array($media_hash, NULL, NULL, NULL, 0, 1)
			);
		}

		// delete media files if TRUE
		if ($delete === TRUE)
		{
			$posts = array();

			foreach ($this->radix->get_all() as $board)
			{
				$posts[] = '
					(
						SELECT *, CONCAT(' . $this->db->escape($board->id) . ') AS board_id
						FROM ' . $this->radix->get_table($board) . '
						WHERE media_hash = ' . $this->db->escape($media_hash) . '
					)
				';
			}

			$query = $this->db->query(implode('UNION', $posts));
			if ($query->num_rows() == 0)
			{
				log_message('error', 'post.php/ban_media: unable to locate posts containing media_hash');
				return FALSE;
			}

			foreach ($query->result() as $post)
			{
				$this->delete_media($this->radix->get_by_id($post->board_id), $post);
			}
		}

		return TRUE;
	}


	/**
	 * Recheck all banned images and remove eventual leftover images
	 *
	 * @param object $board
	 */
	private function p_recheck_banned($board = FALSE)
	{
		if($board === FALSE)
		{
			$boards = $this->radix->get_all();
		}
		else
		{
			$boards = array($board);
			unset($board);
		}

		foreach($boards as $board)
		{
			$query = $this->db->query('
				SELECT *
				FROM ' . $this->radix->get_table($board, '_images') . '
				WHERE banned = 1
			');

			foreach($query->result() as $i)
			{
				if(!is_null($i->preview_op))
				{
					$op = get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' .
						$board->shortname . '/thumb/' .
						substr($i->preview_op, 0, 4) . '/' . substr($i->preview_op, 4, 2) . '/' .
						$i->preview_op;

					if(file_exists($op))
					{
						unlink($op);
					}
				}

				if(!is_null($i->preview_reply))
				{
					$reply = get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' .
						$board->shortname . '/thumb/' .
						substr($i->preview_reply, 0, 4) . '/' . substr($i->preview_reply, 4, 2) . '/' .
						$i->preview_reply;

					if(file_exists($reply))
					{
						unlink($reply);
					}
				}

				if(!is_null($i->media))
				{
					$media = get_setting('fs_fuuka_boards_directory', FOOLFUUKA_BOARDS_DIRECTORY) . '/' .
						$board->shortname . '/image/' .
						substr($i->media, 0, 4) . '/' . substr($i->media, 4, 2) . '/' .
						$i->media;

					if(file_exists($media))
					{
						unlink($media);
					}
				}

			}

		}
	}

}

/* end of file board.php */