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
	var $existing_posts_not = array();
	var $existing_posts_maybe = array();
	var $features = TRUE;
	var $realtime = FALSE;

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
			$this->process_post($post, TRUE);
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
					WHERE ' . $this->table . '.email != \'sage\'
					AND subnum > 0
					ORDER BY timestamp DESC
					LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
				) AS t
				LEFT JOIN ' . $this->table . ' AS g
					ON g.num = t.unq_parent AND g.subnum = 0
				' . $this->sql_report_after_join . '
			');
		}
		else if($thread_order)
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
					WHERE email != \'sage\'
					ORDER BY num DESC
					LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
				) AS t
				LEFT JOIN ' . $this->table . ' AS g
					ON g.num = t.unq_parent AND g.subnum = 0
				' . $this->sql_report_after_join . '
			');
		}


		if($query->num_rows() == 0)
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
			//if (isset($result2[$thread]['posts']))
			//	$result2[$thread]['posts'] = $this->multiSort($result2[$thread]['posts'], 'num', 'subnum');
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
			if ($process === TRUE)
			{
				$this->process_post($post, $clean);
			}

			if ($post->parent > 0)
			{
				// the first you create from a parent is the first thread
				$result[$post->parent]['posts'][] = $post;
			}
			else
			{
				// this should already exist
				$result[$post->num]['op'] = $post;
			}
		}
		// this could be a lot of data, clean it up
		$query->free_result();


		// easier to revert the array here for now
		//if (isset($result[$num]['posts']))
		//	$result[$num]['posts'] = $this->multiSort($result[$num]['posts'], 'num', 'subnum');
		return $result;
	}


	function get_post_thread($num, $subnum = 0)
	{
		$query = $this->db->query('
				SELECT num, parent, subnum FROM ' . $this->table . '
				' . $this->sql_report . '
				WHERE parent = ? OR (num = ? AND subnum = ?)
				LIMIT 0, 1;
			', array($num, $num, $subnum));

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
			if (!is_numeric($search['page']) || $search['page'] > 30)
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
					echo $this->sphinxclient->getLastError();
					// show some actual error...
					show_404();
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
			if(is_array($result[0]['posts']) && $do_reverse)
				$result[0]['posts'] = array_reverse($result[0]['posts']);
			return array('posts' => $result, 'total_found' => $search_result['total_found']);
		}
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
		$result[0]['posts'] = $result[0]['posts'];
		return $result;
	}


	/**
	 * POSTING FUNCTIONS
	 * */
	function comment($data)
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

		// phpass password for extra security, using the same tank_auth setting since it's cool
		$hasher = new PasswordHash(
						$this->config->item('phpass_hash_strength', 'tank_auth'),
						$this->config->item('phpass_hash_portable', 'tank_auth'));
		$password = $hasher->HashPassword($password);

		$num = $data['num'];
		$postas = $data['postas'];

		if ($this->session->userdata('poster_id'))
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
				if ($row->banned == 1)
				{
					return array('error' => 'You are banned from posting.');
				}

				if (time() - strtotime($row->lastpost) < 10 && !$this->tank_auth->is_allowed()) // 10 seconds
				{
					return array('error' => 'You must wait at least 10 seconds before posting again.');
				}

				if (time() - strtotime($row->lastpost) < 20 && $row->lastcomment == $comment)
				{
					return array('error' => 'Your post contained the same text as your previous post.');
				}

				$this->db->where('id', $row->id);
				$this->db->update('posters', array('lastcomment' => $comment, 'lastpost' => date('Y-m-d H:i:s')));
			}
		}
		else
		{
			if (strlen($comment) > 4096)
			{
				return array('error' => 'Your post was too long.');
			}

			$lines = explode("\n", $comment);

			if (count($lines) > 40)
			{
				return array('error' => 'Your post had too many lines.');
			}

			$insert_poster = array(
				'ip' => $this->input->ip_address(),
				'user_agent' => $this->input->user_agent()
			);
			$this->db->insert('posters', $insert_poster);
			$poster_id = $this->db->insert_id();
			$this->session->set_userdata('poster_id', $poster_id);
		}

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

		// I need num and subnum for a proper redirect
		$posted = $this->db->query('
			SELECT * FROM ' . $this->table . '
			WHERE doc_id = ?
			LIMIT 0,1;
		', array($this->db->insert_id()));

		// we don't even need this, but let's leave it for sake of backward compatibility with original fuuka
		$this->db->query('
			replace into ' . $this->table_local . ' (num,parent,subnum,`timestamp`)
			select num,case when parent = 0 then num else parent end as parent,max(subnum),max(`timestamp`) from ' . $this->table . '
			where num = (select max(num) from ' . $this->table . ' where parent=?);
		', array($num));

		$posted = $posted->result();
		$posted = $posted[0];
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

		// safe to say the user is allowed to remove it

		if ($row->parent == 0) // deleting thread
		{
			// we risk getting into a racing condition
			// get rid first of all of OP so posting is stopped
			// first the file
			if (!$this->delete_image($row))
			{
				log_message('error', 'post.php delete() couldn\'t delete thumbnail from thread OP');
				return array('error' => _('Couldn\'t delete the thumbnail.'));
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
			if ($this->delete_image($row) !== TRUE)
			{
				log_message('error', 'post.php delete() couldn\'t delete thumbnail from comment');
				return array('error' => _('Couldn\'t delete the thumbnail.'));
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
	function process_post($post, $clean = TRUE)
	{
		$this->load->helper('text');
		$post->thumbnail_href = $this->get_image_href($post, TRUE);
		$post->image_href = $this->get_image_href($post);
		$post->remote_image_href = $this->get_remote_image_href($post);
		$post->comment_processed = (iconv('UTF-8', 'UTF-8//IGNORE', $this->get_comment_processed($post)));
		$post->comment = (iconv('UTF-8', 'UTF-8//IGNORE', $post->comment));

		foreach (array('title', 'name', 'email', 'trip', 'media', 'preview', 'media_filename', 'media_hash') as $element)
		{
			$element_processed = $element . '_processed';
			$post->$element_processed = (iconv('UTF-8', 'UTF-8//IGNORE', fuuka_htmlescape($post->$element)));
			$post->$element = (iconv('UTF-8', 'UTF-8//IGNORE', $post->$element));
		}

		if ($clean === TRUE)
		{
			unset($post->delpass);
			if (!$this->tank_auth->is_allowed())
			{
				unset($post->poster_id);
			}
		}
		$post->formatted = build_board_comment($post);
	}


	function get_image_href($row, $thumbnail = FALSE)
	{
		if (!$row->preview)
			return FALSE;
		$echo = '';
		if ($row->parent > 0)
			$number = $row->parent;
		else
			$number = $row->num;
		while (strlen((string) $number) < 9)
		{
			$number = '0' . $number;
		}

		if (file_exists($this->get_image_dir($row, $thumbnail)) !== FALSE)
		{
			if ($row->preview_h == 0 && $row->preview_w == 0)
			{
				$row->preview_h = 126;
				$row->preview_w = 126;
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

		if ($row->parent > 0)
			$number = $row->parent;
		else
			$number = $row->num;

		while (strlen((string) $number) < 9)
		{
			$number = '0' . $number;
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

		// Replace Specific Code
		$find = array(
			"'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i"
		);

		$replace = array(
			'\\1<span class="greentext">\\2</span>\\3'
		);

		$adminfind = array(
			"'\[banned\](.*?)\[/banned\]'i"
		);

		$adminreplace = array(
			'<span class="banned">\\1</span>'
		);


		$regexing = $row->comment;
		$regexing = htmlentities($regexing, ENT_COMPAT, 'UTF-8');

		$regexing = auto_link($regexing, 'url', TRUE);

		$regexing = preg_replace_callback("'(&gt;&gt;(\d+(?:,\d+)?))'i", array(get_class($this), 'get_internal_link'), $regexing);
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
		$_suffix = '';
		if ($this->features == FALSE)
		{
			if ($this->fu_theme == 'fuuka')
			{
				$_prefix = '<span class="unkfunc">';
				$_urltag = '#p';
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

		if (array_key_exists($num, $this->existing_posts))
		{
			return $_prefix . '<a href="' . site_url(array(get_selected_board()->shortname,'thread',$num)) . $_urltag . str_replace(',', '_', $num) . '"' . $_option . '>&gt;&gt;' . $num . '</a>' . $_suffix;
		}

		foreach ($this->existing_posts as $key => $thread)
		{
			if (in_array($num, $thread))
			{
				return $_prefix . '<a href="' . site_url(array(get_selected_board()->shortname,'thread',$key)) . $_urltag . str_replace(',', '_', $num) . '"' . $_option . '>&gt;&gt;' . $num . '</a>' . $_suffix;
			}
		}

		if ($this->realtime === TRUE)
		{
			return $_prefix . '<a href="' . site_url(array(get_selected_board()->shortname,'thread',$key)) . $_urltag . str_replace(',', '_', $num) . '"' . $_option . '>&gt;&gt;' . $num . '</a>' . $_suffix;
		}

		// nothing yet? make a generic link with post
		return $_prefix . '<a href="' . site_url(array(get_selected_board()->shortname, 'post', str_replace(',', '_', $num))) . '">&gt;&gt;'.$num.'</a>' . $_suffix;

		// return the thing untouched
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
