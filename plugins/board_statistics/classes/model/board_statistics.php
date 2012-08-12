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
		$stats = $this->get_stats();
		// this variable is going to be a serialized array
		$enabled = get_setting('fu_plugins_board_statistics_enabled');
		
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
		$available = $this->get_available_stats();

		if (isset($available[$stat]))
		{
			if (!isset($available[$stat]['frequency']))
			{
				// real time stat
				$process_function = 'process_' . $stat;
				$result = $this->$process_function($selected_board);

				return array('info' => $available[$stat], 'data' => json_encode($result));
			}
			else
			{
				$query = $this->db->query('
					SELECT *
					FROM ' . $this->db->protect_identifiers('plugin_fu-board-statistics',
						TRUE) . '
					WHERE board_id = ? AND name = ?
					LIMIT 0,1
				',
					array($selected_board->id, $stat));
				if ($query->num_rows() != 1)
				{
					return FALSE;
				}

				$result = $query->result();
			}

			return array('info' => $available[$stat], 'data' => $result[0]->data, 'timestamp' => $result[0]->timestamp);
		}
		return FALSE;
	}
	
	
	public static function get_stat($board_id, $name)
	{
		$stat = $this->db->query('
			SELECT *
			FROM ' . $this->db->protect_identifiers('plugin_fu-board-statistics',
			TRUE) . '
			WHERE board_id = ? and name = ?
		', array($board_id, $name));

		if ($stat->num_rows() == 0)
			return FALSE;

		$result = $stat->result();
		$stat->free_result();
		return $result[0];
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
		$this->db->query('
			UPDATE ' . $this->db->protect_identifiers('plugin_fu-board-statistics',
				TRUE) . '
			SET timestamp = ?
			WHERE board_id = ? AND name = ? AND timestamp = ?
		',
			array(date('Y-m-d H:i:s', time() + 600), $board_id, $name, $temp_timestamp)); // hopefully 10 minutes is enough for everything

		if ($this->db->affected_rows() != 1)
			return FALSE;

		return TRUE;
	}


	public static function save_stat($board_id, $name, $timestamp, $data = '')
	{
		$this->db->query('
			INSERT
			INTO ' . $this->db->protect_identifiers('plugin_fu-board-statistics',
				TRUE) . '
			(board_id, name, timestamp, data)
			VALUES
				(?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE
				timestamp = VALUES(timestamp), data = VALUES(data);
		',
			array($board_id, $name, $timestamp, json_encode($data)));
	}


	public static function process_availability($board)
	{
		$query = $this->db->query('
				SELECT
					name, trip, COUNT(num) AS posts,
					AVG(timestamp%86400) AS avg1,
					STDDEV_POP(timestamp%86400) AS std1,
					(AVG((timestamp+43200)%86400)+43200)%86400 avg2,
					STDDEV_POP((timestamp+43200)%86400) AS std2
				FROM ' . Radix::get_table($board) . '
				FORCE INDEX(fullname_index)
				WHERE timestamp > ?
				GROUP BY name, trip
				HAVING count(*) > 4
				ORDER BY name, trip
		',
			array(time() - 2592000));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	public static function process_daily_activity($board)
	{
		$query = $this->db->query('
			SELECT
				(FLOOR(timestamp/300)%288)*300 AS time, COUNT(timestamp), COUNT(media_hash),
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)
			FROM ' . Radix::get_table($board) . '
			USE INDEX(timestamp_index)
			WHERE timestamp > ?
			GROUP BY FLOOR(timestamp/300)%288
			ORDER BY FLOOR(timestamp/300)%288;
		',
			array(time() - 86400));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	public static function process_daily_activity_archive($board)
	{
		$query = $this->db->query('
			SELECT
				((FLOOR(timestamp/3600)%24)*3600)+1800 AS time, COUNT(timestamp),
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)
			FROM ' . Radix::get_table($board) . '
			USE INDEX(timestamp_index)
			WHERE timestamp> ? AND subnum != 0
			GROUP BY FLOOR(timestamp/3600)%24
			ORDER BY FLOOR(timestamp/3600)%24;
		',
			array(time() - 86400));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	public static function process_daily_activity_hourly($board)
	{
		$query = $this->db->query('
			SELECT
				((FLOOR(timestamp/3600)%24)*3600)+1800 AS time, COUNT(timestamp), COUNT(media_hash),
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)
			FROM ' . Radix::get_table($board) . '
			USE INDEX(timestamp_index)
			WHERE timestamp > ?
			GROUP BY FLOOR(timestamp/3600)%24
			ORDER BY FLOOR(timestamp/3600)%24;
		',
			array(time() - 86400));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	public static function process_image_reposts($board)
	{
		$query = $this->db->query('
			SELECT *
			FROM ' . Radix::get_table($board, '_images') . '
			ORDER BY total DESC
			LIMIT 0, 200;
		');

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	public static function process_karma($board)
	{
		$query = $this->db->query('
			SELECT
				day AS time, posts, images, sage
			FROM ' . Radix::get_table($board, '_daily') . '
			WHERE day > floor((?-31536000)/86400)*86400
			GROUP BY day
			ORDER BY day
		',
			array(time()));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	public static function process_new_users($board)
	{
		$query = $this->db->query('
			SELECT
				name, trip, firstseen, postcount
			FROM ' . Radix::get_table($board, '_users') . '
			WHERE postcount > 30
			ORDER BY firstseen DESC;
		');

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	public static function process_population($board)
	{
		$query = $this->db->query('
			SELECT
				day AS time, trips, names, anons
			FROM ' . Radix::get_table($board, '_daily') . '
			WHERE day > floor((?-31536000)/86400)*86400
			GROUP BY day
			ORDER BY day
		',
			array(time())
		);

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	public static function process_post_count($board)
	{
		$query = $this->db->query('
			SELECT
				name, trip, postcount
			FROM ' . Radix::get_table($board, '_users') . '
			ORDER BY postcount DESC
			LIMIT 512
		');

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	public static function process_post_rate($board)
	{
		$query = $this->db->query('
			SELECT
				COUNT(timestamp), COUNT(timestamp)/60
			FROM ' . Radix::get_table($board) . '
			WHERE timestamp > ?
		',
			array(time() - 3600));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	public static function process_post_rate_archive($board)
	{
		$query = $this->db->query('
			SELECT
				COUNT(timestamp), COUNT(timestamp)/60
			FROM ' . Radix::get_table($board) . '
			WHERE timestamp > ? AND subnum != 0
		',
			array(time() - 3600));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	public static function process_users_online($board)
	{
		$query = $this->db->query('
			SELECT name, trip, MAX(timestamp), num, subnum
			FROM ' . Radix::get_table($board) . '
			WHERE timestamp > ?
			GROUP BY name, trip
			ORDER BY MAX(timestamp) DESC
		',
			array(time() - 1800));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	public static function process_users_online_internal($board)
	{
		$query = $this->db->query('
			SELECT
				name, trip, MAX(timestamp), num, subnum
			FROM ' . Radix::get_table($board) . '
			WHERE poster_ip <> 0 AND timestamp > ?
			GROUP BY name, trip
			ORDER BY MAX(timestamp) DESC
		',
			array(time() - 3600));

		$array = $query->result_array();
		$query->free_result();
		return $array;
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
		if (!file_exists(FCPATH . 'content/cache/'))
		{
			mkdir(FCPATH . 'content/cache/');
		}
		if (!file_exists(FCPATH . 'content/statistics/'))
		{
			mkdir(FCPATH . 'content/statistics/');
		}
		if (!file_exists(FCPATH . 'content/statistics/' . $board . '/'))
		{
			mkdir(FCPATH . 'content/statistics/' . $board . '/');
		}

		// Set PATH for INFILE, GNUFILE, and OUTFILE for read/write.
		$INFILE = FCPATH . 'content/cache/statistics-' . $board . '-' . $stat . '.dat';
		$GNUFILE = FCPATH . 'content/cache/statistics-' . $board . '-' . $stat . '.gnu';
		$OUTFILE = FCPATH . 'content/statistics/' . $board . '/' . $stat . '.png';

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
		write_file($INFILE, $graph_data);

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
			$this->generate_gnuplot_template($stat));
		write_file($GNUFILE, $template);

		// Execute GNUPLOT with GNUFILE input.
		$result = @exec('/usr/bin/gnuplot ' . $GNUFILE);
		return $result;
	}


	public static function generate_gnuplot_template($stat)
	{
		$stats = $this->get_available_stats();
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