<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Post extends CI_Model
{

	var $table = '';
	var $table_local = '';
	var $sql_report = '';
	var $sql_report_after_join = '';
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
		$this->get_table();

		// make so it's shown where are the
		if ($this->tank_auth->is_allowed())
		{
			$this->sql_report = '
					LEFT JOIN
					(
						SELECT post as report_post, reason as report_reason, status as report_status
						FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
						WHERE `board` = ' . get_selected_board()->id . '
					) as q
					ON
					' . $this->table . '.`doc_id`
					=
					' . $this->db->protect_identifiers('q') . '.`report_post`
				';

			$this->sql_report_after_join = '
					LEFT JOIN
					(
						SELECT post as report_post, reason as report_reason, status as report_status
						FROM ' . $this->db->protect_identifiers('reports', TRUE) . '
						WHERE `board` = ' . get_selected_board()->id . '
					) as q
					ON
					g.`doc_id`
					=
					' . $this->db->protect_identifiers('q') . '.`report_post`
				';
		}

		if ($id != FALSE)
		{
			// @todo make the existence of this block useless or something
			// load the functions from the current theme, else load the default one
			if (file_exists('content/themes/' . $this->fu_theme . '/theme_functions.php'))
			{
				require_once('content/themes/' . $this->fu_theme . '/theme_functions.php');
			}
			else
			{
				require_once('content/themes/' . $this->config->item('theme_extends') . '/theme_functions.php');
			}
		}
	}


	function get_table()
	{
		if (get_setting('fs_fuuka_boards_db'))
		{
			$this->table = $this->db->protect_identifiers(get_setting('fs_fuuka_boards_db')) . '.' . $this->db->protect_identifiers(get_selected_board()->shortname);
			$this->table_local = $this->db->protect_identifiers(get_setting('fs_fuuka_boards_db')) . '.' . $this->db->protect_identifiers(get_selected_board()->shortname . '_local');
			return;
		}
		$this->table = $this->db->protect_identifiers('board_' . get_selected_board()->shortname, TRUE);
		$this->table_local = $this->db->protect_identifiers('board_' . get_selected_board()->shortname . '_local', TRUE);
	}


	function gallery()
	{
		$query = $this->db->query('
			SELECT DISTINCT *
			FROM ' . $this->table . '
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
					FROM ' . $this->table . '
					WHERE parent = ' . $row->num . '
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
			$this->process_post($post, TRUE, TRUE);
			$result_num_as_key[$post->num] = $post;
		}

		return $result_num_as_key;
	}


	/**
	 *
	 * @param type $page
	 * @param type $process
	 * @return type
	 */
	function get_latest($page = 1, $per_page = 20, $process = TRUE, $clean = TRUE, $ghost = FALSE, $thread_order = FALSE)
	{

		// get exactly 20 be it thread starters or parents with distinct parent
		if ($ghost)
		{
			// only with ghost replies
			$query = $this->db->query('
				SELECT *
				FROM
				(
					SELECT DISTINCT(parent) as unq_parent
					FROM ' . $this->table . '
					WHERE
					' . $this->table . '.email != \'sage\'
					AND subnum > 0
					ORDER BY timestamp DESC
					LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
				) AS t
				LEFT JOIN ' . $this->table . ' AS g
					ON g.num = t.unq_parent AND g.subnum = 0
				' . $this->sql_report_after_join . '
			');
		}
		else if ($thread_order)
		{
			// by thread creation
			$query = $this->db->query('
				SELECT *
				FROM
				(
					SELECT DISTINCT(num) as unq_parent
					FROM ' . $this->table . '
					WHERE parent = 0 AND subnum = 0
					ORDER BY num DESC
					LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
				) AS t
				LEFT JOIN ' . $this->table . ' AS g
					ON g.num = t.unq_parent AND g.subnum = 0
				' . $this->sql_report_after_join . '
			');
		}
		else
		{
			// normal
			$query = $this->db->query('
				SELECT *
				FROM
				(
					SELECT DISTINCT( IF(parent = 0, num, parent)) as unq_parent, email
					FROM ' . $this->table . '
					WHERE email != \'sage\' OR (email	= \'sage\' AND parent = 0)
					ORDER BY num DESC
					LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
				) AS t
				LEFT JOIN ' . $this->table . ' AS g
					ON g.num = t.unq_parent AND g.subnum = 0
				' . $this->sql_report_after_join . '
			');
		}


		if ($query->num_rows() == 0)
		{
			return array('posts' => array(), 'op' => array());
		}

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
					FROM ' . $this->table . '
					' . $this->sql_report . '
					WHERE parent = ' . $row->unq_parent . '
					ORDER BY num DESC, subnum DESC
					LIMIT 0, 5
				)
			';

			$sqlcount[] = '
				(
					SELECT count(*) AS count_all, count(distinct preview) AS count_images, num, parent
					FROM ' . $this->table . '
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
			//echo '<pre>'; print_r($post); echo '</pre>';
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
				$this->process_post($post, $clean);
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
		return ($result2);
	}


	function get_posts_ghost($page = 1, $per_page = 1000, $process = TRUE, $clean = TRUE)
	{
		// get exactly 20 be it thread starters or parents with different parent
		$query = $this->db->query('
			SELECT num, subnum, timestamp
			FROM ' . $this->table . '
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
					FROM ' . $this->table . '
					WHERE num = ' . $row->num . ' AND subnum = ' . $row->subnum . '
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
				$this->process_post($post, $clean);
			}
			// the first you create from a parent is the first thread
			$result['posts'][] = $post;
		}

		return $result;
	}


	function get_thread($num, $process = TRUE, $clean = TRUE, $realtime = FALSE)
	{
		// settings
		$this->backlinks_hash_only_url = TRUE;
		
		if (is_array($num))
		{
			if (isset($num['latest_doc_id']))
			{
				$query = $this->db->query('
					SELECT * FROM ' . $this->table . '
					WHERE parent = ? AND doc_id > ?
					ORDER BY num, subnum ASC;
				', array($num['num'], $num['latest_doc_id']));
			}
			$num = $num['num'];
		}
		else
		{
			$query = $this->db->query('
				SELECT * FROM ' . $this->table . '
				' . $this->sql_report . '
				WHERE num = ? OR parent = ?
				ORDER BY num, subnum ASC;
			', array($num, $num));
		}
		$result = array();

		// thread not found
		if ($query->num_rows() == 0)
			return FALSE;

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
				$this->process_post($post, $clean, $realtime);
			}
			else if ($process === TRUE && $post->parent == 0)
			{
				$this->process_post($post, TRUE, TRUE);
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

		//print_r($result);
		return $result;
	}


	function get_last50($num, $process = TRUE, $clean = TRUE)
	{
		// settings
		$this->backlinks_hash_only_url = TRUE;

		$query = $this->db->query('
			SELECT * FROM
			(
				SELECT * FROM ' . $this->table . '
				' . $this->sql_report . '
				WHERE num = ? OR parent = ?
				ORDER BY num DESC, subnum DESC
				LIMIT 50
			)
			AS x
			ORDER BY num, subnum ASC;
		', array($num, $num));
		$result = array();

		// thread not found
		if ($query->num_rows() == 0)
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
				$this->process_post($post, $clean);
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

		//print_r($result);
		return $result;
	}


	function get_post_thread($num, $subnum = 0)
	{
		$query = $this->db->query('
				SELECT num, parent, subnum FROM ' . $this->table . '
				' . $this->sql_report . '
				WHERE num = ? AND subnum = ?
				LIMIT 0, 1;
			', array($num, $subnum));

		foreach ($query->result() as $post)
		{
			return $post;
		}

		return FALSE;
	}


	function get_search($search, $process = TRUE, $clean = TRUE)
	{
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

		if (get_selected_board()->sphinx)
		{

			if ($search['username'] || $search['tripcode'] || $search['text'] || $search['deleted'] || $search['ghost'])
			{
				$this->load->library('SphinxClient');
				$this->sphinxclient->SetServer(
						// gotta turn the port into int
						get_setting('fs_sphinx_hostname') ? get_setting('fs_sphinx_hostname') : '127.0.0.1', get_setting('fs_sphinx_hostname') ? get_setting('fs_sphinx_port') : 9312
				);

				$this->sphinxclient->SetLimits(($search['page'] * 25) - 25, 25, 5000);

				$query = '';
				if ($search['username'])
				{
					$query .= '@name ' . $this->sphinxclient->EscapeString(urldecode($search['username'])) . ' ';
				}

				if ($search['tripcode'])
				{
					$query .= '@trip ' . $this->sphinxclient->EscapeString(urldecode($search['tripcode'])) . ' ';
				}

				if ($search['text'])
				{
					if (mb_strlen($search['text']) < 2)
					{
						return array('error' => _('The text you were searching for was too short. It must be at least two characters long.'));
					}

					$query .= '@comment ' . $this->sphinxclient->HalfEscapeString(urldecode($search['text'])) . ' ';
				}

				if ($search['deleted'] == "deleted")
				{
					$this->sphinxclient->setFilter('is_deleted', array(1));
				}
				if ($search['deleted'] == "not-deleted")
				{
					$this->sphinxclient->setFilter('is_deleted', array(0));
				}

				if ($search['ghost'] == "only")
				{
					$this->sphinxclient->setFilter('is_internal', array(1));
				}
				if ($search['ghost'] == "none")
				{
					$this->sphinxclient->setFilter('is_internal', array(0));
				}

				$this->sphinxclient->setMatchMode(SPH_MATCH_EXTENDED);
				if ($search['order'] == 'asc')
				{
					$this->sphinxclient->setSortMode(SPH_SORT_ATTR_ASC, 'num');
				}
				else
				{
					$this->sphinxclient->setSortMode(SPH_SORT_ATTR_DESC, 'num');
				}

				$search_result = $this->sphinxclient->query($query, get_selected_board()->shortname . '_ancient ' . get_selected_board()->shortname . '_main ' . get_selected_board()->shortname . '_delta');
				if ($search_result === false)
				{
					if ($this->sphinxclient->IsConnectError())
					{
						return array('error' => _('The search engine seems to be offline. If it\'s offline for more than a few minutes, you might want to report the issue. Most likely it was turned off and not turned back on.'));
					}

					return array('error' => _('The search engine couldn\'t figure out the query. It\'s possible that you\'ve used characters that aren\'t accepted. If you think this is a bug, report it if possible.'));

					// we could use the rest for debugging purposes,
					// but for now we better just give a generic errors because
					// "human readable" here doesn't mean "public-friendly"
					if ($this->sphinxclient->GetLastError())
					{
						return array('error' => $this->sphinxclient->GetLastError());
					}

					if ($this->sphinxclient->GetLastWarning())
					{
						return array('error' => $this->sphinxclient->GetLastWarning());
					}

					return array('error' => _('Something went wrong with the search engine and we don\'t know what!'));
				}

				$sql = array();

				if (empty($search_result['matches']))
				{
					return array('posts' => array(), 'total_found' => 0);
				}
				foreach ($search_result['matches'] as $key => $matches)
				{
					$sql[] = '
						(
							SELECT *
							FROM ' . $this->table . '
							' . $this->sql_report . '
							WHERE num = ' . $matches['attrs']['num'] . ' AND subnum = ' . $matches['attrs']['subnum'] . '
						)
					';
				}

				$sql = implode('UNION', $sql) . '
				ORDER BY num ASC
			';

				$query = $this->db->query($sql);

				$do_reverse = TRUE;
			}
			else // it's damn slow to run empty searches, unless we use MySQL directly
			{
				if ($search['order'] === 'asc')
				{
					$order = 'ORDER BY num ASC';
				}
				else
				{
					$order = 'ORDER BY num DESC';
				}

				$query = $this->db->query('
					SELECT *
					FROM ' . $this->table . '
					' . $order . '
					LIMIT ' . (($search['page'] * 25) - 25) . ', 25
				');

				$query2 = $this->db->query('
					SELECT count(*) AS total_found
					FROM ' . $this->table . '
					LIMIT 0, 5000
				');

				$found = $query2->result();
				$search_result = array('total_found' => $found[0]->total_found);
				$do_reverse = FALSE;
			}
		}
		else
		{
			$field = array();
			$value = array();
			$index = array();

			if ($search['username'])
			{
				$field[] = 'name = ?';
				$value[] = $search['username'];
				$index[] = 'name_index';
			}

			if ($search['tripcode'])
			{
				$field[] = 'trip = ?';
				$value[] = urldecode($search['tripcode']);
				$index[] = 'trip_index';
			}

			if ($search['text'])
			{
				if (mb_strlen($search['text']) < 2)
				{
					return array('error' => _('The text you were searching for was too short. It must be at least two characters long.'));
				}

				//$field[] = 'match(comment) against (?)';
				$field[] = 'comment LIKE ?';
				$value[] = '%' . urldecode($search['text']) . '%';
			}

			if ($search['deleted'] == "deleted")
			{
				$field[] = 'deleted = ?';
				$value[] = 1;
			}
			if ($search['deleted'] == "not-deleted")
			{
				$field[] = 'deleted = ?';
				$value[] = 0;
			}

			if ($search['ghost'] == "only")
			{
				$field[] = 'subnum = ?';
				$value[] = 1;
			}
			if ($search['ghost'] == "none")
			{
				$field[] = 'subnum = ?';
				$value[] = 0;
			}

			if ($search['order'] === 'asc')
			{
				$order = 'ORDER BY num ASC';
			}
			else
			{
				$order = 'ORDER BY num DESC';
			}

			$query = $this->db->query('
				SELECT *
				FROM ' . $this->table . '
				' . ((!empty($index)) ? ' use index(' . implode(', ', $index) . ')' : '') . ((!empty($field)) ? ' WHERE ' . implode(' AND ', $field) : '') . '
				' . $order . '
				LIMIT ' . (($search['page'] * 25) - 25) . ', 25
			', $value);

			$query2 = $this->db->query('
				SELECT count(*) AS total_found
				FROM ' . $this->table . '
				' . ((!empty($index)) ? ' use index(' . implode(', ', $index) . ')' : '') . ((!empty($field)) ? ' WHERE ' . implode(' AND ', $field) : '') . '
				LIMIT 0, 5000
			', $value);

			if ($query->num_rows() == 0)
			{
				return array('posts' => array(), 'total_found' => 0);
			}

			$found = $query2->result();
			$search_result = array('total_found' => $found[0]->total_found);
			$do_reverse = FALSE;
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
				$this->process_post($post, $clean);
			}
			// the first you create from a parent is the first thread
			$result[0]['posts'][] = $post;
		}

		if (is_array($result[0]['posts']) && $do_reverse)
			$result[0]['posts'] = array_reverse($result[0]['posts']);

		return array('posts' => $result, 'total_found' => $search_result['total_found']);
	}


	function get_image($hash, $page, $per_page = 25, $process = TRUE, $clean = TRUE)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->table . '
			' . $this->sql_report . '
			WHERE media_hash = ?
			ORDER BY num DESC
			LIMIT ' . (($page * $per_page) - $per_page) . ', ' . $per_page . '
			', array($hash));

		if ($query->num_rows() == 0)
		{
			return array();
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
				$this->process_post($post, $clean);
			}
			// the first you create from a parent is the first thread
			$result[0]['posts'][] = $post;
		}
		return $result;
	}


	function get_full_image($image)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->table . '
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

		$image_href = $this->get_image_href($result);

		if ($image_href == '')
		{
			$this->process_post($result, TRUE);
			return array('error_type' => 'not_on_server', 'error_code' => 404, 'result' => $result);
		}

		return array('image_href' => $image_href);
	}


	function check_thread($num)
	{
		if (is_array($num))
		{
			if (!isset($num['parent']))
			{
				return array('invalid_thread' => TRUE);
			}

			$num = $num['parent'];
		}

		if ($num == 0)
		{
			return array('invalid_thread' => TRUE);
		}

		$query = $this->db->query('
			SELECT * FROM ' . $this->table . '
			' . $this->sql_report . '
			WHERE num = ? OR parent = ?
			ORDER BY num, subnum ASC;
		', array($num, $num));

		if ($query->num_rows() == 0)
		{
			return array('invalid_thread' => TRUE);
		}

		$count = array('posts' => 0, 'images' => 0);
		foreach ($query->result() as $post)
		{
			if ($post->media_filename)
			{
				$count['images']++;
			}
			$count['posts']++;
		}

		$query->free_result();

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
	function comment($data, $allow_media = TRUE)
	{
		if (check_stopforumspam_ip($this->input->ip_address()))
		{
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
			$this->input->set_cookie('foolfuuka_reply_email', $email, 60 * 60 * 24 * 30);
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

		if ($data['media'] == FALSE || $data['media'] == '')
		{
			if (is_array($data['num']))
			{
				$parent = $data['num']['parent'];
			}
			else
			{
				$parent = $data['num'];
			}

			if ($parent == 0)
			{
				return array('error' => 'An image is required for creating threads.');
			}

			if (strlen($data['media_error']) == 64)
			{
				return array('error' => 'The filetype you are attempting to upload is not allowed.');
			}

			if (strlen($data['media_error']) == 79)
			{
				return array('error' => 'The image you are attempting to upload is larger than the permitted size.');
			}
		}
		else
		{
			if ($allow_media == FALSE)
			{
				return array('error' => 'Sorry, this thread has reached its maximum amount of image replies.');
			}

			$media = $data['media'];
			if ($media["image_width"] == 0 || $media["image_height"] == 0)
			{
				return array('error' => 'Your image upload is not a valid image file.');
			}

			$media_hash = base64_encode(pack("H*", md5(file_get_contents($media["full_path"]))));
			if (check_commentdata(array($media_hash)))
			{
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

		if (is_array($num))
		{
			$this->db->query('
				INSERT INTO ' . $this->table . '
				(num, subnum, parent, timestamp, capcode, email, name, trip, title, comment, delpass, poster_id)
				VALUES
				(
					(select coalesce(max(num),0)+1 from (select * from ' . $this->table . ') as x),
					?,?,?,?,?,?,?,?,?,?,?
				);
				', array(0, $num['parent'], time(), $postas, $email, $name, $trip, $subject, $comment, $password, $this->session->userdata('poster_id'))
			);
		}
		else
		{
			// get the post after which we're replying to
			// partly copied from Fuuka original
			$this->db->query('
					INSERT INTO ' . $this->table . '
					(num, subnum, parent, timestamp, capcode, email, name, trip, title, comment, delpass, poster_id)
					VALUES
					(
						(select max(num) from (select * from ' . $this->table . ' where parent=? or num=?) as x),
						(select max(subnum)+1 from (select * from ' . $this->table . ' where num=(select max(num) from ' . $this->table . ' where parent=? or num=?)) as x),
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
			SELECT * FROM ' . $this->table . '
			WHERE doc_id = ?
			LIMIT 0,1;
		', array($this->db->insert_id()));

		if (!is_array($num))
		{
			// we don't even need this, but let's leave it for sake of backward compatibility with original fuuka
			$this->db->query('
				replace into ' . $this->table_local . ' (num,parent,subnum,`timestamp`)
				select num,case when parent = 0 then num else parent end as parent,max(subnum),max(`timestamp`) from ' . $this->table . '
				where num = (select max(num) from ' . $this->table . ' where parent=?);
			', array($num));
		}

		$posted = $posted->result();
		$posted = $posted[0];

		if ($data['media'] != FALSE || $data['media'] != '')
		{
			if ($image = $this->process_media($posted, $media, $media_hash))
			{
				$this->db->query('
					UPDATE ' . $this->table . '
					SET preview=?, preview_w=?, preview_h=?, media=?, media_w=?, media_h=?, media_size=?, media_hash=?, media_filename=?
					WHERE doc_id=?
					', $image
				);
			}
		}

		return array('success' => TRUE, 'posted' => $posted);
	}


	function delete($data)
	{
		// $data => { board, post (doc_id), password, remove (post/image) }
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->table . '
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

		if ($hasher->CheckPassword($data['password'], $row->delpass) !== TRUE && !$this->tank_auth->is_allowed())// && !$this->tank_auth->is_allowed())
		{
			log_message('debug', 'post.php delete() inserted wrong password');
			return array('error' => _('The password you inserted did not match the post\'s deletion password.'));
		}

		if (isset($data['remove']) && $data['remove'] == 'image')
		{
			if (!$this->delete_image($row))
			{
				log_message('error', 'post.php delete() couldn\'t delete thumbnail from post');
				return array('error' => _('Couldn\'t delete the thumbnail image.'));
			}

			return array('success' => TRUE);
		}

		// safe to say the user is allowed to remove it
		if ($row->parent == 0) // deleting thread
		{
			// we risk getting into a racing condition
			// get rid first of all of OP so posting is stopped
			// first the file
			if (!get_selected_board()->archive && $this->total_same_media($row->media_hash) > 1)
			{
				// do nothing, this is required to not affect archived boards
			}
			else
			{
				if (!$this->delete_image($row))
				{
					log_message('error', 'post.php delete() couldn\'t delete thumbnail from thread OP');
					return array('error' => _('Couldn\'t delete the thumbnail.'));
				}
			}

			$this->db->query('
				DELETE
				FROM ' . $this->table . '
				WHERE doc_id = ?
			', array($row->doc_id));

			if ($this->db->affected_rows() != 1)
			{
				log_message('error', 'post.php delete() couldn\'t delete thread OP');
				return array('error' => _('Couldn\'t delete thread\'s opening post.'));
			}

			// nobody will post in here anymore, so we can take it easy
			// get all child posts
			$thread = $this->db->query('
				SELECT *
				FROM ' . $this->table . '
				WHERE parent = ?
			', array($row->num));

			if ($thread->num_rows() > 0) // if there's comments at all
			{
				foreach ($thread->result() as $t)
				{
					if ($this->delete_image($t) !== TRUE)
					{
						log_message('error', 'post.php delete() couldn\'t delete image and thumbnail from thread comments');
						return array('error' => _('Couldn\'t delete the thumbnail(s).'));
					}
				}

				$this->db->query('
					DELETE
					FROM ' . $this->table . '
					WHERE parent = ?
				', array($row->num));
			}
			return array('success' => TRUE);
		}
		else
		{
			if (!get_selected_board()->archive && $this->total_same_media($row->media_hash) > 1)
			{
				// do nothing, this is required to not affect archived boards
			}
			else
			{
				if ($this->delete_image($row) !== TRUE)
				{
					log_message('error', 'post.php delete() couldn\'t delete thumbnail from comment');
					return array('error' => _('Couldn\'t delete the thumbnail.'));
				}
			}

			$this->db->query('
				DELETE
				FROM ' . $this->table . '
				WHERE doc_id = ?
			', array($row->doc_id));

			if ($this->db->affected_rows() != 1)
			{
				log_message('error', 'post.php delete() couldn\'t delete comment');
				return array('error' => _('Couldn\'t delete post.'));
			}

			return array('success' => TRUE);
		}

		return FALSE;
	}


	/*
	  function spam($doc_id)
	  {
	  $query = $this->db->query('
	  SELECT *
	  FROM ' . $this->table . '
	  WHERE doc_id = ?
	  LIMIT 0,1;
	  ', $doc_id);

	  if ($query->num_rows() != 1)
	  {
	  log_message('debug', 'post.php spam() post or thread not found');
	  return array('error' => _('There\'s no such record to mark as spam.'));
	  }

	  $row = $query->row();

	  $this->db->query('
	  UPDATE ' . $this->table . '
	  SET spam = 1
	  WHERE doc_id = ?
	  ', $row->doc_id);

	  if ($this->db->affected_rows() != 1)
	  {
	  log_message('debug', 'post.php spam() unable to update record.');
	  return array('error' => _('Unable to mark post/thread as spam.'));
	  }

	  return array('success' => TRUE);
	  }
	 */
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
		$salt = preg_replace('/[^.\/0-9:;<=>?@A-Z\[\\\]\^_`a-z]/', '.', $salt);
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
	function process_post($post, $clean = TRUE, $build = FALSE)
	{
		$this->current_row = $post;
		$this->load->helper('text');
		$post->thumbnail_href = $this->get_image_href($post, TRUE);
		$post->image_href = $this->get_image_href($post);
		$post->remote_image_href = $this->get_remote_image_href($post);
		$post->comment_processed = iconv('UTF-8', 'UTF-8//IGNORE', $this->get_comment_processed($post));
		$post->comment = iconv('UTF-8', 'UTF-8//IGNORE', $post->comment);

		foreach (array('title', 'name', 'email', 'trip', 'media', 'preview', 'media_filename', 'media_hash') as $element)
		{
			$element_processed = $element . '_processed';
			$post->$element_processed = iconv('UTF-8', 'UTF-8//IGNORE', fuuka_htmlescape($post->$element));
			$post->$element = iconv('UTF-8', 'UTF-8//IGNORE', $post->$element);
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
			$post->formatted = $this->build_board_comment($post);
	}


	function total_same_media($media_hash)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . $this->table . '
			WHERE media_hash = ?
		', array($media_hash));

		return $query->num_rows();
	}


	function process_media($post, $media, $media_hash)
	{
		if (!get_selected_board()->archive)
		{
			$query = $this->db->query('
				SELECT *
				FROM ' . $this->table . '
				WHERE media_hash = ?
				LIMIT 0,1;
			', array($media_hash));

			if ($query->num_rows() != 0)
			{
				$file = $query->row();

				return array($file->preview, $file->preview_w, $file->preview_h, $media["file_name"], $file->media_w, $file->media_h, $file->media_size, $file->media_hash, $file->media_filename, $post->doc_id);
			}
			else
			{
				$number = $post->timestamp;
			}
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
		$media_filename = $media_unixtime . $media["file_ext"];
		$thumb_filename = $media_unixtime . "s" . $media["file_ext"];

		// image and thumb paths
		$path = array(
			'image_dir' => (get_setting('fs_fuuka_boards_directory') ? get_setting('fs_fuuka_boards_directory') : FOOLFUUKA_BOARDS_DIRECTORY) . "/" . get_selected_board()->shortname . "/img/" . substr($number, 0, 4) . "/" . substr($number, 4, 2) . "/",
			'thumb_dir' => (get_setting('fs_fuuka_boards_directory') ? get_setting('fs_fuuka_boards_directory') : FOOLFUUKA_BOARDS_DIRECTORY) . "/" . get_selected_board()->shortname . "/thumb/" . substr($number, 0, 4) . "/" . substr($number, 4, 2) . "/"
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

		if ($media["image_width"] > 250 || $media["image_height"])
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


	function build_board_comment($p)
	{
		ob_start();

		if (file_exists('content/themes/' . $this->fu_theme . '/views/board_comment.php'))
			include('content/themes/' . $this->fu_theme . '/views/board_comment.php');
		else
			include('content/themes/' . $this->config->item('theme_extends') . '/views/board_comment.php');

		$string = ob_get_contents();
		ob_end_clean();
		return $string;
	}


	function get_image_href($row, $thumbnail = FALSE)
	{
		if (!$row->preview)
			return FALSE;

		if ($row->parent > 0)
			$number = $row->parent;
		else
		{
			if ($row->parent > 0)
				$number = $row->parent;
			else
				$number = $row->num;

			while (strlen((string) $number) < 9)
			{
				$number = '0' . $number;
			}
		}

		if (file_exists($this->get_image_dir($row, $thumbnail)) !== FALSE)
		{
			if (strlen(get_setting('fs_balancer_clients')) > 10)
			{
				$matches = array();
				preg_match('/([\d]+)/', $row->preview, $matches);
				if (isset($matches[1]))
				{
					$balancer_servers = unserialize(get_setting('fs_balancer_clients'));
					$server_num = (intval($matches[1])) % (count($balancer_servers));
					return $balancer_servers[$server_num]['url'] . '/' . get_selected_board()->shortname . '/' . (($thumbnail) ? 'thumb' : 'img') . '/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . (($thumbnail) ? $row->preview : $row->media_filename);
				}
			}
			return (get_setting('fs_fuuka_boards_url') ? get_setting('fs_fuuka_boards_url') : site_url() . FOOLFUUKA_BOARDS_DIRECTORY) . '/' . get_selected_board()->shortname . '/' . (($thumbnail) ? 'thumb' : 'img') . '/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . (($thumbnail) ? $row->preview : $row->media_filename);
		}
		if ($thumbnail)
		{
			$row->preview_h = 150;
			$row->preview_w = 150;
			return site_url() . 'content/themes/default/images/image_missing.jpg';
		}
		else
		{
			return '';
		}
	}


	function get_image_dir($row, $thumbnail = FALSE)
	{
		if (!$row->preview)
			return FALSE;

		if (!get_selected_board()->archive)
		{
			$number = $row->media_filename;
		}
		else
		{
			if ($row->parent > 0)
				$number = $row->parent;
			else
				$number = $row->num;

			while (strlen((string) $number) < 9)
			{
				$number = '0' . $number;
			}
		}

		return ((get_setting('fs_fuuka_boards_directory') ? get_setting('fs_fuuka_boards_directory') : FOOLFUUKA_BOARDS_DIRECTORY)) . '/' . get_selected_board()->shortname . '/' . (($thumbnail === TRUE) ? 'thumb' : 'img') . '/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . (($thumbnail === TRUE) ? $row->preview : $row->media_filename);
	}


	function delete_image($row, $image = TRUE, $thumbnail = TRUE)
	{
		// don't try deleting what isn't there anyway
		if (!$row->preview)
			return TRUE;

		if ($image)
			if (file_exists($this->get_image_dir($row)))
			{
				if (!@unlink($this->get_image_dir($row)))
				{
					log_message('error', 'post.php delete_image(): couldn\'t remove image: ' . $this->get_image_dir($row));
					return FALSE;
				}
			}

		if ($thumbnail)
			if (file_exists($this->get_image_dir($row, TRUE)))
			{
				if (!@unlink($this->get_image_dir($row, TRUE)))
				{
					log_message('error', 'post.php delete_thumbnail(): couldn\'t remove thumbnail: ' . $this->get_image_dir($row, TRUE));
					return FALSE;
				}
			}
		return TRUE;
	}


	function get_remote_image_href($row)
	{
		if (!$row->media)
			return '';

		// ignore webkit and opera and allow rel="noreferrer" do its work
		if (preg_match('/(opera|webkit)/i', $_SERVER['HTTP_USER_AGENT']))
		{
			return get_selected_board()->images_url . $row->media_filename;
		}
		else
		{
			return site_url(get_selected_board()->shortname . '/redirect/' . $row->media_filename);
		}
	}


	function get_comment_processed($row)
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


		$adminfind = array(
			"'\[banned\](.*?)\[/banned\]'i"
		);

		$adminreplace = array(
			'<span class="banned">\\1</span>'
		);


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

		$regexing = htmlentities($regexing, ENT_COMPAT, 'UTF-8');

		$regexing = auto_link($regexing, 'url', TRUE);

		$regexing = preg_replace_callback("'(&gt;&gt;(\d+(?:,\d+)?))'i", array(get_class($this), 'get_internal_link'), $regexing);
		$regexing = preg_replace_callback("'(&gt;&gt;&gt;(\/(\w+)\/(\d+(?:,\d+)?)?(\/?)))'i", array(get_class($this), 'get_crossboard_link'), $regexing);
		if ($row->subnum == 0)
		{
			$regexing = preg_replace($adminfind, $adminreplace, $regexing);
		}

		$regexing = preg_replace($find, $replace, $regexing);
		$regexing = parse_bbcode($regexing);

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
				. '<a href="' . site_url(array(get_selected_board()->shortname, 'thread', ($this->current_row->parent == 0) ? $this->current_row->num : $this->current_row->parent))
				. $_urltag . $this->current_row->num . (($this->current_row->subnum == 0) ? '' : '_' . $this->current_row->subnum)
				. '"' . $_backlink_option . '>&gt;&gt;' . $this->current_row->num . (($this->current_row->subnum == 0) ? '' : ',' . $this->current_row->subnum) . '</a>' . $_suffix;

		if (array_key_exists($num, $this->existing_posts))
		{
			if ($this->backlinks_hash_only_url)
			{
				return $_prefix . '<a href="' . $_urltag . str_replace(',', '_', $num) . '"' . $_option_op . '>&gt;&gt;' . $num . '</a>' . $_suffix;
			}
			return $_prefix . '<a href="' . site_url(array(get_selected_board()->shortname, 'thread', $num)) . $_urltag . str_replace(',', '_', $num) . '"' . $_option_op . '>&gt;&gt;' . $num . '</a>' . $_suffix;
		}

		foreach ($this->existing_posts as $key => $thread)
		{
			if (in_array($num, $thread))
			{
				if ($this->backlinks_hash_only_url)
				{
					return $_prefix . '<a href="' . $_urltag . str_replace(',', '_', $num) . '"' . $_option . '>&gt;&gt;' . $num . '</a>' . $_suffix;
				}
				return $_prefix . '<a href="' . site_url(array(get_selected_board()->shortname, 'thread', $key)) . $_urltag . str_replace(',', '_', $num) . '"' . $_option . '>&gt;&gt;' . $num . '</a>' . $_suffix;
			}
		}

		if ($this->realtime === TRUE)
		{
			return $_prefix . '<a href="' . site_url(array(get_selected_board()->shortname, 'thread', $key)) . $_urltag . str_replace(',', '_', $num) . '"' . $_option . '>&gt;&gt;' . $num . '</a>' . $_suffix;
		}

		// nothing yet? make a generic link with post
		return $_prefix . '<a href="' . site_url(array(get_selected_board()->shortname, 'post', str_replace(',', '_', $num))) . '">&gt;&gt;' . $num . '</a>' . $_suffix;

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


	// function from usort php page
	function multiSort()
	{
		//get args of the function
		$args = func_get_args();
		$c = count($args);
		if ($c < 2)
		{
			return false;
		}
		//get the array to sort
		$array = array_splice($args, 0, 1);
		$array = $array[0];
		//sort with an anoymous function using args
		usort($array, function($a, $b) use($args)
				{

					$i = 0;
					$c = count($args);
					$cmp = 0;
					while ($cmp == 0 && $i < $c)
					{
						$cmp = $a->$args[$i] > $b->$args[$i];
						$i++;
					}

					return $cmp;
				});

		return $array;
	}


}
