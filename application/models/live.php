<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Live extends CI_Model
{

	var $processes = array(
		'timers' => array(
			0 => array(
				'limit' => 500,
				'functions' => array(),
				'backwards' => FALSE // master switch for backwards
			// it's unsafe to do image processing zero seconds later!
			),
			10 => array(
				'limit' => 5000,
				'functions' => array(
					array( 
						'function' => 'libpuzzle_store',
						'backwards' => TRUE
					),
				),
				'backwards' => TRUE
			)
		)
	);
	
	var $delays = null;

	function __construct($id = NULL)
	{
		parent::__construct();
		puzzle_set_lambdas(9);
		$this->load->model('post');
		$this->load->model('statistics');
	}


	/**
	 * The delays are stored in the same statistics table under _delays
	 * 
	 * @return array 
	 */
	function get_delays($board)
	{
		if (!is_null($this->delays))
			return $this->delays;

		// returns the object
		$delays = $this->statistics->get_stat($board->id, '_live_delays');

		if ($delays == FALSE)
		{
			return $this->delays[$board->id] = array();
		}
		else
		{
			$result = @json_decode($delays->data, TRUE);
			if (is_null($result)) // failing decode
			{
				$this->delays[$board->id] = array();
			}
			else
			{
				$this->delays[$board->id] = $result;
			}
		}
	}


	function cron()
	{
		foreach ($this->radix->get_all() as $board)
		{
			$this->run($board);
		}
	}


	function run($board)
	{
		// fill up the delays table with an array
		$this->get_delays($board);
		
		foreach ($this->processes['timers'] as $key => $process)
		{
			if (isset($this->delays[$board->id][$key]['latest_doc_id']))
				$latest_doc_id = $this->delays[$board->id][$key]['latest_doc_id'];
			else
				$latest_doc_id = 0;
			
			if (isset($this->delays[$board->id][$key]['inferior_doc_id']))
				$inferior_doc_id = $this->delays[$board->id][$key]['inferior_doc_id'];
			else
				$inferior_doc_id = 0;

			$posts = $this->post->get_with_delay($board, $process['limit'], $key, $latest_doc_id);

			if ($posts == FALSE && $process['backwards'])
			{
				// if we're done with going upwards, let's go backwards
				$posts = $this->post->get_with_delay($board, $process['limit'], $key, 0, $inferior_doc_id);
			}
			
			if ($posts == FALSE)
			{
				continue;
			}
						
			foreach ($process['functions'] as $func)
			{
				$fun = $func['function'];
				$this->$fun($board, $posts);
			}
			
			foreach($posts as $post)
			{
				if($latest_doc_id < $post->doc_id)
					$latest_doc_id = $post->doc_id;
				
				if($inferior_doc_id > $post->doc_id)
					$inferior_doc_id = $post->doc_id;
			}
			
			$this->delays[$board->id][$key]['latest_doc_id'] = $latest_doc_id;
			$this->delays[$board->id][$key]['inferior_doc_id'] = $inferior_doc_id;
			$this->statistics->save_stat($board->id, '_live_delays', time(), $this->delays[$board->id]);
		}
		
		
	}


	function libpuzzle_store($board, $posts)
	{
		foreach($posts as $row)
		{
			$image_path = $this->post->get_image_dir($board, $row);
			
			// if we don't have the full image, use the thumbnail
			if(!$image_path)
				$image_path = $this->post->get_image_dir($board, $row, TRUE);
			
			if($image_path)
			{
				// if we already indexed this md5 even through a thumbnail, we're done
				$query = $this->db->query('
					SELECT *
					FROM '. $this->db->protect_identifiers('libpuz_signatures', TRUE) .'
					WHERE md5 = ?
					LIMIT 0, 1
				', array($row->media_hash));
				
				// if it's already in database, don't bother
				if($query->num_rows() != 0)
					continue;

				$signature = puzzle_fill_cvec_from_file($image_path);

				$this->db->query('
					INSERT
					INTO '. $this->db->protect_identifiers('libpuz_signatures', TRUE) .'
					(signature, md5) VALUES (?, ?)
				', array(puzzle_compress_cvec($signature), $row->media_hash));

				$insert_id = $this->db->insert_id();
				
				$words = array();
				
				for ($i = 0; $i < 100; $i++){
					$words[] = array('word' => mb_substr($signature,$i,10), 'pos' => $i, 'signature_id' => $insert_id);
				}
				
				$query->free_result();
				
				$this->db->insert_batch('libpuz_words', $words);
				
				
				}
		}
	}
	
	
	function improve_thumbnails($board, $posts)
	{
		
	}


}