<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Post extends CI_Model
{

	var $table = '';

	function __construct($id = NULL)
	{
		$this->table = $this->db->protect_identifiers('woxxy_tv') . '.' . $this->db->protect_identifiers(get_selected_board()->shortname);
		;
		parent::__construct();
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
				$result[$post->parent]['posts'][$post->num] = $post;
				if(isset($result[$post->parent]['omitted']))
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
				if(!isset($result[$post->num]['omitted']))
				{
					$result[$post->num]['omitted'] = -5;
				}
			}
		}
		
		// this is a lot of data, clean it up
		$query2->free_result();
		return $result;
	}

	
	function get_thread($num, $process = TRUE) {
		
		$query = $this->db->query('
				SELECT * FROM '. $this->table .'
				WHERE num = ? OR parent = ?
				ORDER BY num, parent, subnum ASC;
			', 
			array($num, $num));
		
		$result = array();
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
				$result[$post->parent]['posts'][$post->num] = $post;
			}
			else
			{
				// this should already exist
				$result[$post->num]['op'] = $post;
			}
		}
		// this could be a lot of data, clean it up
		$query->free_result();
		
		return $result;
	}

	function get_thumbnail_href($row)
	{
		$echo = '';
		if($row->parent > 0)
			$number = $row->parent;
		else
			$number = $row->num;
		while (strlen((string) $number) < 9)
		{
			$number = '0' . $number;
		}
		
		if(file_exists((get_setting('fs_fuuka_boards_url')?get_setting('fs_fuuka_boards_url'):FOOLFUUKA_BOARDS_DIRECTORY)).'/' . get_selected_board()->shortname . '/thumb/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . $row->preview)
			return (get_setting('fs_fuuka_boards_url')?get_setting('fs_fuuka_boards_url'):site_url() . FOOLFUUKA_BOARDS_DIRECTORY).'/' . get_selected_board()->shortname . '/thumb/' . substr($number, 0, 4) . '/' . substr($number, 4, 2) . '/' . $row->preview;
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
		//$regexing = preg_replace_callback("'(>>(\d+(?:,\d+)?))'i", array(get_class($this), 'get_internal_link'), $regexing);
		return nl2br(preg_replace($find, $replace, $regexing));
	}


	function get_internal_link($matches)
	{
		$CI = & get_instance();
		$num = substr($matches[0], 2);
		if (!is_numeric($num) || !$num > 0)
		{
			return $matches[0];
		}

		$post = new Post();
		$post->where('num', $num)->get();
		if ($post->result_count() == 0)
		{
			return $matches[0];
		}

		return '<a href="' . site_url($CI->fu_board . '/thread/' . $post->parent . '/') . '#' . $post->num . '">&gt;&gt;' . $num . '</a>';
	}


}