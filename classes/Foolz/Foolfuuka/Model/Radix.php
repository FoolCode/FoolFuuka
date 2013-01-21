<?php

namespace Foolz\Foolfuuka\Model;

use \Foolz\Foolframe\Model\DoctrineConnection as DC;
use \Foolz\Config\Config;
use \Foolz\Cache\Cache;

class Radix
{
	use \Foolz\Plugin\PlugSuit;

	/**
	 * An array of all the preloaded boards
	 *
	 * @var  null|array
	 */
	private static $preloaded_radixes = null;

	/**
	 * The currently selected radix to use with get_selected_radix()
	 *
	 * @var  \Foolz\Foolfuuka\Model\Radix
	 */
	private static $selected_radix = null;

	/**
	 * The structure of the radix table to be used with validation and form creator
	 *
	 * @param  \Foolz\Foolfuuka\Model\Radix|null  $radix  If available insert to customize the structure
	 *
	 * @return  array  The structure
	 */
	public static function structure($radix = null)
	{
		$structure = [
			'open' => ['type' => 'open'],
			'id' => [
				'type' => 'hidden',
				'database' => true,
				'validation_func' => function($input, $form_internal)
				{
					// check that the ID exists
					$row = DC::qb()
						->select('COUNT(*) as count')
						->from(DC::p('boards'), 'b')
						->where('id = :id')
						->setParameter(':id', $input['id'])
						->execute()
						->fetch();

					if ($row['count'] != 1)
					{
						return [
							'error_code' => 'ID_NOT_FOUND',
							'error' => __('Couldn\'t find the board with the submitted ID.'),
							'critical' => true
						];
					}

					return ['success' => true];
				}
			],
			'name' => [
				'database' => true,
				'type' => 'input',
				'label' => __('Name'),
				'help' => __('Insert the name of the board normally shown as title.'),
				'placeholder' => __('Required'),
				'class' => 'span3',
				'validation' => 'required|max_length[128]'
			],
			'shortname' => [
				'database' => true,
				'type' => 'input',
				'label' => __('Shortname'),
				'help' => __('Insert the shorter name of the board. Reserved: "admin".'),
				'placeholder' => __('Req.'),
				'class' => 'span1',
				'validation' => 'required|max_length[5]|valid_string[alpha,dashes,numeric]',
				'validation_func' => function($input, $form_internal)
				{
					// if we're working on the same object
					if (isset($input['id']))
					{
						// existence ensured by CRITICAL in the ID check
						$row = DC::qb()
							->select('shortname')
							->from(DC::p('boards'), 'b')
							->where('id = :id')
							->setParameter(':id', $input['id'])
							->execute()
							->fetch();

						if ($row === false)
						{
							return [
								'error_code' => 'ID_NOT_FOUND',
								'error' => __('Couldn\'t find the board with the submitted ID.')
							];
						}

						// no change?
						if ($input['shortname'] === $row['shortname'])
						{
							// no change
							return ['success' => true];
						}
					}

					$row = DC::qb()
						->select('shortname')
						->from(DC::p('boards'), 'r')
						->where('shortname = :s')
						->setParameter(':s', $input['shortname'])
						->execute()
						->fetch();

					// check that there isn't already a board with that name
					if ($row !== false)
					{
						return [
							'error_code' => 'ALREADY_EXISTS',
							'error' => __('The shortname is already used for another board.')
						];
					}
				}
			],
			'rules' => [
				'database' => true,
				'boards_preferences' => true,
				'type' => 'textarea',
				'label' => __('General rules'),
				'help' => __('Full board rules displayed in a separate page, in <a href="http://daringfireball.net/projects/markdown/basics" target="_blank">MarkDown</a> syntax. Will not display if left empty.'),
				'class' => 'span6',
				'placeholder' => __('MarkDown goes here')
			],
			'separator-3' => [
				'type' => 'separator'
			],
			'posting_rules' => [
				'database' => true,
				'boards_preferences' => true,
				'type' => 'textarea',
				'label' => __('Posting rules'),
				'help' => __('Posting rules displayed in the posting area, in <a href="http://daringfireball.net/projects/markdown/basics" target="_blank">MarkDown</a> syntax. Will not display if left empty.'),
				'class' => 'span6',
				'placeholder' => __('MarkDown goes here')
			],
			'separator-1' => ['type' => 'separator'],
			'threads_per_page' => [
				'database' => true,
				'boards_preferences' => true,
				'label' => __('Maximum number of threads to display in the index pages'),
				'type' => 'input',
				'class' => 'span1',
				'validation' => 'trim|required|valid_string[numeric]',
			],
			'archive' => [
				'database' => true,
				'type' => 'checkbox',
				'help' => __('Is this a 4chan archiving board?'),
				'sub' => [
					'paragraph' => [
						'type' => 'paragraph',
						'help' => __('Options for archive boards')
					],
					'board_url' => [
						'database' => true,
						'boards_preferences' => true,
						'type' => 'input',
						'label' => __('URL to the 4chan board (facultative)'),
						'placeholder' => 'http://boards.4chan.org/'.(is_object($radix) ? $radix->shortname : 'shortname').'/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					],
					'thumbs_url' => [
						'database' => true,
						'boards_preferences' => true,
						'type' => 'input',
						'label' => __('URL to the board thumbnails (facultative)'),
						'placeholder' => 'http://0.thumbs.4chan.org/'.(is_object($radix) ? $radix->shortname : 'shortname').'/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					],
					'images_url' => [
						'database' => true,
						'boards_preferences' => true,
						'type' => 'input',
						'label' => __('URL to the board images (facultative)'),
						'placeholder' => 'http://images.4chan.org/'.(is_object($radix) ? $radix->shortname : 'shortname').'/',
						'class' => 'span4',
						'validation' => 'trim|max_length[256]'
					],
					'media_threads' => [
						'database' => true,
						'boards_preferences' => true,
						'type' => 'input',
						'label' => __('Image fetching workers'),
						'help' => __('The number of workers that will fetch full images. Set to zero not to fetch them.'),
						'placeholder' => 5,
						'value' => 0,
						'class' => 'span1',
						'validation' => 'trim|valid_string[numeric]|numeric_max[32]'
					],
					'thumb_threads' => [
						'database' => true,
						'boards_preferences' => true,
						'type' => 'input',
						'label' => __('Thumbnail fetching workers'),
						'help' => __('The number of workers that will fetch thumbnails'),
						'placeholder' => 5,
						'value' => 5,
						'class' => 'span1',
						'validation' => 'trim|valid_string[numeric]|numeric_max[32]'
					],
					'new_threads_threads' => [
						'database' => true,
						'boards_preferences' => true,
						'type' => 'input',
						'label' => __('Thread fetching workers'),
						'help' => __('The number of workers that fetch new threads'),
						'placeholder' => 5,
						'value' => 5,
						'class' => 'span1',
						'validation' => 'trim|valid_string[numeric]|numeric_max[32]'
					],
					'thread_refresh_rate' => [
						'database' => true,
						'boards_preferences' => true,
						'type' => 'hidden',
						'value' => 3,
						'label' => __('Minutes to refresh the thread'),
						'placeholder' => 3,
						'validation' => 'trim|valid_string[numeric]|numeric_max[32]'
					],
					'page_settings' => [
						'database' => true,
						'boards_preferences' => true,
						'type' => 'textarea',
						'label' => __('Thread refresh rate'),
						'help' => __('Array of refresh rates in seconds per page in JSON format'),
						'placeholder' => htmlspecialchars('[{'delay': 30, 'pages': [0, 1, 2]},'.
							'{'delay': 120, 'pages': [3, 4, 5, 6, 7, 8, 9, 10, 11, 12]},'.
							'{'delay': 30, 'pages': [13, 14, 15]}]'),
						'class' => 'span4',
						'style' => 'height:70px;',
						'validation_func' => function($input, $form_internal)
						{
							if ($input['page_settings'] === '')
							{
								return true;
							}

							$json = @json_decode($input['page_settings']);
							if (is_null($json))
							{
								return [
									'error_code' => 'NOT_JSON',
									'error' => __('The JSON inputted is not valid.')
								];
							}
						}
					]
				],
				'sub_inverse' => [
					'paragraph' => [
						'type' => 'paragraph',
						'help' => __('Options for normal boards')
					],
					'thumbnail_op_width' => [
						'database' => true,
						'boards_preferences' => true,
						'label' => __('Opening post thumbnail maximum width after resizing'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|valid_string[numeric]|numeric_min[25]',
					],
					'thumbnail_op_height' => [
						'database' => true,
						'boards_preferences' => true,
						'label' => __('Opening post thumbnail maximum height after resizing'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|valid_string[numeric]|numeric_min[25]',
					],
					'thumbnail_reply_width' => [
						'database' => true,
						'boards_preferences' => true,
						'label' => __('Reply thumbnail maximum width after resizing'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|valid_string[numeric]|numeric_min[25]',
					],
					'thumbnail_reply_height' => [
						'database' => true,
						'boards_preferences' => true,
						'label' => __('Reply thumbnail maximum height after resizing'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|valid_string[numeric]|numeric_min[25]',
					],
					'max_image_size_kilobytes' => [
						'database' => true,
						'boards_preferences' => true,
						'label' => __('Full image maximum size in kilobytes'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|valid_string[numeric]|numeric_min[25]',
					],
					'max_image_size_width' => [
						'database' => true,
						'boards_preferences' => true,
						'label' => __('Full image maximum width in pixels'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|valid_string[numeric]|numeric_min[25]',
					],
					'max_image_size_height' => [
						'database' => true,
						'boards_preferences' => true,
						'label' => __('Full image maximum height in pixels'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|valid_string[numeric]|numeric_min[25]',
					],
					'max_posts_count' => [
						'database' => true,
						'boards_preferences' => true,
						'label' => __('The maximum amount of posts before a thread 'dies''),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|valid_string[numeric]',
					],
					'max_images_count' => [
						'database' => true,
						'boards_preferences' => true,
						'label' => __('The maximum amount of images in replies before posting more is prohibited'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|valid_string[numeric]',
					],
					'min_image_repost_time' => [
						'database' => true,
						'boards_preferences' => true,
						'label' => __('The minimum time in seconds to repost the same image (0 means no limit, -1 means never allowing a repost)'),
						'type' => 'input',
						'class' => 'span1',
						'validation' => 'trim|required|numeric_min[-2]',
					]
				]
			],
			'anonymous_default_name' => [
				'database' => true,
				'boards_preferences' => true,
				'label' => __('The default name when an user doesn\'t enter a name'),
				'type' => 'input',
				'class' => 'span3',
				'validation' => 'trim|required',
			],
			'transparent_spoiler' => [
				'database' => true,
				'boards_preferences' => true,
				'help' => __('Should the image spoilers be semi-transparent? (mods and admins have it always on for moderation)'),
				'type' => 'checkbox',
			],
			'enable_flags' => [
				'database' => true,
				'boards_preferences' => true,
				'help' => __('Display flags? (needs GeoIP)'),
				'type' => 'checkbox',
			],
			'enable_animated_gif_thumbs' => [
				'database' => true,
				'boards_preferences' => true,
				'help' => __('Enables the CPU-heavy animated gif thumbnail creation'),
				'type' => 'checkbox',
			],
			'display_exif' => [
				'database' => true,
				'boards_preferences' => true,
				'help' => __('Show the EXIF data (EXIF data is saved in the database regardless)'),
				'type' => 'checkbox',
				'disabled' => 'disabled',
			],
			'enable_poster_hash' => [
				'database' => true,
				'boards_preferences' => true,
				'help' => __('Enable poster hashes, an IP-based code to temporarily distinguish Anonymous users'),
				'type' => 'checkbox',
			],
			'disable_ghost' => [
				'database' => true,
				'boards_preferences' => true,
				'help' => __('Don\'t allow ghost posting (disallows infinite replying)'),
				'type' => 'checkbox',
			],
			'hide_thumbnails' => [
				'database' => true,
				'type' => 'checkbox',
				'help' => __('Hide the thumbnails?')
			],
			'sphinx' => [
				'database' => true,
				'type' => 'checkbox',
				'help' => __('Use SphinxSearch as search engine?')
			],
			'hidden' => [
				'database' => true,
				'type' => 'checkbox',
				'help' => __('Hide the board from public access? (only admins and mods will be able to browse it)')
			],
		];

		$structure = \Foolz\Plugin\Hook::forge('fu.radix.structure.structure_alter')
			->setParam('structure', $structure)
			->execute()
			->get($structure);

		$structure = array_merge($structure, [
			'separator-2' => ['type' => 'separator-short'],
			'submit' => [
				'type' => 'submit',
				'class' => 'btn-primary',
				'value' => __('Submit')
			],
			'close' => ['type' => 'close']
		]);

		foreach ($structure as $key => $item)
		{
			$default = Config::get('foolz/foolfuuka', 'package', 'preferences.radix.'.$key);

			if ($default !== null)
			{
				$structure[$key]['default_value'] = $default;
			}

			$subs = ['sub', 'sub_inverse'];

			foreach ($subs as $inv)
			{
				if (isset($item[$inv]))
				{
					foreach ($item[$inv] as $k => $i)
					{
						$default = Config::get('foolz/foolfuuka', 'package', 'preferences.radix.'.$k);

						if (! is_null($default))
						{
							$structure[$key][$inv][$k]['default_value'] = $default;
						}
					}
				}
			}
		}

		return $structure;
	}


	/**
	 * Clears the APC/memcached cache
	 */
	public static function clearCache()
	{
		Cache::item('fu.model.radix.preload')->delete();
		Cache::item('fu.model.radix.load_preferences')->delete();
	}


	/**
	 * Saves the data for a board. Plains the structure, runs the validation.
	 * If 'id' is not set, it creates a new board.
	 *
	 * @param  array  $data  Associative array with the values for the structure
	 */
	public static function save($data)
	{
		// filter _boards data from _boards_preferences data
		$structure = static::structure();
		$data_boards_preferences = [];

		foreach ($structure as $key => $item)
		{
			if ($item['type'] === 'internal')
			{
				// we don't use this function to edit internal preferences
				continue;
			}

			// mix the sub and sub_inverse and flatten the array
			if (isset($item['sub_inverse']) && isset($item['sub']))
			{
				$item['sub'] = array_merge($item['sub'], $item['sub_inverse']);
			}

			if (isset($item['sub']))
			{
				foreach ($item['sub'] as $k => $i)
				{
					if (isset($i['boards_preferences']))
					{
						if (isset($data[$k]))
						{
							$data_boards_preferences[$k] = $data[$k];
						}
						else
						{
							if ($i['type'] === 'checkbox')
							{
								$data_boards_preferences[$k] = false;
							}
							else
							{
								$data_boards_preferences[$k] = null;
							}
						}

						unset($data[$k]);
					}
					elseif (isset($i['database']))
					{
						if (! isset($data[$k]))
						{
							if ($i['type'] === 'checkbox')
							{
								$data[$k] = false;
							}
							else
							{
								$data[$k] = null;
							}
						}
					}
				}
			}

			if (isset($item['boards_preferences']))
			{
				if (isset($data[$key]))
				{
					$data_boards_preferences[$key] = $data[$key];
				}
				else
				{
					if ($item['type'] === 'checkbox')
					{
						$data_boards_preferences[$key] = false;
					}
					else
					{
						$data_boards_preferences[$key] = null;
					}
				}

				unset($data[$key]);
			}
			elseif (isset($item['database']))
			{
				if (! isset($data[$key]))
				{
					if ($item['type'] === 'checkbox')
					{
						$data[$key] = false;
					}
					else
					{
						$data[$key] = null;
					}
				}
			}
		}

		// data must be already sanitized through the form array
		if (isset($data['id']))
		{
			if (! $radix = static::getById($data['id']))
			{
				// @todo proper error
				return;
			}

			DC::forge()->beginTransaction();

			// save normal values
			$update = DC::qb()
				->update(DC::p('boards'));

			foreach ($data as $k => $i)
			{
				$update->set($k, DC::forge()->quote($i));
			}

			$update->where('id = :id')
				->setParameter(':id', $data['id'])
				->execute();

			// save extra preferences
			foreach ($data_boards_preferences as $name => $value)
			{
				static::savePreferences($data['id'], $name, $value);
			}

			DC::forge()->commit();
		}
		else
		{
			DC::forge()->beginTransaction();
			DC::forge()->insert(DC::p('boards'), $data);
			$id = DC::forge()->lastInsertId();


			// save extra preferences
			foreach ($data_boards_preferences as $name => $value)
			{
				static::savePreferences($id, $name, $value);
			}

			static::clearCache();
			static::preload();
			$board = static::getById($id);
			$board->createTables();

			DC::forge()->commit();
		}

		static::clearCache();
		static::preload();
	}


	/**
	 * Insert custom preferences. One must use this for 'internal' preferences
	 *
	 * @param  \Foolz\Foolfuuka\Model\Radix|int  $board_id  can also be the board object
	 * @param  string  $name   The name of the value to insert
	 * @param  mixed   $value  The value to insert
	 */
	public static function savePreferences($board_id, $name, $value)
	{
		if (is_object($board_id))
		{
			$board_id = $board_id->id;
		}

		$result = DC::qb()
			->select('COUNT(*) as count')
			->from(DC::p('boards_preferences'), 'p')
			->where('board_id = :board_id', 'name = :name')
			->setParameter(':board_id', $board_id)
			->setParameter(':name', $name)
			->execute()
			->fetch();

		if ($result['count'])
		{
			DC::qb()
				->update(DC::p('boards_preferences'))
				->set('value', DC::forge()->quote($value))
				->where('board_id = :board_id', 'name = :name')
				->setParameter(':board_id', $board_id)
				->setParameter(':name', $name)
				->execute();
		}
		else
		{
			DC::forge()->insert(DC::p('boards_preferences'), [
				'board_id' => $board_id,
				'name' => $name,
				'value' => $value
			]);
		}

		// only set if object exists
		if (isset(static::$preloaded_radixes[$board_id]))
		{
			// avoid the complete reloading
			static::$preloaded_radixes[$board_id]->$name = $value;
		}

		static::clearCache();
	}


	/**
	 * Removes the board and renames its dir with a _removed suffix and with a number
	 * in case of collision
	 */
	public function remove()
	{
		// always remove the triggers first
		DC::forge()->beginTransaction();
		DC::qb()
			->delete(DC::p('boards_preferences'))
			->where('board_id = :id')
			->setParameter(':id', $this->id)
			->execute();

		DC::qb()
			->delete(DC::p('boards'))
			->where('id = :id')
			->setParameter(':id', $this->id)
			->execute();

		// rename the directory and prevent directory collision
		$base =	\Preferences::get('fu.boards.directory').'/'.$this->shortname;
		if (file_exists($base.'_removed'))
		{
			$incremented = \String::increment('_removed');
			while (file_exists($base.$incremented))
			{
				$incremented = \String::increment($incremented);
			}

			$rename_to = $base.$incremented;
		}
		else
		{
			$rename_to = \Preferences::get('fu.boards.directory').'/'.$this->shortname.'_removed';
		}

		rename($base, $rename_to);

		// for huge boards, this may time out with PHP, while MySQL will keep going
		$this->removeTables();
		DC::forge()->commit();
		static::clearCache();
	}


	/**
	 * Maintenance function to remove leftover _removed folders
	 *
	 * @param   boolean  $echo  echo CLI output
	 *
	 * @return  boolean  true on success, false on failure
	 */
	public static function removeLeftoverDirs($echo = false)
	{
		$all = static::getAll();

		$array = [];

		// get all directories
		if ($handle = opendir(\Preferences::get('fu.boards.directory')))
		{
			while (false !== ($file = readdir($handle)))
			{
				if (in_array($file, ['..', '.']))
					continue;

				if (is_dir(\Preferences::get('fu.boards.directory').'/'.$file))
				{
					$array[] = $file;
				}
			}
			closedir($handle);
		}
		else
		{
			return false;
		}

		// make sure it's a removed folder
		foreach ($array as $key => $dir)
		{
			if (strpos($dir, '_removed') === false)
			{
				unset($array[$key]);
			}

			foreach ($all as $a)
			{
				if ($a->shortname === $dir)
				{
					unset($array[$key]);
				}
			}
		}

		// exec the deletion
		foreach ($array as $dir)
		{
			$cmd = 'rm -Rv '.\Preferences::get('fu.boards.directory').'/'.$dir;
			if ($echo)
			{
				echo $cmd.PHP_EOL;
				passthru($cmd);
				echo PHP_EOL;
			}
			else
			{
				exec($cmd).PHP_EOL;
			}
		}

		return true;
	}


	/**
	 * Puts the table in readily available variables
	 */
	protected static function preload()
	{
		\Profiler::mark('Radix::preload Start');

		try
		{
			$result = Cache::item('fu.model.radix.preload')->get();
		}
		catch (\OutOfBoundsException $e)
		{
			$result = DC::qb()
				->select('*')
				->from(DC::p('boards'), 'b')
				->orderBy('shortname', 'ASC')
				->execute()
				->fetchAll();

			Cache::item('fu.model.radix.preload')->set($result, 900);
		}

		if (! is_array($result) || empty($result))
		{
			static::$preloaded_radixes = [];
			return false;
		}

		foreach ($result as $item)
		{
			$structure = static::structure($item);

			$result_object[$item['id']] = new static();

			foreach ($item as $k => $i)
			{
				$result_object[$item['id']]->$k = $i;
			}

			$result_object[$item['id']]->formatted_title = ($item['name']) ?
				'/'.$item['shortname'].'/ - '.$item['name'] : '/'.$item['shortname'].'/';

			$result_object[$item['id']]->href = \Uri::create($item['shortname']);

			// load the basic value of the preferences
			foreach ($structure as $key => $arr)
			{
				if (! isset($result_object[$item['id']]->$key) && isset($arr['boards_preferences']))
				{
					$result_object[$item['id']]->$key = Config::get('foolz/foolfuuka', 'package', 'preferences.radix.'.$key);
				}

				foreach (['sub', 'sub_inverse'] as $sub)
				{
					if (isset($arr[$sub]))
					{
						foreach ($arr[$sub] as $k => $a)
						{
							if ( ! isset($result_object[$item['id']]->$k) && isset($a['boards_preferences']))
							{
								$result_object[$item['id']]->$k = Config::get('foolz/foolfuuka', 'package', 'preferences.radix.'.$k);
							}
						}
					}
				}
			}
		}

		// load the preferences from the board_preferences table
		\Profiler::mark('Radix::load_preferences Start');
		try
		{
			$preferences = Cache::item('fu.model.radix.load_preferences')->get();
		}
		catch (\OutOfBoundsException $e)
		{
			$preferences = DC::qb()
				->select('*')
				->from(DC::p('boards_preferences'), 'p')
				->execute()
				->fetchAll();

			Cache::item('fu.model.radix.load_preferences')->set($preferences, 900);
		}

		foreach ($preferences as $value)
		{
			// in case of leftover values, it would try instantiating a new stdClass and that would trigger error
			if (isset($result_object[$value['board_id']]))
			{
				$result_object[$value['board_id']]->{$value['name']} = $value['value'];
			}
		}

		// unset the hidden boards
		if (! \Auth::has_access('boards.see_hidden'))
		{
			foreach ($result_object as $key => $value)
			{
				if ($value->hidden)
				{
					unset($result_object[$key]);
				}
			}
		}

		static::$preloaded_radixes = $result_object;
		\Profiler::mark_memory(static::$preloaded_radixes, 'Radix static::$preloaded_radixes');
		\Profiler::mark('Radix::load_preferences End');

		// take them all and then filter/do whatever (we use this to split the boards through various subdomains)
		// only public is affected! admins and mods will see all boards at all the time
		static::$preloaded_radixes = \Foolz\Plugin\Hook::forge('fu.radix.preload.public.alter_result')
			->setParam('preloaded_radixes', static::$preloaded_radixes)
			->execute()
			->get(static::$preloaded_radixes);

		\Profiler::mark('Radix::preload End');
		\Profiler::mark_memory(static::$preloaded_radixes, 'Radix static::$preloaded_radixes w/ preferences');
	}

	/**
	 * Set a radix for contiguous use
	 *
	 * @param   string  $shortname  the board shortname
	 *
	 * @return  \Foolz\Foolfuuka\Model\Radix  false on failure, else the board object
	 */
	public static function setSelectedByShortname($shortname)
	{
		if (false != ($val = static::getByShortname($shortname)))
		{
			static::$selected_radix = $val;
			return $val;
		}

		static::$selected_radix = false;

		return false;
	}


	/**
	 * Returns the object of the selected radix
	 *
	 * @return \Foolz\Foolfuuka\Model\Radix  false if not set, else the Radix object
	 */
	public static function getSelected()
	{
		if (static::$selected_radix === null)
		{
			return false;
		}

		return static::$selected_radix;
	}


	/**
	 * Returns all the radixes as array of objects
	 *
	 * @return  \Foolz\Foolfuuka\Model\Radix[]  the objects of the preloaded radixes
	 */
	public static function getAll()
	{
		static::preload();

		return static::$preloaded_radixes;
	}


	/**
	 * Returns the single radix
	 *
	 * @param   int  $radix_id  the ID of the board
	 *
	 * @return  \Foolz\Foolfuuka\Model\Radix  false on failure, else the board object
	 */
	public static function getById($radix_id)
	{
		$items = static::getAll();

		if (isset($items[$radix_id]))
		{
			return $items[$radix_id];
		}

		return false;
	}


	/**
	 * Returns the single radix by type selected
	 *
	 * @param  string   $value   the value searched
	 * @param  string   $type    the variable name on which to match
	 * @param  boolean  $switch  true if it must be equal or false if not equal
	 *
	 * @return  \Foolz\Foolfuuka\Model\Radix  false if not found or the board object
	 */
	public static function getByType($value, $type, $switch = true)
	{
		$items = static::getAll();

		foreach ($items as $item)
		{
			if ($switch == ($item->$type === $value))
			{
				return $item;
			}
		}

		return false;
	}


	/**
	 * Returns the single radix by shortname
	 *
	 * @return  \Foolz\Foolfuuka\Model\Radix  the board with the shortname, false if not found
	 */
	public static function getByShortname($shortname)
	{
		return static::getByType($shortname, 'shortname');
	}


	/**
	 * Returns only the type specified (exam)
	 *
	 * @param  string   $type    the variable name
	 * @param  boolean  $switch  the value to match
	 *
	 * @return  \Foolz\Foolfuuka\Model\Radix[]  the Radix objects
	 */
	public static function filterByType($type, $switch)
	{
		$items = static::getAll();
		foreach ($items as $key => $item)
		{
			if ($item->$type != $switch)
			{
				unset($items[$key]);
			}
		}

		return $items;
	}


	/**
	 * Returns an array of objects that are archives
	 *
	 * @return  \Foolz\Foolfuuka\Model\Radix[]  the board objects that are archives
	 */
	public static function getArchives()
	{
		return static::filterByType('archive', true);
	}


	/**
	 * Returns an array of objects that are boards (not archives)
	 *
	 * @return  \Foolz\Foolfuuka\Model\Radix[]  the board objects that are boards
	 */
	public static function getBoards()
	{
		return static::filterByType('archive', false);
	}

	/**
	 * Get the board table name with protexted identifiers
	 *
	 * @param   string  $suffix  board suffix like _images
	 *
	 * @return  string  the table name with protected identifiers
	 */
	public function getTable($suffix = '')
	{
		if (\Preferences::get('fu.boards.db'))
		{
			return DC::forge()->quoteIdentifier(\Preferences::get('fu.boards.db'))
				.'.'.DC::forge()->quoteIdentifier($this->shortname.$suffix);
		}
		else
		{
			return DC::forge()->quoteIdentifier(DC::p('board_'.$this->shortname.$suffix));
		}
	}

	/**
	 * Creates the tables for the board
	 */
	public function createTables()
	{
		$charset = 'utf8mb4';
		$collation = 'utf8mb4_unicode_ci';

		$sm = DC::forge()->getSchemaManager();
		$schema = $sm->createSchema();
		$table = $schema->createTable($this->getTable());
		if (DC::forge()->getDriver()->getName() == 'pdo_mysql')
		{
			$table->addOption('charset', $charset);
			$table->addOption('collate', $collation);
		}
		$table->addColumn('doc_id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
		$table->addColumn('media_id', 'integer', ['unsigned' => true, 'default' => 0]);
		$table->addColumn('poster_ip', 'decimal', ['unsigned' => true, 'precision' => 39, 'scale' => 0, 'default' => 0]);
		$table->addColumn('num', 'integer', ['unsigned' => true]);
		$table->addColumn('subnum', 'integer', ['unsigned' => true]);
		$table->addColumn('thread_num', 'integer', ['unsigned' => true, 'default' => 0]);
		$table->addColumn('op', 'boolean', ['default' => 0]);
		$table->addColumn('timestamp', 'integer', ['unsigned' => true]);
		$table->addColumn('timestamp_expired', 'integer', ['unsigned' => true]);
		$table->addColumn('preview_orig', 'string', ['length' => 20, 'notnull' => false]);
		$table->addColumn('preview_w', 'smallint', ['unsigned' => true, 'default' => 0]);
		$table->addColumn('preview_h', 'smallint', ['unsigned' => true, 'default' => 0]);
		$table->addColumn('media_filename', 'text', ['length' => 65532, 'notnull' => false]);
		$table->addColumn('media_w', 'smallint', ['unsigned' => true, 'default' => 0]);
		$table->addColumn('media_h', 'smallint', ['unsigned' => true, 'default' => 0]);
		$table->addColumn('media_size', 'integer', ['unsigned' => true, 'default' => 0]);
		$table->addColumn('media_hash', 'string', ['length' => 25, 'notnull' => false]);
		$table->addColumn('media_orig', 'string', ['length' => 20, 'notnull' => false]);
		$table->addColumn('spoiler', 'boolean', ['default' => 0]);
		$table->addColumn('deleted', 'boolean', ['default' => 0]);
		$table->addColumn('capcode', 'string', ['length' => 1, 'default' => 'N']);
		$table->addColumn('email', 'string', ['length' => 100, 'notnull' => false]);
		$table->addColumn('name', 'string', ['length' => 100, 'notnull' => false]);
		$table->addColumn('trip', 'string', ['length' => 25, 'notnull' => false]);
		$table->addColumn('title', 'string', ['length' => 100, 'notnull' => false]);
		$table->addColumn('comment', 'text', ['length' => 65532, 'notnull' => false]);
		$table->addColumn('delpass', 'text', ['length' => 255, 'notnull' => false]);
		$table->addColumn('sticky', 'boolean', ['default' => 0]);
		$table->addColumn('poster_hash', 'string', ['length' => 8, 'notnull' => false]);
		$table->addColumn('poster_country', 'string', ['length' => 2, 'notnull' => false]);
		$table->addColumn('exif', 'text', ['length' => 65532, 'notnull' => false]);
		$table->setPrimaryKey(['doc_id']);
		$table->addUniqueIndex(['num', 'subnum'], 'num_subnum_index');
		$table->addIndex(['thread_num', 'num', 'subnum'], 'thread_num_subnum_index');
		$table->addIndex(['subnum'], 'subnum_index');
		$table->addIndex(['op'], 'op_index');
		$table->addIndex(['media_id'], 'media_id_index');
		$table->addIndex(['media_hash'], 'media_hash_index');
		$table->addIndex(['media_orig'], 'media_orig_index');
		$table->addIndex(['name', 'trip'], 'name_trip_index');
		$table->addIndex(['trip'], 'trip_index');
		$table->addIndex(['email'], 'email_index');
		$table->addIndex(['poster_ip'], 'poster_ip_index');
		$table->addIndex(['timestamp'], 'timestamp_index');

		$table_threads = $schema->createTable($this->getTable('_threads'));
		if (DC::forge()->getDriver()->getName() == 'pdo_mysql')
		{
			$table_threads->addOption('charset', $charset);
			$table_threads->addOption('collate', $collation);
		}
		$table_threads->addColumn('thread_num', 'integer', ['unsigned' => true]);
		$table_threads->addColumn('time_op', 'integer', ['unsigned' => true]);
		$table_threads->addColumn('time_last', 'integer', ['unsigned' => true]);
		$table_threads->addColumn('time_bump', 'integer', ['unsigned' => true]);
		$table_threads->addColumn('time_ghost', 'integer', ['unsigned' => true, 'notnull' => false, 'default' => 'null']);
		$table_threads->addColumn('time_ghost_bump', 'integer', ['unsigned' => true, 'notnull' => false, 'default' => 'null']);
		$table_threads->addColumn('nreplies', 'integer', ['unsigned' => true, 'default' => 0]);
		$table_threads->addColumn('nimages', 'integer', ['unsigned' => true, 'default' => 0]);
		$table_threads->setPrimaryKey(['thread_num']);
		$table_threads->addIndex(['time_op'], 'time_op_index');
		$table_threads->addIndex(['time_bump'], 'time_bump_index');
		$table_threads->addIndex(['time_ghost_bump'], 'time_ghost_bump_index');

		$table_users = $schema->createTable($this->getTable('_users'));
		if (DC::forge()->getDriver()->getName() == 'pdo_mysql')
		{
			$table_users->addOption('charset', $charset);
			$table_users->addOption('collate', $collation);
		}
		$table_users->addColumn('user_id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
		$table_users->addColumn('name', 'string', ['length' => 100, 'default' => '']);
		$table_users->addColumn('trip', 'string', ['length' => 25, 'default' => '']);
		$table_users->addColumn('firstseen', 'integer', ['unsigned' => true]);
		$table_users->addColumn('postcount', 'integer', ['unsigned' => true]);
		$table_users->setPrimaryKey(['user_id']);
		$table_users->addUniqueIndex(['name', 'trip'], 'name_trip_index');
		$table_users->addIndex(['firstseen'], 'firstseen_index');
		$table_users->addIndex(['postcount'], 'postcount_index');

		$table_images = $schema->createTable($this->getTable('_images'));
		$table_images->addColumn('media_id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
		$table_images->addColumn('media_hash', 'string', ['length' => 25]);
		$table_images->addColumn('media', 'string', ['length' => 20, 'notnull' => false]);
		$table_images->addColumn('preview_op', 'string', ['length' => 20, 'notnull' => false]);
		$table_images->addColumn('preview_reply', 'string', ['length' => 20, 'notnull' => false]);
		$table_images->addColumn('total', 'integer', ['unsigned' => true, 'default' => 0]);
		$table_images->addColumn('banned', 'smallint', ['unsigned' => true, 'default' => 0]);
		$table_images->setPrimaryKey(['media_id']);
		$table_images->addUniqueIndex(['media_hash'], 'media_hash_index');
		$table_images->addIndex(['total'], 'total_index');
		$table_images->addIndex(['banned'], 'banned_index');

		$table_daily = $schema->createTable($this->getTable('_daily'));
		$table_daily->addColumn('day', 'integer', ['unsigned' => true]);
		$table_daily->addColumn('posts', 'integer', ['unsigned' => true]);
		$table_daily->addColumn('images', 'integer', ['unsigned' => true]);
		$table_daily->addColumn('sage', 'integer', ['unsigned' => true]);
		$table_daily->addColumn('anons', 'integer', ['unsigned' => true]);
		$table_daily->addColumn('trips', 'integer', ['unsigned' => true]);
		$table_daily->addColumn('names', 'integer', ['unsigned' => true]);
		$table_daily->setPrimaryKey(['day']);

		$table_extra = $schema->createTable($this->getTable('_extra'));
		if (DC::forge()->getDriver()->getName() == 'pdo_mysql')
		{
			$table_extra->addOption('charset', $charset);
			$table_extra->addOption('collate', $collation);
		}
		$table_extra->addColumn('extra_id', 'integer', ['unsigned' => true]);
		$table_extra->addColumn('json', 'text', ['length' => 65532, 'notnull' => false]);
		$table_extra->setPrimaryKey(['extra_id']);

		DC::forge()->beginTransaction();

		foreach ($schema->getMigrateFromSql($sm->createSchema(), $sm->getDatabasePlatform()) as $query)
		{
			DC::forge()->query($query);
		}

		$md5_array = DC::qb()
			->select('md5')
			->from(DC::p('banned_md5'), 'm')
			->execute()
			->fetchAll();

		// in a transaction multiple inserts are almost like a single one
		foreach ($md5_array as $item)
		{
			DC::forge()->insert($this->getTable('_images'), ['md5' => $item['md5'], 'banned' => 1]);
		}

		DC::forge()->commit();
	}

	/**
	 * Remove the tables associated to the Radix
	 */
	public function removeTables()
	{
		$tables = [
			'',
			'_images',
			'_threads',
			'_users',
			'_daily',
			'_extra'
		];

		$sm = DC::forge()->getSchemaManager();
		$schema = $sm->createSchema();
		foreach ($tables as $table)
		{
			$schema->dropTable($this->getTable($table));
		}

		foreach ($schema->getMigrateFromSql($sm->createSchema(), $sm->getDatabasePlatform()) as $query)
		{
			DC::forge()->query($query);
		}
	}
}