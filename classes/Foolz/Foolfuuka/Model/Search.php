<?php

namespace Foolz\Foolfuuka\Model;

use Foolz\Sphinxql\Sphinxql;

class SearchException extends \FuelException {}
class SearchRequiresSphinxException extends SearchException {}
class SearchSphinxOfflineException extends SearchException {}
class SearchInvalidException extends SearchException {}
class SearchEmptyResultException extends SearchException {}

class Search extends Board
{

	public static function structure()
	{
		return array(
			array(
				'type' => 'input',
				'label' => __('Comment'),
				'name' => 'text'
			),
			array(
				'type' => 'input',
				'label' => __('Subject'),
				'name' => 'subject'
			),
			array(
				'type' => 'input',
				'label' => __('Username'),
				'name' => 'username'
			),
			array(
				'type' => 'input',
				'label' => __('Tripcode'),
				'name' => 'tripcode'
			),
			array(
				'type' => 'input',
				'label' => __('Email'),
				'name' => 'email'
			),
			array(
				'type' => 'input',
				'label' => __('Filename'),
				'name' => 'filename'
			),
			array(
				'type' => 'input',
				'label' => __('Image hash'),
				'placeholder' => __('Drop your image here'),
				'name' => 'image'
			),
			array(
				'type' => 'date',
				'label' => __('Date Start'),
				'name' => 'start',
				'placeholder' => 'YYYY-MM-DD'
			),
			array(
				'type' => 'date',
				'label' => __('Date End'),
				'name' => 'end',
				'placeholder' => 'YYYY-MM-DD'
			),
			array(
				'type' => 'input',
				'label' => __('Poster IP'),
				'name' => 'poster_ip',
				'access' => 'comment.see_ip'
			),

			array(
				'type' => 'radio',
				'label' => __('Deleted posts'),
				'name' => 'deleted',
				'elements' => array(
					array('value' => false, 'text' => __('All')),
					array('value' => 'deleted', 'text' => __('Only Deleted Posts')),
					array('value' => 'not-deleted', 'text' => __('Only Non-Deleted Posts'))
				)
			),
			array(
				'type' => 'radio',
				'label' => __('Ghost posts'),
				'name' => 'ghost',
				'elements' => array(
					array('value' => false, 'text' => __('All')),
					array('value' => 'only', 'text' => __('Only Ghost Posts')),
					array('value' => 'none', 'text' => __('Only Non-Ghost Posts'))
				)
			),
			array(
				'type' => 'radio',
				'label' => __('Show posts'),
				'name' => 'filter',
				'elements' => array(
					array('value' => false, 'text' => __('All')),
					array('value' => 'text', 'text' => __('Only With Images')),
					array('value' => 'image', 'text' => __('Only Without Images'))
				)
			),
			array(
				'type' => 'radio',
				'label' => __('Results'),
				'name' => 'type',
				'elements' => array(
					array('value' => false, 'text' => __('All')),
					array('value' => 'op', 'text' => __('Only Opening Posts')),
					array('value' => 'posts', 'text' => __('Only Reply Posts'))
				)
			),
			array(
				'type' => 'radio',
				'label' => __('Capcode'),
				'name' => 'capcode',
				'elements' => array(
					array('value' => false, 'text' => __('All')),
					array('value' => 'user', 'text' => __('Only User Posts')),
					array('value' => 'mod', 'text' => __('Only Moderator Posts')),
					array('value' => 'admin', 'text' => __('Only Admin Posts')),
					array('value' => 'dev', 'text' => __('Only Developer Posts'))
				)
			),
			array(
				'type' => 'radio',
				'label' => __('Order'),
				'name' => 'order',
				'elements' => array(
					array('value' => false, 'text' => __('Latest Posts First')),
					array('value' => 'asc', 'text' => __('Oldest Posts First'))
				)
			)
		);
	}

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
		\Profiler::mark('Board::get_search_comments Start');
		extract($this->_options);

		$available_fields = array('boards', 'subject', 'text', 'username', 'tripcode', 'email', 'filename', 'capcode',
			'image', 'deleted', 'ghost', 'type', 'filter', 'start', 'end', 'order', 'poster_ip'
		);

		foreach ($available_fields as $field)
		{
			if ( ! isset($args[$field]))
			{
				$args[$field] = null;
			}
		}

		$boards = array();
		if ($args['boards'] !== null)
		{
			\Radix::preload(TRUE);
			foreach ($args['boards'] as $board)
			{
				$b = \Radix::getByShortname($board);
				if ($b)
				{
					$boards[] = $b;
				}
			}
		}

		if (empty($boards))
		{
			\Radix::preload(TRUE);
			$boards = \Radix::getAll();
		}

		// if image is set, get either media_hash or media_id
		if ($args['image'] !== null)
		{
			if(substr($args['image'], -2) !== '==')
			{
				$args['image'] .= '==';
			}

			// if board set, grab media_id
			if ($this->_radix !== null)
			{
				try
				{
					$media = \Media::get_by_media_hash($this->_radix, $args['image']);
				}
				catch (MediaNotFoundException $e)
				{
					$this->_comments_unsorted = array();
					$this->_comments = array();
					\Profiler::mark('Board::get_search_comments End Prematurely');
					throw new SearchEmptyResultException(__('No results found.'));
				}

				$args['image'] = $media->media_id;
			}
		}

		// if global or board => use sphinx, else mysql for board only
		// global search requires sphinx
		if ($this->_radix === null && ! \Preferences::get('fu.sphinx.global'))
		{
			throw new SearchRequiresSphinxException(__('Sorry, this action requires the SphinxSearch engine.'));
		}
		else if (($this->_radix !== null && $this->_radix->sphinx) || ($this->_radix === null && \Preferences::get('fu.sphinx.global')))
		{
			// establish connection to sphinx
			$sphinx_server = explode(':', \Preferences::get('fu.sphinx.listen'));
			Sphinxql::addConnection('default', $sphinx_server[0], $sphinx_server[1]);

			try
			{
				// with suppress error
				Sphinxql::connect(true);
			}
			catch (\Foolz\Sphinxql\SphinxqlConnectionException $e)
			{
				throw new SearchSphinxOfflineException(__('The search backend is currently not online. Try later or contact us in case it\'s offline for too long.'));
			}

			// determine if all boards will be used for search or not
			if ($this->_radix === null)
			{
				$indexes = array();
				foreach ($boards as $radix)
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
					$this->_radix->shortname . '_ancient',
					$this->_radix->shortname . '_main',
					$this->_radix->shortname . '_delta'
				);
			}

			// set db->from with indexes loaded
			$query = Sphinxql::select('id', 'board');
			$query->from($indexes);

			// begin filtering search params
			if ($args['text'])
			{
				if (mb_strlen($args['text']) < 1)
				{
					return array();
				}

				$query->match('comment', $args['text'], true);
			}
			if ($args['subject'])
			{
				$query->match('title', $args['subject']);
			}
			if ($args['username'])
			{
				$query->match('name', $args['username']);
			}
			if ($args['tripcode'])
			{
				$query->match('trip', '"'.$args['tripcode'].'"');
			}
			if ($args['email'])
			{
				$query->match('email', $args['email']);
			}
			if ($args['filename'])
			{
				$query->match('media_filename', $args['filename']);
			}
			if (\Auth::has_access('comment.see_ip') && $args['poster_ip'])
			{
				$query->where('pip', (int) \Inet::ptod($args['poster_ip']));
			}
			if ($args['image'])
			{
				if($this->_radix !== null)
				{
					$query->where('mid', (int) $args['image']);
				}
				else
				{
					$query->match('media_hash', '"'.$args['image'].'"');
				}
			}
			if ($args['capcode'] !== null)
			{
				if ($args['capcode'] === 'user')
				{
					$query->where('cap', ord('N'));
				}
				else if ($args['capcode'] === 'mod')
				{
					$query->where('cap', ord('M'));
				}
				else if ($args['capcode'] === 'admin')
				{
					$query->where('cap', ord('A'));
				}
				else if ($args['capcode'] === 'dev')
				{
					$query->where('cap', ord('D'));
				}
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
				$query->where('timestamp', '>=', intval(strtotime($args['start'])));
			}
			if ($args['end'])
			{
				$query->where('timestamp', '<=', intval(strtotime($args['end'])));
			}
			if ($args['order'] == 'asc')
			{
				$query->orderBy('timestamp', 'ASC');
			}
			else
			{
				$query->orderBy('timestamp', 'DESC');
			}

			// set sphinx options
			$query->limit($limit)
				->offset((($page * $limit) - $limit) >= 5000 ? 4999 : ($page * $limit) - $limit)
				->option('max_matches', \Preferences::get('fu.sphinx.max_matches', 5000))
				->option('reverse_scan', ($args['order'] == 'asc') ? 0 : 1);

			// send sphinxql to searchd
			try
			{
				$search = $query->execute();
			}
			catch (\Foolz\Sphinxql\SphinxqlDatabaseException $e)
			{
				\Log::error('It looks like the user used a bad search string: '.$e->getMessage());
				throw new SearchInvalidException(__('The order of the allowed special characters produced a bad query. Try wrapping your query in double quotes.'));
			}

			if ( ! count($search))
			{
				$this->_comments_unsorted = array();
				$this->_comments = array();
				throw new SearchEmptyResultException(__('No results found.'));
			}

			$meta = Sphinxql::meta();
			$this->_total_count = $meta['total'];

			// populate array to query for full records
			$sql = array();

			foreach ($search as $post => $result)
			{
				$board = \Radix::getById($result['board']);
				$sub = \DB::select('*', \DB::expr($result['board'] . ' AS board_id'))
					->from(\DB::expr($board->getTable()));
				static::sql_media_join($sub, $board);
				static::sql_extra_join($sub, $board);
				$sql[] = '('.$sub->where('doc_id', '=', $result['id']).')';
			}

			// query mysql for full records
			$result = \DB::query(implode(' UNION ', $sql) . '
					ORDER BY timestamp ' . (($args['order'] == 'asc') ? 'ASC' : 'DESC') .',
					num ' . (($args['order'] == 'asc') ? 'ASC' : 'DESC'), \DB::SELECT)
				->as_object()
				->execute()
				->as_array();

		}
		else /* use mysql as fallback for non-sphinx indexed boards */
		{
			// sadly like escaping has no love in fuelphp...
			$like_escape = function($s, $e)
			{
				$s = str_replace(array($e, '_', '%'), array($e.$e, $e.'_', $e.'%'), $s);
				return \DB::expr(\DB::escape("%".$s."%")." ESCAPE '".$e."'");
			};

			// begin filtering search params
			if ($args["text"] || $args['filename'])
			{
				// we're using fulltext fields, we better start from this
				$query = \DB::select(\DB::expr($this->_radix->getTable('_search').'.`doc_id`'))
					->from(\DB::expr($this->_radix->getTable('_search')));

				if($args['text'])
				{
					$query->where(
						\DB::expr('MATCH ('.$this->_radix->getTable('_search').'.`comment`) '.
							'AGAINST ('.\DB::escape($args['text']).' IN BOOLEAN MODE)'),
						\DB::expr(''), \DB::expr(''));
				}

				if($args['filename'])
				{
					$query->where(\DB::expr(
						'MATCH ('.$this->_radix->getTable('_search').'.`media_filename`) '.
							'AGAINST ('.\DB::escape($args['filename']).' IN BOOLEAN MODE)'),
						\DB::expr(''), \DB::expr(''));
				}

				$query->limit(5000);

				$result = $query->as_object()
					->execute()
					->as_array();

				if ( ! count($result))
				{
					$this->_comments_unsorted = array();
					$this->_comments = array();
					throw new SearchEmptyResultException(__('No results found.'));
				}

				$docs = array();
				foreach ($result as $rec)
				{
					$docs[] = $rec->doc_id;
				}
			}

			foreach (array('*', 'COUNT(*) as count') as $select_key => $select)
			{
				$query = \DB::select(\DB::expr($select))
					->from(\DB::expr($this->_radix->getTable()));

				static::sql_media_join($query, $this->_radix);
				static::sql_extra_join($query, $this->_radix);

				if (isset($docs))
				{
					$query->where('doc_id', 'IN', $docs);
				}

				if ($args['subject'])
				{
					$query->where('title', 'like', $like_escape($args['subject'], '='));
				}
				if ($args['username'])
				{
					$query->where('name', 'like', $like_escape($args['username'], '='));
					//$this->db->use_index('name_trip_index');
				}
				if ($args['tripcode'])
				{
					$query->where('trip', 'like', $like_escape($args['tripcode'], '='));
					//$this->db->use_index('trip_index');
				}
				if ($args['email'])
				{
					$query->where('email', 'like', $like_escape($args['email'], '='));
					//$this->db->use_index('email_index');
				}
				if ($args['image'])
				{
					$query->where('media_id', '=', $args['image']);
					//$this->db->use_index('media_id_index');
				}
				if (\Auth::has_access('comment.see_ip') && $args['poster_ip'])
				{
					$query->where('poster_ip', '=', (int) \Inet::ptod($args['poster_ip']));
				}
				if ($args['capcode'] == 'admin')
				{
					$query->where('capcode', '=', 'A');
				}
				if ($args['capcode'] == 'mod')
				{
					$query->where('capcode', '=', 'M');
				}
				if ($args['capcode'] == 'dev')
				{
					$query->where('capcode', '=', 'D');
				}
				if ($args['capcode'] == 'user')
				{
					$query->where('capcode', '<>', 'A');
					$query->where('capcode', '<>', 'M');
					$query->where('capcode', '<>', 'D');
				}
				if ($args['deleted'] == 'deleted')
				{
					$query->where('deleted', '=', 1);
				}
				if ($args['deleted'] == 'not-deleted')
				{
					$query->where('deleted', '=', 0);
				}
				if ($args['ghost'] == 'only')
				{
					$query->where('subnum', '<>', 0);
					//$this->db->use_index('subnum_index');
				}
				if ($args['ghost'] == 'none')
				{
					$query->where('subnum', '=', 0);
					//$this->db->use_index('subnum_index');
				}
				if ($args['type'] == 'op')
				{
					$query->where('op', '=', 1);
					//$this->db->use_index('op_index');
				}
				if ($args['type'] == 'posts')
				{
					$query->where('op', '=', 0);
					//$this->db->use_index('op_index');
				}
				if ($args['filter'] == 'image')
				{
					// gotta use the expr else it will be confused on which media_id to use
					$query->where(\DB::expr($this->_radix->getTable().'.media_id'), '=', 0);
					//$this->db->use_index('media_id_index');
				}
				if ($args['filter'] == 'text')
				{
					$query->where(\DB::expr($this->_radix->getTable().'.media_id'), '<>', 0);
					//$this->db->use_index('media_id_index');
				}
				if ($args['start'])
				{
					$query->where('timestamp', '>=', (int) strtotime($args['start']));
					//$this->db->use_index('timestamp_index');
				}
				if ($args['end'])
				{
					$query->where('timestamp', '<=', (int) strtotime($args['end']));
					//$this->db->use_index('timestamp_index');
				}

				if ( ! $select_key) // we're getting the actual result, not the count
				{
					$result_which = $query->limit($limit)
						->offset((($page * $limit) - $limit) > 5000 ? 5000 : ($page * $limit) - $limit)
						->order_by('timestamp', ($args['order'] === 'asc' ? 'ASC' : 'DESC'))
						->as_object()
						->execute()
						->as_array();

					if ( ! count($result_which))
					{
						$this->_comments_unsorted = array();
						$this->_comments = array();
						throw new SearchEmptyResultException(__('No results found.'));
					}

					$result = $result_which;
				}
				else
				{
					$this->_total_count = $query->as_object()
						->execute()
						->current()
						->count;
				}
			}
		}

		foreach ($result as $item)
		{
			$board = $this->_radix !== null ? $this->_radix : \Radix::getById($item->board_id);
			$this->_comments_unsorted[] = \Comment::forge($item, $board);
		}

		$this->_comments[0]['posts'] = $this->_comments_unsorted;

		return $this;
	}


	public function get_search_count()
	{
		$this->get_comments();
	}

}