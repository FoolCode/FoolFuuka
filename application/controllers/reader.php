<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Reader extends Public_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->library('pagination');
		$this->load->library('template');
		$this->template->set_layout('reader');
	}


	public function index()
	{
		$this->latest();
	}
	
	function sitemap()
	{
		$sitemap = array(
			array(
				// homepage
				'loc' => site_url(),
				'lastmod' => '', // not needed,
				'changefreq' => 'hourly', // extremely fast
				'priority' => '0.8'
			),
			array(
				// release list page
				'loc' => site_url('reader/list'),
				'lastmod' => '',
				'changefreq' => 'weekly', // comics picked up don't change often
				'priority' => '0.5'
			)
		);

		$comics = new Comic();
		$comics->get_iterated();
		foreach ($comics as $comic)
		{
			$sitemap[] =
					array(
						// homepage
						'loc' => $comic->href(),
						'lastmod' => '',
						'changefreq' => 'daily',
						'priority' => '0.4'
			);
		}

		$chapters = new Chapter();
		$chapters->get_iterated();
		foreach ($chapters as $chapter)
		{
			$sitemap[] =
					array(
						// homepage
						'loc' => $chapter->href(),
						'lastmod' => $chapter->created,
						'changefreq' => 'daily',
						'priority' => '0.4'
			);
		}

		$data["sitemap"] = $sitemap;
		$this->load->view('sitemap', $data);
	}


	function feeds($format = NULL)
	{
		//if (is_null($format))
		//	redirect('reader/feeds/rss');
		$this->load->helper('xml');
		$chapters = new Chapter();

		// filter with orderby
		$chapters->order_by('created', 'DESC');

		// get the generic chapters and the comic coming with them
		$chapters->limit(25)->get();
		$chapters->get_comic();

		if ($chapters->result_count() > 0)
		{
			// let's create a pretty array of chapters [comic][chapter][teams]
			$result['chapters'] = array();
			foreach ($chapters->all as $key => $chapter)
			{
				$result['chapters'][$key]['title'] = $chapter->comic->title() . ' ' . $chapter->title();
				$result['chapters'][$key]['thumb'] = $chapter->comic->get_thumb();
				$result['chapters'][$key]['href'] = $chapter->href();
				$result['chapters'][$key]['created'] = $chapter->created;
				$chapter->get_teams();
				foreach ($chapter->teams as $item)
				{
					$result['chapters'][$key]['teams'] = implode(' | ', $item->to_array());
				}
			}
		}
		else
			show_404();

		$data['encoding'] = 'utf-8';
		$data['feed_name'] = get_setting('fs_gen_site_title');
		$data['feed_url'] = site_url('feeds/rss');
		$data['page_description'] = get_setting('fs_gen_site_title') . ' RSS feed';
		$data['page_language'] = get_setting('fs_gen_lang') ? get_setting('fs_gen_lang') : 'en_EN';
		$data['posts'] = $result;
		if ($format == "atom")
		{
			header("Content-Type: application/atom+xml");
			$this->load->view('atom', $data);
			return TRUE;
		}
		header("Content-Type: application/rss+xml");
		$this->load->view('rss', $data);
	}


	public function team($stub = NULL)
	{
		if (is_null($stub))
			show_404();
		$team = new Team();
		$team->where('stub', $stub)->get();
		if ($team->result_count() < 1)
			show_404();

		$memberships = new Membership();
		$members = $memberships->get_members($team->id);

		$this->template->title(_('Team'));
		$this->template->set('team', $team);
		$this->template->set('members', $members);
		$this->template->build('team');
	}


	public function lista($page = 1)
	{
		$this->template->title(_('Series list'));

		$comics = new Comic();
		/**
		 * @todo this needs filtering, though it looks good enough in browser
		 */
		$comics->order_by('name', 'ASC')->get_paged($page, 25);
		foreach ($comics->all as $comic)
		{
			$comic->latest_chapter = new Chapter();
			$comic->latest_chapter->where('comic_id', $comic->id)->order_by('created', 'DESC')->limit(1)->get();
		}

		$this->template->title(_('Series list'), get_setting('fs_gen_site_title'));
		$this->template->set('comics', $comics);
		$this->template->build('list');
	}


	public function latest($page = 1)
	{
		$this->template->title(_('Series list'));
		// Create a "Chapter" object. It can contain more than one chapter!
		$chapters = new Chapter();

		// Select the latest 25 released chapters
		$chapters->order_by('created', 'DESC')->limit(25);

		// Get the chapters!
		$chapters->get();
		$chapters->get_teams();
		//$chapters->get_comic();

		$this->template->set('chapters', $chapters);
		$this->template->set('is_latest', true);
		$this->template->title(_('Latest releases'), get_setting('fs_gen_site_title'));
		$this->template->build('latest');
	}


	public function read($comic, $language = 'en', $volume = 0, $chapter = "", $subchapter = 0, $team = 0, $joint = 0, $pagetext = 'page', $page = 1)
	{
		$comice = new Comic();
		$comice->where('stub', $comic)->get();
		if ($comice->result_count() == 0)
		{
			set_notice('warn', 'This comic doesn\'t exist.');
		}

		if ($chapter == "")
		{
			redirect('/reader/series/' . $comic);
		}

		$chaptere = new Chapter();
		$chaptere->where('comic_id', $comice->id)->where('language', $language)->where('volume', $volume)->where('chapter', $chapter)->order_by('subchapter', 'ASC');

		if (!is_int($subchapter) && $subchapter == 'page')
		{
			$current_page = $team;
		}
		else
		{
			$chaptere->where('subchapter', $subchapter);

			if ($team == 'page')
				$current_page = $joint;
			else
			{
				if ($team != 0)
				{
					$teame = new Team();
					$teame->where('stub', $team)->get();
					$chaptere->where('team_id', $teame->id);
				}

				if ($joint == 'page')
					$current_page = $pagetext;

				if ($joint != 0)
				{
					$chaptere->where('joint_id', $joint);
				}
			}
		}

		if (!isset($current_page))
		{
			if ($page != 1)
				$current_page = $page;
			else
				$current_page = 1;
		}

		$chaptere->get();
		if ($chaptere->result_count() == 0)
		{
			show_404();
		}


		$pages = $chaptere->get_pages();
		foreach ($pages as $page)
			unset($page["object"]);
		$next_chapter = $chaptere->next();

		if ($current_page > count($pages))
			redirect($next_chapter);
		if ($current_page < 1)
			$current_page = 1;

		$chapters = new Chapter();
		$chapters->where('comic_id', $comice->id)->order_by('volume', 'desc')->order_by('chapter', 'desc')->order_by('subchapter', 'desc')->get_bulk();

		$comics = new Comic();
		$comics->order_by('name', 'ASC')->limit(100)->get();

		$this->template->set('is_reader', TRUE);
		$this->template->set('comic', $comice);
		$this->template->set('chapter', $chaptere);
		$this->template->set('chapters', $chapters);
		$this->template->set('comics', $comics);
		$this->template->set('current_page', $current_page);
		$this->template->set('pages', $pages);
		$this->template->set('next_chapter', $next_chapter);
		$this->template->title($comice->name, _('Chapter') . ' ' . $chaptere->chapter, get_setting('fs_gen_site_title'));
		$this->template->build('read');
	}


	public function download($comic, $language = 'en', $volume = 0, $chapter = "", $subchapter = 0, $team = 0, $joint = 0, $pagetext = 'page', $page = 1)
	{
		if (!get_setting('fs_dl_enabled'))
			show_404();
		$comice = new Comic();
		$comice->where('stub', $comic)->get();
		if ($comice->result_count() == 0)
		{
			set_notice('warn', 'This comic doesn\'t exist.');
		}

		if ($chapter == "")
		{
			redirect('/reader/comic/' . $comic);
		}

		$chaptere = new Chapter();
		$chaptere->where('comic_id', $comice->id)->where('language', $language)->where('volume', $volume)->where('chapter', $chapter)->order_by('subchapter', 'ASC');

		if (!is_int($subchapter) && $subchapter == 'page')
		{
			$current_page = $team;
		}
		else
		{
			$chaptere->where('subchapter', $subchapter);

			if ($team == 'page')
				$current_page = $joint;
			else
			{
				if ($team != 0)
				{
					$teame = new Team();
					$teame->where('stub', $team)->get();
					$chaptere->where('team_id', $teame->id);
				}

				if ($joint == 'page')
					$current_page = $pagetext;

				if ($joint != 0)
				{
					$chaptere->where('joint_id', $joint);
				}
			}
		}

		if (!isset($current_page))
		{
			if ($page != 1)
				$current_page = $page;
			else
				$current_page = 1;
		}

		$chaptere->get();
		if ($chaptere->result_count() == 0)
		{
			show_404();
		}

		$archive = new Archive();
		$result = $archive->compress($chaptere);
		if ($this->input->is_cli_request())
		{
			echo $result["server_path"].PHP_EOL;
		}
		else
		{
			redirect($result["url"]);
		}
	}


	/**
	 * Replacing comic with serie, for deprecated "comic"...
	 * 
	 * @deprecated 0.7 30/07/2011
	 * @author Woxxy
	 */
	public function comic($stub = NULL)
	{
		redirect('/reader/series/' . $stub);
	}


	/**
	 * Replacing serie with series, for deprecated "serie"...
	 * 
	 * @deprecated 0.7 30/07/2011
	 * @author Woxxy
	 */
	public function serie($stub = NULL)
	{
		redirect('/reader/series/' . $stub);
	}


	public function series($stub = NULL)
	{
		if (is_null($stub))
			show_404();
		$comic = new Comic();
		$comic->where('stub', $stub)->get();
		if ($comic->result_count() < 1)
			show_404();

		$chapters = new Chapter();
		$chapters->where('comic_id', $comic->id)->order_by('volume', 'desc')->order_by('chapter', 'desc')->order_by('subchapter', 'desc')->get_bulk();

		$this->template->set('comic', $comic);
		$this->template->set('chapters', $chapters);
		$this->template->title($comic->name, get_setting('fs_gen_site_title'));
		$this->template->build('comic');
	}


	public function search()
	{
		if (!$this->input->post('search'))
		{
			$this->template->title(_('Search'), get_setting('fs_gen_site_title'));
			$this->template->build('search_pre');
			return TRUE;
		}

		$search = HTMLpurify($this->input->post('search'), 'unallowed');
		$this->template->title(_('Search'));

		$comics = new Comic();
		$comics->ilike('name', $search)->limit(20)->get();
		foreach ($comics->all as $comic)
		{
			$comic->latest_chapter = new Chapter();
			$comic->latest_chapter->where('comic_id', $comic->id)->order_by('created', 'DESC')->limit(1)->get()->get_teams();
		}


		$this->template->set('search', $search);
		$this->template->set('comics', $comics);
		$this->template->build('search');
	}


	public function _remap($method, $params = array())
	{
		if (method_exists($this->RC, $method))
		{
			return call_user_func_array(array($this->RC, $method), $params);
		}
		
		if (method_exists($this, $method))
		{
			return call_user_func_array(array($this, $method), $params);
		}
		show_404();
	}


}