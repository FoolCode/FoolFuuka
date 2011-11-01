<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Content extends MY_Controller
{
	function __construct()
	{
		parent::__construct();
		
		// If the balancer master URL is not set, this is not a balancer
		if (!get_setting('fs_balancer_master_url'))
		{
			show_404();
		}
	}


	/**
	 * PHP implementation of lastIndexOf
	 * 
	 * @param string $string
	 * @param string $item
	 * @return string 
	 */
	public function _lastIndexOf($string, $item)
	{
		$index = strpos(strrev($string), strrev($item));
		if ($index)
		{
			$index = strlen($string) - strlen($item) - $index;
			return $index;
		}
		else
			return -1;
	}


	/**
	 * Grabs the failed tries to get to the comics, and if this Slide is active
	 * as a load balancer, it will fetch data from the master Slide, copy and 
	 * serve it.
	 * 
	 * @author Woxxy
	 */
	public function comics()
	{
		// grab the urls
		$this->comic_dir = $this->uri->segment(3);
		$this->chapter_dir = $this->uri->segment(4);
		$this->filename = $this->uri->segment(5);

		// check that $comic is actually the interesting kind of foldername
		// get the divider index
		if (($comic_split = $this->_lastIndexOf($this->comic_dir, '_')) == -1)
		{
			show_404();
		}

		// check that $chapter is actually the interesting kind of foldername
		// get the divider index
		if (($chapter_split = $this->_lastIndexOf($this->chapter_dir, '_')) == -1)
		{
			show_404();
		}

		if (!$this->filename)
		{
			show_404();
		}

		// separate stub and uniqid from both folders
		$this->comic_stub = substr($this->comic_dir, 0, $comic_split);
		$this->comic_uniqid = substr($this->comic_dir, $comic_split + 1);
		$this->chapter_stub = substr($this->chapter_dir, 0, $chapter_split);
		$this->chapter_uniqid = substr($this->chapter_dir, $chapter_split + 1);

		// flag that forces updating the data for this chapter
		$this->update = FALSE;

		// flat that allows us not to send an image, used if we're at least cleaning up
		$this->give_404 = FALSE;

		// check that the comic exists in the database
		$this->comic = new Comic();
		$this->comic->where('stub', $this->comic_stub)->where('uniqid', $this->comic_uniqid)->limit(1)->get();

		// if there's a result, let's check if there's the chapter available
		if ($this->comic->result_count() == 1)
		{
			// we got the comic! let's see if we got the chapter
			$this->chapter = new Chapter();
			$this->chapter->where('stub', $this->chapter_stub)->where('uniqid', $this->chapter_uniqid)->limit(1)->get();

			if ($this->chapter->result_count() == 1)
			{
				// we got the chapter! let's see if we're lucky and we already have its page data
				$this->page = new Page();
				$this->page->where('chapter_id', $this->chapter->id)->where('filename', $this->filename)->get();
				if ($this->page->result_count() == 1)
				{
					// we got its pagedata! let's grab the image
					if ($this->_grab_page())
					{
						$this->output
								->set_content_type($this->page->mime)
								->set_output($this->file);

						// good end
						return TRUE;
					}
				}
			}
		}


		// we will need the url to the master Slide anyway
		$this->url = get_setting('fs_balancer_master_url');

		// we want it always with a trailing slash
		if (substr($this->url, -1, 0) != '/')
		{
			$this->url = $this->url . '/';
		}

		$this->load->library('curl');

		// first of all, does the image even exist? Since we're going to grab
		// the image anyway if it exists, lets get ahead and grab it first
		// uri_string starts with a slash, so we have to remove it
		
		$this->file = $this->curl->simple_get($this->url . 'content/comics/' . $this->comic_stub . '_' . $this->comic_uniqid . '/' . $this->chapter_stub . '_' . $this->chapter_uniqid . '/' . $this->filename);

		$this->load->helper('file');
		
		
		/**
		 *  @todo this still doesn't work in chrome at first load, even with echo
		 */
		$this->output->set_content_type(get_mime_by_extension($this->filename))->_display($this->file);
		
		// if the file doesn't exist, let's not go through the rest of the mess
		if (!$this->file)
		{
			show_404();
		}

		// oh no, this chapter might not be up to date! let's grab the comic data
		// /api/reader/comic gives us the comic data and all its chapters!
		// form the get request and decode the result
		// if the master server works it should be trustable
		$request_url = $this->url . 'api/reader/comic/stub/' . $this->comic_stub . '/uniqid/' . $this->comic_uniqid . '/chapter_stub/' . $this->chapter_stub . '/chapter_uniqid/' . $this->chapter_uniqid . '/format/json';
		$result = $this->curl->simple_get($request_url);
		$result = json_decode($result, TRUE);

		// if there's PHP errors in the $result, the json_decode might fail
		// and return NULL, show a 404.
		if (is_null($result))
		{
			log_message('error', 'content:comics() json_decode failed');
			show_404();
		}

		// just show 404 if the API gives a formal error
		if (isset($result["error"]))
		{
			log_message('error', 'content:comics() json had an error: ' . $result["error"]);
			show_404();
		}

		// search for the value of the chapter, so we don't bother anymore
		// if it doesn't exist in database - though it should
		$found = FALSE;
		foreach ($result["chapters"] as $key => $item)
		{
			if ($item["chapter"]["stub"] == $this->chapter_stub && $item["chapter"]["uniqid"] == $this->chapter_uniqid)
			{
				// update the comic in the database
				$comic = new Comic($result["comic"]["id"]);
				// the comic array fits just right, an update costs nearly nothing
				$comic->from_array($result["comic"]);
				if ($comic->result_count() == 0)
				{
					$comic->save_as_new();
				}
				else
				{
					$comic->save();
				}

				// remove remainants of deleted or updated chapters
				// we need an array of chapters
				$chapter_objects = array();
				foreach ($result["chapters"] as $k => $i)
				{
					$chapter_objects[] = $i["chapter"];
				}

				$this->_clean_comic($result["comic"]["id"], $chapter_objects);
				$this->_clean_chapter($item["chapter"]["id"], $item["chapter"]["pages"]);

				$found = TRUE;
				break;
			}
		}
		if (!$found)
		{
			log_message('error', 'content:comics() chapter was not in the json array');
			show_404();
		}
		
		$this->_grab_page();
	}


	/**
	 * Saves the image, when found. Ignorant of thumb_, it will do those too.
	 * 
	 * @return String file
	 */
	public function _grab_page()
	{
		// we will need the url to the master Slide anyway
		$url = get_setting('fs_balancer_master_url');

		// we want it always with a trailing slash
		if (substr($url, -1, 0) != '/')
		{
			$url = $url . '/';
		}

		$this->load->library('curl');
		// maybe we already have the file, in that case just use it
		if (!isset($this->file))
			$this->file = $this->curl->simple_get($url . 'content/comics/' . $this->comic->directory() . '/' . $this->chapter->directory() . '/' . $this->filename);

		if (!$this->file)
		{
			log_message('error', '_grab_page(): file not found');
			return FALSE;
		}

		// make sure
		if(!is_dir('content/comics/' . $this->comic->directory()))
			@mkdir('content/comics/' . $this->comic->directory());
		if(!is_dir('content/comics/' . $this->comic->directory() . '/' . $this->chapter->directory()))
			@mkdir('content/comics/' . $this->comic->directory() . '/' . $this->chapter->directory());

		file_put_contents('content/comics/' . $this->comic->directory() . '/' . $this->chapter->directory() . '/' . $this->filename, $this->file);

		return TRUE;
	}


	/**
	 * When missing chapters are found, this cleans up and adds new chapters
	 *
	 * @param int $comic_id
	 * @param array $new_chapters_array 
	 */
	public function _clean_comic($comic_id, $new_chapters_array)
	{
		// found, let's get all chapters for this comic
		$chapters = new Chapter();
		$chapters->where('comic_id', $comic_id)->get();
		$chapters = $chapters->all_to_array();

		foreach ($new_chapters_array as $key => $item)
		{
			foreach ($chapters as $k => $i)
			{
				if ($item["id"] == $i["id"])
				{
					if ($item["stub"] != $i["stub"] || $item["uniqid"] != $i["uniqid"])
					{
						$chapter = new Chapter($item["id"]);
						$chapter->remove();
						unset($chapters[$k]);
						break;
					}
					unset($chapters[$k]);
					unset($new_chapters_array[$key]);
					break;
				}
			}
		}

		foreach ($new_chapters_array as $key => $item)
		{
			$chapter = new Chapter();
			$chapter->from_array($item);
			$chapter->save_as_new();
		}

		foreach ($chapters as $key => $item)
		{
			$chapter = new Chapter($item["id"]);
			$chapter->remove();
		}
	}


	/**
	 * When you pages are added, this cleans up the chapter and adds new pages
	 * 
	 * @param int $chapter_id
	 * @param array $new_pages_array 
	 */
	public function _clean_chapter($chapter_id, $new_pages_array)
	{
		// found, let's get all chapters for this comic
		$pages = new Page();
		$pages->where('chapter_id', $chapter_id)->get();
		$pages = $pages->all_to_array();

		foreach ($new_pages_array as $key => $item)
		{
			foreach ($pages as $k => $i)
			{
				if ($item["id"] == $i["id"])
				{
					if ($item["filename"] != $i["filename"] || $item["size"] != $i["size"])
					{
						$page = new Page($item["id"]);
						$page->remove_page();
						unset($pages[$k]);
						break;
					}
					unset($pages[$k]);
					unset($new_pages_array[$key]);
					break;
				}
			}
		}

		foreach ($new_pages_array as $key => $item)
		{
			$page = new Page();
			$page->from_array($item);
			$page->save_as_new();
		}

		foreach ($pages as $key => $item)
		{
			$page = new Page();
			$page->remove_page();
		}
	}


}