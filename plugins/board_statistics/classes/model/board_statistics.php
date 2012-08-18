<?php

namespace Foolfuuka\Plugins\Board_Statistics;

if (!defined('DOCROOT'))
	exit('No direct script access allowed');

class Board_Statistics extends \Plugins
{
	public static function get_stats()
	{
		return array(
			'availability' => array(
				'location' => 'availability',
				'name' => __('Availability'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60 * 5,
				'interface' => 'availability'
			),
			'daily_activity' => array(
				'location' => 'daily_activity',
				'name' => __('Daily Activity'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60 * 6, // every 6 hours
				'interface' => 'graph',
				'gnuplot' => array(
					'title' => "'5-Minute Intervals'",
					'style' => 'data fsteps',
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "{{X_START}}" : "{{X_END}}" ]',
					'format' => 'x "%H:%M"',
					'grid' => TRUE,
					'key' => 'left',
					'plot' => array(
						"'{{INFILE}}' using 1:2 t 'Posts'  lt rgb '#008000' with filledcurve x1",
						"'{{INFILE}}' using 1:3 t 'Images' lt rgb '#0000ff' with filledcurve x1",
						"'{{INFILE}}' using 1:4 t 'Sages'  lt rgb '#ff0000' with filledcurve x1"
					)
				)
			),
			'daily_activity_archive' => array(
				'location' => 'daily_activity_archive',
				'name' => __('Daily Activity "Archive"'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60, // every hour
				'interface' => 'graph',
				'gnuplot' => array(
					'title' => "'1-Hour Intervals'",
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "0" : "86400" ]',
					'format' => 'x "%H:%M"',
					'grid' => TRUE,
					'key' => 'left',
					'boxwidth' => 3600,
					'style' => 'fill solid border -1',
					'plot' => array(
						"'{{INFILE}}' using 1:2 t 'Posts' lt rgb '#008000' with boxes",
						"'{{INFILE}}' using 1:3 t 'Sages' lt rgb '#ff0000' with boxes"
					)
				)
			),
			'daily_activity_hourly' => array(
				'location' => 'daily_activity_hourly',
				'name' => __('Daily Activity "Hourly"'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60, // every 6 hours
				'interface' => 'graph',
				'gnuplot' => array(
					'title' => "'1-Hour Intervals'",
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "0" : "86400" ]',
					'format' => 'x "%H:%M"',
					'grid' => TRUE,
					'key' => 'left',
					'boxwidth' => 3600,
					'style' => 'fill solid border -1',
					'plot' => array(
						"'{{INFILE}}' using 1:2 t 'Posts'  lt rgb '#008000' with boxes",
						"'{{INFILE}}' using 1:3 t 'Images' lt rgb '#0000ff' with boxes",
						"'{{INFILE}}' using 1:4 t 'Sages'  lt rgb '#ff0000' with boxes"
					)
				)
			),
			'image_reposts' => array(
				'location' => 'image_reposts',
				'name' => __('Image Reposts'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'image_reposts'
			),
			'karma' => array(
				'location' => 'karma',
				'name' => __('Karma'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60 * 24 * 7, // every 7 days
				'interface' => 'graph',
				'gnuplot' => array(
					'title' => "'1-Day Intervals'",
					'style' => 'data fsteps',
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "{{X_START}}" : "{{X_END}}" ]',
					'format' => 'x "%m/%y"',
					'grid' => TRUE,
					'key' => 'left',
					'plot' => array(
						"'{{INFILE}}' using 1:2 t 'Posts'  lt rgb '#008000' with filledcurve x1",
						"'{{INFILE}}' using 1:3 t 'Images' lt rgb '#0000ff' with filledcurve x1",
						"'{{INFILE}}' using 1:4 t 'Sages'  lt rgb '#ff0000' with filledcurve x1"
					)
				)
			),
			'new_users' => array(
				'location' => 'new_users',
				'name' => __('New Users'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'new_users'
			),
			'population' => array(
				'location' => 'population',
				'name' => __('Population'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'frequency' => 60 * 60 * 24, // every day
				'interface' => 'graph',
				'gnuplot' => array(
					'title' => "'Posters'",
					'style' => 'data fsteps',
					'timefmt' => '"%s"',
					'yrange' => '[ 0 : ]',
					'xdata' => 'time',
					'xrange' => '[ "{{X_START}}" : "{{X_END}}" ]',
					'format' => 'x "%m/%y"',
					'grid' => TRUE,
					'key' => 'left',
					'plot' => array(
						"'{{INFILE}}' using 1:4 t 'Anonymous'   lt rgb '#008000' with filledcurve x1",
						"'{{INFILE}}' using 1:2 t 'Tripfriends' lt rgb '#0000ff' with filledcurve x1",
						"'{{INFILE}}' using 1:3 t 'Namefags'    lt rgb '#ff0000' with filledcurve x1"
					)
				)
			),
			'post_count' => array(
				'location' => 'post_count',
				'name' => __('Post Count'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'post_count'
			),
			'post_rate' => array(
				'location' => 'post_rate',
				'name' => __('Post Rate'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'post_rate'
			),
			'post_rate_archive' => array(
				'location' => 'post_rate_archive',
				'name' => __('Post Rate "Archive"'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'post_rate'
			),
			'users_online' => array(
				'location' => 'users_online',
				'name' => __('Users Online'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'users_online'
			),
			'users_online_internal' => array(
				'location' => 'users_online_internal',
				'name' => __('Users Posting in Archive'),
				'description' => __('Posts in last month by name and availability by time of day.'),
				'interface' => 'users_online'
			)
		);
	}
	
	
	public static function get_available_stats()
	{
		$stats = static::get_stats();
		// this variable is going to be a serialized array
		$enabled = \Preferences::get('fu.plugins.board_statistics.enabled');
		
		if(!$enabled)
		{
			return array();
		}
		
		$enabled = unserialize($enabled);
		
		foreach ($stats as $k => $s)
		{
			if (!$enabled[$k])
			{
				unset($stats[$k]);
			}
		}
		return $stats;
	}
	
	
	public static function check_available_stats($stat, $selected_board)
	{
		$available = static::get_available_stats();

		if (isset($available[$stat]))
		{
			if (!isset($available[$stat]['frequency']))
			{
				// real time stat
				$process_function = 'process_' . $stat;
				$result = static::$process_function($selected_board);

				return array('info' => $available[$stat], 'data' => json_encode($result));
			}
			else
			{
				$result = \DB::select()
					->from('plugin_fu-board-statistics')
					->where('board_id', '=', $selected_board->id)
					->where('name', '=', $stat)
					->offset(0)
					->limit(1)
					->as_object()
					->execute()
					->as_array();
				
				if (count($result) != 1)
				{
					return false;
				}

			}

			return array('info' => $available[$stat], 'data' => $result[0]->data, 'timestamp' => $result[0]->timestamp);
		}
		return false;
	}
	
	
	public static function get_stat($board_id, $name)
	{
		
		$stat = \DB::select()
			->from('plugin_fu-board-statistics')
			->where('board_id', '=', $board_id)
			->where('name', '=', $name)
			->as_object()
			->execute();

		if ( ! count($stat))
			return false;

		return $stat->current();
	}
	
	
	/**
	 * To avoid really dangerous racing conditions, turn up the timer before starting the update
	 *
	 * @param int  $board_id
	 * @param type $name
	 * @param date $temp_timestamp
	 *
	 * @return boolean
	 */
	public static function lock_stat($board_id, $name, $temp_timestamp)
	{
		// again, to avoid racing conditions, let's also check that the timestamp hasn't been changed
		$affected = \DB::update('plugin_fu-board-statistics')
			->set('timestamp', date('Y-m-d H:i:s', time() + 600))
			->where('board_id', '=', $board_id)
			->where('name', '=', $name)
			->where('timestamp', '=', $temp_timestamp)
			->execute(); // hopefully 10 minutes is enough for everything

		if ($affected != 1)
			return false;

		return true;
	}


	public static function save_stat($board_id, $name, $timestamp, $data = '')
	{
		$result = \DB::select(\DB::expr('COUNT(*) as count'))
			->from('plugin_fu-board-statistics')
			->where('board_id', '=', $board_id)
			->where('name', '=', $name)
			->execute()
			->current();
		
		if ( ! $result['count'])
		{
			\DB::insert('plugin_fu-board-statistics')
				->set(array('board_id' => $board_id, 'name' => $name, 
					'timestamp' => $timestamp, 'data' =>json_encode($data)))
				->execute();
		}
		else
		{
			\DB::update('plugin_fu-board-statistics')
				->where('board_id', '=', $board_id)
				->where('name', '=', $name)
				->set(array('timestamp' => $timestamp, 'data' =>json_encode($data)))
				->execute();
		}
	}


	public static function process_availability($board)
	{
		return \DB::select(\DB::expr('
				name, trip, COUNT(num) AS posts,
				AVG(timestamp%86400) AS avg1,
				STDDEV_POP(timestamp%86400) AS std1,
				(AVG((timestamp+43200)%86400)+43200)%86400 avg2,
				STDDEV_POP((timestamp+43200)%86400) AS std2
			'))
			->from(\DB::expr(\Radix::get_table($board).' FORCE INDEX(timestamp_index)'))
			->where('timestamp', '>', time() - 2592000)
			->group_by('name', 'trip')
			->having(\DB::expr('count(*)'), '>', 4)
			->order_by('name')
			->order_by('trip')
			->execute()
			->as_array();
	}


	public static function process_daily_activity($board)
	{
		return \DB::select(\DB::expr('(FLOOR(timestamp/300)%288)*300 AS time'), 
				\DB::expr('COUNT(timestamp)'), 
				\DB::expr('COUNT(media_hash)'),
				\DB::expr('COUNT(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)'))
			->from(\DB::expr(\Radix::get_table($board).' USE INDEX(timestamp_index)'))
			->where('timestamp', '>', time() - 86400)
			->group_by(\DB::expr('FLOOR(timestamp/300)%288'))
			->order_by(\DB::expr('FLOOR(timestamp/300)%288'))
			->execute()
			->as_array();
	}


	public static function process_daily_activity_archive($board)
	{
		return \DB::select(\DB::expr('((FLOOR(timestamp/3600)%24)*3600)+1800 AS time'), 
				\DB::expr('COUNT(timestamp)'),
				\DB::expr('COUNT(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)'))
			->from(\DB::expr(\Radix::get_table($board).' USE INDEX(timestamp_index)'))
			->where('timestamp', '>', time() - 86400)
			->where('subnum', '<>', 0)
			->group_by(\DB::expr('FLOOR(timestamp/3600)%24'))
			->order_by(\DB::expr('FLOOR(timestamp/3600)%24'))
			->execute()
			->as_array();
	}


	public static function process_daily_activity_hourly($board)
	{
		return \DB::select(\DB::expr('((FLOOR(timestamp/3600)%24)*3600)+1800 AS time'), 
				\DB::expr('COUNT(timestamp)'),
				\DB::expr('COUNT(media_hash)'), 
				\DB::expr('COUNT(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)'))
			->from(\DB::expr(\Radix::get_table($board).' USE INDEX(timestamp_index)'))
			->where('timestamp', '>', time() - 86400)
			->group_by(\DB::expr('FLOOR(timestamp/3600)%24'))
			->order_by(\DB::expr('FLOOR(timestamp/3600)%24'))
			->execute()
			->as_array();
	}


	public static function process_image_reposts($board)
	{
		return \DB::select()
			->from(\DB::expr(\Radix::get_table($board, '_images')))
			->order_by('total', 'desc')
			->offset(0)
			->limit(200)
			->execute()
			->as_array();
	}


	public static function process_karma($board)
	{
		return \DB::select(\DB::expr('day AS time'), 'posts', 'images', 'sage')
			->from(\DB::expr(\Radix::get_table($board, '_daily')))
			->where('day', '>', \DB::expr('floor(('.time().'-31536000)/86400)*86400'))
			->group_by('day')
			->order_by('day')
			->execute()
			->as_array();
	}


	public static function process_new_users($board)
	{
		return \DB::select('name', 'trip', 'firstseen', 'postcount')
			->from(\DB::expr(\Radix::get_table($board, '_users')))
			->where('postcount', '>', 30)
			->order_by('firstseen', 'desc')
			->execute()
			->as_array();
	}


	public static function process_population($board)
	{
		return \DB::select(\DB::expr('day AS time'), 'trips', 'names', 'anons')
			->from(\DB::expr(\Radix::get_table($board, '_daily')))	
			->where('day', '>', \DB::expr('floor(('.time().'-31536000)/86400)*86400'))
			->group_by('day')
			->order_by('day')
			->execute()
			->as_array();
	}


	public static function process_post_count($board)
	{
		return \DB::select('name', 'trip', 'postcount')
			->from(\DB::expr(\Radix::get_table($board, '_users')))	
			->order_by('postcount', 'desc')
			->limit(512)
			->execute()
			->as_array();
	}


	public static function process_post_rate($board)
	{
		return \DB::select(\DB::expr('COUNT(timestamp)'), \DB::expr('COUNT(timestamp)/60'))
			->from(\DB::expr(\Radix::get_table($board)))	
			->where('timestamp', '>', time() - 3600)
			->execute()
			->as_array();
	}


	public static function process_post_rate_archive($board)
	{
		return \DB::select(\DB::expr('COUNT(timestamp)'), \DB::expr('COUNT(timestamp)/60'))
			->from(\DB::expr(\Radix::get_table($board)))	
			->where('timestamp', '>', time() - 3600)
			->where('subnum', '<>', 0)
			->execute()
			->as_array();
	}


	public static function process_users_online($board)
	{
		return \DB::select('name', 'trip', \DB::expr('MAX(timestamp)'), 'num', 'subnum')
			->from(\DB::expr(\Radix::get_table($board)))
			->where('timestamp', '>', time() - 1800)
			->group_by('name', 'trip')
			->order_by(\DB::expr('MAX(timestamp)'), 'desc')
			->execute()
			->as_array();
	}


	public static function process_users_online_internal($board)
	{
		return \DB::select('name', 'trip', \DB::expr('MAX(timestamp)'), 'num', 'subnum')
			->from(\DB::expr(\Radix::get_table($board)))
			->where('poster_ip', '<>', 0)
			->where('timestamp', '>', time() - 3600)
			->group_by('name', 'trip')
			->order_by(\DB::expr('MAX(timestamp)'), 'desc')
			->execute()
			->as_array();
	}


	/**
	 * Generate the INFILE, OUTFILE, TEMPLATE files used by GNUPLOT to create graphs.
	 *
	 * @param $board name of the board
	 * @param $stat  name of the statistics generated
	 * @param $data  input dataset array
	 *
	 * @return void
	 */
	public static function graph_gnuplot($board, $stat, $data)
	{
		// Create all missing directory paths for statistics.
		if (!file_exists(DOCROOT . 'foolfuuka/cache/'))
		{
			mkdir(DOCROOT . 'foolfuuka/cache/');
		}
		if (!file_exists(DOCROOT . 'foolfuuka/statistics/'))
		{
			mkdir(DOCROOT . 'foolfuuka/statistics/');
		}
		if (!file_exists(DOCROOT . 'foolfuuka/statistics/' . $board . '/'))
		{
			mkdir(DOCROOT . 'foolfuuka/statistics/' . $board . '/');
		}

		// Set PATH for INFILE, GNUFILE, and OUTFILE for read/write.
		$INFILE = DOCROOT . 'foolfuuka/cache/statistics-' . $board . '-' . $stat . '.dat';
		$GNUFILE = DOCROOT . 'foolfuuka/cache/statistics-' . $board . '-' . $stat . '.gnu';
		$OUTFILE = DOCROOT . 'foolfuuka/statistics/' . $board . '/' . $stat . '.png';

		// Obtain starting and ending data points for x range.
		$X_START = (!empty($data) ? $data[0]['time'] : 0);
		$X_END = (!empty($data) ? $data[count($data) - 1]['time'] : 0);

		// Format and save the INFILE dataset for GNUPLOT.
		$graph_data = array();
		foreach ($data as $line)
		{
			$graph_data[] = implode("\t", $line);
		}
		$graph_data = implode("\n", $graph_data);
		file_put_contents($INFILE, $graph_data);

		// Set template variables for replacement.
		$template_vars = array(
			'{{INFILE}}',
			'{{OUTFILE}}',
			'{{X_START}}',
			'{{X_END}}'
		);
		$template_vals = array(
			$INFILE,
			$OUTFILE,
			$X_START,
			$X_END
		);

		$template = str_replace($template_vars, $template_vals,
			static::generate_gnuplot_template($stat));
		file_put_contents($GNUFILE, $template);

		// Execute GNUPLOT with GNUFILE input.
		$result = @exec('/usr/bin/gnuplot ' . $GNUFILE);
		return $result;
	}


	public static function generate_gnuplot_template($stat)
	{
		$stats = static::get_available_stats();
		$options = $stats[$stat]['gnuplot'];

		$template = array();
		$template[] = "set terminal png transparent size 800,600";
		$template[] = "set output '{{OUTFILE}}'";
		$template[] = "show terminal";

		foreach ($options as $key => $value)
		{
			if ($value === TRUE)
			{
				$template[] = "set {$key}";
			}
			else
			{
				if (is_array($value))
				{
					$value = implode(", ", $value);
					$template[] = "{$key} {$value}";
				}
				else
				{
					$template[] = "set {$key} {$value}";
				}
			}
		}

		return implode("\n", $template);
	}
	
}