<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Feeds extends Public_Controller
{
	function __construct()
	{
		parent::__construct();
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




}