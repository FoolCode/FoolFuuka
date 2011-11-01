<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Archive extends DataMapper
{

	var $has_one = array();
	var $has_many = array();
	var $validation = array(
		'chapter_id' => array(
			'rules' => array(),
			'label' => 'Chapter ID',
		),
		'filename' => array(
			'rules' => array(),
			'label' => 'Filename'
		),
		'size' => array(
			'rules' => array(),
			'label' => 'Size',
		),
		'lastdownload' => array(
			'rules' => array(),
			'label' => 'Last download',
		)
	);

	function __construct($id = NULL)
	{
		parent::__construct($id);
	}


	function post_model_init($from_cache = FALSE)
	{
		
	}


	/**
	 * Creates a compressed cache file for the chapter
	 * 
	 * @author Woxxy
	 * @return url to compressed file
	 */
	function compress($chapter)
	{
		$chapter->get_comic();
		$chapter->get_pages();
		$files = array();

		$this->where('chapter_id', $chapter->id)->get();
		if ($this->result_count() == 0 || !file_exists("content/comics/" . $chapter->comic->directory() . "/" . $chapter->directory() . "/" . $this->filename))
		{
			$this->remove_old();
			$CI = & get_instance();

			require_once(FCPATH . 'assets/pclzip/pclzip.lib.php');
			$filename = $this->filename_compressed($chapter);
			$archive = new PclZip("content/comics/" . $chapter->comic->directory() . "/" . $chapter->directory() . "/" . $filename . '.zip');

			$filearray = array();
			foreach ($chapter->pages as $page)
			{
				$filearray[] = "content/comics/" . $chapter->comic->directory() . "/" . $chapter->directory() . "/" . $page["filename"];
			}

			$v_list = $archive->create(implode(',', $filearray), PCLZIP_OPT_REMOVE_ALL_PATH, PCLZIP_OPT_ADD_PATH, $filename, PCLZIP_OPT_NO_COMPRESSION);

			$this->chapter_id = $chapter->id;
			$this->filename = $filename . '.zip';
			$this->size = filesize("content/comics/" . $chapter->comic->directory() . "/" . $chapter->directory() . "/" . $filename . '.zip');
			$this->lastdownload = date('Y-m-d H:i:s', time());
			$this->save();
		}
		else
		{
			$this->lastdownload = date('Y-m-d H:i:s', time());
			$this->save();
		}

		return array(
			"url" => site_url() . "content/comics/" . $chapter->comic->directory() . "/" . $chapter->directory() . "/" . urlencode($this->filename),
			"server_path" => FCPATH . "content/comics/" . $chapter->comic->directory() . "/" . $chapter->directory() . "/" . $this->filename
		);
	}


	/**
	 * Removes the compressed file from the disk and database
	 * 
	 * @author Woxxy
	 * @returns bool 
	 */
	function remove()
	{
		$chapter = new Chapter($this->chapter_id);
		$chapter->get_comic();

		if (file_exists("content/comics/" . $chapter->comic->directory() . "/" . $chapter->directory() . "/" . $this->filename))
		{
			if (!@unlink("content/comics/" . $chapter->comic->directory() . "/" . $chapter->directory() . "/" . $this->filename))
			{
				log_message('error', 'remove: error when trying to unlink() the compressed ZIP');
				return FALSE;
			}
		}

		$this->delete();
	}


	/**
	 * Calculates the size of the currently stored ZIPs
	 * 
	 * @author Woxxy
	 * @returns int 
	 */
	function calculate_size()
	{
		$this->select_sum('size')->get();
		return $this->size;
	}


	/**
	 * Removes ZIPs that are over the specified size
	 * 
	 * @author Woxxy
	 * @returns bool 
	 */
	function remove_old()
	{
		$unlink_errors = 0;
		while ($this->calculate_size() > (get_setting('fs_dl_archive_max') * 1024 * 1024))
		{
			$archive = new Archive();
			$archive->order_by('lastdownload', 'ASC')->limit(1, $unlink_errors)->get();
			if ($archive->result_count() == 1)
			{
				if (!$archive->remove())
				{
					$unlink_errors++;
				}
			}
			else
			{
				break;
			}
		}
	}


	/**
	 * Removes all the ZIPs
	 * 
	 * @author Woxxy
	 * @returns bool 
	 */
	function remove_all()
	{
		$archives = new Archive();
		$archives->get();
		foreach ($archive->all as $archive)
		{
			$archive->remove();
		}
	}


	/**
	 * Creates the filename for the ZIP
	 * 
	 * @author Woxxy
	 * @returns bool 
	 */
	function filename_compressed($chapter)
	{
		$chapter->get_teams();
		$chapter->get_comic();
		$filename = "";
		/*
		 *  Proposal for guesser
		 * 
		 *  %comic% just name
		 *  %_comic% name with underscores
		 * 	%volume% volume number
		 * 	%chapter% chapter number
		 *  %subchapter% subchapter number
		 *  {volume ... } section dedicated to the volume
		 *  {chapter ... } section dedicated to the chapter
		 *  {subchapter ... } section dedicated to the subchapter
		 *  %group% print the name of the group
		 *  %r_group% print the separator for beginning of groups
		 *  %l_group% print the separator for end of group
		 *  %mid_group% print separator between groups
		 */

		foreach ($chapter->teams as $team)
		{
			$filename .= "[" . $team->name . "]";
		}
		$filename .= $chapter->comic->name;
		if ($chapter->volume !== FALSE && $chapter->volume != 0)
			$filename .= '_v' . $chapter->volume;
		$filename .= '_c' . $chapter->chapter;
		if ($chapter->subchapter !== FALSE && $chapter->subchapter != 0)
			$filename .= '_s' . $chapter->subchapter;

		$filename = str_replace(" ", "_", $filename);

		$bad = array_merge(
				array_map('chr', range(0, 31)), array("<", ">", ":", '"', "/", "\\", "|", "?", "*"));
		$filename = str_replace($bad, "", $filename);

		return $filename;
	}


}

/* End of file team.php */
/* Location: ./application/models/archive.php */