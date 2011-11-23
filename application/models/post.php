<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Post extends CI_Model
{

	var $table = '';
	var $table_local = '';
	var $sql_report = '';
	var $existing_posts = array();
	var $existing_posts_not = array();
	var $existing_posts_maybe = array();

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


	/**
	 *
	 * @param type $page
	 * @param type $process
	 * @return type 
	 */
	function get_latest($page = 1, $per_page = 20, $process = TRUE, $clean = TRUE)
	{

		// get exactly 20 be it thread starters or parents with distinct parent
		$query = $this->db->query('
			SELECT DISTINCT( IF(parent = 0, num, parent)) as unq_parent, email
			FROM ' . $this->table . '
			WHERE email != \'sage\'
			ORDER BY num DESC
			LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
		');

		// get all the posts
		$threads = array();
		$sql = array();
		foreach ($query->result() as $row)
		{
			$threads[] = $row->unq_parent;
			$sql[] = '
				(
					SELECT *
					FROM ' . $this->table . '
					' . $this->sql_report . '
					WHERE num = ' . $row->unq_parent . ' OR parent = ' . $row->unq_parent . '
					ORDER BY num DESC
				)
			';
		}

		$sql = implode('UNION', $sql) . '
			ORDER BY num DESC
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
		foreach ($query2->result() as $post)
		{
			if ($process === TRUE)
			{
				$this->process_post($post, $clean);
			}

			if ($post->parent > 0)
			{
				// the first you create from a parent is the first thread
				$result[$post->parent]['posts'][] = $post;
				if (isset($result[$post->parent]['omitted']))
				{
					$result[$post->parent]['omitted']++;
				}
				else
				{
					$result[$post->parent]['omitted'] = -4;
				}
			}
			else
			{
				// this should already exist
				$result[$post->num]['op'] = $post;
				if (!isset($result[$post->num]['omitted']))
				{
					$result[$post->num]['omitted'] = -5;
				}
			}
		}

		// reorder threads, and the posts inside
		$result2 = array();
		foreach ($threads as $thread)
		{
			$result2[$thread] = $result[$thread];
			if (isset($result2[$thread]['posts']))
				$result2[$thread]['posts'] = $this->multiSort($result2[$thread]['posts'], 'num', 'subnum');
		}

		// this is a lot of data, clean it up
		$query2->free_result();
		return $result2;
	}


	function get_latest_ghost($page = 1, $per_page = 20, $process = TRUE, $clean = TRUE)
	{
		// get exactly 20 be it thread starters or parents with distinct parent
		$query = $this->db->query('
			SELECT DISTINCT(parent) as unq_parent, timestamp
			FROM ' . $this->table_local . '
			WHERE email != \'sage\'
			ORDER BY timestamp DESC
			LIMIT ' . intval(($page * $per_page) - $per_page) . ', ' . intval($per_page) . '
		');

		// get all the posts
		$sql = array();
		$threads = array();
		foreach ($query->result() as $row)
		{
			$threads[] = $row->unq_parent;
			$sql[] = '
				(
					SELECT *
					FROM ' . $this->table . '
					' . $this->sql_report . '
					WHERE num = ' . $row->unq_parent . ' OR parent = ' . $row->unq_parent . '
					ORDER BY num ASC
				)
			';
		}

		$sql = implode('UNION', $sql) . '
			ORDER BY num ASC, subnum DESC
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

			if ($post->parent > 0)
			{
				// the first you create from a parent is the first thread
				$result[$post->parent]['posts'][] = $post;
				if (isset($result[$post->parent]['omitted']))
				{
					$result[$post->parent]['omitted']++;
				}
				else
				{
					$result[$post->parent]['omitted'] = -4;
				}
			}
			else
			{
				// this should already exist
				$result[$post->num]['op'] = $post;
				if (!isset($result[$post->num]['omitted']))
				{
					$result[$post->num]['omitted'] = -5;
				}
			}
		}

		// reorder threads, and the posts inside
		$result2 = array();
		foreach ($threads as $thread)
		{
			$result2[$thread] = $result[$thread];
			if (isset($result2[$thread]['posts']))
				$result2[$thread]['posts'] = $this->multiSort($result2[$thread]['posts'], 'num', 'subnum');
		}

		// this is a lot of data, clean it up
		$query2->free_result();
		return $result2;
	}


	function get_posts_ghost($page = 1, $per_page = 1000, $process = TRUE, $clean = TRUE)
	{
		// get exactly 20 be it thread starters or parents with distinct parent
		$query = $this->db->query('
			SELECT num, subnum, timestamp, email
			FROM ' . $this->table_local . '
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
					' . $this->sql_report . '
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


	function get_thread($num, $process = TRUE, $clean = TRUE)
	{
		$query = $this->db->query('
				SELECT * FROM ' . $this->table . '
				' . $this->sql_report . '
				WHERE num = ? OR parent = ?
				ORDER BY num, parent, subnum ASC;
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
		if (isset($result[$num]['posts']))
			$result[$num]['posts'] = $this->multiSort($result[$num]['posts'], 'num', 'subnum');
		return $result;
	}


	function get_post_thread($num)
	{
		$query = $this->db->query('
				SELECT num, parent FROM ' . $this->table . '
				' . $this->sql_report . '
				WHERE num = ? OR parent = ?
				LIMIT 0, 1;
			', array($num, $num));

		foreach ($query->result() as $post)
		{
			if ($post->parent > 0)
				return $post->parent;
			return $post->num;
		}

		return FALSE;
	}


	function get_search($search, $process = TRUE, $clean = TRUE)
	{
		if ($search['page'])
		{
			if (!is_numeric($search['page']) || $search['page'] > 200)
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
			$this->load->library('SphinxClient');
			$this->sphinxclient->SetServer(
					// gotta turn the port into int
					get_setting('fs_sphinx_hostname') ? get_setting('fs_sphinx_hostname') : '127.0.0.1', get_setting('fs_sphinx_hostname') ? get_setting('fs_sphinx_port') : 9312
			);

			$this->sphinxclient->SetLimits(($search['page'] * 25) - 25, 25, 5000);

			if ($search['username'])
			{
				$this->sphinxclient->setFilter('name', $search['username']);
			}

			if ($search['tripcode'])
			{
				$this->sphinxclient->setFilter('trip', $search['tripcode']);
			}

			if ($search['text'])
			{
				//	$this->sphinxclient->setFilter('comment', $search['text']);
			}

			if ($search['deleted'] == "deleted")
			{
				$this->sphinxclient->setFilter('is_deleted', 1);
			}
			if ($search['deleted'] == "not-deleted")
			{
				$this->sphinxclient->setFilter('is_deleted', 0);
			}

			if ($search['ghost'] == "only")
			{
				$this->sphinxclient->setFilter('is_internal', 1);
			}
			if ($search['ghost'] == "none")
			{
				$this->sphinxclient->setFilter('is_internal', 0);
			}

			$this->sphinxclient->setMatchMode(SPH_MATCH_ALL);
			$this->sphinxclient->setSortMode(SPH_SORT_ATTR_DESC, 'num');
			$search_result = $this->sphinxclient->query($search['text'], 'a_ancient a_main a_delta');
			if ($search_result === false)
			{
				// show some actual error...
				show_404();
			}

			$sql = array();

			if (empty($search_result['matches']))
			{
				$result[0]['posts'] = array();
				return $result;
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
			$result[0]['posts'] = array_reverse($result[0]['posts']);
			return array_reverse($result);
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
		$result[0]['posts'] = array_reverse($result[0]['posts']);
		return $result;
	}


	/*	 * ***
	 * POSTING FUNCTIONS
	 * *** */
	function comment($data)
	{
		if(check_stopforumspam_ip($this->input->ip_address()))
		{
			show_404();
		}
		
		$errors = array();
		if ($data['name'] == FALSE || $data['name'] == '')
		{
			$name = 'Anonymous';
			$trip = '';
		}
		else
		{
			$name_arr = process_name($data['name']);
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
			$this->input->set_cookie('foolfuuka_reply_email', $password, 60*60*24*30);
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
			$this->input->set_cookie('foolfuuka_reply_password', $password, 60*60*24*30);
		}

		$this->db->or_where('ip', $this->input->ip_address());
		if ($this->session->userdata('poster_id'))
		{
			$this->db->or_where('id', $this->session->userdata('poster_id'));
		}
		$query = $this->db->get('posters');

		// if any data that could stop the query is returned, no need to add a row
		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				if ($this->banned == 1)
				{
					// change to proper message
					show_404();
				}

				if ($this->lastpost - time() < 20) // 20 seconds
				{
					// change to proper message
					show_404();
				}
			}
		}
		else
		{
			$insert_poster = array(
				'ip' => $this->input->ip_address(),
				'user_agent' => $this->input->user_agent()
			);
			$this->db->insert('posters', $insert_poster);
			$poster_id = $this->db->insert_id();
			$this->session->set_userdata('poster_id', $poster_id);
		}

		// get the post after which we're replying to
		// mostly copied from Fuuka original
		$this->db->query('
				INSERT INTO ' . $this->table . '
				(num, subnum, parent, timestamp, capcode, email, name, trip, title, comment, pass, poster_id)
				VALUES
				(
					(select max(num) from (select * from ' . $this->table . ' where parent=? or num=?) as x),
					(select max(subnum)+1 from (select * from ' . $this->table . ' where num=(select max(num) from ' . $this->table . ' where parent=? or num=?)) as x),
					?, CURRENT_TIMESTAMP,?,?,?,?,?,?,?,?)
				)
			', array(
				$num, $num, 
				$num, $num,
				$num, 'N', $email, $name, $trip, $title, $comment, $password, $this->session->userdata('poster_id'))
		);
		
		// copypasta from fuuka...
		$this->db->query('
			replace into '.$this->table_local.' (num,parent,subnum,`timestamp`) 
			select num,case when parent = 0 then num else parent end as parent,max(subnum),max(`timestamp`) from '.$this->table.'
			where num = (select max(num) from '.$this->table.' where parent=?);
		', array($num));
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
				$trip = '!' . $this->tripcode($matches2[1]);
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
		$pw = mb_convert_encoding($plain, 'SJIS', 'UTF-8');
		$pw = str_replace('&', '&amp;', $pw);
		$pw = str_replace('"', '&quot;', $pw);
		$pw = str_replace("'", '&#39;', $pw);
		$pw = str_replace('<', '&lt;', $pw);
		$pw = str_replace('>', '&gt;', $pw);

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


	/*	 * ***
	 * MISC FUNCTIONS
	 * *** */
	function process_post($post, $clean = TRUE)
	{
		$this->load->helper('text');
		$post->thumbnail_href = $this->get_thumbnail_href($post);
		$post->comment_processed = $this->get_comment_processed($post);

		// evil: &#8238; oykosneG fo suomynonA ,onriC&#8234;&#8234;&#8234;&#9320;
		foreach (array('title', 'name', 'email', 'trip') as $element)
		{
			$element_processed = $element . '_processed';
			$post->$element = preg_replace('/[\x{E2}\x{80}\x{AE}]/', '', $post->$element);
			//$post->$element = ascii_to_entities($post->$element);
			$post->$element = htmlentities($post->$element, ENT_COMPAT | ENT_IGNORE, 'UTF-8');
			$post->$element_processed = $post->$element;
		}

		if ($clean === TRUE)
		{
			unset($post->delpass);
		}
	}


	function get_thumbnail_href($row)
	{
		if (!$row->preview)
			return '';
		$echo = '';
		if ($row->parent > 0)
			$number = $row->parent;
		else
			$number = $row->num;
		while (strlen((string) $number) < 9)
		{
			$number = '0' . $number;
		}

		if (file_exists((get_setting('fs_fuuka_boards_url') ? get_setting('fs_fuuka_boards_url') : FOOLFUUKA_BOARDS_DIRECTORY)) . '/' . get_selected_board()->shortname . '/thumb/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . $row->preview)
			return (get_setting('fs_fuuka_boards_url') ? get_setting('fs_fuuka_boards_url') : site_url() . FOOLFUUKA_BOARDS_DIRECTORY) . '/' . get_selected_board()->shortname . '/thumb/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . $row->preview;
		return '';
	}


	function get_comment_processed($row)
	{
		$CI = & get_instance();
		$find = array(
			"'(\r?\n|^)(&gt;.*?)(?=$|\r?\n)'i",
			"'\[aa\](.*?)\[/aa\]'is",
			"'\[spoiler](.*?)\[/spoiler]'is",
			"'\[sup\](.*?)\[/sup\]'is",
			"'\[sub\](.*?)\[/sub\]'is",
			"'\[b\](.*?)\[/b\]'is",
			"'\[i\](.*?)\[/i\]'is",
			"'\[u\](.*?)\[/u\]'is",
			"'\[s\](.*?)\[/s\]'is",
			"'\[o\](.*?)\[/o\]'is",
			"'\[m\](.*?)\[/m\]'i",
			"'\[code\](.*?)\[/code\]'i",
			"'\[EXPERT\](.*?)\[/EXPERT\]'i",
			"'\[banned\](.*?)\[/banned\]'i",
		);

		$replace = array(
			'\\1<span class="greentext">\\2</span>\\3',
			'<span class="aa">\\1</span>',
			'<span class="spoiler">\\1</span>',
			'<sup>\\1</sup>',
			'<sub>\\1</sub>',
			'<strong>\\1</strong>',
			'<em>\\1</em>',
			'<span class="u">\\1</span>',
			'<span class="s">\\1</span>',
			'<span class="o">\\1</span>',
			'<tt class="code">\\1</tt>',
			'<code>\\1</code>',
			'<b><span class="u"><span class="o">\\1</span></span></b>',
			'<span class="banned">\\1</span>',
		);

		$regexing = $row->comment;
		$regexing = htmlentities($regexing, ENT_COMPAT, 'UTF-8');
		$regexing = preg_replace_callback("'(&gt;&gt;(\d+(?:,\d+)?))'i", array(get_class($this), 'get_internal_link'), $regexing);
		$regexing = nl2br(trim(preg_replace($find, $replace, $regexing)));

		return $regexing;
	}


	function get_internal_link($matches)
	{
		$num = $matches[2];

		// check if it's the OP that is being linked to
		if (array_key_exists($num, $this->existing_posts))
		{
			return '<a href="' . site_url(get_selected_board()->shortname . '/thread/' . $num . '/') . '#' . $num . '" rel="highlight" id="' . $num . '">&gt;&gt;' . $num . '</a>';
		}

		// check if it's one of the posts we've already met
		foreach ($this->existing_posts as $key => $thread)
		{
			if (in_array($num, $thread))
			{
				return '<a href="' . site_url(get_selected_board()->shortname . '/thread/' . $key . '/') . '#' . str_replace(',', '_', $num) . '" rel="highlight" id="' . $num . '">&gt;&gt;' . $num . '</a>';
			}
		}

		// nothing yet? make a generic link with post
		return '<a href="' . site_url(get_selected_board()->shortname . '/post/' . $num . '/') . '">&gt;&gt;' . $num . '</a>';

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