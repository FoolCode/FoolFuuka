<?php

if (!defined('BASEPATH'))
	exit('No direct script access allowed');

class Statistics extends CI_Model
{

	var $stats = array();

	function __construct($id = NULL)
	{
		parent::__construct();
		$this->load_stats();
	}


	function get_table($board)
	{
		if (get_setting('fs_fuuka_boards_db'))
		{
			return $this->table = $this->db->protect_identifiers(get_setting('fs_fuuka_boards_db')) . '.' . $this->db->protect_identifiers($board->shortname);
		}
		return $this->table = $this->db->protect_identifiers('board_' . $board->shortname, TRUE);
	}


	function load_stats()
	{
		$this->stats = array(
			'availability' => array(
				'location' =>'availability',
				'name' => _('Availability'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 6, // every 6 hours
				'interface' => 'availability'
			),
			'daily_activity' => array(
				'location' =>'daily_activity',
				'name' => _('Daily activity'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 6, // every 6 hours
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
				'location' =>'daily_activity_archive',
				'name' => _('Daily activity archive'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60, // every hour
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
				'location' =>'daily_activity_hourly',
				'name' => _('Daily activity hourly'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60, // every 6 hours
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
				'location' =>'image_reposts',
				'name' => _('Image reposts'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 24 * 7, // every 7 days
				'interface' => 'image_reposts'
			),
			'karma' => array(
				'location' =>'karma',
				'name' => _('Karma'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 24 * 7, // every 7 days
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
			'new_tripfriends' => array(
				'location' =>'new_tripfriends',
				'name' => _('New tripfags'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 24 * 4, // every 4 days
				'interface' => 'new_tripfriends'
			),
			'population' => array(
				'location' =>'population',
				'name' => _('Population'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 24, // every day
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
				'location' =>'post_count',
				'name' => _('Post count'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 60 * 24 * 4, // every 4 days
				'interface' => 'post_count'
			),
			'post_rate' => array(
				'location' =>'post_rate',
				'name' => _('Post rate'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 3, // every 3 minutes
				'interface' => 'post_rate'
			),
			'post_rate_archive' => array(
				'location' =>'post_rate_archive',
				'name' => _('Post rate archive'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60 * 3, // every 3 minutes
				'interface' => 'post_rate'
			),
			'users_online' => array(
				'location' =>'users_online',
				'name' => _('Users online'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60, // every minute
				'interface' => 'users_online'
			),
			'users_online_internal' => array(
				'location' =>'users_online_internal',
				'name' => _('Users posting in archive'),
				'description' => _('Posts in last month by name and availability by time of day.'),
				'enabled' => TRUE,
				'frequence' => 60, // every minute
				'interface' => 'users_online'
			)
		);
	}


	function get_stats()
	{
		return $this->stats;
	}


	function get_available_stats()
	{
		$stats = $this->get_stats();
		foreach ($stats as $k => $s)
		{
			if ($s['enabled'] !== TRUE)
			{
				unset($stats[$k]);
			}
		}
		return $stats;
	}


	function check_available_stats($stat, $selected_board)
	{
		$available = $this->get_available_stats();
		if (isset($available[$stat]) && $available[$stat]['enabled'] === TRUE)
		{
			$query = $this->db->query('
				SELECT *
				FROM ' . $this->db->protect_identifiers('statistics', TRUE) . '
				WHERE board_id = ? AND name = ?
				LIMIT 0,1
			', array($selected_board->id, $stat));
			if ($query->num_rows() != 1)
			{
				return FALSE;
			}

			$result = $query->result();
			return array('info' => $available[$stat], 'data' => $result[0]->data, 'timestamp' => $result[0]->timestamp);
		}
		return FALSE;
	}


	/**
	 * Run a cron to update the statistics of all boards.
	 *
	 * @param int $id
	 */
	function cron($id = NULL)
	{
		$boards = new Board();
		$boards->get();

		$available = $this->get_available_stats();

		/**
		 * Obtain all of the statistics already stored on the database to check for update frequency.
		 */
		$stats = $this->db->query('
			SELECT
				board_id, name, timestamp
			FROM ' . $this->db->protect_identifiers('statistics', TRUE) . '
			ORDER BY timestamp DESC
		');

		/**
		 * Obtain the list of all statistics enabled.
		 */
		$avail = array();
		foreach ($available as $k => $a)
		{
			$avail[] = $k;
		}

		foreach ($boards->all as $board)
		{
			if(!is_null($id) && $id != $board->id)
				continue;

			/**
			 * Update all statistics for the specified board or current board.
			 */
			echo $board->shortname . ' (' . $board->id . ')' . PHP_EOL;
			foreach ($available as $k => $a)
			{
				echo '  ' . $k . ' ';
				$found = FALSE; $skip = FALSE;
				foreach ($stats->result() as $r)
				{
					/**
					 * Determine if the statistics already exists or that the information is outdated.
					 */
					if ($r->board_id == $board->id && $r->name == $k)
					{
						/**
						 * This statistics report has run once already.
						 */
						$found = TRUE;

						/**
						 * This statistics report has not reached its frequency EOL.
						 */
						if ((time() - strtotime($r->timestamp)) <= $a['frequence'])
						{
							$skip = TRUE;
							continue;
						}

						/**
						 * This statistics report has another process locked.
						 */
						if (!$this->lock_stat($r->board_id, $k, $r->timestamp))
						{
							continue;
						}
						break;
					}
				}

				/**
				 * This is an extremely rare case; however, this should avoid encountering any
				 * racing conditions with our cron.
				 */
				if ($found === FALSE)
				{
					$this->save_stat($board->id, $k, date('Y-m-d H:i:s', time() + 600), '');
				}

				/**
				 * We were able to obtain a LOCK on the statistics report and has already reached the
				 * targeted frequency time.
				 */
				if ($skip === FALSE)
				{
					echo '* Processing...';
					$process = 'process_' . $k;
					$this->db->reconnect();
					$result = $this->$process($board);

					/**
					 * This statistics report generates a graph via GNUPLOT.
					 */
					if (isset($this->stats[$k]['gnuplot']) && is_array($result) && !empty($result))
					{
						$this->graph_gnuplot($board->shortname, $k, $result);
					}

					/**
					 * Save the statistics report in a JSON array.
					 */
					$this->save_stat($board->id, $k, date('Y-m-d H:i:s'), $result);
				}

				echo PHP_EOL;
			}
		}
	}


	function get_stat($board_id, $name)
	{
		$stat = $this->db->query('
			SELECT *
			FROM ' . $this->db->protect_identifiers('statistics', TRUE) . '
			WHERE board_id = ? and name = ?
		', array($board_id, $name));

		if($stat->num_rows() == 0)
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
	function lock_stat($board_id, $name, $temp_timestamp)
	{
		// again, to avoid racing conditions, let's also check that the timestamp hasn't been changed
		$this->db->query('
			UPDATE ' . $this->db->protect_identifiers('statistics', TRUE) . '
			SET timestamp = ?
			WHERE board_id = ? AND name = ? AND timestamp = ?
		', array(date('Y-m-d H:i:s', time() + 600), $board_id, $name, $temp_timestamp)); // hopefully 10 minutes is enough for everything

		if ($this->db->affected_rows() != 1)
			return FALSE;

		return TRUE;
	}


	function save_stat($board_id, $name, $timestamp, $data = '')
	{
		$this->db->query('
			INSERT
			INTO ' . $this->db->protect_identifiers('statistics', TRUE) . '
			(board_id, name, timestamp, data)
			VALUES
				(?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE
				timestamp = VALUES(timestamp), data = VALUES(data);
		', array($board_id, $name, $timestamp, json_encode($data)));
	}


	function process_availability($board)
	{
		$query = $this->db->query('
				SELECT
					name, trip, COUNT(num) AS posts,
					AVG(timestamp%86400) AS avg1,
					STD(timestamp%86400) AS std1,
					(AVG((timestamp+43200)%86400)+43200)%86400 avg2,
					STD((timestamp+43200)%86400) AS std2
				FROM ' . $this->get_table($board) . '
				WHERE timestamp > ?
				GROUP BY name, trip
				HAVING count(*) > 4
				ORDER BY name, trip
		', array(time() - 2592000));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	function process_daily_activity($board)
	{
		$query = $this->db->query('
			SELECT
				(FLOOR(timestamp/300)%288)*300 AS time, COUNT(*), COUNT(media_hash),
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)
			FROM ' . $this->get_table($board) . '
			USE INDEX(timestamp_index)
			WHERE timestamp > ?
			GROUP BY FLOOR(timestamp/300)%288
			ORDER BY FLOOR(timestamp/300)%288;
		', array(time() - 86400));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	function process_daily_activity_archive($board)
	{
		$query = $this->db->query('
			SELECT
				((FLOOR(timestamp/3600)%24)*3600)+1800 AS time, COUNT(*),
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)
			FROM ' . $this->get_table($board) . '
			USE INDEX(timestamp_index)
			WHERE timestamp> ? AND subnum != 0
			GROUP BY FLOOR(timestamp/3600)%24
			ORDER BY FLOOR(timestamp/3600)%24;
		', array(time() - 86400));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	function process_daily_activity_hourly($board)
	{
		$query = $this->db->query('
			SELECT
				((FLOOR(timestamp/3600)%24)*3600)+1800 AS time, COUNT(*), COUNT(media_hash),
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)
			FROM ' . $this->get_table($board) . '
			USE INDEX(timestamp_index)
			WHERE timestamp > ?
			GROUP BY FLOOR(timestamp/3600)%24
			ORDER BY FLOOR(timestamp/3600)%24;
		', array(time() - 86400));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	function process_image_reposts($board)
	{
		$query = $this->db->query('
			SELECT
				media_hash AS hash, COUNT(media_hash) AS total
			FROM ' . $this->get_table($board) . '
			WHERE media_hash IS NOT NULL
			GROUP BY media_hash
			ORDER BY COUNT(media_hash) DESC
			LIMIT 32
		');

		$sql = array();
		foreach ($query->result() as $row)
		{
			$sql[] = '
				(
					SELECT
						preview, media_filename, num, subnum, parent, media_hash, COUNT(media_hash) as total
					FROM
						(
							SELECT
								preview, media_filename, preview_w, num, subnum, parent, media_hash
							FROM ' . $this->get_table($board) . '
							USE INDEX(media_hash)
							WHERE media_hash = ' . $this->db->escape($row->hash) . '
							ORDER BY preview_w DESC
						) as x
				)
			';
		}
		$query->free_result();

		$query = $this->db->query('
			' . implode('UNION', $sql) . '
			ORDER BY total DESC
		');

		$array = $query->result();
		$query->free_result();
		return $array;
	}


	function process_karma($board)
	{
		$query = $this->db->query('
			SELECT
				FLOOR(timestamp/86400)*86400 AS time, COUNT(*), COUNT(media_hash),
				COUNT(CASE email WHEN \'sage\' THEN 1 ELSE NULL END)
			FROM ' . $this->get_table($board) . '
			FORCE INDEX(timestamp_index)
			WHERE timestamp > ?
			GROUP BY time
			ORDER BY time;
		', array(time() - 31536000));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	function process_new_tripfriends($board)
	{
		$query = $this->db->query('
			SELECT *
			FROM
				(
					SELECT
						name, trip, MIN(timestamp) AS firstseen, COUNT(num) AS postcount
					FROM ' . $this->get_table($board) . '
					GROUP BY trip
				) as l
			WHERE l.postcount > 30
			ORDER BY firstseen DESC;
		');

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	function process_population($board)
	{
		$query = $this->db->query('
			SELECT
				FLOOR(timestamp/86400)*86400 as time, COUNT(trip),
				COUNT(CASE WHEN (name != \'Anonymous\' AND trip IS NULL) THEN 1 ELSE NULL END),
				COUNT(CASE WHEN (name = \'Anonymous\' AND trip IS NULL) THEN 1 ELSE NULL END)
			FROM ' . $this->get_table($board) . '
			FORCE INDEX(timestamp_index)
			WHERE timestamp > ?
			GROUP BY time
			ORDER BY time
		', array(time() - 31536000));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	function process_post_count($board)
	{
		$query = $this->db->query('
			SELECT
				name, trip, COUNT(*)
			FROM ' . $this->get_table($board) . '
			GROUP BY name, trip
			ORDER BY count(*) DESC
			LIMIT 512
		');

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	function process_post_rate($board)
	{
		$query = $this->db->query('
			SELECT
				COUNT(*), COUNT(*)/60
			FROM ' . $this->get_table($board) . '
			WHERE timestamp > ?
		', array(time() - 3600));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	function process_post_rate_archive($board)
	{
		$query = $this->db->query('
			SELECT
				COUNT(*), COUNT(*)/60
			FROM ' . $this->get_table($board) . '
			WHERE timestamp > ? AND subnum != 0
		', array(time() - 3600));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	function process_users_online($board)
	{
		$query = $this->db->query('
			SELECT name, trip, MAX(timestamp), num, subnum
			FROM ' . $this->get_table($board) . '
			WHERE timestamp > ?
			GROUP BY name, trip
			ORDER BY MAX(timestamp) DESC
		', array(time() - 1800));

		$array = $query->result_array();
		$query->free_result();
		return $array;
	}


	function process_users_online_internal($board)
	{
		$query = $this->db->query('
			SELECT
				GROUP_CONCAT(DISTINCT CONCAT(name) SEPARATOR \', \'), MAX(timestamp), num, subnum
			FROM ' . $this->get_table($board) . '
			WHERE id != 0 AND timestamp > ?
			GROUP BY id
			ORDER BY MAX(timestamp) DESC
		', array(time() - 3600));

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
	function graph_gnuplot($board, $stat, $data)
	{
		/**
		 * Create all missing directory paths for statistics.
		 */
		if(!file_exists(FCPATH . 'content/cache/'))
		{
			mkdir(FCPATH . 'content/cache/');
		}
		if(!file_exists(FCPATH . 'content/statistics/'))
		{
			mkdir(FCPATH . 'content/statistics/');
		}
		if(!file_exists(FCPATH . 'content/statistics/' . $board . '/'))
		{
			mkdir(FCPATH . 'content/statistics/' . $board . '/');
		}

		/**
		 * Set PATH for INFILE, GNUFILE, and OUTFILE for read/write.
		 */
		$INFILE  = FCPATH . 'content/cache/statistics-' . $board . '-' . $stat . '.dat';
		$GNUFILE = FCPATH . 'content/cache/statistics-' . $board . '-' . $stat . '.gnu';
		$OUTFILE = FCPATH . 'content/statistics/' . $board . '/' . $stat . '.png';

		/**
		 * Obtain starting and ending data points for x range.
		 */
		$X_START = (!empty($data) ? $data[0]['time'] : 0);
		$X_END   = (!empty($data) ? $data[count($data) - 1]['time'] : 0);

		/**
		 * Format and save the INFILE dataset for GNUPLOT.
		 */
		$graph_data = array();
		foreach ($data as $line) {
			$graph_data[] = implode("\t", $line);
		}
		$graph_data = implode("\n", $graph_data);
		write_file($INFILE, $graph_data);

		/**
		 * Set template variables for replacement.
		 */
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

		$template = str_replace($template_vars, $template_vals, $this->generate_gnuplot_template($stat));
		write_file($GNUFILE, $template);

		/**
		 * Execute GNUPLOT with GNUFILE input.
		 */
		$result = @exec('/usr/bin/gnuplot ' . $GNUFILE);
		return $result;
	}


	function generate_gnuplot_template($stat)
	{
		$options = $this->stats[$stat]['gnuplot'];

		$template   = array();
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