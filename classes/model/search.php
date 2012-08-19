<?php

namespace Foolfuuka\Model;

class SearchException extends \FuelException {}
class SearchRequiresSphinxException extends SearchException {}
class SearchSphinxOfflineException extends SearchException {}
class SearchEmptyResultException extends SearchException {}

class Search extends Board
{
	
	// \Board::forge()->get_search($arguments)->set_page(1)
	
	protected function p_get_search($arguments)
	{
		// prepare
		$this->set_method_fetching('get_search_comments')
			->set_method_counting('get_search_count')
			->set_options(array(
				'args' => $arguments,
				'limit' => 25,
			));

		return $this;
	}

	protected function p_get_search_comments($options = array())
	{	
		\Profiler::mark('Board::get_latest_comments Start');
		extract($this->_options);


		// if image is set, get either media_hash or media_id
		if ($args['image'] && !is_natural($args['image']))
		{
			// this is urlsafe, let's convert it else decode it
			if (mb_strlen($args['image']) < 23)
			{
				$media = \Media::forge_empty();
				$media->media_hash = $args['image'];
				$args['image'] = $media->get_media_hash();
			}
			else
			{
				$args['image'] = rawurldecode($args['image']);
			}

			if(substr($args['image'], -2) != '==')
			{
				$args['image'] .= '==';
			}

			// if board set, grab media_id
			if ($this->_radix !== false)
			{
				try
				{
					$media = \Media::get_by_media_hash($this->_radix, $args['image']);
				}
				catch (MediaNotFoundException $e)
				{
					$this->_comments = array();
					$this->_comments_unsorted = array();
					$this->_total_count = 0;
				}
				
				$args['image'] = $media->media_id;
			}
		}

		// if global or board => use sphinx, else mysql for board only
		// global search requires sphinx
		if ($board === false && ! \Preferences::get('fu.sphinx.global'))
		{
			throw new SearchRequiresSphinxException(__('Sorry, this action requires the SphinxSearch engine.'));	
		}
		else if (($board === FALSE && get_setting('fs_sphinx_global', 0)) || (is_object($board) && $board->sphinx))
		{
			$sphinxql = new Sphinxql();

			// establish connection to sphinx
			$sphinx_server = explode(':', get_setting('fu.sphinx.listen'));
			
			if (!$sphinxql->set_server($sphinx_server[0], $sphinx_server[1]))
			{
				throw new SearchSphinxOfflineException(__('The search backend is currently not online. Try later or contact us in case it\'s offline for too long.'));
			}

			// determine if all boards will be used for search or not
			if ($board === false)
			{
				\Radix::preload(TRUE);
				$indexes = array();

				foreach (\Radix::get_all() as $radix)
				{
					// ignore boards that don't have sphinx enabled
					if (!$radix->sphinx)
					{
						continue;
					}

					$indexes[] = $radix->shortname . '_ancient';
					$indexes[] = $radix->shortname . '_main';
					$indexes[] = $radix->shortname . '_delta';
				}
			}
			else
			{
				$indexes = array(
					$board->shortname . '_ancient',
					$board->shortname . '_main',
					$board->shortname . '_delta'
				);
			}

			// set db->from with indexes loaded
			$query = $sphinxql->select();
			$query->from($indexes);

			// begin filtering search params
			if ($args['text'])
			{
				if (mb_strlen($args['text']) < 1)
				{
					return array();
				}

				$query->match('comment', $args['text'], 'half', TRUE);
			}
			if ($args['subject'])
			{
				$query->match('title', $args['subject'], 'full', TRUE);
			}
			if ($args['username'])
			{
				$query->match('name', $args['username'], 'full', TRUE);
			}
			if ($args['tripcode'])
			{
				$query->match('trip', $args['tripcode'], 'full', TRUE, TRUE);
			}
			if ($args['email'])
			{
				$query->match('email', $args['email'], 'full', TRUE);
			}
			if ($args['filename'])
			{
				$query->match('media_filename', $args['filename'], 'full', TRUE);
			}
			if (\Auth::has_access('see_ip') && $args['poster_ip'])
			{
				$query->where('pip', (int) \Inet::ptod($args['poster_ip']));
			}
			if ($args['image'])
			{
				if($board !== FALSE)
				{
					$query->where('mid', (int) $args['image']);
				}
				else
				{
					$query->match('media_hash', $args['image'], 'full', TRUE, TRUE);
				}
			}
			if ($args['capcode'] == 'admin')
			{
				$query->where('cap', 3);
			}
			if ($args['capcode'] == 'mod')
			{
				$query->where('cap', 2);
			}
			if ($args['capcode'] == 'user')
			{
				$query->where('cap', 1);
			}
			if ($args['deleted'] == 'deleted')
			{
				$query->where('is_deleted', 1);
			}
			if ($args['deleted'] == 'not-deleted')
			{
				$query->where('is_deleted', 0);
			}
			if ($args['ghost'] == 'only')
			{
				$query->where('is_internal', 1);
			}
			if ($args['ghost'] == 'none')
			{
				$query->where('is_internal', 0);
			}
			if ($args['type'] == 'op')
			{
				$query->where('is_op', 1);
			}
			if ($args['type'] == 'posts')
			{
				$query->where('is_op', 0);
			}
			if ($args['filter'] == 'image')
			{
				$query->where('has_image', 0);
			}
			if ($args['filter'] == 'text')
			{
				$query->where('has_image', 1);
			}
			if ($args['start'])
			{
				$query->where('timestamp >=', intval(strtotime($args['start'])));
			}
			if ($args['end'])
			{
				$query->where('timestamp <=', intval(strtotime($args['end'])));
			}
			if ($args['order'] == 'asc')
			{
				$query->order_by('timestamp', 'ASC');
			}
			else
			{
				$query->order_by('timestamp', 'DESC');
			}

			// set sphinx options
			$query->limit($limit, ($page * $limit) - $limit)
				->sphinx_option('max_matches', \Preferences::get('fu.sphinx.max_matches', 5000))
				->sphinx_option('reverse_scan', ($args['order'] == 'asc') ? 0 : 1);

			// send sphinxql to searchd
			$search = $query->execute();

			if (empty($search['matches']))
			{
				throw new SearchEmptyResultException(__('No results found.'));
			}

			// populate array to query for full records
			$sql = array();

			foreach ($search['matches'] as $post => $result)
			{
				$sql[] = '
					(
						SELECT *, ' . $result['board'] . ' AS board
						FROM ' . $this->radix->get_table($this->radix->get_by_id($result['board'])) . '
						' . $this->sql_media_join($this->radix->get_by_id($result['board'])) . '
						' . $this->sql_report_join($this->radix->get_by_id($result['board'])) . '
						WHERE num = ' . $result['num'] . ' AND subnum = ' . $result['subnum'] . '
					)
				';
			}

			// query mysql for full records
			$query = $this->db->query(implode('UNION', $sql) . '
				ORDER BY timestamp ' . (($args['order'] == 'asc') ? 'ASC' : 'DESC') .',
				num ' . (($args['order'] == 'asc') ? 'ASC' : 'DESC'));
			$total = $search['total_found'];
		}
		else /* use mysql as fallback for non-sphinx indexed boards */
		{
			// begin filtering search params
			if ($args['text'] || $args['filename'])
			{
				if (mb_strlen($args['text']) < 1)
				{
					throw new SearchEmptyResultException(__('No results found.'));
				}

				// we're using fulltext fields, we better start from this
				$query = \DB::select()
					->from(\Radix::get_table($board, '_search'), FALSE, FALSE);

				// select that we'll use for the final statement
				$select = 'SELECT ' . $this->radix->get_table($board, '_search') . '.`doc_id`';

				if($args['text'])
				{
					$this->db->where(
						'MATCH (' . $this->radix->get_table($board, '_search') . '.`comment`) AGAINST (' . $this->db->escape(rawurldecode($args['text'])) . ' IN BOOLEAN MODE)',
						NULL,
						FALSE
					);
				}

				if($args['filename'])
				{
					$this->db->where(
						'MATCH (' . $this->radix->get_table($board, '_search') . '.`media_filename`) AGAINST (' . $this->db->escape(rawurldecode($args['filename'])) . ' IN BOOLEAN MODE)',
						NULL,
						FALSE
					);
				}

				$query = $this->db->query($this->db->statement('', NULL, NULL, 'SELECT doc_id'));
				if ($query->num_rows == 0)
				{
					return array('posts' => array(), 'total_found' => 0);
				}

				$docs = array();
				foreach ($query->result() as $rec)
				{
					$docs[] = $rec->doc_id;
				}
			}

			$this->db->start_cache();

			// no need for the fulltext fields
			$this->db->from($this->radix->get_table($board), FALSE, FALSE);

			// select that we'll use for the final statement
			$select = 'SELECT ' . $this->radix->get_table($board) . '.`doc_id`';

			if (isset($docs))
			{
				$this->db->where_in('doc_id', $docs);
			}

			if ($args['subject'])
			{
				$this->db->like('title', rawurldecode($args['subject']));
			}
			if ($args['username'])
			{
				$this->db->like('name', rawurldecode($args['username']));
				$this->db->use_index('name_trip_index');
			}
			if ($args['tripcode'])
			{
				$this->db->like('trip', rawurldecode($args['tripcode']));
				$this->db->use_index('trip_index');
			}
			if ($args['email'])
			{
				$this->db->like('email', rawurldecode($args['email']));
				$this->db->use_index('email_index');
			}
			if ($args['image'])
			{
				$this->db->where('media_id', $args['image']);
				$this->db->use_index('media_id_index');
			}
			if ($this->auth->is_mod_admin() && $args['poster_ip'])
			{
				$this->db->where('poster_ip', (int) inet_ptod($args['poster_ip']));
			}
			if ($args['capcode'] == 'admin')
			{
				$this->db->where('capcode', 'A');
			}
			if ($args['capcode'] == 'mod')
			{
				$this->db->where('capcode', 'M');
			}
			if ($args['capcode'] == 'user')
			{
				$this->db->where('capcode !=', 'A');
				$this->db->where('capcode !=', 'M');
			}
			if ($args['deleted'] == 'deleted')
			{
				$this->db->where('deleted', 1);
			}
			if ($args['deleted'] == 'not-deleted')
			{
				$this->db->where('deleted', 0);
			}
			if ($args['ghost'] == 'only')
			{
				$this->db->where('subnum <>', 0);
				$this->db->use_index('subnum_index');
			}
			if ($args['ghost'] == 'none')
			{
				$this->db->where('subnum', 0);
				$this->db->use_index('subnum_index');
			}
			if ($args['type'] == 'op')
			{
				$this->db->where('op', 1);
				$this->db->use_index('op_index');
			}
			if ($args['type'] == 'posts')
			{
				$this->db->where('op', 0);
				$this->db->use_index('op_index');
			}
			if ($args['filter'] == 'image')
			{
				$this->db->where('media_id', 0);
				$this->db->use_index('media_id_index');
			}
			if ($args['filter'] == 'text')
			{
				$this->db->where('media_id <>', 0);
				$this->db->use_index('media_id_index');
			}
			if ($args['start'])
			{
				$this->db->where('timestamp >=', intval(strtotime($args['start'])));
				$this->db->use_index('timestamp_index');
			}
			if ($args['end'])
			{
				$this->db->where('timestamp <=', intval(strtotime($args['end'])));
				$this->db->use_index('timestamp_index');
			}

			$this->db->stop_cache();

			// fetch initial total first...
			$this->db->limit(5000);

			// get directly the count for speed
			$count_res = $this->db->query($this->db->statement('', NULL, NULL, 'SELECT COUNT(*) AS count'));
			$total = $count_res->row()->count;

			if (!$total)
			{
				return array('posts' => array(), 'total_found' => 0);
			}

			// now grab those results in order
			$this->db->limit($limit, ($args['page'] * $limit) - $limit);

			$this->db->order_by('timestamp', ($args['order'] == 'asc'?'ASC':'DESC'));

			// get doc_ids, last parameter is the select
			$doc_ids_res = $this->db->query($this->db->statement('', NULL, NULL, $select));

			$doc_ids = array();
			$doc_ids_res_arr = $doc_ids_res->result();
			foreach($doc_ids_res_arr as $doc_id)
			{
				// juuust to be extra sure, make force it to be an int
				$doc_ids[] = intval($doc_id->doc_id);
			}

			$this->db->flush_cache();

			$query = $this->db->query('
				SELECT *
				FROM ' . $this->radix->get_table($board) . '
				' . $this->sql_media_join($board) . '
				' . $this->sql_report_join($board) . '
				WHERE doc_id IN (' . implode(', ', $doc_ids) . ')
				ORDER BY timestamp ' . ($args['order'] == 'asc'?'ASC':'DESC') . ',
					num ' . (($args['order'] == 'asc') ? 'ASC' : 'DESC') . '
				LIMIT ?, ?
			', array(($args['page'] * $limit) - $limit, $limit));

			// query mysql for full records
			//$query = $this->db->query($this->db->statement());
			$total = $doc_ids_res->num_rows();
		}

		// process all results to be displayed
		$results = array();

		$this->populate_posts_arr($query->result());

		foreach ($query->result() as $post)
		{
			// override board with full board information
			if (isset($post->board))
			{
				$post->board = $this->radix->get_by_id($post->board);
				$board = $post->board;
			}

			// populate posts_arr array
			$this->populate_posts_arr($post);

			if ($process === TRUE)
			{
				$this->process_post($board, $post, $clean);
			}

			$results[0]['posts'][] = $post;
		}

		return array('posts' => $results, 'total_found' => $total);
	}
	
}