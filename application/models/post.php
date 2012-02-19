<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Post extends CI_Model
{

	var $existing_posts = array();
	var $backlinks = array();
	var $features = TRUE;
	var $realtime = FALSE;
	// I'd love to get rid of these but there seems to be no other way to pass
	// more parameters to callbacks
	var $current_row = null;
	var $backlinks_hash_only_url = FALSE;

	function __construct($id = NULL)
	{
		parent::__construct();
	}


	/**
	 * Returns the name of the correct table, protected in oblique quotes
	 *
	 * @param type $board
	 * @return type
	 */
	function get_table($board)
	{
		if (get_setting('fs_fuuka_boards_db'))
		{
			return $this->table = $this->db->protect_identifiers(get_setting('fs_fuuka_boards_db')) . '.' . $this->db->protect_identifiers($board->shortname);
		}
		return $this->table = $this->db->protect_identifiers('board_' . $board->shortname, TRUE);
	}


	/**
	 * Returns the SQL string to get the report data with the post
	 *
	 * @param type $board
	 * @return type
	 */
	function get_sql_report($board)
	{
		if(!$this->tank_auth->is_allowed())
			return '';

		return '
					LEFT JOIN
					(
						SELECT id as report_id, post as report_post, reason as report_reason, status as report_status
						FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
						WHERE `board` = ' . $board->id . '
					) as q
					ON
					' . $this->get_table($board) . '.`doc_id`
					=
					' . $this->db->protect_identifiers('q') . '.`report_post`
				';
	}


	/**
	 * Returns the SQL string to get the report data with the post
	 * Special, to be used if there are other joins in the SQL query
	 *
	 * @param type $board
	 * @return type
	 */
	function get_sql_report_after_join($board)
	{
		if(!$this->tank_auth->is_allowed())
			return '';

		return '
					LEFT JOIN
					(
						SELECT id as report_id, post as report_post, reason as report_reason, status as report_status, created as report_created
						FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
						WHERE `board` = ' . $board->id . '
					) as q
					ON
					g.`doc_id`
					=
					' . $this->db->protect_identifiers('q') . '.`report_post`
				';
	}


	/**
	 * Load the last posts created, with said delay and minimum doc_id
	 * Function mainly used by the Live system
	 *
	 * @param type $board
	 * @param type $limit
	 * @param type $delay
	 * @param type $min_doc_id
	 * @return type
	 */
	function get_with_delay($board, $limit = 500, $delay = 0, $latest_doc_id = 0, $inferior_doc_id = NULL)
	{

		if(is_null($inferior_doc_id))
		{
			$query = $this->db->query('
				SELECT *
				FROM ' . $this->get_table($board) . '
				WHERE doc_id > ?
				AND timestamp < ?
				ORDER BY doc_id DESC
				LIMIT 0, '.intval($limit).'
			', array($latest_doc_id, time() - $delay));
		}
		else
		{
			$query = $this->db->query('
				SELECT *
				FROM ' . $this->get_table($board) . '
				WHERE doc_id < ?
				AND timestamp < ?
				ORDER BY doc_id DESC
				LIMIT 0, '.intval($limit).'
			', array($inferior_doc_id, time() - $delay));
		}

		if($query->num_rows() == 0)
		{
			return FALSE;
		}

		$result = $query->result();
		$query->free_result();
		return $result;
	}


	function get_sql_poster_after_join()
	{
		if(!$this->tank_auth->is_allowed())
			return '';

		return '
					LEFT JOIN
					(
						SELECT id as poster_id_join, ip as poster_ip, banned as poster_banned
						FROM ' . $this->db->protect_identifiers('posters', TRUE) . '
					) as p
					ON
					g.`poster_id`
					=
					' . $this->db->protect_identifiers('p') . '.`poster_id_join`
				';

	}


	/**
	 * Returns the last 200 threads that were made, deleted or not, together
	 * with the number of images and posts inside of them
	 *
	 * @param type $board
	 * @return type
	 */
	function get_gallery($board)
	{
		$query = $this->db->query('
			SELECT DISTINCT *
			FROM ' . $this->get_table($board) . '
			WHERE parent = 0
			ORDER BY num DESC
			LIMIT 0, 200
		');

		$sql = array();
		$result = $query->result();
		foreach ($result as $row)
		{
			$sql[] = '
				(
					SELECT count(*) AS count_all, count(distinct preview) AS count_images, parent
					FROM ' . $this->get_table($board) . '
					WHERE parent = ' . intval($row->num) . '
				)
			';
		}

		$sql = implode('UNION', $sql) . '
			ORDER BY parent DESC
		';

		$query2 = $this->db->query($sql);
		$result2 = $query2->result();
		foreach ($result as $key => $row)
		{
			$result[$key]->count_all = 0;
			$result[$key]->count_images = 0;
			// it should basically always be the first found anyway unless not found
			foreach ($result2 as $k => $r)
			{
				if ($r->parent == $row->num)
				{
					$result[$key]->count_all = $result2[$k]->count_all;
					$result[$key]->count_images = $result2[$k]->count_images;
				}
			}
		}

		$result_num_as_key = array();
		foreach ($result as $key => $post)
		{
			$this->process_post($board, $post, TRUE, TRUE);
			$result_num_as_key[$post->num] = $post;
		}

		return $result_num_as_key;
	}


	/**
	 *
	 *
	 * @param type $page
	 * @param type $process
	 * @return type
	 */
	function get_latest($board, $page = 1, $options = array())
	{
		// defaults
		$per_page = 20;
		$process = TRUE;
		$clean = TRUE;
		$type = 'by_post'; // ghost, by_thread
		// overwrite defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// get exactly 20 be it thread starters or parents with distinct parent

		switch ($type)
		{
			case 'ghost':
				$query = $this->db->query('
					SELECT *
					FROM
					(
						SELECT parent as unq_parent, MAX(b.timestamp), b.num
						FROM (
							SELECT num, parent, subnum, timestamp, email
							FROM ' . $this->get_table($board) . '
							WHERE subnum > 0
							AND (email <> \'sage\' OR email IS NULL OR (email = \'sage\' AND parent = 0))
							ORDER BY timestamp DESC
							LIMIT 0, 100000
						) AS b
						GROUP BY unq_parent
						ORDER BY MAX(b.timestamp) DESC
						LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
					) AS t
					LEFT JOIN ' . $this->get_table($board) . ' AS g
						ON g.num = t.unq_parent AND g.subnum = 0
					' . $this->get_sql_report_after_join($board) . '
				');

				// this might not actually be working, we need to test it somewhere
				$query_pages = $this->db->query('
					SELECT FLOOR(count(e.num)/' . intval($per_page) . ')+1 as pages
					FROM
					(
						SELECT DISTINCT(parent), subnum, num
						FROM ' . $this->get_table($board) . '
						WHERE subnum <> 0
						LIMIT 0, ' . ( ((intval($page) > 13)?(intval($page)+5):15) * intval($per_page)) .'
					) AS e
				');
				break;

			case 'by_thread':
				$query = $this->db->query('
					SELECT *
					FROM
					(
						SELECT DISTINCT(num) as unq_parent
						FROM ' . $this->get_table($board) . '
						WHERE parent = 0 AND subnum = 0
						ORDER BY num DESC
						LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
					) AS t
					LEFT JOIN ' . $this->get_table($board) . ' AS g
						ON g.num = t.unq_parent AND g.subnum = 0
					' . $this->get_sql_report_after_join($board) . '
				');

				$query_pages = $this->db->query('
					SELECT FLOOR(count(e.num)/' . intval($per_page) . ')+1 as pages, parent
					FROM
					(
						SELECT num, parent
						FROM ' . $this->get_table($board) . '
						WHERE parent = 0 AND subnum = 0
						LIMIT 0, ' . ( ((intval($page) > 13)?(intval($page)+5):15) * intval($per_page)) .'
					) as e
				');
				break;

			case 'by_post':
				$query = $this->db->query('
					SELECT *
					FROM
					(
						SELECT IF(b.parent = 0, b.num, b.parent) as unq_parent, MAX(b.num), b.num
						FROM (
							SELECT num, parent, email
							FROM ' . $this->get_table($board) . '
							WHERE email <> \'sage\' OR email IS NULL OR (email = \'sage\' AND parent = 0)
							AND subnum = 0
							ORDER BY num DESC
							LIMIT 0, 100000
						) AS b
						GROUP BY unq_parent
						ORDER BY MAX(b.num) DESC
						LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
					) AS t
					LEFT JOIN ' . $this->get_table($board) . ' AS g
						ON g.num = t.unq_parent AND g.subnum = 0
					' . $this->get_sql_report_after_join($board) . '
				');

				$query_pages = $this->db->query('
					SELECT FLOOR(count(e.num)/' . intval($per_page) . ')+1 as pages, parent
					FROM
					(
						SELECT num, parent
						FROM ' . $this->get_table($board) . '
						WHERE parent = 0 AND subnum = 0
						LIMIT 0, ' . ( ((intval($page) > 13)?(intval($page)+5):15) * intval($per_page)) .'
					) as e
				');
				break;
			default:
				log_message('error', 'post.php get_latest(): wrong or no list type selected');
				return FALSE;
		}

		if(isset($query_pages))
		{
			$pages = $query_pages->result();
			$query_pages->free_result();
			$pages = $pages[0]->pages;
			if($pages <= 1)
				$pages = NULL;
		}
		else
		{
			$pages = NULL;
		}


		if ($query->num_rows() == 0)
		{
			return array('result' => array('posts' => array(), 'op' => array()), 'pages' => $pages);
		}

		// echo '<pre>'.print_r($query->result(), TRUE).'</pre>';die();

		// get all the posts
		$threads = array();
		$sql = array();
		$sqlcount = array();
		foreach ($query->result() as $row)
		{
			$threads[] = $row->unq_parent;
			$sql[] = '
				(
					SELECT *
					FROM ' . $this->get_table($board) . '
					' . $this->get_sql_report($board) . '
					WHERE parent = ' . $row->unq_parent . '
					ORDER BY num DESC, subnum DESC
					LIMIT 0, 5
				)
			';

			$sqlcount[] = '
				(
					SELECT count(*) AS count_all, count(distinct preview) AS count_images, num, parent
					FROM ' . $this->get_table($board) . '
					WHERE parent = ' . $row->unq_parent . '
				)
			';
		}

		$sql = implode('UNION', $sql) . '
			ORDER BY num DESC
		';

		$sqlcount = implode('UNION', $sqlcount);

		// quite disordered array
		$query2 = $this->db->query($sql);
		$querycount = $this->db->query($sqlcount);

		// associative array with keys
		$result = array();

		$posts = array_merge($query->result(), array_reverse($query2->result()));

		// cool amount of posts: throw the nums in the cache
		foreach ($posts as $post)
		{
			if ($post->parent == 0)
			{
				$this->existing_posts[$post->num][] = $post->num;
			}
			else
			{
				if ($post->subnum == 0)
					$this->existing_posts[$post->parent][] = $post->num;
				else
					$this->existing_posts[$post->parent][] = $post->num . ',' . $post->subnum;
			}
		}

		// order the array
		foreach ($posts as $post)
		{
			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}

			$post_num = ($post->parent > 0) ? $post->parent : $post->num;
			if (!isset($result[$post_num]['omitted']))
			{
				foreach ($querycount->result() as $counter)
				{
					if ($counter->parent == $post->num)
					{
						$result[$post_num]['omitted'] = $counter->count_all - 5;
						$result[$post_num]['images_omitted'] = $counter->count_images - 1;
					}
				}
			}

			if ($post->parent > 0)
			{
				// the first you create from a parent is the first thread
				$result[$post->parent]['posts'][] = $post;
				if ($post->preview)
				{
					$result[$post->parent]['images_omitted']--;
				}
			}
			else
			{
				// this should already exist
				$result[$post->num]['op'] = $post;
			}
		}

		// reorder threads, and the posts inside
		$result2 = array();
		foreach ($threads as $thread)
		{
			$result2[$thread] = $result[$thread];
		}

		// clean up, even if it's supposedly just little data
		$query->free_result();
		// this is a lot of data, clean it up
		$query2->free_result();

		return array('result' => $result2, 'pages' => $pages);
	}


	function get_posts_ghost($board, $page = 1, $options = array())
	{
		// defaults
		$per_page = 1000;
		$process = TRUE;
		$clean = TRUE;

		// overwrite defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// get exactly 20 be it thread starters or parents with different parent
		$query = $this->db->query('
			SELECT num, subnum, timestamp
			FROM ' . $this->get_table($board) . '
			WHERE subnum > 0
			ORDER BY timestamp DESC
			LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
		');

		// get all the posts
		$sql = array();
		$threads = array();
		foreach ($query->result() as $row)
		{
			$sql[] = '
				(
					SELECT *
					FROM ' . $this->get_table($board) . '
					WHERE num = ' . intval($row->num) . ' AND subnum = ' . intval($row->subnum) . '
				)
			';
		}

		$sql = implode('UNION', $sql) . '
			ORDER BY timestamp DESC
		';

		// clean up, even if it's supposedly just little data
		$query->free_result();

		// quite disordered array
		$query2 = $this->db->query($sql);

		// associative array with keys
		$result = array();

		// cool amount of posts: throw the nums in the cache
		foreach ($query2->result() as $post)
		{
			if ($post->parent == 0)
			{
				$this->existing_posts[$post->num][] = $post->num;
			}
			else
			{
				if ($post->subnum == 0)
					$this->existing_posts[$post->parent][] = $post->num;
				else
					$this->existing_posts[$post->parent][] = $post->num . ',' . $post->subnum;
			}
		}

		// order the array
		foreach ($query2->result() as $post)
		{
			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}
			// the first you create from a parent is the first thread
			$result['posts'][] = $post;
		}

		return $result;
	}


	function get_thread($board, $num, $options = array())
	{
		// defaults
		$process = TRUE;
		$clean = TRUE;
		$type = 'thread'; // from_doc_id, last_x
		$type_extra = array();
		/* examples
		 * last_x: array('last_limit' => 50)
		 * from_doc_id: array('latest_doc_id' => 34202349)
		 */

		$realtime = FALSE; // enables returning the formatted post in AJAX

		// overwrite defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		// module settings
		$this->backlinks_hash_only_url = TRUE;

		switch ($type)
		{
			case 'from_doc_id':

				if (!isset($type_extra['latest_doc_id']) || !is_natural($type_extra['latest_doc_id']))
				{
					log_message('error', 'No correct latest_doc_id set');
					return FALSE;
				}

				$query = $this->db->query('
					SELECT * FROM ' . $this->get_table($board) . '
					WHERE parent = ? AND doc_id > ?
					ORDER BY num, subnum ASC;
				', array($num, $type_extra['latest_doc_id']));

				break;

			case 'last_x':

				if (!isset($type_extra['last_limit']) || !is_natural($type_extra['last_limit']))
				{
					log_message('error', 'No correct last_limit set');
					return FALSE;
				}

				$query = $this->db->query('
					SELECT * FROM
					(
						(
							SELECT * FROM ' . $this->get_table($board) . '
							WHERE num = ? OR parent = ?
							ORDER BY num DESC, subnum DESC
							LIMIT ' . intval($type_extra['last_limit']) . '
						)
						UNION
						(
							SELECT * FROM ' . $this->get_table($board) . '
							WHERE num = ?
							LIMIT 0, 1
						)
					)
					AS x
					ORDER BY num, subnum ASC;
				', array($num, $num, $num));

				break;

			case 'thread':

				$query = $this->db->query('
					SELECT * FROM ' . $this->get_table($board) . '
					WHERE num = ? OR parent = ?
					ORDER BY num, subnum ASC;
				', array($num, $num));

				break;

			case 'ghosts':

				$query = $this->db->query('
					SELECT * FROM ' . $this->get_table($board) . '
					WHERE parent = ? AND subnum != 0
					ORDER BY num, subnum ASC;
				', array($num));

				break;

			default:
				log_message('error', 'post.php get_thread(): wrong or no type selected');
				return FALSE;
		}

		$result = array();

		// thread not found
		if ($query->num_rows() == 0)
		{
			// module settings RESTORE
			$this->backlinks_hash_only_url = FALSE;

			return FALSE;
		}

		// thread is for realtime
		if ($realtime === TRUE)
		{
			$this->realtime = TRUE;
		}

		foreach ($query->result() as $post)
		{
			if ($post->parent == 0)
			{
				$this->existing_posts[$post->num][] = $post->num;
			}
			else
			{
				if ($post->subnum == 0)
					$this->existing_posts[$post->parent][] = $post->num;
				else
					$this->existing_posts[$post->parent][] = $post->num . ',' . $post->subnum;
			}
		}

		foreach ($query->result() as $post)
		{
			if ($process === TRUE && $post->parent != 0)
			{
				$this->process_post($board, $post, $clean, $realtime);
			}
			else if ($process === TRUE && $post->parent == 0)
			{
				$this->process_post($board, $post, TRUE, TRUE);
			}

			if ($post->parent > 0)
			{
				//echo $post->num . (($post->subnum == 0)?'':'_'.$post->subnum).'<br/>';
				$result[$post->parent]['posts'][$post->num . (($post->subnum == 0) ? '' : '_' . $post->subnum)] = $post;
			}
			else
			{
				$result[$post->num]['op'] = $post;
			}
		}
		// this could be a lot of data, clean it up
		$query->free_result();

		// stick the backlinks
		foreach ($this->backlinks as $key => $item)
		{
			if (isset($result[$num]['op']) && $result[$num]['op']->num == $key)
			{
				$result[$num]['op']->backlinks = array_unique($item);
			}
			else if (isset($result[$num]['posts'][$key]))
			{
				$result[$num]['posts'][$key]->backlinks = array_unique($item);
			}
		}

		// module settings RESTORE
		$this->backlinks_hash_only_url = FALSE;
		$this->realtime = FALSE;

		//print_r($result);
		return $result;
	}


	function get_post_thread($board, $num, $subnum = 0)
	{
		$query = $this->db->query('
				SELECT num, parent, subnum FROM ' . $this->get_table($board) . '
				' . $this->get_sql_report($board) . '
				WHERE num = ? AND subnum = ?
				LIMIT 0, 1;
			', array($num, $subnum));

		if ($query->num_rows() == 0)
		{
			return FALSE;
		}

		foreach ($query->result() as $post)
		{
			return $post;
		}

		return FALSE;
	}


	function get_multi_posts($posts = array())
	{
		$query = array();
		foreach ($posts as $post)
		{
			// post [board_id, doc_id = array(1,2,3..)]
			$board = $this->radix->get_by_id($post['board_id']);
			$query[] = '
				(
					SELECT *, CONCAT(' . $this->db->escape($post['board_id']) . ') as board_id
					FROM ' . $this->get_table($board) . ' as g
					' . $this->get_sql_report_after_join($board) . '
					' . $this->get_sql_poster_after_join() . '
					WHERE g.`doc_id` = ' . implode(' OR g.`doc_id` = ', $post['doc_id']) . '
				)
			';
		}

		$query = implode(' UNION ', $query);
		$query = $this->db->query($query);

		if ($query->num_rows() == 0)
		{
			return array();
		}

		$results = array();
		foreach ($query->result() as $post)
		{
			$board = $this->radix->get_by_id($post->board_id);
			$results[] = array('board' => $board, 'post' => $post);
		}

		return $results;
	}


	function get_search($board, $search, $options = array())
	{
		// defaults
		$process = TRUE;
		$clean = TRUE;

		// overwrite defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		if ($search['page'])
		{
			if (!is_numeric($search['page']))
			{
				show_404();
			}
			$search['page'] = intval($search['page']);
		}
		else
		{
			$search['page'] = 1;
		}

		if ($board->sphinx && ($search['subject'] || $search['username'] || $search['tripcode'] || $search['text']))
		{
			$match = array();
			$where = array();

			/*
			 * SPHINXQL CONNECTION
			 */

			$this->load->library('SphinxQL');
			$this->sphinxql->SetServer(
				get_setting('fs_sphinx_hostname') ? get_setting('fs_sphinx_hostname') : '127.0.0.1', get_setting('fs_sphinx_port') ? get_setting('fs_sphinx_port') : 9306
			);

			/*
			 * FULLTEXT MATCH
			 */
			if ($search['subject'])
			{
				$match['@title'] = $this->sphinxql->EscapeString(urldecode($search['subject']));
			}
			if ($search['username'])
			{
				$match['@name'] = $this->sphinxql->EscapeString(urldecode($search['username']));
			}
			if ($search['tripcode'])
			{
				$match['@trip'] = $this->sphinxql->EscapeString(urldecode($search['tripcode']));
			}
			if ($search['text'])
			{
				if (mb_strlen($search['text']) < 2)
				{
					return array('error' => _('The text you were searching for was too short. It must be at least two characters long.'));
				}
				$match['@comment'] = $this->sphinxql->HalfEscapeString(urldecode($search['text']));
			}
			$AGAINST = '';
			foreach ($match as $k => $v)
			{
				$AGAINST .= "{$k} {$v} ";
			}

			/*
			 * WHERE CONDITIONS
			 */
			if ($search['deleted'] == "deleted")
			{
				$where['is_deleted'] = 1;
			}
			if ($search['deleted'] == "not-deleted")
			{
				$where['is_deleted'] = 0;
			}
			if ($search['ghost'] == "only")
			{
				$where['is_internal'] = 1;
			}
			if ($search['ghost'] == "none")
			{
				$where['is_internal'] = 0;
			}
			if ($search['filter'] != "")
			{
				$filters = explode('-', $search['filter']);
				unset($search['filter']);

				foreach ($filters as $key => $value)
				{
					$search['filter'][$value] = TRUE;
				}

				if (!empty($search['filter']['user']) || !empty($search['filter']['mod']) || !empty($search['filter']['admin']))
				{
					$where['cap'] = array();
					if (!empty($search['filter']['user']))
					{
						array_push($where['cap'], 1);
					}
					if (!empty($search['filter']['mod']))
					{
						array_push($where['cap'], 2);
					}
					if (!empty($search['filter']['admin']))
					{
						array_push($where['cap'], 3);
					}
				}

				if (!empty($search['filter']['text']))
				{
					$where['has_image'] = 1;
				}
				if (!empty($search['filter']['image']))
				{
					$where['has_image'] = 0;
				}
			}
			$CONDITIONS = array();
			foreach ($where as $k => $v)
			{
				if (is_array($v))
				{
					foreach ($v as $_k => $_v)
					{
						$CONDITIONS[] = "{$k} != {$_v}";
					}
				}
				else
				{
					$CONDITIONS[] = "{$k} = {$v}";
				}
			}

			/*
			 * QUERY SPHINXQL
			 */
			$search_result = $this->sphinxql->Query('
				SELECT *
				FROM ' . $board->shortname . '_ancient, ' . $board->shortname . '_main, ' . $board->shortname . '_delta
				WHERE MATCH(\''.trim($AGAINST).'\')
					' . ((!empty($CONDITIONS)) ? 'AND ' . implode(' AND ', $CONDITIONS) : '') . '
				ORDER BY timestamp ' . (($search['order'] == 'asc') ? 'ASC' : 'DESC') . '
				LIMIT ' . (($search['page'] * 25) - 25) . ', 25
				OPTION max_matches = 5000, reverse_scan = ' . (($search['order'] == 'asc') ? 0 : 1 ) . '
			');

			if (empty($search_result['matches']))
			{
				return array('posts' => array(), 'total_found' => 0);
			}

			/*
			 * QUERY MYSQL
			 */
			$sql = array();
			foreach ($search_result['matches'] as $key => $matches)
			{
				$sql[] = '
					(
						SELECT *
						FROM ' . $this->get_table($board) . '
						' . $this->get_sql_report($board) . '
						WHERE num = ' . $matches['num'] . ' AND subnum = ' . $matches['subnum'] . '
					)
				';
			}

			if ($search['order'] === 'asc')
			{
				$sql = implode('UNION', $sql) . '
					ORDER BY timestamp ASC
				';
			}
			else
			{
				$sql = implode('UNION', $sql) . '
					ORDER BY timestamp DESC
				';
			}

			$query = $this->db->query($sql);
			$total = $search_result['total_found'];
		}
		else // MySQL for both empty searchs AND non-sphinx indexed boards
		{
			$field = array(); $value = array(); $index = array();

			if ($search['subject'])
			{
				array_push($field, 'title LIKE ?');
				array_push($value, $search['subject']);
			}
			if ($search['username'])
			{
				array_push($field, 'name = ?');
				array_push($value, $search['username']);
				array_push($index, 'name_index');
			}
			if ($search['tripcode'])
			{
				array_push($field, 'trip = ?');
				array_push($value, $search['tripcode']);
				array_push($index, 'trip_index');

			}
			if ($search['text'])
			{
				if (mb_strlen($search['text']) < 2)
				{
					return array('error' => _('The text you were searching for was too short. It must be at least two characters long.'));
				}

				array_push($field, 'comment LIKE ?');
				array_push($value, $search['text']);
			}


			if ($search['deleted'] == "deleted")
			{
				array_push($field, 'deleted = ?');
				array_push($value, 1);
			}
			if ($search['deleted'] == "not-deleted")
			{
				array_push($field, 'deleted = ?');
				array_push($value, 0);
			}
			if ($search['ghost'] == "only")
			{
				array_push($field, 'subnum != ?');
				array_push($value, 0);
			}
			if ($search['ghost'] == "none")
			{
				array_push($field, 'subnum = ?');
				array_push($value, 0);
			}
			if ($search['filter'] != "")
			{
				$filters = explode('-', $search['filter']);
				unset($search['filter']);

				foreach ($filters as $k => $v)
				{
					$search['filter'][$v] = TRUE;
				}

				if (!empty($search['filter']['user']) || !empty($search['filter']['mod']) || !empty($search['filter']['admin']))
				{
					if (!empty($search['filter']['user']))
					{
						array_push($field, 'capcode != ?');
						array_push($value, 'N');
					}
					if (!empty($search['filter']['mod']))
					{
						array_push($field, 'capcode != ?');
						array_push($value, 'M');
					}
					if (!empty($search['filter']['admin']))
					{
						array_push($field, 'capcode != ?');
						array_push($value, 'A');
					}
				}

				if (!empty($search['filter']['text']))
				{
					array_push($field, 'media IS NOT ?');
					array_push($value, NULL);
				}
				if (!empty($search['filter']['image']))
				{
					array_push($field, 'media IS ?');
					array_push($value, NULL);
				}
			}
			if ($search['order'] === 'asc')
			{
				$order = 'ORDER BY timestamp ASC';
			}
			else
			{
				$order = 'ORDER BY timestamp DESC';
			}

			$query = $this->db->query('
				SELECT *
				FROM ' . $this->get_table($board) .
				((!empty($field)) ? ' WHERE ' . implode(' AND ', $field) : '') . '
				' . $order . '
				LIMIT ' . (($search['page'] * 25) - 25) . ', 25
			', $value);

			if ($query->num_rows() == 0)
			{
				return array('posts' => array(), 'total_found' => 0);
			}

			$count = $this->db->query('
				SELECT count(*) AS total_found
				FROM ' . $this->get_table($board) .
				((!empty($field)) ? ' WHERE ' . implode(' AND ', $field) : '') . '
				LIMIT 0, 5000
			', $value);

			$found = $count->result();
			$total = $found[0]->total_found;
		}

		/*
		 * PROCESS AND FORMAT
		 */
		foreach ($query->result() as $post)
		{
			if ($post->parent == 0)
			{
				$this->existing_posts[$post->num][] = $post->num;
			}
			else
			{
				if ($post->subnum == 0)
					$this->existing_posts[$post->parent][] = $post->num;
				else
					$this->existing_posts[$post->parent][] = $post->num . ',' . $post->subnum;
			}

			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}
			$result[0]['posts'][] = $post;
		}

		return array('posts' => $result, 'total_found' => $total);
	}


	function get_image($board, $hash, $page, $options = array())
	{
		// defaults
		$per_page = 25;
		$process = TRUE;
		$clean = TRUE;

		// urlsafe hash
		$hash = base64_encode(urlsafe_b64decode($hash));

		// overwrite defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		$query = $this->db->query('
			SELECT *
			FROM ' . $this->get_table($board) . '
			' . $this->get_sql_report($board) . '
			WHERE media_hash = ?
			ORDER BY num DESC
			LIMIT ' . (($page * $per_page) - $per_page) . ', ' . $per_page . '
		', array($hash));

		$query2 = $this->db->query('
			SELECT count(*) AS total_found
			FROM ' . $this->table . '
			WHERE media_hash = ?
			LIMIT 0, 5000
		', array($hash));

		if ($query->num_rows() == 0)
		{
			return array('posts' => array(), 'total_found' => 0);
		}

		foreach ($query->result() as $post)
		{
			if ($post->parent == 0)
			{
				$this->existing_posts[$post->num][] = $post->num;
			}
			else
			{
				if ($post->subnum == 0)
					$this->existing_posts[$post->parent][] = $post->num;
				else
					$this->existing_posts[$post->parent][] = $post->num . ',' . $post->subnum;
			}
		}

		foreach ($query->result() as $post)
		{
			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}
			// the first you create from a parent is the first thread
			$result[0]['posts'][] = $post;
		}

		$found = $query2->result();
		$search_result = array('total_found' => $found[0]->total_found);

		return array('posts' => $result, 'total_found' => $search_result['total_found']);
	}


	function get_similar_image($board, $hash, $page, $options = array())
	{
		// defaults
		$per_page = 25;
		$process = TRUE;
		$clean = TRUE;

		// overwrite defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}


		$query = $this->db->query('
			SELECT *
			FROM ' . $this->db->protect_identifiers('libpuz_signatures', TRUE) . '
			WHERE md5 = ?
			LIMIT 0, 1
		', array($hash));

		if($query->num_rows() == 0)
			return FALSE;

		$sig = $query->result();

		$signature = puzzle_uncompress_cvec($sig[0]->signature);

		$words = array();
		for ($i = 0; $i < 100; $i++){
			$words[] = $this->db->escape(mb_substr($signature,$i,10));
		}

		//$words = mb_substr($signature, 0, 10);
		$query->free_result();
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->db->protect_identifiers('libpuz_words', TRUE) . ' AS w
			LEFT JOIN ' . $this->db->protect_identifiers('libpuz_signatures', TRUE) . ' AS s
				ON w.signature_id = s.id
			WHERE w.word = ' . implode(' OR  w.word = ', $words) . '
			LIMIT 0, 20000
		');

		if($query->num_rows() == 0)
			return FALSE;

		$md5s = array();
		foreach($query->result() as $item)
		{
			$distance = puzzle_vector_normalized_distance(puzzle_uncompress_cvec($item->signature), $signature);
			if($distance < 0.5)
			{
				$md5s[] = $this->db->escape($item->md5);
			}
		}

		if(count($md5s) == 0)
			return FALSE;
		$query->free_result();
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->get_table($board) . '
			' . $this->get_sql_report($board) . '
			WHERE media_hash = ' . implode(' OR  media_hash = ', $md5s) . '
			LIMIT 0, 200
		');

		if($query->num_rows() == 0)
			return FALSE;

		foreach ($query->result() as $post)
		{
			if ($post->parent == 0)
			{
				$this->existing_posts[$post->num][] = $post->num;
			}
			else
			{
				if ($post->subnum == 0)
					$this->existing_posts[$post->parent][] = $post->num;
				else
					$this->existing_posts[$post->parent][] = $post->num . ',' . $post->subnum;
			}
		}

		foreach ($query->result() as $post)
		{
			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}
			// the first you create from a parent is the first thread
			$result[0]['posts'][] = $post;
		}


		return array('posts' => $result);
	}


	function get_full_image($board, $image)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->get_table($board) . '
			WHERE media_filename = ?
			ORDER BY num DESC
			LIMIT 0, 1
		', array($image));

		if ($query->num_rows() == 0)
		{
			return array('error_type' => 'no_record', 'error_code' => 404);
		}

		$result = $query->result();
		$result = $result[0];

		$image_href = $this->get_image_href($board, $result);

		if ($image_href == '')
		{
			$this->process_post($board, $result, TRUE);
			return array('error_type' => 'not_on_server', 'error_code' => 404, 'result' => $result);
		}

		return array('image_href' => $image_href);
	}


	function check_thread($board, $num)
	{
		if ($num == 0)
		{
			// 0 should be not thrown in an archive,
			// but it's a thread OP in normal boards...
			// either way, it shouldn't reach here if it's 0
			return array('invalid_thread' => TRUE);
		}

		// grab the entire thread
		$query = $this->db->query('
			SELECT * FROM ' . $this->get_table($board) . '
			' . $this->get_sql_report($board) . '
			WHERE num = ? OR parent = ?
			ORDER BY num, subnum ASC;
		', array($num, $num));

		// nothing related found
		if ($query->num_rows() == 0)
		{
			return array('invalid_thread' => TRUE);
		}

		$count = array('posts' => 0, 'images' => 0);
		$thread_op_present = FALSE;
		foreach ($query->result() as $post)
		{
			// we need to find if there's the OP in the list
			// let's be strict, we want the $num to be the OP
			if($post->parent == 0 && $post->subnum == 0 && $post->num === $num)
			{
				$thread_op_present = TRUE;
			}

			if ($post->media_filename)
			{
				$count['images']++;
			}
			$count['posts']++;
		}

		$query->free_result();

		if(!$thread_op_present)
		{
			// we didn't point to the thread OP, this is not a thread
			return array('invalid_thread' => TRUE);
		}

		if ($count['posts'] > 400)
		{
			if ($count['images'] > 200)
			{
				return array('thread_dead' => TRUE, 'disable_image_upload' => TRUE);
			}
			else
			{
				return array('thread_dead' => TRUE);
			}
		}
		else if ($count['images'] > 200)
		{
			return array('disable_image_upload' => TRUE);
		}

		return array('valid_thread' => TRUE);
	}


	/**
	 * POSTING FUNCTIONS
	 */
	function comment($board, $data, $options = array())
	{
		// defaults
		$allow_media = TRUE;

		// overwrite defaults
		foreach ($options as $key => $option)
		{
			$$key = $option;
		}

		if (check_stopforumspam_ip($this->input->ip_address()))
		{
			if ($data['media'] != FALSE || $data['media'] != '')
			{
				if (!unlink($data["media"]["full_path"]))
				{
					log_message('error', 'process_media: failed to remove media file from cache');
				}
			}
			return array('error' => _('Your IP has been identified as a spam proxy. You can\'t post from there.'));
		}

		$errors = array();
		if ($data['name'] == FALSE || $data['name'] == '')
		{
			$name = 'Anonymous';
			$trip = '';
		}
		else
		{
			$this->input->set_cookie('foolfuuka_reply_name', $data['name'], 60 * 60 * 24 * 30);
			$name_arr = $this->process_name($data['name']);
			$name = $name_arr[0];
			if (isset($name_arr[1]))
			{
				$trip = $name_arr[1];
			}
			else
			{
				$trip = '';
			}
		}

		if ($data['email'] == FALSE || $data['email'] == '')
		{
			$email = '';
		}
		else
		{
			$email = $data['email'];

			if($email != 'sage')
			{
				$this->input->set_cookie('foolfuuka_reply_email', $email, 60 * 60 * 24 * 30);
			}
		}

		if ($data['subject'] == FALSE || $data['subject'] == '')
		{
			$subject = '';
		}
		else
		{
			$subject = $data['subject'];
		}

		if ($data['comment'] == FALSE || $data['comment'] == '')
		{
			$comment = '';
		}
		else
		{
			$comment = $data['comment'];
		}

		if ($data['password'] == FALSE || $data['password'] == '')
		{
			$password = '';
		}
		else
		{
			$password = $data['password'];
			$this->input->set_cookie('foolfuuka_reply_password', $password, 60 * 60 * 24 * 30);
		}

		if(isset($data['ghost']) && $data['ghost'] === TRUE)
		{
			$ghost = TRUE;
		}
		else
		{
			$ghost = FALSE;
		}

		if ($data['spoiler'] == FALSE || $data['spoiler'] == '')
		{
			$spoiler = 0;
		}
		else
		{
			$spoiler = $data['spoiler'];
		}

		if ($data['media'] == FALSE || $data['media'] == '')
		{
			if ($spoiler == 1)
			{
				$spoiler = 0;
			}

			if ($data['num'] == 0)
			{
				return array('error' => 'An image is required for creating threads.');
			}

			if(isset($data['media_error']))
			{
				if (strlen($data['media_error']) == 64)
				{
					return array('error' => 'The filetype you are attempting to upload is not allowed.');
				}

				if (strlen($data['media_error']) == 79)
				{
					return array('error' => 'The image you are attempting to upload is larger than the permitted size.');
				}
			}
		}
		else
		{
			$media = $data['media'];

			if ($allow_media == FALSE)
			{
				if (!unlink($media["full_path"]))
				{
					log_message('error', 'process_media: failed to remove media file from cache');
				}
				return array('error' => 'Sorry, this thread has reached its maximum amount of image replies.');
			}

			if ($media["image_width"] == 0 || $media["image_height"] == 0)
			{
				if (!unlink($media["full_path"]))
				{
					log_message('error', 'process_media: failed to remove media file from cache');
				}
				return array('error' => 'Your image upload is not a valid image file.');
			}

			$media_hash = base64_encode(pack("H*", md5(file_get_contents($media["full_path"]))));

			$check = $this->db->get_where('banned_md5', array('md5' => $media_hash));

			if ($check->num_rows() > 0)
			{
				if (!unlink($media["full_path"]))
				{
					log_message('error', 'process_media: failed to remove media file from cache');
				}
				return array('error' => 'Your image upload has been flagged as inappropriate.');
			}
		}

		if (check_commentdata($data))
		{
			return array('error' => 'Your post contains contents that is marked as spam.');
		}

		if (mb_strlen($comment) > 4096)
		{
			return array('error' => 'Your post was too long.');
		}

		$lines = explode("\n", $comment);

		if (count($lines) > 20)
		{
			return array('error' => 'Your post had too many lines.');
		}

		// phpass password for extra security, using the same tank_auth setting since it's cool
		$hasher = new PasswordHash(
						$this->config->item('phpass_hash_strength', 'tank_auth'),
						$this->config->item('phpass_hash_portable', 'tank_auth'));
		$password = $hasher->HashPassword($password);

		$num = $data['num'];
		$postas = $data['postas'];

		if ($this->session->userdata('poster_id') && $this->session->userdata('poster_id') != 0)
		{
			$query = $this->db->get_where('posters', array('id' => $this->session->userdata('poster_id')));
		}
		else
		{
			$query = $this->db->get_where('posters', array('ip' => $this->input->ip_address()));
		}


		// if any data that could stop the query is returned, no need to add a row
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				if ($row->banned == 1 && !$this->tank_auth->is_allowed())
				{
					return array('error' => 'You are banned from posting.');
				}

				if (time() - strtotime($row->lastpost) < 10 && time() - strtotime($row->lastpost) > 0 && !$this->tank_auth->is_allowed()) // 10 seconds
				{
					return array('error' => 'You must wait at least 10 seconds before posting again.');
				}

				$this->db->where('id', $row->id);
				$this->db->update('posters', array('lastcomment' => $comment, 'lastpost' => date('Y-m-d H:i:s')));
				$this->session->set_userdata('poster_id', $row->id);
			}
		}
		else
		{
			$insert_poster = array(
				'ip' => $this->input->ip_address(),
				'user_agent' => $this->input->user_agent(),
				'lastcomment' => $comment
			);
			$this->db->insert('posters', $insert_poster);
			$poster_id = $this->db->insert_id();
			$this->session->set_userdata('poster_id', $poster_id);
		}

		if (!$ghost)
		{
			// NORMAL REPLY

			$this->db->query('
				INSERT INTO ' . $this->get_table($board) . '
				(num, subnum, parent, timestamp, capcode, email, name, trip, title, comment, delpass, spoiler, poster_id)
				VALUES
				(
					(select coalesce(max(num),0)+1 from (select * from ' . $this->get_table($board) . ') as x),
					?,?,?,?,?,?,?,?,?,?,?,?
				);
				', array(0, $num, time(), $postas, $email, $name, $trip, $subject, $comment, $password, $spoiler, $this->session->userdata('poster_id'))
			);
		}
		else
		{
			// GHOST REPLY

			// get the post after which we're replying to
			// partly copied from Fuuka original
			$this->db->query('
					INSERT INTO ' . $this->get_table($board) . '
					(num, subnum, parent, timestamp, capcode, email, name, trip, title, comment, delpass, poster_id)
					VALUES
					(
						(select max(num) from (select * from ' . $this->get_table($board) . ' where parent=? or num=?) as x),
						(select max(subnum)+1 from (select * from ' . $this->get_table($board) . ' where num=(select max(num) from ' . $this->get_table($board) . ' where parent=? or num=?)) as x),
						?,?,?,?,?,?,?,?,?,?
					);
				', array(
				$num, $num,
				$num, $num,
				$num, time(), $postas, $email, $name, $trip, $subject, $comment, $password, $this->session->userdata('poster_id'))
			);
		}

		// I need num and subnum for a proper redirect
		$posted = $this->db->query('
			SELECT * FROM ' . $this->get_table($board) . '
			WHERE doc_id = ?
			LIMIT 0,1;
		', array($this->db->insert_id()));

		$posted = $posted->result();
		$posted = $posted[0];

		if (isset($media))
		{
			$image = $this->process_media($board, $posted, $media, $media_hash);
			if ($image)
			{
				$this->db->query('
					UPDATE ' . $this->get_table($board) . '
					SET preview=?, preview_w=?, preview_h=?, media=?, media_w=?, media_h=?, media_size=?, media_hash=?, media_filename=?
					WHERE doc_id=?
					', $image
				);
			}
		}

		return array('success' => TRUE, 'posted' => $posted);
	}


	function delete($board, $data)
	{
		// $data => { board, [post (doc_id), password, type (post/image)] }
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->get_table($board) . '
			WHERE doc_id = ?
			LIMIT 0,1;
		', $data['post']);

		if ($query->num_rows() != 1)
		{
			log_message('debug', 'post.php delete() post or thread not found');
			return array('error' => _('There\'s no such a post to be deleted.'));
		}

		$row = $query->row();

		$hasher = new PasswordHash(
						$this->config->item('phpass_hash_strength', 'tank_auth'),
						$this->config->item('phpass_hash_portable', 'tank_auth'));

		if ($hasher->CheckPassword($data['password'], $row->delpass) !== TRUE && !$this->tank_auth->is_allowed())
		{
			log_message('debug', 'post.php delete() inserted wrong password');
			return array('error' => _('The password you inserted did not match the post\'s deletion password.'));
		}

		if (isset($data['type']) && $data['type'] == 'image')
		{
			if (!$this->delete_image($board, $row))
			{
				log_message('error', 'post.php delete() couldn\'t delete thumbnail from post');
				return array('error' => _('Couldn\'t delete the thumbnail image.'));
			}

			// If reports exist, remove
			$this->db->delete('reports', array('board' => $board->id, 'post' => $row->doc_id));
			return array('success' => TRUE);
		}

		// safe to say the user is allowed to remove it
		if ($row->parent == 0) // deleting thread
		{
			// we risk getting into a racing condition
			// get rid first of all of OP so posting is stopped
			// first the file
			if (!$board->archive && $this->total_same_media($board, $row->media_hash) > 1)
			{
				// do nothing, this is required to not affect archived boards
			}
			else
			{
				if (!$this->delete_image($board, $row))
				{
					log_message('error', 'post.php delete() couldn\'t delete thumbnail from thread OP');
					return array('error' => _('Couldn\'t delete the thumbnail.'));
				}
			}

			$this->db->query('
				DELETE
				FROM ' . $this->get_table($board) . '
				WHERE doc_id = ?
			', array($row->doc_id));

			if ($this->db->affected_rows() != 1)
			{
				log_message('error', 'post.php delete() couldn\'t delete thread OP');
				return array('error' => _('Couldn\'t delete thread\'s opening post.'));
			}

			// If reports exist, remove
			$this->db->delete('reports', array('board' => $board->id, 'post' => $row->doc_id));

			// nobody will post in here anymore, so we can take it easy
			// get all child posts
			$thread = $this->db->query('
				SELECT *
				FROM ' . $this->get_table($board) . '
				WHERE parent = ?
			', array($row->num));

			if ($thread->num_rows() > 0) // if there's comments at all
			{
				foreach ($thread->result() as $t)
				{
					if ($this->delete_image($board, $t) !== TRUE)
					{
						log_message('error', 'post.php delete() couldn\'t delete image and thumbnail from thread comments');
						return array('error' => _('Couldn\'t delete the thumbnail(s).'));
					}

					// If reports exist, remove
					$this->db->delete('reports', array('board' => $board->id, 'post' => $t->doc_id));
				}

				$this->db->query('
					DELETE
					FROM ' . $this->get_table($board) . '
					WHERE parent = ?
				', array($row->num));
			}
			return array('success' => TRUE);
		}
		else
		{
			if (!$board->archive && $this->total_same_media($board, $row->media_hash) > 1)
			{
				// do nothing, this is required to not affect archived boards
			}
			else
			{
				if ($this->delete_image($board, $row) !== TRUE)
				{
					log_message('error', 'post.php delete() couldn\'t delete thumbnail from comment');
					return array('error' => _('Couldn\'t delete the thumbnail.'));
				}
			}

			$this->db->query('
				DELETE
				FROM ' . $this->get_table($board) . '
				WHERE doc_id = ?
			', array($row->doc_id));

			if ($this->db->affected_rows() != 1)
			{
				log_message('error', 'post.php delete() couldn\'t delete comment');
				return array('error' => _('Couldn\'t delete post.'));
			}

			// If reports exist, remove
			$this->db->delete('reports', array('board' => $board->id, 'post' => $row->doc_id));
			return array('success' => TRUE);
		}

		return FALSE;
	}


	function spam($board, $doc_id)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->get_table($board) . '
			WHERE doc_id = ?
			LIMIT 0,1;
		', array($doc_id));

		if ($query->num_rows() == 0)
		{
			log_message('error', 'spam: the specified post or thread was not found');
			return array('error' => TRUE);
		}

		$row = $query->row();

		$this->db->query('
			UPDATE ' . $this->get_table($board) . '
			SET = ?
			WHERE doc_id = ?
		', array(1, $row->doc_id));

		if ($this->db->affected_rows() != 1)
		{
			log_message('error', 'spam: unable to update record');
			return array('error' => TRUE);
		}

		return array('success' => TRUE);
	}


	function process_name($name)
	{
		$trip = '';
		$secure_trip = '';
		$matches = array();
		if (preg_match("'^(.*?)(#)(.*)$'", $name, $matches))
		{
			$name = trim($matches[1]);
			$matches2 = array();
			preg_match("'^(.*?)(?:#+(.*))?$'", $matches[3], $matches2);

			if (count($matches2) > 1)
			{
				$trip = $this->tripcode($matches2[1]);
				if ($trip != '')
					$trip = '!' . $trip;
			}

			if (count($matches2) > 2)
			{
				$secure_trip = '!!' . $this->secure_tripcode($matches2[2]);
			}
		}
		return array($name, $trip . $secure_trip);
	}


	function tripcode($plain)
	{
		if (trim($plain) == '')
			return '';
		$pw = mb_convert_encoding($plain, 'SJIS', 'UTF-8');
		$pw = str_replace(array('&', '"', "'", '<', '>'), array('&amp;', '&quot;', '&#39;', '&lt;', '&gt;'), $pw);

		$salt = substr($pw . 'H.', 1, 2);
		$salt = preg_replace('/[^.-z]/', '.', $salt);
		$salt = strtr($salt, ':;<=>?@[\]^_`', 'ABCDEFGabcdef');

		$trip = substr(crypt($pw, $salt), -10);
		return $trip;
	}


	function secure_tripcode($plain)
	{
		$secure = 'FW6I5Es311r2JV6EJSnrR2+hw37jIfGI0FB0XU5+9lua9iCCrwgkZDVRZ+1PuClqC+78FiA6hhhX
				U1oq6OyFx/MWYx6tKsYeSA8cAs969NNMQ98SzdLFD7ZifHFreNdrfub3xNQBU21rknftdESFRTUr
				44nqCZ0wyzVVDySGUZkbtyHhnj+cknbZqDu/wjhX/HjSitRbtotpozhF4C9F+MoQCr3LgKg+CiYH
				s3Phd3xk6UC2BG2EU83PignJMOCfxzA02gpVHuwy3sx7hX4yvOYBvo0kCsk7B5DURBaNWH0srWz4
				MpXRcDletGGCeKOz9Hn1WXJu78ZdxC58VDl20UIT9er5QLnWiF1giIGQXQMqBB+Rd48/suEWAOH2
				H9WYimTJWTrK397HMWepK6LJaUB5GdIk56ZAULjgZB29qx8Cl+1K0JWQ0SI5LrdjgyZZUTX8LB/6
				Coix9e6+3c05Pk6Bi1GWsMWcJUf7rL9tpsxROtq0AAQBPQ0rTlstFEziwm3vRaTZvPRboQfREta0
				9VA+tRiWfN3XP+1bbMS9exKacGLMxR/bmO5A57AgQF+bPjhif5M/OOJ6J/76q0JDHA==';

		//$sectrip='!!'.substr(sha1_base64($sectrip.decode_base64($self->{secret})), 0, 11);
		return substr(base64_encode(sha1($plain . (base64_decode($secure)), TRUE)), 0, 11);
	}


	/*
	  |
	  | MISC FUNCTIONS
	  |
	 */
	function process_post($board, $post, $clean = TRUE, $build = FALSE)
	{
		$this->current_row = $post;
		$this->load->helper('text');
		$post->thumbnail_href = $this->get_image_href($board, $post, TRUE);
		$post->image_href = $this->get_image_href($board, $post);
		$post->remote_image_href = $this->get_remote_image_href($board, $post);
		$post->comment_processed = @iconv('UTF-8', 'UTF-8//IGNORE', $this->get_comment_processed($board, $post));
		$post->comment = @iconv('UTF-8', 'UTF-8//IGNORE', $post->comment);

		foreach (array('title', 'name', 'email', 'trip', 'media', 'preview', 'media_filename', 'media_hash') as $element)
		{
			$element_processed = $element . '_processed';
			$post->$element_processed = @iconv('UTF-8', 'UTF-8//IGNORE', fuuka_htmlescape($post->$element));
			$post->$element = @iconv('UTF-8', 'UTF-8//IGNORE', $post->$element);
		}

		if ($clean === TRUE)
		{
			unset($post->delpass);
			if (!$this->tank_auth->is_allowed())
			{
				unset($post->poster_id);
			}
		}

		if ($build)
			$post->formatted = $this->build_board_comment($board, $post);
	}


	function total_same_media($board, $media_hash)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->get_table($board) . '
			WHERE media_hash = ?
		', array($media_hash));

		return $query->num_rows();
	}


	function process_media($board, $post, $media, $media_hash)
	{
		if (!$board->archive)
		{
			$query = $this->db->query('
				SELECT *
				FROM ' . $this->get_table($board) . '
				WHERE media_hash = ?
				ORDER BY doc_id DESC
				LIMIT 0,1;
			', array($media_hash));

			if ($query->num_rows() != 0)
			{
				$file = $query->row();

				if (file_exists($this->get_image_dir($board, $file, FALSE)) !== FALSE)
				{
					if (!unlink($media["full_path"]))
					{
						log_message('error', 'process_media: failed to remove media file from cache');
					}

					return array($file->preview, $file->preview_w, $file->preview_h, $media["file_name"], $file->media_w, $file->media_h, $file->media_size, $file->media_hash, $file->media_filename, $post->doc_id);
				}
			}

			$number = $post->timestamp;
		}
		else
		{
			if ($post->parent > 0)
			{
				$number = $post->parent;
			}
			else
			{
				$number = $post->num;
			}

			while (strlen((string) $number) < 9)
			{
				$number = '0' . $number;
			}
		}

		// generate random filename based on timestamp
		$media_unixtime = time() . rand(1000, 9999);
		$media_filename = $media_unixtime . strtolower($media["file_ext"]);
		$thumb_filename = $media_unixtime . "s" . strtolower($media["file_ext"]);

		// image and thumb paths
		$path = array(
			'image_dir' => (get_setting('fs_fuuka_boards_directory') ? get_setting('fs_fuuka_boards_directory') : FOOLFUUKA_BOARDS_DIRECTORY) . "/" . $board->shortname . "/img/" . substr($number, 0, 4) . "/" . substr($number, 4, 2) . "/",
			'thumb_dir' => (get_setting('fs_fuuka_boards_directory') ? get_setting('fs_fuuka_boards_directory') : FOOLFUUKA_BOARDS_DIRECTORY) . "/" . $board->shortname . "/thumb/" . substr($number, 0, 4) . "/" . substr($number, 4, 2) . "/"
		);

		// generate paths if necessary
		generate_file_path($path['image_dir']);
		generate_file_path($path['thumb_dir']);

		// move media file
		if (!copy($media["full_path"], $path["image_dir"] . $media_filename))
		{
			log_message('error', 'process_media: failed to create/copy media file');
			return FALSE;
		}

		if (!unlink($media["full_path"]))
		{
			log_message('error', 'process_media: failed to remove media file from cache');
		}

		if ($media["image_width"] > 250 || $media["image_height"] > 250)
		{
			// generate thumbnail
			$CI = & get_instance();
			$CI->load->library('image_lib');
			$img_config['image_library'] = (find_imagick()) ? 'ImageMagick' : 'GD2'; // Use GD2 as fallback
			$img_config['library_path'] = (find_imagick()) ? (get_setting('fs_serv_imagick_path') ? get_setting('fs_serv_imagick_path') : '/usr/bin') : ''; // If GD2, use none
			$img_config['source_image'] = $path["image_dir"] . $media_filename;
			$img_config["new_image"] = $path["thumb_dir"] . $thumb_filename;
			$img_config['width'] = ($media["image_width"] > 250) ? 250 : $media["image_width"];
			$img_config['height'] = ($media["image_height"] > 250) ? 250 : $media["image_height"];
			$img_config['maintain_ratio'] = TRUE;
			$img_config['master_dim'] = 'auto';
			$CI->image_lib->initialize($img_config);
			if (!$CI->image_lib->resize())
			{
				log_message('error', 'process_media: failed to create thumbnail');
				return FALSE;
			}
			$CI->image_lib->clear();
			$thumb_dimensions = @getimagesize($path["thumb_dir"] . $thumb_filename);
		}
		else
		{
			$thumb_filename = $media_filename;
			$thumb_dimensions = array($media["image_width"], $media["image_height"]);
		}

		return array($thumb_filename, $thumb_dimensions[0], $thumb_dimensions[1], $media["file_name"], $media["image_width"], $media["image_height"], ($media["file_size"] * 1024), $media_hash, $media_filename, $post->doc_id);
	}


	function build_board_comment($board, $p)
	{
		// load the functions from the current theme, else load the default one
		if (file_exists('content/themes/' . $this->fu_theme . '/theme_functions.php'))
			require_once('content/themes/' . $this->fu_theme . '/theme_functions.php');
		else
			require_once('content/themes/' . $this->config->item('theme_extends') . '/theme_functions.php');

		//require_once_
		ob_start();

		if (file_exists('content/themes/' . $this->fu_theme . '/views/board_comment.php'))
			include('content/themes/' . $this->fu_theme . '/views/board_comment.php');
		else
			include('content/themes/' . $this->config->item('theme_extends') . '/views/board_comment.php');

		$string = ob_get_contents();
		ob_end_clean();
		return $string;
	}


	function get_image_href($board, $row, $thumbnail = FALSE)
	{
		if (!$row->preview)
			return FALSE;

		/**
		 * Checking for Banned MD5 Hashes
		 */
		$query = $this->db->get_where('banned_md5', array('md5' => $row->media_hash));

		if ($query->num_rows() > 0)
		{
			if ($thumbnail)
			{
				$row->preview_h = 150;
				$row->preview_w = 150;
				return site_url() . 'content/themes/default/images/banned-image.png';
			}

			return '';
		}
		/**
		 * End Check
		 */

		if (!$board->thumbnails && !$this->tank_auth->is_allowed())
		{
			if ($thumbnail)
			{
				return site_url() . 'content/themes/default/images/null-image.png';
			}

			return '';
		}

		if ($board->delay_thumbnails && !$this->tank_auth->is_allowed())
		{
			if (isset($row->timestamp) && ($row->timestamp + 86400 > time()))
			{
				if ($thumbnail)
				{
					return site_url() . 'content/themes/default/images/null-image.png';
				}

				return '';
			}
		}

		if (!$board->archive)
		{
			$number = (($thumbnail) ? $row->preview : $row->media_filename);

			if (strpos($number, 's.') === FALSE)
			{
				$thumbnail = FALSE;
			}
		}
		else
		{
			if ($row->parent > 0)
				$number = $row->parent;
			else
				$number = $row->num;

			preg_match('/(\d+?)(\d{2})\d{0,3}$/', $number, $matches);

			if(!isset($matches[1]))
				$matches[1] = '';

			if(!isset($matches[2]))
				$matches[2] = '';

			$number = str_pad($matches[1], 4, "0", STR_PAD_LEFT) . str_pad($matches[2], 2, "0", STR_PAD_LEFT);
		}

		if (file_exists($this->get_image_dir($board, $row, $thumbnail)) !== FALSE)
		{
			if (strlen(get_setting('fs_balancer_clients')) > 10)
			{
				$matches = array();
				preg_match('/([\d]+)/', $row->preview, $matches);
				if (isset($matches[1]))
				{
					$balancer_servers = unserialize(get_setting('fs_balancer_clients'));
					$server_num = (intval($matches[1])) % (count($balancer_servers));
					return $balancer_servers[$server_num]['url'] . '/' . $board->shortname . '/' . (($thumbnail) ? 'thumb' : 'img') . '/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . (($thumbnail) ? $row->preview : $row->media_filename);
				}
			}
			return (get_setting('fs_fuuka_boards_url') ? get_setting('fs_fuuka_boards_url') : site_url() . FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $board->shortname . '/' . (($thumbnail) ? 'thumb' : 'img') . '/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . (($thumbnail) ? $row->preview : $row->media_filename);
		}
		if ($thumbnail)
		{
			$row->preview_h = 150;
			$row->preview_w = 150;
			return site_url() . 'content/themes/default/images/image_missing.jpg';
		}
		else
		{
			if (!$board->archive && strpos($row->preview, 's.') === FALSE)
			{
				$row->preview_h = 150;
				$row->preview_w = 150;
				return site_url() . 'content/themes/default/images/image_missing.jpg';
			}

			return '';
		}
	}


	function get_image_dir($board, $row, $thumbnail = FALSE)
	{
		if (!$row->preview)
			return FALSE;

		if (!$board->archive)
		{
			$number = (($thumbnail) ? $row->preview : $row->media_filename);

			if (strpos($number, 's.') === FALSE)
			{
				$thumbnail = FALSE;
			}
		}
		else
		{
			if ($row->parent > 0)
				$number = $row->parent;
			else
				$number = $row->num;

			preg_match('/(\d+?)(\d{2})\d{0,3}$/', $number, $matches);

			if(!isset($matches[1]))
				$matches[1] = '';

			if(!isset($matches[2]))
				$matches[2] = '';

			$number = str_pad($matches[1], 4, "0", STR_PAD_LEFT) . str_pad($matches[2], 2, "0", STR_PAD_LEFT);
		}

		return ((get_setting('fs_fuuka_boards_directory') ? get_setting('fs_fuuka_boards_directory') : FOOLFUUKA_BOARDS_DIRECTORY)) . '/' . $board->shortname . '/' . (($thumbnail === TRUE) ? 'thumb' : 'img') . '/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . (($thumbnail === TRUE) ? $row->preview : $row->media_filename);
	}


	function delete_image($board, $row, $image = TRUE, $thumbnail = TRUE)
	{
		// don't try deleting what isn't there anyway
		if (!$row->preview)
			return TRUE;

		if ($image)
			if (file_exists($this->get_image_dir($board, $row)))
			{
				if (!@unlink($this->get_image_dir($board, $row)))
				{
					log_message('error', 'post.php delete_image(): couldn\'t remove image: ' . $this->get_image_dir($board, $row));
					return FALSE;
				}
			}

		if ($thumbnail)
			if (file_exists($this->get_image_dir($board, $row, TRUE)))
			{
				if (!@unlink($this->get_image_dir($board, $row, TRUE)))
				{
					log_message('error', 'post.php delete_thumbnail(): couldn\'t remove thumbnail: ' . $this->get_image_dir($board, $row, TRUE));
					return FALSE;
				}
			}
		return TRUE;
	}


	function ban_image_hash($md5)
	{
		// sql insert
		$this->db->insert('banned_md5', array('md5' => $md5));

		// generate mass delete
		$query = array();
		foreach($this->radix->get_all() as $board)
		{
			$query[] = '
				(
					SELECT *, CONCAT(' . $this->db->escape($board->id) . ') as board_id
					FROM ' . $this->get_table($board) . '
					WHERE media_hash = ' . $this->db->escape($md5) . '
				)
			';
		}

		$query = implode(' UNION ', $query);
		$query = $this->db->query($query);

		if ($query->num_rows() == 0)
		{
			log_message('error', 'ban_image_hash: there are no posts that contain this hash');
			return array('error' => TRUE);
		}

		foreach ($query->result() as $image)
		{
			$this->delete_image(
				$this->radix->get_by_id($image->board_id), $image
			);
		}

		return array('');
	}


	function get_remote_image_href($board, $row)
	{
		if (!$row->media)
			return '';

		if ($board->archive)
		{
			// ignore webkit and opera and allow rel="noreferrer" do its work
			if (isset($_SERVER['HTTP_USER_AGENT']))
			{
				if (preg_match('/(opera|webkit)/i', $_SERVER['HTTP_USER_AGENT']))
				{
					return $board->images_url . $row->media_filename;
				}
			}

			return site_url($board->shortname . '/redirect/' . $row->media_filename);
		}
		else
		{
			$number = $row->media_filename;
			return (get_setting('fs_fuuka_boards_url') ? get_setting('fs_fuuka_boards_url') : site_url() . FOOLFUUKA_BOARDS_DIRECTORY) . '/' . $board->shortname . '/' . 'img' . '/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . $row->media_filename;
		}
	}


	function get_comment_processed($board, $row)
	{
		$CI = & get_instance();

		$find = array(
			"'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i"
		);

		$replace = array(
			'\\1<span class="greentext">\\2</span>\\3'
		);

		if ($this->features == FALSE)
		{
			if ($this->fu_theme == 'fuuka')
			{
				$find = array(
					"'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i"
				);

				$replace = array(
					'\\1<span class="greentext">\\2</span>\\3'
				);
			}

			if ($this->fu_theme == 'yotsuba')
			{
				$find = array(
					"'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i"
				);

				$replace = array(
					'\\1<font class="unkfunc">\\2</font>\\3'
				);
			}
		}
		$regexing = $row->comment;

		// get rid of moot's formatting
		if ($row->capcode == 'A' && mb_strpos($regexing, '<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">') === 0)
		{
			$regexing = str_replace('<div style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">', '', $regexing);

			if (mb_substr($regexing, -6, 6) == '</div>')
			{
				$regexing = mb_substr($regexing, 0, mb_strlen($regexing) - 6);
			}
		}

		// get rid of another of moot's cancerous formatting
		if ($row->capcode == 'A' && mb_strpos($regexing, '<span style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">') === 0)
		{
			$regexing = str_replace('<span style="padding: 5px;margin-left: .5em;border-color: #faa;border: 2px dashed rgba(255,0,0,.1);border-radius: 2px">', '', $regexing);

			if (mb_substr($regexing, -10, 10) == '[/spoiler]')
			{
				$regexing = mb_substr($regexing, 0, mb_strlen($regexing) - 10);
			}
		}

		$regexing = htmlentities($regexing, ENT_COMPAT | ENT_IGNORE, 'UTF-8', FALSE);

		// for preg_replace_callback
		$this->current_board_for_prc = $board;
		$regexing = preg_replace_callback("'(&gt;&gt;(\d+(?:,\d+)?))'i", array(get_class($this), 'get_internal_link'), $regexing);
		$regexing = preg_replace_callback("'(&gt;&gt;&gt;(\/(\w+)\/(\d+(?:,\d+)?)?(\/?)))'i", array(get_class($this), 'get_crossboard_link'), $regexing);

		$regexing = auto_linkify($regexing, 'url', TRUE);

		$regexing = preg_replace($find, $replace, $regexing);
		$regexing = parse_bbcode($regexing, (($board->archive && $row->subnum == 0) ? TRUE : FALSE));

		if ($board->archive && $row->subnum == 0)
		{
			$adminfind = array(
				"'\[banned\](.*?)\[/banned\]'i"
			);

			$adminreplace = array(
				'<span class="banned">\\1</span>'
			);

			$regexing = preg_replace($adminfind, $adminreplace, $regexing);

			$litfind = array(
				"'\[banned:lit\]'i",
				"'\[/banned:lit\]'i",
				"'\[moot:lit\]'i",
				"'\[/moot:lit\]'i"
			);

			$litreplace = array(
				'[banned]',
				'[/banned]',
				'[moot]',
				'[/moot]'
			);

			$regexing = preg_replace($litfind, $litreplace, $regexing);
		}

		$regexing = nl2br(trim($regexing));

		return $regexing;
	}


	function get_internal_link($matches)
	{
		$num = $matches[2];

		$_prefix = '';
		$_urltag = '#';
		$_option = ' class="backlink" data-function="highlight" data-backlink="true" data-post="' . str_replace(',', '_', $num) . '"';
		$_option_op = ' class="backlink op" data-function="highlight" data-backlink="true" data-post="' . str_replace(',', '_', $num) . '"';
		$_backlink_option = ' class="backlink" data-function="highlight" data-backlink="true" data-post="' . $this->current_row->num . (($this->current_row->subnum == 0) ? '' : '_' . $this->current_row->subnum) . '"';
		$_suffix = '';
		if ($this->features == FALSE)
		{
			if ($this->fu_theme == 'fuuka')
			{
				$_prefix = '<span class="unkfunc">';
				$_urltag = '#';
				$_option = ' onclick="replyhighlight(\'p' . str_replace(',', '_', $num) . '\')"';
				$_suffix = '</span>';
			}

			if ($this->fu_theme == 'yotsuba')
			{
				$_prefix = '<font class="unkfunc">';
				$_urltag = '#';
				$_option = ' class="quotelink" onclick="replyhl(\'' . str_replace(',', '_', $num) . '\');"';
				$_suffix = '</font>';
			}
		}

		$this->backlinks[str_replace(',', '_', $num)][] = $_prefix
				. '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread', ($this->current_row->parent == 0) ? $this->current_row->num : $this->current_row->parent))
				. $_urltag . $this->current_row->num . (($this->current_row->subnum == 0) ? '' : '_' . $this->current_row->subnum)
				. '"' . $_backlink_option . '>&gt;&gt;' . $this->current_row->num . (($this->current_row->subnum == 0) ? '' : ',' . $this->current_row->subnum) . '</a>' . $_suffix;

		if (array_key_exists($num, $this->existing_posts))
		{
			if ($this->backlinks_hash_only_url)
			{
				return $_prefix . '<a href="' . $_urltag . str_replace(',', '_', $num) . '"' . $_option_op . '>&gt;&gt;' . $num . '</a>' . $_suffix;
			}
			return $_prefix . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread', $num)) . $_urltag . str_replace(',', '_', $num) . '"' . $_option_op . '>&gt;&gt;' . $num . '</a>' . $_suffix;
		}

		foreach ($this->existing_posts as $key => $thread)
		{
			if (in_array($num, $thread))
			{
				if ($this->backlinks_hash_only_url)
				{
					return $_prefix . '<a href="' . $_urltag . str_replace(',', '_', $num) . '"' . $_option . '>&gt;&gt;' . $num . '</a>' . $_suffix;
				}
				return $_prefix . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread', $key)) . $_urltag . str_replace(',', '_', $num) . '"' . $_option . '>&gt;&gt;' . $num . '</a>' . $_suffix;
			}
		}

		if ($this->realtime === TRUE)
		{
			return $_prefix . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'thread', $key)) . $_urltag . str_replace(',', '_', $num) . '"' . $_option . '>&gt;&gt;' . $num . '</a>' . $_suffix;
		}

		// nothing yet? make a generic link with post
		return $_prefix . '<a href="' . site_url(array($this->current_board_for_prc->shortname, 'post', str_replace(',', '_', $num))) . '">&gt;&gt;' . $num . '</a>' . $_suffix;

		// return the thing untouched
		return $matches[0];
	}


	function get_crossboard_link($matches)
	{
		$link = $matches[2];
		$shortname = $matches[3];
		$num = $matches[4];

		$_prefix = '';
		$_suffix = '';
		if ($this->features == FALSE)
		{
			if ($this->fu_theme == 'fuuka')
			{
				$_prefix = '<span class="unkfunc">';
				$_suffix = '</span>';
			}

			if ($this->fu_theme == 'yotsuba')
			{
				$_prefix = '<font class="unkfunc">';
				$_suffix = '</font>';
			}
		}

		$board = new Board();
		$board->where('shortname', $shortname)->get();
		if ($board->result_count() == 0)
		{
			if ($num)
			{
				return $_prefix . '<a href="http://boards.4chan.org/' . $shortname . '/res/' . $num . '">&gt;&gt;&gt;' . $link . '</a>' . $_suffix;
			}

			return $_prefix . '<a href="http://boards.4chan.org/' . $shortname . '/">&gt;&gt;&gt;' . $link . '</a>' . $_suffix;
		}

		if ($num)
		{
			return $_prefix . '<a href="' . site_url(array($board->shortname, 'post', $num)) . '">&gt;&gt;&gt;' . $link . '</a>' . $_suffix;
		}

		return $_prefix . '<a href="' . site_url($board->shortname) . '">&gt;&gt;&gt;' . $link . '</a>' . $_suffix;

		return $matches[0];
	}


}
