<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Post extends CI_Model
{

	var $table = '';
	var $table_local = '';
	var $existing_posts = array();
	var $existing_posts_not = array();
	var $existing_posts_maybe = array();

	function __construct($id = NULL)
	{
		parent::__construct();
		$this->get_table();
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
	function get_latest($page = 1, $per_page = 20, $process = TRUE)
	{

		// get exactly 20 be it thread starters or parents with distinct parent
		$query = $this->db->query('
			SELECT DISTINCT( IF(parent = 0, num, parent)) as unq_parent
			FROM ' . $this->table . '
			ORDER BY num DESC
			LIMIT ' . (($page * $per_page) - $per_page) . ', ' . $per_page . '
		');

		// get all the posts
		$sql = array();
		foreach ($query->result() as $row)
		{
			$sql[] = '
				(
					SELECT *
					FROM ' . $this->table . '
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
				$post->thumbnail_href = $this->get_thumbnail_href($post);
				$post->comment_processed = $this->get_comment_processed($post);
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

		// this is a lot of data, clean it up
		$query2->free_result();
		return $result;
	}


	function get_thread($num, $process = TRUE)
	{
		$query = $this->db->query('
				SELECT * FROM ' . $this->table . '
				WHERE num = ? OR parent = ?
				ORDER BY num, parent, subnum ASC;
			', array($num, $num));

		$result = array();

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
				$post->thumbnail_href = $this->get_thumbnail_href($post);
				$post->comment_processed = $this->get_comment_processed($post);
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
		$result[$num]['posts'] = array_reverse($result[$num]['posts']);
		return $result;
	}


	function get_post_thread($num)
	{
		$query = $this->db->query('
				SELECT num, parent FROM ' . $this->table . '
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


	function get_search($search, $process = TRUE)
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
			
			if(empty($search_result['matches']))
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
					$post->thumbnail_href = $this->get_thumbnail_href($post);
					$post->comment_processed = $this->get_comment_processed($post);
				}
				// the first you create from a parent is the first thread
				$result[0]['posts'][] = $post;
			}
			
			return $result;
		}
	}


	function get_thumbnail_href($row)
	{
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
			"'(\r?\n|^)(>.*?)(?=$|\r?\n)'i",
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
		$regexing = preg_replace_callback("'(>>(\d+(?:,\d+)?))'i", array(get_class($this), 'get_internal_link'), $row->comment);
		return nl2br(trim(preg_replace($find, $replace, $regexing)));
	}


	function get_internal_link($matches)
	{
		$num = $matches[2];

		// check if it's the OP that is being linked to
		if (array_key_exists($num, $this->existing_posts))
		{
			return '<a href="' . site_url(get_selected_board()->shortname . '/thread/' . $num . '/') . '#' . $num . '">&gt;&gt;' . $num . '</a>';
		}

		// check if it's one of the posts we've already met
		foreach ($this->existing_posts as $key => $thread)
		{
			if (in_array($num, $thread))
			{
				return '<a href="' . site_url(get_selected_board()->shortname . '/thread/' . $key . '/') . '#' . str_replace(',', '_', $num) . '">&gt;&gt;' . $num . '</a>';
			}
		}

		// nothing yet? make a generic link with post
		return '<a href="' . site_url(get_selected_board()->shortname . '/post/' . $num . '/') . '">&gt;&gt;' . $num . '</a>';

		// return the thing untouched
		return $matches[0];
	}


}